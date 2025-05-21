<?php
/**
 * Popup Component
 * 
 * This component provides a reusable popup/modal functionality that can be used throughout the application.
 * It integrates with validation.js for showing success/error messages.
 */
?>

<!-- Add Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div id="customPopup" class="popup">
    <div class="popup-content">
        <div class="popup-header">
            <h3 id="popupTitle"><i class="fas fa-info-circle"></i> Message</h3>
            <button id="popupCloseBtn" class="btn-close" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="popup-body">
            <div id="popupMessage"></div>
        </div>
        <div class="popup-footer">
            <button id="popupActionBtn" class="btn btn-primary">Close</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const popup = document.getElementById('customPopup');
    if (popup) {
        // Initialize popup as hidden
        popup.style.display = 'none';
    }
});
</script>
