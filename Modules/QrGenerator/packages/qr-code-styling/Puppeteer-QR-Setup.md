# راهنمای آماده‌سازی Puppeteer + Chrome برای QR (Production)

این راهنما و اسکریپت، محیط اجرای پایدار **Puppeteer** با **Chrome headless** را برای کاربر وب (`www-data`) آماده می‌کند تا مشکلاتی مثل `Could not find Chrome`, `chrome_crashpad_handler: --database is required`, و `ProcessSingleton` پیش نیاید.

> تست‌شده با: Node.js 20، Debian/Ubuntu، php-fpm (`www-data`)

---

## 1) اجرای اسکریپت آماده‌سازی (یک‌بار برای هر سرور)

```bash
sudo bash setup-puppeteer.sh
```

پارامترها (اختیاری):
```bash
WEB_USER=www-data RUNTIME_DIR=/var/cache/puppeteer sudo bash setup-puppeteer.sh
```

اسکریپت:
- دایرکتوری‌های قابل‌نوشتن برای کروم/پاپیتر می‌سازد: `/var/cache/puppeteer/profiles`, `/var/cache/puppeteer/run`, `/var/cache/puppeteer/tmp`
- کروم باندل‌شدهٔ Puppeteer را برای **کاربر وب** نصب می‌کند (`npx puppeteer browsers install chrome`)
- یک **Symlink پایدار** می‌سازد: `/var/cache/puppeteer/chrome/current` → به باینری آخرین کروم نصب‌شده
- پیشنهادهای `.env` و تست سریع CLI را چاپ می‌کند

> نیازمندی: Node.js + npm (`npx`).

---

## 2) تنظیمات `.env` در لاراول

در فایل `.env` (مقادیر پیش‌فرض براساس اسکریپت بالا):

```env
PUPPETEER_EXECUTABLE_PATH=/var/cache/puppeteer/chrome/current
PUPPETEER_CACHE_DIR=/var/cache/puppeteer
PPTR_RUNTIME_DIR=/var/cache/puppeteer
```

> اگر مسیرها را عوض کردید، این سه متغیر را مطابق همان مسیرها به‌روزرسانی کنید.

---

## 3) کد سرویس PHP (ارسال ENVها به Process)

```php
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

$chromePath = env('PUPPETEER_EXECUTABLE_PATH', '/var/cache/puppeteer/chrome/current');
$runtimeDir = env('PPTR_RUNTIME_DIR', '/var/cache/puppeteer');

$env = [
    'PUPPETEER_EXECUTABLE_PATH' => $chromePath,
    'PUPPETEER_CACHE_DIR'       => env('PUPPETEER_CACHE_DIR', $runtimeDir),
    'PPTR_RUNTIME_DIR'          => $runtimeDir,
    'HOME'                      => $runtimeDir,
    'XDG_RUNTIME_DIR'           => $runtimeDir . '/run',
    'TMPDIR'                    => $runtimeDir . '/tmp',
];

$process = new Process([$nodePath, $scriptPath, $url, $outputPath, $logoPath], null, $env);
$process->setTimeout(60);
$process->run();
```

> **نکته:** حتماً فولدر خروجی لاراول (storage/public/qr-codes/...) برای `www-data` قابل‌نوشتن باشد.

---

## 4) اسکریپت Node (`generate-qr.js`) با پروفایل موقّت + غیرفعال‌سازی Crashpad

> مسیر: `Modules/QrGenerator/packages/qr-code-styling/generate-qr.js`

```js
const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

(async () => {
  const qrUrl = process.argv[2];
  const outputPath = process.argv[3] || 'output.png';
  const logoPath = process.argv[4];
  const bgPath = path.resolve(__dirname, 'bg.jpg');
  if (!qrUrl || !logoPath) { console.error('Usage: node generate-qr.js <qr_url> <output_path> <logo_path>'); process.exit(1); }

  const logoBase64 = fs.readFileSync(logoPath, { encoding: 'base64' });
  const bgBase64 = fs.readFileSync(bgPath, { encoding: 'base64' });
  const logoDataUri = `data:image/png;base64,${logoBase64}`;
  const bgDataUri = `data:image/jpeg;base64,${bgBase64}`;

  const baseRuntime = process.env.PPTR_RUNTIME_DIR || '/var/cache/puppeteer';
  const runDir = path.join(baseRuntime, 'run');
  const tmpDir = path.join(baseRuntime, 'tmp');
  const profiles = path.join(baseRuntime, 'profiles');
  [baseRuntime, runDir, tmpDir, profiles].forEach(p => { try { fs.mkdirSync(p, { recursive: true }); } catch {} });

  const tmpProfile = fs.mkdtempSync(path.join(profiles, 'profile-'));
  process.env.HOME = process.env.HOME || baseRuntime;
  process.env.XDG_RUNTIME_DIR = process.env.XDG_RUNTIME_DIR || runDir;
  process.env.TMPDIR = process.env.TMPDIR || tmpDir;

  let browser;
  try {
    const execPath = process.env.PUPPETEER_EXECUTABLE_PATH || '/var/cache/puppeteer/chrome/current';
    browser = await puppeteer.launch({
      executablePath: execPath,
      headless: 'new',
      userDataDir: tmpProfile,
      args: [
        `--user-data-dir=${tmpProfile}`,
        '--no-sandbox','--disable-setuid-sandbox','--disable-dev-shm-usage',
        '--no-zygote','--single-process','--disable-gpu',
        '--disable-crashpad','--disable-breakpad','--no-first-run','--no-default-browser-check'
      ]
    });

    const page = await browser.newPage();
    page.on('console', m => console.log('[page.console]', m.type(), m.text()));
    page.on('pageerror', e => console.error('[page.error]', e));
    page.on('requestfailed', r => console.error('[request.failed]', r.url(), r.failure()?.errorText));

    await page.setContent(`
      <html><head><meta charset="utf-8"/></head>
      <style>
        html,body{margin:0;padding:0} body{font-family:Arial,sans-serif;}
        .qr-container{width:40rem;min-height:40rem;background-image:url('__BG__');background-size:cover;display:flex;justify-content:center;align-items:center;padding:2rem;}
        #qr{margin:0 auto;border-radius:.5rem;overflow:hidden;} #qr canvas{border-radius:10px;overflow:hidden;}
      </style>
      <body><div class="qr-container"><div id="qr"></div></div></body></html>
    `.replace('__BG__', bgDataUri));

    // Inject library locally (no CDN)
    await page.addScriptTag({ path: require.resolve('qr-code-styling/lib/qr-code-styling.js') });
    await page.addScriptTag({ content: `
      const qrCode = new QRCodeStyling({
        width: 500, height: 500, type: 'canvas',
        data: __DATA__,
        margin: 10,
        qrOptions: { errorCorrectionLevel: 'H' },
        imageOptions: { hideBackgroundDots: true, imageSize: 0.4, margin: 5 },
        dotsOptions: { color: '#000000', type: 'rounded' },
        backgroundOptions: { color: '#ffffff' },
        image: __LOGO__,
        cornersSquareOptions: { type: 'extra-rounded', color: '#000000' },
        cornersDotOptions: { type: 'dot', color: '#000000' }
      });
      qrCode.append(document.getElementById('qr'));
    `.replace('__DATA__', JSON.stringify(qrUrl)).replace('__LOGO__', JSON.stringify(logoDataUri)) });

    await page.waitForSelector('#qr canvas', { timeout: 20000 });
    const el = await page.$('.qr-container');
    await el.screenshot({ path: outputPath });
  } catch (e) {
    console.error('Puppeteer error:', e);
    process.exit(1);
  } finally {
    try { if (browser) await browser.close(); } catch {}
    try { fs.rmSync(tmpProfile, { recursive: true, force: true }); } catch {}
  }
})();
```

> **مهم:** لایبرری `qr-code-styling` را محلی inject می‌کنیم (بدون CDN).

---

## 5) تست سریع CLI

```bash
# مسیر باینری کروم پایدار
CHROME=/var/cache/puppeteer/chrome/current

# تست لانچ
sudo -u www-data -H bash -lc '
PUPPETEER_EXECUTABLE_PATH="$CHROME" HOME="/var/cache/puppeteer" XDG_RUNTIME_DIR="/var/cache/puppeteer/run" TMPDIR="/var/cache/puppeteer/tmp" node -e "const p=require("puppeteer");(async()=>{const b=await p.launch({executablePath:process.env.PUPPETEER_EXECUTABLE_PATH,headless:"new",userDataDir:"/var/cache/puppeteer/profiles/test",args:["--user-data-dir=/var/cache/puppeteer/profiles/test","--no-sandbox","--disable-setuid-sandbox","--disable-dev-shm-usage","--no-zygote","--single-process","--disable-gpu","--disable-crashpad","--disable-breakpad","--no-first-run","--no-default-browser-check"]});console.log("launched");await b.close();})().catch(e=>{console.error(e);process.exit(1);});"
'
```

---

## 6) خطاهای رایج و راه‌حل سریع

- **Could not find Chrome (ver …)** → Chrome را برای کاربر وب نصب کن و `PUPPETEER_EXECUTABLE_PATH` بده (این راهنما).
- **chrome_crashpad_handler: --database is required** → `HOME/XDG_RUNTIME_DIR/TMPDIR` را به مسیر قابل‌نوشتن بده و `--disable-crashpad` بزن.
- **Failed to create socket directory / ProcessSingleton** → از **پروفایل یکتای موقت** (`mkdtemp`) استفاده کن؛ بعد از کار پاک کن.
- **Timeout روی `#qr canvas`** → لایبرری `qr-code-styling` را **محلی** تزریق کن (نه CDN)، زمان انتظار را بالا ببر.

---

## 7) نکات دیپلوی
- همیشه Process را به‌صورت کاربر سرویس وب اجرا کن (`www-data` در Debian/Ubuntu).
- به‌صورت موازی مشکلی نیست؛ هر اجرا پروفایل خودش را می‌سازد و حذف می‌کند.
- مسیرهای `storage` لاراول باید برای کاربر وب قابل‌نوشتن باشد.

موفق باشید. :)
