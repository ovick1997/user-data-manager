<?php
/* 
Plugin Name: User and Options Manager
Plugin URI: https://github.com/ovick1997/user-data-manager
Description: Manage WordPress user data and options with an easy-to-use interface.
Version: 1.0.3
Author: Md Shorov Abedin
Author URI: https://shorovabedin.com
Text Domain: user-and-options-manager
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Add the menu page
if(!function_exists('msaudm_add_menu_page')) {
    function msaudm_add_menu_page() {
        add_menu_page(
            'User & Options Manager',
            'Manage Database',
            'manage_options',
            'user-and-options-manager',
            'msaudm_manage_page_content',
            'dashicons-database',
            30
        );
    }
}

add_action('admin_menu', 'msaudm_add_menu_page');

// Enqueue CSS, JS, and Localize Script
if(!function_exists('msaudm_enqueue_assets')) {
    function msaudm_enqueue_assets() {
        $css_version = filemtime(plugin_dir_path(__FILE__) . 'assets/style.css'); // Get the last modified time of the CSS file
        $js_version = filemtime(plugin_dir_path(__FILE__) . 'assets/script.js'); // Get the last modified time of the JS file

        wp_enqueue_style('udm-custom-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), $css_version); // Append the version
        wp_enqueue_script('udm-custom-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), $js_version, true);

        wp_localize_script('udm-custom-script', 'udmAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'udm_nonce_user_data' => wp_create_nonce('msaudm_save_user_data'),
            'udm_nonce_wp_option' => wp_create_nonce('msaudm_save_wp_option'),
        ));
    }
}
add_action('admin_enqueue_scripts', 'msaudm_enqueue_assets');
// Display the content on the plugin page
if(!function_exists('msaudm_manage_page_content')) {
    function msaudm_manage_page_content() {
        ?>
        <div class="wrap msa-udm-wrap">
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
                            //'siteurl', 'home', 
                            $options = ['blogname', 'admin_email'];
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
}

// Handle saving user data via AJAX
if (!function_exists('msaudm_save_user_data')) {
    function msaudm_save_user_data() {
        if (
            !isset($_POST['udm_nonce_user_data']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['udm_nonce_user_data'])), 'msaudm_save_user_data')
        ) {
            wp_send_json_error(['message' => 'Nonce verification failed.']);
        }

        if (isset($_POST['user_id'], $_POST['name'], $_POST['email'])) {
            $user_id = intval($_POST['user_id']);

            // Check if the current user has permission to edit the specified user
            if (!current_user_can('edit_user', $user_id)) {
                wp_send_json_error(['message' => 'Insufficient permissions to edit this user.']);
            }

            $name = sanitize_text_field(wp_unslash($_POST['name']));
            $email = sanitize_email(wp_unslash($_POST['email']));

            // Additional validation for email format
            if (!is_email($email)) {
                wp_send_json_error(['message' => 'Invalid email format.']);
            }

            $user_data = [
                'ID' => $user_id,
                'display_name' => $name,
                'user_email' => $email,
            ];

            $update_result = wp_update_user($user_data);

            if (!is_wp_error($update_result)) {
                wp_send_json_success(['message' => 'User updated successfully.']);
            } else {
                wp_send_json_error(['message' => 'Failed to update user: ' . $update_result->get_error_message()]);
            }
        } else {
            wp_send_json_error(['message' => 'Required parameters are missing.']);
        }
    }
}
add_action('wp_ajax_udm_save_user_data', 'msaudm_save_user_data');

// Handle saving WP options via AJAX
if (!function_exists('msaudm_save_wp_option')) {
    function msaudm_save_wp_option() {
        if (
            !isset($_POST['udm_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['udm_nonce'])), 'msaudm_save_wp_option')
        ) {
            wp_send_json_error(['message' => 'Nonce verification failed.']);
        }
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error(['message' => 'Insufficient permissions to update options.']);
        }
        if (isset($_POST['option_name'], $_POST['value'])) {
            $option_name = sanitize_text_field(wp_unslash($_POST['option_name']));
            $value = sanitize_text_field(wp_unslash($_POST['value']));

            // Validate the option name against the whitelist
            $allowed_options = [
                'blogname',
                'admin_email',
            ];

            if (in_array($option_name, $allowed_options, true)) {
                if (update_option($option_name, $value)) {
                    wp_send_json_success(['message' => 'Option updated successfully.']);
                } else {
                    wp_send_json_error(['message' => 'Failed to update the option.']);
                }
            } else {
                wp_send_json_error(['message' => 'Invalid option name provided.']);
            }
        } else {
            wp_send_json_error(['message' => 'Required parameters are missing.']);
        }
    }
}
add_action('wp_ajax_udm_save_wp_option', 'msaudm_save_wp_option');
