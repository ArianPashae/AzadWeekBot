<?php
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Access Denied');
}

function getPersistentCurlHandle() {
    static $ch = null;
    if ($ch === null && function_exists('curl_init')) {
        $ch = curl_init();
    }
    return $ch;
}

function makeRequest($method, $params = []) {
    $url = API_URL . $method;

    $ch = getPersistentCurlHandle();
    if (!$ch) {
        return ['ok' => false, 'description' => 'cURL is not available'];
    }

    curl_ResetOptions($ch, $url, $params);
    $result = curl_exec($ch);

    if ($result === false) {
        return ['ok' => false, 'description' => curl_error($ch) ?: 'cURL error'];
    }

    $decoded = json_decode($result, true);
    return is_array($decoded) ? $decoded : ['ok' => false, 'description' => 'Invalid Telegram response'];
}

function curl_ResetOptions($ch, $url, $params) {
    if (function_exists('curl_reset')) {
        curl_reset($ch);
    }
    $options = [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $params,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_TCP_KEEPALIVE  => 1,
        CURLOPT_TCP_KEEPIDLE   => 60,
        CURLOPT_DNS_CACHE_TIMEOUT => 300,
    ];
    if (defined('CURL_IPRESOLVE_V4')) {
        $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
    }
    if (defined('CURL_HTTP_VERSION_2_0')) {
        $options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2_0;
    }
    curl_setopt_array($ch, $options);
}

function makeMultiRequests(array $requests) {
    if (empty($requests)) return [];
    if (!function_exists('curl_multi_init') || count($requests) === 1) {
        $results = [];
        foreach ($requests as $key => $req) {
            $results[$key] = makeRequest($req['method'], $req['params'] ?? []);
        }
        return $results;
    }

    $mh = curl_multi_init();
    $handles = [];
    $results = [];

    foreach ($requests as $key => $req) {
        $ch = curl_init();
        curl_ResetOptions($ch, API_URL . $req['method'], $req['params'] ?? []);
        $handles[$key] = $ch;
        curl_multi_add_handle($mh, $ch);
    }

    $active = null;
    do {
        $mrc = curl_multi_exec($mh, $active);
        if ($active) {
            curl_multi_select($mh, 0.2);
        }
    } while ($active && $mrc === CURLM_OK);

    foreach ($handles as $key => $ch) {
        $raw = curl_multi_getcontent($ch);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        $results[$key] = is_array($decoded) ? $decoded : ['ok' => false, 'description' => 'Invalid Telegram response'];
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);
    return $results;
}

function tgJson($value) {
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function ipInCidr($ip, $cidr) {
    if (strpos($cidr, '/') === false) return $ip === $cidr;
    list($subnet, $bits) = explode('/', $cidr, 2);
    $ipLong = ip2long($ip);
    $subnetLong = ip2long($subnet);
    if ($ipLong === false || $subnetLong === false) return false;
    $bits = (int)$bits;
    $mask = $bits === 0 ? 0 : (~0 << (32 - $bits));
    return (($ipLong & $mask) === ($subnetLong & $mask));
}

function isTrustedTelegramWebhookRequest() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return false;
    }

    if (!empty($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN']) && defined('CRON_SECRET_KEY')) {
        $expected = hash_hmac('sha256', BOT_TOKEN, CRON_SECRET_KEY);
        if (
            !hash_equals($expected, (string)$_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN']) &&
            !hash_equals(CRON_SECRET_KEY, (string)$_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'])
        ) {
            return false;
        }
        return true;
    }

    $clientIp = trim((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? ''));
    if ($clientIp !== '' && filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $telegramCidrs = ['149.154.160.0/20', '91.108.4.0/22'];
        foreach ($telegramCidrs as $cidr) {
            if (ipInCidr($clientIp, $cidr)) {
                return true;
            }
        }
        return false;
    }

    return true;
}

function finishWebhookResponse() {
    if (function_exists('fastcgi_finish_request')) {
        @fastcgi_finish_request();
        return;
    }
    if (function_exists('litespeed_finish_request')) {
        @litespeed_finish_request();
        return;
    }
    if (!headers_sent()) {
        header('Connection: close');
        header('Content-Length: 0');
        http_response_code(200);
    }
    while (ob_get_level() > 0) {
        @ob_end_flush();
    }
    @flush();
}

function syncBotCommandsOnce($force = false) {
    $marker = defined('FILE_COMMANDS_SYNCED') ? FILE_COMMANDS_SYNCED : (__DIR__ . '/../storage_backups/bot_commands_synced.json');
    $version = 'v11_open_app_v17';

    if (!$force && is_file($marker)) {
        $data = json_decode((string)@file_get_contents($marker), true);
        if (is_array($data) && ($data['version'] ?? '') === $version) {
            return;
        }
    }

    $dir = dirname($marker);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $group_commands = tgJson([
        ['command' => 'status', 'description' => 'وضعیت امروز (زوج یا فرد)'],
    ]);
    $private_commands = tgJson([
        ['command' => 'start', 'description' => 'نمایش منوی اصلی ربات'],
    ]);

    makeMultiRequests([
        'menu_btn' => [
            'method' => 'setChatMenuButton',
            'params' => [
                'menu_button' => tgJson([
                    'type' => 'web_app',
                    'text' => 'Open App',
                    'web_app' => [
                        'url' => defined('MINIAPP_URL') ? MINIAPP_URL : 'https://example.com/AzadWeek/?action=app&v=17'
                    ]
                ])
            ],
        ],
        'default_cmds' => [
            'method' => 'setMyCommands',
            'params' => [
                'commands' => $group_commands,
                'scope' => tgJson(['type' => 'default']),
            ],
        ],
        'private_cmds' => [
            'method' => 'setMyCommands',
            'params' => [
                'commands' => $private_commands,
                'scope' => tgJson(['type' => 'all_private_chats']),
            ],
        ],
        'group_cmds' => [
            'method' => 'setMyCommands',
            'params' => [
                'commands' => $group_commands,
                'scope' => tgJson(['type' => 'all_group_chats']),
            ],
        ],
        'group_admin_cmds' => [
            'method' => 'setMyCommands',
            'params' => [
                'commands' => $group_commands,
                'scope' => tgJson(['type' => 'all_chat_administrators']),
            ],
        ],
    ]);

    @file_put_contents($marker, tgJson(['version' => $version, 'synced_at' => time()]), LOCK_EX);
}

function customEmojiData($key) {
    $all = defined('CUSTOM_EMOJIS') ? CUSTOM_EMOJIS : [];
    return $all[$key] ?? ['id' => '', 'fallback' => ''];
}

function ceId($key) {
    $item = customEmojiData($key);
    return trim((string)($item['id'] ?? ''));
}

function codepointsToUtf8($hexSequence) {
    $hexSequence = trim((string)$hexSequence);
    if ($hexSequence === '') return '';

    $chars = [];
    foreach (preg_split('/\s+/', $hexSequence) as $hex) {
        $code = hexdec($hex);
        if ($code <= 0) continue;
        $chars[] = mb_chr($code, 'UTF-8');
    }

    return implode('', $chars);
}

function ceFallback($key) {
    static $cache = [];
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $item = customEmojiData($key);
    if (isset($item['fallback_hex'])) {
        return $cache[$key] = codepointsToUtf8($item['fallback_hex']);
    }
    return $cache[$key] = (string)($item['fallback'] ?? '');
}

function ce($key) {
    static $cache = [];
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $id = ceId($key);
    if ($id === '') return $cache[$key] = '';

    $fallback = ceFallback($key);
    if ($fallback === '') return $cache[$key] = '';

    return $cache[$key] = '<tg-emoji emoji-id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">' . $fallback . '</tg-emoji>';
}

function iconText($key, $text) {
    $icon = ce($key);
    return trim(($icon !== '' ? $icon . ' ' : '') . $text);
}

if (!function_exists('weekStatusIcon')) {
    function weekStatusIcon($status) {
        if ($status === 'هفته فرد') return ce('WEEK_ODD');
        if ($status === 'هفته زوج') return ce('WEEK_EVEN');
        return ce('WEEK_DEFAULT');
    }
}

function channelChatId($rawId) {
    $rawId = trim((string)$rawId);
    if ($rawId === '') return '';
    if (substr($rawId, 0, 1) === '@') return $rawId;
    if (preg_match('/^-?\d+$/', $rawId)) return $rawId;
    return '@' . $rawId;
}

function getRequiredChannels() {
    if (defined('REQUIRED_CHANNELS') && is_array(REQUIRED_CHANNELS)) {
        return REQUIRED_CHANNELS;
    }

    if (defined('CHANNEL_ID') && CHANNEL_ID !== '') {
        $id = CHANNEL_ID;
        return [[
            'id' => $id,
            'title' => 'کانال',
            'url' => 'https://t.me/' . ltrim((string)$id, '@'),
        ]];
    }

    return [];
}

function readMembershipCache() {
    $file = defined('FILE_MEMBERSHIP_CACHE') ? FILE_MEMBERSHIP_CACHE : (__DIR__ . '/../storage_backups/membership_cache.json');
    if (!is_file($file)) return [];
    $data = json_decode((string)@file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function writeMembershipCache(array $cache) {
    $file = defined('FILE_MEMBERSHIP_CACHE') ? FILE_MEMBERSHIP_CACHE : (__DIR__ . '/../storage_backups/membership_cache.json');
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $now = time();
    $ttl = defined('MEMBERSHIP_CACHE_TTL') ? MEMBERSHIP_CACHE_TTL : 180;
    foreach ($cache as $uid => $exp) {
        if ((int)$exp < $now) unset($cache[$uid]);
    }
    @file_put_contents($file, tgJson($cache), LOCK_EX);
}

function getUserChannelMembershipStatus($user_id, $force_refresh = false) {
    $channels = getRequiredChannels();
    if (empty($channels)) return [];

    $uid_str = (string)$user_id;
    $is_admin = defined('ADMIN_IDS') && in_array($uid_str, array_map('strval', ADMIN_IDS), true);
    $now = time();
    $ttl = defined('MEMBERSHIP_CACHE_TTL') ? MEMBERSHIP_CACHE_TTL : 180;

    if ($is_admin) {
        $statuses = [];
        foreach ($channels as $index => $channel) {
            $rawId = $channel['id'] ?? '';
            $statuses[] = [
                'id' => $rawId,
                'title' => $channel['title'] ?? ('کانال ' . ($index + 1)),
                'url' => $channel['url'] ?? ('https://t.me/' . ltrim((string)$rawId, '@')),
                'joined' => true,
                'telegram_status' => 'administrator',
            ];
        }
        return $statuses;
    }

    if (!$force_refresh) {
        $cache = readMembershipCache();
        if (isset($cache[$uid_str]) && (int)$cache[$uid_str] >= $now) {
            $statuses = [];
            foreach ($channels as $index => $channel) {
                $rawId = $channel['id'] ?? '';
                $statuses[] = [
                    'id' => $rawId,
                    'title' => $channel['title'] ?? ('کانال ' . ($index + 1)),
                    'url' => $channel['url'] ?? ('https://t.me/' . ltrim((string)$rawId, '@')),
                    'joined' => true,
                    'telegram_status' => 'member',
                ];
            }
            return $statuses;
        }
    }

    $requests = [];
    foreach ($channels as $index => $channel) {
        $chatId = channelChatId($channel['id'] ?? '');
        if ($chatId !== '') {
            $requests[$index] = [
                'method' => 'getChatMember',
                'params' => [
                    'chat_id' => $chatId,
                    'user_id' => $user_id,
                ],
            ];
        }
    }

    $responses = makeMultiRequests($requests);
    $statuses = [];
    $all_joined = true;

    foreach ($channels as $index => $channel) {
        $rawId = $channel['id'] ?? '';
        $title = $channel['title'] ?? ('کانال ' . ($index + 1));
        $url = $channel['url'] ?? ('https://t.me/' . ltrim((string)$rawId, '@'));
        $response = $responses[$index] ?? null;
        $telegramStatus = $response['result']['status'] ?? null;
        $joined = isset($response['ok']) && $response['ok'] === true && !in_array($telegramStatus, ['left', 'kicked'], true);

        if (!$joined) {
            $all_joined = false;
        }

        $statuses[] = [
            'id' => $rawId,
            'title' => $title,
            'url' => $url,
            'joined' => $joined,
            'telegram_status' => $telegramStatus,
        ];
    }

    if ($all_joined) {
        $cache = readMembershipCache();
        $cache[$uid_str] = $now + $ttl;
        writeMembershipCache($cache);
    }

    return $statuses;
}

function isUserInAllRequiredChannels($user_id, $force_refresh = false) {
    $channels = getRequiredChannels();
    if (empty($channels)) return true;

    foreach (getUserChannelMembershipStatus($user_id, $force_refresh) as $status) {
        if (!$status['joined']) return false;
    }

    return true;
}

function isUserInChannel($user_id) {
    return isUserInAllRequiredChannels($user_id);
}

function styledButton($text, $style = 'primary', $emojiKey = '', $extra = []) {
    $button = array_merge(['text' => $text], $extra);
    $button['style'] = $style;

    if ($emojiKey !== '') {
        $emojiId = ceId($emojiKey);
        if ($emojiId !== '') {
            $button['icon_custom_emoji_id'] = $emojiId;
        }
    }

    return $button;
}

function buildChannelLockMarkup($statuses = null) {
    $statuses = $statuses ?? [];
    if (empty($statuses)) {
        foreach (getRequiredChannels() as $index => $channel) {
            $statuses[] = [
                'title' => $channel['title'] ?? ('کانال ' . ($index + 1)),
                'url' => $channel['url'] ?? ('https://t.me/' . ltrim((string)($channel['id'] ?? ''), '@')),
                'joined' => false,
            ];
        }
    }

    $keyboard = [];
    foreach ($statuses as $index => $status) {
        $keyboard[] = [
            styledButton(
                'عضویت در ' . ($status['title'] ?? ('کانال ' . ($index + 1))),
                !empty($status['joined']) ? 'success' : 'primary',
                'JOIN',
                ['url' => $status['url'] ?? '#']
            ),
        ];
    }

    $keyboard[] = [
        styledButton('تایید عضویت', 'success', 'CONFIRM', ['callback_data' => 'check_membership']),
    ];

    return ['inline_keyboard' => $keyboard];
}

function buildChannelLockMessage($statuses = null) {
    $statuses = $statuses ?? [];
    $text = iconText('LOCK', '<b>برای استفاده از ربات باید عضو کانال‌های زیر باشید.</b>') . "\n\n";

    if (!empty($statuses)) {
        foreach ($statuses as $status) {
            $mark = !empty($status['joined']) ? ce('OK') : ce('ERROR');
            $title = htmlspecialchars((string)($status['title'] ?? 'کانال'), ENT_QUOTES, 'UTF-8');
            $text .= trim(($mark !== '' ? $mark . ' ' : '') . $title) . "\n";
        }
        $text .= "\n";
    }

    $text .= iconText('CONFIRM', 'بعد از عضویت، دکمه تایید عضویت را بزنید.');
    return $text;
}

function answerCallbackQuerySimple($callback_query_id, $text = null, $show_alert = false) {
    $params = ['callback_query_id' => $callback_query_id];
    if ($text !== null) $params['text'] = $text;
    if ($show_alert) $params['show_alert'] = true;
    return makeRequest('answerCallbackQuery', $params);
}

function sendMessage($chat_id, $text, $reply_markup = null, $parse_mode = 'HTML', $reply_to_message_id = null) {
    $params = ['chat_id' => $chat_id, 'text' => $text];
    if ($parse_mode !== null && $parse_mode !== '') $params['parse_mode'] = $parse_mode;
    if ($reply_markup !== null) {
        $params['reply_markup'] = is_array($reply_markup) ? tgJson($reply_markup) : $reply_markup;
    }
    if ($reply_to_message_id !== null && (int)$reply_to_message_id > 0) {
        $params['reply_parameters'] = tgJson([
            'message_id' => (int)$reply_to_message_id,
            'allow_sending_without_reply' => true,
        ]);
    }

    $res = makeRequest('sendMessage', $params);
    if ((!isset($res['ok']) || !$res['ok']) && isset($params['reply_parameters'])) {
        unset($params['reply_parameters'], $params['reply_to_message_id'], $params['allow_sending_without_reply']);
        $res = makeRequest('sendMessage', $params);
    }
    return isset($res['ok']) && $res['ok'] ? ($res['result']['message_id'] ?? null) : null;
}

function sendPhoto($chat_id, $photo, $caption = null, $parse_mode = 'HTML') {
    $params = ['chat_id' => $chat_id, 'photo' => $photo];
    if ($caption !== null) $params['caption'] = $caption;
    if ($parse_mode !== null && $parse_mode !== '') $params['parse_mode'] = $parse_mode;
    $res = makeRequest('sendPhoto', $params);
    return isset($res['ok']) && $res['ok'] ? ($res['result']['message_id'] ?? null) : null;
}

function sendVoice($chat_id, $voice, $caption = null, $parse_mode = 'HTML') {
    $params = ['chat_id' => $chat_id, 'voice' => $voice];
    if ($caption !== null) $params['caption'] = $caption;
    if ($parse_mode !== null && $parse_mode !== '') $params['parse_mode'] = $parse_mode;
    $res = makeRequest('sendVoice', $params);
    return isset($res['ok']) && $res['ok'] ? ($res['result']['message_id'] ?? null) : null;
}

function sendVideo($chat_id, $video, $caption = null, $parse_mode = 'HTML') {
    $params = ['chat_id' => $chat_id, 'video' => $video];
    if ($caption !== null) $params['caption'] = $caption;
    if ($parse_mode !== null && $parse_mode !== '') $params['parse_mode'] = $parse_mode;
    $res = makeRequest('sendVideo', $params);
    return isset($res['ok']) && $res['ok'] ? ($res['result']['message_id'] ?? null) : null;
}

function sendAnimation($chat_id, $animation, $caption = null, $parse_mode = 'HTML') {
    $params = ['chat_id' => $chat_id, 'animation' => $animation];
    if ($caption !== null) $params['caption'] = $caption;
    if ($parse_mode !== null && $parse_mode !== '') $params['parse_mode'] = $parse_mode;
    $res = makeRequest('sendAnimation', $params);
    return isset($res['ok']) && $res['ok'] ? ($res['result']['message_id'] ?? null) : null;
}

function sendDocument($chat_id, $file_path, $caption = null) {
    $params = [
        'chat_id' => $chat_id,
        'document' => new CURLFile(realpath($file_path)),
    ];
    if ($caption !== null) $params['caption'] = $caption;
    return makeRequest('sendDocument', $params);
}

function deleteMessageTelegram($chat_id, $message_id) {
    return makeRequest('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $message_id]);
}

function build_inline_result($id, $title, $description, $message_text, $thumb_url, $reply_markup = null) {
    $result = [
        'type' => 'article',
        'id' => $id,
        'title' => $title,
        'description' => $description,
        'input_message_content' => ['message_text' => $message_text, 'parse_mode' => 'HTML'],
        'thumb_url' => $thumb_url,
    ];

    if ($reply_markup) $result['reply_markup'] = $reply_markup;
    return $result;
}

function create_buttons($message_text_for_share) {
    $encoded_text = urlencode(strip_tags($message_text_for_share));

    return ['inline_keyboard' => [[
        styledButton('اشتراک‌گذاری', 'primary', 'SHARE', ['url' => "https://t.me/share/url?url=$encoded_text"]),
        styledButton('ورود به ربات', 'success', 'BOT', ['url' => 'https://t.me/' . BOT_USERNAME]),
    ]]];
}
?>
