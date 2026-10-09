<?php

include_once 'config.php';
include_once 'jdf.php';
include_once 'lib/api.php';
include_once 'lib/database.php';
include_once 'lib/utils.php';

define('BTN_CHECK_DATE', 'بررسی تاریخ');
define('BTN_TODAY', 'وضعیت امروز');
define('BTN_ABOUT', 'درباره ما');
define('BTN_WEEKS', 'لیست هفته‌ها');
define('BTN_BROADCAST', 'ارسال پیام به همه کاربران');
define('BTN_MESSAGES', 'مشاهده پیام‌ها');
define('BTN_STATS', 'آمار کاربران');
define('BTN_EXIT_DATE', 'خروج از بررسی تاریخ');
define('BTN_EXIT', 'خروج');

function mainMenuMarkup($chat_id) {
    $markup = [
        'keyboard' => [
            [
                styledButton(BTN_CHECK_DATE, 'primary', 'SEARCH'),
                styledButton(BTN_TODAY, 'success', 'CALENDAR'),
            ],
            [
                styledButton(BTN_ABOUT, 'primary', 'SHIELD'),
                styledButton(BTN_WEEKS, 'success', 'LIST'),
            ],
        ],
        'resize_keyboard' => true,
        'is_persistent'   => false,
    ];

    if (in_array((string)$chat_id, array_map('strval', ADMIN_IDS), true)) {
        $markup['keyboard'][] = [styledButton(BTN_BROADCAST, 'danger', 'BROADCAST')];
        $markup['keyboard'][] = [
            styledButton(BTN_MESSAGES, 'primary', 'MESSAGES'),
            styledButton(BTN_STATS, 'success', 'STATS'),
        ];
    }

    return $markup;
}

if (!function_exists('weekStatusIcon')) {
    function weekStatusIcon($status) {
        if ($status === 'هفته فرد') return ce('WEEK_ODD');
        if ($status === 'هفته زوج') return ce('WEEK_EVEN');
        return ce('WEEK_DEFAULT');
    }
}

function isAdminChat($chat_id) {
    return in_array((string)$chat_id, array_map('strval', ADMIN_IDS), true);
}

function getCurrentWeekStartTimestamp() {
    $today_start = strtotime('today');
    $day_of_week = (int)date('w', $today_start);
    $days_since_saturday = ($day_of_week + 1) % 7;

    return strtotime("-{$days_since_saturday} days", $today_start);
}

function buildUsersListLines($users) {
    $lines = [];

    foreach ($users as $uid => $uinfo) {
        $username = !empty($uinfo['username']) ? '@' . ltrim((string)$uinfo['username'], '@') : '---';

        $joined = 'نامشخص';
        if (isset($uinfo['joined_at']) && is_numeric($uinfo['joined_at'])) {
            $joined = jdate('Y/m/d H:i', (int)$uinfo['joined_at']);
        }

        $lines[] = $uid . ' | ' . $username . ' | joined: ' . $joined;
    }

    return $lines;
}

function handleGroupMessageAutoDelete($chat_id, $sent_message_id, $user_message_id = null) {
    if (!$sent_message_id && !$user_message_id) return;
    $delay = defined('GROUP_STATUS_AUTODELETE_SECONDS') ? (int)GROUP_STATUS_AUTODELETE_SECONDS : 20;
    if ($sent_message_id) {
        scheduleAutoDeleteMessage($chat_id, $sent_message_id, $delay);
    }
    if ($user_message_id) {
        scheduleAutoDeleteMessage($chat_id, $user_message_id, $delay);
    }
    ignore_user_abort(true);
    @set_time_limit($delay + 15);
    finishWebhookResponse();
    sleep(max(1, $delay));
    processPendingDeletes();
}

if (!isTrustedTelegramWebhookRequest()) {
    http_response_code(403);
    exit;
}

$content = file_get_contents('php://input');
$update = json_decode($content, true);
if (!is_array($update) || empty($update)) exit;

syncBotCommandsOnce();
processPendingDeletes();

if (isset($update['my_chat_member'])) {
    $mcm = $update['my_chat_member'];
    $chat = $mcm['chat'] ?? [];
    $chat_id = $chat['id'] ?? null;
    $chat_type = $chat['type'] ?? '';
    $old_status = $mcm['old_chat_member']['status'] ?? '';
    $new_status = $mcm['new_chat_member']['status'] ?? '';

    if (
        $chat_id &&
        ($chat_type === 'group' || $chat_type === 'supergroup') &&
        in_array($old_status, ['left', 'kicked', ''], true) &&
        in_array($new_status, ['member', 'administrator'], true)
    ) {
        $group_welcome = iconText('WELCOME', '<b>ربات هفته‌های زوج و فرد در گروه فعال شد.</b>') . "\n\n";
        $group_welcome .= iconText('INFO', 'برای دریافت <b>وضعیت امروز</b>، دستور <code>/status</code> را ارسال کنید.');
        $sent_id = sendMessage($chat_id, $group_welcome, ['remove_keyboard' => true]);
        handleGroupMessageAutoDelete($chat_id, $sent_id);
    }
    exit;
}

if (isset($update['message'])) {
    $msg = $update['message'];

    $chat_id = $msg['chat']['id'];
    $user_id = $msg['from']['id'] ?? $chat_id;
    $chat_type = $msg['chat']['type'];
    $username = $msg['from']['username'] ?? '';
    $message_text = $msg['text'] ?? '';

    if ($chat_type === 'group' || $chat_type === 'supergroup') {
        $text_clean = trim($message_text);

        if ($text_clean !== '' && preg_match('/^\/status(?:@' . preg_quote(BOT_USERNAME, '/') . ')?(?:\s|$)/i', $text_clean)) {
            $today = jdate('Y/m/d');
            $day_name = jdate('l');
            $week_info = getWeekInfo($today);
            $week_num = get_week_number($today);

            if ($week_info) {
                $w_icon = weekStatusIcon($week_info['توضیح']);
                $response = iconText('CALENDAR', "<b>وضعیت امروز ($day_name $today):</b>") . "\n"
                    . ($w_icon !== '' ? $w_icon . ' ' : '')
                    . "امروز در <b>{$week_info['توضیح']}</b>"
                    . ($week_num ? " (هفته $week_num ترم)" : '')
                    . " قرار دارد.";
            } else {
                $response = iconText('CALENDAR', "<b>وضعیت امروز ($day_name $today):</b> خارج از بازه ترم");
            }

            $user_msg_id = $msg['message_id'] ?? null;
            $sent_id = sendMessage(
                $chat_id,
                $response,
                ['remove_keyboard' => true],
                'HTML',
                $user_msg_id
            );

            handleGroupMessageAutoDelete($chat_id, $sent_id, $user_msg_id);
        }

        exit;
    }

    if ($chat_type === 'private') {
        $membership_statuses = getUserChannelMembershipStatus($user_id);
        $is_member = true;

        foreach ($membership_statuses as $status) {
            if (!$status['joined']) {
                $is_member = false;
                break;
            }
        }

        if (!$is_member) {
            sendMessage($chat_id, buildChannelLockMessage($membership_statuses), buildChannelLockMarkup($membership_statuses));
            exit;
        }
    }

    if ($chat_type === 'private') {
        saveUserInfo($user_id, $username);

        if ($message_text === '/start') {
            setUserState($chat_id, null);
            $user_state = null;
        } else {
            $user_state = getUserState($chat_id);
        }
        $main_menu_markup = mainMenuMarkup($chat_id);

        if ($user_state === 'waiting_for_date') {
            $date_keyboard = [
                'keyboard' => [[styledButton(BTN_EXIT_DATE, 'danger', 'BACK')]],
                'resize_keyboard' => true,
                'is_persistent' => false,
            ];

            if ($message_text === BTN_EXIT_DATE) {
                setUserState($chat_id, null);
                sendMessage($chat_id, 'شما از حالت بررسی تاریخ خارج شدید.', $main_menu_markup);
            } else {
                $date_input = convertPersianToArabic(trim($message_text));

                if (isValidJalaliDateString($date_input)) {
                    $week_info = getWeekInfo($date_input);

                    if ($week_info) {
                        sendMessage($chat_id, iconText('INFO', "تاریخ $date_input در <b>{$week_info['توضیح']}</b> قرار دارد."), $date_keyboard);
                    } else {
                        sendMessage($chat_id, iconText('INFO', "تاریخ $date_input در این ترم قرار ندارد."), $date_keyboard);
                    }
                } else {
                    sendMessage($chat_id, iconText('WARNING', 'لطفاً یک تاریخ معتبر به فرمت <code>YYYY/MM/DD</code> وارد کنید.'), $date_keyboard);
                }
            }
        } elseif ($user_state === 'waiting_for_broadcast') {
            if ($message_text === BTN_EXIT) {
                setUserState($chat_id, null);
                sendMessage($chat_id, iconText('ERROR', 'شما از حالت ارسال پیام خارج شدید.'), $main_menu_markup);
            } else {
                if (file_exists(FILE_BROADCAST_QUEUE)) {
                    sendMessage($chat_id, iconText('WARNING', 'یک فرآیند ارسال همگانی دیگر در حال اجراست.'), $main_menu_markup);
                    exit;
                }

                $broadcast_data = [
                    'text' => null,
                    'photo' => null,
                    'voice' => null,
                    'video' => null,
                    'animation' => null,
                    'message_ids' => [],
                    'admin_id' => $chat_id,
                ];

                if (isset($msg['photo'])) {
                    $broadcast_data['photo'] = end($msg['photo'])['file_id'];
                    $broadcast_data['text'] = $msg['caption'] ?? '';
                } elseif (isset($msg['voice'])) {
                    $broadcast_data['voice'] = $msg['voice']['file_id'];
                    $broadcast_data['text'] = $msg['caption'] ?? '';
                } elseif (isset($msg['video'])) {
                    $broadcast_data['video'] = $msg['video']['file_id'];
                    $broadcast_data['text'] = $msg['caption'] ?? '';
                } elseif (isset($msg['animation'])) {
                    $broadcast_data['animation'] = $msg['animation']['file_id'];
                    $broadcast_data['text'] = $msg['caption'] ?? '';
                } else {
                    $broadcast_data['text'] = $message_text;
                }

                file_put_contents(FILE_BROADCAST_QUEUE, tgJson($broadcast_data), LOCK_EX);

                $users = getSavedBotUsers();
                syncAllUsersTxt($users);

                if (file_exists(FILE_ALL_USERS_TXT)) {
                    copy(FILE_ALL_USERS_TXT, FILE_USERS_TO_SEND);
                }

                sendMessage($chat_id, iconText('OK', 'پیام شما در صف ارسال قرار گرفت.'), $main_menu_markup);
                setUserState($chat_id, null);
            }
        } else {
            switch ($message_text) {
                case '/start':
                    $welcome_text = iconText('WELCOME', '<b>به ربات هفته‌های فرد و زوج خوش آمدید.</b>') . "\n\n";
                    $welcome_text .= 'با استفاده از این ربات می‌توانید:' . "\n";
                    $welcome_text .= iconText('OK', 'تاریخ‌های خاص را بررسی کنید و ببینید در کدام هفته قرار دارند.') . "\n";
                    $welcome_text .= iconText('OK', 'وضعیت امروز را دریافت کنید.') . "\n";
                    $welcome_text .= iconText('OK', 'تمامی هفته‌های زوج و فرد ترم را مشاهده کنید.') . "\n\n";
                    $welcome_text .= iconText('ROCKET', 'از دکمه‌های زیر استفاده کنید.');

                    sendMessage($chat_id, $welcome_text, $main_menu_markup);
                    break;

                case '/status':
                case BTN_TODAY:
                    $today = jdate('Y/m/d');
                    $day_name = jdate('l');
                    $week_info = getWeekInfo($today);
                    $week_num = get_week_number($today);

                    if ($week_info) {
                        $w_icon = weekStatusIcon($week_info['توضیح']);
                        $response = iconText('CALENDAR', "<b>وضعیت امروز ($day_name $today):</b>") . "\n"
                            . ($w_icon !== '' ? $w_icon . ' ' : '')
                            . "امروز در <b>{$week_info['توضیح']}</b>"
                            . ($week_num ? " (هفته $week_num ترم)" : '')
                            . " قرار دارد.";
                    } else {
                        $response = iconText('CALENDAR', "امروز ($day_name $today) در بازه این ترم قرار ندارد.");
                    }

                    sendMessage($chat_id, $response, $main_menu_markup);
                    break;

                case BTN_CHECK_DATE:
                    $date_keyboard = [
                        'keyboard' => [[styledButton(BTN_EXIT_DATE, 'danger', 'BACK')]],
                        'resize_keyboard' => true,
                        'is_persistent' => false,
                    ];

                    sendMessage(
                        $chat_id,
                        iconText('INFO', 'لطفاً تاریخ را به فرمت <code>YYYY/MM/DD</code> وارد کنید.') . "\n\n" . 'برای لغو، دکمه زیر را بزنید.',
                        $date_keyboard
                    );

                    setUserState($chat_id, 'waiting_for_date');
                    break;

                case BTN_WEEKS:
                    $weeks_text = iconText('LIST', '<b>لیست هفته‌های ترم:</b>') . "\n\n";

                    foreach ($weeks_config as $index => $week) {
                        $icon = ($index % 2 === 0) ? ce('WEEK_ODD') : ce('WEEK_EVEN');
                        $weeks_text .= trim(($icon !== '' ? $icon . ' ' : '') . ($index + 1) . ". از {$week['start']} تا {$week['end']} - <b>{$week['توضیح']}</b>") . "\n";
                    }

                    sendMessage($chat_id, $weeks_text, $main_menu_markup);
                    break;

                case BTN_ABOUT:
                    $about_text = iconText('BOT', '<b>درباره ربات</b>') . "\n\n";
                    $about_text .= 'این ربات توسط <a href="https://arianpashae.com">آرین پاشائی</a>، دانشجوی رشته مهندسی کامپیوتر دانشگاه آزاد اسلامی واحد کرمانشاه، با ایده‌پردازی <a href="https://alirezamohamadiam.github.io">علیرضا محمدی</a> طراحی شده است تا به مدیریت بهتر زمان و برنامه‌ریزی کمک کند.' . "\n\n";
                    $about_text .= iconText('GRADUATION', 'این ابزار برای دانشجویان و اساتید طراحی شده و امکان نمایش وضعیت هفته‌های زوج و فرد ترم را فراهم می‌کند.') . "\n\n";
                    $about_text .= iconText('GLOBE', '<a href="https://t.me/ComputerAzadKsh">کانال رشته مهندسی کامپیوتر دانشگاه آزاد کرمانشاه</a>') . "\n";
                    $about_text .= iconText('Arian', '<a href="https://t.me/ArianPashaeChannel">کانال رسمی آرین پاشائی</a>');

                    sendMessage($chat_id, $about_text, $main_menu_markup);
                    break;

                case BTN_BROADCAST:
                    if (isAdminChat($chat_id)) {
                        if (file_exists(FILE_BROADCAST_QUEUE)) {
                            sendMessage($chat_id, iconText('WARNING', 'یک فرآیند ارسال همگانی دیگر در حال اجراست.'), $main_menu_markup);
                        } else {
                            $exit_keyboard = [
                                'keyboard' => [[styledButton(BTN_EXIT, 'danger', 'BACK')]],
                                'resize_keyboard' => true,
                                'is_persistent' => false,
                            ];

                            sendMessage($chat_id, iconText('NOTE', 'پیام خود را برای ارسال به تمام کاربران بفرستید:'), $exit_keyboard);
                            setUserState($chat_id, 'waiting_for_broadcast');
                        }
                    }
                    break;

                case BTN_STATS:
                    if (isAdminChat($chat_id)) {
                        $json_users = getSavedBotUsers();

                        $user_count = count($json_users);

                        $week_start_ts = getCurrentWeekStartTimestamp();
                        $now = time();
                        $week_users_count = 0;

                        foreach ($json_users as $uid => $u) {
                            if (!isset($u['joined_at']) || !is_numeric($u['joined_at'])) {
                                continue;
                            }

                            $joined_at = (int)$u['joined_at'];

                            if ($joined_at >= $week_start_ts && $joined_at <= $now) {
                                $week_users_count++;
                            }
                        }

                        $stats_text = iconText('STATS', '<b>آمار کاربران</b>') . "\n\n";
                        $stats_text .= iconText('USERS', "<b>تعداد کل کاربران:</b> $user_count نفر") . "\n";
                        $stats_text .= iconText('TREND', "<b>کاربران جدید این هفته:</b> $week_users_count نفر");

                        sendMessage($chat_id, $stats_text, $main_menu_markup);

                        $lines_to_write = buildUsersListLines($json_users);

                        if (!empty($lines_to_write)) {
                            $backup_dir = __DIR__ . '/storage_backups';
                            if (!is_dir($backup_dir)) @mkdir($backup_dir, 0755, true);
                            $file_path = $backup_dir . '/users_list_' . time() . '_' . bin2hex(random_bytes(4)) . '.txt';

                            file_put_contents($file_path, implode("\n", $lines_to_write), LOCK_EX);

                            if (file_exists($file_path)) {
                                sendDocument($chat_id, $file_path, 'لیست کاربران (' . jdate('Y/m/d H:i') . ')');
                                @unlink($file_path);
                            } else {
                                sendMessage($chat_id, iconText('ERROR', 'خطا: فایل ساخته نشد.'), $main_menu_markup);
                            }
                        } else {
                            sendMessage($chat_id, iconText('WARNING', 'لیست کاربران کاملاً خالی است.'), $main_menu_markup);
                        }
                    }
                    break;

                case BTN_MESSAGES:
                    if (isAdminChat($chat_id)) {
                        $messages = file_exists(FILE_SENT_MESSAGES)
                            ? json_decode(file_get_contents(FILE_SENT_MESSAGES), true)
                            : [];

                        if (!is_array($messages) || empty($messages)) {
                            sendMessage($chat_id, 'هنوز هیچ پیامی ارسال نشده است.', $main_menu_markup);
                        } else {
                            $recent_messages = array_slice(array_reverse($messages), 0, 15);
                            $messages_text = iconText('MESSAGES', '<b>آخرین پیام‌های ارسال شده:</b>') . "\n\n";

                            foreach ($recent_messages as $message) {
                                if (!empty($message['text'])) {
                                    $messages_text .= iconText('NOTE', '<b>متن:</b> ' . htmlspecialchars(mb_substr($message['text'], 0, 50), ENT_QUOTES, 'UTF-8') . '...') . "\n";
                                }

                                if (!empty($message['photo'])) {
                                    $messages_text .= iconText('PHOTO', 'دارای عکس') . "\n";
                                }

                                if (!empty($message['voice'])) {
                                    $messages_text .= iconText('VOICE', 'دارای ویس') . "\n";
                                }

                                if (!empty($message['video'])) {
                                    $messages_text .= iconText('VIDEO', 'دارای ویدیو') . "\n";
                                }

                                if (!empty($message['animation'])) {
                                    $messages_text .= iconText('GIF', 'دارای گیف') . "\n";
                                }

                                $messages_text .= iconText('TIME', '<b>زمان:</b> ' . jdate('Y/m/d H:i:s', $message['timestamp'])) . "\n";
                                $messages_text .= iconText('DELETE', '<b>حذف:</b> <code>/delete_' . htmlspecialchars((string)$message['id'], ENT_QUOTES, 'UTF-8') . '</code>') . "\n\n";
                            }

                            sendMessage($chat_id, $messages_text, $main_menu_markup);
                        }
                    }
                    break;

                default:
                    if (strpos($message_text, '/delete_') === 0 && isAdminChat($chat_id)) {
                        $message_id = str_replace('/delete_', '', $message_text);

                        if (deleteMessageFromDB($message_id)) {
                            sendMessage($chat_id, iconText('OK', 'پیام با موفقیت حذف شد.'), $main_menu_markup);
                        } else {
                            sendMessage($chat_id, iconText('ERROR', 'خطا در حذف پیام.'), $main_menu_markup);
                        }
                    } elseif ($message_text !== '') {
                        sendMessage($chat_id, iconText('INFO', 'لطفاً از دکمه‌های منوی پایین استفاده کنید.'), $main_menu_markup);
                    }
                    break;
            }
        }
    }
}

if (isset($update['callback_query'])) {
    $callback_query = $update['callback_query'];

    $callback_id = $callback_query['id'];
    $chat_id = $callback_query['message']['chat']['id'];
    $chat_type = $callback_query['message']['chat']['type'] ?? 'private';
    $user_id = $callback_query['from']['id'];
    $username = $callback_query['from']['username'] ?? '';
    $data = $callback_query['data'];

    if ($data === 'check_membership') {
        $membership_statuses = getUserChannelMembershipStatus($user_id, true);
        $is_member = true;

        foreach ($membership_statuses as $status) {
            if (!$status['joined']) {
                $is_member = false;
                break;
            }
        }

        if ($is_member) {
            if ($chat_type === 'private') {
                saveUserInfo($user_id, $username);
            }

            answerCallbackQuerySimple($callback_id, 'عضویت تایید شد.');
            sendMessage($chat_id, iconText('OK', 'عضویت شما تایید شد. از دکمه‌های منوی زیر استفاده کنید.'), mainMenuMarkup($chat_id));
        } else {
            answerCallbackQuerySimple($callback_id, 'هنوز عضویت کامل نیست.', true);
            sendMessage($chat_id, buildChannelLockMessage($membership_statuses), buildChannelLockMarkup($membership_statuses));
        }
    }
}

if (isset($update['inline_query'])) {
    $inline_query = $update['inline_query'];

    $query_id = $inline_query['id'];
    $user_input = trim($inline_query['query']);
    $results = [];

    $image_urls = [
        'calendar' => BASE_URL . 'assets/calendar.webp',
        'bot'      => BASE_URL . 'assets/bot.webp',
        'help'     => BASE_URL . 'assets/help.webp',
        'stats'    => BASE_URL . 'assets/stats.webp',
        'start'    => BASE_URL . 'assets/start.webp',
        'end'      => BASE_URL . 'assets/end.webp',
    ];

    $days_of_week_translation = [
        'شنبه' => 'Saturday',
        'یکشنبه' => 'Sunday',
        'دوشنبه' => 'Monday',
        'سه شنبه' => 'Tuesday',
        'سه‌شنبه' => 'Tuesday',
        'چهارشنبه' => 'Wednesday',
        'پنجشنبه' => 'Thursday',
        'جمعه' => 'Friday',
    ];

    $bot_signature = "\n\n@" . BOT_USERNAME;

    if (empty($user_input) || strpos($user_input, 'راهنما') !== false) {
        $today = jdate('Y/m/d');
        $week_info = getWeekInfo($today);
        $status = $week_info ? $week_info['توضیح'] : 'خارج از ترم';

        $message = iconText('CALENDAR', '<b>وضعیت امروز:</b> <code>' . $today . '</code>') . "\n\n";
        $message .= trim((weekStatusIcon($status) !== '' ? weekStatusIcon($status) . ' ' : '') . "این هفته، <b>$status</b> است.") . $bot_signature;

        $results[] = build_inline_result(
            'today',
            "وضعیت امروز | $status",
            "تاریخ: $today",
            $message,
            $image_urls['calendar'],
            create_buttons($message)
        );

        $start_of_term = reset($weeks_config)['start'];
        $end_of_term = end($weeks_config)['end'];
        $current_week_num = get_week_number($today);

        $term_summary_text = iconText('STATS', '<b>خلاصه وضعیت ترم:</b>') . "\n\n";
        $term_summary_text .= iconText('TERM_START', "<b>شروع ترم:</b> <code>$start_of_term</code>") . "\n";
        $term_summary_text .= iconText('TERM_END', "<b>پایان ترم:</b> <code>$end_of_term</code>") . "\n";
        $term_summary_text .= iconText('NUMBER', '<b>هفته فعلی:</b> ' . ($current_week_num ? "هفته <b>$current_week_num</b>" : '<i>تعطیلات</i>')) . $bot_signature;

        $results[] = build_inline_result(
            'term_summary',
            'خلاصه وضعیت ترم',
            'شروع، پایان و هفته فعلی',
            $term_summary_text,
            $image_urls['stats'],
            create_buttons($term_summary_text)
        );

        $next_saturday = jdate('Y/m/d', strtotime('next Saturday'));
        $week_info_sat = getWeekInfo($next_saturday);
        $status_sat = $week_info_sat ? $week_info_sat['توضیح'] : 'خارج از ترم';

        $message_sat = iconText('NEXT', "<b>شنبه آینده</b> (<code>$next_saturday</code>) در <b>$status_sat</b> قرار دارد.") . $bot_signature;

        $results[] = build_inline_result(
            'next_saturday',
            "وضعیت شنبه آینده | $status_sat",
            "تاریخ: $next_saturday",
            $message_sat,
            $image_urls['calendar'],
            create_buttons($message_sat)
        );

        $help_message = 'برای استفاده از ربات به صورت اینلاین، می‌توانید از کلمات کلیدی زیر استفاده کنید:' . "\n\n";
        $help_message .= '- <code>امروز</code>' . "\n";
        $help_message .= '- <code>فردا</code>' . "\n";
        $help_message .= '- <code>دیروز</code>' . "\n";
        $help_message .= '- <code>شنبه</code> یا سایر روزهای هفته' . "\n";
        $help_message .= '- <code>شروع ترم</code>' . "\n";
        $help_message .= '- <code>پایان ترم</code>' . "\n\n";
        $help_message .= 'همچنین می‌توانید تاریخ مورد نظر خود را با فرمت <code>YYYY/MM/DD</code> وارد کنید.' . $bot_signature;

        $results[] = build_inline_result(
            'interactive_help',
            'راهنما و پیشنهادات',
            'نمونه دستورات اینلاین',
            iconText('HELP', $help_message),
            $image_urls['help']
        );
    } else {
        $found = false;

        if (strpos($user_input, 'شروع') !== false) {
            $start_of_term = reset($weeks_config)['start'];

            $message = iconText('TERM_START', "تاریخ شروع ترم <code>$start_of_term</code> است.") . $bot_signature;

            $results[] = build_inline_result(
                'start_term',
                'شروع ترم',
                "تاریخ: $start_of_term",
                $message,
                $image_urls['start'],
                create_buttons($message)
            );

            $found = true;
        } elseif (strpos($user_input, 'پایان') !== false || strpos($user_input, 'آخر') !== false) {
            $end_of_term = end($weeks_config)['end'];

            $message = iconText('TERM_END', "تاریخ پایان ترم <code>$end_of_term</code> است.") . $bot_signature;

            $results[] = build_inline_result(
                'end_term',
                'پایان ترم',
                "تاریخ: $end_of_term",
                $message,
                $image_urls['end'],
                create_buttons($message)
            );

            $found = true;
        }

        if (!$found) {
            $keywords = [
                'امروز' => time(),
                'فردا' => time() + 86400,
                'دیروز' => time() - 86400,
            ];

            foreach ($keywords as $fa => $ts) {
                if ($user_input === $fa) {
                    $date = jdate('Y/m/d', $ts);
                    $week_info = getWeekInfo($date);
                    $status = $week_info ? $week_info['توضیح'] : 'خارج از ترم';

                    $message = iconText('CALENDAR', "تاریخ <b>$date</b> ($fa) در <b>$status</b> قرار دارد.") . $bot_signature;

                    $results[] = build_inline_result(
                        $fa,
                        "$fa | $status",
                        "تاریخ: $date",
                        $message,
                        $image_urls['calendar'],
                        create_buttons($message)
                    );

                    $found = true;
                    break;
                }
            }

            if (!$found) {
                foreach ($days_of_week_translation as $fa_day => $en_day) {
                    if ($user_input === $fa_day) {
                        $today_en = date('l');
                        $timestamp = ($today_en === $en_day) ? time() : strtotime("next $en_day");

                        $date = jdate('Y/m/d', $timestamp);
                        $week_info = getWeekInfo($date);
                        $status = $week_info ? $week_info['توضیح'] : 'خارج از ترم';
                        $prefix = ($today_en === $en_day) ? "امروز $fa_day" : "$fa_day آینده";

                        $message = iconText('CALENDAR', "<b>$prefix</b> (<code>$date</code>) در <b>$status</b> قرار دارد.") . $bot_signature;

                        $results[] = build_inline_result(
                            'next_' . $en_day,
                            "$prefix | $status",
                            "تاریخ: $date",
                            $message,
                            $image_urls['calendar'],
                            create_buttons($message)
                        );

                        $found = true;
                        break;
                    }
                }
            }
        }

        if (!$found && isValidJalaliDateString($user_input)) {
            $date_input = convertPersianToArabic($user_input);
            $day_of_week = jdate('l', jalaliToTimestamp($date_input));
            $week_info = getWeekInfo($date_input);
            $status = $week_info ? $week_info['توضیح'] : 'خارج از ترم';

            $message = iconText('CALENDAR', "تاریخ <b>$date_input</b> ($day_of_week) در <b>$status</b> قرار دارد.") . $bot_signature;

            $results[] = build_inline_result(
                'custom_date',
                "$date_input | $status",
                "روز: $day_of_week",
                $message,
                $image_urls['calendar'],
                create_buttons($message)
            );

            $found = true;
        }

        if (!$found) {
            $help_text = 'چیزی پیدا نکردم. این‌ها را امتحان کنید: <code>امروز</code>، <code>فردا</code>، <code>شنبه</code>، <code>شروع ترم</code> یا یک تاریخ مثل <code>1404/08/15</code>';

            $results[] = build_inline_result(
                'help',
                'راهنمای هوشمند',
                'کلمات پیشنهادی: امروز، فردا، شنبه',
                iconText('QUESTION', $help_text) . $bot_signature,
                $image_urls['help']
            );
        }
    }

    makeRequest('answerInlineQuery', [
        'inline_query_id' => $query_id,
        'results' => tgJson($results),
        'cache_time' => 5,
    ]);

    exit;
}
?>
