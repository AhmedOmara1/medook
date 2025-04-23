<?php
/**
 * Admin animations disabling
 * This file disables all animations in admin area for improved performance
 */

// Add style to disable animations in admin area
echo '<style>
    * {
        animation-duration: 0s !important;
        transition-duration: 0s !important;
        animation: none !important;
        transition: none !important;
    }
    
    [data-aos] {
        opacity: 1 !important;
        transform: translate(0) scale(1) !important;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0,0,0,.075) !important;
    }
    
    .btn:hover {
        filter: brightness(90%) !important;
    }
    
    /* Modal fixes */
    .modal.fade,
    .modal-backdrop.fade,
    .modal.fade .modal-dialog {
        transition: none !important;
    }
    
    .modal-backdrop.fade.show {
        opacity: 0.5;
    }
    
    .modal.fade .modal-dialog {
        transform: none !important;
    }
</style>';
?> 