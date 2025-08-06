@props([
    'name' => 'files[]',
    'maxFiles' => 5,
    'maxSize' => 10, // MB
    'accept' => '*/*',
])

<div
	class="file-upload border rounded p-3"
	id="file-upload"
	data-name="{{ $name }}"
	data-max-files="{{ $maxFiles }}"
	data-max-size="{{ $maxSize }}"
	data-accept="{{ $accept }}"
>
	<div id="dropzone" class="dropzone text-center p-4 border border-dashed rounded cursor-pointer">
		<i class="ti ti-upload text-primary fs-2 mb-2"></i>
		<p class="mb-1">{{ tr_helper('contents', 'DropFilesOrClick') }}</p>
		<p id="error-message" class="text-danger small d-none"></p>
		<input
			type="file"
			class="d-none"
			id="file-input"
			multiple
			name="{{ $name }}"
			accept="{{ $accept }}"
		>
	</div>

	<!-- Hidden input to store file data -->
	<input type="hidden" name="uploaded_files_data" id="uploaded-files-data">

	<div class="file-preview row mt-3" id="file-preview"></div>
</div>

<script>
	document.addEventListener('DOMContentLoaded', () => {
		const wrapper = document.getElementById('file-upload');
		const dropzone = document.getElementById('dropzone');
		const fileInput = document.getElementById('file-input');
		const preview = document.getElementById('file-preview');
		const errorMessage = document.getElementById('error-message');
		const uploadedFilesData = document.getElementById('uploaded-files-data');

		const maxFiles = parseInt(wrapper.dataset.maxFiles);
		const maxSize = parseInt(wrapper.dataset.maxSize) * 1024 * 1024;

		let selectedFiles = [];
		let filesArray = []; // Array to hold our files

		dropzone.addEventListener('click', () => fileInput.click());

		fileInput.addEventListener('change', (e) => {
			handleFiles(e.target.files);
		});

		dropzone.addEventListener('dragover', (e) => {
			e.preventDefault();
			dropzone.classList.add('bg-light');
		});

		dropzone.addEventListener('dragleave', () => {
			dropzone.classList.remove('bg-light');
		});

		dropzone.addEventListener('drop', (e) => {
			e.preventDefault();
			dropzone.classList.remove('bg-light');
			handleFiles(e.dataTransfer.files);
		});

		function handleFiles(fileList) {
			const filesArr = Array.from(fileList);

			for (const file of filesArr) {
				if (filesArray.length >= maxFiles) {
					showError(`حداکثر ${maxFiles} فایل مجاز است.`);
					return;
				}

				if (file.size > maxSize) {
					showError(`حجم فایل "${file.name}" بیش از ${wrapper.dataset.maxSize}MB است.`);
					continue;
				}

				hideError();
				const id = Math.random().toString(36).substring(2);
				selectedFiles.push({ id, file });
				filesArray.push(file);

				// Update the file input with all files
				updateFileInput();

				// Update hidden input with file data
				updateHiddenInput();

				const reader = new FileReader();
				reader.onload = (e) => {
					const fileItem = document.createElement('div');
					fileItem.className = 'col-md-3 file-item mb-2';

					let content = `
                    <div class="border rounded p-2 position-relative">
                        <small class="d-block text-truncate">${file.name}</small>
                        <small class="text-muted">${formatSize(file.size)}</small>
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-1" aria-label="Remove" data-id="${id}"></button>
                    </div>
                `;

					// Image preview
					if (file.type.startsWith('image/')) {
						content = `
                        <div class="border rounded p-1 position-relative text-center">
                            <img src="${e.target.result}" class="img-fluid rounded mb-1" alt="${file.name}" style="max-height: 120px;">
                            <small class="d-block text-truncate">${file.name}</small>
                            <small class="text-muted">${formatSize(file.size)}</small>
                            <button type="button" class="btn-close position-absolute top-0 end-0 m-1" aria-label="Remove" data-id="${id}"></button>
                        </div>
                    `;
					}

					fileItem.innerHTML = content;
					preview.appendChild(fileItem);

					fileItem.querySelector('.btn-close').addEventListener('click', () => {
						// Remove file from selectedFiles
						selectedFiles = selectedFiles.filter(f => f.id !== id);

						// Remove file from filesArray
						filesArray = filesArray.filter(f => f !== file);

						// Update the file input
						updateFileInput();

						// Update hidden input
						updateHiddenInput();

						// Remove preview
						fileItem.remove();
					});
				};
				reader.readAsDataURL(file);
			}
		}

		function updateFileInput() {
			const dataTransfer = new DataTransfer();
			filesArray.forEach(file => dataTransfer.items.add(file));
			fileInput.files = dataTransfer.files;
		}

		function updateHiddenInput() {
			const filesData = selectedFiles.map(f => ({
				name: f.file.name,
				size: f.file.size,
				type: f.file.type
			}));
			uploadedFilesData.value = JSON.stringify(filesData);
		}

		function formatSize(bytes) {
			const sizes = ['Bytes', 'KB', 'MB'];
			if (bytes === 0) return '0 Bytes';
			const i = Math.floor(Math.log(bytes) / Math.log(1024));
			return parseFloat((bytes / Math.pow(1024, i)).toFixed(2) + ' ' + sizes[i]);
		}

		function showError(message) {
			errorMessage.textContent = message;
			errorMessage.classList.remove('d-none');
		}

		function hideError() {
			errorMessage.textContent = '';
			errorMessage.classList.add('d-none');
		}
	});
</script>

<style>
	.file-upload {
		border: 1px dashed #d9dee3;
		border-radius: 0.375rem;
		padding: 1.5rem;
	}

	.dropzone {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		min-height: 150px;
		cursor: pointer;
		transition: all 0.3s ease;
	}

	.dropzone.is-dragover {
		background-color: rgba(105, 108, 255, 0.08);
		border-color: #696cff;
	}

	.dz-message {
		text-align: center;
	}

	.dz-message i {
		font-size: 2rem;
		color: #696cff;
		margin-bottom: 1rem;
	}

	.file-preview {
		display: flex;
		flex-wrap: wrap;
		justify-content: space-around;
		gap: 0.5rem;
	}

	.file-item {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 0.5rem;
		border-radius: 0.375rem;
	}

	.file-info {
		display: flex;
		align-items: center;
		gap: 0.75rem;
	}

	.file-info i {
		font-size: 1.5rem;
		color: #696cff;
	}

	.file-details {
		display: flex;
		flex-direction: column;
	}

	.file-name {
		font-weight: 500;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		max-width: 200px;
	}

	.file-size {
		font-size: 0.75rem;
		color: #6c757d;
	}
</style>
