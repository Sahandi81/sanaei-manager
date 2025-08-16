<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document" style="margin: 14vh auto 0 auto;">
		<div class="modal-content overflow-visible">
			<div class="modal-header text-center">
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body text-center">
				<div class="mb-3">
					<img id="modalQrImage" src="" class="img-fluid rounded border cursor-pointer" alt="QR Code" style="max-height: 40rem;">
				</div>
				<div class="input-group mb-3">
					<input type="text" class="form-control" id="subsLink" readonly>
					<button class="btn btn-outline-primary" type="button" id="copySubsBtn">
						<i class="bx bx-copy"></i>
					</button>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
					{{ tr_helper('contents', 'Close') }}
				</button>

				<button type="button" class="btn btn-label-danger disabled">
					{{ tr_helper('contents', 'ReGenerateQrCode') }}
				</button>
			</div>
		</div>
	</div>
</div>
<style>
	#qrModal .modal-content {
		border-radius: 0.5rem;
		overflow: hidden;
		box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
	}

	#qrModal .modal-header {
		background-color: #7367f0;
		color: white;
		border-bottom: none;
	}

	#qrModal .modal-body {
		padding: 2rem;
	}

	#qrModal .btn-close {
		filter: invert(1);
	}

	#modalQrImage {
		border: 1px solid #e9ecef;
		padding: 0.5rem;
	}

	#copySubsBtn, #modalQrImage {
		transition: all 0.3s ease;
	}
</style>
<script>
	document.addEventListener('DOMContentLoaded', function () {
		// QR Modal handling
		const qrModal = document.getElementById('qrModal');
		if (qrModal) {
			qrModal.addEventListener('show.bs.modal', function (event) {
				const button = event.relatedTarget;
				const qrUrl = button.getAttribute('data-qr-url');
				const subs = button.getAttribute('data-subs');

				const modalImage = qrModal.querySelector('#modalQrImage');
				const subsInput = qrModal.querySelector('#subsLink');

				modalImage.src = qrUrl;
				subsInput.value = subs;
			});
		}

		// Copy functionality
		const copySubsBtn = document.getElementById('copySubsBtn');
		if (copySubsBtn) {
			copySubsBtn.addEventListener('click', function () {
				const subsInput = document.getElementById('subsLink');
				subsInput.select();
				document.execCommand('copy');

				// Change button appearance temporarily
				const originalHTML = copySubsBtn.innerHTML;
				copySubsBtn.innerHTML = '<i class="bx bx-check"></i>';
				copySubsBtn.classList.remove('btn-outline-primary');
				copySubsBtn.classList.add('btn-success');

				setTimeout(function() {
					copySubsBtn.innerHTML = originalHTML;
					copySubsBtn.classList.remove('btn-success');
					copySubsBtn.classList.add('btn-outline-primary');
				}, 2000);
			});
		}
		const modalQrImage = document.getElementById('modalQrImage');
		if (modalQrImage) {
			modalQrImage.addEventListener('click', function () {
				const subsInput = document.getElementById('subsLink');
				subsInput.select();
				document.execCommand('copy');

				// Change button appearance temporarily
				const originalHTML = modalQrImage.innerHTML;
				modalQrImage.innerHTML = '<i class="bx bx-check"></i>';
				modalQrImage.classList.add('btn-success');
				copySubsBtn.classList.remove('btn-outline-primary');
				copySubsBtn.classList.add('btn-success');

				setTimeout(function() {
					modalQrImage.innerHTML = originalHTML;
					modalQrImage.classList.remove('btn-success');
					copySubsBtn.classList.remove('btn-success');
					copySubsBtn.classList.add('btn-outline-primary');
				}, 2000);
			});
		}
	});
</script>
