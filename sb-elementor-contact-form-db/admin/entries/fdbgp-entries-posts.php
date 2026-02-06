<?php

namespace Formsdb_Elementor_Forms\Admin\Entries;

use Formsdb_Elementor_Forms\Admin\Entries\FDBGP_List_Table;
use Formsdb_Elementor_Forms\Admin\Register_Menu_Dashboard\FDBGP_Dashboard;
use Formsdb_Elementor_Forms\Admin\Entries\FDBGP_Post_Bulk_Actions;

if ( ! defined( 'ABSPATH' ) ) {
    die;
}
/**
 * Entries Posts
 */     
class FDBGP_Entries_Posts {

    private static $instance = null;

    public static $post_type = 'cfkef-entries';

    /**
     * Get instance
     * 
     * @return FDBGP_Entries_Posts
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }       

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action('add_meta_boxes', [ $this, 'add_submission_meta_boxes' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ]);
        add_action('fdbgp_render_menu_pages', [ $this, 'output_entries_list' ]);
        add_action( 'admin_head', [$this, 'add_screen_option'] );
        add_filter('fdbgp_dashboard_tabs', [ $this, 'add_dashboard_tab' ]);

        $bulk_actions = new FDBGP_Post_Bulk_Actions();
        $bulk_actions->init();

        remove_action( 'admin_head', 'wp_admin_bar_help_menu' );
    }

    /**
     * Add dashboard tab
     */
    public function add_dashboard_tab($tabs) {
        // $tabs[] = array(
        //     'title' => 'Entries',
        //     'position' => 2,
        //     'slug' => 'cfkef-entries',
        // );

        return $tabs;
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_style('cfkef-entries-posts', FDBGP_PLUGIN_URL . 'admin/assets/css/cfkef-entries-post.css', [], FDBGP_PLUGIN_VERSION);
    }

    /**
     * Add admin menu
     */
    public function register_post_type() {
        
        $labels = array(
            'name'                  => esc_html_x( 'Form Entries', 'Post Type General Name', 'sb-elementor-contact-form-db' ),
            'singular_name'         => esc_html_x( 'Entrie', 'Post Type Singular Name', 'sb-elementor-contact-form-db' ),
            'menu_name'             => esc_html__( 'Entrie', 'sb-elementor-contact-form-db' ),
            'name_admin_bar'        => esc_html__( 'Entrie', 'sb-elementor-contact-form-db' ),
            'archives'              => esc_html__( 'Entrie Archives', 'sb-elementor-contact-form-db' ),
            'attributes'            => esc_html__( 'Entrie Attributes', 'sb-elementor-contact-form-db' ),
            'parent_item_colon'     => esc_html__( 'Parent Item:', 'sb-elementor-contact-form-db' ),
            'all_items'             => esc_html__( 'Entries', 'sb-elementor-contact-form-db' ),
            'add_new_item'          => esc_html__( 'Add New Item', 'sb-elementor-contact-form-db' ),
            'add_new'               => esc_html__( 'Add New', 'sb-elementor-contact-form-db' ),
            'new_item'              => esc_html__( 'New Item', 'sb-elementor-contact-form-db' ),
            'edit_item'             => esc_html__( 'View Entry', 'sb-elementor-contact-form-db' ),
            'update_item'           => esc_html__( 'Update Item', 'sb-elementor-contact-form-db' ),
            'view_item'             => esc_html__( 'View Item', 'sb-elementor-contact-form-db' ),
            'view_items'            => esc_html__( 'View Items', 'sb-elementor-contact-form-db' ),
            'search_items'          => esc_html__( 'Search Item', 'sb-elementor-contact-form-db' ),
            'not_found'             => esc_html__( 'Not found', 'sb-elementor-contact-form-db' ),
            'not_found_in_trash'    => esc_html__( 'Not found in Trash', 'sb-elementor-contact-form-db' ),
            'featured_image'        => esc_html__( 'Featured Image', 'sb-elementor-contact-form-db' ),
            'set_featured_image'    => esc_html__( 'Set featured image', 'sb-elementor-contact-form-db' ),
            'remove_featured_image' => esc_html__( 'Remove featured image', 'sb-elementor-contact-form-db' ),
            'use_featured_image'    => esc_html__( 'Use as featured image', 'sb-elementor-contact-form-db' ),
            'insert_into_item'      => esc_html__( 'Insert into item', 'sb-elementor-contact-form-db' ),
            'uploaded_to_this_item' => esc_html__( 'Uploaded to this item', 'sb-elementor-contact-form-db' ),
            'items_list'            => esc_html__( 'Form entries list', 'sb-elementor-contact-form-db' ),
            'items_list_navigation' => esc_html__( 'Form entries list navigation', 'sb-elementor-contact-form-db' ),
            'filter_items_list'     => esc_html__( 'Filter from entry list', 'sb-elementor-contact-form-db' ),
        );

        $args = array(
            'label'                 => esc_html__( 'Form Entries', 'sb-elementor-contact-form-db' ),
            'description'           => esc_html__( 'cool-formkit-entry', 'sb-elementor-contact-form-db' ),
            'labels'                => $labels,
            'supports'              => false,
            'capabilities'          => ['create_posts' => 'do_not_allow'],
            'map_meta_cap'          => true,
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true, // Hide from dashboard
            'show_in_menu'          => false,
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'publicly_queryable'    => false,
            'rewrite'               => false,
            'query_var'             => true,
            'exclude_from_search'   => true,
            'show_in_rest'          => true,
        );

        register_post_type( self::$post_type, $args );
        
    }

    public static function get_view() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view filter for navigation, no data modification.
        $view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'all';
        return in_array( $view, array( 'all', 'trash' ), true ) ? $view : 'all';
    }

    public function output_entries_list(FDBGP_Dashboard $dashboard) {
        if($dashboard->current_screen(self::$post_type)){
            ?>
            <div id="fdbgp-loader" style="display: none;">
                <div class="fdbgp-loader-overlay"></div>
                <div class="fdbgp-loader-spinner"></div>
            </div>
            <div class='fdbgp-promo'>
                <div class="fdbgp-box fdbgp-left">
                    <div class="wrapper-container">
                        <div class="wrapper-header">
                            <div class="cfkef-save-all">
                                <div class="cfkef-title-desc">
                                    <h2><?php esc_html_e( 'Hello+ Form Entries', 'sb-elementor-contact-form-db' ); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="wrapper-body">
                            <div class="cool-formkit-setting-table-con">
                                <div class="cool-formkit-left-side-setting">
                                    <div id='cfkef-entries-list-wrapper'>
                                        <?php
                                        $list_table = FDBGP_List_Table::get_instance(self::$post_type);
                                        $list_table->prepare_items();
                                        $list_table->views();
                                        ?>
                                        <form method="get" action="<?php echo esc_url( admin_url( 'admin.php?page=cfkef-entries' ) ); ?>">
                                            <input type="hidden" name="page" value="<?php echo esc_attr(self::$post_type); ?>">
                                            <input type="hidden" name="view" value="<?php echo esc_attr(self::get_view()); ?>">
                                            <?php 
                                            $list_table->search_box( esc_html__( 'Search Forms', 'sb-elementor-contact-form-db' ), 'cfkef-entries-search' );
                                            $list_table->display();
                                            ?>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $this->render_review_request(); ?>
                </div>
                <?php $this->render_right_sidebar(); ?>
            </div>
            <?php
        }
    }

    public function render_review_request() {
        ?>
        <div class="cfkef-review-request">
            <div class="cfkef-review-left">
                <h3><?php esc_html_e('Enjoying FormsDB for Elementor Forms?', 'sb-elementor-contact-form-db'); ?></h3>
                <p><?php esc_html_e('Please consider leaving us a review. It helps us a lot!', 'sb-elementor-contact-form-db'); ?></p>
            </div>
            <div class="cfkef-review-right">
                <div class="cfkef-stars">
                ★★★★★
                </div>
                <a href="https://wordpress.org/support/plugin/sb-elementor-contact-form-db/reviews/#new-post" class="button button-primary" target="_blank"><?php esc_html_e('Leave a Review', 'sb-elementor-contact-form-db'); ?></a>
            </div>
        </div>
        <?php
    }
    /**
     * Render right sidebar
     */
    private function render_right_sidebar() {
        ?>
        <div class="fdbgp-card fdbgp-right">
            <div class="fdbgp-card-wrapper">
                <h2 class="fdbgp-card-title">
                    <span class="fdbgp-icon">🎓</span> <?php esc_html_e( 'How to use', 'sb-elementor-contact-form-db' ); ?>
                </h2>
    
                <div class="fdbgp-steps">
                    <div class="fdbgp-step">
                        <div class="fdbgp-step-number">1</div>
                        <div class="fdbgp-step-content">
                            <h3><?php esc_html_e( 'Create Form', 'sb-elementor-contact-form-db' ); ?></h3>
                            <p><?php esc_html_e( 'Create a page with Hello+ Form widget', 'sb-elementor-contact-form-db' ); ?></p>
                        </div>
                    </div>
    
                    <div class="fdbgp-step">
                        <div class="fdbgp-step-number">2</div>
                        <div class="fdbgp-step-content">
                            <h3><?php esc_html_e( 'Enable Action', 'sb-elementor-contact-form-db' ); ?></h3>
                            <p><?php esc_html_e( 'Enable "Collect Submissions" action in form settings.', 'sb-elementor-contact-form-db' ); ?></p>
                        </div>
                    </div>
    
                    <div class="fdbgp-step">
                        <div class="fdbgp-step-number">3</div>
                        <div class="fdbgp-step-content">
                            <h3><?php esc_html_e( 'Submit Form', 'sb-elementor-contact-form-db' ); ?></h3>
                            <p><?php esc_html_e( 'Submit your form from the frontend to save entries.', 'sb-elementor-contact-form-db' ); ?></p>
                        </div>
                    </div>
    
                    <div class="fdbgp-step">
                        <div class="fdbgp-step-number">4</div>
                        <div class="fdbgp-step-content">
                            <h3><?php esc_html_e( 'View Entries', 'sb-elementor-contact-form-db' ); ?></h3>
                            <p><?php esc_html_e( 'All submissions will appear in this list.', 'sb-elementor-contact-form-db' ); ?></p>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="fdbgp-help-box">
                    <h4>NEED HELP & SETUP GUIDANCE?</h4>
                    <div class="button-groups">
                        <a href="https://docs.coolplugins.net/doc/formsdb-video-tutorials/?utm_source=formsdb&utm_medium=inside&utm_campaign=docs&utm_content=setting_page_sidebar" target="_blank" rel="noopener noreferrer" class="button button-primary" style="width: 49%;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#f9f9f9ff" style="vertical-align: middle; margin-right: 4px;"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg> Video Tutorial</a>
                        <a href="https://docs.coolplugins.net/doc/save-hello-plus-form-entries/?utm_source=formsdb&utm_medium=inside&utm_campaign=docs&utm_content=setting_page_sidebar" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="width: 49%;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#000" style="vertical-align: middle; margin-right: 4px;"><path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zM3 6h8v12H3V6zm10 12V6h8v12h-8z"/><path d="M14 8h6v2h-6zM14 11h6v2h-6zM14 14h4v2h-4z"/></svg> Read Docs</a>
                    </div>
                </div>

            </div>

            <?php
            // Check if Cool Formkit plugin is active (only cool-formkit, not extensions)
            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $is_cool_formkit_active = is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' );
            $is_extensions_active = is_plugin_active( 'extensions-for-elementor-form/extensions-for-elementor-form.php' );
            
            if ( ! $is_cool_formkit_active ) :
            ?>
                <div class="fdbgp-card-wrapper cool-formkit-card">
                    <h2 class="fdbgp-card-title">
                        <span class="fdbgp-icon">💎</span><?php esc_html_e('Cool Formkit', 'sb-elementor-contact-form-db'); ?>
                    </h2>
                    <p><?php esc_html_e('Extend Elementor Forms and take them to the next level.', 'sb-elementor-contact-form-db'); ?></p>
                    <ul>
                        <li><span class="fdbgp-icon">✔️</span><?php esc_html_e('Add Conditional Fields to Form.', 'sb-elementor-contact-form-db'); ?></li>
                        <li><span class="fdbgp-icon">✔️</span><?php esc_html_e('Advanced Form Builder for Elementor.', 'sb-elementor-contact-form-db'); ?></li>
                        <li><span class="fdbgp-icon">✔️</span><?php esc_html_e('Spam Blocker & Advanced Actions After Submit.', 'sb-elementor-contact-form-db'); ?></li>
                    </ul>
                    <a href="https://coolformkit.com/?utm_source=formsdb&utm_medium=inside&utm_campaign=upgrade&utm_content=setting_page_sidebar" class="button button-primary" target="_blank" style="width: 100%;text-align: center;padding:10px;"><?php esc_html_e('Get Cool Formkit', 'sb-elementor-contact-form-db'); ?></a>
                </div>
            <?php endif; ?>

            <?php
            // Check if Conditional Fields plugin (free or pro) or extensions or cool-formkit is active
            $cf_plugin_file = 'conditional-fields-for-elementor-form/class-conditional-fields-for-elementor-form.php';
            $cf_pro_plugin_file = 'conditional-fields-for-elementor-form-pro/class-conditional-fields-for-elementor-form-pro.php';
            $extensions_plugin_file = 'extensions-for-elementor-form/extensions-for-elementor-form.php';
            $is_cf_plugin_active = is_plugin_active( $cf_plugin_file ) || is_plugin_active( $cf_pro_plugin_file );
            
            // Check if extensions plugin is installed (even if not active)
            $all_plugins = get_plugins();
            $is_extensions_installed = isset( $all_plugins[ $extensions_plugin_file ] );
            
            // Hide card if any related plugin is active OR if extensions is installed
            if ( !$is_cf_plugin_active && !$is_extensions_active && !$is_cool_formkit_active && !$is_extensions_installed ) :
            ?>
            <div class="fdbgp-card-wrapper">
                <h2 class="fdbgp-card-title">
                    <span class="fdbgp-icon">💡</span><?php esc_html_e('Did you know?', 'sb-elementor-contact-form-db'); ?>
                </h2>
                <p><?php esc_html_e('You can now conditionally hide or show form fields using Conditional Fields for Elementor forms.', 'sb-elementor-contact-form-db'); ?></p>
                <div class="button-groups">
                    <?php
                    // Check if pro plugin exists on site, prioritize pro over free
                    $is_cf_pro_installed = isset($all_plugins[$cf_pro_plugin_file]);
                    $is_cf_free_installed = isset($all_plugins[$cf_plugin_file]);
                    
                    // Use pro plugin if it exists, otherwise use free
                    if ( $is_cf_pro_installed ) {
                        $plugin_file = $cf_pro_plugin_file;
                        $plugin_slug = 'conditional-fields-for-elementor-form-pro';
                        $action = 'activate';
                        $button_text = __('Activate Pro', 'sb-elementor-contact-form-db');
                    } else {
                        $plugin_file = $cf_plugin_file;
                        $plugin_slug = 'conditional-fields-for-elementor-form';
                        $action = $is_cf_free_installed ? 'activate' : 'install';
                        $button_text = $is_cf_free_installed ? __('Activate Now', 'sb-elementor-contact-form-db') : __('Install Now', 'sb-elementor-contact-form-db');
                    }
                    ?>
                    <button class="button button-primary fdbgp-install-active-btn" 
                        style="width: 49%;" 
                        data-action="<?php echo esc_attr($action); ?>" 
                        data-slug="<?php echo esc_attr($plugin_slug); ?>" 
                        data-init="<?php echo esc_attr($plugin_file); ?>">
                        <?php echo esc_html($button_text); ?>
                    </button>
                    <a href="https://docs.coolplugins.net/plugin/conditional-fields-for-elementor-form/?utm_source=formsdb&utm_medium=inside&utm_campaign=upgrade&utm_content=setting_page_sidebar" class="button button-secondary" target="_blank" style="width: 49%;text-align: center;"><?php esc_html_e('Read Docs', 'sb-elementor-contact-form-db'); ?></a>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        <?php
    }

    public function add_screen_option() {
        if(FDBGP_Dashboard::current_screen(self::$post_type)){
            $args = array(
                'label'   => 'Items per page',
                'default' => 20,
                'option'  => 'edit_'.self::$post_type.'_per_page',
            );
            
            add_screen_option( 'per_page', $args );
        }
    }
    

    /**
     * Add submission meta boxes
     */
    public function add_submission_meta_boxes() {
        remove_meta_box('submitdiv', self::$post_type, 'side');
        remove_meta_box('slugdiv', self::$post_type, 'normal');
        
        add_meta_box( 'cfkef-entries-meta-box', 'Entry Details', [ $this, 'render_submission_meta_box' ], self::$post_type, 'normal', 'high' );
        add_meta_box( 'cfkef-form-info-meta-box', 'Form Info', [ $this, 'render_form_info_meta_box' ], self::$post_type, 'side', 'high' );
    }

    /**
     * Render submission meta box
     */
    public function render_submission_meta_box() {
        $form_data = get_post_meta(get_the_ID(), '_cfkef_form_data', true);
        
        $this->render_field_html("cfkef-entries-form-data", $form_data);
    }

    /**
     * Render form info meta box
     */
    public function render_form_info_meta_box() {
        $meta = get_post_meta(get_the_ID(), '_cfkef_form_meta', true);

          // Update the form entry id in post meta
        $submission_number = get_post_meta(get_the_ID(), '_cfkef_form_entry_id', true);
  
        // Update the form name in post meta
        $form_name = get_post_meta(get_the_ID(), '_cfkef_form_name', true);
  
        // Update the element id in post meta
        $element_id = get_post_meta(get_the_ID(), '_cfkef_element_id', true);

        $post_id= isset($meta['page_url']['value']) ? url_to_postid(isset($meta['page_url']['value'])) : '';

        $data=[
            'Form Name' => array('value' => $form_name),
            'Entry No.' => array('value' => $submission_number),
            'Page Url' => array('value' => isset($meta['page_url']['value']) ? $meta['page_url']['value'] : ''),
        ];

        $this->render_field_html("cfkef-form-info", $data);
    }

    private function render_field_html($type, $data) {
        echo '<div id="' . esc_attr($type) . '" class="cfkef-entries-field-wrapper">';
        echo '<table class="cfkef-entries-data-table">';
        echo '<tbody>';
        
        foreach ($data as $key => $value) {
            $label = $value['title'] ?? $key;
            echo '<tr class="cfkef-entries-data-table-key">';
            echo '<td>' . esc_html($label) . '</td>';
            echo '</tr>';
            echo '<tr class="cfkef-entries-data-table-value">';
            echo '<td>' . esc_html($value['value']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
}
