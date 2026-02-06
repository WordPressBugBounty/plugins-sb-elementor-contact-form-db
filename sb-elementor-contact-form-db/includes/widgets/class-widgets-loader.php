<?php

if (!defined('ABSPATH')) {
    die;
}

use ElementorPro\Plugin;


class FDBGP_Widgets_Loader {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $this->includes();
        add_action( 'elementor_pro/forms/actions/register', array($this,'register_new_form_actions') );
    }

    public function includes(){
        require_once FDBGP_PLUGIN_DIR . 'includes/lib-helpers/class-fdbgp-google-api.php';
        require_once FDBGP_PLUGIN_DIR . 'includes/lib-helpers/class-fdbgp-google-api-functions.php';
    }

    public function register_new_form_actions($form_fields_registrar){
        require_once FDBGP_PLUGIN_DIR . 'includes/widgets/modules/class-fdbgp-form-sheets-action.php';
        $form_fields_registrar->register(new \FDBGP_Form_Sheets_Action());        

        require_once FDBGP_PLUGIN_DIR . 'includes/widgets/modules/class-fdbgp-form-register-post.php';
        $form_fields_registrar->register(new \FDBGP_Register_Post());        
    }
}
