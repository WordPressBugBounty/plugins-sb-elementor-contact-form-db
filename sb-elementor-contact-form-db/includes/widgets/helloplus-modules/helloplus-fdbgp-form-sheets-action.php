<?php
if ( ! defined( 'ABSPATH' ) ) {
    die;
}

/**
 * Custom elementor form action after submit to add records to
 * Google Spreadsheet
 *
 * @since 1.0.0
 */

use Elementor\Controls_Manager;
use HelloPlus\Modules\Forms\Classes\Action_Base;
use Formsdb_Elementor_Forms\Lib_Helpers\FDBGP_Google_API_Functions;
use Formsdb_Elementor_Forms\Lib_Helpers\FDBGP_Ajax_Handlers;

/**
 * Register Custom Control
 */
add_action( 'elementor/controls/register', function ( $controls_manager ) {
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound	
    class HelloPlus_FDBGP_Control_Dynamic_Select2 extends \Elementor\Base_Data_Control {
        public function get_type() {
            return 'fdbgp_dynamic_select2';
        }

        public function content_template() {
            ?>
            <div class="elementor-control-field">
                <label class="elementor-control-title">{{{ data.label }}}</label>
                <div class="elementor-control-input-wrapper">
                    <select class="elementor-control-dynamic-select2" data-setting="{{ data.name }}" multiple="multiple" style="width:100%">
                        <# console.log(data) #>
                        <# if ( data.options ) { 
                            _.each( data.options, function( option_title, option_value ) { #>
                                <option value="{{ option_value }}">{{{ option_title }}}</option>
                            <# } );
                        } #>
                    </select>
                </div>
                <div class="elementor-control-field-description">{{{ data.description }}}</div>
            </div>
            <?php
        }
    }
    
    $controls_manager->register( new HelloPlus_FDBGP_Control_Dynamic_Select2() );
} );

/**
 * Class HelloPlus_FDBGP_Form_Sheets_Action
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
class HelloPlus_FDBGP_Form_Sheets_Action extends Action_Base {
    
    use FDBGP_Ajax_Handlers;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX actions
        $this->register_ajax_events();
        add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'render_editor_script' ] );
    }
    
    private static $registered_actions = [];


    public static function elementor() {
		return Elementor\Plugin::$instance;
	}
    /**
     * Get Name
     * Return the action name
     *
     * @access public
     * @return string
     */
    public function get_name() : string {
        return esc_html( 'Save Submissions in Google Sheet' );
    }

    /**
     * Get Label
     * Returns the action label
     *
     * @access public
     * @return string
     */
    public function get_label() : string{
        return esc_html__( 'Save Submissions in Google Sheet', 'sb-elementor-contact-form-db' );
    }

    /**
     * Add Prefix
     * Adds prefix to avoid conflicts
     *
     * @access public
     * @param string $id id.
     * @return string
     */
    public function add_prefix( $id ) {
        return 'fdbgp_' . $id;
    }

    /**
     * Render Editor Script
     */
    public function render_editor_script() {
        wp_enqueue_script(
            'fdbgp-editor-script', // Unique Handle
            FDBGP_PLUGIN_URL . 'assets/js/fdbgp-editor.min.js', // Path to your new JS file
            [ 'elementor-editor', 'jquery' ], // Dependencies
            FDBGP_PLUGIN_VERSION, // Version
            true // Load in footer
        );
    }
    
    /**
     * Run
     * Runs the action after submit
     *
     * @access public
     * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record Record.
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler Ajax handler.
     */
    public function run( $record, $ajax_handler ) {

        $fdbgp_settings   = $record->get( 'form_settings' );
        $fdbgp_raw_fields = $record->get( 'fields' );
        $instance_api     = new FDBGP_Google_API_Functions();

        if ( ! $instance_api->checkcredenatials() ) {
            return;
        }

        if ( isset( $fdbgp_settings['cool_formkit_submit_actions'] ) && in_array( $this->get_name(), $fdbgp_settings['cool_formkit_submit_actions'], true ) ) {
            
            $fdbgp_spreadsheetid = $fdbgp_settings[ $this->add_prefix( 'spreadsheetid' ) ];
            
            if ( 'new' === $fdbgp_spreadsheetid ) {
                $fdbgp_sheetname = $fdbgp_settings[ $this->add_prefix( 'sheet_name' ) ];
            } else {
                $fdbgp_sheetname = isset( $fdbgp_settings[ $this->add_prefix( 'sheet_list' ) ] ) ? $fdbgp_settings[ $this->add_prefix( 'sheet_list' ) ] : '';
                // Fallback to text field if list is empty (backward compatibility)
                if ( empty( $fdbgp_sheetname ) && isset( $fdbgp_settings[ $this->add_prefix( 'sheet_name' ) ] ) ) {
                    $fdbgp_sheetname = $fdbgp_settings[ $this->add_prefix( 'sheet_name' ) ];
                }
            }
            
            $is_newly_created = false;
            
            // Handle new spreadsheet creation
            if ( $fdbgp_spreadsheetid === 'new' ) {
                try {
                    $fdbgp_new_spreadsheet_name = isset( $fdbgp_settings[ $this->add_prefix( 'new_spreadsheet_name' ) ] ) ? $fdbgp_settings[ $this->add_prefix( 'new_spreadsheet_name' ) ] : '';
                    
                    // Validate required fields with helpful error messages
                    if ( empty( $fdbgp_new_spreadsheet_name ) && empty( $fdbgp_sheetname ) ) {
                        $ajax_handler->add_admin_error_message( 'Please enter both a Spreadsheet Name and a Sheet Name to create a new spreadsheet.' );
                        return;
                    } elseif ( empty( $fdbgp_new_spreadsheet_name ) ) {
                        $ajax_handler->add_admin_error_message( 'Please enter a Spreadsheet Name to create a new spreadsheet.' );
                        return;
                    } elseif ( empty( $fdbgp_sheetname ) ) {
                        $ajax_handler->add_admin_error_message( 'Please enter a Sheet Name to create a new spreadsheet.' );
                        return;
                    }
                    
                    // Create new spreadsheet with initial sheet
                    $spreadsheet_object  = $instance_api->newspreadsheetobject( $fdbgp_new_spreadsheet_name, $fdbgp_sheetname );
                    $created_spreadsheet = $instance_api->createspreadsheet( $spreadsheet_object );
                    $fdbgp_spreadsheetid = $created_spreadsheet->getSpreadsheetId();
                    $is_newly_created    = true;
                    
                    // Get the sheet ID of the newly created sheet
                    $sheets   = $created_spreadsheet->getSheets();
                    $sheet_id = $sheets[0]['properties']['sheetId'];
                    
                    // Add headers to the new spreadsheet
                    $fdbgp_headers_data = $fdbgp_settings[ $this->add_prefix( 'sheet_headers' ) ];
                    if ( is_array( $fdbgp_headers_data ) && ! empty( $fdbgp_headers_data ) ) {
                        // Get form fields to map headers
                        $fdbgp_raw_fields    = $record->get( 'fields' );
                        $fdbgp_header_labels = array();
                        
                        foreach ( $fdbgp_headers_data as $field_id ) {
                            if ( isset( $fdbgp_raw_fields[ $field_id ] ) ) {
                                $fdbgp_header_labels[] = $fdbgp_raw_fields[ $field_id ]['title'];
                            } else {
                                $fdbgp_header_labels[] = ucfirst( str_replace( '_', ' ', $field_id ) );
                            }
                        }
                        
                        // Add header row
                        $fdbgp_header_range  = $fdbgp_sheetname . '!A1';
                        $fdbgp_header_body   = $instance_api->valuerangeobject( array( $fdbgp_header_labels ) );
                        $fdbgp_header_params = FDBGP_Google_API_Functions::get_row_format();
                        $header_param        = $instance_api->setparamater( $fdbgp_spreadsheetid, $fdbgp_header_range, $fdbgp_header_body, $fdbgp_header_params );
                        $instance_api->updateentry( $header_param );
                        
                        $freeze_object = $instance_api->freezeobject( $sheet_id, 1 );
                        $freeze_param  = array(
                            'spreadsheetid' => $fdbgp_spreadsheetid,
                            'requestbody'   => $freeze_object
                        );
                        $instance_api->formatsheet( $freeze_param );
                    }
                    
                    // Update the form settings to save the new spreadsheet ID
                    // This ensures subsequent form submissions use the created spreadsheet
                    $post_id = get_the_ID();
                    if ( $post_id ) {
                        $elementor_data = get_post_meta( $post_id, '_elementor_data', true );
                        if ( ! empty( $elementor_data ) ) {
                            $elementor_data = json_decode( $elementor_data, true );
                            $this->update_spreadsheet_id_in_data( $elementor_data, $record->get( 'form_settings' )['id'], $fdbgp_spreadsheetid );
                            update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $elementor_data ) ) );
                        }
                    }
                    
                } catch ( Exception $e ) {
                    $ajax_handler->add_admin_error_message( 'Failed to create spreadsheet - ' . $e->getMessage() );
                    return;
                }
            }
            
            // Only validate existence for existing spreadsheets
            // Newly created ones might not appear in the list immediately due to caching/latency
            if ( ! $is_newly_created ) {
                $fdbgp_sheetarray = $instance_api->get_spreadsheet_listing();
                
                if ( ! empty( $fdbgp_spreadsheetid ) && ! array_key_exists( $fdbgp_spreadsheetid, $fdbgp_sheetarray ) ) {
                    return;
                } elseif ( ! empty( $fdbgp_spreadsheetid ) ) {
                    $fdbgp_sheets = array();
                    $response     = $instance_api->get_sheet_listing( $fdbgp_spreadsheetid );
                    
                    foreach ( $response->getSheets() as $s ) {
                        $fdbgp_sheets[] = $s['properties']['title'];
                    }
                    
                    // Handle "Create New Tab" Logic
                    if ( 'create_new_tab' === $fdbgp_sheetname ) {
                        $fdbgp_new_tab_name = isset( $fdbgp_settings[ $this->add_prefix( 'new_sheet_tab_name' ) ] ) ? $fdbgp_settings[ $this->add_prefix( 'new_sheet_tab_name' ) ] : '';
                        
                        if ( ! empty( $fdbgp_new_tab_name ) ) {
                            // Update the main sheetname variable
                            $fdbgp_sheetname = $fdbgp_new_tab_name;
                            
                            // Check if it exists
                            if ( ! in_array( $fdbgp_sheetname, $fdbgp_sheets, true ) ) {
                                try {
                                    // Create the new sheet
                                    $sheet_req_obj  = $instance_api->createsheetobject( $fdbgp_sheetname );
                                    $sheet_param    = array(
                                        'spreadsheetid' => $fdbgp_spreadsheetid,
                                        'requestbody'   => $sheet_req_obj
                                    );
                                    $sheet_response = $instance_api->formatsheet( $sheet_param );
                                    
                                    // Get new sheet ID (needed for freezing rows)
                                    $new_sheet_id = 0;
                                    if ( method_exists( $sheet_response, 'getReplies' ) ) {
                                        $replies = $sheet_response->getReplies();
                                        if ( isset( $replies[0] ) ) {
                                            $addSheet = $replies[0]->getAddSheet();
                                            if ( $addSheet ) {
                                                $new_sheet_id = $addSheet->getProperties()->getSheetId();
                                            }
                                        }
                                    }

                                    // Add Headers
                                    $fdbgp_headers_data = $fdbgp_settings[ $this->add_prefix( 'sheet_headers' ) ];
                                    if ( is_array( $fdbgp_headers_data ) && ! empty( $fdbgp_headers_data ) ) {
                                        $fdbgp_header_labels = array();
                                        $fields_refs         = $record->get( 'fields' );
                                        
                                        foreach ( $fdbgp_headers_data as $field_id ) {
                                            if ( isset( $fields_refs[ $field_id ] ) ) {
                                                $fdbgp_header_labels[] = $fields_refs[ $field_id ]['title'];
                                            } else {
                                                $fdbgp_header_labels[] = ucfirst( str_replace( '_', ' ', $field_id ) );
                                            }
                                        }
                                        
                                        // Add header row
                                        $fdbgp_header_range  = $fdbgp_sheetname . '!A1';
                                        $fdbgp_header_body   = $instance_api->valuerangeobject( array( $fdbgp_header_labels ) );
                                        $fdbgp_header_params = FDBGP_Google_API_Functions::get_row_format();
                                        $header_param        = $instance_api->setparamater( $fdbgp_spreadsheetid, $fdbgp_header_range, $fdbgp_header_body, $fdbgp_header_params );
                                        $instance_api->updateentry( $header_param );
                                        
                                        // Freeze header row
                                        $freeze_object = $instance_api->freezeobject( $new_sheet_id, 1 );
                                        $freeze_param  = array(
                                            'spreadsheetid' => $fdbgp_spreadsheetid,
                                            'requestbody'   => $freeze_object
                                        );
                                        $instance_api->formatsheet( $freeze_param );
                                    }
                                    
                                    // Add to valid lists
                                    $fdbgp_sheets[] = $fdbgp_sheetname;
                                    
                                } catch ( Exception $e ) {
                                    // Fallback: Proceed, maybe it exists and we missed it, or it will fail on insert.
                                }
                            }
                        }
                    }
                    
                    // Fix for legacy settings: If sheet name is an index (e.g. "0"), try to resolve it to the actual name
                    if ( is_numeric( $fdbgp_sheetname ) && ! in_array( $fdbgp_sheetname, $fdbgp_sheets, true ) ) {
                        $index = (int) $fdbgp_sheetname;
                        if ( isset( $fdbgp_sheets[ $index ] ) ) {
                            $fdbgp_sheetname = $fdbgp_sheets[ $index ];
                        }
                    }
                    
                    if ( ! empty( $fdbgp_sheetname ) && ! in_array( $fdbgp_sheetname, $fdbgp_sheets, true ) ) {
                        //error_log( 'Error: Sheet Name not found in spreadsheet.' );
                        return;
                    }
                }
            }
            
            if ( ( empty( $fdbgp_spreadsheetid ) && $fdbgp_spreadsheetid !== '0' ) || ( empty( $fdbgp_sheetname ) && $fdbgp_sheetname !== '0' ) ) {
                //error_log( 'Error: Missing Spreadsheet ID or Sheet Name.' );
                return;
            }
            
            // Validate that at least one field/header is selected
            $fdbgp_sheetheaders = isset( $fdbgp_settings[ $this->add_prefix( 'sheet_headers' ) ] ) ? $fdbgp_settings[ $this->add_prefix( 'sheet_headers' ) ] : [];
            if ( empty( $fdbgp_sheetheaders ) || ! is_array( $fdbgp_sheetheaders ) || count( $fdbgp_sheetheaders ) === 0 ) {
                return;
            }

            // Normalize the Form Data.
            $fdbgp_fields = array();
            foreach ( $fdbgp_raw_fields as $id => $field ) {
                $fdbgp_fields[ $id ] = $field['value'];
            }

            // Add System Fields for mapping
            $fdbgp_fields['user_ip']         = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash($_SERVER['REMOTE_ADDR']) ) : '';
            $fdbgp_fields['user_agent']      = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash($_SERVER['HTTP_USER_AGENT']) ) : '';
            $fdbgp_fields['submission_date'] = current_time( 'mysql' );
            $fdbgp_fields['page_url']        = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash($_SERVER['HTTP_REFERER']) ) : '';
            
            try {
                $fdbgp_headers = isset( $fdbgp_settings[ $this->add_prefix( 'sheet_headers' ) ] ) ? $fdbgp_settings[ $this->add_prefix( 'sheet_headers' ) ] : [];
                
                // Fallback: If no headers selected, send ALL fields
                if ( empty( $fdbgp_headers ) || ! is_array( $fdbgp_headers ) ) {
                    $fdbgp_headers = array_keys( $fdbgp_fields );
                }

                $fdbgp_value_data = array();
                foreach ( $fdbgp_headers as $fdbgp_fieldvalue ) {
                    if ( array_key_exists( $fdbgp_fieldvalue, $fdbgp_fields ) ) {
                        if ( is_array( $fdbgp_fields[ $fdbgp_fieldvalue ] ) ) {
                            $fdbgp_value_data[] = implode( ',', $fdbgp_fields[ $fdbgp_fieldvalue ] );
                        } else {
                            $fdbgp_value_data[] = $fdbgp_fields[ $fdbgp_fieldvalue ];
                        }
                    } else {
                        $fdbgp_value_data[] = '';
                    }
                }
                
                $fdbgp_sheet    = "'" . $fdbgp_sheetname . "'!A:A";
                $fdbgp_allentry = $instance_api->get_row_list( $fdbgp_spreadsheetid, $fdbgp_sheet );
                $fdbgp_data     = $fdbgp_allentry->getValues();
                
                // Fix: Handle null data (empty sheet)
                if ( is_null( $fdbgp_data ) ) {
                    $fdbgp_data = array();
                }

                $fdbgp_data = array_map(
                    function ( $fdbgp_element ) {
                        if ( isset( $fdbgp_element['0'] ) ) {
                            return $fdbgp_element['0'];
                        } else {
                            return '';
                        }
                    },
                    $fdbgp_data
                );
                
                // Use A1 range for append, letting Google determine the next empty row
                $fdbgp_rangetoupdate = "'" . $fdbgp_sheetname . "'!A1";
                $fdbgp_requestbody   = $instance_api->valuerangeobject( array( $fdbgp_value_data ) );
                $fdbgp_params        = FDBGP_Google_API_Functions::get_row_format();
                
                // Add insertDataOption for safe appending
                $fdbgp_params['insertDataOption'] = 'INSERT_ROWS';
                
                $param = $instance_api->setparamater( $fdbgp_spreadsheetid, $fdbgp_rangetoupdate, $fdbgp_requestbody, $fdbgp_params );
                $instance_api->appendentry( $param );
                
            } catch ( Exception $e ) {
              //  error_log( 'Exception: ' . $e->getMessage() );
                $ajax_handler->add_admin_error_message( 'Error: ' . $e->getMessage() );
            }
        }
    }

    /**
     * Register Settings Section
     * Registers the Action controls
     *
     * @access public
     * @param \Elementor\Widget_Base $widget settings.
     */
    public function register_settings_section( $widget ) {
        $control_id = $this->add_prefix('section_google_sheets');
        if ( in_array( $control_id, self::$registered_actions, true ) ) {
            return; // Already registered
        }

        self::$registered_actions[] = $control_id;

        $instance_api          = new FDBGP_Google_API_Functions();
        $fdbgp_google_settings = $instance_api->get_google_creds();
        
        // Local variables to replace globals
        $local_spreadsheet_id = '';
        $local_sheet_name     = '';
        $local_sheet_headers  = array();
        $local_headers        = array();

        $fdbgp_exclude_headertype = array( 'honeypot', 'recaptcha', 'recaptcha_v3', 'html', 'step' );
        $fdbgp_sheetheaders       = array();

        // Retrieve saved settings from Elementor data
        $fdbgp_document = self::elementor()->documents->get( get_the_ID() );
        if ( $fdbgp_document ) {
            $fdbgp_data = $fdbgp_document->get_elements_data();
            $widget_id  = $widget->get_id();
            
            self::elementor()->db->iterate_data(
                $fdbgp_data,
                function ( $element ) use ( &$local_spreadsheet_id, &$local_sheet_name, &$local_sheet_headers, &$local_headers, $widget_id, $fdbgp_exclude_headertype ) {
                    if ( isset( $element['id'] ) && (string) $widget_id === (string) $element['id'] ) {
                        if ( isset( $element['settings'][ $this->add_prefix( 'spreadsheetid' ) ] ) ) {
                            $local_spreadsheet_id = $element['settings'][ $this->add_prefix( 'spreadsheetid' ) ];
                        }
                        if ( isset( $element['settings'][ $this->add_prefix( 'sheet_list' ) ] ) ) {
                            $local_sheet_name = $element['settings'][ $this->add_prefix( 'sheet_list' ) ];
                        }
                        if ( isset( $element['settings'][ $this->add_prefix( 'sheet_headers' ) ] ) ) {
                            $local_sheet_headers = $element['settings'][ $this->add_prefix( 'sheet_headers' ) ];
                        }
                        if ( isset( $element['settings']['form_fields'] ) ) {
                            foreach ( $element['settings']['form_fields'] as $formdata ) {
                                if ( ! isset( $formdata['field_type'] ) || ( isset( $formdata['field_type'] ) && ! in_array( $formdata['field_type'], $fdbgp_exclude_headertype, true ) ) ) {
                                    if ( empty( $formdata['custom_id'] ) ) {
                                        return;
                                    }
                                    $custom_id = (string) $formdata['custom_id'];
                                    $label = ! empty( $formdata['field_label'] )
                                        ? $formdata['field_label']
                                        : ucfirst( $custom_id );
                                    $local_headers[ $custom_id ] = $label;
                                }
                            }
                        }
                    }
                    return $element;
                }
            );
        }

        if ( ! is_array( $fdbgp_sheetheaders ) ) {
            $fdbgp_sheetheaders = array();
        }

        if ( empty( $fdbgp_google_settings['client_token'] ) ) {
            $fdbgp_html = sprintf(
                '<div class="elementor-control-raw-html elementor-panel-alert elementor-panel-alert-danger">%1$s<a href="admin.php?page=formsdb&tab=settings"> <strong>%2$s</strong></a>.</div>',
                esc_html__( 'Authentication required. Connect your Google account to use Sheets.', 'sb-elementor-contact-form-db' ),
                esc_html__( 'Click Here', 'sb-elementor-contact-form-db' )
            );
            $widget->start_controls_section(
                $this->add_prefix('section_google_sheets'),
                array(
                    'label'     => esc_html__( 'Save Submissions in Google Sheet', 'sb-elementor-contact-form-db' ),
                    'tab'       => 'connect_google_sheets_tab',
                    'condition' => array(
                        'cool_formkit_submit_actions' => $this->get_name(),
                    ),
                )
            );

            $widget->add_control(
                $this->add_prefix('html_notice'),
                array(
                    'type' => Controls_Manager::RAW_HTML,
                    'raw'  => $fdbgp_html,
                )
            );
            $widget->end_controls_section();

        } elseif ( ! empty( $fdbgp_google_settings['client_token'] ) && ! $instance_api->checkcredenatials() ) {
            $fdbgp_error = $instance_api->getClient( 1 );
            if ( 'Invalid token format' === (string) $fdbgp_error || 'invalid_grant' === (string) $fdbgp_error ) {
                $fdbgp_html = sprintf(
                    '<div class="elementor-control-raw-html elementor-panel-alert elementor-panel-alert-danger">%1$s<a href="admin.php?page=formsdb"> <strong>%2$s</strong></a>.</div>',
                    esc_html__( 'Error: Invalid Token - Revoke Token with Google Sheet Setting and try again.', 'sb-elementor-contact-form-db' ),
                    esc_html__( 'Click Here', 'sb-elementor-contact-form-db' )
                );
            } else {
                $fdbgp_html = sprintf(
                    '<div class="elementor-control-raw-html elementor-panel-alert elementor-panel-alert-danger">%1$s</div>',
                    'Error: ' . $fdbgp_error
                );
            }
            $widget->start_controls_section(
                $this->add_prefix('section_google_sheets'),
                array(
                    'label'     => esc_attr__( 'Save Submissions in Google Sheet', 'sb-elementor-contact-form-db' ),
                    'condition' => array(
                        'cool_formkit_submit_actions' => $this->get_name(),
                    ),
                )
            );
            $widget->add_control(
                $this->add_prefix( 'setup_clientidsecret' ),
                array(
                    'type' => Controls_Manager::RAW_HTML,
                    'raw'  => $fdbgp_html,
                )
            );
            $widget->end_controls_section();

        } else {
            $widget->start_controls_section(
                $this->add_prefix('section_google_sheets'),
                array(
                    'label'     => esc_html__( 'Save Submissions in Google Sheet', 'sb-elementor-contact-form-db' ),
                    'tab'       => 'connect_google_sheets_tab',
                    'condition' => array(
                        'cool_formkit_submit_actions' => $this->get_name(),
                    ),
                )
            );
            
            $fdbgp_spreadsheets = array();
            try {
                $fdbgp_spreadsheets = $instance_api->get_spreadsheet_listing();
            } catch ( Exception $e ) {
                $fdbgp_spreadsheets = array();
            }
            
            $fdbgp_spreadsheets = array( '' => esc_html__( 'Please Select a Spreadsheet', 'sb-elementor-contact-form-db' ) ) + $fdbgp_spreadsheets;

            $widget->add_control(
                $this->add_prefix( 'spreadsheetid' ),
                array(
                    'label'       => esc_attr__( 'Select Spreadsheet', 'sb-elementor-contact-form-db' ),
                    'type'        => Controls_Manager::SELECT,
                    'default'     => '',
                    'options'     => $fdbgp_spreadsheets,
                    'label_block' => true,
                    'render_type' => 'ui', // Ensure UI update triggers AJAX
                )
            );
            
            $widget->add_control(
                $this->add_prefix( 'new_spreadsheet_name' ),
                array(
                    'label'       => esc_attr__( 'Spreadsheet Name', 'sb-elementor-contact-form-db' ),
                    'type'        => Controls_Manager::TEXT,
                    'label_block' => true,
                    'condition'   => array(
                        $this->add_prefix( 'spreadsheetid' ) => 'new',
                    ),
                    'placeholder' => 'Please Enter a Spreadsheet Name',
                )
            );
            
            $widget->add_control(
                $this->add_prefix( 'sheet_name' ),
                array(
                    'label'       => esc_attr__( 'Sheet Tab Name', 'sb-elementor-contact-form-db' ),
                    'type'        => Controls_Manager::TEXT,
                    'label_block' => true,
                    'condition'   => array(
                        $this->add_prefix( 'spreadsheetid' ) => 'new',
                    ),
                    'placeholder' => 'Please Enter a Sheet Tab Name',
                )
            );

            // Populate Sheet List based on selection (Fixed logic)
            $fdbgp_sheets = array( '' => esc_html__( 'Please Enter Sheet Tab Name', 'sb-elementor-contact-form-db' ) );
            $fdbgp_sheets['create_new_tab'] = esc_html__( 'Create New Tab', 'sb-elementor-contact-form-db' );
            
            if ( ! empty( $local_spreadsheet_id ) && $local_spreadsheet_id !== 'new' ) {
                try {
                    $response = $instance_api->get_sheet_listing( $local_spreadsheet_id );
                    foreach ( $response->getSheets() as $s ) {
                        $title = $s['properties']['title'];
                        $fdbgp_sheets[ $title ] = $title;
                    }
                } catch ( Exception $e ) {
                }
            }
            
            if ( ! empty( $local_sheet_name ) && ! isset( $fdbgp_sheets[ $local_sheet_name ] ) && $local_sheet_name !== 'create_new_tab' ) {
                $fdbgp_sheets[ $local_sheet_name ] = $local_sheet_name;
            }

            $widget->add_control(
                $this->add_prefix( 'sheet_list' ),
                array(
                    'label'       => esc_attr__( 'Select Sheet Tab Name', 'sb-elementor-contact-form-db' ),
                    'type'        => Controls_Manager::SELECT,
                    'default'     => '',
                    'options'     => $fdbgp_sheets,
                    'label_block' => true,
                    'condition'   => array(
                        $this->add_prefix( 'spreadsheetid' ) . '!' => array( 'new', '' ),
                    ),
                    'render_type' => 'ui',
                )
            );

            $widget->add_control(
                $this->add_prefix( 'new_sheet_tab_name' ),
                array(
                    'label'       => esc_attr__( 'New Sheet Tab Name', 'sb-elementor-contact-form-db' ),
                    'type'        => Controls_Manager::TEXT,
                    'label_block' => true,
                    'condition'   => array(
                        $this->add_prefix( 'spreadsheetid' ) . '!' => array( 'new', '' ),
                        $this->add_prefix( 'sheet_list' ) => 'create_new_tab',
                    ),
                    'placeholder' => 'e.g. Sheet2',
                )
            );
            
            $widget->add_control(
                $this->add_prefix( 'sheet_headers' ),
                array(
                    'label'       => esc_attr__( 'Select a data to save in sheet', 'sb-elementor-contact-form-db' ),
                    'type'        => 'fdbgp_dynamic_select2',
                    'label_block' => true,
                    'multiple'    => true, 
                    'default'     => [ 'user_ip','page_url','submission_date' ],
                    'options'     => array(
                        'user_ip'         => esc_html__( 'User IP', 'sb-elementor-contact-form-db' ),
                        'user_agent'      => esc_html__( 'User Agent', 'sb-elementor-contact-form-db' ),
                        'page_url'        => esc_html__( 'Page URL', 'sb-elementor-contact-form-db' ),
                        'submission_date' => esc_html__( 'Submission DateTime', 'sb-elementor-contact-form-db' ),
                    ),
                    'condition'   => array(
                        $this->add_prefix( 'spreadsheetid' ) . '!' => array( '' ),
                    ),
                    'placeholder' => 'Please Select a data to save in sheet',
                )
            );
            
            // Add Create Spreadsheet Now button
            $widget->add_control(
                $this->add_prefix( 'create_spreadsheet_button' ),
                array(
                    'type'      => Controls_Manager::RAW_HTML,
                    'raw'       => '<button type="button" class="elementor-button elementor-button-info fdbgp-create-spreadsheet">
                        <span class="elementor-button-text">Create Spreadsheet</span>
                    </button>
                    <div id="fdbgp-message" class="elementor-control-alert elementor-panel-alert elementor-panel-alert-success" style="display:none;margin-top: 10px;"></div>',
                    'condition' => array(
                        $this->add_prefix( 'spreadsheetid' ) => 'new',
                    ),
                )
            );

            // Add Update Sheet button
            $widget->add_control(
                $this->add_prefix( 'update_sheet_button' ),
                array(
                    'type'      => Controls_Manager::RAW_HTML,
                    'raw'       => '<button type="button" class="elementor-button elementor-button-info fdbgp-update-sheet">
                        <span class="elementor-button-text">Update Sheet</span>
                    </button>
                    <div id="fdbgp-update-message" class="elementor-control-alert elementor-panel-alert elementor-panel-alert-danger" style="margin-top:10px; display:none;"></div>',
                    'condition' => array(
                        $this->add_prefix( 'spreadsheetid' ) . '!' => array( 'new', '' ),
                    ),
                )
            );
            
            $widget->end_controls_section();
        }
    }

    // --- AJAX HANDLERS ---


    
    /**
     * AJAX Handler: Get Sheets from Spreadsheet
     */

    
    /**
     * AJAX Handler: Update Sheet Headers
     */


    /**
     * AJAX Handler: Check Sheet Content
     */


    /**
     * Update Spreadsheet ID in Elementor Data
     * Recursively searches and updates the spreadsheet ID in Elementor data
     *
     * @access private
     * @param array  &$data Reference to Elementor data array.
     * @param string $form_id The form ID to match.
     * @param string $new_spreadsheet_id The new spreadsheet ID.
     * @return bool Whether the update was successful.
     */
    private function update_spreadsheet_id_in_data( &$data, $form_id, $new_spreadsheet_id ) {

        if ( ! is_array( $data ) ) {
            return false;
        }
        
        foreach ( $data as &$element ) {
            // Check if this is the form we're looking for
            if ( isset( $element['id'] ) && $element['id'] === $form_id ) {
                if ( isset( $element['settings'][ $this->add_prefix( 'spreadsheetid' ) ] ) ) {
                    $element['settings'][ $this->add_prefix( 'spreadsheetid' ) ] = $new_spreadsheet_id;
                    return true;
                }
            }
            
            // Recursively check nested elements
            if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
                if ( $this->update_spreadsheet_id_in_data( $element['elements'], $form_id, $new_spreadsheet_id ) ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * On Export
     * Clears form settings on export
     *
     * @access public
     * @param array $element_sheets clear settings.
     */
    public function on_export( $element_sheets ) {
    }
}