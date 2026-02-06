<?php

/*
 * Plugin Name: FormsDB for Elementor Forms
 * Plugin URI:  https://coolplugins.net/product/formsdb-connect-elementor-forms-google-sheets/?utm_source=formsdb&utm_medium=inside&utm_campaign=plugin_page&utm_content=plugins_list
 * Description: Connect Elementor forms with Google Sheets to sync form entries, or save frontend form submissions in any WordPress post type using Elementor Pro or Hello Plus forms.
 * Author:      Cool Plugins
 * Version:     2.1.5
 * Author URI:  https://coolplugins.net/?utm_source=formsdb&utm_medium=inside&utm_campaign=author_page&utm_content=plugins_list
 * Text Domain: sb-elementor-contact-form-db
 * Requires Plugins: elementor
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Elementor tested up to: 3.34.0
 * Elementor Pro tested up to: 3.34.0
 */

namespace Formsdb_Elementor_Forms;
use Formsdb_Elementor_Forms\Admin\CPFM_Feedback_Notice;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}   

define( 'FDBGP_PLUGIN_FILE', __FILE__ );
define( 'FDBGP_PLUGIN_BASENAME', plugin_basename( FDBGP_PLUGIN_FILE ) );
define( 'FDBGP_PLUGIN_DIR', plugin_dir_path( FDBGP_PLUGIN_FILE ) );
define( 'FDBGP_PLUGIN_URL', plugin_dir_url( FDBGP_PLUGIN_FILE ) );
define( 'FDBGP_PLUGIN_VERSION', '2.1.5' );
define('FDBGP_FEEDBACK_URL', 'https://feedback.coolplugins.net/');


register_activation_hook( FDBGP_PLUGIN_FILE, array( 'Formsdb_Elementor_Forms\FDBGP_Main', 'fdbgp_activate' ) );
register_deactivation_hook( FDBGP_PLUGIN_FILE, array( 'Formsdb_Elementor_Forms\FDBGP_Main', 'fdbgp_deactivate' ) );
if(!class_exists('FDBGP_Main')) { 

	class FDBGP_Main {

		private static $instance = null;

		/**
		 * Main FDBGP_Main Instance.
		 *
		 * Ensures only one instance of FDBGP_Main is loaded or can be loaded.
		 *
		 * @return FDBGP_Main - Main instance.
		*/

		public static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * FDBGP_Main Constructor.
		 */
		private function __construct() {
		
			static $autoloader_registered = false;

			if ( ! $autoloader_registered ) {
				$autoloader_registered = spl_autoload_register( [ $this, 'autoload' ] );
			}

			add_action( 'plugins_loaded', array( $this, 'FDBGP_plugins_loaded' ) );
			add_action( 'admin_init', array( $this, 'setting_redirect' ));
			add_filter( 'plugin_row_meta', array( $this, 'fdbgp_plugin_row_meta' ), 10, 2 );
			add_action( 'activated_plugin', array( $this, 'fdbgp_plugin_redirection' ) );

			$this->includes();
			
			add_action( 'init', function () {
				global $wpdb;
				$current_version = get_option( 'formsdb_initial_version' );

				if ( $current_version && version_compare( $current_version, '1.8.1', '>' ) && ! get_option('formdb_initial_version_migration', false) ) {					
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching		
					$post_type_exists = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s LIMIT 1",
								'elementor_cf_db'
						)
					);
	
					if ( $post_type_exists ) {
						update_option( 'formsdb_initial_version', '1.8.1' );
					}						
					update_option('formdb_initial_version_migration', true);
				}
			},20);
		}

		/**
		 * redirection metehod for plugin redirection on plugin activation
		 */
		public function fdbgp_plugin_redirection( $plugin ) {
			if ( $plugin == FDBGP_PLUGIN_BASENAME ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped	
				exit( wp_safe_redirect( admin_url( 'admin.php?page=formsdb' ) ) );
			}
		}

		public function fdbgp_plugin_row_meta( $plugin_meta, $plugin_file ) {
			if ( FDBGP_PLUGIN_BASENAME === $plugin_file ) {
				$row_meta = array(
					'docs' => '<a href="' . esc_url('https://docs.coolplugins.net/plugin/formsdb-for-elementor-forms/?utm_source=formsdb&utm_medium=inside&utm_campaign=docs&utm_content=plugins_list') . '" aria-label="' . esc_attr(esc_html__('View FormsDB Documentation', 'sb-elementor-contact-form-db')) . '" target="_blank">' . esc_html__('Docs', 'sb-elementor-contact-form-db') . '</a>',
				);
				$plugin_meta = array_merge( $plugin_meta, $row_meta );
			}
			return $plugin_meta;
		}

		public function setting_redirect(){
			// Handle OAuth callback
			if ( ! is_user_logged_in() || ! current_user_can('manage_options') ) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if(!isset($_GET['page']) || 'formsdb' !== sanitize_text_field(wp_unslash($_GET['page']))){
				return;
			}

			// Verify state (nonce) returned from Google
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$state = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : '';
			if ( empty($state) || ! wp_verify_nonce($state, 'fdbgp_google_oauth') ) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$code = isset($_GET['code']) && !empty($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : '';
			
			if(!empty($code)){
				// Get Google settings
				$google_settings = get_option(
					'fdbgp_google_settings',
					array(
						'client_id'     => '',
						'client_secret' => '',
						'client_token'  => '',
					)
				);

				// Save token (already sanitized earlier)
				$google_settings['client_token'] = $code;
				update_option( 'fdbgp_google_settings', $google_settings );

				// Clean redirect URL safely
				$redirect_url = remove_query_arg(
					array( 'code', 'scope', 'state' )
				);

				wp_safe_redirect( $redirect_url );
				exit;

			}
		}

		public function FDBGP_plugins_loaded() {
			if (!get_option( 'formsdb_initial_version' ) ) {
                add_option( 'formsdb_initial_version', FDBGP_PLUGIN_VERSION );
            }
			
			// Add plugin dashboard link
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'FDBGP_plugin_dashboard_link' ) );




			// Get the loader instance
			\FDBGP_Loader::get_instance();

			if ( did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Plugin' ) ) {

				require_once FDBGP_PLUGIN_DIR . '/admin/marketing/fdbgp-marketing-common.php';
			}

		}

		public function FDBGP_plugin_dashboard_link($links){
			$settings_link = '<a href="' . admin_url( 'admin.php?page=formsdb' ) . '">Settings</a>';
			array_unshift( $links,  $settings_link );

			return $links;
		}

		private function includes() {
			if(!class_exists('Formsdb_Elementor_Forms\Admin\CPFM_Feedback_Notice')){
				require_once FDBGP_PLUGIN_DIR . 'admin/feedback/cpfm-common-notice.php';
			}

			require_once FDBGP_PLUGIN_DIR . 'includes/class-fdbgp-loader.php';
			require_once FDBGP_PLUGIN_DIR . 'includes/class-fdbgp-cache-manager.php';
			// Load old submission handler globally
			require_once FDBGP_PLUGIN_DIR . 'includes/class-fdbgp-old-submission.php';

			if ( is_admin() ) {
				require_once FDBGP_PLUGIN_DIR . 'admin/feedback/admin-feedback-form.php';
			}
			require_once FDBGP_PLUGIN_DIR . 'admin/feedback/cron/fdbgp-class-cron.php';
		}

		public function autoload( $class_name ) {
			if ( 0 !== strpos( $class_name, __NAMESPACE__ ) ) {
				return;
			}
			$has_class_alias = isset( $this->classes_aliases[ $class_name ] );

			// Backward Compatibility: Save old class name for set an alias after the new class is loaded
			if ( $has_class_alias ) {
				$class_alias_name = $this->classes_aliases[ $class_name ];
				$class_to_load = $class_alias_name;
			} else {
				$class_to_load = $class_name;
			}
			
			if ( ! class_exists( $class_to_load ) ) {
				$filename = strtolower(
					preg_replace(
						[ '/^' . __NAMESPACE__ . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
						[ '', '$1-$2', '-', DIRECTORY_SEPARATOR ],
						$class_to_load
					)
				);


				$filename = trailingslashit( FDBGP_PLUGIN_DIR ) . $filename . '.php';


				if ( is_readable( $filename ) ) {
					include $filename;
				}
			}

			if ( $has_class_alias ) {
				class_alias( $class_alias_name, $class_name );
			}
		}

		public static function fdbgp_activate() {
			update_option( 'fdbgp-v', FDBGP_PLUGIN_VERSION );
			update_option( 'fdbgp-type', 'FREE' );
			update_option( 'fdbgp-installDate', gmdate( 'Y-m-d h:i:s' ) );

			if (!get_option( 'formsdb_initial_version' ) ) {
                add_option( 'formsdb_initial_version', FDBGP_PLUGIN_VERSION );
            }

			if(!get_option( 'fdbgp-install-date' ) ) {
				add_option( 'fdbgp-install-date', gmdate('Y-m-d h:i:s') );
        	}


			$settings       = get_option('cfef_usage_share_data');

			
			if (!empty($settings) || $settings === 'on'){
				
				static::fdbgp_cron_job_init();
			}
		}

		public static function fdbgp_cron_job_init()
		{
			if (!wp_next_scheduled('fdbgp_extra_data_update')) {
				wp_schedule_event(time(), 'every_30_days', 'fdbgp_extra_data_update');
			}
		}


		/**
		 * Function run on plugin deactivate
		 */
		public static function fdbgp_deactivate() {

			if (wp_next_scheduled('fdbgp_extra_data_update')) {
            	wp_clear_scheduled_hook('fdbgp_extra_data_update');
        	}
		}

	}

	FDBGP_Main::get_instance();

}
