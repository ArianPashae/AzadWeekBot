<div align="center">

<img src="AzadWeek/assets/icon-512.png" alt="AzadWeek Logo" width="128" height="128" style="border-radius: 28px;" />

# 🎓 AzadWeek | آزادویک

**Smart Telegram Bot & Native Mini App (WebApp / PWA) for Islamic Azad University Odd & Even Academic Weeks**  
**ربات هوشمند تلگرام و مینی‌اپ اختصاصی تقویم آموزشی هفته‌های زوج و فرد دانشگاه آزاد اسلامی**

[![Telegram Bot](https://img.shields.io/badge/Telegram_Bot-@AzadWeekBot-26A5E4?style=for-the-badge&logo=telegram&logoColor=white)](https://t.me/AzadWeekBot)
[![Author Website](https://img.shields.io/badge/Website-ArianPashae.com-0F172A?style=for-the-badge&logo=google-chrome&logoColor=38BDF8)](https://arianpashae.com)
[![Telegram Channel](https://img.shields.io/badge/Channel-@ArianPashaeChannel-0088CC?style=for-the-badge&logo=telegram&logoColor=white)](https://t.me/ArianPashaeChannel)
[![Uni Channel](https://img.shields.io/badge/CE_Channel-@ComputerAzadKsh-10B981?style=for-the-badge&logo=telegram&logoColor=white)](https://t.me/ComputerAzadKsh)
[![License: MIT](https://img.shields.io/badge/License-MIT-F59E0B?style=for-the-badge)](LICENSE)

<br />

[**🇬🇧 English Documentation**](#-english-documentation) &nbsp;•&nbsp; [**🇮🇷 مستندات و راهنمای فارسی**](#-مستندات-و-راهنمای-فارسی)

</div>

---

## 🇬🇧 English Documentation

### ✨ Overview

**AzadWeek** (`@AzadWeekBot`) is a full-stack academic calendar platform built specifically for students and faculty of **Islamic Azad University**. It combines a high-speed **Telegram Bot** (`AzadWeekBot/`) with a native **Telegram Mini App & Progressive Web App** (`AzadWeek/`) to track **Odd (فرد)** and **Even (زوج)** semester weeks, midterms, finals, holidays, and personal class schedules on the Persian (Jalali) calendar.

### 🔗 Quick Links

| Resource | Link |
| :--- | :--- |
| 🤖 **AzadWeek Telegram Bot** | [t.me/AzadWeekBot](https://t.me/AzadWeekBot) |
| 🌐 **Developer Official Website** | [arianpashae.com](https://arianpashae.com) |
| 📢 **Arian Pashae Telegram Channel** | [t.me/ArianPashaeChannel](https://t.me/ArianPashaeChannel) |
| 🎓 **Computer Engineering Channel** | [t.me/ComputerAzadKsh](https://t.me/ComputerAzadKsh) |
| 💬 **Direct Support & Contact** | [t.me/ArianPashae](https://t.me/ArianPashae) |

---

### 🚀 Key Features

#### 📱 1. Native Telegram Mini App & PWA (`AzadWeek/`)
- **Live Odd/Even Week Dashboard**: Instant visual indicator for the current academic week (`هفته فرد` / `هفته زوج`), week number, remaining weeks, and live semester progress bar.
- **Interactive Semester Timeline & Date Converter**: Jump to any Persian date in the semester (`1405/MM/DD`) or inspect all 17 weeks at a glance with holiday and exam badges.
- **Personal Weekly Class Planner**: Add, edit, and filter your university courses by Odd/Even/Every week, day of week, time slot, instructor, classroom, and building — synced both locally and with your Telegram account.
- ** Exam Countdown & Academic Events**: Tracks midterm weeks, final exam dates, and official holidays.
- **Dynamic Story & Status Card Generator**: Generates high-resolution `1080×1920` WebP/PNG visual cards (Current Status Card & Semester Wrapped Card) using HTML5 Canvas + PHP GD (`card.php`), with one-tap **Share to Telegram Story**, **Send to Bot Chat**, and **Direct Device Download**.
- **Universal `.ics` Calendar Export**: Exports the entire semester's Odd/Even schedule as a standard iCalendar (`.ics`) file compatible with Google Calendar, Apple Calendar, and Outlook.
- **Custom Weekly Reminders**: Users can configure automated weekly Telegram notifications for their preferred day (Friday or Saturday) and time (`08:00`, `14:00`, `20:00`, `22:00`), plus trigger an instant sample reminder from inside the Mini App.
- **Native Telegram WebApp Integration**: Full support for Telegram Bot API 8.0+ fullscreen mode on mobile devices, haptic feedback, native home-screen shortcut installation, biometric lock, and dark/light theme synchronization.

#### 🤖 2. Telegram Bot Backend (`AzadWeekBot/`)
- **Instant Week Inquiry**: Check the current week status, next week's status, or query any specific Jalali date (`1405/07/15`) via private chat, groups, or **Inline Mode** (`@AzadWeekBot`).
- **Smart Group Mode & Auto-Delete**: Responds to natural triggers in university groups (`هفته زوج یا فرد`, `/week`, `/status`) and automatically cleans up temporary bot messages after a configurable delay (`GROUP_STATUS_AUTODELETE_SECONDS`).
- **Multi-Channel Mandatory Membership Lock**: Supports forced subscription checks (`REQUIRED_CHANNELS`) with smart caching (`MEMBERSHIP_CACHE_TTL`) and one-tap verification buttons.
- **Automated Cron Engine (`cron_sender.php`)**:
  1. **Saturday 07:00 Broadcast**: Automatically notifies all users of the new week's Odd/Even status every Saturday morning.
  2. **Personalized Mini App Reminders**: Delivers user-scheduled reminders on their chosen day and hour.
  3. **Queue-Based Admin Broadcasts**: Sends text, photo, video, voice, GIF, or forwarded announcements in safe batches (`40 users/run`) without hitting Telegram flood limits.
  4. **Group Message Auto-Delete**: Cleans up expired group status responses in the background.
- **Comprehensive Admin Panel**: Real-time user statistics, growth metrics, queued broadcasting, and one-click deletion of the last broadcast across all users.

---

### 📂 Repository Structure

```text
AzadWeekBot/
├── AzadWeek/                         # 📱 Telegram Mini App (WebApp) & PWA
│   ├── index.html                    # Main Mini App UI & styles
│   ├── aw_native.js                  # Native Telegram WebApp logic, planner, canvas & sync
│   ├── connect.php                   # Mini App REST API (sync, reminders, .ics & card delivery)
│   ├── card.php                      # Server-side GD visual card renderer (WebP)
│   ├── manifest.webmanifest          # PWA Web App Manifest
│   ├── sw.js                         # Offline-capable Service Worker
│   └── assets/                       # Icons, fonts (Vazirmatn, Aviny), templates & Telegram SDK
│
├── AzadWeekBot/                      # 🤖 Telegram Bot Core Backend
│   ├── bot.php                       # Main Telegram Webhook handler
│   ├── config.php                    # Central configuration (Token, Channels, Admins, Semester Weeks)
│   ├── cron_sender.php               # Background Cron worker (Reminders, Broadcasts, Cleanup)
│   ├── jdf.php                       # Jalali (Shamsi) calendar library
│   ├── lib/
│   │   ├── api.php                   # Telegram Bot API wrapper & Custom Emoji renderer
│   │   ├── database.php              # JSON file storage engine with atomic locking
│   │   └── utils.php                 # Semester calculation & text normalization helpers
│   ├── assets/                       # WebP stickers/banners for bot responses
│   └── storage_backups/              # Protected directory for locks, caches & pending deletes
│
├── LICENSE                           # MIT License
└── README.md                         # Bilingual Documentation (EN / FA)
```

---

### 🛠️ Installation & Setup Guide

#### Prerequisites
- **PHP 8.0+** with `curl`, `mbstring`, `json`, and `gd` (with WebP & FreeType support) extensions enabled.
- An **HTTPS domain** (SSL certificate is required by Telegram for both Webhooks and Mini Apps).
- A **Telegram Bot Token** from [@BotFather](https://t.me/BotFather).

#### Step 1: Upload Files to Your Server
1. Clone or download this repository:
   ```bash
   git clone https://github.com/ArianPashae/AzadWeekBot.git
   ```
2. Upload the `AzadWeekBot/` directory to your bot path (e.g., `https://yourdomain.com/University/AzadWeekBot/`) and `AzadWeek/` to your Mini App path (e.g., `https://yourdomain.com/UniWebsite/AzadWeek/`).
   > **Note:** `AzadWeek/connect.php` automatically resolves `AzadWeekBot/config.php` when placed side-by-side (`../AzadWeekBot`) or via the `AZAD_WEEK_BOT_ROOT` environment variable.

#### Step 2: Configure `AzadWeekBot/config.php`
Open `AzadWeekBot/config.php` and set your credentials and URLs:
```php
define('BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('BOT_USERNAME', 'AzadWeekBot');
define('BASE_URL', 'https://yourdomain.com/University/AzadWeekBot/');
define('MINIAPP_URL', 'https://yourdomain.com/UniWebsite/AzadWeek/?action=app&v=17');
define('ADMIN_IDS', ['5472263975']);
define('CRON_SECRET_KEY', 'YOUR_CRON_SECRET_KEY');
```
- Update `REQUIRED_CHANNELS` with your channel usernames (make sure the bot is an administrator in those channels).
- Update `$weeks_config` at the bottom of `config.php` with the start and end dates (`YYYY/MM/DD` in Jalali) for each week of the new semester.

#### Step 3: Set the Telegram Webhook
Open the following URL in your browser (replace `<BOT_TOKEN>` and your domain):
```text
https://api.telegram.org/bot<BOT_TOKEN>/setWebhook?url=https://yourdomain.com/University/AzadWeekBot/bot.php
```
Once the first user interacts with the bot, `bot.php` automatically registers the slash commands and configures the **Open App** menu button via `syncBotCommandsOnce()`.

#### Step 4: Configure the Cron Job
Set up a single Cron Job in **cPanel / DirectAdmin** or an external service like **[cron-job.org](https://cron-job.org)** to run **every 1 minute** (`* * * * *`):
```text
https://yourdomain.com/University/AzadWeekBot/cron_sender.php?secret=YOUR_CRON_SECRET_KEY
```
Or via server crontab (`* * * * *`):
```bash
curl -s "https://yourdomain.com/University/AzadWeekBot/cron_sender.php?secret=YOUR_CRON_SECRET_KEY" >/dev/null 2>&1
```

---

<div dir="rtl" align="right">## 🇮🇷 مستندات و راهنمای فارسی

### ✨ معرفی پروژه

**آزادویک (AzadWeek)** یک سامانهٔ یکپارچه شامل **ربات تلگرام** و **مینی‌اپ اختصاصی (Telegram Mini App / PWA)** برای دانشجویان و اساتید **دانشگاه آزاد اسلامی** است. این پروژه به شما کمک می‌کند در هر لحظه وضعیت **هفتهٔ زوج یا فرد** ترم جاری، شمارهٔ هفته، تقویم کامل ترم، روزشمار امتحانات میان‌ترم و پایان‌ترم، و برنامهٔ کلاسی هفتگی خود را مشاهده و مدیریت کنید.

### 🔗 لینک‌های ارتباطی و دسترسی سریع

| عنوان | لینک دسترسی |
| :--- | :--- |
| 🤖 **ربات تلگرام آزادویک** | [t.me/AzadWeekBot](https://t.me/AzadWeekBot) |
| 🌐 **وب‌سایت رسمی توسعه‌دهنده (آرین پاشایی)** | [arianpashae.com](https://arianpashae.com) |
| 📢 **کانال تلگرام آرین پاشایی** | [t.me/ArianPashaeChannel](https://t.me/ArianPashaeChannel) |
| 🎓 **کانال مهندسی کامپیوتر دانشگاه آزاد کرمانشاه** | [t.me/ComputerAzadKsh](https://t.me/ComputerAzadKsh) |
| 💬 **پشتیبانی و ارتباط مستقیم در تلگرام** | [t.me/ArianPashae](https://t.me/ArianPashae) |

---

### 🚀 قابلیت‌ها و امکانات کلیدی

#### 📱 ۱. مینی‌اپ تلگرام و وب‌اپلیکیشن پیش‌رونده (`AzadWeek/`)
- **داشبورد زندهٔ وضعیت هفته**: نمایش آنی وضعیت هفتهٔ جاری (فرد یا زوج)، شمارهٔ هفتهٔ ترم، تعداد هفته‌های باقی‌مانده و نوار پیشرفت ترم به همراه روزشمار زنده تا پایان هفته.
- **تقویم کامل ۱۷ هفته‌ای و مبدل تاریخ**: مشاهدهٔ تمامی هفته‌های ترم با برچسب تعطیلات رسمی، بازهٔ حذف و اضافه، میان‌ترم و امتحانات پایان‌ترم، به همراه جست‌وجوی وضعیت هر تاریخ دلخواه شمسی.
- **برنامه‌ریز هوشمند کلاس‌های هفتگی**: ثبت دروس دانشگاهی با تفکیک «هردو هفته»، «فقط هفته‌های فرد» و «فقط هفته‌های زوج»، نام استاد، شمارهٔ کلاس و دانشکده، با همگام‌سازی خودکار روی سرور تلگرام.
- **ساخت کارت استوری و تصویر وضعیت (Story & Wrapped Card)**: تولید کارت‌های گرافیکی باکیفیت با فونت وزیرمتن و آوینی، با قابلیت **اشتراک‌گذاری مستقیم در استوری تلگرام**، **ارسال آنی به چت ربات** و **دانلود مستقیم روی گوشی یا سیستم**.
- **خروجی تقویم استاندارد (`.ics`)**: افزودن تمام هفته‌های زوج و فرد ترم به تقویم گوگل (Google Calendar)، تقویم اپل (iOS Calendar) و ویندوز با یک کلیک یا دریافت مستقیم فایل در چت تلگرام.
- **یادآور هفتگی شخصی‌سازی‌شده**: تنظیم دریافت پیام یادآور خودکار در روز دلخواه (جمعه یا شنبه) و ساعت انتخابی (`08:00`، `14:00`، `20:00` یا `22:00`) به همراه دکمهٔ «ارسال نمونه پیام یادآور» برای تست آنی.
- **پشتیبانی کامل از قابلیت‌های بومی تلگرام**: حالت تمام‌صفحه (Fullscreen) خودکار در موبایل، بازخورد لرزشی (Haptic Feedback)، افزودن میان‌بر به صفحهٔ اصلی گوشی (Add to Home Screen)، قفل بیومتریک و هماهنگی کامل با تم تیره و روشن.

#### 🤖 ۲. هستهٔ ربات تلگرام (`AzadWeekBot/`)
- **استعلام سریع در چت خصوصی، گروه و حالت درون‌خطی (Inline)**: پاسخ‌گویی آنی به دستورات و پیام‌های متنی (مانند «هفته زوج یا فرد»، «هفته بعد»، یا ارسال تاریخ به صورت `1405/07/15`) و پشتیبانی از حالت اینلاین `@AzadWeekBot` در هر چتی.
- **مدیریت هوشمند در گروه‌های دانشجویی**: پاسخ به پرسش‌های وضعیت هفته در گروه‌ها و حذف خودکار پیام‌های موقت پس از ۲۰ ثانیه برای جلوگیری از شلوغی گروه.
- **قفل عضویت اجباری چندکاناله**: بررسی عضویت کاربران در کانال‌های تعیین‌شده (`REQUIRED_CHANNELS`) با کش هوشمند ۳ دقیقه‌ای برای سرعت پاسخ‌گویی بالا.
- **موتور کرون‌جاب چندمنظوره (`cron_sender.php`)**:
  ۱. **اعلان خودکار شنبه‌ها ساعت ۰۷:۰۰ صبح**: ارسال وضعیت هفتهٔ جدید به تمامی اعضا در ابتدای هر هفته.
  ۲. **ارسال یادآورهای اختصاصی مینی‌اپ**: ارسال نوتیفیکیشن شخصی بر اساس روز و ساعت انتخابی هر دانشجو.
  ۳. **ارسال همگانی صف‌بندی‌شده (Broadcast Queue)**: ارسال پیام‌های متنی، عکس، ویدیو، ویس، گیف و فوروارد به صورت دسته‌ای (۴۰ کاربر در هر دقیقه) بدون برخورد با محدودیت‌های تلگرام.
  ۴. **پاک‌سازی خودکار پیام‌های گروه**: حذف پیام‌های زمان‌بندی‌شدهٔ ربات در گروه‌ها.
- **پنل مدیریت پیشرفتهٔ ادمین**: مشاهدهٔ آمار دقیق اعضا، رشد روزانه و هفتگی، ارسال پیام همگانی یا فوروارد همگانی و قابلیت حذف آخرین پیام همگانی ارسال‌شده از چت تمام کاربران.

---

### 🛠️ آموزش نصب و راه‌اندازی گام‌به‌گام

#### پیش‌نیازها
- **PHP نسخهٔ ۸.۰ یا بالاتر** به همراه افزونه‌های `curl`، `mbstring`، `json` و `gd` (با پشتیبانی از WebP و FreeType).
- **هاست دارای گواهی SSL (پروتکل HTTPS)** برای اتصال وبهوک و اجرای مینی‌اپ تلگرام.
- **توکن ربات تلگرام** دریافت‌شده از [@BotFather](https://t.me/BotFather).

#### گام اول: آپلود فایل‌ها روی هاست
۱. سورس پروژه را کلون یا دانلود کنید:
```bash
git clone https://github.com/ArianPashae/AzadWeekBot.git
```
۲. پوشهٔ `AzadWeekBot` و پوشهٔ `AzadWeek` را روی هاست خود آپلود کنید.
> فایل `AzadWeek/connect.php` به صورت خودکار مسیر پوشهٔ `AzadWeekBot` را در کنار خود (`../AzadWeekBot`) یا در مسیر `University/AzadWeekBot` شناسایی می‌کند.

#### گام دوم: ویرایش تنظیمات در `AzadWeekBot/config.php`
فایل `AzadWeekBot/config.php` را باز کرده و مقادیر زیر را متناسب با ربات و دامنهٔ خود جایگزین کنید:
- `BOT_TOKEN`: توکن ربات تلگرام خود را قرار دهید.
- `BOT_USERNAME`: نام کاربری ربات بدون `@` (مانند `AzadWeekBot`).
- `BASE_URL`: آدرس کامل پوشهٔ ربات روی هاست شما (همراه با `/` انتهایی).
- `MINIAPP_URL`: آدرس کامل پوشهٔ مینی‌اپ روی هاست شما (مانند `https://yourdomain.com/UniWebsite/AzadWeek/?action=app&v=17`).
- `ADMIN_IDS`: آرایه‌ای از آیدی‌های عددی ادمین‌های ربات.
- `CRON_SECRET_KEY`: یک کلید امنیتی دلخواه برای اجرای امن فایل کرون‌جاب.
- `REQUIRED_CHANNELS`: لیست کانال‌های قفل عضویت اجباری (ربات باید در این کانال‌ها ادمین باشد).
- `$weeks_config`: تاریخ شروع و پایان ۱۷ هفتهٔ ترم تحصیلی جاری به شمسی.

#### گام سوم: ثبت وبهوک (Webhook) تلگرام
آدرس زیر را با جایگزینی توکن ربات و دامنهٔ خود در مرورگر باز کنید تا وبهوک ست شود:
```text
https://api.telegram.org/bot<BOT_TOKEN>/setWebhook?url=https://yourdomain.com/University/AzadWeekBot/bot.php
```
پس از اولین اجرای ربات، منوی دستورات و دکمهٔ **Open App** به صورت خودکار برای ربات تنظیم می‌شوند.

#### گام چهارم: تنظیم کرون‌جاب (Cron Job)
برای فعال‌سازی ارسال خودکار شنبه‌ها، یادآورهای شخصی مینی‌اپ، صف ارسال همگانی و پاک‌سازی پیام‌های گروه، فقط **یک کرون‌جاب با بازهٔ زمانی هر ۱ دقیقه (`* * * * *`)** روی هاست خود یا در سرویس **[cron-job.org](https://cron-job.org)** روی آدرس زیر تنظیم کنید:
```text
https://yourdomain.com/University/AzadWeekBot/cron_sender.php?secret=YOUR_CRON_SECRET_KEY
```

---

### 👤 توسعه‌دهنده و مجوز انتشار

این پروژه توسط **آرین پاشایی (Arian Pashae)** طراحی و توسعه داده شده و تحت مجوز متن‌باز **[MIT License](LICENSE)** منتشر شده است.

- 🌐 **وب‌سایت شخصی**: [arianpashae.com](https://arianpashae.com)
- 📢 **کانال تلگرام**: [@ArianPashaeChannel](https://t.me/ArianPashaeChannel)
- 🎓 **کانال مهندسی کامپیوتر**: [@ComputerAzadKsh](https://t.me/ComputerAzadKsh)
- 🤖 **ربات آزادویک**: [@AzadWeekBot](https://t.me/AzadWeekBot)

</div>
