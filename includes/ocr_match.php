<?php
// includes/ocr_match.php — SMART DRUG MATCHING ENGINE
// Takes raw OCR text from a scanned prescription and matches it
// against the medicines table using tokenisation + fuzzy string matching.
// This is deliberately dependency-free (pure PHP) so it runs on any
// stock XAMPP install with no extra PECL/composer packages.

/**
 * Break OCR text into candidate "drug name" tokens.
 * Prescriptions are messy (handwriting OCR is noisy), so we:
 *  - split on newlines / common separators
 *  - strip dosage/frequency noise (mg, ml, tablet, 1+0+1, etc.)
 *  - keep lines that look like they contain a word (drug name)
 */
function extractCandidateLines($ocrText) {
    $lines = preg_split('/[\r\n]+/', $ocrText);
    $candidates = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        // Skip obvious non-drug lines (dates, doctor names, headers)
        if (preg_match('/^(dr\.?|dt\.?|date|patient|age|sex|address|reg\.?\s*no|hospital|clinic)\b/i', $line)) {
            continue;
        }

        // Remove common dosage / instruction noise so the drug name is isolated
        $stripped = preg_replace(
            '/\b(\d+\s*(mg|ml|mcg|g|gm|iu)\b|\d+\s*\+\s*\d+\s*\+\s*\d+|\btab(let)?s?\b|\bcap(sule)?s?\b|\bsyrup\b|\bonce\b|\btwice\b|\bdaily\b|\bbd\b|\btds\b|\bod\b|\bqid\b|\bafter\s+meal\b|\bbefore\s+meal\b|\bx\s*\d+\s*days?\b)/i',
            '',
            $line
        );
        $stripped = trim(preg_replace('/\s+/', ' ', $stripped));
        $stripped = trim($stripped, " -.:,");

        if (strlen($stripped) >= 3) {
            $candidates[] = $stripped;
        }
    }

    // De-duplicate while preserving order
    return array_values(array_unique($candidates));
}

/**
 * Fuzzy-match a candidate line against a list of medicine names.
 * Returns [medicine_row, score 0-100] or [null, 0] if nothing close enough.
 * Uses similar_text() percentage + a Levenshtein sanity check.
 */
function fuzzyMatchMedicine($candidate, $medicineRows) {
    $best = null;
    $bestScore = 0;

    $candidateNorm = strtolower(preg_replace('/[^a-z0-9 ]/i', '', $candidate));

    foreach ($medicineRows as $med) {
        $nameNorm = strtolower(preg_replace('/[^a-z0-9 ]/i', '', $med['name']));

        // Quick containment boost (handles OCR truncation like "Paracet" -> "Paracetamol")
        $containBoost = 0;
        if ($candidateNorm !== '' && (str_contains($nameNorm, $candidateNorm) || str_contains($candidateNorm, $nameNorm))) {
            $containBoost = 15;
        }

        similar_text($candidateNorm, $nameNorm, $pct);
        $score = min(100, $pct + $containBoost);

        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $med;
        }
    }

    // Require a reasonably confident match to avoid false positives
    if ($bestScore >= 55) {
        return [$best, round($bestScore, 1)];
    }
    return [null, 0];
}

/**
 * Full pipeline: OCR text -> matched medicines with availability.
 * Returns array of rows: candidate_text, medicine (or null), score, available_qty
 */
function matchPrescriptionText($conn, $ocrText) {
    $candidates = extractCandidateLines($ocrText);

    $medRes = mysqli_query($conn, "SELECT id, name, quantity, price, expiry_date FROM medicines");
    $medicineRows = [];
    while ($row = mysqli_fetch_assoc($medRes)) $medicineRows[] = $row;

    $results = [];
    foreach ($candidates as $c) {
        [$match, $score] = fuzzyMatchMedicine($c, $medicineRows);
        $results[] = [
            'candidate_text' => $c,
            'medicine'       => $match,
            'score'          => $score,
            'available_qty'  => $match ? (int)$match['quantity'] : 0,
        ];
    }
    return $results;
}
