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
    if (!file_exists($user_file)) {
        file_put_contents($user_file, json_encode([]));
    }
    $users = json_decode(file_get_contents($user_file), true);
    $users[$chat_id] = ['username' => $username, 'joined_at' => time()]; // Save username and membership time
    file_put_contents($user_file, json_encode($users));
}
?>
