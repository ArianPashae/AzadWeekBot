<?php
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Access Denied');
}

define('BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('BOT_USERNAME', 'AzadWeekBot');
define('BASE_URL', 'https://arianpashae.com/University/AzadWeekBot/');
define('MINIAPP_URL', 'https://arianpashae.com/UniWebsite/AzadWeek/?action=app&v=17');
define('ADMIN_IDS', ['5472263975', '1975573498', '1235825796']);
define('CRON_SECRET_KEY', 'YOUR_CRON_SECRET_KEY');
define('GROUP_STATUS_AUTODELETE_SECONDS', 20);
define('MEMBERSHIP_CACHE_TTL', 180);

define('CHANNEL_ID', 'ComputerAzadKsh');

define('REQUIRED_CHANNELS', [
    [
        'id'    => 'ComputerAzadKsh',
        'title' => 'کانال اول',
        'url'   => 'https://t.me/ComputerAzadKsh',
    ],
    [
        'id'    => 'ArianPashaeChannel',
        'title' => 'کانال دوم',
        'url'   => 'https://t.me/ArianPashaeChannel',
    ],
]);

define('CUSTOM_EMOJIS', [
    'SEARCH'        => ['id' => '5188311512791393083', 'fallback_hex' => '1F50D'],
    'CALENDAR'      => ['id' => '6334449941187921414', 'fallback_hex' => '1F4C5'],
    'SHIELD'        => ['id' => '5249050854392091366', 'fallback_hex' => '1F530'],
    'LIST'          => ['id' => '5274055917766202507', 'fallback_hex' => '1F5D3 FE0F'],
    'BROADCAST'     => ['id' => '5445325005778866735', 'fallback_hex' => '1F4E2'],
    'MESSAGES'      => ['id' => '5274102582585877844', 'fallback_hex' => '1F4E8'],
    'STATS'         => ['id' => '5231200819986047254', 'fallback_hex' => '1F4CA'],
    'BACK'          => ['id' => '5440735760208637835', 'fallback_hex' => '21A9 FE0F'],
    'JOIN'          => ['id' => '5251662946127336150', 'fallback_hex' => '2795'],
    'CONFIRM'       => ['id' => '5978787831164704530', 'fallback_hex' => '2705'],
    'LOCK'          => ['id' => '5296369303661067030', 'fallback_hex' => '1F512'],
    'ERROR'         => ['id' => '5161208387957950108', 'fallback_hex' => '274C'],
    'WARNING'       => ['id' => '5420323339723881652', 'fallback_hex' => '26A0 FE0F'],
    'INFO'          => ['id' => '4927486932113425461', 'fallback_hex' => '2139 FE0F'],
    'WELCOME'       => ['id' => '5456522767203577268', 'fallback_hex' => '1F389'],
    'ROCKET'        => ['id' => '5188481279963715781', 'fallback_hex' => '1F680'],
    'OK'            => ['id' => '4911379469018596278', 'fallback_hex' => '2705'],
    'NOTE'          => ['id' => '5334882760735598374', 'fallback_hex' => '1F4DD'],
    'PHOTO'         => ['id' => '5895427227528467580', 'fallback_hex' => '1F5BC FE0F'],
    'VOICE'         => ['id' => '5850483186504568476', 'fallback_hex' => '1F3A4'],
    'VIDEO'         => ['id' => '6026053001364378800', 'fallback_hex' => '1F4F9'],
    'GIF'           => ['id' => '5974121731449687786', 'fallback_hex' => '1F39E FE0F'],
    'TIME'          => ['id' => '5981043230160981261', 'fallback_hex' => '23F0'],
    'DELETE'        => ['id' => '5019500511871632068', 'fallback_hex' => '1F5D1 FE0F'],
    'USERS'         => ['id' => '5465493682574604098', 'fallback_hex' => '1F465'],
    'TREND'         => ['id' => '6264895716083109895', 'fallback_hex' => '1F4C8'],
    'GLOBE'         => ['id' => '5791716858390387174', 'fallback_hex' => '1F310'],
    'Arian'         => ['id' => '5791959266344574681', 'fallback_hex' => '1F311'],
    'GRADUATION'    => ['id' => '5375163339154399459', 'fallback_hex' => '1F393'],
    'TERM_START'    => ['id' => '5213147006561692829', 'fallback_hex' => '1F3C1'],
    'TERM_END'      => ['id' => '5956275721428012889', 'fallback_hex' => '1F3C6'],
    'NUMBER'        => ['id' => '5262556520788273962', 'fallback_hex' => '1F522'],
    'NEXT'          => ['id' => '5920285072308572176', 'fallback_hex' => '27A1 FE0F'],
    'HELP'          => ['id' => '5440854455924840813', 'fallback_hex' => '1F4A1'],
    'QUESTION'      => ['id' => '5436113877181941026', 'fallback_hex' => '2753'],
    'SHARE'         => ['id' => '5390945398347032435', 'fallback_hex' => '1F4E4'],
    'BOT'           => ['id' => '5372981976804366741', 'fallback_hex' => '1F916'],
    'WEEK_ODD'      => ['id' => '5474335461163964443', 'fallback_hex' => '1F7E2'],
    'WEEK_EVEN'     => ['id' => '5188160849633647368', 'fallback_hex' => '1F534'],
    'WEEK_DEFAULT'  => ['id' => '5474386116008242642', 'fallback_hex' => '26AB FE0F'],
]);

define('FILE_USER_STATES', __DIR__ . '/user_states.json');
define('FILE_USERS', __DIR__ . '/users.json');
define('FILE_SENT_MESSAGES', __DIR__ . '/sent_messages.json');
define('FILE_ALL_USERS_TXT', __DIR__ . '/all_users.txt');
define('FILE_BROADCAST_QUEUE', __DIR__ . '/broadcast_queue.json');
define('FILE_USERS_TO_SEND', __DIR__ . '/users_to_send.txt');
define('FILE_LAST_SCHEDULED', __DIR__ . '/last_scheduled_send.txt');
define('FILE_PENDING_DELETES', __DIR__ . '/storage_backups/pending_deletes.json');
define('FILE_MEMBERSHIP_CACHE', __DIR__ . '/storage_backups/membership_cache.json');
define('FILE_COMMANDS_SYNCED', __DIR__ . '/storage_backups/bot_commands_synced.json');
define('FILE_CRON_LOCK', __DIR__ . '/storage_backups/cron.lock');

$weeks_config = [
    ['توضیح' => 'هفته فرد', 'start' => '1405/06/21', 'end' => '1405/06/27'],
    ['توضیح' => 'هفته زوج', 'start' => '1405/06/28', 'end' => '1405/07/03'],
    ['توضیح' => 'هفته فرد', 'start' => '1405/07/04', 'end' => '1405/07/10'],
    ['توضیح' => 'هفته زوج', 'start' => '1405/07/11', 'end' => '1405/07/17'],
    ['توضیح' => 'هفته فرد', 'start' => '1405/07/18', 'end' => '1405/07/24'],
    ['توضیح' => 'هفته زوج', 'start' => '1405/07/25', 'end' => '1405/08/01'],
    ['توضیح' => 'هفته فرد', 'start' => '1405/08/02', 'end' => '1405/08/08'],
    ['توضیح' => 'هفته زوج', 'start' => '1405/08/09', 'end' => '1405/08/15'],
    ['توضیح' => 'هفته فرد', 'start' => '1405/08/16', 'end' => '1405/08/22'],
    ['توضیح' => 'هفته زوج', 'start' => '1405/08/23', 'end' => '1405/08/29'],
    ['توضیح' => 'هفته فرد', 'start' => '1405/08/30', 'end' => '1405/09/06'],
    ['توضیح' => 'هفته زوج', 'start' => '1405/09/07', 'end' => '1405/09/13'],
    ['توضیح' => 'هفته فرد', 'start' => '1405/09/14', 'end' => '1405/09/20'],
    ['توضیح' => 'هفته زوج', 'start' => '1405/09/21', 'end' => '1405/09/27'],
    ['توضیح' => 'هفته فرد', 'start' => '1405/09/28', 'end' => '1405/10/04'],
    ['توضیح' => 'هفته زوج', 'start' => '1405/10/05', 'end' => '1405/10/11'],
    ['توضیح' => 'هفته فرد', 'start' => '1405/10/12', 'end' => '1405/10/17'],
];
