<?php
namespace Formsdb_Elementor_Forms\Lib_Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    die;
}

use Formsdb_Elementor_Forms\Lib_Helpers\FDBGP_Google_API_Functions;
use Exception;

/**
 * Trait for FDBGP AJAX Handlers
 */
trait FDBGP_Ajax_Handlers {

    /**
     * Register All AJAX Actions
     */
    public function register_ajax_events() {
        add_action( 'wp_ajax_fdbgp_get_sheets', array( $this, 'ajax_get_sheets' ) );
        add_action( 'wp_ajax_fdbgp_create_spreadsheet', array( $this, 'ajax_create_spreadsheet' ) );
        add_action( 'wp_ajax_fdbgp_update_sheet_headers', array( $this, 'ajax_update_sheet_headers' ) );
        add_action( 'wp_ajax_fdbgp_check_sheet_headers', array( $this, 'ajax_check_sheet_headers' ) );
    }

    /**
     * AJAX Handler: Create Spreadsheet
     */
    public function ajax_create_spreadsheet() {

        check_ajax_referer( 'elementor_ajax', '_nonce' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
        }
        
        $spreadsheet_name = isset( $_POST['spreadsheet_name'] ) ? sanitize_text_field( wp_unslash($_POST['spreadsheet_name']) ) : '';
        $sheet_name       = isset( $_POST['sheet_name'] ) ? sanitize_text_field( wp_unslash($_POST['sheet_name']) ) : '';
        $headers          = isset( $_POST['headers'] ) && is_array( $_POST['headers'] ) ? array_map( 'sanitize_text_field', wp_unslash($_POST['headers']) ) : array();
        
        // Validate input
        if ( empty( $spreadsheet_name ) ) {
            wp_send_json_error( array( 'message' => 'Please enter a Spreadsheet Name' ) );
        }
        
        if ( empty( $sheet_name ) ) {
            wp_send_json_error( array( 'message' => 'Please enter a Sheet Name' ) );
        }
        
        try {
            $instance_api = new FDBGP_Google_API_Functions();
            
            if ( ! $instance_api->checkcredenatials() ) {
                wp_send_json_error( array( 'message' => 'Google API credentials not configured' ) );
            }
            
            // Create new spreadsheet with initial sheet
            $spreadsheet_object  = $instance_api->newspreadsheetobject( $spreadsheet_name, $sheet_name );
            $created_spreadsheet = $instance_api->createspreadsheet( $spreadsheet_object );
            $spreadsheet_id      = $created_spreadsheet->getSpreadsheetId();
            
            // Get the sheet ID of the newly created sheet
            $sheets   = $created_spreadsheet->getSheets();
            $sheet_id = $sheets[0]['properties']['sheetId'];
            
            // Add headers if provided
            if ( ! empty( $headers ) ) {
                $header_range  = $sheet_name . '!A1';
                $header_body   = $instance_api->valuerangeobject( array( $headers ) );
                $header_params = FDBGP_Google_API_Functions::get_row_format();
                $header_param  = $instance_api->setparamater( $spreadsheet_id, $header_range, $header_body, $header_params );
                $instance_api->updateentry( $header_param );
                
                // Freeze header row
                $freeze_object = $instance_api->freezeobject( $sheet_id, 1 );
                $freeze_param  = array(
                    'spreadsheetid' => $spreadsheet_id,
                    'requestbody'   => $freeze_object
                );
                $instance_api->formatsheet( $freeze_param );
            }
            
            wp_send_json_success( array(
                'message'          => 'Spreadsheet created successfully!',
                'spreadsheet_id'   => $spreadsheet_id,
                'spreadsheet_name' => $spreadsheet_name,
                'sheet_name'       => $sheet_name,
            ) );
            
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
        }
    }
    
    /**
     * AJAX Handler: Get Sheets from Spreadsheet
     */
    public function ajax_get_sheets() {

        check_ajax_referer( 'elementor_ajax', '_nonce' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
        }
        
        $spreadsheet_id = isset( $_POST['spreadsheet_id'] ) ? sanitize_text_field( wp_unslash($_POST['spreadsheet_id']) ) : '';
        
        if ( empty( $spreadsheet_id ) ) {
            wp_send_json_error( array( 'message' => 'Spreadsheet ID is required' ) );
        }
        
        try {
            $instance_api = new FDBGP_Google_API_Functions();
            
            if ( ! $instance_api->checkcredenatials() ) {
                wp_send_json_error( array( 'message' => 'Google API credentials not configured' ) );
            }
            
            $response = $instance_api->get_sheet_listing( $spreadsheet_id );
            $sheets   = array();
            
            // Add Create New Tab option
            $sheets['create_new_tab'] = esc_html__( 'Create New Tab', 'sb-elementor-contact-form-db' );
            
            foreach ( $response->getSheets() as $s ) {
                $title = $s['properties']['title'];
                $sheets[ $title ] = $title;
            }
            
            wp_send_json_success( array( 'sheets' => $sheets ) );
            
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
        }
    }

    /**
     * AJAX Handler: Update Sheet Headers
     */
    public function ajax_update_sheet_headers() {

        check_ajax_referer( 'elementor_ajax', '_nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }

        $spreadsheet_id = isset( $_POST['spreadsheet_id'] ) ? sanitize_text_field( wp_unslash($_POST['spreadsheet_id']) ) : '';
        $sheet_name     = isset( $_POST['sheet_name'] ) ? sanitize_text_field( wp_unslash($_POST['sheet_name']) ) : '';
        $new_sheet_name = isset( $_POST['new_sheet_name'] ) ? sanitize_text_field( wp_unslash($_POST['new_sheet_name']) ) : '';
        $headers        = isset( $_POST['headers'] ) && is_array( $_POST['headers'] ) ? array_map( 'sanitize_text_field', wp_unslash($_POST['headers']) ) : array();

        if ( empty( $spreadsheet_id ) ) {
            wp_send_json_error( array( 'message' => 'Spreadsheet ID missing' ) );
        }

        try {
            $api = new FDBGP_Google_API_Functions();
            if ( ! $api->checkcredenatials() ) {
                wp_send_json_error( array( 'message' => 'API Error' ) );
            }

            $target_sheet_name = $sheet_name;

            // Handle Create New Tab
            if ( 'create_new_tab' === $sheet_name ) {
                if ( empty( $new_sheet_name ) ) {
                    wp_send_json_error( array( 'message' => 'New Sheet Name is required' ) );
                }
                $target_sheet_name = $new_sheet_name;

                // Check existence
                $existing_sheets = $api->get_sheet_listing( $spreadsheet_id );
                $titles          = array();
                foreach ( $existing_sheets->getSheets() as $s ) {
                    $titles[] = $s['properties']['title'];
                }

                if ( ! in_array( $target_sheet_name, $titles, true ) ) {
                    // Create
                    $req = $api->createsheetobject( $target_sheet_name );
                    $api->formatsheet( array(
                        'spreadsheetid' => $spreadsheet_id,
                        'requestbody'   => $req,
                    ) );
                }
            }

            // Update Headers
            if ( ! empty( $headers ) ) {
                $range  = $target_sheet_name . '!A1';
                $body   = $api->valuerangeobject( array( $headers ) );
                $params = FDBGP_Google_API_Functions::get_row_format();
                $api->updateentry( $api->setparamater( $spreadsheet_id, $range, $body, $params ) );

                // Freeze
                $sheet_id = 0;
                $refresh  = $api->get_sheet_listing( $spreadsheet_id );
                foreach ( $refresh->getSheets() as $s ) {
                    if ( $s['properties']['title'] === $target_sheet_name ) {
                        $sheet_id = $s['properties']['sheetId'];
                        break;
                    }
                }
                $api->formatsheet( array(
                    'spreadsheetid' => $spreadsheet_id,
                    'requestbody'   => $api->freezeobject( $sheet_id, 1 ),
                ) );
            }
            
            wp_send_json_success( array(
                'message'    => 'Sheet Updated Successfully!',
                'sheet_name' => $target_sheet_name,
            ) );

        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
        }
    }

    /**
     * AJAX Handler: Check Sheet Content
     */
    public function ajax_check_sheet_headers() {

        check_ajax_referer( 'elementor_ajax', '_nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ) );
        }

        $spreadsheet_id = isset( $_POST['spreadsheet_id'] ) ? sanitize_text_field( wp_unslash($_POST['spreadsheet_id']) ) : '';
        $sheet_name     = isset( $_POST['sheet_name'] ) ? sanitize_text_field( wp_unslash($_POST['sheet_name']) ) : '';

        if ( empty( $spreadsheet_id ) || empty( $sheet_name ) || 'create_new_tab' === $sheet_name ) {
            wp_send_json_success( array( 'has_content' => false ) );
            return;
        }

        try {
            $api = new FDBGP_Google_API_Functions();
            if ( ! $api->checkcredenatials() ) {
                wp_send_json_error( array( 'message' => 'API Error' ) );
            }

            // Check if sheet has data beyond just headers (check first few rows across all columns)
            $check_range = "'" . $sheet_name . "'!A2:Z100";
            $existing    = $api->get_row_list( $spreadsheet_id, $check_range );
            $rows        = $existing->getValues();

            // Check if there's any actual data (non-empty rows beyond header)
            $has_data = false;
            if ( ! empty( $rows ) ) {
                foreach ( $rows as $row ) {
                    // Check if row has any non-empty values
                    if ( ! empty( $row ) && ! empty( array_filter( $row ) ) ) {
                        $has_data = true;
                        break;
                    }
                }
            }

            if ( $has_data ) {
                wp_send_json_success( array(
                    'has_content' => true,
                    'message'     => 'Selected sheet is not empty. Backup recommended before updating.',
                ) );
            } else {
                wp_send_json_success( array( 'has_content' => false ) );
            }

        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => 'Error: ' . $e->getMessage() ) );
        }
    }
}
