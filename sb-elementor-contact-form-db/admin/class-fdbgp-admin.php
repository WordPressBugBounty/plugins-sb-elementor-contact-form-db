<?php
if (!defined('ABSPATH')) {
    die;
}

use Formsdb_Elementor_Forms\Admin\CPFM_Feedback_Notice;

if(!class_exists('FDBGP_Admin')) { 

    class FDBGP_Admin {

        private static $instance = null;
        private $plugin_name;
        private $version;
        private $google_settings;
        
        /**
         * Main FDBGP_Admin Instance.
         */
        public static function get_instance($plugin_name, $version) {
            if ( null == self::$instance ) {
                self::$instance = new self($plugin_name, $version);
            }
            return self::$instance;
        }

        /**
         * FDBGP_Admin Constructor.
         */
        private function __construct($plugin_name, $version) {
            $this->plugin_name = $plugin_name;
            $this->version = $version;
            
            // Initialize Google settings
            $this->google_settings = get_option('fdbgp_google_settings', array(
                'client_id' => '',
                'client_secret' => '',
                'client_token' => ''
            ));
            
            add_action('admin_menu', array($this, 'add_plugin_admin_menu'), 999);
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
            add_action('admin_action_fdbgp_create_elementor_page', array($this, 'redirect_to_elementor_builder'));

            add_action('cpfm_register_notice', function () { 
                if (!class_exists('Formsdb_Elementor_Forms\Admin\CPFM_Feedback_Notice') || !current_user_can('manage_options')) {
                    return;
                }

                $notice = [
                    'title' => __('Elementor Form Addons by Cool Plugins', 'sb-elementor-contact-form-db'),
                    'message' => __('Help us make this plugin more compatible with your site by sharing non-sensitive site data.', 'sb-elementor-contact-form-db'),
                    'pages' => ['cool-formkit','cfkef-entries','cool-formkit&tab=recaptcha-settings','formsdb'],
                    'always_show_on' => ['cool-formkit','cfkef-entries','cool-formkit&tab=recaptcha-settings','formsdb'], // This enables auto-show
                    'plugin_name'=>'fdbgp'
                ];

                CPFM_Feedback_Notice::cpfm_register_notice('cool_forms', $notice);

                    if (!isset($GLOBALS['cool_plugins_feedback'])) {
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Shared across Cool Plugins products.
                        $GLOBALS['cool_plugins_feedback'] = [];
                    }
                    
                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Shared across Cool Plugins products.
                    $GLOBALS['cool_plugins_feedback']['cool_forms'][] = $notice;
            
            });
        
            add_action('cpfm_after_opt_in_fdbgp', function($category) {
                
                    if ($category === 'cool_forms') {

                        require_once FDBGP_PLUGIN_DIR . 'admin/feedback/cron/fdbgp-class-cron.php';

                        // Set the usage share data option to 'on'
                        update_option( 'cfef_usage_share_data', 'on' );

                        // Send initial data for this plugin
                        fdbgp_cronjob::fdbgp_send_data();

                        // Schedule crons for all form plugins
                        // Include the settings file where fdbgp_handle_unchecked_checkbox is defined
                        if (!function_exists('fdbgp_handle_unchecked_checkbox')) {
                            require_once FDBGP_PLUGIN_DIR . 'admin/views/settings.php';
                        }
                        
                        // Call the function to schedule crons - now safe since settings.php no longer outputs HTML on include
                        fdbgp_handle_unchecked_checkbox();
                    } 
            });

            add_action( 'wp_ajax_fdbgp_plugin_install', 'wp_ajax_install_plugin' );
            add_action( 'wp_ajax_fdbgp_plugin_activate', array($this, 'fdbgp_plugin_activate') );
        }

        public function fdbgp_plugin_activate(){
            check_ajax_referer( 'fdbgp_plugin_nonce', 'security' );
            if ( ! current_user_can( 'activate_plugins' ) ) {
                wp_send_json_error( [ 'message' => 'Permission denied' ] );
            }
    
            if ( empty( $_POST['init'] ) ) {
                wp_send_json_error( [ 'message' => 'Plugin init file missing' ] );
            }
    
            if ( ! function_exists( 'get_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
    
            $init_file = sanitize_text_field( wp_unslash($_POST['init']) );
    
            // Use silent activation to prevent redirection hooks from breaking AJAX response
            $activate = activate_plugin( $init_file, '', false, true );
    
            if ( is_wp_error( $activate ) ) {
                wp_send_json_error( [ 'message' => $activate->get_error_message() ] );
            }
    
            wp_send_json_success( [ 'message' => 'Plugin activated successfully' ] );
        } 

        /**
         * Create a new page and redirect to Elementor Editor
         */
        public function redirect_to_elementor_builder() {
            if ( ! current_user_can( 'edit_pages' ) ) {
                wp_die( 'Insufficient permissions' );
            }

            $post_data = array(
                'post_title'  => 'New Elementor Form',
                'post_type'   => 'page',
                'post_status' => 'draft',
            );
            
            $post_id = wp_insert_post($post_data);
            
            if($post_id && !is_wp_error($post_id)){
                update_post_meta($post_id, '_elementor_edit_mode', 'builder');
                
                // Redirect to Elementor Editor
                $redirect_url = admin_url( 'post.php?post=' . $post_id . '&action=elementor' );
                wp_safe_redirect($redirect_url);
                exit;
            }
            
            wp_safe_redirect(admin_url('post-new.php?post_type=page'));
            exit;
        }

        public function add_plugin_admin_menu() {
            // Check if conflicting plugins are active
            $is_conflicting_active = is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' ) 
                || is_plugin_active( 'extensions-for-elementor-form/extensions-for-elementor-form.php' );
            
            if ( $is_conflicting_active ) {
                add_submenu_page(
                    'elementor',
                    __('FormsDB', 'sb-elementor-contact-form-db'),
                    __('↳ FormsDB', 'sb-elementor-contact-form-db'),
                    'manage_options',
                    'formsdb',
                    array($this, 'display_plugin_admin_page'),
                    18 // Position after cool-formkit (which is at default position)
                );
            } else {
                // Add as submenu under elementor (default behavior)
                add_submenu_page(
                    'elementor',
                    __('FormsDB', 'sb-elementor-contact-form-db'),
                    __('FormsDB', 'sb-elementor-contact-form-db'),
                    'manage_options',
                    'formsdb',
                    array($this, 'display_plugin_admin_page')
                );
            }
        }

        public function display_plugin_admin_page() {
            $allowed_tabs = array('forms-sheets', 'post-type', 'settings', 'advanced', 'old-submission');
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab selection for navigation, no data modification.
            $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'forms-sheets';
            if ( ! in_array( $tab, $allowed_tabs, true ) ) {
                $tab = 'forms-sheets'; 
            }

            // Check for old submissions
            if (!class_exists('FDBGP_Old_Submission')) {
                require_once FDBGP_PLUGIN_DIR . 'includes/class-fdbgp-old-submission.php';
            }
            $has_old_submissions = FDBGP_Old_Submission::has_old_submissions();
            
            ?>
            <div class="fdbgp-wrapper">
                <div id="fdbgp-loader" style="display: none;">
                    <div class="fdbgp-loader-overlay"></div>
                    <div class="fdbgp-loader-spinner"></div>
                </div>
                <div class="fdbgp-header">
                    <div class="fdbgp-header-logo">
                        <a href="?page=formsdb">
                            <img src="<?php echo esc_url(FDBGP_PLUGIN_URL . 'assets/images/formsDB-logo.svg'); ?>" alt="Cool FormKit Logo">
                        </a>
                    </div>

                    <div class="fdbgp-header-buttons">

                        <?php
                            if (! is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' )) :
                        ?>
                    
                        <span>Unlock advanced fields and features for Elementor Forms.</span>
                        <a href="https://coolformkit.com/features/?utm_source=formsdb&utm_medium=inside&utm_campaign=demo&utm_content=setting_page_header" class="button button-primary fdbgp-try-cool-form" target="_blank"><?php 
                            esc_html_e('Try Cool FormKit for Elementor', 'sb-elementor-contact-form-db');?></a>

                        <?php else: ?>

                            <span>Use advanced fields and features for Elementor Forms.</span>
                        <a href="<?php echo  esc_url( admin_url( 'admin.php?page=cool-formkit' ))?>" class="button button-primary fdbgp-try-cool-form" target="_blank"><?php 
                            esc_html_e('Use Cool FormKit for Elementor', 'sb-elementor-contact-form-db');?></a>

                        <?php endif; ?>

                    </div>
                </div>
                <h2 class="nav-tab-wrapper">
                    <a href="?page=formsdb&tab=forms-sheets" class="nav-tab <?php echo $tab == 'forms-sheets' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Forms To Sheet', 'sb-elementor-contact-form-db'); ?></a>
                    <a href="?page=formsdb&tab=post-type" class="nav-tab <?php echo $tab == 'post-type' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Forms To Post Type', 'sb-elementor-contact-form-db'); ?></a>
                    <?php
                    if (
                        is_plugin_active( 'hello-plus/hello-plus.php' ) &&
                        ! is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' ) &&
                        ! is_plugin_active( 'extensions-for-elementor-form/extensions-for-elementor-form.php' )
                    ) :
                    ?>
                        <a href="?page=cfkef-entries" class="nav-tab <?php echo $tab == 'cfkef-entries' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Hello+ Form Entries', 'sb-elementor-contact-form-db'); ?></a>
                    <?php endif; ?>
                    <a href="?page=formsdb&tab=settings" class="nav-tab <?php echo $tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Settings', 'sb-elementor-contact-form-db'); ?></a>
                    <?php
                    if (! is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' )) :
                    ?>
                        <a href="?page=formsdb&tab=advanced" class="nav-tab <?php echo $tab == 'advanced' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Advanced Fields', 'sb-elementor-contact-form-db'); ?></a>
                    <?php endif; ?>
                    <?php if ($has_old_submissions) : ?>
                        <a href="?page=formsdb&tab=old-submission" class="nav-tab <?php echo $tab == 'old-submission' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Old Submissions', 'sb-elementor-contact-form-db'); ?></a>
                    <?php endif; ?>
                </h2>
                <div class="tab-content">
                    <?php
                    switch ($tab) {
                        case 'settings':
                            require_once 'views/settings.php';
                            $settings_page = new FDBGP_Settings_Page();
                            $settings_page->render();
                            break;
                        case 'post-type':
                            include_once 'views/form-to-posttype.php';
                            break;
                        case 'forms-sheets':
                            include_once 'views/form-to-sheet.php';
                            break;
                        case 'advanced':
                            if(is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' )){
                                include_once 'views/form-to-sheet.php';
                                break;
                            }else{
                                include_once 'views/advanced-fields.php';
                                break;
                            }
                        case 'old-submission':
                            include_once 'views/old-submission.php';
                            break;
                        default:
                            // Show default tab content
                            break;
                    }
                    ?>
                </div>
            </div>
            <?php
        }

        public function enqueue_admin_styles() {
            $is_conflicting_active = is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' ) || is_plugin_active( 'extensions-for-elementor-form/extensions-for-elementor-form.php' );
            if(!$is_conflicting_active){
                wp_enqueue_style('fdbgp-admin-global-style', FDBGP_PLUGIN_URL . 'assets/css/global-admin-style.css', array(), $this->version, 'all');
            }else{
                ?>
                <style>
                    li a[href="admin.php?page=formsdb"] {
                        padding-left: 10px;
                        font-style: italic;
                        opacity: 0.85;
                    }
                </style>
                <?php
            }

            $screen = get_current_screen();

            if ( $screen && 'elementor_page_e-form-submissions' === $screen->id ) {
                $button_text = __('Save To Google Sheet', 'sb-elementor-contact-form-db');
                $button_url = esc_url(admin_url('admin.php?page=formsdb'));
                
                $custom_js = "
                    jQuery(document).ready(function($) {
                        var button = '<a href=\"{$button_url}\" target=\"_blank\" class=\"button button-primary\">{$button_text}</a>';
                        $('#e-form-submissions .e-form-submissions-search').prepend(button);
                    });
                ";
                wp_add_inline_script('jquery-core', $custom_js);
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page check for loading assets, no data modification.
            $current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
            if ( strpos( $current_page, 'formsdb' ) !== false || strpos( $current_page, 'cfkef-entries' ) !== false ) {
                wp_enqueue_style('fdbgp-admin-style', FDBGP_PLUGIN_URL . 'assets/css/admin-style.css', array(), $this->version, 'all');
                wp_enqueue_style('dashicons');

                wp_enqueue_style('fdbgp-admin-style', FDBGP_PLUGIN_URL . 'assets/css/admin-style.css', array(), $this->version, 'all');
                
                wp_enqueue_script('fdbgp-admin-script', FDBGP_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), $this->version, true); 

                wp_localize_script( 'fdbgp-admin-script', 'fdbgp_plugin_vars', [
                    'nonce' => wp_create_nonce( 'fdbgp_plugin_nonce' ),
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'installNonce' => wp_create_nonce( 'updates' ),
                ] );
            } elseif ( strpos( $current_page, 'cool-formkit' ) !== false ) {
                wp_enqueue_script('fdbgp-admin-script', FDBGP_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), $this->version, true); 
            }
        }
    }
}