<?php
namespace Formsdb_Elementor_Forms\Lib_Helpers;

if (!defined('ABSPATH')) {
    die;
}
/**
 * Google API Base Class
 *
 * Abstract base class providing common wrapper methods for interacting
 * with the Google Sheets API.
 *
 * @since 1.0.0
 */


abstract class FDBGP_Google_API {

	/**
	 * Get parameters for row formatting in Google Sheets API.
	 *
	 * @since 1.0.0
	 * @return array Associative array of parameters for row formatting.
	 */
	public static function get_row_format() {

		$params = array( 'valueInputOption' => 'RAW' );

		return $params;

	}


	/**
	 * Retrieve spreadsheet metadata from Google Sheets API.
	 *
	 * @param \Google_Service_Sheets $service    The Google Sheets service instance.
	 * @param string                $spreadsheetid The ID of the spreadsheet to retrieve.
	 * @since 1.0.0
	 * @return \Google_Service_Sheets_Spreadsheet Spreadsheet object containing metadata and sheets.
	 */
	public function get_sheets( $service, $spreadsheetid ) {
		return $service->spreadsheets->get( $spreadsheetid );
	}


	/**
	 * Create a new spreadsheet in Google Drive.
	 *
	 * @param \Google_Service_Sheets                 $service    The Google Sheets service instance.
	 * @param \Google_Service_Sheets_Spreadsheet $requestbody Spreadsheet object with properties and initial sheets.
	 * @since 1.0.0
	 * @return \Google_Service_Sheets_Spreadsheet The created spreadsheet with its ID.
	 */
	public function create_spreadsheet( $service, $requestbody ) {
		return $service->spreadsheets->create( $requestbody );
	}


	/**
	 * Append values to a range in a Google Sheet.
	 *
	 * @param \Google_Service_Sheets $service  The Google Sheets service instance.
	 * @param array                 $param    Associative array with keys:
	 *                                         - 'spreadsheetid': Spreadsheet ID
	 *                                         - 'range': A1 notation range
	 *                                         - 'requestbody': ValueRange object
	 *                                         - 'params': Additional request parameters
	 * @since 1.0.0
	 * @return \Google_Service_Sheets_AppendValuesResponse The result of the append operation.
	 */
	public function append_entry( $service, $param ) {
		return $service->spreadsheets_values->append( $param['spreadsheetid'], $param['range'], $param['requestbody'], $param['params'] );
	}


	/**
	 * Update values in a range in a Google Sheet.
	 *
	 * @param \Google_Service_Sheets $service  The Google Sheets service instance.
	 * @param array                 $param    Associative array with keys:
	 *                                         - 'spreadsheetid': Spreadsheet ID
	 *                                         - 'range': A1 notation range
	 *                                         - 'requestbody': ValueRange object
	 *                                         - 'params': Additional request parameters
	 * @since 1.0.0
	 * @return \Google_Service_Sheets_UpdateValuesResponse The result of the update operation.
	 */
	public function update_entry( $service, $param ) {
		return $service->spreadsheets_values->update( $param['spreadsheetid'], $param['range'], $param['requestbody'], $param['params'] );
	}


	/**
	 * Execute batch update requests on a spreadsheet.
	 *
	 * @param \Google_Service_Sheets                    $service  The Google Sheets service instance.
	 * @param array                                    $param    Associative array with keys:
	 *                                                   - 'spreadsheetid': Spreadsheet ID
	 *                                                   - 'requestbody': BatchUpdateSpreadsheetRequest object
	 * @since 1.0.0
	 * @return \Google_Service_Sheets_BatchUpdateSpreadsheetResponse Response from the batch update.
	 */
	public function batchupdate( $service, $param ) {
		return $service->spreadsheets->batchUpdate( $param['spreadsheetid'], $param['requestbody'] );
	}


	/**
	 * Retrieve values from a range in a Google Sheet.
	 *
	 * @param \Google_Service_Sheets $service  The Google Sheets service instance.
	 * @param array                 $param    Associative array with keys:
	 *                                         - 'spreadsheetid': Spreadsheet ID
	 *                                         - 'sheetname': Sheet name or A1 notation range
	 * @since 1.0.0
	 * @return \Google_Service_Sheets_ValueRange The values in the specified range.
	 */
	public function get_values( $service, $param ) {
		return $service->spreadsheets_values->get( $param['spreadsheetid'], $param['sheetname'] );
	}


	/**
	 * Clear all values from a range in a Google Sheet.
	 *
	 * @param \Google_Service_Sheets          $service  The Google Sheets service instance.
	 * @param array                          $param    Associative array with keys:
	 *                                                   - 'spreadsheetid': Spreadsheet ID
	 *                                                   - 'sheetname': Sheet name or A1 notation range
	 *                                                   - 'requestbody': ClearValuesRequest object
	 * @since 1.0.0
	 * @return \Google_Service_Sheets_ClearValuesResponse Response from the clear operation.
	 */
	public function clearsheet( $service, $param ) {
		return $service->spreadsheets_values->clear( $param['spreadsheetid'], $param['sheetname'], $param['requestbody'] );
	}
}