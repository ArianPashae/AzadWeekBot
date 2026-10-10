<div align="center">

<img src="docs/icons/banner.svg" alt="AzadWeek — Smart Academic Calendar Bot & Mini App" width="100%" style="max-width: 100%; height: auto;" />

<br /><br />

<p align="center">
  <a href="https://t.me/AzadWeekBot"><img src="https://img.shields.io/badge/Telegram_Bot-@AzadWeekBot-0284C7?style=for-the-badge&logo=telegram&logoColor=white" alt="Telegram Bot" /></a>
  &nbsp;
  <a href="https://arianpashae.com"><img src="https://img.shields.io/badge/Website-ArianPashae.com-0F172A?style=for-the-badge&logo=google-chrome&logoColor=38BDF8" alt="Official Website" /></a>
  &nbsp;
  <a href="https://t.me/ArianPashaeChannel"><img src="https://img.shields.io/badge/Channel-@ArianPashaeChannel-0088CC?style=for-the-badge&logo=telegram&logoColor=white" alt="Telegram Channel" /></a>
  &nbsp;
  <a href="https://t.me/ComputerAzadKsh"><img src="https://img.shields.io/badge/CE_Channel-@ComputerAzadKsh-10B981?style=for-the-badge&logo=telegram&logoColor=white" alt="Computer Engineering Channel" /></a>
  &nbsp;
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-F59E0B?style=for-the-badge" alt="MIT License" /></a>
</p>

<p align="center">
  <img src="docs/icons/globe.svg" width="20" height="20" align="absmiddle" />
  &nbsp;<a href="#english-documentation"><b>English Documentation</b></a>
  &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
  <img src="docs/icons/globe.svg" width="20" height="20" align="absmiddle" />
  &nbsp;<a href="#مستندات-و-راهنمای-فارسی"><b>مستندات و راهنمای فارسی</b></a>
</p>

</div>

---

<a id="english-documentation"></a>

## <img src="docs/icons/globe.svg" width="26" height="26" align="absmiddle" /> English Documentation

### <img src="docs/icons/sparkles.svg" width="22" height="22" align="absmiddle" /> Overview

**AzadWeek** ([`@AzadWeekBot`](https://t.me/AzadWeekBot)) is a full-featured academic calendar platform engineered for students and faculty of **Islamic Azad University**. It pairs a high-performance **Telegram Bot backend** (`AzadWeekBot/`) with a native **Telegram Mini App & Progressive Web App** (`AzadWeek/`) to monitor **Odd (فرد)** and **Even (زوج)** semester weeks, midterms, finals, official holidays, and personal class schedules on the Persian (Jalali) calendar.

---

### <img src="docs/icons/link.svg" width="22" height="22" align="absmiddle" /> Official Links & Channels

<div align="center">

| Resource | Link |
| :--- | :--- |
| <img src="docs/icons/bot.svg" width="18" height="18" align="absmiddle" /> &nbsp;**AzadWeek Telegram Bot** | [t.me/AzadWeekBot](https://t.me/AzadWeekBot) |
| <img src="docs/icons/globe.svg" width="18" height="18" align="absmiddle" /> &nbsp;**Official Website (Arian Pashae)** | [arianpashae.com](https://arianpashae.com) |
| <img src="docs/icons/telegram.svg" width="18" height="18" align="absmiddle" /> &nbsp;**Arian Pashae Telegram Channel** | [t.me/ArianPashaeChannel](https://t.me/ArianPashaeChannel) |
| <img src="docs/icons/graduation.svg" width="18" height="18" align="absmiddle" /> &nbsp;**Computer Engineering Channel** | [t.me/ComputerAzadKsh](https://t.me/ComputerAzadKsh) |
| <img src="docs/icons/code.svg" width="18" height="18" align="absmiddle" /> &nbsp;**Developer Direct Contact** | [t.me/ArianPashae](https://t.me/ArianPashae) |

</div>

---

### <img src="docs/icons/rocket.svg" width="22" height="22" align="absmiddle" /> Key Features & Capabilities

#### <img src="docs/icons/mobile.svg" width="20" height="20" align="absmiddle" /> 1. Native Telegram Mini App & PWA (`AzadWeek/`)

- <img src="docs/icons/check.svg" width="16" height="16" align="absmiddle" /> **Live Odd/Even Week Dashboard**: Real-time indicator for the active semester week (`هفته فرد` / `هفته زوج`), current week index, remaining weeks, and an animated semester progress bar.
- <img src="docs/icons/calendar.svg" width="16" height="16" align="absmiddle" /> **17-Week Interactive Semester Timeline & Date Converter**: Browse all academic weeks with badges for add/drop periods, midterms, finals, and official holidays, or query any Jalali date (`1405/MM/DD`).
- <img src="docs/icons/graduation.svg" width="16" height="16" align="absmiddle" /> **Weekly University Class Planner**: Organize university courses by Odd, Even, or Every week with day, time slot, instructor name, room, and campus building — synced locally and across your Telegram profile.
- <img src="docs/icons/card.svg" width="16" height="16" align="absmiddle" /> **Story & Status Card Visual Generator**: Generates high-resolution `1080×1920` visual status cards and semester wrapped summaries with one-tap **Share to Telegram Story**, **Send to Bot Chat**, and **Direct Download**.
- <img src="docs/icons/calendar.svg" width="16" height="16" align="absmiddle" /> **Universal `.ics` Calendar Export**: Export all Odd/Even term weeks into Google Calendar, Apple Calendar, and Outlook, or request the file directly in the Telegram chat.
- <img src="docs/icons/bell.svg" width="16" height="16" align="absmiddle" /> **Personalized Weekly Reminders**: Configure automated notifications for Friday or Saturday at your preferred hour (`08:00`, `14:00`, `20:00`, `22:00`) with instant test delivery.
- <img src="docs/icons/sparkles.svg" width="16" height="16" align="absmiddle" /> **Telegram WebApp 8.0+ Integration**: Automatic mobile fullscreen mode, tactile haptic feedback, home-screen shortcut installation, biometric lock support, and automatic dark/light theme matching.

#### <img src="docs/icons/bot.svg" width="20" height="20" align="absmiddle" /> 2. Telegram Bot Backend (`AzadWeekBot/`)

- <img src="docs/icons/check.svg" width="16" height="16" align="absmiddle" /> **Private, Group & Inline Queries**: Check the current week, next week, or any custom Shamsi date, with full **Inline Mode** (`@AzadWeekBot`) enabled in any conversation.
- <img src="docs/icons/shield.svg" width="16" height="16" align="absmiddle" /> **Multi-Channel Membership Gate**: Enforces subscription to mandatory channels (`REQUIRED_CHANNELS`) with an in-memory TTL cache to maximize response speed.
- <img src="docs/icons/clock.svg" width="16" height="16" align="absmiddle" /> **Smart Group Mode with Auto-Delete**: Answers academic week inquiries in university student groups and automatically removes temporary responses after a configurable delay (`GROUP_STATUS_AUTODELETE_SECONDS`).
- <img src="docs/icons/settings.svg" width="16" height="16" align="absmiddle" /> **Unified Background Cron Worker (`cron_sender.php`)**:
  - **Saturday 07:00 Broadcast**: Automatically sends the new week's Odd/Even status to all subscribers every Saturday morning.
  - **Custom User Reminders**: Dispatches scheduled notifications matching each user's customized day and hour.
  - **Rate-Limited Broadcast Queue**: Delivers administrative announcements (text, media, audio, or forwarded posts) in safe batches (`40 users/minute`).
  - **Group Message Purge**: Automatically cleans up expired temporary responses in groups.
- <img src="docs/icons/chart.svg" width="16" height="16" align="absmiddle" /> **Admin Control Dashboard**: Real-time user statistics, growth trends, queued broadcasts, and one-click global recall (deletion) of the latest broadcast.

---

### <img src="docs/icons/folder.svg" width="22" height="22" align="absmiddle" /> Repository Structure

<div dir="ltr" align="left">

```text
AzadWeekBot/
├── AzadWeek/                         # Telegram Mini App (WebApp) & PWA
│   ├── index.html                    # Mini App UI and responsive styles
│   ├── aw_native.js                  # WebApp controller, planner, canvas & cloud sync
│   ├── connect.php                   # Mini App REST API (sync, reminders, cards, .ics)
│   ├── card.php                      # Server-side GD WebP status card generator
│   ├── manifest.webmanifest          # PWA Web App Manifest
│   ├── sw.js                         # Offline Service Worker
│   └── assets/                       # Icons, fonts (Vazirmatn, Aviny), templates & SDK
│
├── AzadWeekBot/                      # Telegram Bot Backend Core
│   ├── bot.php                       # Main Telegram Webhook entry point
│   ├── config.php                    # Central configuration (Token, Channels, Admins, Weeks)
│   ├── cron_sender.php               # Unified Cron worker (Broadcasts, Reminders, Auto-Delete)
│   ├── jdf.php                       # Persian (Jalali) calendar conversion library
│   ├── lib/
│   │   ├── api.php                   # Telegram Bot API client & Custom Emoji renderer
│   │   ├── database.php              # Atomic JSON storage engine
│   │   └── utils.php                 # Academic week calculation & string utilities
│   ├── assets/                       # WebP visual banners for bot commands
│   └── storage_backups/              # Protected directory for locks, caches & pending deletes
│
├── docs/icons/                       # Bespoke Premium SVG Vector Icons & Hero Banner
├── LICENSE                           # MIT License
└── README.md                         # Bilingual Documentation (EN / FA)
```

</div>

---

### <img src="docs/icons/settings.svg" width="22" height="22" align="absmiddle" /> Installation & Deployment Guide

#### <img src="docs/icons/check.svg" width="18" height="18" align="absmiddle" /> Prerequisites
- **PHP 8.0 or higher** with `curl`, `mbstring`, `json`, and `gd` (with WebP & FreeType support) enabled.
- A **valid SSL-enabled domain (HTTPS)** required by Telegram for Webhooks and Mini Apps.
- A **Telegram Bot Token** obtained from [@BotFather](https://t.me/BotFather).

#### <img src="docs/icons/terminal.svg" width="18" height="18" align="absmiddle" /> Step 1: Clone & Upload Files
Clone the repository and upload `AzadWeekBot/` and `AzadWeek/` to your web server:

<div dir="ltr" align="left">

```bash
git clone https://github.com/ArianPashae/AzadWeekBot.git
```

</div>

> `AzadWeek/connect.php` automatically resolves `AzadWeekBot/config.php` when placed in adjacent folders (`../AzadWeekBot`) or via the `AZAD_WEEK_BOT_ROOT` environment variable.

#### <img src="docs/icons/code.svg" width="18" height="18" align="absmiddle" /> Step 2: Configure `AzadWeekBot/config.php`
Open `AzadWeekBot/config.php` and fill in your environment settings:

<div dir="ltr" align="left">

```php
define('BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('BOT_USERNAME', 'AzadWeekBot');
define('BASE_URL', 'https://example.com/AzadWeekBot/');
define('MINIAPP_URL', 'https://example.com/AzadWeek/?action=app&v=17');
define('ADMIN_IDS', ['YOUR_ADMIN_TELEGRAM_ID']);
define('CRON_SECRET_KEY', 'YOUR_CRON_SECRET_KEY');
```

</div>

- Add your channel usernames to `REQUIRED_CHANNELS` (ensure the bot is an administrator in each).
- Update the `$weeks_config` array at the bottom with the Jalali start and end dates (`YYYY/MM/DD`) of your academic semester.

#### <img src="docs/icons/telegram.svg" width="18" height="18" align="absmiddle" /> Step 3: Register the Webhook
Open this URL in your web browser after replacing `<YOUR_BOT_TOKEN>` and your server domain:

<div dir="ltr" align="left">

```text
https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook?url=https://example.com/AzadWeekBot/bot.php
```

</div>

On the first incoming message, the bot automatically registers commands and configures the **Open App** menu button.

#### <img src="docs/icons/clock.svg" width="18" height="18" align="absmiddle" /> Step 4: Configure the Cron Job
Set up a single Cron Job running **every 1 minute** (`* * * * *`) via cPanel, DirectAdmin, crontab, or **[cron-job.org](https://cron-job.org)**:

<div dir="ltr" align="left">

```text
https://example.com/AzadWeekBot/cron_sender.php?secret=YOUR_CRON_SECRET_KEY
```

</div>

---

<a id="مستندات-و-راهنمای-فارسی"></a>

<div dir="rtl" align="right">

## <img src="docs/icons/globe.svg" width="26" height="26" align="absmiddle" /> مستندات و راهنمای فارسی

### <img src="docs/icons/sparkles.svg" width="22" height="22" align="absmiddle" /> معرفی پروژه

**آزادویک (AzadWeek)** یک سامانهٔ جامع شامل **ربات هوشمند تلگرام** و **مینی‌اپ اختصاصی (Telegram Mini App / PWA)** برای دانشجویان و اساتید **دانشگاه آزاد اسلامی** است. با این سامانه در هر لحظه وضعیت **هفتهٔ زوج یا فرد**، شمارهٔ هفتهٔ جاری، تقویم ۱۷ هفته‌ای ترم، روزشمار امتحانات میان‌ترم و پایان‌ترم، تعطیلات رسمی و برنامهٔ هفتگی کلاس‌ها با سرعتی بالا در دسترس شماست.

---

### <img src="docs/icons/link.svg" width="22" height="22" align="absmiddle" /> لینک‌های رسمی و راه‌های ارتباطی

</div>

<div align="center">

| عنوان | لینک دسترسی |
| :--- | :--- |
| <img src="docs/icons/bot.svg" width="18" height="18" align="absmiddle" /> &nbsp;**ربات تلگرام آزادویک** | [t.me/AzadWeekBot](https://t.me/AzadWeekBot) |
| <img src="docs/icons/globe.svg" width="18" height="18" align="absmiddle" /> &nbsp;**وب‌سایت رسمی (آرین پاشایی)** | [arianpashae.com](https://arianpashae.com) |
| <img src="docs/icons/telegram.svg" width="18" height="18" align="absmiddle" /> &nbsp;**کانال تلگرام آرین پاشایی** | [t.me/ArianPashaeChannel](https://t.me/ArianPashaeChannel) |
| <img src="docs/icons/graduation.svg" width="18" height="18" align="absmiddle" /> &nbsp;**کانال مهندسی کامپیوتر** | [t.me/ComputerAzadKsh](https://t.me/ComputerAzadKsh) |
| <img src="docs/icons/code.svg" width="18" height="18" align="absmiddle" /> &nbsp;**ارتباط مستقیم با توسعه‌دهنده** | [t.me/ArianPashae](https://t.me/ArianPashae) |

</div>

<div dir="rtl" align="right">

---

### <img src="docs/icons/rocket.svg" width="22" height="22" align="absmiddle" /> قابلیت‌ها و امکانات کلیدی

#### <img src="docs/icons/mobile.svg" width="20" height="20" align="absmiddle" /> ۱. مینی‌اپ اختصاصی تلگرام و وب‌اپلیکیشن (`AzadWeek/`)

<p>
  <img src="docs/icons/check.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>داشبورد زندهٔ وضعیت هفته:</b> نمایش لحظه‌ای وضعیت هفتهٔ جاری (فرد یا زوج)، شمارهٔ هفتهٔ ترم، تعداد هفته‌های باقی‌مانده، شمارش معکوس زنده تا پایان هفته و نوار پیشرفت ترم.
</p>

<p>
  <img src="docs/icons/calendar.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>تقویم تعاملی ۱۷ هفته‌ای و استعلام تاریخ:</b> مشاهدهٔ کامل هفته‌های ترم با برچسب تعطیلات رسمی، حذف و اضافه، میان‌ترم و امتحانات پایان‌ترم به همراه جست‌وجوی تاریخ شمسی دلخواه.
</p>

<p>
  <img src="docs/icons/graduation.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>برنامه‌ریز هوشمند کلاس‌های دانشگاهی:</b> ثبت دروس بر اساس «هردو هفته»، «فقط هفته‌های فرد» و «فقط هفته‌های زوج» همراه با نام استاد، ساعت، شمارهٔ کلاس و دانشکده با ذخیره‌سازی ابری روی اکانت تلگرام.
</p>

<p>
  <img src="docs/icons/card.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>مولد کارت استوری و کارنامهٔ ترم (Story &amp; Wrapped Card):</b> ساخت تصاویر گرافیکی باکیفیت با فونت وزیرمتن و آوینی، با امکان <b>اشتراک‌گذاری مستقیم در استوری تلگرام</b>، <b>ارسال آنی تصویر به چت ربات</b> و <b>دانلود مستقیم روی دستگاه</b>.
</p>

<p>
  <img src="docs/icons/calendar.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>خروجی استاندارد تقویم (<bdi><code>.ics</code></bdi>):</b> دانلود مستقیم یا ارسال فایل تقویم کل ترم به چت تلگرام برای افزودن یک‌جای هفته‌های زوج و فرد به Google Calendar، Apple Calendar و Outlook.
</p>

<p>
  <img src="docs/icons/bell.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>سیستم یادآور هفتگی اختصاصی:</b> تنظیم دریافت اعلان خودکار در تلگرام برای روز دلخواه (جمعه یا شنبه) و ساعت انتخابی (<bdi><code>08:00</code></bdi>، <bdi><code>14:00</code></bdi>، <bdi><code>20:00</code></bdi> یا <bdi><code>22:00</code></bdi>) به همراه دکمهٔ تست آنی یادآور از داخل مینی‌اپ.
</p>

<p>
  <img src="docs/icons/sparkles.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>تجربهٔ بومی با Telegram WebApp:</b> حالت تمام‌صفحه (Fullscreen) خودکار در موبایل، بازخورد لرزشی (Haptic Feedback)، افزودن میان‌بر به صفحهٔ اصلی گوشی (Home Screen)، قفل بیومتریک و پشتیبانی از تم تیره و روشن.
</p>

<br />

#### <img src="docs/icons/bot.svg" width="20" height="20" align="absmiddle" /> ۲. هستهٔ ربات تلگرام (`AzadWeekBot/`)

<p>
  <img src="docs/icons/check.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>پاسخ‌گویی در چت خصوصی، گروه و حالت درون‌خطی (Inline):</b> استعلام وضعیت هفتهٔ فعلی، هفتهٔ آینده، تقویم کامل ترم و تبدیل تاریخ شمسی، همراه با امکان استفادهٔ اینلاین با تایپ <bdi><code>@AzadWeekBot</code></bdi> در تمامی چت‌ها.
</p>

<p>
  <img src="docs/icons/shield.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>قفل عضویت اجباری چندکاناله:</b> بررسی عضویت کاربران در کانال‌های تعیین‌شده (<bdi><code>REQUIRED_CHANNELS</code></bdi>) با سیستم کش هوشمند برای پاسخ‌گویی بدون تأخیر.
</p>

<p>
  <img src="docs/icons/clock.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>مدیریت هوشمند در گروه‌ها و حذف خودکار:</b> پاسخ به پرسش‌های وضعیت هفته در گروه‌ها و حذف خودکار پیام‌های موقت پس از ۲۰ ثانیه جهت حفظ نظم گروه.
</p>

<p>
  <img src="docs/icons/settings.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>موتور کرون‌جاب یکپارچه (<bdi><code>cron_sender.php</code></bdi>):</b>
</p>

<div style="margin-right: 24px;">
  <p>
    <img src="docs/icons/clock.svg" width="15" height="15" align="absmiddle" />
    &nbsp;<b>اعلان خودکار شنبه‌ها ساعت ۰۷:۰۰ صبح:</b> ارسال وضعیت هفتهٔ جدید به تمام کاربران در ابتدای هر هفته.
  </p>
  <p>
    <img src="docs/icons/bell.svg" width="15" height="15" align="absmiddle" />
    &nbsp;<b>ارسال یادآورهای اختصاصی مینی‌اپ:</b> ارسال پیام یادآور هفتگی بر اساس زمان‌بندی انتخابی هر دانشجو.
  </p>
  <p>
    <img src="docs/icons/chart.svg" width="15" height="15" align="absmiddle" />
    &nbsp;<b>صف ارسال همگانی ایمن:</b> ارسال دسته‌ای پیام‌ها و فورواردهای همگانی (۴۰ کاربر در هر دقیقه) بدون محدودیت تلگرام.
  </p>
  <p>
    <img src="docs/icons/settings.svg" width="15" height="15" align="absmiddle" />
    &nbsp;<b>پاک‌سازی پیام‌های گروه:</b> حذف خودکار پیام‌های منقضی‌شدهٔ ربات در گروه‌های دانشجویی.
  </p>
</div>

<p>
  <img src="docs/icons/chart.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>پنل مدیریت پیشرفتهٔ ادمین:</b> مشاهدهٔ آمار دقیق اعضا، رشد روزانه و هفتگی، صف ارسال همگانی و امکان حذف یک‌کلیکی آخرین پیام همگانی از چت تمامی کاربران.
</p>

---

### <img src="docs/icons/settings.svg" width="22" height="22" align="absmiddle" /> آموزش نصب و راه‌اندازی گام‌به‌گام

#### <img src="docs/icons/check.svg" width="18" height="18" align="absmiddle" /> پیش‌نیازها

<p>
  <img src="docs/icons/check.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>PHP نسخهٔ ۸.۰ یا بالاتر</b> به همراه افزونه‌های <bdi><code>curl</code></bdi>، <bdi><code>mbstring</code></bdi>، <bdi><code>json</code></bdi> و <bdi><code>gd</code></bdi> (با پشتیبانی از <bdi>WebP</bdi> و <bdi>FreeType</bdi>).
</p>

<p>
  <img src="docs/icons/check.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>دامنه و هاست مجهز به گواهی SSL (پروتکل HTTPS)</b> جهت ثبت وبهوک و اجرای مینی‌اپ تلگرام.
</p>

<p>
  <img src="docs/icons/check.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>توکن ربات تلگرام</b> دریافت‌شده از <a href="https://t.me/BotFather">@BotFather</a>.
</p>

<br />

#### <img src="docs/icons/terminal.svg" width="18" height="18" align="absmiddle" /> گام اول: دریافت سورس و آپلود روی هاست

<p>مخزن پروژه را کلون یا دانلود کنید:</p>

</div>

<div dir="ltr" align="left">

```bash
git clone https://github.com/ArianPashae/AzadWeekBot.git
```

</div>

<div dir="rtl" align="right">

<p>پوشه‌های <code>AzadWeekBot</code> و <code>AzadWeek</code> را روی هاست خود آپلود کنید. فایل <code>AzadWeek/connect.php</code> به‌صورت خودکار پوشهٔ <code>AzadWeekBot</code> را در کنار مینی‌اپ یا از طریق متغیر محیطی <code>AZAD_WEEK_BOT_ROOT</code> شناسایی می‌کند.</p>

<br />

#### <img src="docs/icons/code.svg" width="18" height="18" align="absmiddle" /> گام دوم: پیکربندی فایل `AzadWeekBot/config.php`

<p>فایل <code>AzadWeekBot/config.php</code> را باز کرده و مقادیر نمونه را با اطلاعات اختصاصی خود جایگزین کنید:</p>

</div>

<div dir="ltr" align="left">

```php
define('BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('BOT_USERNAME', 'AzadWeekBot');
define('BASE_URL', 'https://example.com/AzadWeekBot/');
define('MINIAPP_URL', 'https://example.com/AzadWeek/?action=app&v=17');
define('ADMIN_IDS', ['YOUR_ADMIN_TELEGRAM_ID']);
define('CRON_SECRET_KEY', 'YOUR_CRON_SECRET_KEY');
```

</div>

<div dir="rtl" align="right">

<p>
  <img src="docs/icons/check.svg" width="16" height="16" align="absmiddle" />
  &nbsp;در آرایهٔ <code>REQUIRED_CHANNELS</code> آیدی و لینک کانال‌های موردنظر برای قفل عضویت اجباری را قرار دهید (ربات باید در این کانال‌ها ادمین باشد).
</p>

<p>
  <img src="docs/icons/check.svg" width="16" height="16" align="absmiddle" />
  &nbsp;در آرایهٔ <code>$weeks_config</code> در انتهای فایل <code>config.php</code>، تاریخ شروع و پایان هفته‌های ترم تحصیلی جدید را به شمسی وارد کنید.
</p>

<br />

#### <img src="docs/icons/telegram.svg" width="18" height="18" align="absmiddle" /> گام سوم: ثبت وبهوک (Webhook) تلگرام

<p>آدرس زیر را پس از جایگزینی توکن ربات و دامنهٔ خود در مرورگر باز کنید تا وبهوک ثبت شود:</p>

</div>

<div dir="ltr" align="left">

```text
https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook?url=https://example.com/AzadWeekBot/bot.php
```

</div>

<div dir="rtl" align="right">

<p>پس از دریافت اولین پیام، ربات به‌صورت خودکار دستورات منو و دکمهٔ <b>Open App</b> را روی آدرس مینی‌اپ شما تنظیم می‌کند.</p>

<br />

#### <img src="docs/icons/clock.svg" width="18" height="18" align="absmiddle" /> گام چهارم: تنظیم کرون‌جاب (Cron Job)

<p>برای فعال‌سازی اعلان شنبه‌ها، یادآورهای هفتگی، صف ارسال همگانی و پاک‌سازی پیام‌های گروه، یک کرون‌جاب با بازهٔ زمانی <b>هر ۱ دقیقه (<bdi><code>* * * * *</code></bdi>)</b> روی آدرس زیر ایجاد کنید:</p>

</div>

<div dir="ltr" align="left">

```text
https://example.com/AzadWeekBot/cron_sender.php?secret=YOUR_CRON_SECRET_KEY
```

</div>

<div dir="rtl" align="right">

---

### <img src="docs/icons/shield.svg" width="22" height="22" align="absmiddle" /> توسعه‌دهنده و مجوز انتشار

<p>این پروژه توسط <b>آرین پاشایی (Arian Pashae)</b> طراحی و توسعه یافته و تحت مجوز متن‌باز <a href="LICENSE"><b>MIT License</b></a> منتشر شده است.</p>

<p>
  <img src="docs/icons/globe.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>وب‌سایت رسمی:</b> <a href="https://arianpashae.com">arianpashae.com</a>
</p>

<p>
  <img src="docs/icons/telegram.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>کانال تلگرام آرین پاشایی:</b> <a href="https://t.me/ArianPashaeChannel">@ArianPashaeChannel</a>
</p>

<p>
  <img src="docs/icons/graduation.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>کانال مهندسی کامپیوتر:</b> <a href="https://t.me/ComputerAzadKsh">@ComputerAzadKsh</a>
</p>

<p>
  <img src="docs/icons/bot.svg" width="16" height="16" align="absmiddle" />
  &nbsp;<b>ربات تلگرام آزادویک:</b> <a href="https://t.me/AzadWeekBot">@AzadWeekBot</a>
</p>

</div>
