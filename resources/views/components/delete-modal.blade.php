<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<form method="POST" id="deleteForm-{{ $modalId }}" class="w-100">
			@csrf
			@method('DELETE')

			<div class="modal-content border-0 shadow-lg rounded-4">
				<div class="modal-body text-center p-5">
					<div class="mb-4">
						<div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle" style="width: 64px; height: 64px;">
							<i class="bx bx-trash fs-1"></i>
						</div>
					</div>

					<h4 class="fw-bold mb-3">{{ $title }}</h4>

					<p class="text-muted mb-4">
						{{ $message ?? tr_helper('contents', 'ThisActionCannotBeUndone') }}
					</p>

					<p class="fw-semibold fs-6 text-dark mb-4 item-name-placeholder">
						<!-- Filled by JS -->
					</p>

					<div class="d-flex justify-content-center gap-2">
						<button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
							<i class="bx bx-x me-1"></i> {{ tr_helper('contents', 'Cancel') }}
						</button>

						<button type="submit" class="btn btn-danger px-4">
							<i class="bx bx-trash me-1"></i> {{ tr_helper('contents', 'DeleteNow') }}
						</button>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function () {
		// Find all delete buttons
		document.querySelectorAll('.open-delete-modal').forEach(button => {
			button.addEventListener('click', function() {
				const modalId = this.getAttribute('data-bs-target').substring(1); // Remove the #
				const form = document.getElementById(`deleteForm-${modalId}`);
				const namePlaceholder = document.querySelector(`${this.getAttribute('data-bs-target')} .item-name-placeholder`);

				if (form && namePlaceholder) {
					const itemName = this.getAttribute('data-item-name');
					const deleteRoute = this.getAttribute('data-delete-route');

					form.setAttribute('action', deleteRoute);
					namePlaceholder.textContent = `« ${itemName} »`;
				}
			});
		});
	});
</script>
