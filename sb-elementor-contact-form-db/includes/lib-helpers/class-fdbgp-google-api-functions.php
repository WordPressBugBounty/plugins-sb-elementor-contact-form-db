<?php
namespace Formsdb_Elementor_Forms\Lib_Helpers;

if (!defined('ABSPATH')) {
    die;
}

use Formsdb_Elementor_Forms\Lib_Helpers\FDBGP_Google_API;

/**
 * FDBGP_Google_API_Functions class.
 *
 * @since 1.0.0
 */
class FDBGP_Google_API_Functions extends FDBGP_Google_API {

	/**
	 * Google Sheets Service Object
	 *
	 * @var object
	 * @since 1.0.0
	 */
	private static $instance_service = null;

	/**
	 * Google Drive Object
	 *
	 * @var object
	 * @since 1.0.0
	 */
	private static $instance_drive = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( self::checkcredenatials() ) {
			self::loadobject();
		}
	}

	/**
	 * Load Google Service and Drive Object.
	 *
	 * @since 1.0.0
	 */
	public function loadobject() {
		self::$instance_service = self::get_client_object();
		self::$instance_drive   = self::get_drive_object();
	}

	/**
	 * Load Google API Library.
	 *
	 * @since 1.0.0
	 */
	public function fdbgp_load_library() {
			if ( file_exists( FDBGP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
				require_once FDBGP_PLUGIN_DIR . 'vendor/autoload.php';
			}
    }
	/**
	 * Get Google Sheets Service Object.
	 *
	 * @since 1.0.0
	 */
	public function get_client_object() {
		if ( null === self::$instance_service ) {
			$client                 = self::getClient();
			self::$instance_service = new \Google\Service\Sheets( $client );
		}
		return self::$instance_service;
	}

	/**
	 * Regenerate Google Sheets Service Object.
	 *
	 * @since 1.0.0
	 */
	public function refreshobject() {
		self::$instance_service = null;
		self::get_client_object();
	}

	/**
	 * Get Google Drive Object.
	 *
	 * @since 1.0.0
	 */
	public function get_drive_object() {
		if ( null === self::$instance_drive ) {
			$client               = self::getClient();
			self::$instance_drive = new \Google\Service\Drive( $client );
		}
		return self::$instance_drive;
	}

	/**
	 * Regenerate Google Drive Object.
	 *
	 * @since 1.0.0
	 */
	public static function get_object_drive_object() {
		self::$instance_drive = null;
	}

	/**
	 * Check Google API Credentials.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function checkcredenatials() {
		$fdbgp_google_settings_value = $this->get_google_creds();
		$clientid                     = isset( $fdbgp_google_settings_value['client_id'] ) ? $fdbgp_google_settings_value['client_id'] : '';
		$clientsecert                 = isset( $fdbgp_google_settings_value['client_secret'] ) ? $fdbgp_google_settings_value['client_secret'] : '';
		$auth_token                   = isset( $fdbgp_google_settings_value['client_token'] ) ? $fdbgp_google_settings_value['client_token'] : '';
		if ( empty( $clientid ) || empty( $clientsecert ) || empty( $auth_token ) ) {
			return false;
		} else {
			try {
				if ( self::getClient() ) {
					return true;
				} else {
					return false;
				}
			} catch ( Exception $e ) {
				return false;
			}
		}
	}

	public function get_google_creds(){
		return get_option('fdbgp_google_settings', array(
			'client_id' => '',
			'client_secret' => '',
			'client_token' => ''
		));
	}

	/**
	 * Get Google Client Object.
	 *
	 * @param int $flag flag to return error message.
	 * @since 1.0.0
	 *
	 * @return object|string.
	 */
	public function getClient( $flag = 0 ) {

		$this->fdbgp_load_library();
		$fdbgp_google_settings_value = $this->get_google_creds();
		$clientid                     = isset( $fdbgp_google_settings_value['client_id'] ) ? $fdbgp_google_settings_value['client_id'] : '';
		$clientsecert                 = isset( $fdbgp_google_settings_value['client_secret'] ) ? $fdbgp_google_settings_value['client_secret'] : '';
		$auth_token                   = isset( $fdbgp_google_settings_value['client_token'] ) ? $fdbgp_google_settings_value['client_token'] : '';
		$client       = new \Google\Client();
		$client->setApplicationName( 'FormsDB For Elementor Forms' );
		$client->setScopes( \Google\Service\Sheets::SPREADSHEETS_READONLY );
		$client->setScopes( \Google\Service\Drive::DRIVE_METADATA_READONLY );
		$client->addScope( \Google\Service\Sheets::SPREADSHEETS );
		$client->setClientId( $clientid );
		$client->setClientSecret( $clientsecert );
		$client->setRedirectUri(admin_url( 'admin.php?page=formsdb&tab=settings' ));
		$client->setAccessType( 'offline' );
		$client->setPrompt('consent');
		// Load previously authorized credentials from a database.
		try {
			if ( empty( $auth_token ) ) {
				$state = wp_create_nonce( 'fdbgp_google_oauth' );
				$client->setState( $state );
				$auth_url = $client->createAuthUrl();
				return $auth_url;
			}
			$fdbgp_accesstoken = get_option( 'fdbgp_google_access_token' );

			if ( ! empty( $fdbgp_accesstoken ) ) {
				$accesstoken = json_decode( $fdbgp_accesstoken, true );
			} else {
				if ( empty( $auth_token ) ) {
					$auth_url = $client->createAuthUrl();
					return $auth_url;
				} else {
					$authcode = trim( $auth_token );
					// Exchange authorization code for an access token.
					$accesstoken = $client->fetchAccessTokenWithAuthCode( $authcode );
					if(! isset( $accesstoken['refresh_token'] ) || empty( $accesstoken['refresh_token'] ) ){
						$accesstoken['refresh_token'] = $client->getRefreshToken();
					}
					// Store the credentials to disk.
					update_option( 'fdbgp_google_access_token', wp_json_encode( $accesstoken ) );
				}
			}

			// Check for invalid token.
			if ( is_array( $accesstoken ) && isset( $accesstoken['error'] ) && ! empty( $accesstoken['error'] ) ) {
				if ( $flag ) {
					return $accesstoken['error'];
				}
				return false;
			}

			$client->setAccessToken( $accesstoken );
			// Refresh the token if it's expired.
			if ( $client->isAccessTokenExpired() ) {
				// save refresh token to some variable.				
				$refreshtokensaved = ( isset( $accesstoken['refresh_token'] ) && !empty( $accesstoken['refresh_token'] ) ) ? $accesstoken['refresh_token'] : $client->getRefreshToken();
				if( Null === $refreshtokensaved || empty( $refreshtokensaved ) ){
					if ( $flag ) {
						$m = 'Please revoke the token and generate it again.';
						return $m;
					} else {
						return false;
					}
				}
				$newaccesstoken = $client->fetchAccessTokenWithRefreshToken( $refreshtokensaved );

				if ( is_array( $newaccesstoken ) && isset( $newaccesstoken['error'] ) && ! empty( $newaccesstoken['error'] ) ) {
					if ( $flag ) {
						return $newaccesstoken['error'];
					}
					return false;
				}
				
				// pass access token to some variable.
				$accesstokenupdated = $client->getAccessToken();
				if(! isset( $accesstokenupdated['refresh_token'] ) ){
					// append refresh token.
					$accesstokenupdated['refresh_token'] = $refreshtokensaved;
				}
				// Set the new access token.
				update_option( 'fdbgp_google_access_token', wp_json_encode( $accesstokenupdated ) );
				$accesstoken = json_decode( wp_json_encode( $accesstokenupdated ), true );
				$client->setAccessToken( $accesstoken );
			}
		} catch ( Exception $e ) {
			if ( $flag ) {
				return $e->getMessage();
			} else {
				return false;
			}
		}
		return $client;
	}

	/**
	 * Get Spreadsheet Listing.
	 *
	 * @param array $sheetarray sheet array.
	 * @since 1.0.0
	 *
	 * @return array.
	 */
	public function get_spreadsheet_listing( $sheetarray = array() ) {
		
		if ( self::checkcredenatials() ) {
			self::get_object_drive_object();
			self::loadobject();
		} else {
			return $sheetarray;
		}
		// Print the names and IDs of files.
		$optparams = array(
			'fields' => 'nextPageToken, files(id, name, mimeType)',
			'q'      => "mimeType='application/vnd.google-apps.spreadsheet' and trashed = false",
		);
		$sheetarray['new'] = esc_html__( 'Create New Spreadsheet', 'sb-elementor-contact-form-db' );
		$results = self::$instance_drive->files->listFiles( $optparams );
		if ( count( $results->getFiles() ) > 0 ) {
			foreach ( $results->getFiles() as $file ) {
				$sheetarray[ $file->getId() ] = $file->getName();
			}
		}
		return $sheetarray;
	}

	/**
	 * Fetch sheet listing from Google Sheet.
	 *
	 * @param string $spreadsheetid Spreadsheet ID.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function get_sheet_listing( $spreadsheetid = '' ) {
		self::refreshobject();
		return parent::get_sheets( self::$instance_service, $spreadsheetid );
	}

	/**
	 * Fetch row listing from Google Sheet.
	 *
	 * @param string $spreadsheetid Spreadsheet ID.
	 * @param string $sheetname Sheet Name.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function get_row_list( $spreadsheetid, $sheetname ) {
		self::refreshobject();
		$param                  = array();
		$param['spreadsheetid'] = trim( $spreadsheetid );
		$param['sheetname']     = trim( $sheetname );
		return parent::get_values( self::$instance_service, $param );
	}

	/**
	 * Create sheet array.
	 *
	 * @param object $response_object google sheet object.
	 * @since 1.0.0
	 *
	 * @return array.
	 */
	public function get_sheet_list( $response_object ) {
		$sheets = array();
		foreach ( $response_object->getSheets() as $key => $value ) {
			$sheets[ $value['properties']['title'] ] = $value['properties']['sheetId'];
		}
		return $sheets;
	}


	/**
	 * Create Google_Service_Sheets_BatchUpdateSpreadsheetRequest Object
	 * to freeze first row.
	 *
	 * @param int $sheetid Sheet ID.
	 * @param int $fdbgp_freeze number of rows to be freezed.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function freezeobject( $sheetid = 0, $fdbgp_freeze = 0 ) {
		$requestbody = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest(
			array(
				'requests' => array(
					'updateSheetProperties' => array(
						'properties' => array(
							'sheetId'        => $sheetid,
							'gridProperties' => array(
								'frozenRowCount' => $fdbgp_freeze,
							),
						),
						'fields'     => 'gridProperties.frozenRowCount',
					),
				),
			)
		);
		return $requestbody;
	}

	/**
	 * Create Google_Service_Sheets_Spreadsheet Object
	 *
	 * @param string $spreadsheetname Spreadsheet Name.
	 * @param string $sheetname Sheet Name.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function newspreadsheetobject( $spreadsheetname = '', $sheetname = '' ) {
		$requestbody = new \Google\Service\Sheets\Spreadsheet(
			array(
				'properties' => array(
					'title' => $spreadsheetname,
				),
				'sheets'     => array(
					array(
						'properties' => array(
							'title' => $sheetname,
						),
					),
				),
			)
		);
		return $requestbody;
	}

	/**
	 * Prepare parameter array.
	 *
	 * @param string $spreadsheetid Spreadsheet ID.
	 * @param string $range Range.
	 * @param array  $requestbody Request Body.
	 * @param array  $params Params.
	 * @since 1.0.0
	 *
	 * @return array.
	 */
	public function setparamater( $spreadsheetid = '', $range = '', $requestbody = array(), $params = array() ) {
		$param                  = array();
		$param['spreadsheetid'] = $spreadsheetid;
		$param['range']         = $range;
		$param['requestbody']   = $requestbody;
		$param['params']        = $params;
		return $param;
	}

	/**
	 * Create Google_Service_Sheets_ValueRange Object.
	 *
	 * @param array $values_data values data.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function valuerangeobject( $values_data = array() ) {
		$requestbody = new \Google\Service\Sheets\ValueRange( array( 'values' => $values_data ) );
		return $requestbody;
	}

	/**
	 * Google_Service_Sheets_BatchUpdateSpreadsheetRequest Object
	 *
	 * @param string $fdbgp_sheetname Sheet Name.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function createsheetobject( $fdbgp_sheetname = '' ) {
		$fdbgp_requestbody = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest(
			array(
				'requests' => array(
					'addSheet' => array(
						'properties' => array(
							'title' => $fdbgp_sheetname,
						),
					),
				),
			)
		);
		return $fdbgp_requestbody;
	}

	/**
	 * Create Google_Service_Sheets_ClearValuesRequest Object
	 *
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function clearobject() {
		$requestbody = new \Google\Service\Sheets\ClearValuesRequest();
		return $requestbody;
	}

	/**
	 * Format google sheet.
	 *
	 * @param array $param contains spreadsheetid, requestbody, params.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function formatsheet( $param = array() ) {
		return parent::batchupdate( self::$instance_service, $param );
	}

	/**
	 * Update entry to google sheet.
	 *
	 * @param array $param contains spreadsheetid, range, requestbody, params.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function updateentry( $param = array() ) {
		return parent::update_entry( self::$instance_service, $param );
	}

	/**
	 * Append entry to google sheet.
	 *
	 * @param array $param contains spreadsheetid, range, requestbody, params.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function appendentry( $param = array() ) {
		return parent::append_entry( self::$instance_service, $param );
	}

	/**
	 * Create Spreadsheet.
	 *
	 * @param array $requestbody request body.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function createspreadsheet( $requestbody = array() ) {
		return parent::create_spreadsheet( self::$instance_service, $requestbody );
	}

	/**
	 * Clear Google Sheet.
	 *
	 * @param array $param contains spreadsheetid, range, requestbody, params.
	 * @since 1.0.0
	 *
	 * @return object.
	 */
	public function clear( $param = array() ) {
		return parent::clearsheet( self::$instance_service, $param );
	}
}