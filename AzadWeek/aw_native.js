(function () {
  'use strict';

  const TG = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
  const APP = window.AzadWeekNative || {};
  const STATE = {
    view: 'home',
    home: null,
    lastResult: null,
    selectedDate: null,
    lastShakeAt: 0,
    preparedMessageId: null,
    securePrefs: {},
    devicePrefs: {},
    homeScreenStatus: null,
    motionStarted: false,
    mobileFullscreen: false,
    lastWrappedKind: 'personal'
  };
  const STATUS_POEMS = [
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
    ['تحصیل، نردبانِ آرامِ امید است', 'پایانِ جهل و آغازِ سپید است']
  ];

  const CARD_RUNTIME = {
    imagePromises: new Map(),
    fontPromise: null,
    blobCache: new Map()
  };

  const CONFIG = {
    botUsername: 'AzadWeekBot',
    apiUrl: 'connect.php',
    storageKeys: {
      cloudLast: 'aw_last_result',
      cloudDate: 'aw_last_date',
      cloudSettings: 'aw_settings',
      devicePrefs: 'aw_device_prefs',
      securePrefs: 'aw_secure_prefs',
      wrappedStats: 'aw_wrapped_stats'
    },
    buttonEmojiIds: Object.assign({
      share: '',
      wrapped: '',
      calendar: '',
      search: '',
      today: ''
    }, window.AZADWEEK_BOTTOM_BUTTON_EMOJI_IDS || {})
  };

  function isTelegram() { return !!TG; }
  function versionAtLeast(v) {
    try { return !!(TG && TG.isVersionAtLeast && TG.isVersionAtLeast(v)); }
    catch (_) { return false; }
  }
  function tgCall(fn, ...args) {
    try {
      if (!TG || typeof TG[fn] !== 'function') return undefined;
      return TG[fn](...args);
    } catch (err) {
      console.debug('[AzadWeekNative]', fn, err);
      return undefined;
    }
  }
  function haptic(type, value) {
    try {
      if (!TG || !TG.HapticFeedback) return;
      if (type === 'impact') TG.HapticFeedback.impactOccurred(value || 'light');
      if (type === 'notify') TG.HapticFeedback.notificationOccurred(value || 'success');
      if (type === 'select') TG.HapticFeedback.selectionChanged();
    } catch (_) {}
  }
  function alertNative(message) {
    if (TG && TG.showAlert) return TG.showAlert(message);
    window.alert(message);
  }
  function confirmNative(message, cb) {
    if (TG && TG.showConfirm) return TG.showConfirm(message, cb);
    cb(window.confirm(message));
  }
  function ensureToastStyle() {
    if (document.getElementById('azadToastStyle')) return;
    const style = document.createElement('style');
    style.id = 'azadToastStyle';
    style.textContent = `
      .aw-toast{position:fixed;left:50%;bottom:calc(96px + max(var(--azad-safe-bottom,0px),var(--azad-content-safe-bottom,0px)));transform:translate(-50%,16px) scale(0.95);z-index:11500;max-width:min(92vw,430px);padding:9px 18px;border-radius:999px;background:rgba(11,18,34,0.96);border:1px solid rgba(255,255,255,0.13);box-shadow:0 14px 38px rgba(0,0,0,0.55),0 0 18px rgba(0,229,255,0.14);backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px);color:#f8fafc;font-family:'Vazirmatn',-apple-system,sans-serif;font-size:0.83rem;font-weight:700;line-height:1.45;direction:rtl;text-align:center;opacity:0;pointer-events:none;display:inline-flex;align-items:center;justify-content:center;gap:9px;white-space:nowrap;transition:all 0.26s cubic-bezier(0.34,1.56,0.64,1)}
      .aw-toast.show{opacity:1;transform:translate(-50%,0) scale(1)}
      .aw-toast-spinner{width:16px;height:16px;border:2px solid rgba(0,229,255,0.22);border-top-color:#00e5ff;border-radius:50%;animation:awSpin 0.75s linear infinite;flex-shrink:0}
      .aw-toast-svg{width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}
      .aw-toast-svg svg{width:100%;height:100%;display:block}
      @keyframes awSpin{to{transform:rotate(360deg)}}
      .aw-action-list button[disabled],.aw-action-grid button[disabled]{opacity:.65;pointer-events:none;filter:saturate(.75)}
    `;
    document.head.appendChild(style);
  }
  function showToast(message, duration, opts) {
    ensureToastStyle();
    let toast = document.getElementById('awToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'awToast';
      toast.className = 'aw-toast';
      document.body.appendChild(toast);
    }
    opts = opts || {};
    const text = String(message || 'در حال انجام...');
    const isLoading = text.includes('در حال') || text.includes('درحال') || opts.loading;
    const isSuccess = text.includes('انجام شد') || text.includes('ذخیره شد') || text.includes('فعال شد') || opts.type === 'success';
    const isWarn = text.includes('لغو شد') || text.includes('خطا') || text.includes('ناموفق') || opts.type === 'warning';

    let iconHtml = '';
    if (isLoading) {
      iconHtml = '<span class="aw-toast-spinner"></span>';
    } else if (isSuccess) {
      iconHtml = '<span class="aw-toast-svg"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#10b981" fill-opacity="0.22" stroke="#10b981" stroke-width="2"/><path d="M7.5 12.2l3 3 6-6" stroke="#10b981" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
    } else if (isWarn) {
      iconHtml = '<span class="aw-toast-svg"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3l9 16H3L12 3z" fill="#f59e0b" fill-opacity="0.2" stroke="#f59e0b" stroke-width="2" stroke-linejoin="round"/><path d="M12 9v4.5M12 16.5v.5" stroke="#f59e0b" stroke-width="2.2" stroke-linecap="round"/></svg></span>';
    } else {
      iconHtml = '<span class="aw-toast-svg"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#00e5ff" fill-opacity="0.18" stroke="#00e5ff" stroke-width="2"/><path d="M12 8v.5M12 11.5v4.5" stroke="#00e5ff" stroke-width="2.2" stroke-linecap="round"/></svg></span>';
    }
    toast.innerHTML = iconHtml + `<span>${xmlEscape(text)}</span>`;
    clearTimeout(showToast._timer);
    requestAnimationFrame(() => toast.classList.add('show'));
    showToast._timer = setTimeout(() => toast.classList.remove('show'), duration || (isLoading ? 2400 : 1900));
  }
  function setActionBusy(btn, busy, text) {
    if (!btn) return;
    if (busy) {
      if (!btn.dataset.oldHtml) btn.dataset.oldHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = `<span>${xmlEscape(text || 'در حال انجام...')}</span><ion-icon name="sync-outline"></ion-icon>`;
    } else {
      btn.disabled = false;
      if (btn.dataset.oldHtml) btn.innerHTML = btn.dataset.oldHtml;
      delete btn.dataset.oldHtml;
    }
  }
  function showPopup(params, cb) {
    if (TG && TG.showPopup) return TG.showPopup(params, cb);
    const labels = (params.buttons || []).map(b => b.text).join(' / ');
    const ok = window.confirm((params.title ? params.title + '\n' : '') + params.message + (labels ? '\n\n' + labels : ''));
    cb && cb(ok ? ((params.buttons || [])[0] || {}).id : null);
  }
  function isMobileClient() {
    const platform = String((TG && TG.platform) || '').toLowerCase();
    if (['tdesktop', 'macos', 'windows', 'linux', 'web', 'weba', 'webk', 'unigram'].includes(platform)) return false;
    const ua = String(navigator.userAgent || '');
    if (/ipad|tablet|playbook|silk/i.test(ua) || (/android/i.test(ua) && !/mobile/i.test(ua))) return false;
    const sw = (window.screen && window.screen.width) ? window.screen.width : window.innerWidth;
    const sh = (window.screen && window.screen.height) ? window.screen.height : window.innerHeight;
    if (Math.min(sw || 390, sh || 844) >= 600) return false;
    if (['ios', 'android', 'android_x', 'mobile'].includes(platform)) return true;
    return /iphone|ipod|android.*mobile/i.test(ua);
  }
  function absoluteUrl(path) {
    try { return new URL(path, window.location.href).toString(); }
    catch (_) { return path; }
  }
  function normalizeJalaliDate(date) {
    const fa = '۰۱۲۳۴۵۶۷۸۹';
    const ar = '٠١٢٣٤٥٦٧٨٩';
    let value = String(date || '').trim();
    value = value.replace(/[۰-۹]/g, d => String(fa.indexOf(d)));
    value = value.replace(/[٠-٩]/g, d => String(ar.indexOf(d)));
    value = value.replace(/[.\-\s]+/g, '/').replace(/\/+/g, '/');
    const m = value.match(/^(1[34]\d{2})\/(\d{1,2})\/(\d{1,2})$/);
    if (!m) return '';
    const y = Number(m[1]), mo = Number(m[2]), d = Number(m[3]);
    if (mo < 1 || mo > 12 || d < 1 || d > 31) return '';
    return `${y}/${String(mo).padStart(2, '0')}/${String(d).padStart(2, '0')}`;
  }
  function getCurrentDate() {
    const fromUi = window.AzadWeekGetSelectedDate ? window.AzadWeekGetSelectedDate() : ((document.getElementById('dateInput') || {}).value || '');
    const domResultDate = ((document.getElementById('resDate') || {}).textContent || '').trim();
    const domHeaderDate = ((document.getElementById('headerDate') || {}).textContent || '').trim();
    return normalizeJalaliDate(fromUi) || normalizeJalaliDate(STATE.selectedDate) || normalizeJalaliDate(STATE.lastResult && STATE.lastResult.date) || normalizeJalaliDate(domResultDate) || normalizeJalaliDate(domHeaderDate) || normalizeJalaliDate(STATE.home && STATE.home.today) || '';
  }
  function normalizeWeekStatusText(value) {
    const s = String(value || '').trim();
    if (!s || /^(---|\.\.\.)$/.test(s) || /بررسی وضعیت|سیستم آماده|loading|invalid/i.test(s)) return '';
    if (s.includes('فرد') || /odd/i.test(s)) return 'هفته فرد';
    if (s.includes('زوج') || /even/i.test(s)) return 'هفته زوج';
    if (s.includes('خارج') || /out/i.test(s)) return 'خارج از بازه ترم';
    if (/وضعیت هفته/.test(s)) return '';
    return s;
  }
  function getStatusText() {
    const domResult = ((document.getElementById('resStatus') || {}).textContent || '').trim();
    const domTitle = ((document.getElementById('statusTitle') || {}).textContent || '').trim();
    const domDesc = ((document.getElementById('statusDesc') || {}).textContent || '').trim();
    const candidates = [
      STATE.lastResult && STATE.lastResult.week_status,
      STATE.home && STATE.home.today_status,
      domResult,
      domTitle,
      domDesc
    ];
    for (const c of candidates) {
      const normalized = normalizeWeekStatusText(c);
      if (normalized) return normalized;
    }
    return '';
  }
  function getTitleLine() {
    const status = getStatusText();
    const date = getCurrentDate() || (STATE.home && STATE.home.today) || '';
    return date ? `${date} — ${status}` : status;
  }
  function setCssVar(name, value) {
    document.documentElement.style.setProperty(name, `${Number(value || 0)}px`);
  }
  function applySafeArea() {
    const sa = TG && TG.safeAreaInset ? TG.safeAreaInset : {};
    const csa = TG && TG.contentSafeAreaInset ? TG.contentSafeAreaInset : {};
    setCssVar('--azad-safe-top', sa.top);
    setCssVar('--azad-safe-bottom', sa.bottom);
    setCssVar('--azad-safe-left', sa.left);
    setCssVar('--azad-safe-right', sa.right);
    setCssVar('--azad-content-safe-top', csa.top);
    setCssVar('--azad-content-safe-bottom', csa.bottom);
    setCssVar('--azad-content-safe-left', csa.left);
    setCssVar('--azad-content-safe-right', csa.right);
  }
  function applyTelegramTheme() {
    if (!TG) return;
    try {
      const bg = '#090D16';
      if (TG.setHeaderColor) TG.setHeaderColor(bg);
      if (TG.setBackgroundColor) TG.setBackgroundColor(bg);
      if (TG.setBottomBarColor) TG.setBottomBarColor(bg);
    } catch (_) {}
  }
  function postFullscreenBridge(enable) {
    if (!TG) return;
    try {
      if (enable) {
        tgCall('expand');
        if (typeof TG.requestFullscreen === 'function') TG.requestFullscreen();
        if (window.Telegram && window.Telegram.WebView && typeof window.Telegram.WebView.postEvent === 'function') {
          window.Telegram.WebView.postEvent('web_app_expand');
          window.Telegram.WebView.postEvent('web_app_request_fullscreen');
        }
      } else {
        if (TG.isFullscreen && typeof TG.exitFullscreen === 'function') TG.exitFullscreen();
        if (window.Telegram && window.Telegram.WebView && typeof window.Telegram.WebView.postEvent === 'function') {
          window.Telegram.WebView.postEvent('web_app_exit_fullscreen');
        }
      }
    } catch (_) {}
  }
  function enterMobileFullscreen() {
    if (!TG) return;
    if (!isMobileClient()) {
      STATE.mobileFullscreen = false;
      document.body.classList.remove('tg-mobile-fullscreen');
      postFullscreenBridge(false);
      return;
    }
    STATE.mobileFullscreen = true;
    document.body.classList.add('tg-mobile-fullscreen');
    postFullscreenBridge(true);
    [60, 180, 360, 650, 1100].forEach(function (ms) {
      setTimeout(function () {
        if (STATE.mobileFullscreen && !TG.isFullscreen) postFullscreenBridge(true);
      }, ms);
    });
    if (versionAtLeast('7.7')) tgCall('disableVerticalSwipes');
  }
  function saveLocal(key, value) {
    try { localStorage.setItem(key, JSON.stringify(value)); } catch (_) {}
  }
  function loadLocal(key, fallback) {
    try {
      const raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : fallback;
    } catch (_) { return fallback; }
  }
  function storageSet(area, key, value) {
    const str = typeof value === 'string' ? value : JSON.stringify(value);
    saveLocal(key, value);
    try {
      if (area === 'cloud' && TG && TG.CloudStorage) TG.CloudStorage.setItem(key, str, function () {});
      if (area === 'device' && TG && TG.DeviceStorage) TG.DeviceStorage.setItem(key, str, function () {});
      if (area === 'secure' && TG && TG.SecureStorage) TG.SecureStorage.setItem(key, str, function () {});
    } catch (_) {}
  }
  function storageGet(area, key, cb) {
    const fallback = loadLocal(key, null);
    try {
      const store = area === 'cloud' ? TG && TG.CloudStorage : area === 'device' ? TG && TG.DeviceStorage : TG && TG.SecureStorage;
      if (store && store.getItem) {
        store.getItem(key, function (err, value) {
          if (!err && value) {
            try { cb(JSON.parse(value)); } catch (_) { cb(value); }
          } else cb(fallback);
        });
        return;
      }
    } catch (_) {}
    cb(fallback);
  }
  function loadPreferences() {
    STATE.securePrefs = loadLocal(CONFIG.storageKeys.securePrefs, {}) || {};
    STATE.devicePrefs = loadLocal(CONFIG.storageKeys.devicePrefs, {}) || {};
    storageGet('secure', CONFIG.storageKeys.securePrefs, function (prefs) {
      if (prefs && typeof prefs === 'object') STATE.securePrefs = prefs;
    });
    storageGet('device', CONFIG.storageKeys.devicePrefs, function (prefs) {
      if (prefs && typeof prefs === 'object') STATE.devicePrefs = prefs;
    });
  }
  function savePreferences() {
    storageSet('secure', CONFIG.storageKeys.securePrefs, STATE.securePrefs || {});
    storageSet('device', CONFIG.storageKeys.devicePrefs, STATE.devicePrefs || {});
    storageSet('cloud', CONFIG.storageKeys.cloudSettings, {
      reminder: !!STATE.securePrefs.reminder,
      shake: STATE.devicePrefs.shake !== false,
      updatedAt: Date.now()
    });
  }

  function getWrappedStats() {
    const stats = loadLocal(CONFIG.storageKeys.wrappedStats, {}) || {};
    if (!stats.firstOpen) stats.firstOpen = Date.now();
    stats.opens = Number(stats.opens || 0);
    stats.checks = Number(stats.checks || 0);
    stats.shares = Number(stats.shares || 0);
    stats.downloads = Number(stats.downloads || 0);
    stats.shakes = Number(stats.shakes || 0);
    stats.odd = Number(stats.odd || 0);
    stats.even = Number(stats.even || 0);
    stats.outside = Number(stats.outside || 0);
    stats.views = Object.assign({ home: 0, search: 0, list: 0 }, stats.views || {});
    return stats;
  }
  function saveWrappedStats(stats) {
    stats.lastUpdate = Date.now();
    saveLocal(CONFIG.storageKeys.wrappedStats, stats);
    storageSet('device', CONFIG.storageKeys.wrappedStats, stats);
  }
  function classifyStatus(status) {
    status = String(status || '');
    if (status.includes('فرد')) return 'odd';
    if (status.includes('زوج')) return 'even';
    return 'outside';
  }
  function recordWrappedEvent(type, payload) {
    const stats = getWrappedStats();
    if (type === 'open') {
      if (!STATE.__openRecorded) { stats.opens += 1; STATE.__openRecorded = true; }
      stats.lastOpen = Date.now();
    }
    if (type === 'view') {
      const view = payload && payload.view ? payload.view : STATE.view;
      stats.views[view] = Number(stats.views[view] || 0) + 1;
    }
    if (type === 'check') {
      stats.checks += 1;
      const bucket = classifyStatus(payload && (payload.week_status || payload.today_status));
      stats[bucket] = Number(stats[bucket] || 0) + 1;
      stats.lastStatus = payload && (payload.week_status || payload.today_status) || stats.lastStatus || '';
      stats.lastDate = payload && (payload.date || payload.today) || getCurrentDate() || stats.lastDate || '';
    }
    if (type === 'share') stats.shares += 1;
    if (type === 'download') stats.downloads += 1;
    if (type === 'shake') stats.shakes += 1;
    saveWrappedStats(stats);
    return stats;
  }
  function topWrappedLabel(stats) {
    const pairs = [
      ['Home', Number(stats.views.home || 0)],
      ['Time Machine', Number(stats.views.search || 0)],
      ['Term Map', Number(stats.views.list || 0)]
    ].sort((a, b) => b[1] - a[1]);
    return pairs[0][1] > 0 ? pairs[0][0] : 'Home';
  }
  const DEFAULT_FALLBACK_WEEKS = [
    { id: 1, start: '1405/06/22', end: '1405/06/28', status: 'هفته فرد' },
    { id: 2, start: '1405/06/29', end: '1405/07/04', status: 'هفته زوج' },
    { id: 3, start: '1405/07/05', end: '1405/07/11', status: 'هفته فرد' },
    { id: 4, start: '1405/07/12', end: '1405/07/18', status: 'هفته زوج' },
    { id: 5, start: '1405/07/19', end: '1405/07/25', status: 'هفته فرد' },
    { id: 6, start: '1405/07/26', end: '1405/08/02', status: 'هفته زوج' },
    { id: 7, start: '1405/08/03', end: '1405/08/09', status: 'هفته فرد' },
    { id: 8, start: '1405/08/10', end: '1405/08/16', status: 'هفته زوج' },
    { id: 9, start: '1405/08/17', end: '1405/08/23', status: 'هفته فرد' },
    { id: 10, start: '1405/08/24', end: '1405/08/30', status: 'هفته زوج' },
    { id: 11, start: '1405/09/01', end: '1405/09/07', status: 'هفته فرد' },
    { id: 12, start: '1405/09/08', end: '1405/09/14', status: 'هفته زوج' },
    { id: 13, start: '1405/09/15', end: '1405/09/21', status: 'هفته فرد' },
    { id: 14, start: '1405/09/22', end: '1405/09/28', status: 'هفته زوج' },
    { id: 15, start: '1405/09/29', end: '1405/10/05', status: 'هفته فرد' },
    { id: 16, start: '1405/10/06', end: '1405/10/12', status: 'هفته زوج' },
    { id: 17, start: '1405/10/13', end: '1405/10/19', status: 'هفته فرد' }
  ];

  function getTermWeeks() {
    if (STATE.home && Array.isArray(STATE.home.weeks) && STATE.home.weeks.length) return STATE.home.weeks;
    if (typeof window.AzadWeekGetLocalWeeks === 'function') {
      const lw = window.AzadWeekGetLocalWeeks();
      if (Array.isArray(lw) && lw.length) return lw;
    }
    return DEFAULT_FALLBACK_WEEKS;
  }
  function getWrappedPayload(kind) {
    kind = kind === 'term' ? 'term' : 'personal';
    if (kind === 'term') {
      const weeks = getTermWeeks();
      const total = Number((STATE.home && STATE.home.term && STATE.home.term.total_weeks) || weeks.length || 17);
      const current = (STATE.home && STATE.home.current_week) || weeks.find(w => w && w.is_current) || weeks[3] || null;
      const oddCount = weeks.filter(w => String(w.status || '').includes('فرد')).length || 9;
      const evenCount = weeks.filter(w => String(w.status || '').includes('زوج')).length || 8;
      const currentId = current ? Number(current.id || 4) : 4;
      const currentStatus = current ? normalizeWeekStatusText(current.status || '') : 'هفته زوج';
      const termStart = (STATE.home && STATE.home.term && STATE.home.term.start) || '۲۲ شهریور ۱۴۰۵';
      const progress = Number((STATE.home && STATE.home.term && STATE.home.term.progress_percent) || 23);

      return {
        kind: 'term',
        title: 'خلاصه وضعیت ترم',
        subtitle: 'پیشرفت ترم و تقویم هفته‌های آموزشی',
        progress: progress,
        items: [
          { label: 'کل هفته‌های ترم', value: total, color: '#00e5ff' },
          { label: 'هفته فعلی', value: currentId, color: '#d946ef' },
          { label: 'هفته‌های فرد', value: oddCount, color: '#00e5ff' },
          { label: 'هفته‌های زوج', value: evenCount, color: '#d946ef' }
        ],
        line1: 'شروع ترم: شنبه ' + termStart,
        line2: 'وضعیت این هفته: «' + currentStatus + '» (هفته ' + faNumber(currentId) + ' از ' + faNumber(total) + ')'
      };
    }
    const stats = getWrappedStats();
    const opens = Number(stats.opens || 0);
    const checks = Number(stats.checks || 0);
    const shares = Number(stats.shares || 0);
    const shakes = Number(stats.shakes || 0);
    const dominant = (stats.odd || 0) > (stats.even || 0) ? 'هفته فرد' : 'هفته زوج';

    return {
      kind: 'personal',
      title: 'آمار و فعالیت من',
      subtitle: 'خلاصه استفاده از تقویم هوشمند آزادویک',
      items: [
        { label: 'تعداد بازدیدها', value: opens, color: '#00e5ff' },
        { label: 'بررسی تاریخ‌ها', value: checks, color: '#d946ef' },
        { label: 'اشتراک‌گذاری‌ها', value: shares, color: '#00e5ff' },
        { label: 'بروزرسانی‌ها', value: shakes, color: '#d946ef' }
      ],
      line1: 'بخش پرکاربرد: ' + (wrappedFavoritePersian(topWrappedLabel(stats)) || 'وضعیت امروز'),
      line2: 'بیشترین وضعیت مشاهده‌شده: «' + dominant + '»'
    };
  }
  function getWrappedShareText(kind) {
    const p = getWrappedPayload(kind);
    if (p.kind === 'term') {
      return `🎓 گزارش وضعیت ترم آموزشی دانشگاه آزاد | آزادویک\n\n` +
        `📊 وضعیت کل ترم:\n` +
        p.items.map(i => `▫️ ${i.label}: ${faNumber(i.value)}`).join('\n') + `\n\n` +
        `📌 ${p.line1}\n` +
        `⚡ ${p.line2}\n\n` +
        `🎯 بررسی برنامه کلاس‌ها و تقویم هوشمند:\n` +
        `@${CONFIG.botUsername}`;
    }
    return `✨ گزارش فعالیت من در آزادویک\n\n` +
      `📱 خلاصه استفاده و پیگیری هفته‌ها:\n` +
      p.items.map(i => `▫️ ${i.label}: ${faNumber(i.value)}`).join('\n') + `\n\n` +
      `🔹 ${p.line1}\n` +
      `🔸 ${p.line2}\n\n` +
      `📲 ورود به سامانه هوشمند آزادویک:\n` +
      `@${CONFIG.botUsername}`;
  }
  async function sharePlainText(text, title) {
    const payload = String(text || '').trim();
    if (!payload) return;
    showToast('در حال باز کردن صفحه ارسال...');
    recordWrappedEvent('share');
    if (navigator.share) {
      try { await navigator.share({ title: title || 'آزادویک', text: payload }); haptic('notify', 'success'); return; } catch (_) {}
    }
    if (TG && TG.openTelegramLink) {
      try {
        const shareUrl = 'https://t.me/share/url?url=' + encodeURIComponent('https://t.me/' + CONFIG.botUsername) + '&text=' + encodeURIComponent(payload);
        TG.openTelegramLink(shareUrl);
        return;
      } catch (_) {}
    }
    if (TG && TG.switchInlineQuery) {
      try { TG.switchInlineQuery(payload, ['users', 'groups', 'channels']); return; } catch (_) {}
    }
    copyToClipboard(payload);
  }
  function api(action, data) {
    const fd = new FormData();
    fd.append('action', action);
    if (TG && TG.initData) fd.append('initData', TG.initData);
    if (TG && TG.platform) fd.append('tgPlatform', TG.platform);
    if (data && typeof data === 'object') {
      Object.keys(data).forEach(k => fd.append(k, data[k] == null ? '' : data[k]));
    }
    return fetch(CONFIG.apiUrl, { method: 'POST', body: fd }).then(r => {
      const type = r.headers.get('content-type') || '';
      if (type.includes('application/json')) return r.json();
      return r.text();
    });
  }
  function onHomeData(data) {
    if (!data || data.status !== 'success') return;
    STATE.home = data;
    const stats = getWrappedStats();
    stats.lastStatus = data.today_status || stats.lastStatus || '';
    stats.lastDate = data.today || stats.lastDate || '';
    saveWrappedStats(stats);
    storageSet('cloud', CONFIG.storageKeys.cloudLast, data);
    storageSet('device', CONFIG.storageKeys.devicePrefs, Object.assign({}, STATE.devicePrefs, { lastOpen: Date.now() }));
    updateNativeButtons();
  }
  function onCheckResult(data) {
    if (!data || data.status !== 'success') return;
    STATE.lastResult = data;
    STATE.selectedDate = data.date;
    recordWrappedEvent('check', data);
    storageSet('cloud', CONFIG.storageKeys.cloudDate, data.date);
    storageSet('cloud', CONFIG.storageKeys.cloudLast, data);
    updateNativeButtons();
  }
  function setView(view) {
    STATE.view = view || 'home';
    recordWrappedEvent('view', { view: STATE.view });
    syncSheetMode();
    updateNativeButtons();
    updateBackButton();
  }
  function updateBackButton() {
    if (!TG || !TG.BackButton) return;
    try {
      const modalOpen = !!document.querySelector('.bottom-sheet-overlay.show');
      if (STATE.view !== 'home' || modalOpen) TG.BackButton.show();
      else TG.BackButton.hide();
    } catch (_) {}
  }
  function goBack() {
    const nativeSheet = document.getElementById('awNativeSheet');
    if (nativeSheet && nativeSheet.classList.contains('show')) { closeNativeSheet(); return; }
    const previewSheet = document.querySelector('.aw-preview-sheet.show');
    if (previewSheet) { previewSheet.click(); return; }
    const modal = document.querySelector('#calendar-modal.bottom-sheet-overlay.show');
    if (modal && window.closeCalendar) {
      window.closeCalendar();
      updateBackButton();
      return;
    }
    const anyModal = document.querySelector('.bottom-sheet-overlay.show');
    if (anyModal) {
      anyModal.click();
      updateBackButton();
      return;
    }
    if (STATE.view !== 'home' && window.switchView) {
      window.switchView('home', 0);
      updateBackButton();
      return;
    }
    if (TG && typeof TG.close === 'function') TG.close();
    else tgCall('close');
  }
  function bindBottomButton(button, handler) {
    if (!button || button.__azadBound) return;
    button.__azadBound = true;
    if (button.onClick) button.onClick(handler);
  }
  function setBottomButton(button, params) {
    if (!button) return;
    try {
      if (button.hide) button.hide();
    } catch (_) {}
  }
  function emojiIcon(name) {
    return CONFIG.buttonEmojiIds && CONFIG.buttonEmojiIds[name] ? String(CONFIG.buttonEmojiIds[name]) : '';
  }
  function updateNativeButtons() {
    hideTelegramBottomButtons();
  }
  function hideTelegramBottomButtons() {
    try { TG && TG.MainButton && TG.MainButton.hide && TG.MainButton.hide(); } catch (_) {}
    try { TG && TG.SecondaryButton && TG.SecondaryButton.hide && TG.SecondaryButton.hide(); } catch (_) {}
  }
  function setSheetMode(active) {
    try {
      const isOpen = !!active;
      document.body.classList.toggle('aw-sheet-open', isOpen);
      document.documentElement.classList.toggle('aw-sheet-open', isOpen);
      if (isOpen) {
        if (TG && TG.disableVerticalSwipes) tgCall('disableVerticalSwipes');
      } else {
        if (TG && TG.disableVerticalSwipes) tgCall('disableVerticalSwipes');
      }
    } catch (_) {}
    hideTelegramBottomButtons();
  }
  function hasOpenSheet() {
    return !!document.querySelector('.bottom-sheet-overlay.show, .bottom-sheet-overlay.active, #calendar-modal.show, .aw-native-sheet.show, #awNativeSheet.show');
  }
  function syncSheetMode() {
    setSheetMode(hasOpenSheet());
  }
  function mainButtonAction() {
    haptic('impact', 'medium');
    if (STATE.view === 'search') {
      if (TG && TG.hideKeyboard) tgCall('hideKeyboard');
      if (window.checkDate) return window.checkDate();
    }
    if (STATE.view === 'list') return downloadCalendar();
    return shareVisualCard();
  }
  function secondaryButtonAction() {
    haptic('impact', 'light');
    if (STATE.view === 'search') return checkToday();
    return showWrapped(STATE.view === 'list' ? 'term' : 'personal');
  }
  function pickRandomStatusPoem(forceNew) {
    if (!forceNew && STATE.lastStatusPoem && Array.isArray(STATE.lastStatusPoem)) return STATE.lastStatusPoem;
    const list = (typeof STATUS_POEMS !== 'undefined' && Array.isArray(STATUS_POEMS) && STATUS_POEMS.length)
      ? STATUS_POEMS
      : [['تحصیل، نردبانِ آرامِ امید است', 'پایانِ جهل و آغازِ سپید است']];
    const idx = Math.floor(Math.random() * list.length);
    const picked = list[idx] || list[0];
    STATE.lastStatusPoem = [picked[0], picked[1]];
    return STATE.lastStatusPoem;
  }
  function getCurrentStatusPoem() {
    return pickRandomStatusPoem(false);
  }
  function getShareText() {
    const status = statusLabelPersian(getStatusText(), 'هفته آموزشی');
    const date = getCurrentDate() || (STATE.home && STATE.home.today) || '';
    const day = (STATE.lastResult && STATE.lastResult.day) || (STATE.home && STATE.home.today_day) || '';
    const weekNum = (STATE.home && STATE.home.current_week && STATE.home.current_week.id) ? ` (هفته ${faNumber(STATE.home.current_week.id)} از ۱۷ ترم)` : '';
    const poem = getCurrentStatusPoem();
    const poemBlock = (poem && poem[0] && poem[1])
      ? `\n✨ «${poem[0]}\n   ${poem[1]}»\n`
      : (poem && poem[0] ? `\n✨ «${poem[0]}»\n` : '');

    return `📅 تقویم هفته‌های آموزشی دانشگاه آزاد | آزادویک\n\n` +
      `🗓 تاریخ: ${day ? day + '، ' : ''}${faNumber(date)}\n` +
      `⚡ وضعیت کلاس‌ها: «${status}»${weekNum}\n` +
      poemBlock + `\n` +
      `🔗 مشاهده آنلاین و برنامه ترم:\n` +
      `@${CONFIG.botUsername}`;
  }
  async function ensureStatusContext() {
    let status = getStatusText();
    let date = getCurrentDate() || (STATE.home && STATE.home.today) || '';
    if (status && date) return { status, date };
    try {
      if (date) {
        const checked = await api('check_date', { date });
        if (checked && checked.status === 'success') {
          onCheckResult(checked);
          status = normalizeWeekStatusText(checked.week_status) || checked.week_status || status;
          date = checked.date || date;
          return { status, date };
        }
      }
    } catch (_) {}
    try {
      const home = await api('init', {});
      if (home && home.status === 'success') {
        onHomeData(home);
        status = normalizeWeekStatusText(home.today_status) || home.today_status || status;
        date = home.today || date;
      }
    } catch (_) {}
    return { status: normalizeWeekStatusText(status) || status || '', date };
  }
  function xmlEscape(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
  function faNumber(value) {
    return String(value == null ? '' : value).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[Number(d)]);
  }
  function statusLabelEnglish(value) {
    const s = String(value || getStatusText() || '').toLowerCase();
    if (s.includes('فرد') || s.includes('odd')) return 'ODD WEEK';
    if (s.includes('زوج') || s.includes('even')) return 'EVEN WEEK';
    if (s.includes('خارج') || s.includes('out')) return 'OUT OF TERM';
    return 'WEEK STATUS';
  }
  function svgText(text, x, y, size, weight, color, extra) {
    return `<text x="${x}" y="${y}" text-anchor="middle" direction="rtl" unicode-bidi="plaintext" font-size="${size}" font-weight="${weight || 400}" fill="${color || '#fff'}" ${extra || ''}>${xmlEscape(faNumber(text))}</text>`;
  }
  function svgCardShell(accent) {
    return `
      <defs>
        <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0" stop-color="#050511"/><stop offset="0.55" stop-color="#111127"/><stop offset="1" stop-color="#02020a"/>
        </linearGradient>
        <radialGradient id="glow" cx="50%" cy="35%" r="70%">
          <stop offset="0" stop-color="${accent}" stop-opacity="0.35"/><stop offset="1" stop-color="${accent}" stop-opacity="0"/>
        </radialGradient>
        <filter id="softGlow"><feGaussianBlur stdDeviation="16" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
      </defs>
      <rect width="1080" height="1920" fill="url(#bg)"/>
      <rect width="1080" height="1920" fill="url(#glow)"/>
      <g opacity="0.28">
        <path d="M120 280h240v120h180" stroke="#00f2ff" stroke-width="4" fill="none"/>
        <path d="M920 410h-260v150h-210" stroke="#8324e4" stroke-width="4" fill="none"/>
        <path d="M140 1530h280v-150h230" stroke="#00f2ff" stroke-width="4" fill="none"/>
        <path d="M890 1450h-230v-110h-180" stroke="#8324e4" stroke-width="4" fill="none"/>
        <circle cx="360" cy="400" r="10" fill="#00f2ff"/><circle cx="660" cy="560" r="10" fill="#8324e4"/>
        <circle cx="420" cy="1380" r="10" fill="#00f2ff"/><circle cx="660" cy="1340" r="10" fill="#8324e4"/>
      </g>`;
  }
  function svgToPngBlob(svg, width = 1080, height = 1920) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      const url = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
      img.onload = function () {
        try {
          const canvas = document.createElement('canvas');
          canvas.width = width; canvas.height = height;
          const ctx = canvas.getContext('2d');
          ctx.fillStyle = '#050511';
          ctx.fillRect(0, 0, width, height);
          ctx.drawImage(img, 0, 0, width, height);
          canvas.toBlob(blob => blob ? resolve(blob) : reject(new Error('empty blob')), 'image/webp', 0.92);
        } catch (err) { reject(err); }
      };
      img.onerror = reject;
      img.src = url;
    });
  }
  function openImagePreview(blob, title, filename, extraActions) {
    ensureNativeSheetStyles();
    const url = URL.createObjectURL(blob);
    const overlay = document.createElement('div');
    overlay.className = 'bottom-sheet-overlay aw-preview-sheet show';
    overlay.innerHTML = `
      <div class="bottom-sheet aw-preview-inner" onclick="event.stopPropagation()">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
          <span class="title-font">${xmlEscape(title || 'تصویر وضعیت هفته')}</span>
          <button class="close-btn" type="button"><i class="fas fa-times"></i></button>
        </div>
        <div class="aw-preview-frame"><img src="${url}" alt="${xmlEscape(title || 'تصویر وضعیت آزادویک')}"></div>
        <div class="aw-action-grid">
          <button type="button" data-action="share"><ion-icon name="share-social-outline"></ion-icon><span>ارسال برای دوستان</span></button>
          <button type="button" data-action="download"><ion-icon name="download-outline"></ion-icon><span>ذخیره در گالری</span></button>
          ${(extraActions || []).map(a => `<button type="button" data-action="${xmlEscape(a.id)}"><ion-icon name="${xmlEscape(a.icon || 'sparkles-outline')}"></ion-icon><span>${xmlEscape(a.text)}</span></button>`).join('')}
        </div>
        <p class="aw-preview-hint text-font">اگر ذخیره خودکار انجام نشد، انگشتتان را روی تصویر نگه دارید و گزینه ذخیره تصویر را بزنید.</p>
      </div>`;
    const close = () => { overlay.classList.remove('show'); setTimeout(() => { URL.revokeObjectURL(url); overlay.remove(); updateBackButton(); syncSheetMode(); }, 250); };
    overlay.addEventListener('click', close);
    overlay.querySelector('.close-btn').addEventListener('click', close);
    overlay.querySelectorAll('[data-action]').forEach(btn => btn.addEventListener('click', async function (ev) {
      ev.stopPropagation();
      const action = this.dataset.action;
      haptic('impact', 'light');
      if (action === 'download') {
        setActionBusy(this, true, 'در حال ذخیره...');
        try { await downloadPngBlob(blob, (filename || 'azadweek-card.png').replace(/\.webp$/i, '.png'), (filename || '').includes('wrapped') ? 'wrapped' : 'status'); }
        finally { setTimeout(() => setActionBusy(this, false), 600); }
        return;
      }
      if (action === 'share') {
        setActionBusy(this, true, 'آماده‌سازی ارسال...');
        try {
          await shareCardToFriends(blob, title, filename);
        } finally {
          setTimeout(() => setActionBusy(this, false), 600);
        }
        return;
      }
      const handler = (extraActions || []).find(a => a.id === action && typeof a.run === 'function');
      if (handler) {
        setActionBusy(this, true, 'در حال انجام...');
        try { await Promise.resolve(handler.run()); }
        finally { setTimeout(() => setActionBusy(this, false), 500); }
      }
    }));
    document.body.appendChild(overlay);
    if (window.AzadWeekRenderIcons) window.AzadWeekRenderIcons(overlay);
    setSheetMode(true);
    updateBackButton();
  }

  function statusLabelPersian(value, fallback) {
    const s = String(value || getStatusText() || '').trim();
    if (s.includes('فرد') || /odd/i.test(s)) return 'هفته فرد';
    if (s.includes('زوج') || /even/i.test(s)) return 'هفته زوج';
    if (s.includes('خارج') || /out/i.test(s)) return 'خارج از بازه ترم';
    if (!s || /وضعیت هفته|بررسی وضعیت|---/.test(s)) return fallback == null ? '' : fallback;
    return s;
  }
  function wrappedFavoritePersian(label) {
    const s = String(label || topWrappedLabel(getWrappedStats()) || '').toLowerCase();
    if (s.includes('time') || s.includes('search') || s.includes('ماشین')) return 'جستجوی تاریخ';
    if (s.includes('term') || s.includes('list') || s.includes('ترم')) return 'جدول ترم';
    return 'وضعیت امروز';
  }
  function serverCardUrl(type, wrappedKind) {
    const params = new URLSearchParams({
      type: type || 'status',
      date: getCurrentDate() || (STATE.home && STATE.home.today) || '',
      status: statusLabelPersian(getStatusText(), ''),
      t: String(Date.now()),
      cv: 'template-fields-29'
    });
    const poem = getCurrentStatusPoem();
    params.set('poem1', poem[0] || '');
    params.set('poem2', poem[1] || '');
    if (type === 'wrapped') {
      const payload = getWrappedPayload(wrappedKind || STATE.lastWrappedKind || 'personal');
      params.set('mode', payload.kind);
      params.set('title', payload.title);
      params.set('subtitle', payload.subtitle);
      payload.items.forEach((item, idx) => {
        const n = idx + 1;
        params.set('value' + n, String(item.value || 0));
        params.set('label' + n, item.label || '');
      });
      params.set('line1', payload.line1 || '');
      params.set('line2', payload.line2 || '');
    }
    return absoluteUrl('card.php?' + params.toString());
  }
  async function fetchPngBlob(url) {
    const res = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
    if (!res.ok) throw new Error('WebP endpoint failed: ' + res.status);
    const blob = await res.blob();
    if (!blob || blob.size < 64) throw new Error('Empty WebP blob');
    return blob.type && blob.type.includes('webp') ? blob : new Blob([blob], { type: 'image/webp' });
  }

  function blobToDataUrl(blob) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(String(reader.result || ''));
      reader.onerror = reject;
      reader.readAsDataURL(blob);
    });
  }
  async function uploadCardBlob(blob, kind) {
    const dataUrl = await blobToDataUrl(blob);
    const res = await api('upload_card', {
      type: kind === 'wrapped' ? 'wrapped' : 'status',
      image: dataUrl
    });
    if (!res || res.status !== 'success' || !res.url) throw new Error((res && res.message) || 'Card upload failed');
    return res.url;
  }
  function templateUrl(kind) {
    return absoluteUrl('assets/card_templates/' + (kind === 'wrapped' ? 'wrapped_template.webp' : 'status_template.webp') + '?v=29-term-exact');
  }
  function loadImage(src) {
    const key = String(src || '');
    if (CARD_RUNTIME.imagePromises.has(key)) return CARD_RUNTIME.imagePromises.get(key);
    const promise = new Promise((resolve, reject) => {
      const img = new Image();
      img.crossOrigin = 'anonymous';
      img.decoding = 'async';
      img.onload = () => resolve(img);
      img.onerror = reject;
      img.src = src;
    });
    CARD_RUNTIME.imagePromises.set(key, promise);
    return promise;
  }
  async function ensureCardFonts() {
    if (CARD_RUNTIME.fontPromise) return CARD_RUNTIME.fontPromise;
    CARD_RUNTIME.fontPromise = (async () => {
      try {
        if (document.fonts && document.fonts.load) {
          await Promise.all([
            document.fonts.load('400 32px Vazirmatn'),
            document.fonts.load('700 44px Vazirmatn'),
            document.fonts.load('800 48px Vazirmatn'),
            document.fonts.load('900 136px Vazirmatn'),
            document.fonts.load('400 90px Aviny')
          ]);
          await document.fonts.ready;
        }
      } catch (_) {}
      return true;
    })();
    return CARD_RUNTIME.fontPromise;
  }
  function canvasBlob(canvas, mimeType, quality) {
    mimeType = mimeType || 'image/png';
    quality = quality || 0.95;
    return new Promise((resolve, reject) => {
      canvas.toBlob(b => b ? resolve(b) : reject(new Error('empty canvas blob')), mimeType, quality);
    });
  }
  function drawNeonText(ctx, text, x, y, size, color, opts) {
    opts = opts || {};
    const family = opts.family || (opts.title ? 'Aviny, "IRAN Sans Regular", Tahoma, Arial, sans-serif' : '"IRAN Sans Regular", Tahoma, Arial, sans-serif');
    ctx.save();
    ctx.direction = opts.ltr ? 'ltr' : 'rtl';
    ctx.textAlign = opts.align || 'center';
    ctx.textBaseline = 'middle';
    ctx.font = `${opts.weight || 700} ${size}px ${family}`;
    ctx.shadowColor = opts.glow || color;
    ctx.shadowBlur = opts.blur == null ? 16 : opts.blur;
    ctx.fillStyle = color;
    ctx.fillText(opts.noDigits ? String(text || '') : faNumber(text), x, y, opts.maxWidth || undefined);
    ctx.restore();
  }
  function fitNeonText(ctx, text, x, y, maxWidth, maxSize, minSize, color, opts) {
    opts = opts || {};
    const family = opts.family || (opts.title ? 'Aviny, "IRAN Sans Regular", Tahoma, Arial, sans-serif' : '"IRAN Sans Regular", Tahoma, Arial, sans-serif');
    let size = maxSize;
    ctx.save();
    ctx.direction = opts.ltr ? 'ltr' : 'rtl';
    ctx.textAlign = opts.align || 'center';
    ctx.textBaseline = 'middle';
    do {
      ctx.font = `${opts.weight || 700} ${size}px ${family}`;
      if (ctx.measureText(faNumber(text)).width <= maxWidth || size <= minSize) break;
      size -= 4;
    } while (size > minSize);
    ctx.restore();
    drawNeonText(ctx, text, x, y, size, color, Object.assign({}, opts, { maxWidth }));
  }
  function boxCenter(box) {
    return { x: box.x + box.w / 2, y: box.y + box.h / 2 };
  }
  function fitSizeInBox(ctx, text, box, maxSize, minSize, family, weight) {
    let size = maxSize;
    ctx.save();
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.direction = 'rtl';
    do {
      ctx.font = `${weight || 700} ${size}px ${family}`;
      if (ctx.measureText(faNumber(text)).width <= box.w * 0.9 || size <= minSize) break;
      size -= 4;
    } while (size > minSize);
    ctx.restore();
    return size;
  }
  function drawTextInBox(ctx, text, box, opts) {
    opts = opts || {};
    const family = opts.family || (opts.title ? 'Aviny, "IRAN Sans Regular", Tahoma, Arial, sans-serif' : '"IRAN Sans Regular", Tahoma, Arial, sans-serif');
    const center = boxCenter(box);
    const size = fitSizeInBox(ctx, text, box, opts.maxSize || 64, opts.minSize || 18, family, opts.weight || 700);
    drawNeonText(ctx, text, center.x, center.y + (opts.offsetY || 0), size, opts.color || '#fff', {
      title: !!opts.title,
      weight: opts.weight || 700,
      family,
      blur: opts.blur,
      glow: opts.glow,
      ltr: !!opts.ltr,
      noDigits: !!opts.noDigits
    });
    return size;
  }
  function drawExactText(ctx, text, x, y, size, opts) {
    opts = opts || {};
    const family = opts.family || 'Aviny, "IRAN Sans Regular", Tahoma, Arial, sans-serif';
    const rendered = opts.noDigits ? String(text || '') : faNumber(text);
    ctx.save();
    ctx.direction = opts.ltr ? 'ltr' : 'rtl';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'alphabetic';
    ctx.font = `${opts.weight || 400} ${size}px ${family}`;
    const metrics = ctx.measureText(rendered);
    const ascent = metrics.actualBoundingBoxAscent || size * 0.72;
    const descent = metrics.actualBoundingBoxDescent || size * 0.22;
    const baseline = y + (ascent - descent) / 2;
    if ((opts.blur || 0) > 0) {
      ctx.shadowColor = opts.glow || opts.color || '#ffffff';
      ctx.shadowBlur = opts.blur;
    } else {
      ctx.shadowBlur = 0;
    }
    ctx.fillStyle = opts.color || '#ffffff';
    ctx.fillText(rendered, x, baseline, opts.maxWidth || undefined);
    ctx.restore();
  }
  function cacheBlobGet(key) {
    return CARD_RUNTIME.blobCache.get(key) || null;
  }
  function cacheBlobSet(key, blob) {
    CARD_RUNTIME.blobCache.set(key, blob);
    if (CARD_RUNTIME.blobCache.size > 12) {
      const first = CARD_RUNTIME.blobCache.keys().next();
      if (!first.done) CARD_RUNTIME.blobCache.delete(first.value);
    }
  }
  function canvasWeekHint(status) {
    if (String(status).includes('خارج')) return 'این تاریخ در بازه فعال ترم نیست';
    const poem = getCurrentStatusPoem();
    return poem.join(' | ');
  }
  async function createTemplateCanvasBlob(kind, wrappedKind) {
    let cacheKey = '';
    let statusContext = null;
    let wrappedPayload = null;
    if (kind === 'wrapped') {
      wrappedPayload = getWrappedPayload(wrappedKind || STATE.lastWrappedKind || 'term');
      const poem = getCurrentStatusPoem();
      cacheKey = 'wrapped_v3:' + JSON.stringify({
        k: wrappedPayload.kind,
        t: wrappedPayload.title,
        s: wrappedPayload.subtitle,
        i: wrappedPayload.items,
        l1: wrappedPayload.line1,
        l2: wrappedPayload.line2,
        p1: poem[0],
        p2: poem[1]
      });
    } else {
      statusContext = await ensureStatusContext();
      const status = statusLabelPersian(statusContext.status || getStatusText(), 'وضعیت نامشخص');
      const date = statusContext.date || getCurrentDate() || (STATE.home && STATE.home.today) || '';
      const poem = getCurrentStatusPoem();
      cacheKey = 'status_v3:' + JSON.stringify({ s: status, d: date, p1: poem[0], p2: poem[1] });
    }
    const cached = cacheBlobGet(cacheKey);
    if (cached) return cached;

    await ensureCardFonts();
    const canvas = document.createElement('canvas');
    canvas.width = 1080; canvas.height = 1920;
    const ctx = canvas.getContext('2d');
    if (!ctx) throw new Error('No canvas context');

    const isOdd = kind === 'wrapped'
      ? false
      : String((statusContext && statusContext.status) || getStatusText() || '').includes('فرد');
    const isTermWrapped = kind === 'wrapped' && wrappedPayload && wrappedPayload.kind === 'term';
    const primaryColor = kind === 'wrapped'
      ? (isTermWrapped ? '#00e5ff' : '#d946ef')
      : (isOdd ? '#00e5ff' : '#d946ef');
    const secondaryColor = kind === 'wrapped'
      ? (isTermWrapped ? '#0284c7' : '#8b5cf6')
      : (isOdd ? '#0284c7' : '#8b5cf6');
    const vazir = "'Vazirmatn', -apple-system, BlinkMacSystemFont, sans-serif";

    const bgGrad = ctx.createLinearGradient(0, 0, 0, 1920);
    bgGrad.addColorStop(0, '#060D1A');
    bgGrad.addColorStop(0.25, (isOdd || isTermWrapped) ? '#0A172E' : '#140D2B');
    bgGrad.addColorStop(0.75, '#070E1C');
    bgGrad.addColorStop(1, '#040812');
    ctx.fillStyle = bgGrad;
    ctx.fillRect(0, 0, 1080, 1920);

    const topAura = ctx.createRadialGradient(540, 240, 0, 540, 240, 520);
    topAura.addColorStop(0, isOdd ? 'rgba(0, 229, 255, 0.18)' : 'rgba(217, 70, 239, 0.18)');
    topAura.addColorStop(1, 'rgba(6, 13, 26, 0)');
    ctx.fillStyle = topAura;
    ctx.fillRect(0, 0, 1080, 800);

    const btmAura = ctx.createRadialGradient(540, 1680, 0, 540, 1680, 480);
    btmAura.addColorStop(0, 'rgba(99, 102, 241, 0.12)');
    btmAura.addColorStop(1, 'rgba(6, 13, 26, 0)');
    ctx.fillStyle = btmAura;
    ctx.fillRect(0, 1200, 1080, 720);

    ctx.fillStyle = 'rgba(255, 255, 255, 0.22)';
    const seedPoints = [
      [120, 140, 2.5], [920, 160, 2], [240, 420, 3], [850, 480, 2],
      [160, 890, 2.5], [910, 940, 3], [140, 1340, 2], [930, 1420, 2.5],
      [300, 1720, 2.5], [820, 1750, 3], [540, 90, 2]
    ];
    seedPoints.forEach(pt => {
      ctx.beginPath();
      ctx.arc(pt[0], pt[1], pt[2], 0, Math.PI * 2);
      ctx.fill();
    });

    const mainX = 52, mainY = 60, mainW = 976, mainH = 1800, mainR = 54;
    roundRect(ctx, mainX, mainY, mainW, mainH, mainR);
    ctx.fillStyle = 'rgba(10, 19, 36, 0.84)';
    ctx.fill();

    const mainBorder = ctx.createLinearGradient(mainX, mainY, mainX + mainW, mainY + mainH);
    mainBorder.addColorStop(0, isOdd ? 'rgba(0, 229, 255, 0.55)' : 'rgba(217, 70, 239, 0.55)');
    mainBorder.addColorStop(0.5, 'rgba(255, 255, 255, 0.15)');
    mainBorder.addColorStop(1, 'rgba(99, 102, 241, 0.45)');
    ctx.strokeStyle = mainBorder;
    ctx.lineWidth = 2.5;
    ctx.stroke();

    ctx.save();
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.direction = 'rtl';

    const embX = 540, embY = 160, embR = 48;
    ctx.beginPath();
    ctx.arc(embX, embY, embR, 0, Math.PI * 2);
    ctx.fillStyle = 'rgba(15, 28, 54, 0.9)';
    ctx.fill();
    ctx.strokeStyle = primaryColor;
    ctx.lineWidth = 2;
    ctx.stroke();

    ctx.beginPath();
    ctx.arc(embX, embY, embR - 10, 0, Math.PI * 2);
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.25)';
    ctx.lineWidth = 1.5;
    ctx.stroke();

    ctx.save();
    ctx.fillStyle = primaryColor;
    ctx.strokeStyle = primaryColor;
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.beginPath();
    ctx.moveTo(embX, embY - 14);
    ctx.lineTo(embX + 24, embY - 3);
    ctx.lineTo(embX, embY + 8);
    ctx.lineTo(embX - 24, embY - 3);
    ctx.closePath();
    ctx.fill();
    ctx.beginPath();
    ctx.moveTo(embX - 14, embY + 2);
    ctx.quadraticCurveTo(embX - 14, embY + 16, embX, embY + 17);
    ctx.quadraticCurveTo(embX + 14, embY + 16, embX + 14, embY + 2);
    ctx.lineTo(embX + 10, embY + 3);
    ctx.quadraticCurveTo(embX, embY + 13, embX - 10, embY + 3);
    ctx.closePath();
    ctx.fill();
    ctx.beginPath();
    ctx.moveTo(embX, embY - 3);
    ctx.quadraticCurveTo(embX + 22, embY - 1, embX + 23, embY + 14);
    ctx.stroke();
    ctx.beginPath();
    ctx.arc(embX + 23, embY + 16, 2.5, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();

    ctx.font = `700 34px ${vazir}`;
    ctx.fillStyle = '#94a3b8';
    ctx.fillText('دانشگاه آزاد اسلامی', 540, 236);

    ctx.font = `800 42px ${vazir}`;
    ctx.fillStyle = '#ffffff';
    ctx.fillText('تقویم هوشمند هفته‌های آموزشی | آزادویک', 540, 288);

    const divGrad = ctx.createLinearGradient(200, 325, 880, 325);
    divGrad.addColorStop(0, 'rgba(255, 255, 255, 0)');
    divGrad.addColorStop(0.5, 'rgba(255, 255, 255, 0.2)');
    divGrad.addColorStop(1, 'rgba(255, 255, 255, 0)');
    ctx.strokeStyle = divGrad;
    ctx.lineWidth = 1.5;
    ctx.beginPath();
    ctx.moveTo(200, 325);
    ctx.lineTo(880, 325);
    ctx.stroke();
    ctx.restore();

    if (kind === 'wrapped') {
      const payload = wrappedPayload || getWrappedPayload(wrappedKind || STATE.lastWrappedKind || 'term');
      const isPersonal = (payload.kind || 'term') === 'personal';
      const poem = getCurrentStatusPoem();
      const heroColor = isPersonal ? '#d946ef' : '#00e5ff';
      const subColor = isPersonal ? '#f472b6' : '#38bdf8';

      const capX = 100, capY = 325, capW = 880, capH = 330, capR = 42;
      roundRect(ctx, capX, capY, capW, capH, capR);
      ctx.fillStyle = isPersonal ? 'rgba(22, 14, 42, 0.88)' : 'rgba(12, 24, 46, 0.88)';
      ctx.fill();

      ctx.save();
      ctx.strokeStyle = heroColor;
      ctx.lineWidth = 2.5;
      ctx.shadowColor = heroColor;
      ctx.shadowBlur = 32;
      ctx.stroke();
      ctx.restore();

      ctx.save();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.direction = 'rtl';

      ctx.font = `700 28px ${vazir}`;
      ctx.fillStyle = '#94a3b8';
      ctx.fillText(isPersonal ? 'کارنامه فعالیت و پایش برنامه در این دستگاه' : 'وضعیت کلی و تلمتری ترم آموزشی جاری', 540, capY + 45);

      ctx.font = `900 95px ${vazir}`;
      ctx.fillStyle = heroColor;
      ctx.shadowColor = heroColor;
      ctx.shadowBlur = 34;
      ctx.fillText(isPersonal ? 'دانشجوی همراه' : (payload.title || 'جمع‌بندی ترم'), 540, capY + 135);

      ctx.font = `700 32px ${vazir}`;
      ctx.fillStyle = '#ffffff';
      ctx.shadowBlur = 0;
      ctx.fillText(payload.line1 || (isPersonal ? 'بخش پرکاربرد: وضعیت امروز' : 'شروع ترم: ۲۵ شهریور • پایان: بهمن'), 540, capY + 230);

      ctx.font = `500 26px ${vazir}`;
      ctx.fillStyle = subColor;
      ctx.fillText(payload.line2 || (isPersonal ? 'ثبت و پایش منظم کلاس‌های زوج و فرد دانشگاه' : 'تقویم رسمی دانشگاه آزاد اسلامی'), 540, capY + 285);
      ctx.restore();

      const gridCenters = [
        { x: 100, y: 675, w: 425, h: 175, col: '#00e5ff' },
        { x: 555, y: 675, w: 425, h: 175, col: '#d946ef' },
        { x: 100, y: 870, w: 425, h: 175, col: '#d946ef' },
        { x: 555, y: 870, w: 425, h: 175, col: '#00e5ff' }
      ];

      (payload.items || []).forEach((item, idx) => {
        const box = gridCenters[idx];
        if (!box) return;
        roundRect(ctx, box.x, box.y, box.w, box.h, 28);
        ctx.fillStyle = 'rgba(13, 24, 46, 0.82)';
        ctx.fill();
        ctx.strokeStyle = box.col === '#00e5ff' ? 'rgba(0, 229, 255, 0.38)' : 'rgba(217, 70, 239, 0.38)';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.direction = 'rtl';

        ctx.font = `600 28px ${vazir}`;
        ctx.fillStyle = '#94a3b8';
        ctx.fillText(item.label || '', box.x + box.w / 2, box.y + 48);

        ctx.font = `900 66px ${vazir}`;
        ctx.fillStyle = box.col;
        ctx.shadowColor = box.col;
        ctx.shadowBlur = 18;
        ctx.fillText(faNumber(item.value ?? 0), box.x + box.w / 2, box.y + 118);
        ctx.restore();
      });

      const poemX = 100, poemY = 1070, poemW = 880, poemH = 420, poemR = 38;
      roundRect(ctx, poemX, poemY, poemW, poemH, poemR);
      ctx.fillStyle = 'rgba(14, 25, 52, 0.86)';
      ctx.fill();
      ctx.strokeStyle = isPersonal ? 'rgba(217, 70, 239, 0.38)' : 'rgba(0, 229, 255, 0.38)';
      ctx.lineWidth = 1.5;
      ctx.stroke();

      ctx.save();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.direction = 'rtl';

      ctx.font = `700 52px ${vazir}`;
      ctx.fillStyle = heroColor;
      ctx.fillText('«', 540, poemY + 60);

      ctx.font = `700 44px ${vazir}`;
      ctx.fillStyle = '#f8fafc';
      ctx.shadowColor = 'rgba(255, 255, 255, 0.3)';
      ctx.shadowBlur = 10;
      ctx.fillText(poem[0] || 'توانا بود هر که دانا بود', 540, poemY + 145);
      ctx.fillText(poem[1] || 'ز دانش دل پیر برنا بود', 540, poemY + 230);

      ctx.font = `700 52px ${vazir}`;
      ctx.fillStyle = heroColor;
      ctx.shadowBlur = 0;
      ctx.fillText('»', 540, poemY + 305);

      ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)';
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.moveTo(200, poemY + 355);
      ctx.lineTo(880, poemY + 355);
      ctx.stroke();

      ctx.font = `500 24px ${vazir}`;
      ctx.fillStyle = '#64748b';
      ctx.fillText('گاه‌شمار ادب و دانش • دانشگاه آزاد اسلامی', 540, poemY + 385);
      ctx.restore();

      const ftrX = 100, ftrY = 1520, ftrW = 880, ftrH = 230, ftrR = 36;
      roundRect(ctx, ftrX, ftrY, ftrW, ftrH, ftrR);
      ctx.fillStyle = 'rgba(15, 28, 56, 0.92)';
      ctx.fill();
      ctx.strokeStyle = 'rgba(0, 229, 255, 0.38)';
      ctx.lineWidth = 1.5;
      ctx.stroke();

      ctx.save();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';

      ctx.font = `800 54px ${vazir}`;
      ctx.fillStyle = '#00e5ff';
      ctx.fillText('@' + CONFIG.botUsername, 540, ftrY + 80);

      ctx.font = `600 30px ${vazir}`;
      ctx.fillStyle = '#cbd5e1';
      ctx.direction = 'rtl';
      ctx.fillText('دسترسی سریع به برنامه و تقویم هوشمند دانشگاه', 540, ftrY + 155);
      ctx.restore();
    } else {
      const context = statusContext || await ensureStatusContext();
      const status = statusLabelPersian(context.status || getStatusText(), 'وضعیت نامشخص');
      const date = context.date || getCurrentDate() || (STATE.home && STATE.home.today) || '';
      const day = (STATE.lastResult && STATE.lastResult.day) || (STATE.home && STATE.home.today_day) || '';
      const poem = getCurrentStatusPoem();
      const currentWeekId = (STATE.home && STATE.home.current_week && STATE.home.current_week.id) || 4;
      const progressPct = Number((STATE.home && STATE.home.term && STATE.home.term.progress_percent) || 23);
      const remainingWeeks = Math.max(1, 17 - currentWeekId);

      const capX = 100, capY = 340, capW = 880, capH = 430, capR = 44;
      roundRect(ctx, capX, capY, capW, capH, capR);
      ctx.fillStyle = 'rgba(12, 22, 44, 0.88)';
      ctx.fill();

      ctx.save();
      ctx.strokeStyle = primaryColor;
      ctx.lineWidth = 2.5;
      ctx.shadowColor = primaryColor;
      ctx.shadowBlur = 32;
      ctx.stroke();
      ctx.restore();

      ctx.save();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.direction = 'rtl';

      ctx.font = `700 32px ${vazir}`;
      ctx.fillStyle = '#94a3b8';
      ctx.fillText('وضعیت برگزاری کلاس‌ها در این هفته', 540, capY + 70);

      ctx.font = `900 136px ${vazir}`;
      ctx.fillStyle = primaryColor;
      ctx.shadowColor = primaryColor;
      ctx.shadowBlur = 36;
      ctx.fillText(status, 540, capY + 225);

      ctx.font = `700 36px ${vazir}`;
      ctx.fillStyle = '#ffffff';
      ctx.shadowBlur = 0;
      ctx.fillText(`(هفته ${faNumber(currentWeekId)} از ۱۷ ترم آموزشی)`, 540, capY + 340);

      ctx.font = `500 28px ${vazir}`;
      ctx.fillStyle = '#38bdf8';
      ctx.fillText(isOdd ? 'کلاس‌های تک‌جلسه‌ای و عملی گروه‌های فرد دایر است' : 'کلاس‌های تک‌جلسه‌ای و عملی گروه‌های زوج دایر است', 540, capY + 392);
      ctx.restore();

      const dateX = 100, dateY = 800, dateW = 880, dateH = 265, dateR = 36;
      roundRect(ctx, dateX, dateY, dateW, dateH, dateR);
      ctx.fillStyle = 'rgba(13, 24, 46, 0.82)';
      ctx.fill();
      ctx.strokeStyle = 'rgba(255, 255, 255, 0.12)';
      ctx.lineWidth = 1.5;
      ctx.stroke();

      ctx.save();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.direction = 'rtl';

      ctx.font = `800 48px ${vazir}`;
      ctx.fillStyle = '#ffffff';
      const fullDateStr = (day ? day + '، ' : '') + faNumber(date);
      ctx.fillText(fullDateStr, 540, dateY + 65);

      ctx.font = `600 28px ${vazir}`;
      ctx.fillStyle = '#38bdf8';
      ctx.fillText(`پیشرفت ترم آموزشی: ${faNumber(progressPct)}٪ سپری‌شده (${faNumber(remainingWeeks)} هفته باقیمانده)`, 540, dateY + 130);

      const dTrkX = dateX + 50, dTrkY = dateY + 185, dTrkW = dateW - 100, dTrkH = 18;
      roundRect(ctx, dTrkX, dTrkY, dTrkW, dTrkH, 9);
      ctx.fillStyle = 'rgba(255, 255, 255, 0.08)';
      ctx.fill();

      const dFillW = Math.max(18, (dTrkW * progressPct) / 100);
      roundRect(ctx, dTrkX, dTrkY, dFillW, dTrkH, 9);
      const dFillGrad = ctx.createLinearGradient(dTrkX, dTrkY, dTrkX + dFillW, dTrkY);
      dFillGrad.addColorStop(0, '#00e5ff');
      dFillGrad.addColorStop(1, '#d946ef');
      ctx.fillStyle = dFillGrad;
      ctx.fill();
      ctx.restore();

      const poemX = 100, poemY = 1095, poemW = 880, poemH = 410, poemR = 38;
      roundRect(ctx, poemX, poemY, poemW, poemH, poemR);
      ctx.fillStyle = 'rgba(13, 24, 48, 0.84)';
      ctx.fill();
      ctx.strokeStyle = isOdd ? 'rgba(0, 229, 255, 0.35)' : 'rgba(217, 70, 239, 0.35)';
      ctx.lineWidth = 1.5;
      ctx.stroke();

      ctx.save();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.direction = 'rtl';

      ctx.font = `700 48px ${vazir}`;
      ctx.fillStyle = primaryColor;
      ctx.fillText('«', 540, poemY + 60);

      ctx.font = `700 44px ${vazir}`;
      ctx.fillStyle = '#f8fafc';
      ctx.shadowColor = 'rgba(255, 255, 255, 0.3)';
      ctx.shadowBlur = 10;
      ctx.fillText(poem[0] || 'تحصیل، نردبانِ آرامِ امید است', 540, poemY + 145);
      ctx.fillText(poem[1] || 'پایانِ جهل و آغازِ سپید است', 540, poemY + 235);

      ctx.font = `700 48px ${vazir}`;
      ctx.fillStyle = primaryColor;
      ctx.shadowBlur = 0;
      ctx.fillText('»', 540, poemY + 315);

      ctx.font = `500 24px ${vazir}`;
      ctx.fillStyle = '#64748b';
      ctx.fillText('گاه‌شمار ادب و دانش • نیم‌سال تحصیلی دانشگاه آزاد اسلامی', 540, poemY + 365);
      ctx.restore();

      const ftrX = 100, ftrY = 1535, ftrW = 880, ftrH = 225, ftrR = 36;
      roundRect(ctx, ftrX, ftrY, ftrW, ftrH, ftrR);
      ctx.fillStyle = 'rgba(15, 28, 56, 0.92)';
      ctx.fill();
      ctx.strokeStyle = 'rgba(0, 229, 255, 0.38)';
      ctx.lineWidth = 1.5;
      ctx.stroke();

      ctx.save();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';

      ctx.font = `800 52px ${vazir}`;
      ctx.fillStyle = '#00e5ff';
      ctx.fillText('@' + CONFIG.botUsername, 540, ftrY + 80);

      ctx.font = `600 30px ${vazir}`;
      ctx.fillStyle = '#cbd5e1';
      ctx.direction = 'rtl';
      ctx.fillText('دسترسی سریع به برنامه و تقویم هوشمند دانشگاه', 540, ftrY + 155);
      ctx.restore();
    }

    const blob = await canvasBlob(canvas);
    cacheBlobSet(cacheKey, blob);
    return blob;
  }
  function hexToRgba(hex, alpha) {
    const m = String(hex || '#ffffff').replace('#','');
    const r = parseInt(m.slice(0,2), 16) || 255;
    const g = parseInt(m.slice(2,4), 16) || 255;
    const b = parseInt(m.slice(4,6), 16) || 255;
    return `rgba(${r},${g},${b},${alpha == null ? 1 : alpha})`;
  }
  function createFallbackCanvasBlob(kind) {
    return createTemplateCanvasBlob(kind).catch(() => fetchPngBlob(serverCardUrl(kind, STATE.lastWrappedKind)));
  }
  function downloadUrl(url, filename) {
    showToast('در حال آماده‌سازی فایل...');
    return fetchPngBlob(url)
      .then(blob => downloadPngBlob(blob, filename || 'azadweek-card.png', (filename || '').includes('wrapped') ? 'wrapped' : 'status'))
      .catch(() => alertNative('ذخیره این فایل انجام نشد. دوباره تلاش کنید.'));
  }
  function openImagePreviewUrl(imageUrl, title, filename, extraActions) {
    ensureNativeSheetStyles();
    const overlay = document.createElement('div');
    overlay.className = 'bottom-sheet-overlay aw-preview-sheet show';
    overlay.innerHTML = `
      <div class="bottom-sheet aw-preview-inner" onclick="event.stopPropagation()">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
          <span class="title-font">${xmlEscape(title || 'تصویر وضعیت هفته')}</span>
          <button class="close-btn" type="button"><i class="fas fa-times"></i></button>
        </div>
        <div class="aw-preview-frame"><img src="${xmlEscape(imageUrl)}" alt="${xmlEscape(title || 'تصویر وضعیت آزادویک')}"></div>
        <div class="aw-action-grid">
          <button type="button" data-action="share"><ion-icon name="share-social-outline"></ion-icon><span>ارسال برای دوستان</span></button>
          <button type="button" data-action="download"><ion-icon name="download-outline"></ion-icon><span>ذخیره در گالری</span></button>
          ${(extraActions || []).map(a => `<button type="button" data-action="${xmlEscape(a.id)}"><ion-icon name="${xmlEscape(a.icon || 'sparkles-outline')}"></ion-icon><span>${xmlEscape(a.text)}</span></button>`).join('')}
        </div>
        <p class="aw-preview-hint text-font">برای ذخیره مستقیم، می‌توانید انگشتتان را روی تصویر نگه دارید.</p>
      </div>`;
    const close = () => { overlay.classList.remove('show'); setTimeout(() => { overlay.remove(); updateBackButton(); syncSheetMode(); }, 250); };
    overlay.addEventListener('click', close);
    overlay.querySelector('.close-btn').addEventListener('click', close);
    overlay.querySelectorAll('[data-action]').forEach(btn => btn.addEventListener('click', async function (ev) {
      ev.stopPropagation();
      const action = this.dataset.action;
      haptic('impact', 'light');
      if (action === 'download') {
        setActionBusy(this, true, 'در حال ذخیره...');
        try { await downloadUrl(imageUrl, (filename || 'azadweek-card.png').replace(/\.webp$/i, '.png')); }
        finally { setTimeout(() => setActionBusy(this, false), 600); }
        return;
      }
      if (action === 'share') {
        setActionBusy(this, true, 'آماده‌سازی ارسال...');
        try {
          await shareCardToFriends(imageUrl, title, filename);
        } finally {
          setTimeout(() => setActionBusy(this, false), 600);
        }
        return;
      }
      const handler = (extraActions || []).find(a => a.id === action && typeof a.run === 'function');
      if (handler) {
        setActionBusy(this, true, 'در حال انجام...');
        try { await Promise.resolve(handler.run()); }
        finally { setTimeout(() => setActionBusy(this, false), 500); }
      }
    }));
    document.body.appendChild(overlay);
    if (window.AzadWeekRenderIcons) window.AzadWeekRenderIcons(overlay);
    setSheetMode(true);
    updateBackButton();
  }

  async function createCardBlob() {
    await ensureStatusContext();
    pickRandomStatusPoem(true);
    try { return await createTemplateCanvasBlob('status'); }
    catch (_) { return fetchPngBlob(serverCardUrl('status')); }
  }
  async function openStatusCardPreview() {
    haptic('impact', 'medium');
    showToast('در حال ساخت تصویر وضعیت...');
    try {
      const blob = await createCardBlob();
      openImagePreview(blob, 'تصویر وضعیت این هفته', 'azadweek-status-card.webp', [
        { id: 'inline', text: 'ارسال متنی', icon: 'send-outline', run: switchInlineShare },
        { id: 'story', text: 'انتشار در استوری', icon: 'sparkles-outline', run: shareToStory }
      ]);
    } catch (err) {
      console.debug('[AzadWeekNative] status preview failed', err);
      openImagePreviewUrl(serverCardUrl('status'), 'تصویر وضعیت این هفته', 'azadweek-status-card.webp', [
        { id: 'inline', text: 'ارسال متنی', icon: 'send-outline', run: switchInlineShare },
        { id: 'story', text: 'انتشار در استوری', icon: 'sparkles-outline', run: shareToStory }
      ]);
    }
  }
  async function downloadStatusCard() {
    haptic('impact', 'medium');
    showToast('در حال آماده‌سازی تصویر وضعیت...');
    try {
      const blob = await createCardBlob();
      await downloadPngBlob(blob, 'azadweek-status-card.webp', 'status');
    } catch (err) {
      console.debug('[AzadWeekNative] status download failed', err);
      alertNative('ساخت تصویر وضعیت انجام نشد. دوباره تلاش کنید.');
    }
  }

  function roundRect(ctx, x, y, w, h, r, fill, stroke) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    if (fill) ctx.fill();
    if (stroke) ctx.stroke();
  }
  function wrapText(ctx, text, x, y, maxWidth, lineHeight) {
    const words = String(text || '').split(' ');
    let line = '';
    for (let n = 0; n < words.length; n++) {
      const testLine = line + words[n] + ' ';
      if (ctx.measureText(testLine).width > maxWidth && n > 0) {
        ctx.fillText(line.trim(), x, y);
        line = words[n] + ' ';
        y += lineHeight;
      } else line = testLine;
    }
    ctx.fillText(line.trim(), x, y);
  }
  async function createWrappedBlob(kind) {
    kind = kind === 'term' ? 'term' : 'personal';
    STATE.lastWrappedKind = kind;
    pickRandomStatusPoem(true);
    try { return await createTemplateCanvasBlob('wrapped', kind); }
    catch (_) { return fetchPngBlob(serverCardUrl('wrapped', kind)); }
  }

  async function showWrappedCard(kind) {
    kind = kind === 'term' ? 'term' : 'personal';
    STATE.lastWrappedKind = kind;
    haptic('impact', 'medium');
    showToast(kind === 'term' ? 'در حال آماده‌سازی خلاصه ترم...' : 'در حال آماده‌سازی آمار شما...');
    const wrappedUrl = serverCardUrl('wrapped', kind);
    const actions = kind === 'personal'
      ? [{ id: 'clearWrapped', text: 'صفر کردن آمار', icon: 'refresh-outline', run: function () { return resetWrappedStats(true); } }]
      : [{ id: 'calendar', text: 'افزودن تقویم به گوشی', icon: 'calendar-outline', run: downloadCalendar }];
    try {
      const blob = await createWrappedBlob(kind);
      haptic('notify', 'success');
      openImagePreview(blob, kind === 'term' ? 'تصویر خلاصه ترم' : 'تصویر آمار من', kind === 'term' ? 'azadweek-term-wrapped.webp' : 'azadweek-my-wrapped.webp', actions);
    } catch (err) {
      console.debug('[AzadWeekNative] wrapped blob fallback', err);
      openImagePreviewUrl(wrappedUrl, kind === 'term' ? 'تصویر خلاصه ترم' : 'تصویر آمار من', kind === 'term' ? 'azadweek-term-wrapped.webp' : 'azadweek-my-wrapped.webp', actions);
    }
  }
  function resetWrappedStats(refreshPreview) {
    try { localStorage.removeItem(CONFIG.storageKeys.wrappedStats); } catch (_) {}
    try { if (TG && TG.CloudStorage) TG.CloudStorage.removeItem(CONFIG.storageKeys.wrappedStats, function () {}); } catch (_) {}
    try { if (TG && TG.DeviceStorage) TG.DeviceStorage.removeItem(CONFIG.storageKeys.wrappedStats, function () {}); } catch (_) {}
    STATE.__openRecorded = false;
    CARD_RUNTIME.blobCache.clear();
    const fresh = {
      firstOpen: Date.now(),
      opens: 0,
      checks: 0,
      shares: 0,
      downloads: 0,
      shakes: 0,
      odd: 0,
      even: 0,
      outside: 0,
      views: { home: 0, search: 0, list: 0 },
      lastUpdate: Date.now()
    };
    saveWrappedStats(fresh);
    haptic('notify', 'success');
    showToast('آمار بازدیدهای شما با موفقیت صفر شد ✨', 2500, { type: 'success' });
    const openPreview = document.querySelector('.bottom-sheet-overlay.show');
    if (openPreview) openPreview.click();
    setTimeout(() => {
      showWrapped('personal');
    }, 200);
    return Promise.resolve(true);
  }
  function showWrapped(kind) {
    kind = kind === 'term' ? 'term' : 'personal';
    STATE.lastWrappedKind = kind;
    haptic('impact', 'medium');
    const payload = getWrappedPayload(kind);
    const summary = payload.items.map(i => i.label + ': ' + faNumber(i.value)).join(' | ') + ' — ' + payload.line1 + '، ' + payload.line2;
    const actions = [
      { text: 'دریافت تصویر خلاصه', icon: 'image-outline', run: function () { showWrappedCard(kind); } },
      { text: 'ارسال متنی برای دوستان', icon: 'send-outline', run: function () { sharePlainText(getWrappedShareText(kind), payload.title); } }
    ];
    if (kind === 'personal') actions.push({ text: 'صفر کردن آمار من', icon: 'refresh-outline', danger: true, run: function () { return resetWrappedStats(true); } });
    else actions.push({ text: 'افزودن تقویم ترم به گوشی', icon: 'calendar-outline', run: downloadCalendar });
    showNativeSheet(payload.kind === 'term' ? 'خلاصه وضعیت کل ترم' : 'آمار بازدیدهای من', summary, actions);
  }
  async function shareVisualCard() {
    haptic('impact', 'medium');
    showToast('در حال آماده‌سازی تصویر وضعیت...');
    await ensureStatusContext();
    let blob = null;
    let uploadedUrl = '';
    try {
      blob = await createCardBlob();
    } catch (err) {
      console.debug('[AzadWeekNative] create status card failed', err);
    }

    if (blob) {
      try { uploadedUrl = await uploadCardBlob(blob, 'status'); } catch (err) { console.debug('[AzadWeekNative] upload status card failed', err); }
      if (uploadedUrl) {
        try {
          await prepareShareMessage(true, uploadedUrl, 'status', getShareText());
          if (STATE.preparedMessageId && TG && TG.shareMessage) {
            return TG.shareMessage(STATE.preparedMessageId, function (sent) {
              haptic('notify', sent ? 'success' : 'warning');
              if (sent) { recordWrappedEvent('share'); return; }
              openImagePreview(blob, 'تصویر وضعیت این هفته', 'azadweek-card.webp', [
                { id: 'inline', text: 'ارسال متنی', icon: 'send-outline', run: switchInlineShare },
                { id: 'story', text: 'انتشار در استوری', icon: 'sparkles-outline', run: shareToStory }
              ]);
            });
          }
        } catch (err) { console.debug('[AzadWeekNative] prepared share failed', err); }
      }
      return openImagePreview(blob, 'تصویر وضعیت این هفته', 'azadweek-card.webp', [
        { id: 'inline', text: 'ارسال متنی', icon: 'send-outline', run: switchInlineShare },
        { id: 'story', text: 'انتشار در استوری', icon: 'sparkles-outline', run: shareToStory }
      ]);
    }

    const fallbackUrl = serverCardUrl('status');
    openImagePreviewUrl(fallbackUrl, 'تصویر وضعیت این هفته', 'azadweek-card.webp', [
      { id: 'inline', text: 'ارسال متنی', icon: 'send-outline', run: switchInlineShare },
      { id: 'story', text: 'انتشار در استوری', icon: 'sparkles-outline', run: shareToStory }
    ]);
  }
  function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'azadweek-file';
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    showToast('فایل آماده ذخیره شد.');
    haptic('notify', 'success');
    setTimeout(() => { URL.revokeObjectURL(url); a.remove(); }, 2500);
  }
  async function shareCardToFriends(blobOrUrl, title, filename) {
    haptic('impact', 'medium');
    showToast('در حال آماده‌سازی تصویر برای ارسال به دوستان...', 3000, { loading: true });
    let uploadedUrl = '';
    const kind = (filename || '').includes('wrapped') ? ((filename || '').includes('term') ? 'wrapped_term' : 'wrapped_personal') : 'status';

    try {
      if (blobOrUrl instanceof Blob) {
        uploadedUrl = await uploadCardBlob(blobOrUrl, kind.includes('wrapped') ? 'wrapped' : 'status');
      } else if (typeof blobOrUrl === 'string' && blobOrUrl.startsWith('http')) {
        uploadedUrl = blobOrUrl;
      } else {
        const b = await createCardBlob();
        uploadedUrl = await uploadCardBlob(b, kind.includes('wrapped') ? 'wrapped' : 'status');
      }
    } catch (err) {
      console.debug('[AzadWeekNative] upload for shareCardToFriends failed', err);
    }

    if (uploadedUrl) {
      try {
        const prepId = await prepareShareMessage(true, uploadedUrl, kind);
        if (prepId && TG && typeof TG.shareMessage === 'function') {
          TG.shareMessage(prepId, function (sent) {
            haptic('notify', sent ? 'success' : 'warning');
            if (sent) {
              recordWrappedEvent('share');
              showToast('تصویر با موفقیت ارسال شد ✨', 2500, { type: 'success' });
            }
          });
          return;
        }
      } catch (err) {
        console.debug('[AzadWeekNative] TG.shareMessage error', err);
      }
    }

    if (TG && typeof TG.switchInlineQuery === 'function') {
      const q = getCurrentDate() || 'امروز';
      TG.switchInlineQuery(q, ['users', 'groups']);
      showToast('مخاطب یا گروه مورد نظر را انتخاب کنید.', 2500);
      return;
    }

    const shareText = kind.includes('wrapped') ? getWrappedShareText(kind.includes('term') ? 'term' : 'personal') : getShareText();
    sharePlainText(shareText, title);
  }

  function triggerHiddenDownload(url) {
    if (!url) return;
    try {
      const iframe = document.createElement('iframe');
      iframe.style.display = 'none';
      iframe.src = url;
      document.body.appendChild(iframe);
      setTimeout(() => { try { iframe.remove(); } catch (_) {} }, 15000);
    } catch (_) {}
  }

  async function downloadPngBlob(blob, filename, kind) {
    filename = filename ? filename.replace(/\.webp$/i, '.png') : 'azadweek-card.png';
    recordWrappedEvent('download');
    if (!isMobileClient() && blob) {
      try { downloadBlob(blob, filename); } catch (_) {}
    }

    let sentToChat = false;
    let dlUrl = '';
    try {
      const dataUrl = await blobToDataUrl(blob);
      const user = TG && TG.initDataUnsafe && TG.initDataUnsafe.user ? TG.initDataUnsafe.user : null;
      const res = await api('send_card_to_chat', {
        type: kind === 'wrapped' ? 'wrapped' : 'status',
        image: dataUrl,
        user_id: user && user.id ? user.id : ''
      });
      if (res && res.status === 'success') {
        sentToChat = !!res.sent_to_chat;
        dlUrl = res.download_url || '';
      }
    } catch (err) {
      console.debug('[AzadWeekNative] send_card_to_chat error', err);
    }

    if (isMobileClient()) {
      if (dlUrl) triggerHiddenDownload(dlUrl);
      else if (blob) {
        try { downloadBlob(blob, filename); } catch (_) {}
      }
    }

    haptic('notify', 'success');
    if (sentToChat) {
      showToast('تصویر ذخیره شد و به چت ربات هم ارسال گردید ✨', 3500, { type: 'success' });
    } else {
      showToast('تصویر با موفقیت آماده و ذخیره شد ✨', 3200, { type: 'success' });
    }
  }

  async function shareToStory() {
    haptic('impact', 'medium');
    showToast('در حال آماده‌سازی تصویر برای انتشار در استوری...', 3000, { loading: true });
    await ensureStatusContext();
    let media = '';
    let blob = null;
    try {
      blob = await createCardBlob();
    } catch (err) {
      console.debug('[AzadWeekNative] create story blob failed', err);
    }

    const hasNativeStory = !!(TG && typeof TG.shareToStory === 'function');
    if (hasNativeStory) {
      try {
        if (blob) {
          media = await uploadCardBlob(blob, 'status');
        } else {
          media = serverCardUrl('status', null, 'png');
        }
        if (media && String(media).startsWith('http')) {
          const status = statusLabelPersian(getStatusText(), 'هفته آموزشی');
          const storyCaption = `تقویم آموزشی دانشگاه آزاد | ${status}\nبرنامه در آزادویک 🎓\n@${CONFIG.botUsername}`;
          TG.shareToStory(media, {
            text: storyCaption
          });
          haptic('notify', 'success');
          showToast('در حال انتقال به استوری‌ساز تلگرام...', 2500, { type: 'success' });
          return;
        }
      } catch (err) {
        console.debug('[AzadWeekNative] native shareToStory error', err);
      }
    }

    if (blob) {
      await downloadPngBlob(blob, 'azadweek-story-card.png', 'status');
      haptic('notify', 'success');
      showToast('تصویر استوری ذخیره شد؛ می‌توانید در استوری قرار دهید.', 3500, { type: 'success' });
    } else {
      showToast('استوری در نسخه فعلی تلگرام شما در دسترس نیست.', 3000);
    }
  }
  function prepareShareMessage(silent, cardUrl, shareKind, textOverride) {
    const user = TG && TG.initDataUnsafe && TG.initDataUnsafe.user ? TG.initDataUnsafe.user : null;
    if (!user || !user.id) {
      if (!silent) switchInlineShare();
      return Promise.resolve(null);
    }
    const normalizedKind = shareKind || 'status';
    const statusText = statusLabelPersian(getStatusText(), '');
    return api('prepare_share_card', {
      user_id: user.id,
      share_type: normalizedKind,
      date: getCurrentDate(),
      status_text: statusText,
      text: textOverride || (normalizedKind === 'wrapped_term' ? getWrappedShareText('term') : normalizedKind === 'wrapped_personal' ? getWrappedShareText('personal') : getShareText()),
      card_url: cardUrl || ''
    }).then(res => {
      if (res && res.status === 'success' && res.prepared_message_id) {
        STATE.preparedMessageId = res.prepared_message_id;
        return res.prepared_message_id;
      }
      if (!silent && res && res.message) alertNative(res.message);
      return null;
    }).catch(() => null);
  }
  function switchInlineShare() {
    return sharePlainText(getShareText(), 'وضعیت هفته در آزادویک');
  }
  function copyToClipboard(text) {
    if (navigator.clipboard) navigator.clipboard.writeText(text).then(() => alertNative('متن وضعیت هفته کپی شد.'));
    else alertNative(text);
  }
  async function downloadCalendar() {
    haptic('impact', 'medium');
    recordWrappedEvent('download');
    showToast('در حال ارسال و ذخیره تقویم ترم...', 2500, { loading: true });
    const dynamicUrl = absoluteUrl('connect.php?action=download_ics&download=1&v=' + Date.now());
    const staticUrl = absoluteUrl('assets/calendar.ics?v=' + Date.now());

    triggerHiddenDownload(dynamicUrl);

    let sentToChat = false;
    try {
      const user = TG && TG.initDataUnsafe && TG.initDataUnsafe.user ? TG.initDataUnsafe.user : null;
      const res = await api('send_ics_to_chat', {
        user_id: user && user.id ? user.id : ''
      });
      if (res && res.status === 'success' && res.sent_to_chat) {
        sentToChat = true;
      }
    } catch (err) {
      console.debug('[AzadWeekNative] send_ics_to_chat error', err);
    }

    if (!isMobileClient()) {
      try {
        let r = await fetch(staticUrl, { cache: 'no-store' });
        if (!r.ok) r = await fetch(dynamicUrl, { cache: 'no-store' });
        if (r.ok) {
          const blob = await r.blob();
          if (blob && blob.size >= 32) {
            const calendarBlob = new Blob([blob], { type: 'text/calendar;charset=utf-8' });
            downloadBlob(calendarBlob, 'AzadWeek-Term-Calendar.ics');
          }
        }
      } catch (_) {}
    }

    haptic('notify', 'success');
    if (sentToChat) {
      showToast('تقویم ترم دانلود شد و به چت ربات هم ارسال گردید ✨', 4000, { type: 'success' });
    } else {
      showToast('تقویم ترم با موفقیت دانلود شد ✨', 3500, { type: 'success' });
    }
  }

  function checkToday() {
    setSheetMode(false);
    try {
      document.body.classList.remove('aw-sheet-open');
      document.documentElement.classList.remove('aw-sheet-open');
      if (TG && TG.disableVerticalSwipes) tgCall('disableVerticalSwipes');
    } catch (_) {}
    closeNativeSheet();
    haptic('impact', 'light');
    if (window.switchView) window.switchView('home', 0);
    const today = (STATE.home && STATE.home.today) || getCurrentDate() || '';
    if (today && window.AzadWeekSetSelectedDate) {
      window.AzadWeekSetSelectedDate(today);
    }
    if (window.fetchData) {
      window.fetchData('init');
    }
  }
  function extractDate(text) {
    const normalized = String(text || '').replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/-/g, '/');
    try {
      const url = new URL(normalized);
      const qp = url.searchParams.get('date') || url.searchParams.get('d') || url.searchParams.get('startapp') || '';
      const m = qp.replace(/date_/i, '').replace(/-/g, '/').match(/(1[34]\d{2})\/(\d{1,2})\/(\d{1,2})/);
      if (m) return `${m[1]}/${String(m[2]).padStart(2, '0')}/${String(m[3]).padStart(2, '0')}`;
    } catch (_) {}
    const m = normalized.match(/(1[34]\d{2})\/(\d{1,2})\/(\d{1,2})/);
    return m ? `${m[1]}/${String(m[2]).padStart(2, '0')}/${String(m[3]).padStart(2, '0')}` : null;
  }
  function applyDateFromExternal(date) {
    const normalized = normalizeJalaliDate(date);
    if (!normalized) return alertNative('تاریخ واردشده معتبر نیست.');
    STATE.selectedDate = normalized;
    if (window.AzadWeekSetSelectedDate) window.AzadWeekSetSelectedDate(normalized);
    else {
      const input = document.getElementById('dateInput');
      const display = document.getElementById('dateDisplay');
      if (input) input.value = normalized;
      if (display) { display.textContent = normalized; display.classList.remove('placeholder'); }
    }
    if (window.switchView) window.switchView('search', 1);
    setTimeout(() => { if (window.checkDate) window.checkDate(); }, 350);
  }
  function requestReminderAccess() {
    haptic('impact', 'medium');
    if (TG && TG.requestWriteAccess) {
      TG.requestWriteAccess(function (granted) {
        STATE.securePrefs.reminder = !!granted;
        STATE.securePrefs.reminderUpdatedAt = Date.now();
        savePreferences();
        api('save_preferences', { preferences: JSON.stringify(STATE.securePrefs) }).catch(() => {});
        haptic('notify', granted ? 'success' : 'warning');
        alertNative(granted ? 'ارسال پیام یادآور در تلگرام برای شما فعال شد.' : 'اجازه ارسال پیام یادآور داده نشد.');
      });
    } else alertNative('این قابلیت در نسخه فعلی تلگرام شما در دسترس نیست.');
  }
  function maybeSuggestShortcut(force) {
    if (!TG) {
      if (force) showToast('این قابلیت فقط در اپلیکیشن تلگرام فعال است.', 2500);
      return;
    }
    if (typeof TG.checkHomeScreenStatus === 'function') {
      try {
        TG.checkHomeScreenStatus(function (status) {
          STATE.homeScreenStatus = status;
          if (force) {
            if (status === 'added') {
              haptic('notify', 'success');
              showToast('آیکون آزادویک از قبل در صفحه اصلی شما وجود دارد ✨', 3000, { type: 'success' });
            } else if (typeof TG.addToHomeScreen === 'function') {
              haptic('impact', 'medium');
              TG.addToHomeScreen();
            } else {
              showNativeSheet('افزودن به صفحه اصلی گوشی', 'برای دسترسی سریع و همیشگی به آزادویک:\n۱. روی علامت سه نقطه (⋮) در بالای تلگرام بزنید.\n۲. گزینه «Add to Home screen» را انتخاب کنید.\nآیکون برنامه به صفحه موبایل اضافه می‌شود ✨', [
                { text: 'متوجه شدم', icon: 'checkmark-circle-outline', run: closeNativeSheet }
              ]);
            }
            return;
          }
          const opens = Number(STATE.devicePrefs.opens || 0) + 1;
          STATE.devicePrefs.opens = opens;
          savePreferences();
          if (opens >= 3 && (status === 'missed' || status === 'unknown') && typeof TG.addToHomeScreen === 'function') {
            TG.addToHomeScreen();
          }
        });
        return;
      } catch (err) {
        console.debug('[AzadWeekNative] checkHomeScreenStatus error', err);
      }
    }
    if (force) {
      if (typeof TG.addToHomeScreen === 'function') {
        haptic('impact', 'medium');
        try {
          TG.addToHomeScreen();
          return;
        } catch (_) {}
      }
      showNativeSheet('افزودن به صفحه اصلی گوشی', 'برای دسترسی سریع و همیشگی به آزادویک:\n۱. روی علامت سه نقطه (⋮) در بالای تلگرام بزنید.\n۲. گزینه «Add to Home screen» را انتخاب کنید.\nآیکون برنامه به صفحه موبایل اضافه می‌شود ✨', [
        { text: 'متوجه شدم', icon: 'checkmark-circle-outline', run: closeNativeSheet }
      ]);
    }
  }
  function startMotionRefresh() {
    if (!isMobileClient() || STATE.motionStarted) return;
    STATE.motionStarted = true;
    if (TG && TG.Accelerometer && versionAtLeast('8.0')) {
      try {
        TG.onEvent && TG.onEvent('accelerometerChanged', function () {
          const a = TG.Accelerometer;
          const mag = Math.sqrt((a.x || 0) ** 2 + (a.y || 0) ** 2 + (a.z || 0) ** 2);
          if (mag > 23) triggerShakeRefresh();
        });
        TG.Accelerometer.start({ refresh_rate: 80 }, function () {});
        return;
      } catch (_) {}
    }
    try {
      window.addEventListener('devicemotion', function (e) {
        const a = e.accelerationIncludingGravity || e.acceleration || {};
        const mag = Math.sqrt((a.x || 0) ** 2 + (a.y || 0) ** 2 + (a.z || 0) ** 2);
        if (mag > 24) triggerShakeRefresh();
      }, { passive: true });
    } catch (_) {}
  }
  function pulse(selector) {
    const el = document.querySelector(selector);
    if (!el) return;
    el.classList.remove('azad-shake-pulse');
    void el.offsetWidth;
    el.classList.add('azad-shake-pulse');
    setTimeout(() => el.classList.remove('azad-shake-pulse'), 850);
  }
  function focusCurrentWeek() {
    const row = document.querySelector('.week-row.current');
    if (!row) return;
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    row.classList.remove('azad-focus');
    void row.offsetWidth;
    row.classList.add('azad-focus');
    setTimeout(() => row.classList.remove('azad-focus'), 1100);
  }
  function triggerShakeRefresh() {
    if (STATE.devicePrefs.shake === false) return;
    const now = Date.now();
    if (now - STATE.lastShakeAt < 1800) return;
    const chained = now - STATE.lastShakeAt < 6500;
    STATE.lastShakeAt = now;
    STATE.shakeCombo = chained ? Number(STATE.shakeCombo || 0) + 1 : 1;
    recordWrappedEvent('shake');
    haptic('notify', STATE.shakeCombo >= 3 ? 'success' : 'warning');
    document.body.classList.add('azad-shake-pulse');
    setTimeout(() => document.body.classList.remove('azad-shake-pulse'), 700);
    if (STATE.shakeCombo >= 3) {
      STATE.shakeCombo = 0;
      return showWrapped('personal');
    }
    if (STATE.view === 'search') {
      if (getCurrentDate()) {
        pulse('#resultCard');
        if (window.checkDate) window.checkDate();
      } else checkToday();
      return;
    }
    if (STATE.view === 'list') {
      focusCurrentWeek();
      return;
    }
    pulse('.main-card');
    if (window.fetchData) window.fetchData('init');
  }
  function ensureNativeSheetStyles() {
    if (document.getElementById('azadNativeSheetStyle')) return;
    const style = document.createElement('style');
    style.id = 'azadNativeSheetStyle';
    style.textContent = `
      .aw-native-sheet .bottom-sheet{max-height:86vh;overflow:auto}.aw-action-list{display:grid;gap:10px;margin-top:12px}
      .aw-action-list button,.aw-action-grid button{border:1px solid rgba(255,255,255,.09);background:rgba(30,41,59,.62);color:#f8fafc;border-radius:16px;padding:12px 14px;font-family:var(--font-text);font-size:.86rem;font-weight:700;direction:rtl;display:flex;flex-direction:row;align-items:center;justify-content:flex-start;gap:10px;box-shadow:0 8px 20px rgba(0,0,0,.22);cursor:pointer;transition:.2s ease}
      .aw-action-list button:hover,.aw-action-grid button:hover{background:rgba(51,65,85,.72);border-color:rgba(99,102,241,.4)}
      .aw-action-list button:active,.aw-action-grid button:active{transform:scale(.98)}
      .aw-action-list ion-icon,.aw-action-grid ion-icon{font-size:1.25rem;color:var(--accent);flex-shrink:0}
      .aw-action-list .danger{border-color:rgba(244,63,94,.35);color:#fda4af}.aw-action-list .danger ion-icon{color:#f43f5e}
      .aw-subtitle{color:var(--text-muted);font-size:.82rem;line-height:1.75;margin:4px 0 12px}.aw-preview-inner{max-height:92vh;overflow:auto}.aw-preview-frame{border-radius:20px;overflow:hidden;background:#090D16;border:1px solid rgba(255,255,255,.1);box-shadow:0 12px 32px rgba(0,0,0,.45);margin:12px 0}
      .aw-preview-frame img{display:block;width:100%;height:auto;max-height:64vh;object-fit:contain;background:#090D16;-webkit-touch-callout:default!important;-webkit-user-select:auto!important;user-select:auto!important;pointer-events:auto!important}
      .aw-action-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.aw-preview-hint{color:var(--text-muted);text-align:center;font-size:.76rem;margin:12px 0 0}.cyber-nav{transition:opacity .25s ease,transform .25s ease}body.aw-sheet-open .cyber-nav{opacity:0;pointer-events:none;transform:translateY(140%) scale(.98)}
      .aw-settings-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(56,189,248,.12);border:1px solid rgba(56,189,248,.25);border-radius:999px;padding:6px 10px;font-size:.78rem;color:#bae6fd}
      .aw-action-grid button span,.aw-action-list button span{flex:1;white-space:normal;text-align:right;direction:rtl;line-height:1.35}.aw-action-grid button{min-height:48px}
    `;
    document.head.appendChild(style);
  }
  function closeNativeSheet() {
    clearTimeout(closeNativeSheet._timer);
    const sheet = document.getElementById('awNativeSheet');
    if (!sheet) {
      updateBackButton();
      syncSheetMode();
      return;
    }
    sheet.classList.remove('show');
    syncSheetMode();
    closeNativeSheet._timer = setTimeout(() => {
      if (sheet.parentNode && !sheet.classList.contains('show')) sheet.remove();
      updateBackButton();
      syncSheetMode();
    }, 220);
  }
  function showNativeSheet(title, subtitle, actions) {
    clearTimeout(closeNativeSheet._timer);
    ensureNativeSheetStyles();
    const old = document.getElementById('awNativeSheet');
    if (old) old.remove();
    const overlay = document.createElement('div');
    overlay.id = 'awNativeSheet';
    overlay.className = 'bottom-sheet-overlay aw-native-sheet show';
    overlay.innerHTML = `
      <div class="bottom-sheet" onclick="event.stopPropagation()">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
          <span class="title-font">${xmlEscape(title || 'امکانات')}</span>
          <button class="close-btn" type="button"><i class="fas fa-times"></i></button>
        </div>
        ${subtitle ? `<p class="aw-subtitle text-font">${xmlEscape(subtitle)}</p>` : ''}
        <div class="aw-action-list"></div>
      </div>`;
    const list = overlay.querySelector('.aw-action-list');
    (actions || []).forEach(action => {
      const btn = document.createElement('button');
      btn.type = 'button';
      if (action.danger) btn.classList.add('danger');
      btn.innerHTML = `<span>${xmlEscape(action.text)}</span><ion-icon name="${xmlEscape(action.icon || 'chevron-back-outline')}"></ion-icon>`;
      btn.addEventListener('click', function (ev) {
        ev.stopPropagation();
        haptic('impact', 'light');
        if (action.close !== false) closeNativeSheet();
        setTimeout(() => action.run && action.run(), action.close === false ? 0 : 120);
      });
      list.appendChild(btn);
    });
    overlay.addEventListener('click', closeNativeSheet);
    overlay.querySelector('.close-btn').addEventListener('click', closeNativeSheet);
    document.body.appendChild(overlay);
    if (window.AzadWeekRenderIcons) window.AzadWeekRenderIcons(overlay);
    setSheetMode(true);
    updateBackButton();
  }
  function openSettingsMenu() {
    haptic('impact', 'light');
    showNativeSheet('امکانات آزادویک', 'ارسال وضعیت هفته برای هم‌کلاسی‌ها، افزودن تقویم ترم به موبایل و تنظیمات برنامه', [
      { text: 'اشتراک‌گذاری وضعیت این هفته', icon: 'share-social-outline', run: openShareMenu },
      { text: 'خلاصه وضعیت ترم و آمار من', icon: 'pie-chart-outline', run: openWrappedMenu },
      { text: 'افزودن تقویم ترم به گوشی', icon: 'calendar-outline', run: openToolsMenu },
      { text: 'یادآور تلگرام و تنظیمات', icon: 'options-outline', run: openPrefsMenu }
    ]);
  }
  function openShareMenu() {
    showNativeSheet('اشتراک‌گذاری وضعیت هفته', getTitleLine(), [
      { text: 'مشاهده و ارسال تصویر وضعیت', icon: 'image-outline', run: openStatusCardPreview },
      { text: 'ذخیره تصویر وضعیت در گالری', icon: 'download-outline', run: downloadStatusCard },
      { text: 'انتشار مستقیم در استوری تلگرام', icon: 'sparkles-outline', run: shareToStory },
      { text: 'ارسال پیام متنی به گروه یا دوستان', icon: 'send-outline', run: switchInlineShare }
    ]);
  }
  function openWrappedMenu() {
    showNativeSheet('خلاصه ترم و آمار من', 'مرور تعداد هفته‌های زوج و فرد این ترم یا آمار استفاده شما از برنامه', [
      { text: 'خلاصه وضعیت کل ترم', icon: 'analytics-outline', run: function () { showWrapped('term'); } },
      { text: 'آمار بازدیدهای من', icon: 'person-circle-outline', run: function () { showWrapped('personal'); } }
    ]);
  }
  function openToolsMenu() {
    showNativeSheet('تقویم موبایل و میانبر', 'می‌توانید تمام هفته‌های زوج و فرد این ترم را به تقویم گوشی خود اضافه کنید.', [
      { text: 'افزودن تقویم کامل ترم به گوشی', icon: 'calendar-outline', run: downloadCalendar },
      { text: 'افزودن آیکون برنامه به صفحه اصلی گوشی', icon: 'phone-portrait-outline', run: function () { maybeSuggestShortcut(true); } },
      { text: 'مشاهده وضعیت امروز', icon: 'today-outline', run: checkToday }
    ]);
  }
  async function sendTestReminder() {
    haptic('impact', 'medium');
    showToast('در حال ارسال پیام آزمایشی به تلگرام شما...', 3000, { loading: true });
    try {
      const user = TG && TG.initDataUnsafe && TG.initDataUnsafe.user ? TG.initDataUnsafe.user : null;
      const res = await api('test_reminder', { user_id: user && user.id ? user.id : '' });
      if (res && res.status === 'success') {
        haptic('notify', 'success');
        showToast(res.message || 'پیام آزمایشی به تلگرام شما ارسال شد ✨', 3500, { type: 'success' });
      } else {
        haptic('notify', 'warning');
        alertNative((res && res.message) || 'ارسال پیام با خطا مواجه شد. لطفاً ابتدا در ربات دکمه شروع (Start) را بزنید.');
      }
    } catch (err) {
      console.debug('[AzadWeekNative] test_reminder failed', err);
      showToast('خطا در ارتباط با سرور. دوباره تلاش کنید.');
    }
  }

  function toggleWeeklyReminder() {
    const current = STATE.securePrefs.reminder !== false;
    const nextState = !current;
    STATE.securePrefs.reminder = nextState;
    STATE.securePrefs.reminderUpdatedAt = Date.now();
    savePreferences();
    api('save_preferences', { preferences: JSON.stringify(STATE.securePrefs) }).catch(() => {});
    if (nextState && TG && TG.requestWriteAccess) {
      try { TG.requestWriteAccess(() => {}); } catch (_) {}
    }
    haptic('notify', nextState ? 'success' : 'light');
    showToast(nextState ? 'یادآور هفتگی در تلگرام فعال شد (جمعه‌ها ساعت ۲۲:۰۰)' : 'یادآور هفتگی در تلگرام غیرفعال شد.', 3000, { type: nextState ? 'success' : '' });
    const openSheet = document.querySelector('.bottom-sheet-overlay.show');
    if (openSheet) {
      openSheet.click();
      setTimeout(openPrefsMenu, 260);
    }
  }

  function openPrefsMenu() {
    const reminderOn = STATE.securePrefs.reminder !== false;
    const shakeOn = STATE.devicePrefs.shake !== false;
    const desc = 'وضعیت یادآور تلگرام: ' + (reminderOn ? 'فعال (جمعه‌ها ساعت ۲۲:۰۰)' : 'غیرفعال') + ' | بروزرسانی با تکان: ' + (shakeOn ? 'فعال' : 'غیرفعال');
    showNativeSheet('یادآور و تنظیمات', desc, [
      { text: reminderOn ? 'غیرفعال‌سازی یادآور هفتگی' : 'فعال‌سازی یادآور هفتگی در تلگرام', icon: reminderOn ? 'notifications-off-outline' : 'notifications-outline', run: toggleWeeklyReminder },
      { text: 'ارسال نمونه پیام یادآور به پیوی من', icon: 'paper-plane-outline', run: sendTestReminder },
      { text: shakeOn ? 'خاموش کردن بروزرسانی با تکان گوشی' : 'روشن کردن بروزرسانی با تکان گوشی', icon: 'phone-landscape-outline', run: function () {
        STATE.devicePrefs.shake = !shakeOn;
        savePreferences();
        alertNative(STATE.devicePrefs.shake === false ? 'بروزرسانی با تکان دادن گوشی خاموش شد.' : 'بروزرسانی با تکان دادن گوشی روشن شد.');
      }},
      { text: 'بازنشانی تنظیمات برنامه', icon: 'trash-outline', danger: true, run: clearAllStorage }
    ]);
  }

  function clearAllStorage() {
    confirmNative('تنظیمات و اطلاعات ذخیره‌شده در این گوشی پاک شود؟', function (ok) {
      if (!ok) return;
      try { localStorage.removeItem(CONFIG.storageKeys.cloudLast); localStorage.removeItem(CONFIG.storageKeys.cloudDate); localStorage.removeItem(CONFIG.storageKeys.securePrefs); localStorage.removeItem(CONFIG.storageKeys.devicePrefs); localStorage.removeItem(CONFIG.storageKeys.wrappedStats); } catch (_) {}
      try { TG && TG.CloudStorage && TG.CloudStorage.removeItems([CONFIG.storageKeys.cloudLast, CONFIG.storageKeys.cloudDate, CONFIG.storageKeys.cloudSettings]); } catch (_) {}
      try { TG && TG.DeviceStorage && TG.DeviceStorage.removeItem(CONFIG.storageKeys.devicePrefs); TG && TG.DeviceStorage && TG.DeviceStorage.removeItem(CONFIG.storageKeys.wrappedStats); } catch (_) {}
      try { TG && TG.SecureStorage && TG.SecureStorage.removeItem(CONFIG.storageKeys.securePrefs); } catch (_) {}
      STATE.securePrefs = {}; STATE.devicePrefs = {};
      haptic('notify', 'success');
    });
  }
  function handleStartParam() {
    const direct = new URLSearchParams(window.location.search).get('tgWebAppStartParam') || new URLSearchParams(window.location.search).get('date');
    const param = direct || (TG && TG.initDataUnsafe && TG.initDataUnsafe.start_param) || '';
    const date = extractDate(param);
    if (date) setTimeout(() => applyDateFromExternal(date), 600);
  }
  function wrapOriginalFunctions() {
    if (typeof window.fetchData === 'function' && !window.fetchData.__azadWrapped) {
      const original = window.fetchData;
      window.fetchData = function (action, body) {
        if (!body) body = new FormData();
        try {
          if (TG && TG.initData && !body.has('initData')) body.append('initData', TG.initData);
          if (TG && TG.platform && !body.has('tgPlatform')) body.append('tgPlatform', TG.platform);
        } catch (_) {}
        const p = original.call(this, action, body);
        if (p && p.then) {
          p.then(function (data) {
            if (action === 'init' || !action) onHomeData(data);
            if (action === 'check_date') onCheckResult(data);
          }).catch(function () {});
        }
        return p;
      };
      window.fetchData.__azadWrapped = true;
    }
    if (typeof window.switchView === 'function' && !window.switchView.__azadWrapped) {
      const original = window.switchView;
      window.switchView = function (viewName, index) {
        const ret = original.call(this, viewName, index);
        setView(viewName);
        return ret;
      };
      window.switchView.__azadWrapped = true;
    }
    if (typeof window.openCalendar === 'function' && !window.openCalendar.__azadWrapped) {
      const original = window.openCalendar;
      window.openCalendar = function () { const ret = original.call(this); syncSheetMode(); updateBackButton(); return ret; };
      window.openCalendar.__azadWrapped = true;
    }
    if (typeof window.closeCalendar === 'function' && !window.closeCalendar.__azadWrapped) {
      const original = window.closeCalendar;
      window.closeCalendar = function (e) { const ret = original.call(this, e); setTimeout(() => { syncSheetMode(); updateBackButton(); }, 60); return ret; };
      window.closeCalendar.__azadWrapped = true;
    }
    if (typeof window.confirmDate === 'function' && !window.confirmDate.__azadWrapped) {
      const original = window.confirmDate;
      window.confirmDate = function () { const ret = original.call(this); setTimeout(() => { syncSheetMode(); updateBackButton(); }, 60); return ret; };
      window.confirmDate.__azadWrapped = true;
    }
  }
  function initNative() {
    if (window.isTelegramAccessAllowed && !window.isTelegramAccessAllowed()) {
      hideTelegramBottomButtons();
      return;
    }
    loadPreferences();
    recordWrappedEvent('open');
    applySafeArea();
    applyTelegramTheme();
    if (TG) {
      tgCall('ready');
      tgCall('expand');
      if (TG.onEvent) {
        TG.onEvent('themeChanged', applyTelegramTheme);
        TG.onEvent('safeAreaChanged', applySafeArea);
        TG.onEvent('contentSafeAreaChanged', applySafeArea);
        TG.onEvent('fullscreenChanged', applySafeArea);
        TG.onEvent('activated', function () { if (STATE.mobileFullscreen) enterMobileFullscreen(); });
      }
      enterMobileFullscreen();
      if (TG.BackButton && TG.BackButton.onClick) TG.BackButton.onClick(goBack);
      if (TG.SettingsButton) {
        try { TG.SettingsButton.show(); TG.SettingsButton.onClick(openSettingsMenu); } catch (_) {}
      }
      bindBottomButton(TG.MainButton, mainButtonAction);
      bindBottomButton(TG.SecondaryButton, secondaryButtonAction);
      updateNativeButtons();
      maybeSuggestShortcut(false);
    }
    startMotionRefresh();
    handleStartParam();
    wrapOriginalFunctions();
    setView('home');
    document.addEventListener('click', function (e) {
      if (e.target.closest('button, .search-box, .nav-item, .cal-day, .close-btn')) haptic('impact', 'light');
    }, true);
  }

  APP.openStatusCardPreview = openStatusCardPreview;
  APP.downloadStatusCard = downloadStatusCard;
  APP.shareVisualCard = shareVisualCard;
  APP.shareToStory = shareToStory;
  APP.downloadCalendar = downloadCalendar;
  APP.requestReminderAccess = requestReminderAccess;
  APP.openSettingsMenu = openSettingsMenu;
  APP.showWrapped = showWrapped;
  APP.showWrappedTerm = function () { return showWrapped('term'); };
  APP.showMyWrapped = function () { return showWrapped('personal'); };
  APP.onHomeData = onHomeData;
  APP.onCheckResult = onCheckResult;
  APP.setView = setView;
  APP.applyDateFromExternal = applyDateFromExternal;
  APP.isMobileClient = isMobileClient;
  window.AzadWeekNative = APP;

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initNative);
  else initNative();
})();
