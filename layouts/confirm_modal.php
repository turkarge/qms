<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}
?>

<div class="modal modal-blur fade" id="confirm-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="ti ti-alert-circle fs-1 text-danger"></i>
                </div>
                <h3 class="mb-2">Emin misiniz?</h3>
                <div class="text-secondary" id="confirm-modal-text">
                    Bu işlemi gerçekleştirmek istediğinizden emin misiniz?
                </div>
            </div>
            <div class="modal-footer">
                <div class="w-100 d-flex gap-2">
                    <button type="button" class="btn btn-secondary w-50" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="button" class="btn btn-danger w-50" id="confirm-modal-yes">Evet</button>
                </div>
            </div>
        </div>
    </div>
</div>