// generate-qr.js
const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

(async () => {
	// ===== ورودی‌ها =====
	const qrUrl = process.argv[2];
	const outputPath = process.argv[3] || 'output.png';
	const logoPath = process.argv[4];
	const bgPath = path.resolve(__dirname, 'bg.jpg');

	if (!qrUrl || !logoPath) {
		console.error('❌ استفاده صحیح: node generate-qr.js <qr_url> <output_path> <logo_path>');
		process.exit(1);
	}

	// ===== آماده‌سازی دیتا =====
	const logoBase64 = fs.readFileSync(logoPath, { encoding: 'base64' });
	const bgBase64 = fs.readFileSync(bgPath, { encoding: 'base64' });
	const logoDataUri = `data:image/png;base64,${logoBase64}`;
	const bgDataUri = `data:image/jpeg;base64,${bgBase64}`;

	// ===== مسیرهای رانتایم قابل‌نوشتن =====
	const baseRuntime = process.env.PPTR_RUNTIME_DIR || '/var/cache/puppeteer';
	const runDir     = path.join(baseRuntime, 'run');
	const tmpDir     = path.join(baseRuntime, 'tmp');
	const profiles   = path.join(baseRuntime, 'profiles');

	[baseRuntime, runDir, tmpDir, profiles].forEach((p) => {
		try { fs.mkdirSync(p, { recursive: true }); } catch {}
	});

	// پروفایل یکتای موقت برای هر اجرا (حل مشکل ProcessSingleton)
	const tmpProfile = fs.mkdtempSync(path.join(profiles, 'profile-'));

	// اگر php-fpm HOME/XDG/TMP ندارد، ست می‌کنیم
	process.env.HOME            = process.env.HOME || baseRuntime;
	process.env.XDG_RUNTIME_DIR = process.env.XDG_RUNTIME_DIR || runDir;
	process.env.TMPDIR          = process.env.TMPDIR || tmpDir;

	// ===== لاگ‌های صفحه برای دیباگ =====
	const log = (...a) => console.log(...a);
	const err = (...a) => console.error(...a);

	let browser;
	try {
		// مسیر کروم: از ENV یا مسیر پیش‌فرض
		const execPath = process.env.PUPPETEER_EXECUTABLE_PATH
			|| '/var/cache/puppeteer/chrome/linux-139.0.7258.66/chrome-linux64/chrome';

		// ===== Launch Puppeteer =====
		browser = await puppeteer.launch({
			executablePath: execPath,
			headless: 'new',
			userDataDir: tmpProfile,
			args: [
				`--user-data-dir=${tmpProfile}`,
				'--no-sandbox',
				'--disable-setuid-sandbox',
				'--disable-dev-shm-usage',
				'--no-zygote',
				'--single-process',
				'--disable-gpu',
				'--disable-crashpad',
				'--disable-breakpad',
				'--no-first-run',
				'--no-default-browser-check'
			]
		});

		const page = await browser.newPage();

		page.on('console', (m) => log('[page.console]', m.type(), m.text()));
		page.on('pageerror', (e) => err('[page.error]', e));
		page.on('requestfailed', (r) => err('[request.failed]', r.url(), r.failure()?.errorText));

		// ===== HTML =====
		await page.setContent(`
      <html>
      <head><meta charset="utf-8" /></head>
      <style>
        html,body{margin:0;padding:0}
        body{font-family:Arial, sans-serif;}
        .qr-container{
          width:40rem; min-height:40rem;
          background-image:url('${bgDataUri}');
          background-size:cover; display:flex;
          justify-content:center; align-items:center; padding:2rem;
        }
        #qr{margin:0 auto; border-radius:0.5rem; overflow:hidden;}
        #qr canvas{border-radius:10px; overflow:hidden;}
      </style>
      <body>
        <div class="qr-container"><div id="qr"></div></div>
      </body>
      </html>
    `);

		// ===== تزریق محلی لایبرری (بدون CDN) =====
		await page.addScriptTag({
			path: require.resolve('qr-code-styling/lib/qr-code-styling.js')
		});

		// ===== ساخت QR =====
		await page.addScriptTag({
			content: `
        const qrCode = new QRCodeStyling({
          width: 500,
          height: 500,
          type: 'canvas',
          data: ${JSON.stringify(qrUrl)},
          margin: 10,
          qrOptions: { errorCorrectionLevel: 'H' },
          imageOptions: { hideBackgroundDots: true, imageSize: 0.4, margin: 5 },
          dotsOptions: { color: '#000000', type: 'rounded' },
          backgroundOptions: { color: '#ffffff' },
          image: ${JSON.stringify(logoDataUri)},
          cornersSquareOptions: { type: 'extra-rounded', color: '#000000' },
          cornersDotOptions: { type: 'dot', color: '#000000' }
        });
        qrCode.append(document.getElementById('qr'));
      `
		});

		// صبر تا canvas ساخته شود
		await page.waitForSelector('#qr canvas', { timeout: 20000 });

		// اسکرین‌شات از کارت
		const element = await page.$('.qr-container');
		await element.screenshot({ path: outputPath });

	} catch (e) {
		err('Puppeteer error:', e);
		process.exit(1);
	} finally {
		try { if (browser) await browser.close(); } catch {}
		// پاک‌سازی پروفایل موقت
		try { fs.rmSync(tmpProfile, { recursive: true, force: true }); } catch {}
	}
})();
