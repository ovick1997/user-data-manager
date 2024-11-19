jQuery(document).ready(function($) {
   // Tab switching functionality
   $('.udm-tab-link').click(function() {
        var tabId = $(this).data('tab');
        $('.udm-tab-link, .udm-tab-pane').removeClass('active');
        $(this).addClass('active');
        $('#' + tabId).addClass('active');
    });

    // Edit User Data
    $('.udm-edit-user-button').click(function() {
        var row = $(this).closest('tr');
        var userId = row.data('user-id');
        var name = row.find('.udm-display-name').text();
        var email = row.find('.udm-display-email').text();

        row.find('.udm-display-name').html('<input type="text" value="' + name + '" data-id="' + userId + '">');
        row.find('.udm-display-email').html('<input type="email" value="' + email + '" data-id="' + userId + '">');
        row.find('.udm-save-user-button').show();
        $(this).hide();
    });

    // Save User Data
    $('.udm-save-user-button').click(function() {
        var row = $(this).closest('tr');
        var userId = row.find('.udm-display-name input').data('id');
        var name = row.find('.udm-display-name input').val();
        var email = row.find('.udm-display-email input').val();

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'udm_save_user_data',
                user_id: userId,
                name: name,
                email: email,
                udm_nonce_user_data: udmAjax.udm_nonce_user_data // Pass the correct nonce
            },
            success: function(response) {
                if (response.success) {
                    row.find('.udm-display-name').text(name);
                    row.find('.udm-display-email').text(email);
                    row.find('.udm-save-user-button').hide();
                    row.find('.udm-edit-user-button').show();
                } else {
                    alert('Error saving user data');
                }
            }
        });
    });

    // Edit WP Option
    $('.udm-edit-option-button').click(function() {
        var row = $(this).closest('tr');
        var optionName = row.data('option-name');
        var value = row.find('.udm-display-' + optionName).text();

        row.find('.udm-display-' + optionName).html('<input type="text" value="' + value + '" data-name="' + optionName + '">');
        row.find('.udm-save-option-button').show();
        $(this).hide();
    });

    // Save WP Option
    $('.udm-save-option-button').click(function() {
        var row = $(this).closest('tr');
        var optionName = row.find('input').data('name');
        var value = row.find('input').val();

        $.ajax({
            url: udmAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'udm_save_wp_option',
                option_name: optionName,
                value: value,
                udm_nonce: udmAjax.udm_nonce_wp_option // Pass the nonce
            },
            success: function(response) {
                if (response.success) {
                    row.find('.udm-display-' + optionName).text(value);
                    row.find('.udm-save-option-button').hide();
                    row.find('.udm-edit-option-button').show();
                } else {
                    alert(response.data.message || 'Error saving WP option');
                }
            }
        });
    });
});
