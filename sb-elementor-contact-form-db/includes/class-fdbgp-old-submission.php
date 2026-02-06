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
        // Init hooks
        add_action('init', array($this, 'init_hooks'));
        add_action('admin_init', array($this, 'handle_csv_download'));
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('elementor_pro/forms/new_record', array($this, 'save_legacy_record'), 10, 2);
    }

    /**
     * Initialize hooks and post type
     */
    public function init_hooks() {
        // Register post type if not exists (it might be registered by old plugin if active, but we should ensure it exists)
        // $this->register_post_type();
    }

    /**
     * Register Custom Post Type for Legacy Submissions
     */
    private function register_post_type() {
        if (!post_type_exists('elementor_cf_db')) {
            $labels = array(
                'name'               => _x( 'Elementor DB', 'post type general name', 'sb-elementor-contact-form-db' ),
                'singular_name'      => _x( 'Elementor DB', 'post type singular name', 'sb-elementor-contact-form-db' ),
                'menu_name'          => _x( 'Elementor DB', 'admin menu', 'sb-elementor-contact-form-db' ),
                'name_admin_bar'     => _x( 'Elementor DB', 'add new on admin bar', 'sb-elementor-contact-form-db' ),
                'add_new'            => _x( 'Add New', 'elementor_cf_db', 'sb-elementor-contact-form-db' ),
                'add_new_item'       => __( 'Add New Submission', 'sb-elementor-contact-form-db' ),
                'new_item'           => __( 'New Submission', 'sb-elementor-contact-form-db' ),
                'edit_item'          => __( 'Edit Submission', 'sb-elementor-contact-form-db' ),
                'view_item'          => __( 'View Submission', 'sb-elementor-contact-form-db' ),
                'all_items'          => __( 'All Submissions', 'sb-elementor-contact-form-db' ),
                'search_items'       => __( 'Search Submissions', 'sb-elementor-contact-form-db' ),
                'parent_item_colon'  => __( 'Parent Submissions:', 'sb-elementor-contact-form-db' ),
                'not_found'          => __( 'No submissions found.', 'sb-elementor-contact-form-db' ),
                'not_found_in_trash' => __( 'No submissions found in Trash.', 'sb-elementor-contact-form-db' )
            );
    
            $args = array(
                'labels'             => $labels,
                'public'             => false,
                'publicly_queryable' => false,
                'show_ui'            => false, // We use our own UI
                'show_in_menu'       => false,
                'query_var'          => true,
                'rewrite'            => array( 'slug' => 'elementor_cf_db' ),
                'capability_type'    => 'post',
                'has_archive'        => true,
                'hierarchical'       => false,
                'menu_position'      => null,
                'supports'           => array( 'title', 'editor', 'author' )
            );
    
            register_post_type( 'elementor_cf_db', $args );
        }
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
            // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date	
            'post_title' => $form_name . ' - ' . date('Y-m-d H:i:s'),
            'post_status' => 'publish',
            'post_type' => 'elementor_cf_db',
        );

        $post_id = wp_insert_post($post_data);

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, 'sb_elem_cfd', $meta);
            update_post_meta($post_id, 'sb_elem_cfd_form_id', $form_name);
            update_post_meta($post_id, 'sb_elem_cfd_submitted_on_id', get_the_ID());
            
            // Add read status
            $read = array(
                'by_name' => '',
                'by' => 0,
                'on' => 0
            );
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
    
    // ... (get_form_ids and get_submitted_pages skipped for brevity if unchanged, but actually I need to preserve them. I will include everything to be safe or use replace correctly) ...
    // Wait, replace_file_content replaces a block. I need to be careful not to cut off methods.
    // I will replace from handle_actions down to get_submission_count, modifying what's needed.

    // ...



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
                            $row .= '"' . addslashes($field['value']) . '",';
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
                            $row .= '"' . addslashes($field['value']) . '",';
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
