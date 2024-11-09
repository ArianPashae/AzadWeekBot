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
?>
