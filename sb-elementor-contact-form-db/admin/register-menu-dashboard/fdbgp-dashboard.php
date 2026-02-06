<?php
namespace Formsdb_Elementor_Forms\Admin\Register_Menu_Dashboard;

if (!defined('ABSPATH')) {
    die;
}

class FDBGP_Dashboard {

    private $parent_slug = 'elementor';
    private $capability = 'manage_options';
    private $version;
    private $plugin_name;
    private static $allowed_pages = array(
        'formsdb',
        'cfkef-entries'
    );
    private static $instance = null;
    public static function get_instance($plugin_name, $version)
    {
        if (null === self::$instance) {
            self::$instance = new self($plugin_name, $version);
        }
        return self::$instance;
    }

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $dashboard_pages = array();

        if ($this->should_show_entries()) {
            $dashboard_pages['cfkef-entries'] = array(
                'title' => '↳ Entries',
                'position' => 46,
                'slug' => 'cfkef-entries', // Retained the original slug with post-new.php?post_type=
            );
        }

        $dashboard_pages = apply_filters('fdbgp_dashboard_pages', $dashboard_pages);

        foreach (self::$allowed_pages as $page) {
            if (isset($dashboard_pages[$page]['slug']) && isset($dashboard_pages[$page]['title']) && isset($dashboard_pages[$page]['position'])) {
                $this->add_menu_page($dashboard_pages[$page]['slug'], $dashboard_pages[$page]['title'], isset($dashboard_pages[$page]['callback']) ? $dashboard_pages[$page]['callback'] : [$this, 'render_page'], $dashboard_pages[$page]['position']);
            }
        }

        add_action('elementor/admin-top-bar/is-active', [$this, 'hide_elementor_top_bar']);
        add_action('admin_print_scripts', [$this, 'hide_unrelated_notices']);
    }

    private function should_show_entries() {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        return is_plugin_active( 'hello-plus/hello-plus.php' ) &&
               ! is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' ) &&
               ! is_plugin_active( 'extensions-for-elementor-form/extensions-for-elementor-form.php' );
    }

    public function add_menu_page($slug, $title, $callback, $position = 99)
    {
        add_action('admin_menu', function () use ($slug, $title, $callback, $position) {
            add_submenu_page(
                $this->parent_slug,
                str_replace('↳ ', '', $title),
                esc_html($title),
                $this->capability,
                $slug,
                $callback,
                $position
            );
        }, 999);
    }


    public function hide_elementor_top_bar($is_active)
    {
        foreach (self::$allowed_pages as $page) {
            if (self::current_screen($page)) {
                return false;
            }
        }

        return $is_active;
    }

    public function hide_unrelated_notices()
    { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded, Generic.Metrics.NestingLevel.MaxExceeded
        
        $fdbgp_pages = false;
        foreach (self::$allowed_pages as $page) {

            if (self::current_screen($page)) {
                $fdbgp_pages = true;
                break;
            }
        }


        if ($fdbgp_pages) {
            global $wp_filter;

            // Define rules to remove callbacks.
            $rules = [
                'user_admin_notices' => [], // remove all callbacks.
                'admin_notices'      => [],
                'all_admin_notices'  => [],
                'admin_footer'       => [
                    'render_delayed_admin_notices', // remove this particular callback.
                ],
            ];

            $notice_types = array_keys($rules);

            foreach ($notice_types as $notice_type) {
                if (empty($wp_filter[$notice_type]->callbacks) || ! is_array($wp_filter[$notice_type]->callbacks)) {
                    continue;
                }

                $remove_all_filters = empty($rules[$notice_type]);

                foreach ($wp_filter[$notice_type]->callbacks as $priority => $hooks) {
                    foreach ($hooks as $name => $arr) {
                        if (is_object($arr['function']) && is_callable($arr['function'])) {
                            if ($remove_all_filters) {
                                unset($wp_filter[$notice_type]->callbacks[$priority][$name]);
                            }
                            continue;
                        }

                        $class = ! empty($arr['function'][0]) && is_object($arr['function'][0]) ? strtolower(get_class($arr['function'][0])) : '';


                        // Remove all callbacks except WPForms notices.
                        if ($remove_all_filters && strpos($class, 'wpforms') === false) {
                            unset($wp_filter[$notice_type]->callbacks[$priority][$name]);
                            continue;
                        }

                        $cb = is_array($arr['function']) ? $arr['function'][1] : $arr['function'];
                        

                        // Remove a specific callback.
                        if (! $remove_all_filters) {
                            if (in_array($cb, $rules[$notice_type], true)) {
                                unset($wp_filter[$notice_type]->callbacks[$priority][$name]);
                            }
                            continue;
                        }
                    }
                }
            }
        }

        add_action( 'admin_notices', [ $this, 'display_admin_notices' ], PHP_INT_MAX );
    }

    public function display_admin_notices() {
        do_action('fdbgp_admin_notices');
    }

    public static function current_screen($slug)
    {
        $slug = sanitize_text_field($slug);
        return self::fdbgp_current_page($slug);
    }

    private static function fdbgp_current_page($slug)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only for current page detection, no data modification.
        $current_page = isset($_REQUEST['page']) ? sanitize_key($_REQUEST['page']) : (isset($_REQUEST['post_type']) ? sanitize_key($_REQUEST['post_type']) : '');
        $status=false;

        if (in_array($current_page, self::get_allowed_pages()) && $current_page === $slug) {
            $status=true;
        }

        if(function_exists('get_current_screen') && in_array($slug, self::get_allowed_pages())){
            $screen = get_current_screen();

            if($screen && property_exists($screen, 'id') && $screen->id && $screen->id === $slug){
                $status=true;
            }
        }

        return $status;
    }

    public static function get_allowed_pages()
    {
        $allowed_pages = self::$allowed_pages;

        $allowed_pages = apply_filters('fdbgp_dashboard_allowed_pages', $allowed_pages);

        return $allowed_pages;
    }

    public function render_page()
    {
        echo '<div class="fdbgp-wrapper">';
        ?>
        <div class="fdbgp-header">
                <div class="fdbgp-header-logo">
                    <a href="?page=formsdb">
                        <img src="<?php echo esc_url(FDBGP_PLUGIN_URL . 'assets/images/formsDB-logo.svg'); ?>" alt="Cool FormKit Logo">
                    </a>
                </div>

                <div class="fdbgp-header-buttons">

                    <?php
                        if (! is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' )) :
                    ?>

                    <span>Unlock advanced fields and features for Elementor Forms.</span>
                    <a href="https://coolformkit.com/features/?utm_source=formsdb&utm_medium=inside&utm_campaign=demo&utm_content=setting_page_header" class="button button-primary fdbgp-try-cool-form" target="_blank"><?php 
                        esc_html_e('Try Cool FormKit for Elementor', 'sb-elementor-contact-form-db');
                     ?></a>

                    <?php else: ?>

                    <span>Use advanced fields and features for Elementor Forms.</span>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=cool-formkit' ))?>" class="button button-primary fdbgp-try-cool-form" target="_blank"><?php 
                        esc_html_e('use Cool FormKit for Elementor', 'sb-elementor-contact-form-db');
                     ?></a>

                    <?php endif; ?>

                
                    
                </div>

            </div>
        <?php

        $this->render_tabs();

        echo '<div class="tab-content">';

        do_action('fdbgp_render_menu_pages', $this);

        echo '</div></div>';
    }


    public function render_tabs(){
        $tabs = $this->fdbgp_get_tabs();
        
        echo '<h2 class="nav-tab-wrapper fdbgp-dashboard-tabs">';
        foreach ($tabs as $tab) {
            $active_class = self::current_screen($tab['slug']) ? ' nav-tab-active' : '';
            echo '<a href="' . esc_url(admin_url('admin.php?page=' . $tab['slug'])) . '" class="nav-tab ' . esc_attr($active_class) . '">' . esc_html($tab['title']) . '</a>';
        }
        echo '</h2>';
    }

    public function fdbgp_get_tabs(){
        $has_old_submissions = \FDBGP_Old_Submission::has_old_submissions();

        $default_tabs = array(
            array(
                'title' => 'Forms To Sheet',
                'position' => 1,
                'slug' => 'formsdb',
            ),
            array(
                'title' => 'Forms To Post Type',
                'position' => 2,
                'slug' => 'formsdb&tab=post-type',
            ),
            array(
                'title' => 'Settings',
                'position' => 4,
                'slug' => 'formsdb&tab=settings',
            ),
            array(
                'title' => 'Advanced Fields',
                'position' => 5,
                'slug' => 'formsdb&tab=advanced',
            ),
        );

        if ($this->should_show_entries()) {
            $default_tabs[] = array(
                'title' => 'Hello+ Form Entries',
                'position' => 3,
                'slug' => 'cfkef-entries',
            );
        }

        if($has_old_submissions){
            $default_tabs[] = array(
                'title' => 'Old Submissions',
                'position' => 6,
                'slug' => 'formsdb&tab=old-submission',
            );
        }

        $tabs = apply_filters('fdbgp_dashboard_tabs', $default_tabs);
        // Set the index of tabs based on their position
        usort($tabs, function($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        return $tabs;
    }
}