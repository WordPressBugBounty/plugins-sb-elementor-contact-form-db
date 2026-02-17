<?php

use Formsdb_Elementor_Forms\Admin\Register_Menu_Dashboard\FDBGP_Dashboard;
use Formsdb_Elementor_Forms\Admin\Entries\FDBGP_Entries_Posts;

if (!defined('ABSPATH')) {
    die;
}

if(!class_exists('FDBGP_Loader')) {

    class FDBGP_Loader {

        protected $plugin_name;
        protected $version;
        private static $instance = null;
    
        private function __construct() {

            $this->plugin_name = 'formsdb-elementor-google-sheets-posttype';
            $this->version = FDBGP_PLUGIN_VERSION;
    
            $this->admin_menu_dashboard();
    
            $this->load_dependencies();
        }

        public static function get_instance() {
            if (null == self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function load_dependencies() {
            require_once FDBGP_PLUGIN_DIR . 'admin/class-fdbgp-admin.php';
            FDBGP_Admin::get_instance($this->get_plugin_name(), $this->get_version());

            require_once FDBGP_PLUGIN_DIR . 'includes/lib-helpers/trait-fdbgp-ajax-handlers.php';
          
            require_once FDBGP_PLUGIN_DIR . 'includes/widgets/class-widgets-loader.php';
            new FDBGP_Widgets_Loader($this->get_plugin_name(), $this->get_version());

            require_once FDBGP_PLUGIN_DIR . 'includes/widgets/helloplus-widget-loader.php';
            new HelloPlus_Widget_Loader();

            require_once FDBGP_PLUGIN_DIR . 'includes/widgets/coolform-widget-loader.php';
            new CoolForm_Widget_Loader();
        }

        private function admin_menu_dashboard() {
            if(class_exists(FDBGP_Dashboard::class)){
                FDBGP_Dashboard::get_instance($this->get_plugin_name(), $this->get_version());
            }
            if(class_exists(FDBGP_Entries_Posts::class)){
                FDBGP_Entries_Posts::get_instance();
            }
        }

        public function get_plugin_name() {
            return $this->plugin_name;
        }

        public function get_version() {
            return $this->version;
        }

    }
     
    
}