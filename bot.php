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
// Function to Convert Persian number to Arabic number
function convertPersianToArabic($input) {
    $persian_numbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic_numbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($persian_numbers, $arabic_numbers, $input);
}
// Weeks of each semester in pairs and odd
$weeks = [
    ['توضیح' => 'هفته فرد', 'start' => '1403/06/17', 'end' => '1403/06/23'],
    ['توضیح' => 'هفته زوج', 'start' => '1403/06/24', 'end' => '1403/06/30'],
    ['توضیح' => 'هفته فرد', 'start' => '1403/06/31', 'end' => '1403/07/06'],
    ['توضیح' => 'هفته زوج', 'start' => '1403/07/07', 'end' => '1403/07/13'],
    ['توضیح' => 'هفته فرد', 'start' => '1403/07/14', 'end' => '1403/07/20'],
    ['توضیح' => 'هفته زوج', 'start' => '1403/07/21', 'end' => '1403/07/27'],
    ['توضیح' => 'هفته فرد', 'start' => '1403/07/28', 'end' => '1403/08/04'],
    ['توضیح' => 'هفته زوج', 'start' => '1403/08/05', 'end' => '1403/08/11'],
    ['توضیح' => 'هفته فرد', 'start' => '1403/08/12', 'end' => '1403/08/18'],
    ['توضیح' => 'هفته زوج', 'start' => '1403/08/19', 'end' => '1403/08/25'],
    ['توضیح' => 'هفته فرد', 'start' => '1403/08/26', 'end' => '1403/09/02'],
    ['توضیح' => 'هفته زوج', 'start' => '1403/09/03', 'end' => '1403/09/09'],
    ['توضیح' => 'هفته فرد', 'start' => '1403/09/10', 'end' => '1403/09/16'],
    ['توضیح' => 'هفته زوج', 'start' => '1403/09/17', 'end' => '1403/09/23'],
    ['توضیح' => 'هفته فرد', 'start' => '1403/09/24', 'end' => '1403/09/30'],
    ['توضیح' => 'هفته زوج', 'start' => '1403/10/01', 'end' => '1403/10/07'],
    ['توضیح' => 'هفته فرد', 'start' => '1403/10/08', 'end' => '1403/10/14'],
];
// Function to Get Week Info
function getWeekInfo($date) {
    global $weeks;
    $input_timestamp = jalaliToTimestamp($date);
    
    foreach ($weeks as $week) {
        $start_timestamp = jalaliToTimestamp($week['start']);
        $end_timestamp = jalaliToTimestamp($week['end']);
        
        if ($input_timestamp >= $start_timestamp && $input_timestamp <= $end_timestamp) {
            return $week;
        }
    }
    return null;
}
// Function to Checking the user's membership in the channel
function isUserInChannel($chat_id) {
    global $api_url, $channel_username;
    $channel_username = str_replace('@', '', $channel_username); // Remove @ from the beginning of the channel ID
    $url = $api_url . "getChatMember?chat_id=@$channel_username&user_id=$chat_id";
    $response = json_decode(file_get_contents($url), true);
    return isset($response['result']['status']) && $response['result']['status'] != 'left';
}
// Send an HTTP POST request to a specified URL.
function sendRequest($url, $post_fields) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
// Receive input from Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);
// Check if there is a message in the update received from Telegram
if (isset($update['message'])) {
    // Get the chat ID of the user who sent the message
    $chat_id = $update['message']['chat']['id'];
    // Check if the user has a username; if not, use their first name instead
    $username = isset($update['message']['chat']['username']) 
                ? $update['message']['chat']['username'] 
                : $update['message']['chat']['first_name'];
    // Get the text content of the message sent by the user
    $message = $update['message']['text'];
     // Save user information
    saveUserInfo($chat_id, $username); // Save user ID and name

    // Save new users to file
    $file_path = "users.txt";
    if (!file_exists($file_path)) {
        file_put_contents($file_path, '');
    }
    $users = explode("\n", trim(file_get_contents($file_path)));
    if (!in_array($chat_id, $users)) {
        file_put_contents($file_path, $chat_id . "\n", FILE_APPEND);
    }
    // Checking the user's membership in the channel
    if (!isUserInChannel($chat_id)) {
        $inline_keyboard = [
            [['text' => "عضویت در چنل", 'url' => "https://t.me/$channel_username"]],
            [['text' => "✅  تایید عضویت", 'callback_data' => "check_membership"]]
        ];
        $reply_markup = ['inline_keyboard' => $inline_keyboard];
        sendMessage($chat_id, "❌ شما باید عضو چنل رشته مهندسی کامپیوتر دانشگاه آزاد کرمانشاه باشید تا بتوانید از ربات استفاده کنید.
        
✅ لطفاً دکمه زیر را بزنید و دوباره تلاش کنید.", $reply_markup);
        exit;
    }
    // Get user status
    $user_state = getUserState($chat_id);
    // Check the value of the user's message and respond accordingly
switch ($message) {
    // If the message is "/start", set up the keyboard layout for the main menu
    case "/start":
        $reply_markup = [
            'keyboard' => [
                // Define two rows of buttons
                [['text' => "🔍 بررسی تاریخ"], ['text' => "📅 وضعیت امروز"]], // Row 1 buttons
                [['text' => "🔰 درباره ما"], ['text' => "🗓 لیست هفته‌ها"]], // Row 2 buttons
            ],
            'resize_keyboard' => true, // Automatically resize keyboard to fit screen
            'one_time_keyboard' => false, // Keep the keyboard open after user presses a button
        ];
        // Only display the "Send message to all users" option if the user is an admin
        if (in_array($chat_id, $admin_chat_ids)) {
            // Add a button for sending a message to all users
            $reply_markup['keyboard'][] = [['text' => "📢 ارسال پیام به همه کاربران"]];
            // Add a button for viewing user statistics
            $reply_markup['keyboard'][] = [['text' => "📊 آمار کاربران"]];
        }




?>
