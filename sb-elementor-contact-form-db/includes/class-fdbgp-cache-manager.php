<?php
namespace Formsdb_Elementor_Forms\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FDBGP_Cache_Manager {

    private static $instance = null;

    public static function get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Elementor save hook
        add_action( 'elementor/editor/after_save', [ $this, 'flush_cache' ] );
        
        // General post update hooks (fallback)
        add_action( 'save_post', [ $this, 'flush_cache' ] );
        add_action( 'trashed_post', [ $this, 'flush_cache' ] );
        
        // Add action for manual cache clearing if needed in future
        add_action( 'fdbgp_flush_cache', [ $this, 'flush_cache' ] );
    }

    /**
     * Clear all plugin transients
     */
    public function flush_cache() {
        delete_transient( 'fdbgp_forms_sheet_data' );
        delete_transient( 'fdbgp_forms_posttype_data' );
    }
}

FDBGP_Cache_Manager::get_instance();
