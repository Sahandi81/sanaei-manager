const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

(async () => {
	// دریافت آرگومان‌ها
	const qrUrl = process.argv[2]; // لینک QR
	const outputPath = process.argv[3] || 'output.png'; // مسیر خروجی
	const logoPath = process.argv[4]; // مسیر لوگو (آرگومان سوم)
	const bgPath = path.resolve(__dirname, 'bg.jpg'); // بک‌گراند ثابت کنار اسکریپت

	if (!qrUrl || !logoPath) {
		console.error('❌ استفاده صحیح: node generate-qr.js <qr_url> <output_path> <logo_path>');
		process.exit(1);
	}

	// تبدیل به Base64
	const logoBase64 = fs.readFileSync(logoPath, { encoding: 'base64' });
	const bgBase64 = fs.readFileSync(bgPath, { encoding: 'base64' });

	// Data URI
	const logoDataUri = `data:image/png;base64,${logoBase64}`;
	const bgDataUri = `data:image/jpeg;base64,${bgBase64}`;

	const browser = await puppeteer.launch({ args: ['--no-sandbox'] });
	const page = await browser.newPage();

	await page.setContent(`
        <html>
        <style>
            body {
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
            }
            .qr-container {
                width: 40rem;
                min-height: 40rem;
                background-image: url('${bgDataUri}');
                background-size: cover;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 2rem;
            }
            #qr {
                margin: 0 auto;
                border-radius: 0.5rem;
                overflow: hidden;
            }
            #qr canvas {
                border-radius: 10px;
                overflow: hidden;
            }
        </style>
        <body>
            <div class="qr-container">
                <div id="qr"></div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/qr-code-styling/lib/qr-code-styling.js"></script>
            <script>
                const qrCode = new QRCodeStyling({
                    width: 500,
                    height: 500,
                    data: '${qrUrl}',
                    margin: 10,
                    qrOptions: { errorCorrectionLevel: 'H' },
                    imageOptions: { hideBackgroundDots: true, imageSize: 0.4, margin: 5 },
                    dotsOptions: { color: '#000000', type: 'rounded' },
                    backgroundOptions: { color: '#ffffff' },
                    image: '${logoDataUri}',
                    cornersSquareOptions: { type: 'extra-rounded', color: '#FFAA00' },
                    cornersDotOptions: { type: 'dot', color: '#000000' }
                });
                qrCode.append(document.getElementById('qr'));
            </script>
        </body>
        </html>
    `);

	await page.waitForSelector('#qr canvas', { timeout: 5000 });
	const element = await page.$('.qr-container');
	await element.screenshot({ path: outputPath });

	await browser.close();
})();
