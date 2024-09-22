<?php

/*
 * Plugin Name: Contact Form DB - Elementor 
 * Plugin URI:  https://webacetechs.in
 * Description: A simple plugin to save contact form submissions in the database, designed for the Elementor Form Module
 * Author:      Web Ace Tech Services
 * Version:     1.8.1
 * Author URI:  https://webacetechs.in
 * Text Domain: elementor-contact-form-db
 * Domain Path: /languages
 */

if(!defined( 'WPINC' )) {
	die;
}

define( 'SB_ELEM_CFD_DB_ITEM_NAME', 'Elementor Contact Form DB' );
define( 'SB_ELEM_CFD_DB_VERSION', '1.8.1' );

add_action( 'plugins_loaded', 'sb_elem_cfd_init' );

function sb_elem_cfd_init() {
	add_action( 'admin_enqueue_scripts', 'sb_elem_cfd_css_enqueue', 9999 );

	add_action( 'elementor_pro/forms/new_record', 'sb_elem_cfd_new_record', 10, 10 );

	add_action( 'add_meta_boxes', 'sb_elem_cfd_register_meta_box' );
	add_action( 'init', 'sb_elem_cfd_pt_init' );
	add_action( 'admin_notices', 'sb_elem_cfd_admin_notice' );
	add_action( 'admin_head', 'sb_elem_cfd_admin_head' );
	add_action( 'admin_menu', 'sb_elem_cfd_submenu' );
	add_action( 'admin_init', 'sb_elem_cfd_download_csv', 1, 1 );

	add_filter( 'manage_elementor_cf_db_posts_columns', 'sb_elem_cfd_columns_head', 100 );
	add_action( 'manage_elementor_cf_db_posts_custom_column', 'sb_elem_cfd_columns_content', 100, 2 );
}

function sb_elem_cfd_submenu() {

	$sb_elem_cfd = get_option( 'sb_elem_cfd' ) ? get_option( 'sb_elem_cfd' ) : '';
	$min_role    = (isset( $sb_elem_cfd['records_min_role'] ) ? sanitize_text_field($sb_elem_cfd['records_min_role']) : 'administrator');

	add_submenu_page( 'edit.php?post_type=elementor_cf_db', 'Export', 'Export', $min_role, 'sb_elem_cfd', 'sb_elem_cfd_submenu_cb' );
	add_submenu_page( 'edit.php?post_type=elementor_cf_db', 'Settings', 'Settings', 'manage_options', 'sb_elem_cfd_settings', 'sb_elem_cfd_settings_submenu_cb' );

	sb_elem_cfd_disable_add_new();
}

function sb_elem_cfd_disable_add_new() {
	// Hide sidebar link
	global $submenu;
	if(isset($submenu['edit.php?post_type=elementor_cf_db'][10])) unset( $submenu['edit.php?post_type=elementor_cf_db'][10] );

}

function sb_elem_cfd_box_start($title) {
	return '<div class="postbox">
                    <h2 class="hndle">' . esc_attr($title) . '</h2>
                    <div class="inside">';
}

function sb_elem_cfd_download_csv() {

	if(isset( $_REQUEST['download_csv'] )) {
		if(!empty( $_POST['sb_elem_cfd_export'] )) {
			if(wp_verify_nonce( $_POST['sb_elem_cfd_export'], 'sb_elem_cfd_export' )) {
				echo '<input name="sb_elem_cfd_export" type="hidden" value="' . wp_create_nonce( 'sb_elem_cfd_export' ) . '" />';

				if(isset( $_REQUEST['form_name'] )) {
					$form_name = sanitize_text_field($_REQUEST['form_name']);
					if($rows = sb_elem_cfd_get_export_rows( $form_name )) {

						header( 'Content-Type: application/csv' );
						header( 'Content-Disposition: attachment; filename=' . sanitize_title( $form_name ) . '.csv' );
						header( 'Pragma: no-cache' );
						$rows_html = implode( "\n", $rows );
						echo esc_attr($rows_html);
						die;
					}
				}

				if(isset( $_REQUEST['form_id'] )) {
					$form_id = sanitize_text_field($_REQUEST['form_id']);
					if($rows = sb_elem_cfd_get_export_rows_by_form_id( $form_id )) {

						header( 'Content-Type: application/csv' );
						header( 'Content-Disposition: attachment; filename=' . sanitize_title( $form_id ) . '.csv' );
						header( 'Pragma: no-cache' );
						$rows_html = implode( "\n", $rows );
						echo esc_attr($rows_html);
						die;
					}
				}
			}
		}
	}
}

function sb_elem_cfd_box_end() {
	return '    <div style="clear: both;">&nbsp;</div></div>
                </div>';
}


function sb_elem_cfd_submenu_cb() {
	global $wpdb;

	$forms = $forms2 = array();

	$sql = 'SELECT DISTINCT(pm.meta_value) AS form_name
			FROM 
				' . $wpdb->posts . ' p 
				JOIN ' . $wpdb->postmeta . ' pm ON (
					p.ID = pm.post_id AND 
					pm.meta_key = "sb_elem_cfd_form_id"
				) 
			WHERE 
				p.post_type = "elementor_cf_db"
				AND p.post_status = "publish"';


	$sql2 = 'SELECT DISTINCT(pm.meta_value) AS submitted_id
			FROM 
				' . $wpdb->posts . ' p 
				JOIN ' . $wpdb->postmeta . ' pm ON (
					p.ID = pm.post_id AND 
					pm.meta_key = "sb_elem_cfd_submitted_on_id"
				) 
			WHERE 
				p.post_type = "elementor_cf_db"
				AND p.post_status = "publish"';

	echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
	$sb_item_name = SB_ELEM_CFD_DB_ITEM_NAME ? sanitize_text_field(SB_ELEM_CFD_DB_ITEM_NAME) : '';
	$sb_version = SB_ELEM_CFD_DB_VERSION ? sanitize_text_field(SB_ELEM_CFD_DB_VERSION) : '';
	echo '<h2>' . esc_attr($sb_item_name) . ' - Version ' . esc_attr($sb_version) . '</h2>';

	echo '<div id="poststuff">';

	echo '<div id="post-body" class="metabox-holder columns-2">';

	echo sb_elem_cfd_box_start( 'Export Results' );

	echo '<p>'.__("Use this simple form to export your contact data to CSV file. This is fairly crude but we don\'t have names for forms but we do have the page it was submitted from. Elementor has the facility to give a form an ID (in the additional tab of the builder). If set then you can also export by Form ID which is perhaps more useful!", "elementor-contact-form-db").'</p>';

	if($form_names = $wpdb->get_results( $sql )) {
		foreach($form_names as $form_name) {
			$forms2[$form_name->form_name] = $form_name->form_name;
		}
	}

	if($submitted_ids = $wpdb->get_results( $sql2 )) {
		foreach($submitted_ids as $submitted_id) {
			$forms[$submitted_id->submitted_id] = get_the_title( $submitted_id->submitted_id );
		}
	}

	if(get_posts( 'post_type=elementor_cf_db&posts_per_page=1' )) { //get one record only. we don't need it but just to show there is a single submission

		set_time_limit( 0 );
		//delete_option('sb_elem_cfd_record_update_v15'); //debug

		//updating old data for a faster structure
		if(!get_option( 'sb_elem_cfd_record_update_v15' )) {
			if($posts = get_posts( 'post_type=elementor_cf_db&posts_per_page=4000&meta_key=sb_elem_cfd_submitted_on_id&meta_compare=NOT EXISTS' )) {
				echo __('Found ', 'elementor-contact-form-db') . esc_attr(count( $posts )) . __(' Items to convert.<br />', 'elementor-contact-form-db');

				foreach($posts as $post) {
					if($data = sb_elem_cfd_get_meta( $post->ID )) {
						$forms[$data['extra']['submitted_on_id']] = $data['extra']['submitted_on'];
						$k_submit_id = isset($data['extra']['submitted_on_id']) ? sanitize_text_field($data['extra']['submitted_on_id']) : '';
						update_post_meta( $post->ID, 'sb_elem_cfd_submitted_on_id', $k_submit_id );
					}
				}

				echo '<p>'.__('Data Structure Updated. Refresh the page for a faster interface', 'elementor-contact-form-db').'</p>';
			} else {
				update_option( 'sb_elem_cfd_record_update_v15', time() );
			}

		}

		echo '<h3>'.__('Select a form to export', 'elementor-contact-form-db').'</h3>';

		echo '<form method="POST" style="width: 48%; float: left;">';
		echo '<p><strong>'.__('By Page Submitted', 'elementor-contact-form-db').'</strong></p>';
		echo '<select  style="margin-right: 10px; width: 200px;" name="form_name">';

		ksort( $forms );
		foreach($forms as $form => $label) {
			echo '<option ' . (isset( $_REQUEST['form_name'] ) && $_REQUEST['form_name'] == $form ? 'selected="selected"' : '') . ' value="' . esc_attr($form) . '">' . esc_attr($label) . '</option>';
		}

		echo '</select>';
		echo '<input type="submit" name="" class="button-primary" value="Export Form" />';
		echo '<input name="sb_elem_cfd_export" type="hidden" value="' . wp_create_nonce( 'sb_elem_cfd_export' ) . '" />';
		echo '</form>';

		echo '<form method="POST" style="width: 48%; float: left;">';
		echo '<p><strong>'.__('By form_id (additional options in the form module)', 'elementor-contact-form-db').'</strong></p>';
		echo '<select  style="margin-right: 10px; width: 200px;" name="form_id">';

		ksort( $forms2 );
		foreach($forms2 as $form) {
			echo '<option ' . (isset( $_REQUEST['form_id'] ) && $_REQUEST['form_id'] == $form ? 'selected="selected"' : '') . ' value="' . esc_attr($form) . '">' . esc_attr($form) . '</option>';
		}

		echo '</select>';
		echo '<input type="submit" name="" class="button-primary" value="Export Form" />';
		echo '<input name="sb_elem_cfd_export" type="hidden" value="' . wp_create_nonce( 'sb_elem_cfd_export' ) . '" />';
		echo '</form>';

		echo '<div style="clear: both;">&nbsp;</div>';

		if(isset( $_REQUEST['form_name'] )) {

			$form_name = sanitize_text_field($_REQUEST['form_name']);
			$rows = sb_elem_cfd_get_export_rows( $form_name, 50 );

			echo '<h3>'.__('CSV Content (by Submitted Page)', 'elementor-contact-form-db').'</h3>';
			echo '<p>'.__('Please review the data below and press "Download CSV File" to start the download. This list will show up to 50 submissions. The export will show the full list.', 'elementor-contact-form-db').'</p>';
			$rows_html = implode( '<br />', $rows );
			echo '<div style="margin-top: 20px; min-height: 150px; max-height: 350px; overflow: scroll; margin-bottom: 10px; border: 1px solid #EEE; padding: 20px;">' . esc_attr($rows_html) . '</div>';

			echo '<form method="POST">';
			echo '<input type="hidden" name="form_name" value="' . esc_attr($form_name) . '" />';
			echo '<input type="submit" name="download_csv" class="button-primary" value="Download CSV File" />';
			echo '<input name="sb_elem_cfd_export" type="hidden" value="' . wp_create_nonce( 'sb_elem_cfd_export' ) . '" />';
			echo '</form>';
		} elseif(isset( $_REQUEST['form_id'] )) {

			$form_id = sanitize_text_field($_REQUEST['form_id']);
			$rows = sb_elem_cfd_get_export_rows_by_form_id( $form_id, 50 );

			echo '<h3>'.__('CSV Content (by Form ID)', 'elementor-contact-form-db').'</h3>';
			echo '<p>'.__('Please review the data below and press "Download CSV File" to start the download. This list will show up to 50 submissions. The export will show the full list.', 'elementor-contact-form-db').'</p>';
			$rows_html = implode( '<br />', $rows );
			echo '<div style="margin-top: 20px; min-height: 150px; max-height: 350px; overflow: scroll; margin-bottom: 10px; border: 1px solid #EEE; padding: 20px;">' . esc_attr($rows_html) . '</div>';

			echo '<form method="POST">';
			echo '<input type="hidden" name="form_id" value="' . esc_attr($form_id) . '" />';
			echo '<input type="submit" name="download_csv" class="button-primary" value="Download CSV File" />';
			echo '<input name="sb_elem_cfd_export" type="hidden" value="' . wp_create_nonce( 'sb_elem_cfd_export' ) . '" />';
			echo '</form>';
		}
	} else {
		echo '<p>'.__('This page will show a form when you have at least one submission. Until then, enjoy this picture of a cat!', 'elementor-contact-form-db').'</p>';
		echo '<img src="'.plugin_dir_url( __FILE__ ).'cat.png" />';
	}

	echo sb_elem_cfd_box_end();

	echo '</div>';

	echo '</div>';
	echo '</div>';
}

function sb_elem_cfd_settings_submenu_cb() {

	$sb_item_name = SB_ELEM_CFD_DB_ITEM_NAME ? sanitize_text_field(SB_ELEM_CFD_DB_ITEM_NAME) : '';
	$sb_version = SB_ELEM_CFD_DB_VERSION ? sanitize_text_field(SB_ELEM_CFD_DB_VERSION) : '';

	echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
	echo '<h2>' . esc_attr($sb_item_name) . ' - Version ' . esc_attr($sb_version) . '</h2>';

	echo '<div id="poststuff">';

	echo '<div id="post-body" class="metabox-holder columns-2">';

	if(isset( $_POST['sb_elem_cfd_save'] )) {
		if(!empty( $_POST['sb_elem_cfd_save_settings'] )) {
			if(wp_verify_nonce( $_POST['sb_elem_cfd_save_settings'], 'sb_elem_cfd_save_settings' )) {
				$k_elem_cfd = isset($_POST['sb_elem_cfd']) ? sanitize_text_field($_POST['sb_elem_cfd']) : '';
				update_option( 'sb_elem_cfd', $k_elem_cfd );
				echo '<div id="message" class="updated fade"><p>'.__('Settings saved successfully', 'elementor-contact-form-db').'</p></div>';
			}
		}
	}

	$sb_elem_cfd = get_option( 'sb_elem_cfd' ) ? get_option( 'sb_elem_cfd' ) : '';

	echo sb_elem_cfd_box_start( 'Settings' );

	echo '<p>'.__('This simple form will provide some handy switches and settings for the plugin.', 'elementor-contact-form-db').'</p>';


	echo '<form method="POST">';
	echo '<table class="form-table widefat">';

	echo '<tr>
				<td>'.__('Disable Admin Nag?', 'elementor-contact-form-db').'</td>
                <td>
                	<input type="checkbox" name="sb_elem_cfd[disable_admin_nag]" ' . checked( 1, (isset( $sb_elem_cfd['disable_admin_nag'] ) ? 1 : 0), false ) . ' value="1" />
				</td>
				<td>
					<small>'.__('The admin nag is the red box that shows at the top of your admin pages when there is a contact submission to review. If you would prefer to use the plugin as a backup only then just check this box to turn the nag off..', 'elementor-contact-form-db').'</small>
				</td>
            </tr>';

	ob_start();
	wp_dropdown_roles( isset( $sb_elem_cfd['records_min_role'] ) ? $sb_elem_cfd['records_min_role'] : esc_html_e('administrator', 'elementor-contact-form-db') );
	$role_options = ob_get_clean();

	$select = '<select name="sb_elem_cfd[records_min_role]">' . esc_attr($role_options) . '</select>';

	$title_plural = isset( $sb_elem_cfd['title_plural'] ) ? sanitize_text_field($sb_elem_cfd['title_plural']) : __('Elementor DB', 'elementor-contact-form-db') ;
	$title_singular = isset( $sb_elem_cfd['title_singular'] ) ? sanitize_text_field($sb_elem_cfd['title_singular']) : __('Elementor DB', 'elementor-contact-form-db') ;

	echo '<tr>
				<td>'.__('Minimum role to view records', 'elementor-contact-form-db').'</td>
                <td>' . esc_attr($select) . '</td>
                <td><small>'.__('The minimum role needed to export the records. Normally administrator but some sites may use editor or other roles. Note that this settings page is only ever usable by administrators.', 'elementor-contact-form-db').'</small></td>
            </tr>';

	echo '<tr>
				<td>Menu / Plural Label</td>
                <td><input type="text" name="sb_elem_cfd[title_plural]" value="' . esc_attr($title_plural) . '" /></td>
                <td><small>'.__('The name of the menu item to show. Good for white labelling for clients', 'elementor-contact-form-db').'</small></td>
            </tr>';

	echo '<tr>
				<td>Secondary / Singular Label</td>
                <td><input type="text" name="sb_elem_cfd[title_singular]" value="' . esc_attr($title_singular) . '" /></td>
                <td><small>'.__('The secondary (singular) name of the post type to show. Good for white labelling for clients', 'elementor-contact-form-db').'</small></td>
            </tr>';

	echo '</table>';

	echo '<p>';
	echo '<input name="sb_elem_cfd_save_settings" type="hidden" value="' . wp_create_nonce( 'sb_elem_cfd_save_settings' ) . '" />';
	echo '<input type="submit" name="sb_elem_cfd_save" class="button-primary" value="'.__("Save Settings", "elementor-contact-form-db").' />';
	echo '</p>';

	echo '</form>';

	echo sb_elem_cfd_box_end();

	echo '</div>';

	echo '</div>';
	echo '</div>';
}

function sb_elem_cfd_get_export_rows($submitted_id, $limit = - 1) {
	$rows = array();
	$args = 'post_type=elementor_cf_db&meta_key=sb_elem_cfd_submitted_on_id&posts_per_page=' . $limit . '&meta_value=' . $submitted_id;

	if($posts = get_posts( $args )) {

		$first_post = current( $posts );

		$row = '';
		$row .= esc_html_e('"Date","Submitted On","Form ID","Submitted By",', 'elementor-contact-form-db');

		if($data = sb_elem_cfd_get_meta( $first_post->ID )) {
			foreach($data['data'] as $field) {
				$row .= '"' . esc_attr($field['label']) . '",';
			}
		}

		$rows[] = rtrim( $row, ',' );

		foreach($posts as $post) {
			if($data = sb_elem_cfd_get_meta( $post->ID )) {
				$row = '';

				$form_id = get_post_meta( $post->ID, 'sb_elem_cfd_form_id', true ) ? get_post_meta( $post->ID, 'sb_elem_cfd_form_id', true ) : '';
				$k_date = $post->post_date ? $post->post_date : '';
				$k_submit_on = isset($data['extra']['submitted_on']) ? sanitize_text_field($data['extra']['submitted_on']) : '';
				$k_submit_by = isset($data['extra']['submitted_by']) ? sanitize_text_field($data['extra']['submitted_by']) : '';
				$row     .= '"' . esc_attr($k_date) . '","' . esc_attr($k_submit_on) . '","' . esc_attr($form_id) . '","' . esc_attr($k_submit_by) . '",';

				foreach($data['data'] as $field) {
					$row .= '"' . addslashes( $field['value'] ) . '",';
				}

				$rows[] = rtrim( $row, ',' );
			}
		}
	}

	return $rows;
}

function sb_elem_cfd_get_meta($sub_id) {
	global $wpdb;

	$return = false;

	$sql = 'SELECT meta_value
			FROM ' . $wpdb->postmeta . '
			WHERE
				meta_key = "sb_elem_cfd"
				AND post_id = ' . $sub_id;

	if($meta = $wpdb->get_var( $sql )) {
		$return = unserialize( $meta );
	}

	return $return;
}

function sb_elem_cfd_get_export_rows_by_form_id($form_id, $limit = - 1) {

	$rows = array();

	if($posts = get_posts( 'post_type=elementor_cf_db&posts_per_page=' . $limit . '&meta_key=sb_elem_cfd_form_id&meta_value=' . $form_id )) {
		$row = '';
		$row .= esc_html_e('"Date","Submitted On","Form ID","Submitted By",', 'elementor-contact-form-db');

		//labels. loop once
		$first_post = current( $posts );
		$data       = sb_elem_cfd_get_meta( $first_post->ID );

		foreach($data['data'] as $field) {
			$row .= '"' . esc_attr($field['label']) . '",';
		}

		$rows[] = rtrim( $row, ',' );

		//fields
		foreach($posts as $post) {
			$data = sb_elem_cfd_get_meta( $post->ID );

			$row = '';
			$k_date = $post->post_date ? $post->post_date : '';
			$k_submit_on = isset($data['extra']['submitted_on']) ? sanitize_text_field($data['extra']['submitted_on']) : '';
			$k_submit_by = isset($data['extra']['submitted_by']) ? sanitize_text_field($data['extra']['submitted_by']) : '';
			$row .= '"' . esc_attr($k_date) . '","' . esc_attr($k_submit_on) . '","' . esc_attr($form_id) . '","' . esc_attr($k_submit_by) . '",';

			foreach($data['data'] as $field) {
				$row .= '"' . addslashes( $field['value'] ) . '",';
			}

			$rows[] = rtrim( $row, ',' );
		}
	}

	return $rows;
}

function sb_elem_cfd_css_enqueue() {
	global $current_screen;

	if($current_screen->id == 'elementor_cf_db') {
		wp_enqueue_script( 'sb_elem_cfd_js', plugins_url( '/script.js', __FILE__ ) );
	}
}

function sb_elem_cfd_columns_head($defaults) {
	if($defaults['date']) unset( $defaults['date'] );
	//unset($defaults['cb']);
	if($defaults['title']) unset( $defaults['title'] );

	$defaults['cf_elementor_title'] = __('View', 'elementor-contact-form-db');
	$defaults['form_id']            = __('Form ID', 'elementor-contact-form-db');
	$defaults['email']              = __('Email', 'elementor-contact-form-db');
	$defaults['read']               = __('Read/Unread', 'elementor-contact-form-db');
	$defaults['cloned']             = __('Cloned', 'elementor-contact-form-db');
	$defaults['sub_on']             = __('Submitted On', 'elementor-contact-form-db');
	$defaults['sub_date']           = __('Submission Date', 'elementor-contact-form-db');

	return $defaults;
}

// SHOW THE FEATURED IMAGE
function sb_elem_cfd_columns_content($column_name, $post_id) {
	$contact = get_post( $post_id );
	$data    = get_post_meta( $post_id, 'sb_elem_cfd', true ) ? get_post_meta( $post_id, 'sb_elem_cfd', true ) : '';

	if($column_name == 'cf_elementor_title') {
		echo '<a href="' . esc_url(admin_url( 'post.php?action=edit&post=' . $post_id )) . '">'.__('View Submission', 'elementor-contact-form-db').'</a>';
	} elseif($column_name == 'read') {
		if($read = get_post_meta( $post_id, 'sb_elem_cfd_read', true )) {
			echo '<span style="color: green;">' . esc_attr($read['by_name']) . '<br />' . date( 'Y-m-d H:i', $read['on'] ) . '</span>';
		} else {
			echo '<span class="dashicons dashicons-email-alt"></span>';
		}
	} elseif($column_name == 'sub_on') {
		if($data['extra']['submitted_on']) {
			$k_submit_id = isset($data['extra']['submitted_on_id']) ? sanitize_text_field($data['extra']['submitted_on_id']) : '';
			echo '<a href="' . esc_url(get_permalink( $k_submit_id )) . '">' . esc_attr($data['extra']['submitted_on']) . '</a>';
		}
	} elseif($column_name == 'sub_date') {
		echo esc_attr($contact->post_date);
	} elseif($column_name == 'cloned') {
		if($cloned = get_post_meta( $post_id, 'sb_elem_cfd_cloned', true )) {
			$cloned_count = count( $cloned );

			echo '<span class="dashicons dashicons-yes"></span> (' . esc_attr($cloned_count) . ')';
		} else {
			echo '<span class="dashicons dashicons-no-alt"></span>';
		}
	} elseif($column_name == 'email') {
		if($email = get_post_meta( $post_id, 'sb_elem_cfd_email', true )) {
			$email = '<a href="mailto:' . esc_attr($email) . '" target="_blank">' . esc_attr($email) . '</a>';
		} else {
			$email = '-';
		}
		echo esc_attr($email);
	} elseif($column_name == 'form_id') {
		if(!$form_id = get_post_meta( $post_id, 'sb_elem_cfd_form_id', true )) {
			$form_id = '-';
		}

		echo esc_attr($form_id);
	}
}

function sb_elem_cfd_admin_head() {
	global $current_user;

	// Hide link on listing page
	if((isset( $_GET['post_type'] ) && $_GET['post_type'] == 'elementor_cf_db') || (isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'elementor_cf_db')) {
		echo '<style type="text/css">
	    .page-title-action, #favorite-actions, .add-new-h2 { display:none; }
	    </style>';
	}

	if(isset( $_GET['sb-action'] )) {
		$action = sanitize_text_field($_GET['sb-action']);

		if($action == 'mark-all-read') {
			$args = array(
				'posts_per_page' => - 1,
				'meta_key'       => 'sb_elem_cfd_read',
				'meta_value'     => 0,
				'post_type'      => 'elementor_cf_db',
				'post_status'    => 'publish',
			);

			if($other_contacts = get_posts( $args )) {
				foreach($other_contacts as $other_contact) {
					$read = array(
						'by_name' => $current_user->display_name,
						'by'      => $current_user->ID,
						'on'      => time()
					);
					update_post_meta( $other_contact->ID, 'sb_elem_cfd_read', $read );
				}
			}
		}
	}
}

function sb_elem_cfd_admin_notice() {
	if(!current_user_can( 'administrator' )) {
		return;
	}

	$sb_elem_cfd = get_option( 'sb_elem_cfd' ) ? get_option( 'sb_elem_cfd' ) : '';

	if(isset( $sb_elem_cfd['disable_admin_nag'] ) && $sb_elem_cfd['disable_admin_nag']) {
		return;
	}

	$args = array(
		'posts_per_page' => - 1,
		'meta_key'       => 'sb_elem_cfd_read',
		'meta_value'     => 0,
		'post_type'      => 'elementor_cf_db',
		'post_status'    => 'publish',
	);

	if($other_contacts = get_posts( $args )) {
		//Use notice-warning for a yellow/orange, and notice-info for a blue left border.
		$class   = 'notice notice-error is-dismissible';
		$message = __( 'You have ' . count( $other_contacts ) . ' unread contact form submissions. Click <a href="' . esc_url(admin_url( 'edit.php?post_type=elementor_cf_db' )) . '">here</a> to visit them or click <a href="' . esc_url(admin_url( 'edit.php?post_type=elementor_cf_db&sb-action=mark-all-read' )) . '">here</a> to mark all as read', 'elementor-contact-form-db' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
	}
}

function sb_elem_cfd_register_meta_box() {
	add_meta_box( 'sb_elem_cfd', esc_html__( 'Form Submission', 'elementor-contact-form-db' ), 'sb_elem_cfd_meta_box_callback', 'elementor_cf_db', 'normal', 'high' );
	add_meta_box( 'sb_elem_cfd_extra', esc_html__( 'Extra Information', 'elementor-contact-form-db' ), 'sb_elem_cfd_meta_box_callback_extra', 'elementor_cf_db', 'normal', 'high' );
	add_meta_box( 'sb_elem_cfd_actions', esc_html__( 'Actions', 'elementor-contact-form-db' ), 'sb_elem_cfd_meta_box_callback_actions', 'elementor_cf_db', 'normal', 'high' );
}

function sb_elem_cfd_meta_box_callback() {
	global $current_user;

	$submission = get_post( get_the_ID() );

	if(!$read = get_post_meta( get_the_ID(), 'sb_elem_cfd_read', true )) {
		$read = array('by_name' => $current_user->display_name, 'by' => $current_user->ID, 'on' => time());
		update_post_meta( get_the_ID(), 'sb_elem_cfd_read', $read );
	}

	$class   = 'notice notice-info';
	$message = 'First read by ' . esc_attr($read['by_name']) . ' at ' . date( 'Y-m-d H:i', $read['on'] );
	printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

	if($data = get_post_meta( get_the_ID(), 'sb_elem_cfd', true )) {

		if($fields = $data['data']) {
			echo '<table class="widefat">
                        <thead>
                        <tr>
                            <th>'.__('Label', 'elementor-contact-form-db').'</th>
                            <th>'.__('Value', 'elementor-contact-form-db').'</th>
                        </tr>
                        </thead>
                        <tbody>';

			foreach($fields as $field) {
				$value = $field['value'] ? $field['value'] : '';

				if(is_email( $value )) {
					$value = '<a href="mailto:' . esc_attr($value) . '" target="_blank">' . esc_attr($value) . '</a>';
				}

				echo '<tr>
                            <td><strong>' . esc_attr($field['label']) . '</strong></td>
                            <td>' . wpautop( esc_attr( $value ) ) . '</td>
                        </tr>';
			}

			echo '<tr>
                            <td><strong>Date of Submission</strong></td>
                            <td>' . esc_attr($submission->post_date) . '</td>
                        </tr>';

			echo '</tbody>
                </table>';
		}
	}

}

function sb_elem_cfd_meta_box_callback_extra() {
	$other_submissions = '';

	if($data = get_post_meta( get_the_ID(), 'sb_elem_cfd', true )) {
		if($extra = $data['extra']) {
			echo '<table class="widefat">
                        <thead>
                        <tr>
                            <th>'.__('Label', 'elementor-contact-form-db').'</th>
                            <th>'.__('Value', 'elementor-contact-form-db').'</th>
                        </tr>
                        </thead>
                        <tbody>';

			foreach($extra as $key => $value) {

				switch($key) {
					case 'submitted_on_id':
					case 'submitted_by_id':
						continue(2); //we don't really care about these ones
						break;
					case 'submitted_on':
						if($extra['submitted_on_id']) {
							$value = $value . ' (<a href="' . esc_url(get_permalink( $extra['submitted_on_id'] )) . '" target="_blank">View Page</a> | <a href="' . esc_url(admin_url( 'post.php?action=edit&post=' . $extra['submitted_on_id'] )) . '" target="_blank">'.__('Edit Page', 'elementor-contact-form-db').'</a>)';
						} else {
							$value = '<em>'.__('Unknown', 'elementor-contact-form-db').'</em>';
						}
						break;
					case 'submitted_by':
						if($extra['submitted_by_id']) {
							$value = $value . ' (<a href="' . esc_url(admin_url( 'user-edit.php?user_id=' . $extra['submitted_by_id'] )) . '" target="_blank">'.__('View User Profiile', 'elementor-contact-form-db').'</a>';

							$args = array(
								'posts_per_page' => - 1,
								'meta_key'       => 'sb_elem_cfd_submitted_by',
								'meta_value'     => $extra['submitted_by_id'],
								'post_type'      => 'elementor_cf_db',
								'post_status'    => 'publish',
							);

							if($other_contacts = get_posts( $args )) {
								$value             .= ' | <a style="cursor: pointer;" onclick="jQuery(\'.other_submissions\').slideToggle();">View ' . esc_attr(count( $other_contacts )) . ' more submissions by this user</a>';
								$other_submissions .= '<div style="display: none;" class="other_submissions">
                                                            <h3>'.__('Other submissions made by the same person', 'elementor-contact-form-db').'</h3>';
								$other_submissions .= '<table class="widefat">';

								foreach($other_contacts as $other_contact) {
									$other_submissions .= '<tr><td><a href="' . esc_url(admin_url( 'post.php?action=edit&post=' . $other_contact->ID )) . '">' . $other_contact->post_title . '</a></td></tr>';
								}

								$other_submissions .= '</table></div>';
							}

							$value .= ')';
						} else {
							$value = '<em>'.__('Not a registered user', 'elementor-contact-form-db').'</em>';
						}

						break;
				}

				$key_label = ucwords( str_replace( '_', ' ', $key ) );

				echo '<tr>
                            <td><strong>' . esc_attr($key_label) . '</strong></td>
                            <td>' . esc_attr($value) . '</td>
                        </tr>';
			}

			echo '</tbody>
                </table>';

			echo esc_attr($other_submissions);
		}

	}

}

function sb_elem_cfd_meta_box_callback_actions() {
	$submission = get_post( get_the_ID() );
	$data       = get_post_meta( get_the_ID(), 'sb_elem_cfd', true ) ? get_post_meta( get_the_ID(), 'sb_elem_cfd', true ) : '';

	$w_post = isset($_GET['post']) ? sanitize_text_field($_GET['post']) : '';
	if(isset( $_POST['sb_elem_cfd_map_to'] )) {
		$map_to       = sanitize_text_field($_POST['sb_elem_cfd_map_to']);
		$map_to_other = isset($_POST['sb_elem_cfd_map_to_other']) ? sanitize_text_field($_POST['sb_elem_cfd_map_to_other']) : '';

		if($fields = $data['data']) {
			$mapped_fields = array();
			$custom_fields = array();

			foreach($fields as $field) {
				$mapped_fields[$field['label']] = isset($field['value']) ? sanitize_text_field($field['value']) : '';
			}

			$k_pt = isset($_POST['sb_elem_cfd_pt']) ? sanitize_text_field($_POST['sb_elem_cfd_pt']) : '';

			$db_ins = array(
				'post_title'   => esc_html_e('Cloned from contact form', 'elementor-contact-form-db'),
				'post_content' => esc_html_e('Cloned from contact form', 'elementor-contact-form-db'),
				'post_status'  => 'draft',
				'post_type'    => $k_pt,
			);

			if(isset( $_POST['sb_elem_cfd_date'] )) {
				$db_ins['post_date'] = sanitize_text_field($_POST['sb_elem_cfd_date']);
			}

			$found = 0;

			foreach($map_to as $key => $field) {
				if($field) {
					$found ++;

					if($field == 'custom_field') {
						if($map_to_other[$key]) {
							$custom_fields[$map_to_other[$key]] = $mapped_fields[$key];
						}
					} else {
						$db_ins[$field] = $mapped_fields[$key];
					}
				}
			}

			if($found) {
				// Insert the post into the database
				if($post_id = wp_insert_post( $db_ins )) {
					if(!is_wp_error( $post_id )) {
						foreach($custom_fields as $key => $value) {
							update_post_meta( $post_id, $key, $value );
						}

						echo '<div id="message" class="updated fade">
                                    <p>'.__('Successfully copied the content of this contact form submission to another post type. Click here to', 'elementor-contact-form-db').' <a href="' . esc_url(get_permalink( $post_id )) . '">View</a> or <a href="' . esc_url(admin_url( 'post.php?action=edit&post=' . $post_id )) . '">'.__('Edit', 'elementor-contact-form-db').'</a></p>
                                </div>';

						if(!$cloned = get_post_meta( $w_post, 'sb_elem_cfd_cloned', true )) {
							$cloned = array();
						}

						$cloned[$post_id] = time();

						update_post_meta( $w_post, 'sb_elem_cfd_cloned', $cloned );

					} else {
						echo '<div id="message" class="error fade">
                                    <p>'.__('Oops something went wrong. This error message may be helpful:', 'elementor-contact-form-db').' ' . print_r( $post_id, true ) . '</p>
                                </div>';
					}
				}
			} else {
				echo '<div id="message" class="error fade">
                            <p>'.__('You need to choose at least one field to map against for the clone to work.', 'elementor-contact-form-db').'</p>
                        </div>';
			}
		}
	}

	$map_to_options = array();
	$maps           = array(
		'post_title'   => esc_html_e('Title', 'elementor-contact-form-db'),
		'post_content' => esc_html_e('Content', 'elementor-contact-form-db'),
		'custom_field' => 'Custom Field'
	);

	foreach($maps as $key => $value) {
		$map_to_options[] = '<option value="' . esc_attr($key) . '">' . esc_attr($value) . '</option>';
	}

	$types        = get_post_types();
	$type_options = array();

	foreach($types as $type2) {
		$type_obj2 = get_post_type_object( $type2 );

		if(!$type_obj2->public) {
			continue;
		}

		$type_options[] = '<option value="' . esc_attr($type2) . '">' . esc_attr($type_obj2->labels->name) . '</option>';
	}

	echo '<p>';

	if($email = get_post_meta( get_the_ID(), 'sb_elem_cfd_email', true )) {
		echo '<a style="margin-right: 10px;" class="button-primary" target="_blank" href="mailto:' . esc_attr($email) . '">'.__('Reply via Email', 'elementor-contact-form-db').'</a>';
	}

	echo '<a onclick="jQuery(\'.sb_elem_cfd_convert\').slideToggle();" class="button-secondary">'.__('Copy to another Post Type', 'elementor-contact-form-db').'</a>';

	echo '</p>';

	///////////////////////////////////

	echo '<div style="display: none; overflow: scroll;" class="sb_elem_cfd_convert">';

	echo '<h3>'.__('Copy to another post type', 'elementor-contact-form-db').'</h3>';

	$type_options_ht = implode( '', $type_options );

	echo '<p><label>'.__('Select Post Type: ', 'elementor-contact-form-db').'<select name="sb_elem_cfd_pt">' . esc_attr($type_options_ht) . '</select></label></p>';
	echo '<p>'.__('Select Field Mappings:', 'elementor-contact-form-db').'</p>';

	echo '<table class="widefat">';

	foreach($data['fields_original']['form_fields'] as $field) {
		$map_to_options_ht = implode( '', $map_to_options );
		echo '<tr>
                    <td>' . esc_attr($field['field_label']) . '</td>
                    <td>
                        <select name="sb_elem_cfd_map_to[' . esc_attr($field['field_label']) . ']"><option value="">-- Unused --</option>' . esc_attr($map_to_options_ht) . '</select>
                        <span style="margin-left: 20px; display: inline-block;">(If "Custom Field" selected, enter field name: <input type="text" name="sb_elem_cfd_map_to_other[' . esc_attr($field['field_label']) . ']" />)</span>
                    </td>
                </tr>';
	}

	echo '</table>';

	echo '<p><label><input type="checkbox" name="sb_elem_cfd_date" value="' . esc_attr($submission->post_date) . '" />&nbsp;Keep date of original submission? (' . esc_attr($submission->post_date) . ')</label></p>';
	echo '<p><input type="submit" class="button-primary sb_elem_cfd_copy" value="Copy" /></p>';

	echo '</div>';

	if($cloned = get_post_meta( $w_post, 'sb_elem_cfd_cloned', true )) {
		echo '<h3>'.__('Clone History', 'elementor-contact-form-db').'</h3>';

		echo '<table class="widefat">
                    <thead>
                        <tr>
                            <th>'.__('New Post Title', 'elementor-contact-form-db').'</th>
                            <th>'.__('Post Type', 'elementor-contact-form-db').'</th>
                            <th>'.__('Date Cloned', 'elementor-contact-form-db').'</th>
                            <th>'.__('Actions', 'elementor-contact-form-db').'</th>
                        </tr>
                    </thead>';

		foreach($cloned as $cloned_id => $date) {
			if($cloned_post = get_post( $cloned_id )) {
				$type_obj  = get_post_type_object( $cloned_post->post_type );
				$type_name = $type_obj->labels->name ? $type_obj->labels->name : '';

				echo '<tr>
                            <td>' . esc_attr($cloned_post->post_title) . '</td>
                            <td>' . esc_attr($type_name) . '</td>
                            <td>' . date( 'Y-m-d H:i', $date ) . '</td>
                            <td><a href="' . esc_url(get_permalink( $cloned_id )) . '">View</a> | <a href="' . esc_url(admin_url( 'post.php?action=edit&post=' . $post_id )) . '">Edit</a></td>
                        </tr>';
			}
		}

		echo '</table>';

	}
}

function sb_elem_cfd_meta_box_callback_debug() {

	if($data = get_post_meta( get_the_ID(), 'sb_elem_cfd', true )) {
		echo '<div style="display: none; overflow: scroll;" class="sb_elem_cfd_debug">';

		echo '<pre>';
		print_r( esc_attr($data) );
		echo '</pre>';

		echo '</div>';

		echo '<p><a onclick="jQuery(\'.sb_elem_cfd_debug\').slideToggle();" class="button-secondary">'.__('Reveal Debug/Server Information', 'elementor-contact-form-db').'</a></p>';
	}

}

function sb_elem_cfd_pt_init() {
	$sb_elem_cfd = get_option( 'sb_elem_cfd' ) ? get_option( 'sb_elem_cfd' ) : '';
	$title_singular    = (isset( $sb_elem_cfd['title_singular'] ) ? $sb_elem_cfd['title_singular'] : _x( 'Elementor DB', 'post type singular name', 'elementor-contact-form-db' ));
	$title_plural    = isset( $sb_elem_cfd['title_plural'] ) ? $sb_elem_cfd['title_plural'] : $title_singular;

	$labels = array(
		'name'               => $title_plural,
		'singular_name'      => $title_singular,
		'menu_name'          => $title_plural,
		'name_admin_bar'     => $title_plural,
		'add_new'            => _x( 'Add New', 'Elementor DB', 'elementor-contact-form-db' ),
		'add_new_item'       => __( 'Add New', 'elementor-contact-form-db' ),
		'new_item'           => __( 'New', 'elementor-contact-form-db' ) . ' ' . $title_singular,
		'edit_item'          => __( 'Edit', 'elementor-contact-form-db' ) . ' ' . $title_singular,
		'view_item'          => __( 'View', 'elementor-contact-form-db' ) . ' ' . $title_singular,
		'all_items'          => __( 'All', 'elementor-contact-form-db' ) . ' ' . $title_singular,
		'search_items'       => __( 'Search', 'elementor-contact-form-db' ) . ' ' . $title_singular,
		'parent_item_colon'  => __( 'Parent:', 'elementor-contact-form-db' ) . ' ' . $title_singular,
		'not_found'          => __( 'No contact form submissions found.', 'elementor-contact-form-db' ),
		'not_found_in_trash' => __( 'No contact form submissions found in Trash.', 'elementor-contact-form-db' )
	);

	$args = array(
		'labels'             => $labels,
		'description'        => __( 'For storing Elementor contact form submissions.', 'elementor-contact-form-db' ),
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => false,
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => null,
		'menu_icon'          => 'dashicons-admin-comments',
		'supports'           => array('title')
	);

	register_post_type( 'elementor_cf_db', $args );
}

function sb_elem_cfd_new_record($record, $form_class) {

	if($fields = $record->get_formatted_data()) {
		$data  = array();
		$email = false;

		foreach($fields as $label => $value) {

			if(stripos( $label, 'email' ) !== false) {
				$email = $value;
			}

			$data[] = array('label' => $label, 'value' => sanitize_text_field( $value ));
		}

		$this_pageid = isset($_POST['post_id']) ? sanitize_text_field($_POST['post_id']) : '';
		$this_page    = get_post( $this_pageid );
		$this_user    = false;
		$current_user = get_current_user_id();

		if($this_user_id = ($current_user ? $current_user : 0)) {
			if($this_user = get_userdata( $this_user_id )) {
				$this_user = $this_user->display_name;
			}
		}

		$extra = array(
			'submitted_on'    => $this_page->post_title,
			'submitted_on_id' => $this_page->ID,
			'submitted_by'    => $this_user,
			'submitted_by_id' => $this_user_id
		);

		$db_ins = array(
			'post_title'  => $record->get_form_settings( 'form_name' ) . ' - ' . date( 'Y-m-d H:i:s' ),
			'post_status' => 'publish',
			'post_type'   => 'elementor_cf_db',
		);

		// Insert the post into the database
		if($post_id = wp_insert_post( $db_ins )) {
			update_post_meta( $post_id, 'sb_elem_cfd', array(
				'data'            => $data,
				'extra'           => $extra,
				'fields_original' => array('form_fields' => $record->get_form_settings( 'form_fields' )),
				'record_original' => $record,
			) );

			if($this_user_id) {
				update_post_meta( $post_id, 'sb_elem_cfd_submitted_by', $this_user_id );
			}

			update_post_meta( $post_id, 'sb_elem_cfd_read', 0 );
			update_post_meta( $post_id, 'sb_elem_cfd_email', $email );
			update_post_meta( $post_id, 'sb_elem_cfd_form_id', $record->get_form_settings( 'form_name' ) );
			update_post_meta( $post_id, 'sb_elem_cfd_submitted_on_id', $this_page->ID );

		}
	}
}
