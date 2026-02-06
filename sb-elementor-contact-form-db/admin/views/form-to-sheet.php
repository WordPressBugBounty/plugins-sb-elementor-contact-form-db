<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FDBGP_Form_To_Sheet_Settings {

    /**
     * Render settings UI
     */
    private $per_page = 10;

    /**
     * Render settings UI
     */
    public function __construct() {
        $forms = $this->get_all_forms();
        
        $total_items = count( $forms );
        $current_page = $this->get_current_page();
        $offset = ( $current_page - 1 ) * $this->per_page;
        
        $forms_to_show = array_slice( $forms, $offset, $this->per_page );
        $this->render_page( $forms_to_show, $total_items, $current_page );
    }

    private function get_current_page() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- get the current page, no data modification.
        $paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        return max( 1, $paged );
    }

    private function get_spreadsheet_tab_url( $spreadsheet_id, $sheet_title ) {
        if ( empty( $spreadsheet_id ) || empty( $sheet_title ) ) {
            return '';
        }

        try {
            $api = new \Formsdb_Elementor_Forms\Lib_Helpers\FDBGP_Google_API_Functions();
            $client = $api->getClient();
            
            if(gettype($client) === 'string' || gettype($client) === 'boolean'){
                return '';
            }

            $service = new \Google_Service_Sheets( $client );
            $spreadsheet = $service->spreadsheets->get( $spreadsheet_id );

            foreach ( $spreadsheet->getSheets() as $sheet ) {
                $properties = $sheet->getProperties();

                if ( $properties->getTitle() === $sheet_title ) {
                    $sheet_id = $properties->getSheetId();

                    return sprintf(
                        'https://docs.google.com/spreadsheets/d/%s/edit#gid=%d',
                        $spreadsheet_id,
                        $sheet_id
                    );
                }
            }

        } catch ( \Exception $e ) {
            // Optional: log error for debugging
        }

        return '';
    }

    /**
     * Render page
     *
     * @param array $forms
     * @param int $total_items
     * @param int $current_page
     */
    private function render_page( array $forms, $total_items = 0, $current_page = 1 ) {
        ?>
        <div class='fdbgp-promo'>
            <div class="fdbgp-box fdbgp-left">
                <div class="wrapper-container">
                    <div class="wrapper-header">
                        <div class="cfkef-save-all">
                            <div class="cfkef-title-desc">
                                <h2><?php esc_html_e( 'Connect Elementor Forms to Google Sheets', 'sb-elementor-contact-form-db' ); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="wrapper-body">
                        <?php
                        if ( ! empty( $forms ) || $total_items > 0 ) {
                            $this->render_forms_table( $forms, $total_items, $current_page );
                        } else {
                            $this->render_empty_state();
                        }
                        ?>
                    </div>
                </div>
                <?php $this->render_review_request(); ?>
            </div>
            <?php $this->render_google_sheets_sidebar(); ?>
        </div>
        <?php
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

    private function render_google_sheets_sidebar() {
        ?>
            <div class="fdbgp-card fdbgp-right">
                <div class="fdbgp-card-wrapper">
                    <h2 class="fdbgp-card-title">
                        <span class="fdbgp-icon">🎓</span> How to use
                    </h2>

                    <div class="fdbgp-steps">
                        <div class="fdbgp-step">
                        <div class="fdbgp-step-number">1</div>
                        <div class="fdbgp-step-content">
                            <h3>Configure Google API</h3>
                            <p>Navigate to the Settings tab and authenticate your Google Account.</p>
                            <a href="admin.php?page=formsdb&tab=settings">Go to Settings →</a>
                        </div>
                        </div>
    
                        <div class="fdbgp-step">
                        <div class="fdbgp-step-number">2</div>
                        <div class="fdbgp-step-content">
                            <h3>Edit your Form</h3>
                            <p>Open your page in Elementor and select your form widget.</p>
                        </div>
                        </div>
    
                        <div class="fdbgp-step">
                        <div class="fdbgp-step-number">3</div>
                        <div class="fdbgp-step-content">
                            <h3>Add Action</h3>
                            <p>Under <strong>'Actions After Submit'</strong>, add <strong>Save Submissions in Google Sheet</strong>.</p>
                        </div>
                        </div>
    
                        <div class="fdbgp-step">
                        <div class="fdbgp-step-number">4</div>
                        <div class="fdbgp-step-content">
                            <h3>Map Fields</h3>
                            <p>Select your spreadsheet and map form fields to columns.</p>
                        </div>
                        </div>
                    </div>
    
                    <hr>
                    <div class="fdbgp-help-box">
                        <h4>NEED HELP & SETUP GUIDANCE?</h4>
                        <div class="button-groups">
                            <a href="https://docs.coolplugins.net/doc/formsdb-video-tutorials/?utm_source=formsdb&utm_medium=inside&utm_campaign=docs&utm_content=setting_page_sidebar" target="_blank" rel="noopener noreferrer" class="button button-primary" style="width: 49%;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#f9f9f9ff" style="vertical-align: middle; margin-right: 4px;"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg> Video Tutorial</a>
                            <a href="https://docs.coolplugins.net/doc/sync-form-submissions-google-sheets/?utm_source=formsdb&utm_medium=inside&utm_campaign=docs&utm_content=setting_page_sidebar" target="_blank" rel="noopener noreferrer" class="button button-secondary" style="width: 49%;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="#000" style="vertical-align: middle; margin-right: 4px;"><path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zM3 6h8v12H3V6zm10 12V6h8v12h-8z"/><path d="M14 8h6v2h-6zM14 11h6v2h-6zM14 14h4v2h-4z"/></svg> Read Docs</a>
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


    /**
     * Render table of forms
     *
     * @param array $forms
     * @param int   $total_items
     * @param int   $current_page
     */
    private function render_forms_table( array $forms, $total_items, $current_page ) {
        ?>
        <p><?php esc_html_e( 'See all your Elementor forms and connect them to Google Sheets.
        New form submissions will be saved automatically in your selected sheet.', 'sb-elementor-contact-form-db' ); ?></p>
        <div class="cool-formkit-setting-table-con">
            <div class="cool-formkit-left-side-setting">
                <?php
                echo '<table class="widefat striped">';
                echo '<thead>
                        <tr>
                            <th>' . esc_html__( 'Form Name', 'sb-elementor-contact-form-db' ) . '</th>
                            <th>' . esc_html__( 'Used On Page', 'sb-elementor-contact-form-db' ) . '</th>
                            <th>' . esc_html__( 'Status', 'sb-elementor-contact-form-db' ) . '</th>
                            <th>' . esc_html__( 'Connected Sheet', 'sb-elementor-contact-form-db' ) . '</th>
                            <th>' . esc_html__( 'Manage', 'sb-elementor-contact-form-db' ) . '</th>
                        </tr>
                    </thead><tbody>';

                foreach ( $forms as $form ) {
                    $sheet_status = '<a href="' . esc_url( $form['edit_url'] ) . '" target="_blank" class="button button-secondary">
                            <span>❌</span> ' . esc_html__( 'Connect Sheet', 'sb-elementor-contact-form-db' ) . '
                        </a>';

                    if ( ! empty( $form['spreadsheet_url'] ) ) {
                        $sheet_status = '<a href="' . esc_url( $form['spreadsheet_url'] ) . '" target="_blank" class="button button-secondary">
                            <span>✅</span> ' . esc_html__( 'View Sheet', 'sb-elementor-contact-form-db' ) . '
                        </a>';
                    }

                    echo '<tr>
                            <td>' . esc_html( $form['form_name'] ) . '</td>
                            <td><a href="' . esc_url( $form['frontend_url'] ) . '" target="_blank">' . esc_html( $form['post_title'] ) . '</a></td>
                            <td>' . ( $form['status'] ? '<span style="color:green;">Enabled</span>' : '<span style="color:red;">Disabled</span>') . '</td>
                            <td>' . wp_kses_post($sheet_status) . '</td>
                            <td>
                                <a class="button button-secondary" href="' . esc_url( $form['edit_url'] ) . '" target="_blank">
                                    ' . esc_html__( 'Edit Form', 'sb-elementor-contact-form-db' ) . '
                                </a>
                            </td>
                        </tr>';
                }

                echo '</tbody></table>';
                $this->render_pagination( $total_items, $current_page );
                ?>
            </div>

        </div>
        <?php
    }


    /**
     * Render empty state
     */
    private function render_empty_state() {

        $create_form_url = admin_url( 'admin.php?action=fdbgp_create_elementor_page' );
        ?>
        <div class="cool-formkit-setting-table-con">
            <div class="cool-formkit-left-side-setting">

                <p>
                    <?php esc_html_e(
                        'No Elementor form is using the "Save Submissions in Google Sheet" action.',
                        'sb-elementor-contact-form-db'
                    ); ?>
                </p>

                <p>
                    <a class="button button-primary" href="<?php echo esc_url( $create_form_url ); ?>" target="_blank">
                        <?php esc_html_e( 'Create New Form', 'sb-elementor-contact-form-db' ); ?>
                    </a>
                </p>

                <p class="description">
                    <?php esc_html_e(
                        'Create a new Elementor Form and enable the "Save Submissions in Google Sheet" action under Actions After Submit.',
                        'sb-elementor-contact-form-db'
                    ); ?>
                </p>

            </div>

        </div>
        <?php
    }


    /**
     * Get All Elementor forms
     *
     * @return array
     */
    /**
     * Get All Elementor forms
     *
     * @return array
     */
    private function get_all_forms() {
        
        // Try to get from cache first
        $cached_forms = get_transient( 'fdbgp_forms_sheet_data' );
        if ( false !== $cached_forms ) {
            return $cached_forms;
        }

        $forms = [];

        $posts = get_posts( [
            'post_type'      => 'any',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Necessary to find Elementor posts, results are cached with transients.
            'meta_key'       => '_elementor_data',
        ] );

        
        foreach ( $posts as $post ) {

            $data = get_post_meta( $post->ID, '_elementor_data', true );
            
            if ( empty( $data ) ) {
                continue;
            }

            $elements = json_decode( $data, true );
            if ( ! is_array( $elements ) ) {
                continue;
            }

            $this->walk_elements( $elements, $post, $forms );
        }

        // Cache the result for 24 hours (will be flushed on save)
        set_transient( 'fdbgp_forms_sheet_data', $forms, DAY_IN_SECONDS );

        return $forms;
    }

    /**
     * Recursive Elementor element walker
     *
     * @param array   $elements
     * @param WP_Post $post
     * @param array   $forms
     */
    private function walk_elements( array $elements, $post, array &$forms ) {

        foreach ( $elements as $element ) {
            $is_found = false;
            $spreadsheet_url = '';

            if ( isset( $element['widgetType'] ) && ( 'form' === $element['widgetType'] || 'ehp-form' === $element['widgetType'] ) ) {
                $is_found = true;
                
                // Check if Google Sheet action is enabled
                $submit_actions = [];
                if ( 'form' === $element['widgetType'] && ! empty( $element['settings']['submit_actions'] ) ) {
                    $submit_actions = $element['settings']['submit_actions'];
                } elseif ( 'ehp-form' === $element['widgetType'] && ! empty( $element['settings']['cool_formkit_submit_actions'] ) ) {
                    $submit_actions = $element['settings']['cool_formkit_submit_actions'];
                }

                if ( in_array( 'Save Submissions in Google Sheet', $submit_actions, true ) ) {
                    // Get Spreadsheet ID
                    $spreadsheet_id = '';
                    if ( ! empty( $element['settings']['fdbgp_spreadsheetid'] ) ) {
                        $spreadsheet_id = $element['settings']['fdbgp_spreadsheetid'];
                    }

                    if ( ! empty( $element['settings']['fdbgp_sheet_list'] ) ) {
                        $sheet_title = $element['settings']['fdbgp_sheet_list'];
                    }else{
                        $sheet_title = '';
                    }                    
                    if ( ! empty( $spreadsheet_id ) && 'new' !== $spreadsheet_id ) {
                        $spreadsheet_url = 'https://docs.google.com/spreadsheets/d/' . $spreadsheet_id;
                    }

                    $spreadsheet_url = $this->get_spreadsheet_tab_url(
                        $spreadsheet_id,
                        $sheet_title
                    );
                }
            }

            if ($is_found) {
                $forms[] = [
                    'post_id'         => $post->ID,
                    'post_title'      => get_the_title( $post->ID ),
                    'frontend_url'    => get_permalink( $post->ID ),
                    'form_name'       => $element['settings']['form_name'] ?? esc_html__( 'Unnamed Form', 'sb-elementor-contact-form-db' ),
                    'edit_url'        => admin_url( 'post.php?post=' . $post->ID . '&action=elementor' ),
                    'widget_type'     => strtoupper($element['widgetType']),
                    'spreadsheet_url' => $spreadsheet_url,
                    'status' => in_array( 'Save Submissions in Google Sheet', $submit_actions, true ),
                ];
            }

            if ( ! empty( $element['elements'] ) ) {
                $this->walk_elements( $element['elements'], $post, $forms );
            }
        }
    }

    /**
     * Render pagination
     *
     * @param int $total_items
     * @param int $current_page
     */
    private function render_pagination( $total_items, $current_page ) {
        $total_pages = ceil( $total_items / $this->per_page );

        if ( $total_pages > 1 ) {
            $pagination_args = [
                'base'      => add_query_arg( 'paged', '%#%' ),
                'format'    => '',
                'current'   => $current_page,
                'total'     => $total_pages,
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
            ];

            echo '<div class="formsdb-tablenav bottom"><div class="tablenav-pages" style="margin: 1em 0;">';
            echo wp_kses_post(paginate_links( $pagination_args ));
            echo '</div></div>';
        }
    }
}

new FDBGP_Form_To_Sheet_Settings();