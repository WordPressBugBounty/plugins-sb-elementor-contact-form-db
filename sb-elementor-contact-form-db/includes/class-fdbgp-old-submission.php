<?php
/**
 * Old Submission Handler Class
 * 
 * Handles legacy form submissions from the old plugin version
 * stored in elementor_cf_db post type with sb_elem_cfd meta key.
 */

if (!defined('ABSPATH')) {
    die;
}

class FDBGP_Old_Submission {

    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_init', array($this, 'handle_csv_download'));
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('elementor_pro/forms/new_record', array($this, 'save_legacy_record'), 10, 2);
    }

    /**
     * Save record in legacy format
     * 
     * @param \ElementorPro\Modules\Forms\Classes\Record $record
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $handler
     */
    public function save_legacy_record($record, $handler) {
        // Check if legacy saving is enabled
        if (!$this->is_legacy_save_enabled()) {
            return;
        }

        $form_name = $record->get_form_settings('form_name');
        $form_id = $record->get_form_settings('form_id');
        $raw_fields = $record->get('fields');

        // Prepare data matching old plugin structure
        $fields = array();
        foreach ($raw_fields as $id => $field) {
            $fields[$id] = array(
                'label' => $field['title'],
                'value' => $field['value']
            );
        }

        $meta = array(
            'form_id' => $form_name,
            'data' => $fields,
            'extra' => array(
                'submitted_on' => get_the_title(),
                'submitted_on_id' => get_the_ID(),
                'submitted_by' => is_user_logged_in() ? wp_get_current_user()->display_name : 'Guest',
                'submitted_by_id' => is_user_logged_in() ? get_current_user_id() : 0,
            )
        );

        $post_data = array(
            'post_title' => $form_name . ' - ' . current_time( 'mysql' ),
            'post_status' => 'publish',
            'post_type' => 'elementor_cf_db',
        );

        $post_id = wp_insert_post($post_data);

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, 'sb_elem_cfd', $meta);
            update_post_meta($post_id, 'sb_elem_cfd_form_id', $form_name);
            update_post_meta($post_id, 'sb_elem_cfd_submitted_on_id', get_the_ID());
            update_post_meta($post_id, 'sb_elem_cfd_read', 0); // 0 means unread
        }
    }

    /**
     * Handle actions (toggle legacy mode, delete submission)
     */
    public function handle_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Toggle Legacy Mode
        if (isset($_POST['fdbgp_toggle_legacy_save']) && check_admin_referer('fdbgp_legacy_action', 'fdbgp_legacy_nonce')) {
            $enable = isset($_POST['enable_legacy_save']) ? '1' : '0';
            update_option('fdbgp_legacy_save_enabled', $enable);
            wp_safe_redirect(remove_query_arg(array('fdbgp_toggle_legacy_save', 'enable_legacy_save')));
            exit;
        }

        // Trash Submission
        if (isset($_GET['action']) && $_GET['action'] === 'fdbgp_trash_submission' && isset($_GET['post_id'])) {
            if (check_admin_referer('fdbgp_trash_submission_' . sanitize_text_field(wp_unslash($_GET['post_id'])))) {
                $post_id = intval($_GET['post_id']);
                wp_trash_post($post_id);
                wp_safe_redirect(remove_query_arg(array('action', 'post_id', '_wpnonce')));
                exit;
            }
        }

        // Restore Submission
        if (isset($_GET['action']) && $_GET['action'] === 'fdbgp_restore_submission' && isset($_GET['post_id'])) {
            if (check_admin_referer('fdbgp_restore_submission_' . sanitize_text_field(wp_unslash($_GET['post_id'])))) {
                $post_id = intval($_GET['post_id']);
                wp_untrash_post($post_id);
                wp_update_post(array(
                    'ID'          => $post_id,
                    'post_status' => 'publish',
                ));
                wp_safe_redirect(remove_query_arg(array('action', 'post_id', '_wpnonce')));
                exit;
            }
        }

        // Permanent Delete Submission
        if (isset($_GET['action']) && $_GET['action'] === 'fdbgp_delete_submission' && isset($_GET['post_id'])) {
            if (check_admin_referer('fdbgp_delete_submission_' . sanitize_text_field(wp_unslash($_GET['post_id'])))) {
                $post_id = intval($_GET['post_id']);
                wp_delete_post($post_id, true);
                wp_safe_redirect(remove_query_arg(array('action', 'post_id', '_wpnonce')));
                exit;
            }
        }
    }

    /**
     * Get legacy save status
     */
    public function is_legacy_save_enabled() {
        $default = self::has_old_submissions() ? '1' : '0';
        return get_option( 'fdbgp_legacy_save_enabled', $default ) === '1';
    }

    /**
     * Get all submissions with pagination
     */
    public function get_all_submissions($per_page = 20, $page = 1, $status = 'publish') {
        $args = array(
            'post_type' => 'elementor_cf_db',
            'post_status' => $status,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        return new WP_Query($args);
    }
    
    /**
     * Check if old submissions exist in the database
     * 
     * @return bool
     */
    public static function has_old_submissions() {
        $posts = get_posts(array(
            'post_type' => 'elementor_cf_db',
            'posts_per_page' => 1,
            'post_status' => array('publish', 'trash'), // Check trash too
            'fields' => 'ids'
        ));
        
        return !empty($posts);
    }
    

    /**
     * Get all unique form IDs from old submissions
     * 
     * @return array
     */
    public function get_form_ids() {
        global $wpdb;

        $sql = $wpdb->prepare(
                "SELECT DISTINCT pm.meta_value AS form_id
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm
                    ON p.ID = pm.post_id
                WHERE p.post_type = %s
                AND p.post_status = %s
                AND pm.meta_key = %s
                AND pm.meta_value != ''",
                'elementor_cf_db',
                'publish',
                'sb_elem_cfd_form_id'
            );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query is prepared above with $wpdb->prepare().        
        $results = $wpdb->get_results( $sql );
        $form_ids = array();

        if ($results) {
            foreach ($results as $result) {
                if (!empty($result->form_id)) {
                    $form_ids[$result->form_id] = $result->form_id;
                }
            }
        }

        return $form_ids;
    }

    /**
     * Get all unique submitted pages from old submissions
     * 
     * @return array
     */
    public function get_submitted_pages() {
        global $wpdb;

        $sql = "SELECT DISTINCT(pm.meta_value) AS submitted_id
                FROM {$wpdb->posts} p 
                JOIN {$wpdb->postmeta} pm ON (
                    p.ID = pm.post_id AND 
                    pm.meta_key = 'sb_elem_cfd_submitted_on_id'
                ) 
                WHERE 
                    p.post_type = 'elementor_cf_db'
                    AND p.post_status = 'publish'";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query uses hardcoded safe values, no user input.
        $results = $wpdb->get_results( $sql );
        $pages = array();

        if ($results) {
            foreach ($results as $result) {
                if (!empty($result->submitted_id)) {
                    $pages[$result->submitted_id] = get_the_title($result->submitted_id);
                }
            }
        }

        return $pages;
    }

    /**
     * Get form submission meta data
     * 
     * @param int $post_id
     * @return array|false
     */
    public function get_submission_meta($post_id) {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'sb_elem_cfd' AND post_id = %d",
            $post_id
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query is prepared above with $wpdb->prepare().
        $meta = $wpdb->get_var( $sql );
        
        if ($meta) {
            return maybe_unserialize($meta);
        }

        return false;
    }

    /**
     * Prefix spreadsheet-formula characters so CSV exports open as text in Excel/Sheets.
     *
     * @param mixed $value Cell value.
     * @return string
     */
    private function neutralize_csv_cell( $value ) {
        $value = is_scalar( $value ) ? (string) $value : '';

        if ( $value !== '' && preg_match( '/^[=+\-@]/', $value ) ) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * Escape a value for CSV output (RFC 4180 quoting).
     *
     * @param mixed $value Cell value.
     * @return string
     */
    private function format_csv_cell( $value ) {
        $value = $this->neutralize_csv_cell( $value );

        return '"' . str_replace( '"', '""', (string) $value ) . '"';
    }

    /**
     * Get export rows by submitted page ID
     * 
     * @param int $submitted_id
     * @param int $limit
     * @return array
     */
    public function get_export_rows_by_page($submitted_id, $limit = -1) {
        $rows = array();
        $args = array(
            'post_type' => 'elementor_cf_db',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_key' => 'sb_elem_cfd_submitted_on_id',
            'posts_per_page' => $limit,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            'meta_value' => $submitted_id,
            'post_status' => 'publish'
        );

        $posts = get_posts($args);

        if ($posts) {
            $first_post = current($posts);
            $row = '"Date","Submitted On","Form ID","Submitted By",';

            $data = $this->get_submission_meta($first_post->ID);
            if ($data && isset($data['data'])) {
                foreach ($data['data'] as $field) {
                    $row .= '"' . esc_attr($field['label']) . '",';
                }
            }

            $rows[] = rtrim($row, ',');

            foreach ($posts as $post) {
                $data = $this->get_submission_meta($post->ID);
                if ($data) {
                    $row = '';
                    $form_id = get_post_meta($post->ID, 'sb_elem_cfd_form_id', true);
                    $submitted_on = isset($data['extra']['submitted_on']) ? sanitize_text_field($data['extra']['submitted_on']) : '';
                    $submitted_by = isset($data['extra']['submitted_by']) ? sanitize_text_field($data['extra']['submitted_by']) : '';

                    $row .= '"' . esc_attr($post->post_date) . '","' . esc_attr($submitted_on) . '","' . esc_attr($form_id) . '","' . esc_attr($submitted_by) . '",';

                    if (isset($data['data'])) {
                        foreach ($data['data'] as $field) {
                            $row .= $this->format_csv_cell( isset( $field['value'] ) ? $field['value'] : '' ) . ',';
                        }
                    }

                    $rows[] = rtrim($row, ',');
                }
            }
        }

        return $rows;
    }

    /**
     * Get export rows by form ID
     * 
     * @param string $form_id
     * @param int $limit
     * @return array
     */
    public function get_export_rows_by_form_id($form_id, $limit = -1) {
        $rows = array();
        $args = array(
            'post_type' => 'elementor_cf_db',
            'posts_per_page' => $limit,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
            'meta_key' => 'sb_elem_cfd_form_id',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            'meta_value' => $form_id,
            'post_status' => 'publish'
        );

        $posts = get_posts($args);

        if ($posts) {
            $row = '"Date","Submitted On","Form ID","Submitted By",';

            $first_post = current($posts);
            $data = $this->get_submission_meta($first_post->ID);

            if ($data && isset($data['data'])) {
                foreach ($data['data'] as $field) {
                    $row .= '"' . esc_attr($field['label']) . '",';
                }
            }

            $rows[] = rtrim($row, ',');

            foreach ($posts as $post) {
                $data = $this->get_submission_meta($post->ID);
                if ($data) {
                    $row = '';
                    $submitted_on = isset($data['extra']['submitted_on']) ? sanitize_text_field($data['extra']['submitted_on']) : '';
                    $submitted_by = isset($data['extra']['submitted_by']) ? sanitize_text_field($data['extra']['submitted_by']) : '';

                    $row .= '"' . esc_attr($post->post_date) . '","' . esc_attr($submitted_on) . '","' . esc_attr($form_id) . '","' . esc_attr($submitted_by) . '",';

                    if (isset($data['data'])) {
                        foreach ($data['data'] as $field) {
                            $row .= $this->format_csv_cell( isset( $field['value'] ) ? $field['value'] : '' ) . ',';
                        }
                    }

                    $rows[] = rtrim($row, ',');
                }
            }
        }

        return $rows;
    }

    /**
     * Handle CSV download request
     */
    public function handle_csv_download() {
        if (!isset($_REQUEST['download_old_csv'])) {
            return;
        }

        $nonce = isset( $_POST['fdbgp_old_export_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['fdbgp_old_export_nonce'] ) ) : '';
        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'fdbgp_old_export' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $rows = array();
        $filename = 'old-submissions';

        if ( isset( $_REQUEST['form_name'] ) && ! empty( $_REQUEST['form_name'] ) ) {
            $form_name = sanitize_text_field( wp_unslash( $_REQUEST['form_name'] ) );
            $rows = $this->get_export_rows_by_page( $form_name );
            $filename = sanitize_title( $form_name );
        } elseif ( isset( $_REQUEST['form_id'] ) && ! empty( $_REQUEST['form_id'] ) ) {
            $form_id = sanitize_text_field( wp_unslash( $_REQUEST['form_id'] ) );
            $rows = $this->get_export_rows_by_form_id( $form_id );
            $filename = sanitize_title( $form_id );
        }

        if (!empty($rows)) {
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename=' . $filename . '.csv');
            header('Pragma: no-cache');
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
            echo implode("\n", $rows);
            exit;
        }
    }

    /**
     * Get total submission count
     * 
     * @param string $status
     * @return int
     */
    public function get_submission_count($status = 'publish') {
        $posts = get_posts(array(
            'post_type' => 'elementor_cf_db',
            'posts_per_page' => -1,
            'post_status' => $status,
            'fields' => 'ids'
        ));
        
        return count($posts);
    }
}

// Initialize the class
FDBGP_Old_Submission::get_instance();
