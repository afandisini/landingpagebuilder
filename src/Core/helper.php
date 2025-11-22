<?php
function shortNumber($num) {
    $num = (float)$num;

    if ($num >= 1000000000000) { // 1 Triliun
        return round($num / 1000000000000, 1) . 'T';
    }
    if ($num >= 1000000000) { // 1 Miliar
        return round($num / 1000000000, 1) . 'M';
    }
    if ($num >= 1000000) { // 1 Juta
        return round($num / 1000000, 1) . 'jt';
    }
    if ($num >= 1000) { // 1 Ribu
        return round($num / 1000, 1) . 'k';
    }

    return number_format($num, 0, ',', '.');
}
