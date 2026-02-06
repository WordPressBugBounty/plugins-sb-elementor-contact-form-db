<?php

if (!defined('ABSPATH')) {
    die;
}

if(!class_exists('HelloPlus_Widget_Loader')) { 
class HelloPlus_Widget_Loader {

    protected $plugin_name;

    public $actions_registrar_helloplus;
    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The loader instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      HelloPlus_Widget_Loader    $instance    The loader instance.
     */
    private static $instance = null;

    public function __construct() {
        $this->version = FDBGP_PLUGIN_VERSION;

        if(is_plugin_active( 'hello-plus/hello-plus.php' )){
            add_action('plugins_loaded',function(){
                if ( class_exists( 'HelloPlus\Modules\Forms\Module' ) ) {
                    $forms_module = \HelloPlus\Modules\Forms\Module::instance();
                    if ( $forms_module && isset( $forms_module->actions_registrar ) ) {
                        $this->actions_registrar_helloplus = $forms_module->actions_registrar;
                    }
                }
            });

            if(! is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' ) && ! is_plugin_active( 'extensions-for-elementor-form/extensions-for-elementor-form.php' )){
                add_action('elementor/element/ehp-form/section_integration/after_section_end',array($this,'show_actions_on_editor_side') , 10, 2 );
            }else{
                // hook for compatibility with cool formkit pro plugin
                add_action('elementor/element/ehp-form/section_integration/after_section_end',array($this,'show_actions_on_editor_side_in_delay') , 20, 2 );
            }
            
            $this->load_actions();
        }
    }

    public function load_actions() {
        $is_conflicting_active = is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' ) || is_plugin_active( 'extensions-for-elementor-form/extensions-for-elementor-form.php' );

        require_once FDBGP_PLUGIN_DIR . 'includes/widgets/helloplus-modules/helloplus-fdbgp-form-register-post.php';
        require_once FDBGP_PLUGIN_DIR . 'includes/widgets/helloplus-modules/helloplus-fdbgp-form-sheets-action.php';
        require_once FDBGP_PLUGIN_DIR . 'includes/widgets/helloplus-modules/action/collect-entries.php';
        require_once FDBGP_PLUGIN_DIR . 'includes/widgets/helloplus-modules/action/save-form-data.php';

        if ( ! $is_conflicting_active ) {
            new Save_Form_Data();
        }

        if ( class_exists( 'HelloPlus\Modules\Forms\Module' ) ) {
            $forms_module = \HelloPlus\Modules\Forms\Module::instance();
            if ( $forms_module && isset( $forms_module->actions_registrar ) ) {
                $forms_module->actions_registrar->register( new HelloPlus_FDBGP_Register_Post() );
                if ( ! $is_conflicting_active ) {
                    $forms_module->actions_registrar->register( new HelloPlus_Collect_Entries() );
                }
                $forms_module->actions_registrar->register( new HelloPlus_FDBGP_Form_Sheets_Action() );
            }
        }
    }

    public function show_actions_on_editor_side( $element, $args ) {
        require_once FDBGP_PLUGIN_DIR . 'includes/widgets/helloplus-modules/helloplus-fdbgp-form-register-post.php';
        require_once FDBGP_PLUGIN_DIR . 'includes/widgets/helloplus-modules/helloplus-fdbgp-form-sheets-action.php';

        $instance = new HelloPlus_FDBGP_Register_Post();
        $custom_actions[ $instance->get_name() ] = $instance->get_label();
        $action_instances[] = $instance;        

        $instance = new HelloPlus_FDBGP_Form_Sheets_Action();
        $custom_actions[ $instance->get_name() ] = $instance->get_label();
        $action_instances[] = $instance;        

        $element->start_controls_section(
            'cool_formkit_conditional_actions_section',
            [
                'label' => esc_html__( 'Cool Actions After Submit', 'sb-elementor-contact-form-db' ),
            ]
        );

        $element->add_control( 'cool_formkit_submit_actions', [
            'label'       => __( 'Actions After Submit', 'sb-elementor-contact-form-db' ),
            'type'        => \Elementor\Controls_Manager::SELECT2,
            'multiple'    => true,
            'label_block' => true,
            'options'     => $custom_actions,
            'default'     => [ ],
            'render_type' => 'template',
        ] );

        $element->end_controls_section();

        // === 4. Register All Controls with Condition
        foreach ( $action_instances as $instance ) {
            if ( method_exists( $instance, 'register_settings_section' ) ) {
                // Inside each register_settings_section(), use:
                // 'condition' => [ 'cool_formkit_submit_actions' => $this->get_name() ]
                $instance->register_settings_section( $element );
            }
        }
    }

    public function show_actions_on_editor_side_in_delay($element, $args){
        if ( ! method_exists( $element, 'get_controls' ) ) {
            return;
        }

        $controls = $element->get_controls();

        // Ensure the control exists
        if ( empty( $controls['cool_formkit_submit_actions'] ) ) {
            return;
        }

        // Load your action classes
        require_once FDBGP_PLUGIN_DIR . 'includes/widgets/helloplus-modules/helloplus-fdbgp-form-register-post.php';
        require_once FDBGP_PLUGIN_DIR . 'includes/widgets/helloplus-modules/helloplus-fdbgp-form-sheets-action.php';

        $new_actions     = [];
        $action_instances = [];

        $instance = new HelloPlus_FDBGP_Register_Post();
        $new_actions[ $instance->get_name() ] = $instance->get_label();
        $action_instances[] = $instance;

        $instance = new HelloPlus_FDBGP_Form_Sheets_Action();
        $new_actions[ $instance->get_name() ] = $instance->get_label();
        $action_instances[] = $instance;

        // Merge existing + new options
        $options = $controls['cool_formkit_submit_actions']['options'] ?? [];
        $options = array_merge( $options, $new_actions );

        // Update control
        $element->update_control(
            'cool_formkit_submit_actions',
            [
                'options' => $options,
            ]
        );

        // Register settings sections for your actions
        foreach ( $action_instances as $instance ) {
            if ( method_exists( $instance, 'register_settings_section' ) ) {
                $instance->register_settings_section( $element );
            }
        }
    }

    /**
     * Get the instance of this class.
     *
     * @since    1.0.0
     * @return   CFKEF_Loader    The instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since    1.0.0
     * @return   string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}

}