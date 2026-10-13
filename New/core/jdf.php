<?php
/**
 * Minimal Jalali date functions
 */
function div($a, $b) {
    return (int) ($a / $b);
}

function gregorian_to_jalali($g_y, $g_m, $g_d) {
    $gy = $g_y - 1600;
    $gm = $g_m - 1;
    $gd = $g_d - 1;
    $g_day_no = 365 * $gy + div($gy + 3, 4) - div($gy + 99, 100) + div($gy + 399, 400);
    for ($i = 0; $i < $gm; ++$i) {
        $g_day_no += [31,28,31,30,31,30,31,31,30,31,30,31][$i];
    }
    if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0))) {
        $g_day_no++;
    }
    $g_day_no += $gd;
    $j_day_no = $g_day_no - 79;
    $j_np = div($j_day_no, 12053);
    $j_day_no %= 12053;
    $jy = 979 + 33 * $j_np + 4 * div($j_day_no, 1461);
    $j_day_no %= 1461;
    if ($j_day_no >= 366) {
        $jy += div($j_day_no - 1, 365);
        $j_day_no = ($j_day_no - 1) % 365;
    }
    $j_month = 0;
    $days_in_month = [31,31,31,31,31,31,30,30,30,30,30,29];
    for ($i = 0; $i < 12 && $j_day_no >= $days_in_month[$i]; ++$i) {
        $j_day_no -= $days_in_month[$i];
        $j_month++;
    }
    return [$jy, $j_month + 1, $j_day_no + 1];
}

function jdate(string $format, $timestamp = null) {
    $time = $timestamp ?? time();
    $g_y = (int) date('Y', $time);
    $g_m = (int) date('m', $time);
    $g_d = (int) date('d', $time);
    list($j_y, $j_m, $j_d) = gregorian_to_jalali($g_y, $g_m, $g_d);
    $replace = [
        'Y' => $j_y,
        'y' => substr($j_y, -2),
        'm' => str_pad($j_m, 2, '0', STR_PAD_LEFT),
        'n' => $j_m,
        'd' => str_pad($j_d, 2, '0', STR_PAD_LEFT),
        'j' => $j_d,
        'md' => str_pad($j_m, 2, '0', STR_PAD_LEFT) . str_pad($j_d, 2, '0', STR_PAD_LEFT),
    ];
    return strtr($format, $replace);
}