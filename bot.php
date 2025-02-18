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
        // Send a welcome message to the user introducing the bot's features and options
sendMessage($chat_id, "🎉 خوش آمدید به ربات هفته‌های فرد و زوج! 🎉   
ما اینجا هستیم تا به شما کمک کنیم با راحتی بیشتر، وضعیت هفته‌های فرد و زوج را بررسی کنید.
با استفاده از این ربات می‌توانید:
✅ تاریخ‌های خاص را بررسی کنید و ببینید در کدام هفته قرار دارند.
✅ وضعیت امروز را به راحتی دریافت کنید.
✅ تمامی هفته‌های زوج و فرد ترم را مشاهده کنید.
    
🚀 اگر آماده‌اید، دکمه‌های زیر را انتخاب کنید و با ما شروع کنید!", $reply_markup);

// Clear the user's state, as they have just started the bot
setUserState($chat_id, null);
break;
    // If the user selects the "🔍 بررسی تاریخ" option, prompt them to enter a date
case "🔍 بررسی تاریخ":
    sendMessage($chat_id, "❗️ لطفاً تاریخ را به فرمت YYYY/MM/DD وارد کنید.\nاگر می‌خواهید از حالت بررسی خارج شوید، روی دکمه زیر کلیک کنید.", [
        'keyboard' => [[['text' => "↩️ خروج از بررسی تاریخ"]]], // Add exit button
        'resize_keyboard' => true, // Resize the keyboard to fit the screen
        'one_time_keyboard' => true, // Close the keyboard after use
    ]);
    // Set the user's state to 'waiting_for_date' to track their input
    setUserState($chat_id, 'waiting_for_date');
    break;
// If the user selects the "↩️ خروج از بررسی تاریخ" option, exit the date-checking mode
case "↩️ خروج از بررسی تاریخ":
    // Check if the user is currently in the 'waiting_for_date' state
    if ($user_state === 'waiting_for_date') {
        // Set up the main menu keyboard layout
        $reply_markup = [
            'keyboard' => [
                [['text' => "🔍 بررسی تاریخ"], ['text' => "📅 وضعیت امروز"]], // Main menu buttons
                [['text' => "🔰 درباره ما"], ['text' => "🗓 لیست هفته‌ها"]], // Additional menu buttons
            ],
            'resize_keyboard' => true, // Resize the keyboard to fit the screen
            'one_time_keyboard' => false, // Keep the keyboard open after use
        ];

        // If the user is an admin, display admin options for messaging and statistics
        if (in_array($chat_id, $admin_chat_ids)) {
            $reply_markup['keyboard'][] = [['text' => "📢 ارسال پیام به همه کاربران"]];
            $reply_markup['keyboard'][] = [['text' => "📊 آمار کاربران"]];
        }

        // Send the message to inform the user they have exited date-checking mode
        sendMessage($chat_id, "شما از حالت بررسی تاریخ خارج شدید.\n❗️ لطفاً یکی از گزینه‌ها را انتخاب کنید:", $reply_markup);
        
        // Reset the user's state to null
        setUserState($chat_id, null);
    }
    break;
    // If the user selects the "🗓 لیست هفته‌ها" option
case "🗓 لیست هفته‌ها":
    // Check if the user is currently in 'waiting_for_date' state
    if ($user_state === 'waiting_for_date') {
        // Inform the user that they need to complete the date check first
        sendMessage($chat_id, "❗️ شما در حال بررسی تاریخ هستید. لطفاً تاریخ را بررسی کنید.");
    } else {
        // Generate the list of weeks with start and end dates, and their description
        $weeks_text = "🗓 لیست هفته‌ها:\n\n";
        foreach ($weeks as $index => $week) {
            $number = $index + 1; // Week number
            $color = ($index % 2 === 0) ? "🟢" : "🔴"; // Determine color based on week number
            $weeks_text .= "$color $number. از {$week['start']} تا {$week['end']} - {$week['توضیح']}\n"; // Add week info
        }
        // Send the list of weeks to the user
        sendMessage($chat_id, $weeks_text);
        
        // Reset the user's state to null
        setUserState($chat_id, null);
    }
    break;
    // If the user selects the "📅 وضعیت امروز" option
case "📅 وضعیت امروز":
    // Check if the user is currently in 'waiting_for_date' state
    if ($user_state === 'waiting_for_date') {
        // Inform the user that they need to complete the date check first
        sendMessage($chat_id, "❗️ شما در حال بررسی تاریخ هستید. لطفاً تاریخ را بررسی کنید.");
    } else {
        // Get today's date using jdf library
        $today = jdate('Y/m/d');
        // Get the week info for today
        $week_info = getWeekInfo($today);
        
        // If week info is found, send the week's description
        if ($week_info) {
            sendMessage($chat_id, "📅 امروز ($today) در {$week_info['توضیح']} قرار دارد.");
        } else {
            // If no week info is found, inform the user
            sendMessage($chat_id, "📅 امروز ($today) در این ترم قرار ندارد.");
        }

        // Display the main menu again after sending today's status
        $reply_markup = [
            'keyboard' => [
                [['text' => "🔍 بررسی تاریخ"], ['text' => "📅 وضعیت امروز"]], // Main menu buttons
                [['text' => "🔰 درباره ما"], ['text' => "🗓 نمایش هفته‌ها"]], // Additional menu buttons
            ],
            'resize_keyboard' => true, // Resize the keyboard to fit the screen
            'one_time_keyboard' => false, // Keep the keyboard open after use
        ];

        // If the user is an admin, add admin options for messaging and statistics
        if (in_array($chat_id, $admin_chat_ids)) {
            $reply_markup['keyboard'][] = [['text' => "📢 ارسال پیام به همه کاربران"]];
            $reply_markup['keyboard'][] = [['text' => "📊 آمار کاربران"]];
        }

        // Send the main menu without additional message
        sendMessage($chat_id, "", $reply_markup);
    }
    break;
    // If the user selects the "🔰 درباره ما" option
case "🔰 درباره ما":
    // Check if the user is currently in 'waiting_for_date' state
    if ($user_state === 'waiting_for_date') {
        // Inform the user that they need to complete the date check first
        sendMessage($chat_id, "❗️ شما در حال بررسی تاریخ هستید. لطفاً تاریخ را بررسی کنید.");
    } else {
        // Define the about text with a description of the bot and its features
        $about_text = "🤖این ربات توسط دانشجویان رشته مهندسی کامپیوتر دانشگاه آزاد اسلامی واحد کرمانشاه طراحی شده است تا به شما در مدیریت بهتر زمان و برنامه‌ریزی کمک کند.
با استفاده از این ربات می‌توانید به راحتی وضعیت هفته‌های زوج و فرد را بررسی کرده و مطلع شوید که امروز یا هر تاریخ دلخواه دیگری در کدام هفته قرار دارد.\n\n🎓 این ابزار ساده و کاربردی برای دانشجویان و اساتید طراحی شده تا با اطمینان بیشتری در کلاس‌های خود شرکت کنند. همچنین امکان نمایش تمامی هفته‌های زوج و فرد ترم نیز فراهم شده است.\n\n" .
        "[🌐 کانال رشته مهندسی کامپیوتر دانشگاه آزاد کرمانشاه](https://t.me/ComputerAzadKsh)";
        
        // Send the 'about' text in Markdown format
        sendMessage($chat_id, $about_text, null, "Markdown");
    }
    break;
    // If the user selects the "📢 ارسال پیام به همه کاربران" option
case "📢 ارسال پیام به همه کاربران":
    // Check if the user is an admin by comparing chat_id with admin_chat_ids
    if (in_array($chat_id, $admin_chat_ids)) {
        // Prompt the admin to send a message for broadcasting
        sendMessage($chat_id, "✍ لطفاً پیام خود را ارسال کنید:", [
            'keyboard' => [[['text' => "↩️ خروج"]]], // Provide a "↩️ Exit" button
            'resize_keyboard' => true,  // Resize the keyboard to fit the screen
            'one_time_keyboard' => false,  // Keep the keyboard available for multiple uses
        ]);
        // Set user state to 'waiting_for_broadcast' to handle the broadcasting process
        setUserState($chat_id, 'waiting_for_broadcast');
    }
    break;
    // If the user selects the "↩️ خروج" option
case "↩️ خروج":
    // Check if the user is currently in the 'waiting_for_broadcast' state
    if ($user_state === 'waiting_for_broadcast') {
        // Cancel the broadcast message operation and return to the main menu
        $reply_markup = [
            'keyboard' => [
                [['text' => "🔍 بررسی تاریخ"], ['text' => "📅 وضعیت امروز"]],
                [['text' => "🔰 درباره ما"], ['text' => "🗓 لیست هفته‌ها"]],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
        ];

        // If the user is an admin, add the admin-specific options
        if (in_array($chat_id, $admin_chat_ids)) {
            $reply_markup['keyboard'][] = [['text' => "📢 ارسال پیام به همه کاربران"]];
            $reply_markup['keyboard'][] = [['text' => "📊 آمار کاربران"]];
        }

        // Inform the user that the broadcast mode has been exited
        sendMessage($chat_id, "❌ شما از حالت ارسال پیام خارج شدید.", $reply_markup);
        setUserState($chat_id, null); // Reset the user state
    }
    break;
    case "📊 آمار کاربران":
    // Check if the user is an admin
    if (in_array($chat_id, $admin_chat_ids)) {
        
        // Count the total number of users from "users.txt" file
        $user_count = count(explode("\n", trim(file_get_contents("users.txt"))));
        
        // Get today's date in Jalali (Persian) format
        $today = jdate('Y/m/d');
        
        // Get the week information based on today's date
        $week_info = getWeekInfo($today);
        
        // Set the current week status or default to "current term" if no info is available
        $week_status = $week_info ? $week_info['توضیح'] : "Current Term";

        // Calculate the number of users added this week
        $users = json_decode(file_get_contents($user_file), true);
        
        // Convert the week's start and end dates from Jalali to timestamps
        $week_start = jalaliToTimestamp($week_info['start']);
        $week_end = jalaliToTimestamp($week_info['end']);
        
        // Initialize counters and lists for this week's users
        $week_users_count = 0;
        $week_users_list = [];
        $all_users_list = [];

        // Loop through all users to identify those who joined this week
        foreach ($users as $user_id => $user_info) {
            // Add user to the total users list
            $all_users_list[] = "$user_id - {$user_info['username']}";
            
            // Check if the user's join date is within this week's range
            if ($user_info['joined_at'] >= $week_start && $user_info['joined_at'] <= $week_end) {
                $week_users_count++;
                $week_users_list[] = "$user_id - {$user_info['username']}";
            }
        }

        // Create the user statistics message to send to the admin
        $stats_text = "📊 User Statistics\n\n";
        $stats_text .= "👥 Total Users: $user_count\n";
        $stats_text .= "👥 Users This Week: $week_users_count\n";

        // Send the statistics message to the admin
        sendMessage($chat_id, $stats_text);

        // Create the content for the user list file and save it as a text file
        $users_file_content = implode("\n", $all_users_list);
        $users_file_path = "all_users.txt";
        file_put_contents($users_file_path, $users_file_content);

        // Check if the file was created and send it to the admin
        if (file_exists($users_file_path)) {
            $url = $api_url . "sendDocument";
            $post_fields = [
                'chat_id' => $chat_id,
                'document' => new CURLFile(realpath($users_file_path))
            ];

            // Send the request to upload the user list file to the admin
            sendRequest($url, $post_fields);
        }
    }
    break;
    default:
    // Check if the user is in the 'waiting_for_date' state
    if ($user_state === 'waiting_for_date') {
        
        // Validate that the message is in the correct date format (Gregorian or Persian numbers)
        if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $message) || preg_match('/^[۰-۹]{4}\/[۰-۹]{2}\/[۰-۹]{2}$/u', $message)) {
            
            // Convert the input date to Arabic numbers
            $message = convertPersianToArabic($message);
            
            // Get week information for the provided date
            $week_info = getWeekInfo($message);
            
            // Check if the date falls within the current term and send appropriate message
            if ($week_info) {
                sendMessage($chat_id, "❗️ تاریخ $message در {$week_info['توضیح']} قرار دارد.");
            } else {
                sendMessage($chat_id, "❗️ تاریخ $message در این ترم قرار ندارد.");
            }
        } else {
            // Notify the user to enter the date in the correct format
            sendMessage($chat_id, "❗️ لطفاً تاریخ را به فرمت YYYY/MM/DD وارد کنید.");
        }
    } elseif ($user_state === 'waiting_for_broadcast') {
    
    // Check if the received message contains a photo
    if (isset($update['message']['photo'])) {
        // Get the file ID of the largest size photo
        $photo = end($update['message']['photo'])['file_id'];
        
        // Send the photo with its caption to all users
        sendToAll($update['message']['caption'], $photo);
    
    // Check if the received message contains a voice message
    } elseif (isset($update['message']['voice'])) {
        $voice = $update['message']['voice']['file_id'];
        
        // Send the voice message with its caption to all users
        sendToAll($update['message']['caption'], null, $voice);
    
    // Otherwise, send a text message to all users
    } else {
        sendToAll($message);
    }
    
    // Confirm to the admin that the message has been sent to all users
    sendMessage($chat_id, "✅ پیام شما به همه کاربران ارسال شد.");

    // **اینجا را اضافه کنید:**
$reply_markup = [
    // Define the main keyboard layout with buttons for regular users
    'keyboard' => [
        [['text' => "🔍 بررسی تاریخ"], ['text' => "📅 وضعیت امروز"]],
        [['text' => "🔰 درباره ما"], ['text' => "🗓 لیست هفته‌ها"]],
    ],
    'resize_keyboard' => true,  // Automatically resize keyboard for optimal display
    'one_time_keyboard' => false,  // Keep the keyboard open after each message
];

// Add additional options for admin users
if (in_array($chat_id, $admin_chat_ids)) {
    $reply_markup['keyboard'][] = [['text' => "📢 ارسال پیام به همه کاربران"]];
    $reply_markup['keyboard'][] = [['text' => "📊 آمار کاربران"]];
}

// Send a message to the user confirming they have exited broadcast mode, with the updated keyboard
sendMessage($chat_id, "❌ شما از حالت ارسال پیام خارج شدید.", $reply_markup);

// Reset the user's state to null to clear any previous actions
setUserState($chat_id, null);
    }
    break;
    }
} elseif (isset($update['callback_query'])) {
    $callback_query = $update['callback_query'];
    $chat_id = $callback_query['message']['chat']['id'];
    $callback_data = $callback_query['data'];

    // Check if the callback data is for checking membership status
    if ($callback_data === "check_membership") {
        
        // If the user is in the channel, show the main keyboard
        if (isUserInChannel($chat_id)) {
            $reply_markup = [
                'keyboard' => [
                    [['text' => "🔍 بررسی تاریخ"], ['text' => "📅 وضعیت امروز"]],
                    [['text' => "🔰 درباره ما"], ['text' => "🗓 لیست هفته‌ها"]],
                ],
                'resize_keyboard' => true,  // Automatically resize keyboard for optimal display
                'one_time_keyboard' => false,  // Keep the keyboard open after each message
            ];
        
            // Only if the user is an admin, add the options for broadcasting and viewing stats
            if (in_array($chat_id, $admin_chat_ids)) {
                $reply_markup['keyboard'][] = [['text' => "📢 ارسال پیام به همه کاربران"]];
                $reply_markup['keyboard'][] = [['text' => "📊 آمار کاربران"]];
            }
        
            // Send message confirming the user is in the channel and can use the bot
            sendMessage($chat_id, "✅ شما عضو چنل شده‌اید و می‌توانید از ربات استفاده کنید.", $reply_markup);
        } else {
            // Show inline keyboard to allow the user to join the channel
            $inline_keyboard = [
                [['text' => "عضویت در چنل", 'url' => "https://t.me/$channel_username"]],
                [['text' => "✅  تایید عضویت", 'callback_data' => "check_membership"]]
            ];
            $reply_markup = ['inline_keyboard' => $inline_keyboard];
            
            // Notify the user they need to join the channel first
            sendMessage($chat_id, "❌ شما هنوز عضو چنل نشده‌اید. لطفاً دکمه زیر را بزنید و دوباره تلاش کنید.", $reply_markup);
        }
    }
}
?>
