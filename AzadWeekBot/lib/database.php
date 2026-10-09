<?php
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Access Denied');
}

function backupStorageFilesOnce() {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $backup_dir = __DIR__ . '/../storage_backups';
    $marker_file = $backup_dir . '/initial_stats_backup_done.txt';

    if (file_exists($marker_file)) {
        return;
    }

    if (!is_dir($backup_dir)) {
        @mkdir($backup_dir, 0755, true);
    }

    $files = [
        FILE_USERS,
        FILE_ALL_USERS_TXT,
    ];

    $has_any_file = false;
    foreach ($files as $file) {
        if (file_exists($file)) {
            $has_any_file = true;
            break;
        }
    }

    $stamp = date('Ymd_His');

    if ($has_any_file && is_dir($backup_dir)) {
        foreach ($files as $file) {
            if (file_exists($file)) {
                @copy($file, $backup_dir . '/' . basename($file) . '.' . $stamp . '.bak');
            }
        }

        @file_put_contents($marker_file, 'Backup created at ' . date('Y-m-d H:i:s'), LOCK_EX);
    } elseif (is_dir($backup_dir)) {
        @file_put_contents($marker_file, 'No storage files existed at ' . date('Y-m-d H:i:s'), LOCK_EX);
    }
}

function readJsonArrayFile($file) {
    if (!file_exists($file)) {
        return [];
    }

    $content = @file_get_contents($file);
    if ($content === false || $content === '') {
        return [];
    }
    $data = json_decode($content, true);

    return is_array($data) ? $data : [];
}

function isValidTelegramUserId($id) {
    $id = trim((string)$id);

    return $id !== '' && preg_match('/^\d+$/', $id);
}

function cleanUsername($username) {
    $username = trim((string)$username);
    return ltrim($username, '@');
}

function syncAllUsersTxt($users) {
    $ids = [];

    foreach ($users as $uid => $info) {
        $uid = trim((string)$uid);

        if (isValidTelegramUserId($uid)) {
            $ids[] = $uid;
        }
    }

    $ids = array_values(array_unique($ids));

    file_put_contents(
        FILE_ALL_USERS_TXT,
        implode("\n", $ids) . (!empty($ids) ? "\n" : ''),
        LOCK_EX
    );
}

function &getUsersMemoryCache() {
    static $cache = null;
    if ($cache === null) {
        $cache = readJsonArrayFile(FILE_USERS);
    }
    return $cache;
}

function getSavedBotUsers() {
    backupStorageFilesOnce();

    $json_users = &getUsersMemoryCache();
    $merged_users = [];
    $needs_save = false;

    foreach ($json_users as $uid => $info) {
        $uid = trim((string)$uid);

        if (!isValidTelegramUserId($uid)) {
            $needs_save = true;
            continue;
        }

        if (!is_array($info)) {
            $info = [];
            $needs_save = true;
        }

        $username = cleanUsername($info['username'] ?? '');

        $joined_at = null;
        if (isset($info['joined_at']) && is_numeric($info['joined_at'])) {
            $joined_at = (int)$info['joined_at'];
        }

        $merged_users[$uid] = [
            'username' => $username,
            'joined_at' => $joined_at,
        ];
    }

    if (file_exists(FILE_ALL_USERS_TXT)) {
        $txt_users = file(FILE_ALL_USERS_TXT, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (is_array($txt_users)) {
            foreach ($txt_users as $uid) {
                $uid = trim((string)$uid);

                if (!isValidTelegramUserId($uid)) {
                    continue;
                }

                if (!isset($merged_users[$uid])) {
                    $merged_users[$uid] = [
                        'username' => '',
                        'joined_at' => null,
                    ];
                    $needs_save = true;
                }
            }
        }
    }

    $json_users = $merged_users;

    if ($needs_save || !file_exists(FILE_USERS) || !file_exists(FILE_ALL_USERS_TXT)) {
        file_put_contents(
            FILE_USERS,
            json_encode($merged_users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );
        syncAllUsersTxt($merged_users);
    }

    return $merged_users;
}

function saveUserInfo($user_id, $username = '') {
    $user_id = trim((string)$user_id);

    if (!isValidTelegramUserId($user_id)) {
        return false;
    }

    $username = cleanUsername($username);
    $users = &getUsersMemoryCache();

    if (empty($users) && file_exists(FILE_ALL_USERS_TXT)) {
        $users = getSavedBotUsers();
    }

    $is_new = !isset($users[$user_id]);

    if ($is_new) {
        $users[$user_id] = [
            'username' => $username,
            'joined_at' => time(),
        ];

        file_put_contents(
            FILE_USERS,
            json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );
        file_put_contents(FILE_ALL_USERS_TXT, $user_id . "\n", FILE_APPEND | LOCK_EX);
        return true;
    }

    $current_username = (string)($users[$user_id]['username'] ?? '');
    if ($username === '' || $username === $current_username) {
        return false;
    }

    $users[$user_id]['username'] = $username;
    if (!array_key_exists('joined_at', $users[$user_id])) {
        $users[$user_id]['joined_at'] = null;
    }

    file_put_contents(
        FILE_USERS,
        json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );

    return false;
}

function &getStatesMemoryCache() {
    static $states = null;
    if ($states === null) {
        $states = readJsonArrayFile(FILE_USER_STATES);
    }
    return $states;
}

function getUserState($chat_id) {
    $states = &getStatesMemoryCache();
    return $states[$chat_id] ?? null;
}

function setUserState($chat_id, $state) {
    $states = &getStatesMemoryCache();
    $current = $states[$chat_id] ?? null;

    if ($current === $state) {
        return;
    }

    if ($state === null) {
        if (!array_key_exists($chat_id, $states)) {
            return;
        }
        unset($states[$chat_id]);
    } else {
        $states[$chat_id] = $state;
    }

    file_put_contents(
        FILE_USER_STATES,
        json_encode($states, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

function saveSentMessageToDB($text, $photo, $voice, $video, $animation, $message_ids, $stats) {
    $messages = readJsonArrayFile(FILE_SENT_MESSAGES);

    $new_message = [
        'id' => uniqid(),
        'text' => $text,
        'photo' => $photo,
        'voice' => $voice,
        'video' => $video,
        'animation' => $animation,
        'message_ids' => $message_ids,
        'stats' => $stats,
        'timestamp' => time(),
    ];

    $messages[] = $new_message;

    file_put_contents(
        FILE_SENT_MESSAGES,
        json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

function deleteMessageFromDB($message_id_to_delete) {
    if (!file_exists(FILE_SENT_MESSAGES)) {
        return false;
    }

    $messages = readJsonArrayFile(FILE_SENT_MESSAGES);
    $found = false;

    foreach ($messages as $index => $message) {
        if (($message['id'] ?? '') === $message_id_to_delete) {
            if (isset($message['message_ids']) && is_array($message['message_ids']) && !empty($message['message_ids'])) {
                $batch = [];
                foreach ($message['message_ids'] as $user_chat_id => $msg_id) {
                    $batch[] = [
                        'method' => 'deleteMessage',
                        'params' => [
                            'chat_id' => $user_chat_id,
                            'message_id' => $msg_id,
                        ],
                    ];
                    if (count($batch) >= 25) {
                        if (function_exists('makeMultiRequests')) {
                            makeMultiRequests($batch);
                        } else {
                            foreach ($batch as $req) {
                                deleteMessageTelegram($req['params']['chat_id'], $req['params']['message_id']);
                            }
                        }
                        $batch = [];
                    }
                }
                if (!empty($batch)) {
                    if (function_exists('makeMultiRequests')) {
                        makeMultiRequests($batch);
                    } else {
                        foreach ($batch as $req) {
                            deleteMessageTelegram($req['params']['chat_id'], $req['params']['message_id']);
                        }
                    }
                }
            }

            unset($messages[$index]);
            $found = true;
            break;
        }
    }

    if ($found) {
        file_put_contents(
            FILE_SENT_MESSAGES,
            json_encode(array_values($messages), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );

        return true;
    }

    return false;
}

function scheduleAutoDeleteMessage($chat_id, $message_id, $delay_seconds = 20) {
    $msg_id = (int)$message_id;
    if (!$chat_id || $msg_id <= 0) return;
    $file = defined('FILE_PENDING_DELETES') ? FILE_PENDING_DELETES : (__DIR__ . '/../storage_backups/pending_deletes.json');
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $fp = @fopen($file, 'c+');
    if (!$fp) return;
    if (@flock($fp, LOCK_EX)) {
        $raw = stream_get_contents($fp);
        $queue = ($raw !== false && $raw !== '') ? json_decode($raw, true) : [];
        if (!is_array($queue)) $queue = [];

        $key = $chat_id . ':' . $msg_id;
        $exists = false;
        foreach ($queue as $item) {
            if (($item['chat_id'] ?? '') . ':' . ($item['message_id'] ?? '') === $key) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $queue[] = [
                'chat_id' => $chat_id,
                'message_id' => $msg_id,
                'delete_at' => time() + max(1, (int)$delay_seconds),
            ];
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($queue, JSON_UNESCAPED_UNICODE));
            fflush($fp);
        }
        @flock($fp, LOCK_UN);
    }
    @fclose($fp);
}

function processPendingDeletes() {
    $file = defined('FILE_PENDING_DELETES') ? FILE_PENDING_DELETES : (__DIR__ . '/../storage_backups/pending_deletes.json');
    if (!is_file($file)) return;

    $to_delete = [];
    $fp = @fopen($file, 'c+');
    if (!$fp) return;

    if (@flock($fp, LOCK_EX)) {
        $raw = stream_get_contents($fp);
        $queue = ($raw !== false && $raw !== '') ? json_decode($raw, true) : [];
        if (!is_array($queue)) $queue = [];

        $now = time();
        $remaining = [];
        $seen = [];

        foreach ($queue as $item) {
            if (!isset($item['chat_id'], $item['message_id'], $item['delete_at'])) {
                continue;
            }
            $msg_id = (int)$item['message_id'];
            if ($msg_id <= 0) continue;

            $key = $item['chat_id'] . ':' . $msg_id;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            if ((int)$item['delete_at'] <= $now) {
                $to_delete[$key] = [
                    'method' => 'deleteMessage',
                    'params' => [
                        'chat_id' => $item['chat_id'],
                        'message_id' => $msg_id,
                    ],
                ];
            } elseif ((int)$item['delete_at'] - $now <= 86400) {
                $remaining[] = $item;
            }
        }

        ftruncate($fp, 0);
        rewind($fp);
        if (!empty($remaining)) {
            fwrite($fp, json_encode(array_values($remaining), JSON_UNESCAPED_UNICODE));
        }
        fflush($fp);
        @flock($fp, LOCK_UN);
    }
    @fclose($fp);

    if (!empty($to_delete)) {
        if (function_exists('makeMultiRequests')) {
            makeMultiRequests($to_delete);
        } else {
            foreach ($to_delete as $req) {
                @deleteMessageTelegram($req['params']['chat_id'], $req['params']['message_id']);
            }
        }
    }
}
?>
