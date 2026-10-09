<?php
@ini_set('display_errors', '0');
error_reporting(0);
ob_start();

$action = $_POST['action'] ?? $_GET['action'] ?? 'init';

if ($action === 'download_ics' || $action === 'download_card' || $action === 'user_avatar') {
} else {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
}
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') exit;

function aw_json($payload, int $code = 200): void {
    while (ob_get_level() > 0 && ob_get_length() > 0) {
        @ob_clean();
        break;
    }
    http_response_code($code);
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function aw_resolve_bot_root(): string {
    $env = getenv('AZAD_WEEK_BOT_ROOT');
    $candidates = array_filter([
        $env ?: null,
        __DIR__ . '/../../University/AzadWeekBot',
        __DIR__ . '/../AzadWeekBot',
        __DIR__ . '/../../AzadWeekBot',
        dirname(__DIR__) . '/AzadWeekBot',
        ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/University/AzadWeekBot',
        ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/AzadWeekBot',
    ]);
    foreach ($candidates as $candidate) {
        $real = realpath($candidate);
        if ($real && is_file($real . '/config.php') && is_file($real . '/jdf.php') && is_file($real . '/lib/utils.php')) {
            return $real;
        }
    }
    return '';
}

$bot_root = aw_resolve_bot_root();
if ($bot_root === '') {
    aw_json([
        'status' => 'error',
        'message' => 'Config file not found. Set AZAD_WEEK_BOT_ROOT or place AzadWeekBot in the expected path.'
    ], 500);
}

include_once $bot_root . '/config.php';
include_once $bot_root . '/jdf.php';
include_once $bot_root . '/lib/utils.php';
if (is_file($bot_root . '/lib/api.php')) {
    include_once $bot_root . '/lib/api.php';
}

function aw_public_base_url(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $scheme . '://' . $host . ($dir ? $dir : '');
}

function aw_generated_cards_dir(): string {
    return __DIR__ . '/assets/generated_cards';
}

function aw_generated_cards_url_base(): string {
    return aw_public_base_url() . '/assets/generated_cards';
}

function aw_prepare_generated_cards_dir(): bool {
    $dir = aw_generated_cards_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return false;
    $index = $dir . '/index.html';
    if (!is_file($index)) @file_put_contents($index, '');
    $htaccess = $dir . '/.htaccess';
    if (!is_file($htaccess)) @file_put_contents($htaccess, "<FilesMatch \"\\.php$\">\nDeny from all\n</FilesMatch>\n");
    return is_dir($dir) && is_writable($dir);
}

function aw_cleanup_generated_cards(int $max_age = 21600): void {
    $dir = aw_generated_cards_dir();
    if (!is_dir($dir)) return;
    $now = time();
    foreach (glob($dir . '/aw_card_*.*') ?: [] as $file) {
        if (is_file($file) && ($now - @filemtime($file)) > $max_age) @unlink($file);
    }
}

function aw_safe_generated_card_url(string $url): string {
    $url = trim($url);
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) return '';
    $base = aw_generated_cards_url_base() . '/';
    if (strpos($url, $base) !== 0) return '';
    $path = parse_url($url, PHP_URL_PATH) ?: '';
    if (!preg_match('~/assets/generated_cards/aw_card_[a-f0-9]{16,64}\.(png|jpg|webp)$~i', $path)) return '';
    return $url;
}

function aw_store_uploaded_card(string $data_url, string $type = 'status'): array {
    aw_cleanup_generated_cards();
    if (!aw_prepare_generated_cards_dir()) return ['ok' => false, 'message' => 'Generated cards directory is not writable'];
    $data_url = trim($data_url);
    if (!preg_match('~^data:image/(png|jpeg|jpg|webp);base64,([A-Za-z0-9+/=\r\n]+)$~i', $data_url, $m)) {
        return ['ok' => false, 'message' => 'Invalid image format'];
    }
    $mime_type = strtolower($m[1]);
    $ext = ($mime_type === 'jpeg') ? 'jpg' : $mime_type;
    $bin = base64_decode(str_replace(["\r", "\n"], '', $m[2]), true);
    if ($bin === false || strlen($bin) < 256) return ['ok' => false, 'message' => 'Empty image data'];
    if (strlen($bin) > 8 * 1024 * 1024) return ['ok' => false, 'message' => 'Image is too large'];

    $name = 'aw_card_' . bin2hex(random_bytes(16)) . '.' . $ext;
    $file = aw_generated_cards_dir() . '/' . $name;
    if (@file_put_contents($file, $bin, LOCK_EX) === false) return ['ok' => false, 'message' => 'Could not save image'];
    @chmod($file, 0644);
    return [
        'ok' => true,
        'url' => aw_generated_cards_url_base() . '/' . $name,
        'download_url' => aw_public_base_url() . '/connect.php?action=download_card&file=' . rawurlencode($name),
        'file' => $file,
        'name' => $name,
        'type' => $type === 'wrapped' ? 'wrapped' : 'status',
        'bytes' => strlen($bin),
        'ext' => $ext,
    ];
}

function aw_normalize_jalali_date(string $date): string {
    $date = convertPersianToArabic(trim($date));
    $date = str_replace(['-', '.', ' '], '/', $date);
    if (!preg_match('/^(1[34]\d{2})\/(\d{1,2})\/(\d{1,2})$/', $date, $m)) return '';
    $y = (int)$m[1];
    $mo = (int)$m[2];
    $d = (int)$m[3];
    if (function_exists('jcheckdate') && !jcheckdate($mo, $d, $y)) return '';
    if ($mo < 1 || $mo > 12 || $d < 1 || $d > 31) return '';
    return sprintf('%04d/%02d/%02d', $y, $mo, $d);
}

function aw_week_payload(array $week, int $index, int $now): array {
    $start_ts = jalaliToTimestamp($week['start']);
    $parts = explode('/', $week['end']);
    $end_ts = jmktime(23, 59, 59, (int)$parts[1], (int)$parts[2], (int)$parts[0]);
    return [
        'id' => $index + 1,
        'start' => $week['start'],
        'end' => $week['end'],
        'status' => $week['توضیح'],
        'is_current' => ($now >= $start_ts && $now <= $end_ts),
        'start_ts' => $start_ts,
        'end_ts' => $end_ts,
    ];
}

function aw_all_weeks(): array {
    global $weeks_config;
    $now = time();
    $weeks = [];
    foreach ($weeks_config as $i => $week) $weeks[] = aw_week_payload($week, $i, $now);
    return $weeks;
}

function aw_current_week(array $weeks): ?array {
    foreach ($weeks as $w) if (!empty($w['is_current'])) return $w;
    return null;
}

function aw_next_week(array $weeks): ?array {
    $now = time();
    foreach ($weeks as $w) if (($w['start_ts'] ?? 0) > $now) return $w;
    return null;
}

function aw_days_between(int $from, int $to): int {
    return max(0, (int)ceil(($to - $from) / 86400));
}

function aw_init_payload(array $verified_user = [], string $raw_init_data = ''): array {
    $today = jdate('Y/m/d', '', '', 'Asia/Tehran', 'en');
    $today_day = jdate('l', '', '', 'Asia/Tehran', 'fa');
    $today_readable = jdate('j F Y', '', '', 'Asia/Tehran', 'fa');
    $week_info = getWeekInfo($today);
    $weeks = aw_all_weeks();
    $current = aw_current_week($weeks);
    $next = aw_next_week($weeks);
    $now = time();
    $term_start = $weeks[0] ?? null;
    $term_end = $weeks ? $weeks[count($weeks) - 1] : null;

    $term_start_ts = $term_start['start_ts'] ?? 0;
    $term_end_ts = $term_end['end_ts'] ?? 0;
    $total_duration = max(1, $term_end_ts - $term_start_ts);
    $elapsed_seconds = ($term_start_ts > 0 && $term_end_ts > $term_start_ts)
        ? max(0, min($total_duration, $now - $term_start_ts))
        : 0;
    $progress_percent = ($now < $term_start_ts)
        ? 0
        : (($now >= $term_end_ts && $term_end_ts > 0) ? 100 : (int)round(($elapsed_seconds / $total_duration) * 100));

    $avatar_proxy = ($raw_init_data !== '' && !empty($verified_user['id']))
        ? (aw_public_base_url() . '/connect.php?action=user_avatar&initData=' . rawurlencode($raw_init_data))
        : '';

    $user_prefs = ['reminder' => true];
    if (!empty($verified_user['id'])) {
        $pref_file = $GLOBALS['bot_root'] . '/miniapp_preferences.json';
        if (is_file($pref_file)) {
            $all_prefs = json_decode((string)@file_get_contents($pref_file), true);
            if (is_array($all_prefs) && isset($all_prefs[(string)$verified_user['id']])) {
                $user_prefs = array_merge($user_prefs, (array)$all_prefs[(string)$verified_user['id']]);
            }
        }
    }

    return [
        'status' => 'success',
        'today' => $today,
        'today_day' => $today_day,
        'today_readable' => $today_readable,
        'today_status' => $week_info ? $week_info['توضیح'] : 'خارج از بازه ترم',
        'user' => !empty($verified_user) ? [
            'id' => $verified_user['id'] ?? null,
            'first_name' => $verified_user['first_name'] ?? '',
            'last_name' => $verified_user['last_name'] ?? '',
            'username' => $verified_user['username'] ?? '',
            'photo_url' => $verified_user['photo_url'] ?? '',
            'avatar_proxy_url' => $avatar_proxy,
        ] : null,
        'preferences' => $user_prefs,
        'weeks' => array_map(function ($w) {
            unset($w['start_ts'], $w['end_ts']);
            return $w;
        }, $weeks),
        'current_week' => $current ? [
            'id' => $current['id'],
            'start' => $current['start'],
            'end' => $current['end'],
            'status' => $current['status'],
            'days_to_end' => aw_days_between($now, $current['end_ts']),
        ] : null,
        'next_week' => $next ? [
            'id' => $next['id'],
            'start' => $next['start'],
            'status' => $next['status'],
            'days_to_start' => aw_days_between($now, $next['start_ts']),
        ] : null,
        'term' => [
            'start' => $term_start['start'] ?? null,
            'end' => $term_end['end'] ?? null,
            'total_weeks' => count($weeks),
            'days_to_end' => ($current && $term_end) ? aw_days_between($now, $term_end['end_ts']) : 0,
            'progress_percent' => $progress_percent,
        ],
        'urls' => [
            'base' => aw_public_base_url(),
            'ics' => aw_public_base_url() . '/connect.php?action=download_ics',
            'card' => aw_public_base_url() . '/card.php',
        ],
        'features' => [
            'share_card_webp' => true,
            'wrapped' => true,
            'advanced_ics' => true,
            'shake_refresh_plus' => true,
            'secure_preferences' => true,
            'mobile_fullscreen_only' => true,
        ],
    ];
}

function aw_validate_init_data(?string $init_data): array {
    if (!$init_data || !defined('BOT_TOKEN')) return ['ok' => false, 'reason' => 'missing_init_data'];
    parse_str($init_data, $data);
    if (empty($data['hash'])) return ['ok' => false, 'reason' => 'missing_hash'];
    $hash = $data['hash'];
    unset($data['hash']);
    ksort($data);
    $pairs = [];
    foreach ($data as $k => $v) $pairs[] = $k . '=' . $v;
    $check_string = implode("\n", $pairs);
    $secret_key = hash_hmac('sha256', BOT_TOKEN, 'WebAppData', true);
    $calculated_hash = hash_hmac('sha256', $check_string, $secret_key);
    if (!hash_equals($calculated_hash, $hash)) return ['ok' => false, 'reason' => 'invalid_hash'];
    if (!empty($data['auth_date']) && is_numeric($data['auth_date'])) {
        $age = time() - (int)$data['auth_date'];
        if ($age > 172800 || $age < -300) {
            return ['ok' => false, 'reason' => 'expired_init_data'];
        }
    }
    $user = [];
    if (!empty($data['user'])) {
        $decoded = json_decode($data['user'], true);
        if (is_array($decoded)) $user = $decoded;
    }
    return ['ok' => true, 'data' => $data, 'user' => $user];
}

function aw_bot_api(string $method, array $params): array {
    if (!defined('BOT_TOKEN') || BOT_TOKEN === '') return ['ok' => false, 'description' => 'BOT_TOKEN is not defined'];
    $url = 'https://api.telegram.org/bot' . BOT_TOKEN . '/' . $method;
    $payload = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false || $raw === '') return ['ok' => false, 'description' => $err ?: 'Empty Bot API response'];
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 10,
            ]
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false || $raw === '') return ['ok' => false, 'description' => 'Bot API request failed'];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : ['ok' => false, 'description' => 'Invalid Bot API response'];
}

function aw_status_text_en(string $status): string {
    if (strpos($status, 'فرد') !== false || stripos($status, 'odd') !== false) return 'ODD WEEK';
    if (strpos($status, 'زوج') !== false || stripos($status, 'even') !== false) return 'EVEN WEEK';
    if (strpos($status, 'خارج') !== false || stripos($status, 'out') !== false) return 'OUT OF TERM';
    return 'WEEK STATUS';
}

function aw_escape_html(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function aw_escape_ics(string $text): string {
    $text = str_replace(["\r\n", "\n", "\r"], '\\n', $text);
    return str_replace(['\\', ';', ','], ['\\\\', '\\;', '\\,'], $text);
}

function aw_fold_ics_line(string $line): string {
    $out = '';
    while (strlen($line) > 73) {
        $out .= substr($line, 0, 73) . "\r\n ";
        $line = substr($line, 73);
    }
    return $out . $line;
}

function aw_jalali_date_to_ymd(string $date): string {
    $ts = jalaliToTimestamp($date);
    return date('Ymd', $ts);
}

function aw_ics_event(string $uid, string $summary, string $start_jalali, string $end_jalali, string $description = '', bool $alarm = true): array {
    $start = aw_jalali_date_to_ymd($start_jalali);
    $end_ts = jalaliToTimestamp($end_jalali) + 86400;
    $end = date('Ymd', $end_ts);
    $lines = [
        'BEGIN:VEVENT',
        'UID:' . $uid,
        'DTSTAMP:' . gmdate('Ymd\THis\Z'),
        'SUMMARY:' . aw_escape_ics($summary),
        'DTSTART;VALUE=DATE:' . $start,
        'DTEND;VALUE=DATE:' . $end,
        'DESCRIPTION:' . aw_escape_ics($description),
        'TRANSP:TRANSPARENT',
    ];
    if ($alarm) {
        $lines[] = 'BEGIN:VALARM';
        $lines[] = 'TRIGGER:-PT12H';
        $lines[] = 'ACTION:DISPLAY';
        $lines[] = 'DESCRIPTION:' . aw_escape_ics('یادآور آزادویک: ' . $summary);
        $lines[] = 'END:VALARM';
    }
    $lines[] = 'END:VEVENT';
    return $lines;
}

function aw_generate_ics(): string {
    global $weeks_config;
    $lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//AzadWeekBot//AzadWeek Mini App//FA',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'X-WR-CALNAME:آزادویک - تقویم هفته‌های فرد و زوج',
        'X-WR-CALDESC:تقویم آموزشی هفته‌های فرد و زوج دانشگاه آزاد در آزادویک',
    ];
    foreach ($weeks_config as $i => $week) {
        $summary = 'هفته ' . ($i + 1) . ' آموزشی (' . $week['توضیح'] . ')';
        $desc = 'بازه هفته: ' . $week['start'] . ' تا ' . $week['end'] . ' - ربات آزادویک';
        $lines = array_merge($lines, aw_ics_event('azadweek-week-' . ($i + 1) . '@azadweekbot', $summary, $week['start'], $week['end'], $desc, true));
    }
    if (!empty($weeks_config)) {
        $first = $weeks_config[0];
        $last = $weeks_config[count($weeks_config) - 1];
        $lines = array_merge($lines, aw_ics_event('azadweek-term-start@azadweekbot', 'شروع ترم آموزشی', $first['start'], $first['start'], 'شروع تقویم آموزشی در آزادویک', true));
        $lines = array_merge($lines, aw_ics_event('azadweek-term-end@azadweekbot', 'پایان ترم آموزشی', $last['end'], $last['end'], 'پایان تقویم آموزشی در آزادویک', true));
    }
    $lines[] = 'END:VCALENDAR';
    return implode("\r\n", $lines) . "\r\n";
}

function aw_require_telegram_init_data(): array {
    $raw = trim((string)($_POST['initData'] ?? $_GET['initData'] ?? ''));
    if ($raw !== '') {
        $init = aw_validate_init_data($raw);
        if (!empty($init['ok'])) {
            $init['raw'] = $raw;
            return $init;
        }
    }
    $tg_platform = strtolower(trim((string)($_POST['tgPlatform'] ?? $_GET['tgPlatform'] ?? '')));
    $allowed_platforms = ['android', 'android_x', 'ios', 'tdesktop', 'macos', 'weba', 'webk', 'unigram'];
    if (in_array($tg_platform, $allowed_platforms, true)) {
        return ['ok' => true, 'data' => [], 'user' => [], 'raw' => ''];
    }
    aw_json([
        'status' => 'telegram_only',
        'message' => 'این برنامه فقط از داخل تلگرام قابل اجرا است.',
        'bot_url' => 'https://t.me/' . (defined('BOT_USERNAME') ? BOT_USERNAME : 'AzadWeekBot'),
    ], 403);
}

switch ($action) {
    case 'init':
        $init = aw_require_telegram_init_data();
        aw_json(aw_init_payload($init['user'] ?? [], $init['raw'] ?? ''));
        break;

    case 'check_date':
        aw_require_telegram_init_data();
        $date_input = aw_normalize_jalali_date($_POST['date'] ?? $_GET['date'] ?? '');
        if ($date_input === '') aw_json(['status' => 'error', 'message' => 'Date is invalid or empty'], 422);
        $week_info = getWeekInfo($date_input);
        $week_number = function_exists('get_week_number') ? get_week_number($date_input) : null;
        $ts = jalaliToTimestamp($date_input);
        aw_json([
            'status' => 'success',
            'date' => $date_input,
            'day' => jdate('l', $ts),
            'week_status' => $week_info ? $week_info['توضیح'] : 'خارج از ترم',
            'week_number' => $week_number,
            'week_start' => $week_info['start'] ?? null,
            'week_end' => $week_info['end'] ?? null,
            'share_text' => 'وضعیت آزادویک: ' . $date_input . ' - ' . ($week_info ? $week_info['توضیح'] : 'خارج از ترم') . ' @' . (defined('BOT_USERNAME') ? BOT_USERNAME : 'AzadWeekBot'),
        ]);
        break;

    case 'user_avatar':
        $raw_init = (string)($_GET['initData'] ?? $_POST['initData'] ?? '');
        $init = aw_validate_init_data($raw_init);
        $uid = $init['ok'] && !empty($init['user']['id']) ? (int)$init['user']['id'] : 0;
        if ($uid <= 0) {
            http_response_code(403);
            exit;
        }
        $cache_dir = __DIR__ . '/assets/avatar_cache';
        if (!is_dir($cache_dir)) @mkdir($cache_dir, 0755, true);
        $cache_file = $cache_dir . '/uid_' . $uid . '.jpg';
        if (is_file($cache_file) && (time() - filemtime($cache_file) < 43200)) {
            header_remove('Content-Type');
            header('Content-Type: image/jpeg');
            header('Cache-Control: private, max-age=43200');
            readfile($cache_file);
            exit;
        }
        $photos = aw_bot_api('getUserProfilePhotos', ['user_id' => $uid, 'limit' => 1]);
        $first_group = $photos['result']['photos'][0] ?? null;
        if (!is_array($first_group) || empty($first_group)) {
            http_response_code(404);
            exit;
        }
        $chosen = $first_group[count($first_group) - 1] ?? $first_group[0];
        $file_id = $chosen['file_id'] ?? '';
        if ($file_id === '') {
            http_response_code(404);
            exit;
        }
        $file_info = aw_bot_api('getFile', ['file_id' => $file_id]);
        $file_path = $file_info['result']['file_path'] ?? '';
        if ($file_path === '') {
            http_response_code(404);
            exit;
        }
        $file_url = 'https://api.telegram.org/file/bot' . BOT_TOKEN . '/' . ltrim($file_path, '/');
        $img_bin = false;
        if (function_exists('curl_init')) {
            $ch = curl_init($file_url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 10,
            ]);
            $img_bin = curl_exec($ch);
            curl_close($ch);
        } else {
            $img_bin = @file_get_contents($file_url);
        }
        if ($img_bin === false || strlen($img_bin) < 64) {
            http_response_code(404);
            exit;
        }
        @file_put_contents($cache_file, $img_bin);
        header_remove('Content-Type');
        header('Content-Type: image/jpeg');
        header('Cache-Control: private, max-age=43200');
        echo $img_bin;
        exit;

    case 'download_ics':
        while (ob_get_level() > 0) @ob_end_clean();
        $ics = aw_generate_ics();
        @file_put_contents(__DIR__ . '/assets/calendar.ics', $ics);
        header_remove('Content-Type');
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="AzadWeek-Term-Calendar.ics"');
        header('Content-Length: ' . strlen($ics));
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $ics;
        exit;

    case 'download_card':
        while (ob_get_level() > 0) @ob_end_clean();
        $fname = basename(trim((string)($_GET['file'] ?? '')));
        if (!preg_match('/^aw_card_[a-f0-9]{16,64}\.(png|jpg|webp)$/i', $fname, $m)) {
            http_response_code(404);
            exit('Not found');
        }
        $fpath = aw_generated_cards_dir() . '/' . $fname;
        if (!is_file($fpath)) {
            http_response_code(404);
            exit('Not found');
        }
        $ext = strtolower($m[1]);
        $mime = $ext === 'png' ? 'image/png' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg');
        $dl_name = 'azadweek-card.' . $ext;
        header_remove('Content-Type');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $dl_name . '"');
        header('Content-Length: ' . filesize($fpath));
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: private, max-age=3600');
        readfile($fpath);
        exit;

    case 'upload_card':
        aw_require_telegram_init_data();
        $type = strtolower(trim($_POST['type'] ?? 'status')) === 'wrapped' ? 'wrapped' : 'status';
        $stored = aw_store_uploaded_card((string)($_POST['image'] ?? ''), $type);
        if (empty($stored['ok'])) aw_json(['status' => 'error', 'message' => $stored['message'] ?? 'Upload failed'], 422);
        aw_json([
            'status' => 'success',
            'url' => $stored['url'],
            'download_url' => $stored['download_url'],
            'type' => $stored['type'],
            'bytes' => $stored['bytes'],
        ]);
        break;

    case 'send_card_to_chat':
        $init = aw_require_telegram_init_data();
        $user_id = (int)($init['user']['id'] ?? ($_POST['user_id'] ?? 0));
        $type = strtolower(trim($_POST['type'] ?? 'status')) === 'wrapped' ? 'wrapped' : 'status';
        $stored = aw_store_uploaded_card((string)($_POST['image'] ?? ''), $type);
        if (empty($stored['ok'])) aw_json(['status' => 'error', 'message' => $stored['message'] ?? 'Upload failed'], 422);
        $sent_chat = false;
        if ($user_id > 0 && function_exists('curl_init') && class_exists('CURLFile') && defined('BOT_TOKEN') && BOT_TOKEN !== '') {
            $bot_username = defined('BOT_USERNAME') ? BOT_USERNAME : 'AzadWeekBot';
            $caption = ($type === 'wrapped' ? "📊 تصویر خلاصه آمار شما در آزادویک" : "🖼 تصویر وضعیت هفته در آزادویک") . "\n@" . $bot_username;
            $ch = curl_init('https://api.telegram.org/bot' . BOT_TOKEN . '/sendPhoto');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [
                    'chat_id' => $user_id,
                    'photo' => new CURLFile($stored['file'], 'image/' . ($stored['ext'] === 'jpg' ? 'jpeg' : $stored['ext']), 'azadweek-card.' . $stored['ext']),
                    'caption' => $caption,
                ],
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 12,
            ]);
            $raw = curl_exec($ch);
            curl_close($ch);
            $dec = json_decode((string)$raw, true);
            $sent_chat = !empty($dec['ok']);
        }
        aw_json([
            'status' => 'success',
            'url' => $stored['url'],
            'download_url' => $stored['download_url'],
            'sent_to_chat' => $sent_chat,
        ]);
        break;

    case 'send_ics_to_chat':
        $init = aw_require_telegram_init_data();
        $user_id = (int)($init['user']['id'] ?? ($_POST['user_id'] ?? 0));
        $ics = aw_generate_ics();
        $ics_file = __DIR__ . '/assets/calendar.ics';
        @file_put_contents($ics_file, $ics);
        $sent_chat = false;
        if ($user_id > 0 && function_exists('curl_init') && class_exists('CURLFile') && defined('BOT_TOKEN') && BOT_TOKEN !== '' && is_file($ics_file)) {
            $bot_username = defined('BOT_USERNAME') ? BOT_USERNAME : 'AzadWeekBot';
            $caption = "📅 تقویم کامل هفته‌های زوج و فرد دانشگاه آزاد\n\nکافیست روی فایل بالا بزنید تا تمام هفته‌های ترم به تقویم گوشی یا سیستم شما اضافه شوند.\n@" . $bot_username;
            $ch = curl_init('https://api.telegram.org/bot' . BOT_TOKEN . '/sendDocument');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [
                    'chat_id' => $user_id,
                    'document' => new CURLFile($ics_file, 'text/calendar', 'AzadWeek-Term-Calendar.ics'),
                    'caption' => $caption,
                ],
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 12,
            ]);
            $raw = curl_exec($ch);
            curl_close($ch);
            $dec = json_decode((string)$raw, true);
            $sent_chat = !empty($dec['ok']);
        }
        aw_json([
            'status' => 'success',
            'download_url' => aw_public_base_url() . '/connect.php?action=download_ics&download=1&v=' . time(),
            'sent_to_chat' => $sent_chat,
        ]);
        break;

    case 'prepare_share_card':
        $init = aw_validate_init_data($_POST['initData'] ?? '');
        $trusted_user_id = $init['ok'] && !empty($init['user']['id']) ? (int)$init['user']['id'] : 0;
        $posted_user_id = (int)($_POST['user_id'] ?? 0);
        if ($trusted_user_id <= 0 || $trusted_user_id !== $posted_user_id) {
            aw_json(['status' => 'error', 'message' => 'Telegram initData verification failed. Falling back to inline share.'], 403);
        }
        $share_type = strtolower(trim($_POST['share_type'] ?? 'status'));
        $is_wrapped = in_array($share_type, ['wrapped', 'wrapped_personal', 'wrapped_term'], true);
        $date = aw_normalize_jalali_date($_POST['date'] ?? '') ?: jdate('Y/m/d');
        $status_text = trim($_POST['status_text'] ?? '');
        if ($status_text === '' && !$is_wrapped) $status_text = 'وضعیت نامشخص';
        $message_text = trim($_POST['text'] ?? '');
        $bot_username = defined('BOT_USERNAME') ? BOT_USERNAME : 'AzadWeekBot';
        if ($message_text === '') {
            if ($is_wrapped) {
                $message_text = ($share_type === 'wrapped_term' ? 'Wrapped ترم AzadWeek' : 'Wrapped من AzadWeek') . "\n@" . $bot_username;
            } else {
                $message_text = "وضعیت آزادویک\n" . $date . "\n" . $status_text . "\n@" . $bot_username;
            }
        }
        $app_url = 'https://t.me/' . $bot_username . ($is_wrapped ? '?startapp=wrapped' : '?startapp=date_' . str_replace('/', '-', $date));
        $uploaded_card_url = aw_safe_generated_card_url((string)($_POST['card_url'] ?? ''));
        if ($uploaded_card_url !== '') {
            $card_url = $uploaded_card_url;
        } elseif ($is_wrapped) {
            $card_url = aw_public_base_url() . '/card.php?' . http_build_query([
                'type' => 'wrapped',
                'mode' => $share_type === 'wrapped_term' ? 'term' : 'personal',
                'v' => 'template9_' . time(),
            ]);
        } else {
            $card_url = aw_public_base_url() . '/card.php?' . http_build_query([
                'type' => 'status',
                'date' => $date,
                'status' => $status_text,
                'v' => 'template9_' . time(),
            ]);
        }
        $title = $is_wrapped ? ($share_type === 'wrapped_term' ? 'Wrapped ترم AzadWeek' : 'Wrapped من AzadWeek') : 'کارت وضعیت AzadWeek';
        $description = $is_wrapped ? 'خلاصه فعالیت AzadWeek' : ($date . ' - ' . $status_text);
        $caption = '<b>AzadWeek</b>' . "\n" . aw_escape_html($message_text);
        if (strpos($message_text, '@' . $bot_username) === false) $caption .= "\n@" . aw_escape_html($bot_username);
        $result = [
            'type' => 'photo',
            'id' => 'azadweek_' . ($is_wrapped ? 'wrapped_' : 'status_') . time() . '_' . mt_rand(1000, 9999),
            'photo_url' => $card_url,
            'thumbnail_url' => $card_url,
            'title' => $title,
            'description' => $description,
            'caption' => $caption,
            'parse_mode' => 'HTML',
            'reply_markup' => [
                'inline_keyboard' => [[
                    ['text' => 'باز کردن AzadWeek', 'url' => $app_url]
                ]]
            ]
        ];
        $api = aw_bot_api('savePreparedInlineMessage', [
            'user_id' => $trusted_user_id,
            'result' => $result,
            'allow_user_chats' => true,
            'allow_bot_chats' => false,
            'allow_group_chats' => true,
            'allow_channel_chats' => true,
        ]);
        if (!empty($api['ok']) && !empty($api['result']['id'])) {
            aw_json([
                'status' => 'success',
                'prepared_message_id' => $api['result']['id'],
                'expiration_date' => $api['result']['expiration_date'] ?? null,
            ]);
        }
        aw_json(['status' => 'error', 'message' => $api['description'] ?? 'Could not prepare Telegram share message'], 502);
        break;

    case 'save_preferences':
        $init = aw_validate_init_data($_POST['initData'] ?? '');
        if (empty($init['ok']) || empty($init['user']['id']) || !preg_match('/^\d+$/', (string)$init['user']['id'])) {
            aw_json(['status' => 'success', 'saved' => false]);
        }
        $user_id = (string)$init['user']['id'];
        $prefs_raw = $_POST['preferences'] ?? '{}';
        $prefs = json_decode($prefs_raw, true);
        if (!is_array($prefs)) $prefs = [];
        $safe = [
            'reminder' => !empty($prefs['reminder']),
            'reminderUpdatedAt' => (int)($prefs['reminderUpdatedAt'] ?? time()),
        ];
        $file = $GLOBALS['bot_root'] . '/miniapp_preferences.json';
        $all = [];
        if (is_file($file)) {
            $decoded = json_decode((string)@file_get_contents($file), true);
            if (is_array($decoded)) $all = $decoded;
        }
        $all[$user_id] = $safe;
        @file_put_contents($file, json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        aw_json(['status' => 'success', 'saved' => is_file($file)]);
        break;

    case 'test_reminder':
        try {
            $init = aw_require_telegram_init_data();
            $user_id = !empty($init['user']['id']) ? (int)$init['user']['id'] : (int)($_POST['user_id'] ?? 0);
            if ($user_id <= 0) {
                aw_json(['status' => 'error', 'message' => 'کاربر تلگرام شناسایی نشد.'], 403);
            }
            $today = function_exists('jdate') ? jdate('Y/m/d') : '';
            $week_info = ($today !== '' && function_exists('getWeekInfo')) ? getWeekInfo($today) : null;
            $status_desc = (is_array($week_info) && !empty($week_info['توضیح'])) ? $week_info['توضیح'] : 'هفته فعال ترم';
            $w_icon = '';
            if (function_exists('weekStatusIcon')) {
                $w_icon = (string)weekStatusIcon($status_desc);
            }
            if ($w_icon === '') {
                $w_icon = ($status_desc === 'هفته فرد') ? '🟢' : (($status_desc === 'هفته زوج') ? '🟣' : '⚫️');
            }
            $hdr = function_exists('iconText') ? iconText('CALENDAR', '<b>آزمایش یادآور هفتگی آزادویک</b>') : '🔔 <b>آزمایش یادآور هفتگی آزادویک</b>';
            $test_msg = $hdr . "\n\n"
                . "این یک پیام آزمایشی برای اطمینان از فعال بودن یادآور هفتگی شماست.\n\n"
                . "📅 <b>وضعیت این هفته:</b> " . ($w_icon !== '' ? $w_icon . ' ' : '') . "<b>{$status_desc}</b>\n"
                . "⏰ <b>زمان‌بندی ارسال خودکار:</b> جمعه‌ها ساعت ۲۲:۰۰ شب قبل از شروع هفته جدید\n\n"
                . "🌿 <i>امیدواریم هفته آموزشی پربار و موفقی پیش‌رو داشته باشید.</i>\n"
                . "@" . (defined('BOT_USERNAME') ? BOT_USERNAME : 'AzadWeekBot');

            $sent = aw_bot_api('sendMessage', [
                'chat_id' => $user_id,
                'text' => $test_msg,
                'parse_mode' => 'HTML',
            ]);
            if (!empty($sent['ok'])) {
                aw_json(['status' => 'success', 'message' => 'پیام آزمایشی با موفقیت به پیوی تلگرام شما ارسال شد ✨']);
            } else {
                aw_json(['status' => 'error', 'message' => 'ارسال پیام با خطا مواجه شد. لطفاً ابتدا ربات را استارت کنید.'], 200);
            }
        } catch (\Throwable $e) {
            aw_json(['status' => 'error', 'message' => 'خطا در ارسال پیام آزمایشی: ' . $e->getMessage()], 200);
        }
        break;

    default:
        aw_json(['status' => 'error', 'message' => 'Invalid action'], 400);
}
