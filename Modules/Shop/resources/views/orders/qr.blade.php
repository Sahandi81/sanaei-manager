<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>QR Code Generator</title>
	<style>
		body {
			margin: 0;
			padding: 0;
			font-family: Arial, sans-serif;
		}

		.qr-container {
			width: 40rem;
			min-height: 40rem;
			background-image: url(/bg.jpg);
			background-size: cover;
			display: flex;
			justify-content: center;
			align-items: center;
			padding: 2rem;
		}


		#qr-code {
			margin: 0 auto;
			border-radius: 0.5rem;
			overflow: hidden;
		}

		#qr-code canvas {
			border-radius: 10px;
			overflow: hidden;
		}
	</style>
</head>
<body>
<div class="qr-container">
	<div id="qr-code"></div>
</div>
<script src="/assets/customJs/qr-code-styling.min.js"></script>
<script type="module">


	const qrUrl = "{{ $qrUrl }}";
	const logoUrl = '/logo.png';

	const qrCode = new window.QRCodeStyling({
		width: 500,
		height: 500,
		data: qrUrl,
		margin: 10,
		qrOptions: {
			typeNumber: 0,
			mode: 'Byte',
			errorCorrectionLevel: 'H' // High error correction
		},
		imageOptions: {
			hideBackgroundDots: true,
			imageSize: 0.4,
			margin: 5
		},
		dotsOptions: {
			color: '#000000',
			type: 'rounded' // می‌توانید به 'square', 'dots', 'rounded' تغییر دهید
		},
		backgroundOptions: {
			color: '#ffffff',
		},
		image: logoUrl,
		cornersSquareOptions: {
			type: 'extra-rounded',
			color: '#FFAA00',
		},
		cornersDotOptions: {
			type: 'dot',
			color: '#000000',
		}
	});

	// نمایش QR Code
	qrCode.append(document.getElementById('qr-code'));

</script>
</body>
</html>
