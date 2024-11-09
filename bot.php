<?php
include_once('jdf.php'); // Add the jdf library

$token = ''; // bot token
$api_url = "https://api.telegram.org/bot$token/";
$admin_chat_ids = ['', '', '']; // Admin IDs
$channel_username = 'ComputerAzadKsh'; // Channel username without @

// User status file
$state_file = 'user_states.json';
$user_file = 'users.json'; // New file to store user information

// Function to store and retrieve user information
function saveUserInfo($chat_id, $username) {
    global $user_file;
    $users = file_exists($user_file) ? json_decode(file_get_contents($user_file), true) : [];
    $users[$chat_id] = ['username' => $username, 'joined_at' => time()]; // Save username and membership time
    file_put_contents($user_file, json_encode($users));
}
// Function to Get User Info
function getUserInfo($chat_id) {
    global $user_file;
    if (!file_exists($user_file)) {
        return null;
    }
    $users = json_decode(file_get_contents($user_file), true);
    return isset($users[$chat_id]) ? $users[$chat_id] : null;
}
// Function to Get User State
function getUserState($chat_id) {
    global $state_file;
    $states = file_exists($state_file) ? json_decode(file_get_contents($state_file), true) : [];
    return isset($states[$chat_id]) ? $states[$chat_id] : null;
}
// Function to Set User State
function setUserState($chat_id, $state) {
    global $state_file;
    $states = file_exists($state_file) ? json_decode(file_get_contents($state_file), true) : [];
    $states[$chat_id] = $state;
    file_put_contents($state_file, json_encode($states));
}
// Function to Send Message
function sendMessage($chat_id, $text, $reply_markup = null, $parse_mode = null) {
    global $api_url;
    $url = $api_url . "sendMessage";
    $post_fields = [
        'chat_id' => $chat_id,
        'text' => $text,
    ];
    if ($reply_markup) {
        $post_fields['reply_markup'] = json_encode($reply_markup);
    }
    if ($parse_mode) {
        $post_fields['parse_mode'] = $parse_mode;
    }
    sendRequest($url, $post_fields);
}
// Function to Send Photo
function sendPhoto($chat_id, $photo, $caption = null) {
    global $api_url;
    $url = $api_url . "sendPhoto";
    $post_fields = [
        'chat_id' => $chat_id,
        'photo' => $photo,
    ];
    if ($caption) {
        $post_fields['caption'] = $caption;
    }
    sendRequest($url, $post_fields);
}
// Function to Send Voice
function sendVoice($chat_id, $voice, $caption = null) {
    global $api_url;
    $url = $api_url . "sendVoice";
    $post_fields = [
        'chat_id' => $chat_id,
        'voice' => $voice,
    ];
    if ($caption) {
        $post_fields['caption'] = $caption;
    }
    sendRequest($url, $post_fields);
}
// Function to Send All
function sendToAll($message, $photo = null, $voice = null) {
    $file_path = "users.txt";
    if (!file_exists($file_path)) {
        file_put_contents($file_path, '');
    }
    $users = explode("\n", trim(file_get_contents($file_path)));
    
    foreach ($users as $user) {
        if ($photo) {
            sendPhoto($user, $photo, $message);
        } elseif ($voice) {
            sendVoice($user, $voice, $message);
        } else {
            sendMessage($user, $message);
        }
    }
}
// Function to jalali_date
function jalaliToTimestamp($jalali_date) {
    list($year, $month, $day) = explode('/', $jalali_date);
    return jmktime(0, 0, 0, $month, $day, $year);
}
?>
