<?php

include_once 'config.php';
include_once 'jdf.php';
include_once 'lib/api.php';
include_once 'lib/database.php';
include_once 'lib/utils.php';

$is_cli = (php_sapi_name() === 'cli' || empty($_SERVER['REMOTE_ADDR']));
if (!$is_cli) {
    $secret = (string)($_GET['secret'] ?? $_POST['secret'] ?? '');
    if (!defined('CRON_SECRET_KEY') || !hash_equals((string)CRON_SECRET_KEY, $secret)) {
        http_response_code(403);
        die('Access Denied: Invalid Cron Secret');
    }
}

set_time_limit(0);
ignore_user_abort(true);

$lock_file = defined('FILE_CRON_LOCK') ? FILE_CRON_LOCK : (__DIR__ . '/storage_backups/cron.lock');
$lock_dir = dirname($lock_file);
if (!is_dir($lock_dir)) {
    @mkdir($lock_dir, 0755, true);
}
$lock_fp = @fopen($lock_file, 'c+');
if ($lock_fp && !flock($lock_fp, LOCK_EX | LOCK_NB)) {
    fclose($lock_fp);
    echo 'Another cron instance is already running.';
    exit;
}

if (function_exists('processPendingDeletes')) {
    processPendingDeletes();
}

function checkAndSendWeeklyStatus() {
    if (jdate('l') !== 'جمعه') return;

    $last_sent_file = FILE_LAST_SCHEDULED;
    $current_hour = jdate('H');
    $today_date = jdate('Y-m-d');

    $last_sent_marker = file_exists($last_sent_file) ? file_get_contents($last_sent_file) : '';
    $should_send = false;
    $current_marker = '';

    if ($current_hour == '22') {
        $current_marker = $today_date . '-22';
        if ($last_sent_marker !== $current_marker) $should_send = true;
    }

    if ($should_send) {
        $next_week = getNextWeekStatus();
        if ($next_week) {
            $w_icon = function_exists('weekStatusIcon') ? weekStatusIcon($next_week['توضیح']) : '';
            $message = iconText('CALENDAR', "<b>یادآور هفتگی دانشگاه آزاد (آزادویک)</b>") . "\n\n"
                . "هفته آینده ({$next_week['start']} تا {$next_week['end']}): "
                . ($w_icon !== '' ? $w_icon . ' ' : '') . "<b>{$next_week['توضیح']}</b> است.\n\n"
                . "🌿 <i>امیدواریم هفته آموزشی پربار و موفقی داشته باشید.</i>\n"
                . "@" . (defined('BOT_USERNAME') ? BOT_USERNAME : 'AzadWeekBot');

            if (!file_exists(FILE_BROADCAST_QUEUE) && file_exists(FILE_ALL_USERS_TXT)) {
                $broadcast_data = [
                    'text' => $message,
                    'photo' => null,
                    'voice' => null,
                    'video' => null,
                    'animation' => null,
                    'message_ids' => [],
                    'admin_id' => 'AUTOMATIC',
                ];

                $pref_file = __DIR__ . '/miniapp_preferences.json';
                $prefs = is_file($pref_file) ? json_decode((string)file_get_contents($pref_file), true) : [];
                $disabled_uids = [];
                if (is_array($prefs)) {
                    foreach ($prefs as $uid => $p) {
                        if (isset($p['reminder']) && !$p['reminder']) {
                            $disabled_uids[(string)$uid] = true;
                        }
                    }
                }

                $users_lines = file(FILE_ALL_USERS_TXT, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $active_users = [];
                foreach ($users_lines as $u) {
                    $u = trim((string)$u);
                    if ($u !== '' && !isset($disabled_uids[$u])) {
                        $active_users[] = $u;
                    }
                }

                file_put_contents(FILE_BROADCAST_QUEUE, tgJson($broadcast_data));
                file_put_contents(FILE_USERS_TO_SEND, implode("\n", $active_users) . "\n", LOCK_EX);
                file_put_contents($last_sent_file, $current_marker);
            }
        }
    }
}

checkAndSendWeeklyStatus();

if (!file_exists(FILE_BROADCAST_QUEUE)) {
    echo 'No broadcast job in queue.';
    exit;
}

$broadcast_data = json_decode(file_get_contents(FILE_BROADCAST_QUEUE), true);
if (!is_array($broadcast_data)) {
    @unlink(FILE_BROADCAST_QUEUE);
    echo 'Invalid broadcast queue.';
    exit;
}

$message_text = $broadcast_data['text'] ?? '';
$photo_id = $broadcast_data['photo'] ?? null;
$voice_id = $broadcast_data['voice'] ?? null;
$video_id = $broadcast_data['video'] ?? null;
$animation_id = $broadcast_data['animation'] ?? null;
$admin_id = $broadcast_data['admin_id'] ?? 'AUTOMATIC';

if (!isset($broadcast_data['stats'])) {
    $total_users = file_exists(FILE_USERS_TO_SEND) ? count(file(FILE_USERS_TO_SEND, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : 0;
    $broadcast_data['stats'] = [
        'total_users' => $total_users,
        'sent_count' => 0,
        'failed_count' => 0,
        'start_time' => time(),
    ];
}

$users = file_exists(FILE_USERS_TO_SEND) ? file(FILE_USERS_TO_SEND, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

if (empty($users)) {
    if ($admin_id !== 'AUTOMATIC') {
        $stats = $broadcast_data['stats'];
        $duration = time() - ($stats['start_time'] ?? time());
        $duration_formatted = gmdate('H:i:s', $duration);

        if ($photo_id) {
            sendPhoto($admin_id, $photo_id, $message_text);
        } elseif ($voice_id) {
            sendVoice($admin_id, $voice_id, $message_text);
        } elseif ($video_id) {
            sendVideo($admin_id, $video_id, $message_text);
        } elseif ($animation_id) {
            sendAnimation($admin_id, $animation_id, $message_text);
        } else {
            sendMessage($admin_id, 'پیام زیر ارسال شد:' . "\n\n" . $message_text);
        }

        $report = iconText('STATS', '<b>گزارش نهایی ارسال همگانی</b>') . "\n\n";
        $report .= iconText('USERS', '<b>کاربران هدف:</b> ' . ($stats['total_users'] ?? 'N/A') . ' نفر') . "\n";
        $report .= iconText('OK', '<b>ارسال موفق:</b> ' . ($stats['sent_count'] ?? 'N/A') . ' نفر') . "\n";
        $report .= iconText('ERROR', '<b>ارسال ناموفق:</b> ' . ($stats['failed_count'] ?? 'N/A') . ' نفر') . "\n";
        $report .= iconText('TIME', '<b>مدت زمان کل:</b> ' . $duration_formatted) . "\n";
        $report .= iconText('CALENDAR', '<b>زمان اتمام:</b> ' . jdate('Y/m/d H:i:s'));
        sendMessage($admin_id, $report);
    }

    saveSentMessageToDB(
        $message_text,
        $photo_id,
        $voice_id,
        $video_id,
        $animation_id,
        $broadcast_data['message_ids'] ?? [],
        $broadcast_data['stats'] ?? []
    );

    @unlink(FILE_BROADCAST_QUEUE);
    @unlink(FILE_USERS_TO_SEND);
    echo 'Broadcast finished successfully.';
    exit;
}

$batch_size = 25;
$batch_to_send = array_slice($users, 0, $batch_size);

foreach ($batch_to_send as $user_id) {
    $user_id = trim($user_id);
    if ($user_id === '') continue;

    $message_id = null;

    if ($photo_id) {
        $message_id = sendPhoto($user_id, $photo_id, $message_text);
    } elseif ($voice_id) {
        $message_id = sendVoice($user_id, $voice_id, $message_text);
    } elseif ($video_id) {
        $message_id = sendVideo($user_id, $video_id, $message_text);
    } elseif ($animation_id) {
        $message_id = sendAnimation($user_id, $animation_id, $message_text);
    } else {
        $message_id = sendMessage($user_id, $message_text);
    }

    if ($message_id) {
        $broadcast_data['message_ids'][$user_id] = $message_id;
        $broadcast_data['stats']['sent_count']++;
    } else {
        $broadcast_data['stats']['failed_count']++;
    }

    usleep(100000);
}

file_put_contents(FILE_USERS_TO_SEND, implode("\n", array_slice($users, $batch_size)));
file_put_contents(FILE_BROADCAST_QUEUE, tgJson($broadcast_data));

echo 'Batch processed. Sent: ' . $broadcast_data['stats']['sent_count'];
?>
