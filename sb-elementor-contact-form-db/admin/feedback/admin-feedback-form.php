<?php
namespace Formsdb_Elementor_Forms\Admin\feedback;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

class fdbgp_feedback {

		private $plugin_url     = FDBGP_PLUGIN_URL;
		private $plugin_version = FDBGP_PLUGIN_VERSION;
		private $plugin_name    = 'FormsDB For Elementor Forms';
		private $plugin_slug    = 'fdbgp';
		private $installation_date_option = 'fdbgp-installDate';
		private $review_option = 'fdbgp_elementor_notice_dismiss';
		private $review_link = 'https://wordpress.org/support/plugin/sb-elementor-contact-form-db/reviews/#new-post';
		private $has_old_submissions = false;

	/*
	|-----------------------------------------------------------------|
	|   Use this constructor to fire all actions and filters          |
	|-----------------------------------------------------------------|
	*/
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_feedback_scripts' ) );
		add_action( 'admin_head', array( $this, 'show_deactivate_feedback_popup' ) );
		add_action( 'wp_ajax_' . $this->plugin_slug . '_submit_deactivation_response', array( $this, 'submit_deactivation_response' ) );
		add_action( 'admin_notices', array( $this, 'fdbgp_admin_notice_for_review' ) );
		add_action( 'wp_ajax_' . $this->plugin_slug . '_dismiss_notice', array( $this, 'fdbgp_dismiss_review_notice' ) );

		if (!class_exists('\FDBGP_Old_Submission')) {
            require_once FDBGP_PLUGIN_DIR . 'includes/class-fdbgp-old-submission.php';
        }
        $this->has_old_submissions = \FDBGP_Old_Submission::has_old_submissions();
	}

	public function fdbgp_dismiss_review_notice(){
		$rs = update_option( $this->review_option, 'yes' );
		echo json_encode( array( 'success' => 'true' ) );
		exit;
	}

	public function fdbgp_admin_notice_for_review(){
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		// get installation dates and rated settings
		$installation_date = get_option( $this->installation_date_option );
		$alreadyRated      = get_option( $this->review_option ) != false ? get_option( $this->review_option ) : 'no';


		// check user already rated
		if ( $alreadyRated == 'yes' ) {
			return;
		}

		// grab plugin installation date and compare it with current date
		$display_date = gmdate( 'Y-m-d h:i:s' );
		$install_date = new \DateTime( $installation_date );
		$current_date = new \DateTime( $display_date );
		$difference   = $install_date->diff( $current_date );
		$diff_days    = $difference->days;

		// check if installation days is greator then week

		if ( isset( $diff_days ) && $diff_days >= 3 && !$this->has_old_submissions) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
			echo $this->fdbgp_create_notice_content();
		}
	}

	function fdbgp_create_notice_content() {
		$html = '
		<div data-ajax-url="' . admin_url( 'admin-ajax.php' ) . '" data-ajax-callback="' . sanitize_text_field($this->plugin_slug). '_dismiss_notice" class="' . sanitize_text_field($this->plugin_slug) . '-review-notice-wrapper notice notice-info is-dismissible">
			
			<div class="message_container">
				<p>Thanks for using <b>FormsDB for Elementor Forms</b> WordPress plugin. We hope it meets your expectations!<br/>Please give us a quick rating, it works as a boost for us to keep working on more <a href="https://coolplugins.net" target="_blank"><strong>Cool Plugins</strong></a>!</p>
				<ul>
					<li><a href="' . esc_url( $this->review_link ) . '" class="rate-it-btn button button-primary" target="_blank" title="Submit A Review...">Rate Now! ★★★★★</a></li>
					<li><a href="javascript:void(0);" class="already-rated-btn button button-secondary ' . sanitize_text_field($this->plugin_slug) . '_dismiss_notice" title="Already Rated - Close This Notice!">Already Rated</a></li>
					<li><a href="javascript:void(0);" class="already-rated-btn button button-secondary ' . sanitize_text_field($this->plugin_slug) . '_dismiss_notice" title="Not Interested - Close This Notice!">Not Interested</a></li>
				</ul>
			</div>
		</div>
		';

		return $html;
	}
	/*
	|-----------------------------------------------------------------|
	|   Enqueue all scripts and styles to required page only          |
	|-----------------------------------------------------------------|
	*/
	function enqueue_feedback_scripts() {
		$screen = get_current_screen();
		if ( isset( $screen ) && $screen->id == 'plugins' ) {
		wp_enqueue_script( __NAMESPACE__ . 'feedback-script', $this->plugin_url . 'admin/feedback/js/admin-feedback.js', array( 'jquery' ), $this->plugin_version, true );
			wp_enqueue_style( 'cool-plugins-feedback-css', $this->plugin_url . 'admin/feedback/css/admin-feedback.css', null, $this->plugin_version );
		}

		wp_enqueue_style( 'fdbgp-admin-review-notice-css', $this->plugin_url . 'admin/feedback/css/fdbgp-admin-review-notice.css', null, $this->plugin_version );

		wp_enqueue_script( 'fdbgp-admin-review-notice-js', $this->plugin_url . 'admin/feedback/js/fdbgp-admin-review-notice.js', array( 'jquery' ), $this->plugin_version , false);
	}

	function fdbgp_get_user_info() {
        global $wpdb;
        // Server and WP environment details
        $server_info = [
            'server_software'        => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'N/A',
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query is incrementally prepared above.
            'mysql_version'          => $wpdb ? sanitize_text_field($wpdb->get_var("SELECT VERSION()")) : 'N/A',
            'php_version'            => sanitize_text_field(phpversion() ?: 'N/A'),
            'wp_version'             => sanitize_text_field(get_bloginfo('version') ?: 'N/A'),
            'wp_debug'               => (defined('WP_DEBUG') && WP_DEBUG) ? 'Enabled' : 'Disabled',
            'wp_memory_limit'        => sanitize_text_field(ini_get('memory_limit') ?: 'N/A'),
            'wp_max_upload_size'     => sanitize_text_field(ini_get('upload_max_filesize') ?: 'N/A'),
            'wp_permalink_structure' => sanitize_text_field(get_option('permalink_structure') ?: 'Default'),
            'wp_multisite'           => is_multisite() ? 'Enabled' : 'Disabled',
            'wp_language'            => sanitize_text_field(get_option('WPLANG') ?: get_locale()),
            'wp_prefix'              => isset($wpdb->prefix) ? sanitize_key($wpdb->prefix) : 'N/A',
        ];
        // Theme details
        $theme = wp_get_theme();
        $theme_data = [
            'name'      => sanitize_text_field($theme->get('Name')),
            'version'   => sanitize_text_field($theme->get('Version')),
            'theme_uri' => esc_url($theme->get('ThemeURI')),
        ];
        // Ensure plugin functions are loaded
        if ( ! function_exists('get_plugins') ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        // Active plugins details
        $active_plugins = get_option('active_plugins', []);
        $plugin_data = [];
        foreach ( $active_plugins as $plugin_path ) {
            $plugin_info = get_plugin_data(WP_PLUGIN_DIR . '/' . sanitize_text_field($plugin_path));
            $plugin_data[] = [
                'name'       => sanitize_text_field($plugin_info['Name']),
                'version'    => sanitize_text_field($plugin_info['Version']),
                'plugin_uri' => esc_url($plugin_info['PluginURI']),
            ];
        }
        return [
            'server_info'   => $server_info,
            'extra_details' => [
                'wp_theme'       => $theme_data,
                'active_plugins' => $plugin_data,
            ],
        ];
    }

	/*
	|-----------------------------------------------------------------|
	|   HTML for creating feedback popup form                         |
	|-----------------------------------------------------------------|
	*/
	public function show_deactivate_feedback_popup() {
		$screen = get_current_screen();
		if ( ! isset( $screen ) || $screen->id != 'plugins' ) {
			return;
		}
		$deactivate_reasons = array(
			'didnt_work_as_expected'         => array(
				'title'             => __( 'The plugin didn\'t work as expected.', 'sb-elementor-contact-form-db' ),
				'input_placeholder' => 'What did you expect?',
			),
			'found_a_better_plugin'          => array(
				'title'             => __( 'I found a better plugin.', 'sb-elementor-contact-form-db' ),
				'input_placeholder' => __( 'Please share which plugin.', 'sb-elementor-contact-form-db' ),
			),
			'couldnt_get_the_plugin_to_work' => array(
				'title'             => __( 'The plugin is not working.', 'sb-elementor-contact-form-db' ),
				'input_placeholder' => 'Please share your issue. So we can fix that for other users.',
			),
			'temporary_deactivation'         => array(
				'title'             => __( 'It\'s a temporary deactivation.', 'sb-elementor-contact-form-db' ),
				'input_placeholder' => '',
			),
			'other'                          => array(
				'title'             => __( 'Other reason.', 'sb-elementor-contact-form-db' ),
				'input_placeholder' => __( 'Please share the reason.', 'sb-elementor-contact-form-db' ),
			),
		);

		?>
		<div id="cool-plugins-feedback-<?php echo esc_attr( $this->plugin_slug ); ?>" class="hide-feedback-popup">
						
			<div class="cp-feedback-wrapper">

			<div class="cp-feedback-header">
				<div class="cp-feedback-title"><?php echo esc_html__( 'Quick Feedback', 'sb-elementor-contact-form-db' ); ?></div>
				<div class="cp-feedback-title-link">A plugin by <a href="https://coolplugins.net/?utm_source=<?php echo esc_attr( $this->plugin_slug ); ?>_plugin&utm_medium=inside&utm_campaign=coolplugins&utm_content=deactivation_feedback" target="_blank">CoolPlugins.net</a></div>
			</div>

			<div class="cp-feedback-loader">
				<img src="<?php echo esc_url( $this->plugin_url ); ?>admin/feedback/images/cool-plugins-preloader.gif">
			</div>

			<div class="cp-feedback-form-wrapper">
				<div class="cp-feedback-form-title"><?php echo esc_html__( 'If you have a moment, please share the reason for deactivating this plugin.', 'sb-elementor-contact-form-db' ); ?></div>
				<form class="cp-feedback-form" method="post">
					<?php
					wp_nonce_field( '_cool-plugins_deactivate_feedback_nonce' );
					?>
					<input type="hidden" name="action" value="cool-plugins_deactivate_feedback" />
					
					<?php foreach ( $deactivate_reasons as $reason_key => $reason ) : ?>
						<div class="cp-feedback-input-wrapper">
							<input id="cp-feedback-reason-<?php echo esc_attr( $reason_key ); ?>" class="cp-feedback-input" type="radio" name="reason_key" value="<?php echo esc_attr( $reason_key ); ?>" />
							<label for="cp-feedback-reason-<?php echo esc_attr( $reason_key ); ?>" class="cp-feedback-reason-label"><?php echo esc_html( $reason['title'] ); ?></label>
							<?php if ( ! empty( $reason['input_placeholder'] ) ) : ?>
								<textarea class="cp-feedback-text" type="textarea" name="reason_<?php echo esc_attr( $reason_key ); ?>" placeholder="<?php echo esc_attr( $reason['input_placeholder'] ); ?>"></textarea>
							<?php endif; ?>
							<?php if ( ! empty( $reason['alert'] ) ) : ?>
								<div class="cp-feedback-text"><?php echo esc_html( $reason['alert'] ); ?></div>
							<?php endif; ?>	
						</div>
					<?php endforeach; ?>
					
					<div class="cp-feedback-terms">
					<input class="cp-feedback-terms-input" id="cp-feedback-terms-input" type="checkbox"><label for="cp-feedback-terms-input"><?php echo esc_html__( 'I agree to share anonymous usage data and basic site details (such as server, PHP, and WordPress versions) to support FormsDB For Elementor Forms improvement efforts. Additionally, I allow Cool Plugins to store all information provided through this form and to respond to my inquiry', 'sb-elementor-contact-form-db' ); ?></label>
					</div>

					<div class="cp-feedback-button-wrapper">
						<a class="cp-feedback-button cp-submit" id="cool-plugin-submitNdeactivate">Submit and Deactivate</a>
						<a class="cp-feedback-button cp-skip" id="cool-plugin-skipNdeactivate">Skip and Deactivate</a>
					</div>
				</form>
			</div>


		   </div>
		</div>
		<?php
	}


	function submit_deactivation_response() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), '_cool-plugins_deactivate_feedback_nonce' ) || ! current_user_can( 'manage_options' )) {
			wp_send_json_error();
		} else {
			$reason             = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';
			$deactivate_reasons = array(
				'didnt_work_as_expected'         => array(
					'title'             => __( 'The plugin didn\'t work as expected', 'sb-elementor-contact-form-db' ),
					'input_placeholder' => 'What did you expect?',
				),
				'found_a_better_plugin'          => array(
					'title'             => __( 'I found a better plugin', 'sb-elementor-contact-form-db' ),
					'input_placeholder' => __( 'Please share which plugin.', 'sb-elementor-contact-form-db' ),
				),
				'couldnt_get_the_plugin_to_work' => array(
					'title'             => __( 'The plugin is not working', 'sb-elementor-contact-form-db' ),
					'input_placeholder' => 'Please share your issue. So we can fix that for other users.',
				),
				'temporary_deactivation'         => array(
					'title'             => __( 'It\'s a temporary deactivation.', 'sb-elementor-contact-form-db' ),
					'input_placeholder' => '',
				),
				'other'                          => array(
					'title'             => __( 'Other', 'sb-elementor-contact-form-db' ),
					'input_placeholder' => __( 'Please share the reason.', 'sb-elementor-contact-form-db' ),
				),
			);

			$plugin_initial =  get_option( 'formsdb_initial_version' );

			$deativation_reason = array_key_exists( $reason, $deactivate_reasons ) ? $reason : 'other';

			$deativation_reason = esc_html($deativation_reason);
			$message = isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : '';
			$sanitized_message = empty( $message ) ? 'N/A' : $message;
			$admin_email       = sanitize_email( get_option( 'admin_email' ) );
			$site_url          = esc_url( site_url() );
			$feedback_url      = FDBGP_FEEDBACK_URL.'wp-json/coolplugins-feedback/v1/feedback';
			$install_date 		= get_option('fdbgp-install-date');
			$unique_key     	= '12';
			$site_id        	= $site_url . '-' . $install_date . '-' . $unique_key;
			$response          = wp_remote_post(
				esc_url($feedback_url),
				array(
                    'timeout' => 30,
                        'body'    => array(
                        'server_info' => serialize($this->fdbgp_get_user_info()['server_info']),
                        'extra_details' => serialize($this->fdbgp_get_user_info()['extra_details']),
                        'plugin_initial'  => isset($plugin_initial) ? sanitize_text_field($plugin_initial) : 'N/A',
                        'plugin_version' => sanitize_text_field($this->plugin_version),
                        'plugin_name'    => sanitize_text_field($this->plugin_name),
                        'reason'         => sanitize_text_field($deativation_reason),
                        'review'         => sanitize_textarea_field($sanitized_message),
                        'email'          => sanitize_email($admin_email),
                        'domain'         => esc_url($site_url),
						'site_id'    	 => sanitize_text_field(md5($site_id)),
                    ),
                )
			);

			die( json_encode( array( 'response' => $response ) ) );
		}

	}
}
new fdbgp_feedback();
