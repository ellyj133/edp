<?php
/**
 * Admin Footer Include - Required in all admin pages
 * Standardized admin page footer with scripts
 */
?>
            </div> <!-- End admin-content -->
        </div> <!-- End row -->
    </div> <!-- End container-fluid -->

    <!-- JavaScript Libraries -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Admin Common Scripts -->
    <script>
        $(document).ready(function() {
            // Initialize DataTables with common options
            $('.data-table').DataTable({
                "pageLength": 25,
                "order": [[ 0, "desc" ]],
                "responsive": true,
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });
            
            // CSRF token for AJAX requests
            $.ajaxSetup({
                beforeSend: function(xhr, settings) {
                    if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                        xhr.setRequestHeader("X-CSRFToken", $('meta[name=csrf-token]').attr('content'));
                    }
                }
            });
            
            // Auto-hide alerts after 5 seconds
            $('.alert').each(function() {
                const alert = $(this);
                setTimeout(function() {
                    alert.fadeOut();
                }, 5000);
            });
            
            // Confirmation dialogs for dangerous actions
            $('.confirm-action').on('click', function(e) {
                const message = $(this).data('confirm-message') || 'Are you sure you want to perform this action?';
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Status badge updates
            $('.status-select').on('change', function() {
                const select = $(this);
                const form = select.closest('form');
                if (form.length) {
                    form.submit();
                }
            });
            
            // File upload progress
            $('input[type="file"]').on('change', function() {
                const file = this.files[0];
                const maxSize = 10 * 1024 * 1024; // 10MB
                
                if (file && file.size > maxSize) {
                    alert('File size must be less than 10MB');
                    $(this).val('');
                    return false;
                }
            });
            
            // Form validation
            $('.admin-form').on('submit', function() {
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                
                // Disable submit button to prevent double submission
                submitBtn.prop('disabled', true);
                submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Processing...');
                
                // Re-enable after 3 seconds as fallback
                setTimeout(function() {
                    submitBtn.prop('disabled', false);
                    submitBtn.html(submitBtn.data('original-text') || 'Submit');
                }, 3000);
            });
            
            // Store original button text
            $('button[type="submit"]').each(function() {
                $(this).data('original-text', $(this).html());
            });
            
            // Tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
            
            // Auto-refresh elements (for real-time updates)
            $('.auto-refresh').each(function() {
                const element = $(this);
                const interval = element.data('refresh-interval') || 30000; // 30 seconds default
                const url = element.data('refresh-url');
                
                if (url) {
                    setInterval(function() {
                        $.get(url, function(data) {
                            element.html(data);
                        }).fail(function() {
                            console.log('Auto-refresh failed for element');
                        });
                    }, interval);
                }
            });
        });
        
        // Common admin functions
        function formatCurrency(amount, currency = 'USD') {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(amount);
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function showToast(message, type = 'info') {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            let toastContainer = $('.toast-container');
            if (toastContainer.length === 0) {
                toastContainer = $('<div class="toast-container position-fixed top-0 end-0 p-3"></div>');
                $('body').append(toastContainer);
            }
            
            const toast = $(toastHtml);
            toastContainer.append(toast);
            
            const bsToast = new bootstrap.Toast(toast[0]);
            bsToast.show();
            
            toast.on('hidden.bs.toast', function() {
                toast.remove();
            });
        }
        
        // Bulk actions handler
        function handleBulkActions() {
            $('.bulk-action-form').on('submit', function(e) {
                const form = $(this);
                const action = form.find('select[name="bulk_action"]').val();
                const selected = form.find('input[name="selected_items[]"]:checked');
                
                if (!action) {
                    e.preventDefault();
                    alert('Please select an action');
                    return false;
                }
                
                if (selected.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one item');
                    return false;
                }
                
                const confirmMessage = `Are you sure you want to ${action} ${selected.length} item(s)?`;
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Select all checkbox
            $('.select-all').on('change', function() {
                const checked = $(this).prop('checked');
                $('.item-select').prop('checked', checked);
            });
            
            // Update select all when individual items change
            $('.item-select').on('change', function() {
                const total = $('.item-select').length;
                const checked = $('.item-select:checked').length;
                $('.select-all').prop('checked', total === checked);
            });
        }
        
        // Initialize bulk actions
        $(document).ready(function() {
            handleBulkActions();
        });
    </script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($additional_scripts)): ?>
        <?php echo $additional_scripts; ?>
    <?php endif; ?>
    
    <!-- Debug information (only in development) -->
    <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
    <div class="debug-info" style="position: fixed; bottom: 0; right: 0; background: #f8f9fa; padding: 0.5rem; font-size: 0.75rem; border: 1px solid #dee2e6;">
        <strong>Debug Info:</strong><br>
        Memory: <?php echo round(memory_get_usage() / 1024 / 1024, 2); ?>MB<br>
        Time: <?php echo round((microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 2); ?>ms<br>
        User: <?php echo htmlspecialchars(getCurrentUserRole()); ?> (ID: <?php echo getCurrentUserId(); ?>)
    </div>
    <?php endif; ?>
    
</body>
</html>