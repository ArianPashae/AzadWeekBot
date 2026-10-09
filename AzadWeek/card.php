<?php

function aw_param($key, $default = '') { return trim((string)($_GET[$key] ?? $default)); }

$fmt = strtolower(aw_param('format', aw_param('ext', 'webp')));
if ($fmt === 'png') {
    header('Content-Type: image/png');
} else {
    header('Content-Type: image/webp');
}
if (aw_param('download') === '1') {
    $fn = (aw_param('type') === 'wrapped' ? 'AzadWeek-Wrapped' : 'AzadWeek-Status') . '.' . ($fmt === 'png' ? 'png' : 'webp');
    header('Content-Disposition: attachment; filename="' . $fn . '"');
}
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
function aw_digits_en($s) {
    return str_replace(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩'], ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'], (string)$s);
}
function aw_digits_fa($s) {
    return str_replace(['0','1','2','3','4','5','6','7','8','9','٠','١','٢','٣','٤','٥','٦','٧','٨','٩'], ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'], (string)$s);
}
function aw_status_fa($status) {
    $s = trim((string)$status);
    if ($s === '') return 'وضعیت هفته';
    if (strpos($s, 'فرد') !== false || stripos($s, 'odd') !== false) return 'هفته فرد';
    if (strpos($s, 'زوج') !== false || stripos($s, 'even') !== false) return 'هفته زوج';
    if (strpos($s, 'خارج') !== false || stripos($s, 'outside') !== false) return 'خارج از بازه ترم';
    return $s;
}
function aw_week_hint($status_fa) {
    if (strpos($status_fa, 'فرد') !== false) return 'برنامه‌ات را با این هفته هماهنگ کن';
    if (strpos($status_fa, 'زوج') !== false) return 'برنامه‌ات را با این هفته هماهنگ کن';
    return 'این تاریخ در بازه فعال ترم نیست';
}
function aw_strip_diacritics($s) {
    return preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', (string)$s);
}
function aw_status_poems() {
    return [
        ['توانا بود هر که دانا بود', 'ز دانش دل پیر برنا بود'],
        ['علم، چراغی‌ست در دستِ انسان', 'تحصیل، راهی‌ست از خاک تا آسمان'],
        ['هر واژه که می‌آموزم، جهانی تازه است', 'دانایی، آغازِ پروازِ بی‌اندازه است'],
        ['درس اگر سخت است، ریشه دارد', 'هر درختِ بلند، صبرِ بیشه دارد'],
        ['مدادِ کوچک، رؤیای بزرگ می‌نویسد', 'دانش، سرنوشتِ فردا را می‌سازد'],
        ['تحصیل یعنی از تاریکی گذشتن', 'با نورِ فهم، خود را دوباره نوشتن'],
        ['کتاب، سکوتی‌ست پر از صدا', 'هر صفحه‌اش راهی به سمتِ خدا'],
        ['هر سؤال، دری‌ست رو به بیداری', 'هر پاسخ، قدمی به سوی دانایی'],
        ['درس خواندن فقط حفظِ کلمات نیست', 'ساختنِ خویش است، با رنجی که بی‌ثمر نیست'],
        ['دانش اگر در دل بنشیند، نور می‌شود', 'انسانِ آگاه، خودش مسیر می‌شود'],
        ['تحصیل، نردبانِ آرامِ امید است', 'پایانِ جهل و آغازِ سپید است'],
    ];
}
function aw_random_poem() {
    $all = aw_status_poems();
    if (!is_array($all) || !count($all)) return ['توانا بود هر که دانا بود', 'ز دانش دل پیر برنا بود'];
    return $all[array_rand($all)];
}
function aw_fav_fa($fav) {
    $fav = trim((string)$fav);
    if ($fav === '' || stripos($fav, 'home') !== false || strpos($fav, 'خانه') !== false) return 'خانه';
    if (stripos($fav, 'time') !== false || stripos($fav, 'search') !== false || strpos($fav, 'ماشین') !== false) return 'ماشین زمان';
    if (stripos($fav, 'term') !== false || stripos($fav, 'list') !== false || strpos($fav, 'ترم') !== false) return 'نقشه ترم';
    return $fav;
}
function aw_hex($im, $hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    return imagecolorallocate($im, hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2)));
}
function aw_font($bold = false) {
    $local = $bold ? [
        __DIR__ . '/assets/fonts/Vazirmatn-Bold.ttf',
        __DIR__ . '/assets/fonts/Vazirmatn-Regular.ttf',
        __DIR__ . '/assets/fonts/IRANSans-Bold.ttf',
        __DIR__ . '/assets/fonts/AVINY.TTF',
    ] : [
        __DIR__ . '/assets/fonts/Vazirmatn-Regular.ttf',
        __DIR__ . '/assets/fonts/Vazirmatn-Bold.ttf',
        __DIR__ . '/assets/fonts/IRANSans.ttf',
        __DIR__ . '/assets/fonts/AVINY.TTF',
    ];
    $system = [
        '/usr/share/fonts/truetype/dejavu/' . ($bold ? 'DejaVuSans-Bold.ttf' : 'DejaVuSans.ttf'),
        '/usr/share/fonts/truetype/noto/NotoNaskhArabic-Regular.ttf',
        '/usr/share/fonts/truetype/noto/NotoSansArabic-Regular.ttf',
        '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
        'C:\\Windows\\Fonts\\tahoma.ttf',
        'C:\\Windows\\Fonts\\arial.ttf',
    ];
    foreach (array_merge($local, $system) as $f) if (is_file($f)) return $f;
    return '';
}
function aw_split_chars($s) {
    $arr = preg_split('//u', (string)$s, -1, PREG_SPLIT_NO_EMPTY);
    return is_array($arr) ? $arr : [];
}
function aw_join_forms() {
    static $forms = null;
    if ($forms !== null) return $forms;
    $forms = [
        'ا'=>['ﺍ','ﺎ',null,null], 'آ'=>['ﺁ','ﺂ',null,null], 'أ'=>['ﺃ','ﺄ',null,null], 'إ'=>['ﺇ','ﺈ',null,null], 'د'=>['ﺩ','ﺪ',null,null], 'ذ'=>['ﺫ','ﺬ',null,null], 'ر'=>['ﺭ','ﺮ',null,null], 'ز'=>['ﺯ','ﺰ',null,null], 'ژ'=>['ﮊ','ﮋ',null,null], 'و'=>['ﻭ','ﻮ',null,null],
        'ب'=>['ﺏ','ﺐ','ﺑ','ﺒ'], 'پ'=>['ﭖ','ﭗ','ﭘ','ﭙ'], 'ت'=>['ﺕ','ﺖ','ﺗ','ﺘ'], 'ث'=>['ﺙ','ﺚ','ﺛ','ﺜ'], 'ج'=>['ﺝ','ﺞ','ﺟ','ﺠ'], 'چ'=>['ﭺ','ﭻ','ﭼ','ﭽ'], 'ح'=>['ﺡ','ﺢ','ﺣ','ﺤ'], 'خ'=>['ﺥ','ﺦ','ﺧ','ﺨ'],
        'س'=>['ﺱ','ﺲ','ﺳ','ﺴ'], 'ش'=>['ﺵ','ﺶ','ﺷ','ﺸ'], 'ص'=>['ﺹ','ﺺ','ﺻ','ﺼ'], 'ض'=>['ﺽ','ﺾ','ﺿ','ﻀ'], 'ط'=>['ﻁ','ﻂ','ﻃ','ﻄ'], 'ظ'=>['ﻅ','ﻆ','ﻇ','ﻈ'], 'ع'=>['ﻉ','ﻊ','ﻋ','ﻌ'], 'غ'=>['ﻍ','ﻎ','ﻏ','ﻐ'],
        'ف'=>['ﻑ','ﻒ','ﻓ','ﻔ'], 'ق'=>['ﻕ','ﻖ','ﻗ','ﻘ'], 'ک'=>['ﮎ','ﮏ','ﮐ','ﮑ'], 'ك'=>['ﻙ','ﻚ','ﻛ','ﻜ'], 'گ'=>['ﮒ','ﮓ','ﮔ','ﮕ'], 'ل'=>['ﻝ','ﻞ','ﻟ','ﻠ'], 'م'=>['ﻡ','ﻢ','ﻣ','ﻤ'], 'ن'=>['ﻥ','ﻦ','ﻧ','ﻨ'],
        'ه'=>['ﻩ','ﻪ','ﻫ','ﻬ'], 'ة'=>['ﺓ','ﺔ',null,null], 'ی'=>['ﯼ','ﯽ','ﯾ','ﯿ'], 'ي'=>['ﻱ','ﻲ','ﻳ','ﻴ'], 'ى'=>['ﻯ','ﻰ',null,null], 'ئ'=>['ﺉ','ﺊ','ﺋ','ﺌ']
    ];
    return $forms;
}
function aw_can_join_prev($ch) { $f = aw_join_forms(); return isset($f[$ch]) && $f[$ch][1] !== null; }
function aw_can_join_next($ch) { $f = aw_join_forms(); return isset($f[$ch]) && $f[$ch][2] !== null; }
function aw_shape_rtl($text) {
    $text = aw_strip_diacritics($text);
    $chars = aw_split_chars($text);
    $forms = aw_join_forms();
    $out = [];
    $n = count($chars);
    for ($i = 0; $i < $n; $i++) {
        $ch = $chars[$i];
        if (!isset($forms[$ch])) { $out[] = $ch; continue; }
        $prev = $i > 0 ? $chars[$i-1] : '';
        $next = $i + 1 < $n ? $chars[$i+1] : '';
        $joinPrev = aw_can_join_prev($ch) && aw_can_join_next($prev);
        $joinNext = aw_can_join_next($ch) && aw_can_join_prev($next);
        if ($joinPrev && $joinNext && $forms[$ch][3] !== null) $out[] = $forms[$ch][3];
        elseif ($joinPrev) $out[] = $forms[$ch][1];
        elseif ($joinNext && $forms[$ch][2] !== null) $out[] = $forms[$ch][2];
        else $out[] = $forms[$ch][0];
    }
    return implode('', array_reverse($out));
}
function aw_text_width($size, $font, $text) {
    if (!$font || !function_exists('imagettfbbox')) return strlen((string)$text) * 10;
    $b = imagettfbbox($size, 0, $font, (string)$text);
    return abs($b[2] - $b[0]);
}
function aw_draw_text($im, $x, $y, $text, $size, $color, $font, $align = 'center', $rtl = false) {
    if (!$font || !function_exists('imagettftext')) return;
    $text = $rtl ? aw_shape_rtl((string)$text) : (string)$text;
    $w = aw_text_width($size, $font, $text);
    if ($align === 'center') $x -= $w / 2;
    elseif ($align === 'right') $x -= $w;
    imagettftext($im, $size, 0, (int)$x, (int)$y, $color, $font, $text);
}
function aw_fit_size($font, $text, $maxSize, $minSize, $maxWidth, $rtl = false) {
    $size = $maxSize;
    while ($size > $minSize) {
        $t = $rtl ? aw_shape_rtl((string)$text) : (string)$text;
        if (aw_text_width($size, $font, $t) <= $maxWidth * 0.9) break;
        $size -= 4;
    }
    return max($minSize, $size);
}
function aw_draw_box_text($im, $box, $text, $maxSize, $minSize, $color, $font, $rtl = false) {
    $size = aw_fit_size($font, $text, $maxSize, $minSize, $box['w'], $rtl);
    $x = $box['x'] + $box['w'] / 2;
    $y = $box['y'] + $box['h'] / 2 + $size * 0.38;
    aw_draw_text($im, $x, $y, $text, $size, $color, $font, 'center', $rtl);
}
function aw_draw_exact_text($im, $cx, $cy, $text, $size, $color, $font, $rtl = false, $maxWidth = 0) {
    if (!$font || !function_exists('imagettftext')) return;
    $text = $rtl ? aw_shape_rtl((string)$text) : (string)$text;
    $useSize = $size;
    if ($maxWidth > 0) {
        while ($useSize > 14 && aw_text_width($useSize, $font, $text) > $maxWidth) $useSize -= 2;
    }
    $bbox = imagettfbbox($useSize, 0, $font, $text);
    $minX = min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
    $maxX = max($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
    $minY = min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
    $maxY = max($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
    $x = $cx - ($minX + $maxX) / 2;
    $y = $cy - ($minY + $maxY) / 2;
    imagettftext($im, $useSize, 0, (int)round($x), (int)round($y), $color, $font, $text);
}
function aw_draw_status($im) {
    $font = aw_font(false); $bold = aw_font(true) ?: $font;
    $white = aw_hex($im, '#ffffff');
    $muted = aw_hex($im, '#94a3b8');
    $slate = aw_hex($im, '#cbd5e1');
    $cyan = aw_hex($im, '#00eaff');
    $mag = aw_hex($im, '#d947ff');
    $status = aw_status_fa(aw_param('status', 'وضعیت نامشخص'));
    $date = aw_digits_fa(aw_param('date', date('Y/m/d')));
    $poem = [aw_param('poem1', ''), aw_param('poem2', '')];
    if (!$poem[0] || !$poem[1]) $poem = function_exists('aw_random_poem') ? aw_random_poem() : ['توانا بود هر که دانا بود', 'ز دانش دل پیر برنا بود'];
    $isOdd = strpos($status, 'فرد') !== false;
    $statusColor = $isOdd ? $cyan : (strpos($status, 'زوج') !== false ? $mag : $white);

    $subtitle = $isOdd ? 'کلاس‌های تک‌جلسه‌ای و عملی گروه‌های فرد دایر است' : (strpos($status, 'زوج') !== false ? 'کلاس‌های تک‌جلسه‌ای و عملی گروه‌های زوج دایر است' : 'تقویم رسمی دانشگاه آزاد اسلامی');

    aw_draw_exact_text($im, 540, 236, 'دانشگاه آزاد اسلامی', 34, $muted, $bold, true);
    aw_draw_exact_text($im, 540, 288, 'تقویم هوشمند هفته‌های آموزشی | آزادویک', 40, $white, $bold, true);

    aw_draw_exact_text($im, 540, 390, 'وضعیت برگزاری کلاس‌ها در این هفته', 30, $muted, $font, true);
    aw_draw_exact_text($im, 540, 520, $status, 130, $statusColor, $bold, true, 800);
    aw_draw_exact_text($im, 540, 650, 'هفته جاری در نیم‌سال تحصیلی', 34, $white, $font, true);
    aw_draw_exact_text($im, 540, 715, $subtitle, 28, $statusColor, $font, true, 820);

    aw_draw_exact_text($im, 540, 875, $date, 52, $white, $bold, false);
    aw_draw_exact_text($im, 540, 940, 'پیشرفت نیم‌سال تحصیلی دانشگاه آزاد اسلامی', 28, $muted, $font, true);

    aw_draw_exact_text($im, 540, 1150, '«', 52, $statusColor, $bold, false);
    aw_draw_exact_text($im, 540, 1235, $poem[0], 44, $white, $bold, true, 800);
    aw_draw_exact_text($im, 540, 1325, $poem[1], 44, $white, $bold, true, 800);
    aw_draw_exact_text($im, 540, 1405, '»', 52, $statusColor, $bold, false);
    aw_draw_exact_text($im, 540, 1470, 'گاه‌شمار ادب و دانش • دانشگاه آزاد اسلامی', 24, $muted, $font, true);

    aw_draw_exact_text($im, 540, 1615, '@AzadWeekBot', 54, $cyan, $bold, false);
    aw_draw_exact_text($im, 540, 1695, 'دسترسی سریع به برنامه و تقویم هوشمند دانشگاه', 30, $slate, $font, true, 820);
}
function aw_draw_wrapped($im) {
    $font = aw_font(false); $bold = aw_font(true) ?: $font;
    $white = aw_hex($im, '#ffffff');
    $muted = aw_hex($im, '#94a3b8');
    $slate = aw_hex($im, '#cbd5e1');
    $cyan = aw_hex($im, '#00eaff');
    $mag = aw_hex($im, '#d947ff');
    $mode = strtolower(aw_param('mode', 'personal')) === 'term' ? 'term' : 'personal';
    $title = aw_param('title', $mode === 'term' ? 'جمع‌بندی ترم' : 'دانشجوی همراه');
    $badgeTitle = $mode === 'term' ? 'وضعیت کلی و تلمتری ترم آموزشی جاری' : 'کارنامه فعالیت و پایش برنامه در این دستگاه';
    $defaults = $mode === 'term'
        ? [
            ['کل هفته‌ها', '17', $cyan], ['هفته فعلی', '4', $mag], ['هفته‌های فرد', '9', $mag], ['هفته‌های زوج', '8', $cyan]
          ]
        : [
            ['بازدیدها', '0', $cyan], ['بررسی تاریخ', '0', $mag], ['اشتراک‌گذاری', '0', $mag], ['تکان‌های دستگاه', '0', $cyan]
          ];
    $items = [];
    for ($i = 1; $i <= 4; $i++) {
        $d = $defaults[$i - 1];
        $items[] = [aw_param('label' . $i, $d[0]), max(0, (int)aw_digits_en(aw_param('value' . $i, $d[1]))), $d[2]];
    }
    $line1 = aw_param('line1', $mode === 'term' ? 'شروع ترم: ۲۵ شهریور • پایان: بهمن' : 'بخش پرکاربرد: وضعیت امروز');
    $line2 = aw_param('line2', $mode === 'term' ? 'تقویم رسمی دانشگاه آزاد اسلامی' : 'ثبت و پایش منظم کلاس‌های زوج و فرد دانشگاه');
    $poem = [aw_param('poem1', ''), aw_param('poem2', '')];
    if (!$poem[0] || !$poem[1]) $poem = function_exists('aw_random_poem') ? aw_random_poem() : ['توانا بود هر که دانا بود', 'ز دانش دل پیر برنا بود'];

    $heroColor = $mode === 'personal' ? $mag : $cyan;
    $subColor = $mode === 'personal' ? aw_hex($im, '#f472b6') : $cyan;

    aw_draw_exact_text($im, 540, 222, 'دانشگاه آزاد اسلامی', 32, $muted, $bold, true);
    aw_draw_exact_text($im, 540, 268, 'تقویم هوشمند هفته‌های آموزشی | آزادویک', 38, $white, $bold, true);

    aw_draw_exact_text($im, 540, 368, $badgeTitle, 28, $muted, $font, true, 800);
    aw_draw_exact_text($im, 540, 460, $title, 95, $heroColor, $bold, true, 800);
    aw_draw_exact_text($im, 540, 555, $line1, 32, $white, $bold, true, 820);
    aw_draw_exact_text($im, 540, 612, $line2, 26, $subColor, $font, true, 820);

    $boxes = [
        [312.5, 725, 795, $items[0]],
        [767.5, 725, 795, $items[1]],
        [312.5, 920, 990, $items[2]],
        [767.5, 920, 990, $items[3]]
    ];
    foreach ($boxes as $b) {
        $cx = $b[0]; $ly = $b[1]; $vy = $b[2]; $it = $b[3];
        aw_draw_exact_text($im, $cx, $ly, $it[0], 28, $muted, $font, true, 380);
        aw_draw_exact_text($im, $cx, $vy, aw_digits_fa((string)$it[1]), 66, $it[2], $bold, false);
    }

    aw_draw_exact_text($im, 540, 1130, '«', 52, $heroColor, $bold, false);
    aw_draw_exact_text($im, 540, 1215, $poem[0], 44, $white, $bold, true, 800);
    aw_draw_exact_text($im, 540, 1300, $poem[1], 44, $white, $bold, true, 800);
    aw_draw_exact_text($im, 540, 1375, '»', 52, $heroColor, $bold, false);
    aw_draw_exact_text($im, 540, 1455, 'گاه‌شمار ادب و دانش • دانشگاه آزاد اسلامی', 24, $muted, $font, true);

    aw_draw_exact_text($im, 540, 1600, '@AzadWeekBot', 54, $cyan, $bold, false);
    aw_draw_exact_text($im, 540, 1675, 'دسترسی سریع به برنامه و تقویم هوشمند دانشگاه', 30, $slate, $font, true, 820);
}
function aw_blank_template($type) {
    $file = __DIR__ . '/assets/card_templates/' . ($type === 'wrapped' ? 'wrapped_template.webp' : 'status_template.webp');
    if (is_file($file)) {
        header_remove('Content-Type');
        header('Content-Type: image/webp');
        readfile($file);
        exit;
    }
    http_response_code(404); exit;
}

$type = strtolower(aw_param('type', 'status')) === 'wrapped' ? 'wrapped' : 'status';
$cache_key = md5(($_SERVER['QUERY_STRING'] ?? '') . $type);
$card_cache_dir = __DIR__ . '/assets/generated_cards';
if (!is_dir($card_cache_dir)) @mkdir($card_cache_dir, 0755, true);
$card_cache_file = $card_cache_dir . '/card_' . $cache_key . '.' . ($fmt === 'png' ? 'png' : 'webp');

if (is_file($card_cache_file) && (time() - filemtime($card_cache_file) < 86400)) {
    readfile($card_cache_file);
    exit;
}

$template = __DIR__ . '/assets/card_templates/' . ($type === 'wrapped' ? 'wrapped_template.webp' : 'status_template.webp');
if (!function_exists('imagecreatefromwebp') || !function_exists('imagewebp') || !is_file($template)) aw_blank_template($type);
$im = @imagecreatefromwebp($template);
if (!$im) aw_blank_template($type);
$type === 'wrapped' ? aw_draw_wrapped($im) : aw_draw_status($im);
if ($fmt === 'png' && function_exists('imagepng')) {
    @imagepng($im, $card_cache_file);
    imagepng($im);
} else {
    @imagewebp($im, $card_cache_file, 92);
    imagewebp($im, null, 92);
}
imagedestroy($im);
exit;
