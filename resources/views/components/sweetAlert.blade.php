<div class="swal2-container swal2-center swal2-backdrop-show d-none" id="SweetAlertDelete" style="overflow-y: auto;">
    <div aria-labelledby="swal2-title" aria-describedby="swal2-html-container"
         class="swal2-popup swal2-modal swal2-icon-warning swal2-show" tabindex="-1" role="dialog" aria-live="assertive"
         aria-modal="true" style="display: grid;">
        <button type="button" class="swal2-close" aria-label="Close this dialog" style="display: none;">×</button>
        <ul class="swal2-progress-steps" style="display: none;"></ul>
        <div class="swal2-icon swal2-warning swal2-icon-show" style="display: flex;">
            <div class="swal2-icon-content">!</div>
        </div>
        <img class="swal2-image" style="display: none;">
        <h2 class="swal2-title" id="swal2-title" style="display: block;">{{ tr_helper('contents', 'ARE_YOU_SURE') }}</h2>
        <div class="swal2-html-container" id="swal2-html-container" style="display: block;">
        </div>
        <input class="swal2-input" style="display: none;"><input type="file" class="swal2-file" style="display: none;">
        <div class="swal2-range" style="display: none;"><input type="range">
            <output></output>
        </div>
        <select class="swal2-select" style="display: none;"></select>
        <div class="swal2-radio" style="display: none;"></div>
        <label for="swal2-checkbox" class="swal2-checkbox" style="display: none;"><input type="checkbox"><span
                    class="swal2-label"></span></label><textarea class="swal2-textarea"
                                                                 style="display: none;"></textarea>
        <div class="swal2-validation-message" id="swal2-validation-message" style="display: none;"></div>
        <div class="swal2-actions" style="display: flex;">
            <div class="swal2-loader"></div>
            <a href="#"  class="yes-btn swal2-confirm btn btn-primary me-3" aria-label=""
                    style="display: inline-block;">{{ tr_helper('contents', 'YES_DELETE_IT') }}
            </a>
            <button type="button" class="swal2-cancel btn btn-label-secondary" aria-label=""
                    onclick="cancelAlert('SweetAlertDelete')" style="display: inline-block;">{{ tr_helper('contents', 'CANCEL') }}
            </button>
        </div>
        <div class="swal2-footer" style="display: none;"></div>
        <div class="swal2-timer-progress-bar-container">
            <div class="swal2-timer-progress-bar" style="display: none;"></div>
        </div>
    </div>
</div>