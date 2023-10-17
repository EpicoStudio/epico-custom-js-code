<?php
/**
 * Plugin Name: Épico Custom Javascript Code
 * Plugin URI: https://epico.studio
 * Description: Adds custom Javascript code to the site front-end.
 * Version: 1.0.1
 * Text Domain: epico-custom-js-code
 * Author: Márcio Duarte
 * Author URI: https://epico.studio
 * License: GPL2
 */

/*
    Copyright (C) 2023  Márcio Duarte  falecom@epico.studiom

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

namespace Epico;

// Exit if accessed directly.
if ( !defined('ABSPATH') ) {
    exit;
}

/**
 * CustomJSCode class for managing custom JavaScript code in WordPress.
 */
class CustomJSCode {

    /**
     * Constructor for the CustomJSCode class.
     * Initializes hooks and actions.
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_submenu_page']);
        add_action('admin_post_epico_save_code', [$this, 'saveCode']);
        add_action('wp_head', [$this, 'includeHeaderCode'], -999); // Add it right after the start of the <head> tag.
        add_action('wp_body_open', [$this, 'includeBodyCode'], -999);
        add_action('wp_footer', [$this, 'includeFooterCode'], 999); // Add it right before the closing of the </body> tag.
        register_uninstall_hook(__FILE__, 'clearOptionsOnUninstallation');
        add_action('admin_enqueue_scripts', [$this, 'enqueueCodeMirror']);
        add_filter('safe_style_css', [$this, 'keepStyleProperties']);
    }

    /**
     * Adds a submenu page to the WordPress admin menu.
     */
    public function add_submenu_page() {
        add_submenu_page(
            'options-general.php',
            __( 'Épico Custom Javascript Code', 'epico-custom-javascript-code' ),
            __( 'Épico Custom Javascript Code', 'epico-custom-javascript-code' ),
            'manage_options',
            'epico-custom-js-code',
            [$this, 'render_submenu_page']
        );
    }

    /**
     * Renders the content of the subpage for managing custom JavaScript code.
     */
    public function render_submenu_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Épico Custom Javascript Code', 'epico-custom-javascript-code' ); ?></h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('epico_save_code', 'epico_nonce'); ?>
                <div>
                    <p>
                        <label for="epico_header_code"><?php _e( 'Insert Javascript code in the “head” tag of the website:', 'epico-custom-javascript-code' ); ?></label>
                    </p>
                    <p>
                        <textarea name="epico_header_code" id="epico_header_code" rows="15" cols="50"><?php echo esc_textarea(get_option('epico_head_code')); ?></textarea><br>
                    </p>
                </div>
                <div>
                    <p>
                        <label for="epico_body_code"><?php _e( 'Insert Javascript code right after the opening of the “body” tag:', 'epico-custom-javascript-code' ); ?></label>
                    </p>
                    <p>
                        <textarea name="epico_body_code" id="epico_body_code" rows="15" cols="50"><?php echo esc_textarea(get_option('epico_body_code')); ?></textarea><br>
                    </p>
                </div>
                <div>
                    <p>
                        <label for="epico_footer_code"><?php _e( 'Insert Javascript code right before the closing “body” tag:', 'epico-custom-javascript-code' ); ?></label>
                    </p>
                    <p>
                        <textarea name="epico_footer_code" id="epico_footer_code" rows="15" cols="50"><?php echo esc_textarea(get_option('epico_footer_code')); ?></textarea><br>
                    </p>
                </div>
                <div>
                    <p>
                        <label for="epico_clean_on_uninstall">
                            <input type="checkbox" id="epico_clean_on_uninstall" name="epico_clean_on_uninstall" value="1" <?php checked( get_option('epico_clean_on_uninstall'), 1, false ) ?>>
                            <?php _e('Remove the code from the database on plugin deactivation','epico-custom-javascript-code');?>
                        </label>
                    </p>
                </div>
                <input type="hidden" name="action" value="epico_save_code">
                <input type="submit" value="Save" class="button-primary">
            </form>
        </div>
        <?php
    }

    /**
     * Saves the JavaScript code provided by the user.
     */
    public function saveCode() {
        if (current_user_can('manage_options') && isset($_POST['epico_nonce']) && wp_verify_nonce($_POST['epico_nonce'], 'epico_save_code')) {

            if (isset($_POST['epico_header_code'])) {
                update_option('epico_head_code', wp_kses(stripslashes($_POST['epico_header_code']), $this->allowedHTML(), $this->allowedProtocols()));
            } else {
                delete_option('epico_head_code');
            }

            if (isset($_POST['epico_body_code'])) {
                update_option('epico_body_code', wp_kses(stripslashes($_POST['epico_body_code']), $this->allowedHTML(), $this->allowedProtocols()));
            } else {
                delete_option('epico_body_code');
            }


            if (isset($_POST['epico_footer_code'])) {
                update_option('epico_footer_code', wp_kses(stripslashes($_POST['epico_footer_code']), $this->allowedHTML(), $this->allowedProtocols()));
            } else {
                delete_option('epico_footer_code');
            }

            if (isset($_POST['epico_clean_on_uninstall'])) {
                update_option('epico_clean_on_uninstall', isset($_POST['epico_clean_on_uninstall']) ? 1 : 0);
            } else {
                delete_option('epico_clean_on_uninstall');
            }
        }
        wp_safe_redirect(esc_url(admin_url('admin.php?page=epico-custom-js-code')));
        exit();
    }

    /**
     * Includes JavaScript code in the head section of the site.
     */
    public function includeHeaderCode() {
        $header_code = get_option('epico_head_code');

        if ($header_code) {
            echo wp_kses($header_code, $this->allowedHTML(), $this->allowedProtocols());
        }
    }

    /**
     * Includes JavaScript code right after the opening of the body tag.
     */
    public function includeBodyCode() {
        $body_code = get_option('epico_body_code');

        if ($body_code) {
            echo wp_kses($body_code, $this->allowedHTML(), $this->allowedProtocols());
        }
    }

    /**
     * Includes JavaScript code right before the closing body tag.
     */
    public function includeFooterCode() {
        $footer_code = get_option('epico_footer_code');

        if ($footer_code) {
            echo wp_kses($footer_code, $this->allowedHTML(), $this->allowedProtocols());
        }
    }

    /**
     * Clears options from the database on plugin uninstallation.
     */
    public static function clearOptionsOnUninstallation() {
        delete_option('epico_head_code');
        delete_option('epico_footer_code');
        delete_option('epico_clean_on_uninstall');
    }

    /**
     * Enqueues the CodeMirror script for code editing.
     */
    public function enqueueCodeMirror($hook) {
        if ($hook != "settings_page_epico-custom-js-code") {
            return;
        }

        // Work with Javascript only.
        $settings = wp_enqueue_code_editor(['type' => 'application/javascript']);

        // Accept <script> tags.
        $settings['codemirror']['mode'] = 'htmlmixed';

        if (false === $settings) {
            wp_add_inline_script(
                'wp-codemirror',
                'jQuery(function($) { $("#epico_header_code, #epico_body_code, #epico_footer_code").prop("disabled", false); })'
            );
            return;
        }

        wp_add_inline_script(
            'wp-codemirror',
            sprintf(
                'jQuery(function($) { var settings = %s; $("#epico_header_code, #epico_body_code, #epico_footer_code").each(function(index, element) { wp.codeEditor.initialize(element, settings); }); })',
                wp_json_encode($settings)
            )
        );

        wp_enqueue_script('wp-theme-plugin-editor');
        wp_enqueue_style('wp-codemirror');
    }

    /**
     * Adds style properties to the list of safe styles.
     */
    public function keepStyleProperties($styles) {
        $styles[] = 'display';
        $styles[] = 'visibility';
        $styles[] = 'width';
        $styles[] = 'height';
        return $styles;
    }

    /**
     * Returns an array of allowed HTML elements and attributes.
     */
    private function allowedHTML() {
        return [
            'script' => [
                'async' => [],
                'defer' => [],
                'src' => [],
                'type' => [],
                'id' => [],
            ],
            'iframe' => [
                'src' => [],
                'style' => [
                    'visibility' => [],
                    'display' => [],
                ],
                'width' => [],
                'height' => [],
                'id' => [],
            ],
            'noscript' => [],
        ];
    }

    /**
     * Returns an array of allowed protocols.
     */
    private function allowedProtocols() {
        return ['https'];
    }
}

// Create an instance of the CustomJSCode class to initiate the plugin.
new CustomJSCode();
