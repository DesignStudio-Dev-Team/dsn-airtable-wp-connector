jQuery(document).ready(function($) {
    // Load Tables logic
    $('#dsn-at-load-tables-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $spinner = $('#dsn-at-load-tables-spinner');
        var $container = $('#dsn-at-tables-dropdown-container');
        var $dropdown = $('#dsn-at-tables-dropdown');

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dsn_at_fetch_tables'
            },
            success: function(response) {
                if (response.success) {
                    $dropdown.empty().append('<option value="">Select a table...</option>');
                    $.each(response.data, function(index, table) {
                        $dropdown.append($('<option>', {
                            value: table.name,
                            text: table.name
                        }));
                    });
                    $container.show();
                    
                    // Auto-fill input when select changes
                    $dropdown.on('change', function() {
                        $('#dsn_at_table_name').val($(this).val());
                    });
                } else {
                    alert('Failed to load tables: ' + response.data);
                }
            },
            error: function() {
                alert('Failed to load tables: Server error.');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });

    // Sync logic
    $('#dsn-at-sync-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $status = $('#dsn-at-sync-status');
        var $table = $('#dsn-at-products-table');

        $btn.prop('disabled', true).text('Syncing...');
        $status.text('Starting sync...').css('color', 'black');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dsn_at_sync'
            },
            success: function(response) {
                if (response.success) {
                    $status.text('Sync completed successfully!').css('color', 'green');
                    loadProducts();
                } else {
                    $status.text('Sync failed: ' + response.data).css('color', 'red');
                }
            },
            error: function() {
                $status.text('Sync failed: Server error.').css('color', 'red');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Sync Now');
            }
        });
    });

    function loadProducts() {
        var $table = $('#dsn-at-products-table');
        $table.html('Loading products...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dsn_at_get_products'
            },
            success: function(response) {
                if (response.success) {
                    $table.html(response.data);
                } else {
                    $table.html('Failed to load products.');
                }
            }
        });
    }

    // Load products on page load
    loadProducts();
});
