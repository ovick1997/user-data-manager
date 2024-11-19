<?php
/* 
Plugin Name: User and WP Options Manager
Description: Manage WordPress user data and options with an easy-to-use interface.
Version: 1.0
Author: Md Shorov Abedin
Author URI: https://shorovabedin.com
Text Domain: user-wp-options-manager
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Add the menu page
function udm_add_menu_page() {
    add_menu_page(
        'User & WP Options Manager',
        'Manage Database',
        'manage_options',
        'user-wp-options-manager',
        'udm_manage_page_content',
        'dashicons-database',
        30
    );
}
add_action('admin_menu', 'udm_add_menu_page');

// Enqueue CSS, JS, and Localize Script
function udm_enqueue_assets() {
    $css_version = filemtime(plugin_dir_path(__FILE__) . 'assets/style.css'); // Get the last modified time of the CSS file
    $js_version = filemtime(plugin_dir_path(__FILE__) . 'assets/script.js'); // Get the last modified time of the JS file

    wp_enqueue_style('udm-custom-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), $css_version); // Append the version
    wp_enqueue_script('udm-custom-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), $js_version, true);

    wp_localize_script('udm-custom-script', 'udmAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'udm_nonce_user_data' => wp_create_nonce('udm_save_user_data'),
        'udm_nonce_wp_option' => wp_create_nonce('udm_save_wp_option'),
    ));
}
add_action('admin_enqueue_scripts', 'udm_enqueue_assets');
// Display the content on the plugin page
function udm_manage_page_content() {
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Manage Database</h1>
        <ul class="udm-tabs">
            <li class="udm-tab-link active" data-tab="user-tab">User</li>
            <li class="udm-tab-link" data-tab="wp-options-tab">WP Options</li>
        </ul>
        <div class="udm-tab-content">
            <div class="udm-tab-pane active" id="user-tab">
                <h2>User Information</h2>
                <table class="udm-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $users = get_users();
                        foreach ($users as $user) {
                            ?>
                            <tr data-user-id="<?php echo esc_attr($user->ID); ?>">
                                <td class="udm-display-name"><?php echo esc_html($user->display_name); ?></td>
                                <td class="udm-display-email"><?php echo esc_html($user->user_email); ?></td>
                                <td>
                                    <button class="udm-edit-user-button">Edit</button>
                                    <button class="udm-save-user-button" style="display:none;">Save</button>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="udm-tab-pane" id="wp-options-tab">
                <h2>WP Options</h2>
                <table class="udm-table">
                    <thead>
                        <tr>
                            <th>Option Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $options = ['siteurl', 'home', 'blogname', 'admin_email'];
                        foreach ($options as $option_name) {
                            ?>
                            <tr data-option-name="<?php echo esc_attr($option_name); ?>">
                                <td class="udm-display-<?php echo esc_attr($option_name); ?>"><?php echo esc_html(get_option($option_name)); ?></td>
                                <td>
                                    <button class="udm-edit-option-button">Edit</button>
                                    <button class="udm-save-option-button" style="display:none;">Save</button>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

// Handle saving user data via AJAX
function udm_save_user_data() {
    if (!isset($_POST['udm_nonce_user_data']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['udm_nonce_user_data'])), 'udm_save_user_data')) { // Use the correct nonce
        wp_send_json_error(array('message' => 'Nonce verification failed.'));
    }

    if (isset($_POST['user_id'], $_POST['name'], $_POST['email'])) {
        $user_id = intval($_POST['user_id']);
        $name = sanitize_text_field(wp_unslash($_POST['name']));
        $email = sanitize_email(wp_unslash($_POST['email']));

        $user_data = ['ID' => $user_id, 'display_name' => $name, 'user_email' => $email];
        if (!is_wp_error(wp_update_user($user_data))) {
            wp_send_json_success();
        }
    }
    wp_send_json_error(array('message' => 'Failed to update user.'));
}
add_action('wp_ajax_udm_save_user_data', 'udm_save_user_data');

// Handle saving WP options via AJAX
function udm_save_wp_option() {
    if (!isset($_POST['udm_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['udm_nonce'])), 'udm_save_wp_option')) {
        wp_send_json_error(array('message' => 'Nonce verification failed.'));
    }

    if (isset($_POST['option_name'], $_POST['value'])) {
        $option_name = sanitize_text_field(wp_unslash($_POST['option_name']));
        $value = sanitize_text_field(wp_unslash($_POST['value']));

        if (update_option($option_name, $value)) {
            wp_send_json_success(array('message' => 'Option updated successfully.'));
        }
    }
    wp_send_json_error(array('message' => 'Failed to update the option.'));
}
add_action('wp_ajax_udm_save_wp_option', 'udm_save_wp_option');
