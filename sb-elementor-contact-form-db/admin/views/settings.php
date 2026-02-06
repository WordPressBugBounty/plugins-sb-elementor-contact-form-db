<?php
if (!defined('ABSPATH')) {
    die;
}

use Formsdb_Elementor_Forms\Lib_Helpers\FDBGP_Google_API_Functions;

/**
 * Handle unchecked checkbox for usage share data.
 * 
 * This function is kept as a standalone function for backward compatibility
 * with AJAX calls that require it.
 */
if (!function_exists('fdbgp_handle_unchecked_checkbox')) {
    function fdbgp_handle_unchecked_checkbox() {
        $choice  = get_option('cpfm_opt_in_choice_cool_forms');
        $options = get_option('cfef_usage_share_data');

        if (!empty($choice)) {

            // If the checkbox is unchecked (value is empty, false, or null)
            if (empty($options)) {
                // formsDB
                wp_clear_scheduled_hook('fdbgp_extra_data_update');

                // conditional free
                if(method_exists('cfef_cronjob', 'cfef_send_data')){
                    wp_clear_scheduled_hook('cfef_extra_data_update');
                }

                // conditional pro
                if(method_exists('cfefp_cronjob', 'cfefp_send_data')){

                    wp_clear_scheduled_hook('cfefp_extra_data_update');
                }

                // country code
                if(method_exists('ccfef_cronjob', 'ccfef_send_data')){
                    wp_clear_scheduled_hook('ccfef_extra_data_update');
                }

                // form mask input
                if(method_exists('fme_cronjob', 'fme_send_data')){

                    wp_clear_scheduled_hook('fme_extra_data_update');
                }

                // input form mask
                if(method_exists('Mask_Form_Elementor\mfe_cronjob', 'mfe_send_data')){
                    wp_clear_scheduled_hook('mfe_extra_data_update');
                }

            }else {
                // formsDB
                if (!wp_next_scheduled('fdbgp_extra_data_update')) {
                    if (class_exists('fdbgp_cronjob') && method_exists('fdbgp_cronjob', 'fdbgp_send_data')) {
                        fdbgp_cronjob::fdbgp_send_data();
                    }
                    wp_schedule_event(time(), 'every_30_days', 'fdbgp_extra_data_update');
                }

                // conditional free
                if(method_exists('cfef_cronjob', 'cfef_send_data')){                    
                    if (!wp_next_scheduled('cfef_extra_data_update')) {
                        cfef_cronjob::cfef_send_data();
                        wp_schedule_event(time(), 'every_30_days', 'cfef_extra_data_update');
                    }
                }

                // condition field pro
                if(method_exists('cfefp_cronjob', 'cfefp_send_data')){
                    if (!wp_next_scheduled('cfefp_extra_data_update')) {
                        cfefp_cronjob::cfefp_send_data();
                        wp_schedule_event(time(), 'every_30_days', 'cfefp_extra_data_update');
                    }
                }

                // country code
                if(method_exists('ccfef_cronjob', 'ccfef_send_data')){
                    if (!wp_next_scheduled('ccfef_extra_data_update')) {
                        ccfef_cronjob::ccfef_send_data();
                        wp_schedule_event(time(), 'every_30_days', 'ccfef_extra_data_update');
                    }
                }

                // form mask input
                if(method_exists('fme_cronjob', 'fme_send_data')){
                    if (!wp_next_scheduled('fme_extra_data_update')) {
                        fme_cronjob::fme_send_data();
                        wp_schedule_event(time(), 'every_30_days', 'fme_extra_data_update');
                    }

                }

                // input form mask
                if(method_exists('Mask_Form_Elementor\mfe_cronjob', 'mfe_send_data')){
                    if (!wp_next_scheduled('mfe_extra_data_update')) {
                        wp_schedule_event(time(), 'every_30_days', 'mfe_extra_data_update');
                        Mask_Form_Elementor\mfe_cronjob::mfe_send_data();
                    }
                }
            }
        }
    }
}

/**
 * Settings Page Class
 * 
 * Handles the settings page functionality for FormsDB plugin.
 */
if (!class_exists('FDBGP_Settings_Page')) {

    class FDBGP_Settings_Page {

        private $api_instance;
        private $google_settings;
        private $oauth_code;
        private $site_domain;
        private $redirect_uri;
        private $success_message = '';
        private $error_message = '';

        /**
         * Constructor.
         */
        public function __construct() {
            $this->api_instance = new FDBGP_Google_API_Functions();
            $this->init_settings();
            $this->process_form_submission();
        }

        /**
         * Initialize settings.
         */
        private function init_settings() {
            $this->google_settings = get_option('fdbgp_google_settings', array(
                'client_id' => '',
                'client_secret' => '',
                'client_token' => ''
            ));

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google doesn't include nonce.
            $this->oauth_code = isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
            
            $site_url = wp_parse_url( site_url(), PHP_URL_HOST );
            $this->site_domain = str_replace( 'www.', '', $site_url );
            $this->redirect_uri = admin_url( 'admin.php?page=formsdb&tab=settings' );
        }

        /**
         * Process form submission.
         */
        private function process_form_submission() {
            $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
            if ( 'POST' !== $request_method || ! isset( $_POST['fdbgp_settings_nonce'] ) ) {
                return;
            }

            if (!current_user_can('manage_options')) {
                wp_die('Unauthorized');
            }

            if (!check_admin_referer('fdbgp_settings_action', 'fdbgp_settings_nonce')) {
                $this->error_message = __('Security check failed. Please try again.', 'sb-elementor-contact-form-db');
                return;
            }

            $cfef_usage_share_data = isset( $_POST['cfef_usage_share_data'] ) ? sanitize_text_field( wp_unslash( $_POST['cfef_usage_share_data'] ) ) : '';
            update_option( 'cfef_usage_share_data', $cfef_usage_share_data );

            fdbgp_handle_unchecked_checkbox();

            // Save Google Settings
            if ( isset( $_POST['save_google_settings'] ) ) {
                $new_settings = array(
                    'client_id'     => isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '',
                    'client_secret' => isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '',
                    'client_token'  => isset( $_POST['client_token'] ) ? sanitize_text_field( wp_unslash( $_POST['client_token'] ) ) : ( $this->google_settings['client_token'] ?? '' ),
                );

                update_option('fdbgp_google_settings', $new_settings);
                $this->google_settings = $new_settings;
                $this->success_message = __('Settings saved successfully!', 'sb-elementor-contact-form-db');
            }

            // Revoke Token
            if (isset($_POST['revoke_token'])) {
                $this->google_settings['client_token'] = '';
                update_option('fdbgp_google_settings', $this->google_settings);
                delete_option('fdbgp_google_access_token');
                $this->success_message = __('Token revoked successfully!', 'sb-elementor-contact-form-db');
            }

            // Reset Settings
            if (isset($_POST['reset_google_settings'])) {
                delete_option('fdbgp_google_settings');
                delete_option('fdbgp_google_access_token');
                $this->google_settings = array('client_id' => '', 'client_secret' => '', 'client_token' => '');
                $this->success_message = __('Settings reset successfully!', 'sb-elementor-contact-form-db');
            }
        }

        public function render_review_request() {
            ?>
            <div class="cfkef-review-request">
                <div class="cfkef-review-left">
                    <h3><?php esc_html_e('Enjoying FormsDB for Elementor Forms?', 'sb-elementor-contact-form-db'); ?></h3>
                    <p><?php esc_html_e('Please consider leaving us a review. It helps us a lot!', 'sb-elementor-contact-form-db'); ?></p>
                </div>
                <div class="cfkef-review-right">
                    <div class="cfkef-stars">
                    ★★★★★
                    </div>
                    <a href="https://wordpress.org/support/plugin/sb-elementor-contact-form-db/reviews/#new-post" class="button button-primary" target="_blank"><?php esc_html_e('Leave a Review', 'sb-elementor-contact-form-db'); ?></a>
                </div>
            </div>
            <?php
        }
        /**
         * Render the settings page.
         */
        public function render() {
            $google_settings = $this->google_settings;
            $oauth_code = $this->oauth_code;
            $site_domain = $this->site_domain;
            $redirect_uri = $this->redirect_uri;
            $success_message = $this->success_message;
            $error_message = $this->error_message;
            $instance_api = $this->api_instance;
            ?>

            <div class="fdbgp-settings-box">
                <div class="fdbgp-promo">
                    <div class="fdbgp-box fdbgp-left">
                        <div class="wrapper-container">
                            <form method="post" action="" class="cool-formkit-form">
                                <div class="wrapper-header">
                                    <div class="fdbgp-save-all">
                                        <div class="fdbgp-title-desc">
                                            <h2><?php esc_html_e('Settings', 'sb-elementor-contact-form-db'); ?></h2>
                                        </div>
                                        <div class="fdbgp-save-controls">
                                            <button type="submit" name="save_google_settings" class="button button-primary">
                                                <?php esc_html_e('Save Changes', 'sb-elementor-contact-form-db'); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
    
                                <div class="wrapper-body">
                                    <!-- Display messages -->
                                    <?php if (!empty($success_message)) : ?>
                                        <div class="notice notice-success is-dismissible">
                                            <p><?php echo esc_html($success_message); ?></p>
                                        </div>
                                    <?php endif; ?>
    
                                    <?php if (!empty($error_message)) : ?>
                                        <div class="notice notice-error is-dismissible">
                                            <p><?php echo esc_html($error_message); ?></p>
                                        </div>
                                    <?php endif; ?>
    
                                    <div class="cool-formkit-left-side-setting">
                                        <h3><?php esc_html_e('Google API Settings', 'sb-elementor-contact-form-db'); ?></h3>
                                        <p class="description"><?php esc_html_e('Connect your Google account to sync form data with Google Sheets.', 'sb-elementor-contact-form-db'); ?></p>
    
                                        <table class="form-table cool-formkit-table">
                                            <!-- Client ID -->
                                            <tr>
                                                <th class="cool-formkit-table-th">
                                                    <label for="client_id" class="cool-formkit-label">
                                                        <?php esc_html_e('Client ID', 'sb-elementor-contact-form-db'); ?>
                                                    </label>
                                                </th>
                                                <td class="cool-formkit-table-td">
                                                    <input type="text"
                                                        id="client_id"
                                                        name="client_id"
                                                        class="regular-text cool-formkit-input"
                                                        value="<?php echo esc_attr($google_settings['client_id']); ?>"
                                                        placeholder="Enter your Client ID"
                                                        <?php echo !empty($google_settings['client_id']) ? 'readonly' : ''; ?> />
                                                    <p class="description"><?php esc_html_e('Enter your Google API Client ID', 'sb-elementor-contact-form-db'); ?></p>
                                                </td>
                                            </tr>
    
                                            <!-- Client Secret -->
                                            <tr>
                                                <th class="cool-formkit-table-th">
                                                    <label for="client_secret" class="cool-formkit-label">
                                                        <?php esc_html_e('Client Secret Key', 'sb-elementor-contact-form-db'); ?>
                                                    </label>
                                                </th>
                                                <td class="cool-formkit-table-td">
                                                    <input type="text"
                                                        id="client_secret"
                                                        name="client_secret"
                                                        class="regular-text cool-formkit-input"
                                                        value="<?php echo esc_attr($google_settings['client_secret']); ?>"
                                                        placeholder="Enter your Client Secret key"
                                                        <?php echo !empty($google_settings['client_secret']) ? 'readonly' : ''; ?> />
                                                    <p class="description"><?php esc_html_e('Enter your Google API Client Secret key', 'sb-elementor-contact-form-db'); ?></p>
                                                </td>
                                            </tr>
    
                                            <!-- Authentication Token -->
                                            <?php if (!empty($google_settings['client_id']) && !empty($google_settings['client_secret'])) : ?>
                                                <tr>
                                                    <th class="cool-formkit-table-th">
                                                        <label for="client_token" class="cool-formkit-label">
                                                            <?php esc_html_e('Authentication Token', 'sb-elementor-contact-form-db'); ?>
                                                        </label>
                                                    </th>
                                                    <td class="cool-formkit-table-td">
                                                        <?php if (empty($google_settings['client_token']) && empty($oauth_code)) : ?>
                                                            <?php $auth_url = $instance_api->getClient(); ?>
                                                            <div id="authbtn" style="margin-bottom: 10px;">
                                                                <a href="<?php echo esc_url($auth_url); ?>" id="authlink" target="_blank" class="button button-secondary">
                                                                    <?php esc_html_e('Generate Authentication Token', 'sb-elementor-contact-form-db'); ?>
                                                                </a>
                                                            </div>
                                                        <?php endif; ?>
    
                                                        <div id="authtext">
                                                            <input type="text"
                                                                id="client_token"
                                                                name="client_token"
                                                                class="regular-text cool-formkit-input"
                                                                value="<?php echo esc_attr($google_settings['client_token']); ?>"
                                                                placeholder="Authentication token will appear here"
                                                                <?php echo !empty($google_settings['client_token']) ? 'readonly' : ''; ?> />
                                                            <?php if (!empty($google_settings['client_token'])) : ?>
                                                                <p class="description">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                                                                        <path fill="#11ec38" d="M10 2c-4.42 0-8 3.58-8 8s3.58 8 8 8s8-3.58 8-8s-3.58-8-8-8m-.615 12.66h-1.34l-3.24-4.54l1.341-1.25l2.569 2.4l5.141-5.931l1.34.94z" />
                                                                    </svg>
                                                                    <?php esc_html_e('Token is configured', 'sb-elementor-contact-form-db'); ?>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($google_settings['client_token'])) : ?>
                                                            <input type="submit" name="revoke_token" class="revoke-button revoke-button-secondary"
                                                                value="<?php esc_html_e('Revoke Token', 'sb-elementor-contact-form-db'); ?>"
                                                                onclick="return confirm('<?php esc_attr_e('Are you sure you want to revoke the token?', 'sb-elementor-contact-form-db'); ?>');">
                                                            </input>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
    
                                            <?php $cpfm_opt_in = get_option('cpfm_opt_in_choice_cool_forms','');
                                                if ($cpfm_opt_in) {
                                                    $check_option =  get_option( 'cfef_usage_share_data','');
                                                    if($check_option == 'on'){
                                                        $checked = 'checked';
                                                    }else{
                                                        $checked = '';
                                                    }                
                                                    ?>            
                                                    <tr>
                                                        <th scope="row" class="cool-formkit-table-th">
                                                            <label for="cfef_usage_share_data" class="usage-share-data-label"><?php esc_html_e('Help Improve Plugin', 'sb-elementor-contact-form-db'); ?></label>
                                                        </th>
                                                        <td class="cool-formkit-table-td usage-share-data">
                                                            <input type="checkbox" id="cfef_usage_share_data" name="cfef_usage_share_data" value="on" <?php echo esc_attr($checked) ?>  class="regular-text cool-formkit-input"  />
                                                            <div class="description cool-formkit-description">
                                                                <?php esc_html_e('Help us make this plugin more compatible with your site by sharing non-sensitive site data.', 'sb-elementor-contact-form-db'); ?>
                                                                <a href="#" class="fdbgp-ccpw-see-terms">[<?php esc_html_e('See terms', 'sb-elementor-contact-form-db'); ?>]</a>
                            
                                                                <div id="termsBox" style="display: none; padding-left: 20px; margin-top: 10px; font-size: 12px; color: #999;">
                                                                    <p>
                                                                        <?php esc_html_e('Opt in to receive email updates about security improvements, new features, helpful tutorials, and occasional special offers. We\'ll collect:', 'sb-elementor-contact-form-db'); ?>
                                                                        <a href="https://my.coolplugins.net/terms/usage-tracking/" target="_blank">Click Here</a>
    
                                                                    </p>
                                                                    <ul style="list-style-type: auto;">
                                                                        <li><?php esc_html_e('Your website home URL and WordPress admin email.', 'sb-elementor-contact-form-db'); ?></li>
                                                                        <li><?php esc_html_e('To check plugin compatibility, we will collect the following: list of active plugins and themes, server type, MySQL version, WordPress version, memory limit, site language and database prefix.', 'sb-elementor-contact-form-db'); ?></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                            <?php } ?>
    
                                        </table>
                                    </div>
    
                                    <?php wp_nonce_field('fdbgp_settings_action', 'fdbgp_settings_nonce'); ?>
                                    <button type="submit" name="save_google_settings" class="button button-primary">
                                        <?php esc_html_e('Save Settings', 'sb-elementor-contact-form-db'); ?>
                                    </button>
                                    <?php if (!empty($google_settings['client_id']) || !empty($google_settings['client_secret'])) : ?>
                                        <button type="submit" name="reset_google_settings" class="button button-secondary"
                                            onclick="return confirm('<?php esc_attr_e('Are you sure you want to reset all Google API settings?', 'sb-elementor-contact-form-db'); ?>');">
                                            <?php esc_html_e('Reset Google Settings', 'sb-elementor-contact-form-db'); ?>
                                        </button>
                                    <?php endif; ?>
    
                                </div>
    
                            </form>
                        </div>
                        <?php $this->render_review_request(); ?>
                    </div>
                    
                    <div class="fdbgp-card fdbgp-right">
                        <div class="fdbgp-card-wrapper">
                            <h2 class="fdbgp-card-title">
                                <span class="fdbgp-icon">🎓</span> Google API Configuration Instructions
                            </h2>
    
                            <div class="fdbgp-steps">
                                <div class="fdbgp-step">
                                    <div class="fdbgp-step-number">1</div>
                                    <div class="fdbgp-step-content">
                                        <h3>Go to</h3>
                                        <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console →</a>
                                    </div>
                                </div>
    
                                <div class="fdbgp-step">
                                    <div class="fdbgp-step-number">2</div>
                                    <div class="fdbgp-step-content">
                                        <h3>Create project</h3>
                                        <p>Create a new project or select existing one.</p>
                                    </div>
                                </div>
    
                                <div class="fdbgp-step">
                                    <div class="fdbgp-step-number">3</div>
                                    <div class="fdbgp-step-content">
                                        <h3>Google API</h3>
                                        <p>Enable Google Sheets API and Google Drive API.</p>
                                    </div>
                                </div>
    
                                <div class="fdbgp-step">
                                    <div class="fdbgp-step-number">4</div>
                                    <div class="fdbgp-step-content">
                                        <h3>Add Authorized Domain</h3>
                                        <code id="auth-domain"><?php echo esc_html($site_domain); ?></code>
                                        <button type="button" class="button button-small copy-btn" data-clipboard-target="#auth-domain">
                                            <?php esc_html_e('Copy', 'sb-elementor-contact-form-db'); ?>
                                        </button>
                                    </div>
                                </div>
    
                                <div class="fdbgp-step">
                                    <div class="fdbgp-step-number">5</div>
                                    <div class="fdbgp-step-content">
                                        <h3>Add Authorized Redirect URI</h3>
                                        <code id="redirect-uri"><?php echo esc_url($redirect_uri); ?></code>
                                        <button type="button" class="button button-small copy-btn" data-clipboard-target="#redirect-uri">
                                            <?php esc_html_e('Copy', 'sb-elementor-contact-form-db'); ?>
                                        </button>
                                    </div>
                                </div>
    
                                <div class="fdbgp-step">
                                    <div class="fdbgp-step-number">6</div>
                                    <div class="fdbgp-step-content">
                                        <h3>Google OAuth</h3>
                                        <p>Create OAuth 2.0 credentials.</p>
                                    </div>
                                </div>
    
                            </div>

                            <hr>
                            <div class="fdbgp-help-box">
                                <h4>NEED HELP & SETUP GUIDANCE?</h4>
                                <div class="button-groups">
                                    <a href="https://docs.coolplugins.net/doc/formsdb-video-tutorials/?utm_source=formsdb&utm_medium=inside&utm_campaign=docs&utm_content=setting_page_sidebar" target="_blank" rel="noopener noreferrer" class="button button-primary" style="width: 49%;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#f9f9f9ff" style="vertical-align: middle; margin-right: 4px;"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg> Video Tutorial</a>
                                    <a href="https://docs.coolplugins.net/doc/google-api-setup-connect-elementor-google-sheets/?utm_source=formsdb&utm_medium=inside&utm_campaign=docs&utm_content=setting_page_sidebar" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="width: 49%;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#000" style="vertical-align: middle; margin-right: 4px;"><path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zM3 6h8v12H3V6zm10 12V6h8v12h-8z"/><path d="M14 8h6v2h-6zM14 11h6v2h-6zM14 14h4v2h-4z"/></svg> Read Docs</a>
                                </div>
                            </div>

                        </div>  
                        
                        <?php
                        // Check if Cool Formkit plugin is active (only cool-formkit, not extensions)
                        if ( ! function_exists( 'is_plugin_active' ) ) {
                            require_once ABSPATH . 'wp-admin/includes/plugin.php';
                        }
                        $is_cool_formkit_active = is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' );
                        $is_extensions_active = is_plugin_active( 'extensions-for-elementor-form/extensions-for-elementor-form.php' );
                        
                        if ( ! $is_cool_formkit_active ) :
                        ?>
                        <div class="fdbgp-card-wrapper cool-formkit-card">
                            <h2 class="fdbgp-card-title">
                                <span class="fdbgp-icon">💎</span><?php esc_html_e('Cool Formkit', 'sb-elementor-contact-form-db'); ?>
                            </h2>
                            <p><?php esc_html_e('Extend Elementor Forms and take them to the next level.', 'sb-elementor-contact-form-db'); ?></p>
                            <ul>
                                <li><span class="fdbgp-icon">✔️</span><?php esc_html_e('Add Conditional Fields to Form.', 'sb-elementor-contact-form-db'); ?></li>
                                <li><span class="fdbgp-icon">✔️</span><?php esc_html_e('Advanced Form Builder for Elementor.', 'sb-elementor-contact-form-db'); ?></li>
                                <li><span class="fdbgp-icon">✔️</span><?php esc_html_e('Spam Blocker & Advanced Actions After Submit.', 'sb-elementor-contact-form-db'); ?></li>
                            </ul>
                            <a href="https://coolformkit.com/?utm_source=formsdb&utm_medium=inside&utm_campaign=upgrade&utm_content=setting_page_sidebar" class="button button-primary" target="_blank" style="width: 100%;text-align: center;padding:10px;"><?php esc_html_e('Get Cool Formkit', 'sb-elementor-contact-form-db'); ?></a>
                        </div>
                        <?php endif; ?>

                        <?php
                        // Check if Conditional Fields plugin (free or pro) or extensions or cool-formkit is active
                        $cf_plugin_file = 'conditional-fields-for-elementor-form/class-conditional-fields-for-elementor-form.php';
                        $cf_pro_plugin_file = 'conditional-fields-for-elementor-form-pro/class-conditional-fields-for-elementor-form-pro.php';
                        $extensions_plugin_file = 'extensions-for-elementor-form/extensions-for-elementor-form.php';
                        $is_cf_plugin_active = is_plugin_active( $cf_plugin_file ) || is_plugin_active( $cf_pro_plugin_file );
                        
                        // Check if extensions plugin is installed (even if not active)
                        $all_plugins = get_plugins();
                        $is_extensions_installed = isset( $all_plugins[ $extensions_plugin_file ] );
                        
                        // Hide card if any related plugin is active OR if extensions is installed
                        if ( !$is_cf_plugin_active && !$is_extensions_active && !$is_cool_formkit_active && !$is_extensions_installed ) :
                        ?>
                        <div class="fdbgp-card-wrapper">
                            <h2 class="fdbgp-card-title">
                                <span class="fdbgp-icon">💡</span><?php esc_html_e('Did you know?', 'sb-elementor-contact-form-db'); ?>
                            </h2>
                            <p><?php esc_html_e('You can now conditionally hide or show form fields using Conditional Fields for Elementor forms.', 'sb-elementor-contact-form-db'); ?></p>
                            <div class="button-groups">
                                <?php
                                // Check if pro plugin exists on site, prioritize pro over free
                                $is_cf_pro_installed = isset($all_plugins[$cf_pro_plugin_file]);
                                $is_cf_free_installed = isset($all_plugins[$cf_plugin_file]);
                                
                                // Use pro plugin if it exists, otherwise use free
                                if ( $is_cf_pro_installed ) {
                                    $plugin_file = $cf_pro_plugin_file;
                                    $plugin_slug = 'conditional-fields-for-elementor-form-pro';
                                    $action = 'activate';
                                    $button_text = __('Activate Pro', 'sb-elementor-contact-form-db');
                                } else {
                                    $plugin_file = $cf_plugin_file;
                                    $plugin_slug = 'conditional-fields-for-elementor-form';
                                    $action = $is_cf_free_installed ? 'activate' : 'install';
                                    $button_text = $is_cf_free_installed ? __('Activate Now', 'sb-elementor-contact-form-db') : __('Install Now', 'sb-elementor-contact-form-db');
                                }
                                ?>
                                <button class="button button-primary fdbgp-install-active-btn" 
                                    style="width: 49%;" 
                                    data-action="<?php echo esc_attr($action); ?>" 
                                    data-slug="<?php echo esc_attr($plugin_slug); ?>" 
                                    data-init="<?php echo esc_attr($plugin_file); ?>">
                                    <?php echo esc_html($button_text); ?>
                                </button>
                                <a href="https://docs.coolplugins.net/plugin/conditional-fields-for-elementor-form/?utm_source=formsdb&utm_medium=inside&utm_campaign=upgrade&utm_content=setting_page_sidebar" class="button button-secondary" target="_blank" style="width: 49%;text-align: center;"><?php esc_html_e('Read Docs', 'sb-elementor-contact-form-db'); ?></a>
                        </div>
                        <?php endif; ?>     

                    </div>
                </div>
            </div>
            </div>
            <?php
        }
    }
}