function showToast(type, message) {
	const container = document.getElementById('toast-container');

	if (!container) return;

	const toastId = 'toast-' + Math.floor(Math.random() * 10000);
	const toastHTML = `
		<div id="${toastId}" class="toast align-items-center text-bg-${type} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
			<div class="d-flex">
				<div class="toast-body">${message}</div>
				<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
			</div>
		</div>
	`;

	container.insertAdjacentHTML('beforeend', toastHTML);

	const toastEl = document.getElementById(toastId);
	const bsToast = new bootstrap.Toast(toastEl);
	bsToast.show();

	// Remove toast element after it hides
	toastEl.addEventListener('hidden.bs.toast', () => {
		toastEl.remove();
	});
}
document.addEventListener('DOMContentLoaded', function() {
	const alerts = [
		document.getElementById('error-alert'),
		document.getElementById('session-error-alert'),
		document.getElementById('success-alert')
	].filter(alert => alert !== null);

	alerts.forEach(alert => {
		setTimeout(() => {
			alert.classList.add('fade-out');
			setTimeout(() => {
				alert.remove();
			}, 500); // Match CSS transition duration
		}, 5000); // 5 seconds delay
	});
});
