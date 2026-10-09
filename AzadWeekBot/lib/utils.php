<?php
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Access Denied');
}

function convertPersianToArabic($input) {
    return str_replace(
        ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
        ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        (string)$input
    );
}

function isValidJalaliDateString($jalali_date) {
    $clean = convertPersianToArabic(trim((string)$jalali_date));
    if (!preg_match('/^(1[34]\d{2})\/(\d{1,2})\/(\d{1,2})$/', $clean, $m)) {
        return false;
    }
    $y = (int)$m[1];
    $mo = (int)$m[2];
    $d = (int)$m[3];
    if (function_exists('jcheckdate')) {
        return (bool)jcheckdate($mo, $d, $y);
    }
    return ($mo >= 1 && $mo <= 12 && $d >= 1 && $d <= 31);
}

function jalaliToTimestamp($jalali_date) {
    $clean = convertPersianToArabic(trim((string)$jalali_date));
    $parts = explode('/', $clean);
    if (count($parts) !== 3) return 0;
    list($year, $month, $day) = array_map('intval', $parts);
    if (function_exists('jcheckdate') && !jcheckdate($month, $day, $year)) {
        return 0;
    }
    return (int)jmktime(0, 0, 0, $month, $day, $year);
}

function getCompiledWeeksConfig() {
    static $compiled = null;
    if ($compiled !== null) {
        return $compiled;
    }

    global $weeks_config;
    $compiled = [];
    if (!is_array($weeks_config)) {
        return $compiled;
    }

    foreach ($weeks_config as $index => $week) {
        $start_ts = jalaliToTimestamp($week['start']);
        $end_parts = array_map('intval', explode('/', convertPersianToArabic($week['end'])));
        $end_ts = count($end_parts) === 3
            ? (int)jmktime(23, 59, 59, $end_parts[1], $end_parts[2], $end_parts[0])
            : ($start_ts + 604799);

        $compiled[] = [
            'index'    => $index,
            'week'     => $week,
            'start_ts' => $start_ts,
            'end_ts'   => $end_ts,
        ];
    }

    return $compiled;
}

function getWeekInfo($date) {
    if (!isValidJalaliDateString($date)) return null;
    $input_timestamp = jalaliToTimestamp($date);
    if ($input_timestamp <= 0) return null;

    foreach (getCompiledWeeksConfig() as $item) {
        if ($input_timestamp >= $item['start_ts'] && $input_timestamp <= $item['end_ts']) {
            return $item['week'];
        }
    }
    return null;
}

function get_week_number($date) {
    if (!isValidJalaliDateString($date)) return null;
    $timestamp = jalaliToTimestamp($date);
    if ($timestamp <= 0) return null;

    foreach (getCompiledWeeksConfig() as $item) {
        if ($timestamp >= $item['start_ts'] && $timestamp <= $item['end_ts']) {
            return $item['index'] + 1;
        }
    }
    return null;
}

function getNextWeekStatus() {
    $today_timestamp = time();
    foreach (getCompiledWeeksConfig() as $item) {
        if ($item['start_ts'] > $today_timestamp) {
            $days_until_start = ($item['start_ts'] - $today_timestamp) / 86400;
            if ($days_until_start <= 7) {
                return $item['week'];
            }
        }
    }
    return null;
}
