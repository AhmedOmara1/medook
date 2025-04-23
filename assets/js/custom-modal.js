/**
 * Custom Modal Enhancements
 * This script improves modal behavior and ensures scrolling works properly
 */

document.addEventListener('DOMContentLoaded', function() {
    // Fix for Bootstrap modals to ensure they're scrollable
    const fixModals = function() {
        // Find all modals in the document
        document.querySelectorAll('.modal').forEach(function(modal) {
            // Add scrollable class if not present
            if (!modal.querySelector('.modal-dialog').classList.contains('modal-dialog-scrollable')) {
                modal.querySelector('.modal-dialog').classList.add('modal-dialog-scrollable');
            }
            
            // Make sure modals are centered
            if (!modal.querySelector('.modal-dialog').classList.contains('modal-dialog-centered')) {
                modal.querySelector('.modal-dialog').classList.add('modal-dialog-centered');
            }
            
            // Add event listener to ensure body content is visible
            modal.addEventListener('shown.bs.modal', function() {
                // Force recalculation of modal body max-height
                const modalBody = this.querySelector('.modal-body');
                if (modalBody) {
                    const modalHeader = this.querySelector('.modal-header');
                    const modalFooter = this.querySelector('.modal-footer');
                    const headerHeight = modalHeader ? modalHeader.offsetHeight : 0;
                    const footerHeight = modalFooter ? modalFooter.offsetHeight : 0;
                    
                    // Calculate available height for modal body
                    const windowHeight = window.innerHeight;
                    const maxHeight = windowHeight - headerHeight - footerHeight - 40; // 40px for padding
                    
                    // Apply max height to ensure scrolling works properly
                    modalBody.style.maxHeight = maxHeight + 'px';
                    modalBody.style.overflowY = 'auto';
                }
            });
        });
    };
    
    // Run the fix after a slight delay to ensure the DOM is fully processed
    setTimeout(fixModals, 500);
    
    // Also run the fix when DOM changes (for dynamically loaded modals)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                fixModals();
            }
        });
    });
    
    // Start observing the document body for changes
    observer.observe(document.body, { childList: true, subtree: true });
}); 