<?php
/**
 * Dışa aktarım (Excel, JSON zip, kargo): telefonu tek tipe indirger.
 * Rakam çıkarılabiliyorsa sadece rakamlar; değilse trimlenmiş ham metin (+, boşluk vb. rakamlarla gider).
 */
function panel_export_normalize_phone($val) {
    $raw = trim((string) $val);
    if ($raw === '') {
        return '';
    }
    $digits = preg_replace('/\D+/', '', $raw);
    return $digits !== '' ? $digits : $raw;
}
