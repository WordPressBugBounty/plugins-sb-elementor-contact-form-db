<?php
/**
 * Old Submission Tab View
 * 
 * Displays legacy form submissions from the old plugin version.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include the helper class if not already loaded
if (!class_exists('FDBGP_Old_Submission')) {
    require_once FDBGP_PLUGIN_DIR . 'includes/class-fdbgp-old-submission.php';
}

class FDBGP_Old_Submission_View {

    private $per_page = 10;
    private $helper;

    public function __construct() {
        $this->helper = FDBGP_Old_Submission::get_instance();
        $this->render_page();
    }

    private function render_page() {
        $forms = $this->helper->get_submitted_pages();
        $form_ids = $this->helper->get_form_ids();
        $total_count = $this->helper->get_submission_count();
        ?>
        <div class='fdbgp-promo'>
            <div class="fdbgp-box fdbgp-left">
                <div class="wrapper-container">
                    <div class="wrapper-header">
                        <div class="cfkef-save-all">
                            <div class="cfkef-title-desc">
                                <h2><?php esc_html_e('Old Form Submissions', 'sb-elementor-contact-form-db'); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="wrapper-body">
                        <?php $this->render_content($forms, $form_ids, $total_count); ?>
                    </div>
                </div>
                
            </div>
            <?php $this->render_sidebar(); ?>
        </div>
        <?php
    }

    private function render_content($forms, $form_ids, $total_count) {
        $legacy_enabled = $this->helper->is_legacy_save_enabled();
        
        // Handle pagination and status
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended	
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended	
        $status = isset($_GET['status']) && $_GET['status'] === 'trash' ? 'trash' : 'publish';
        
        $submissions_query = $this->helper->get_all_submissions($this->per_page, $page, $status);
        $count_publish = $this->helper->get_submission_count('publish');
        $count_trash = $this->helper->get_submission_count('trash');
        ?>
        <div class="cool-formkit-setting-table-con">
            <div class="cool-formkit-left-side-setting">
                
                <!-- Legacy Saving Toggle -->
                <div class="fdbgp-legacy-toggle-section" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <form method="POST" action="">
                        <?php wp_nonce_field('fdbgp_legacy_action', 'fdbgp_legacy_nonce'); ?>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <h3 style="margin: 0 0 10px 0;"><?php esc_html_e('Continue Using Old Submission Method', 'sb-elementor-contact-form-db'); ?></h3>
                                <p style="margin: 0;">
                                    <?php esc_html_e('When enabled, new form submissions will continue to be saved using the old automatic method (used in plugin version 1.8.1 and earlier).', 'sb-elementor-contact-form-db'); ?>
                                </p>
                            </div>
                            <div style="flex-shrink: 0; margin-left: 20px;">
                                <label class="fdbgp-switch">
                                    <input type="checkbox" name="enable_legacy_save" value="1" <?php checked($legacy_enabled, true); ?> onchange="this.form.submit()">
                                    <span class="slider round"></span>
                                    <span style="margin-left: 5px; font-weight: 600;"><?php echo $legacy_enabled ? esc_html__('Enabled', 'sb-elementor-contact-form-db') : esc_html__('Disabled', 'sb-elementor-contact-form-db'); ?></span>
                                </label>
                                <input type="hidden" name="fdbgp_toggle_legacy_save" value="1">
                            </div>
                        </div>
                        <?php if ($legacy_enabled): ?>
                            <div class="notice notice-info inline" style="margin: 10px 0 0 0;">
                                <p><?php esc_html_e('If you disable this option, new submissions will not be saved using the old system. To save new submissions, you must edit your form in Elementor and select Save Submission under Actions After Submit.', 'sb-elementor-contact-form-db'); ?></p>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Submissions Table -->
                <h3 style="margin-bottom: 5px;"><?php esc_html_e('Your Old (Legacy) Form Submissions', 'sb-elementor-contact-form-db'); ?></h3>
                <p style="margin-bottom: 5px;"><?php esc_html_e('Here you can view all form submissions that were saved using the legacy method supported in plugin version 1.8.1 or earlier.', 'sb-elementor-contact-form-db'); ?></p>
                
                <ul class="subsubsub">
                    <li class="all"><a href="<?php echo esc_url(remove_query_arg(array('status', 'paged'))); ?>" class="<?php echo $status === 'publish' ? 'current' : ''; ?>"><?php esc_html_e('All', 'sb-elementor-contact-form-db'); ?> <span class="count">(<?php echo intval($count_publish); ?>)</span></a> |</li>
                    <li class="trash"><a href="<?php echo esc_url(add_query_arg('status', 'trash', remove_query_arg('paged'))); ?>" class="<?php echo $status === 'trash' ? 'current' : ''; ?>"><?php esc_html_e('Trash', 'sb-elementor-contact-form-db'); ?> <span class="count">(<?php echo intval($count_trash); ?>)</span></a></li>
                </ul>
                
                <?php if ($submissions_query->have_posts()): ?>
                    <form method="get">
                        <input type="hidden" name="page" value="old-submissions-page" /> <!-- Adjust if needed based on actual admin page slug, looks like it might be dynamic -->
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Date', 'sb-elementor-contact-form-db'); ?></th>
                                    <th><?php esc_html_e('Form Name', 'sb-elementor-contact-form-db'); ?></th>
                                    <th><?php esc_html_e('Details', 'sb-elementor-contact-form-db'); ?></th>
                                    <th><?php esc_html_e('Actions', 'sb-elementor-contact-form-db'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($submissions_query->have_posts()): $submissions_query->the_post(); 
                                    $post_id = get_the_ID();
                                    $meta = $this->helper->get_submission_meta($post_id);
                                    $form_id = get_post_meta($post_id, 'sb_elem_cfd_form_id', true);
                                    $display_name = get_the_title();
                                    
                                    // Attempt to find email in data
                                    $email = '-';
                                    if (isset($meta['data'])) {
                                        foreach ($meta['data'] as $field) {
                                            if (is_email($field['value'])) {
                                                $email = $field['value'];
                                                break;
                                            }
                                        }
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo get_the_date('Y-m-d H:i:s'); ?></td>
                                        <td>
                                            <strong><?php echo esc_html($display_name); ?></strong><br>
                                            <small><?php 
                                                printf(
                                                    /* translators: %s: Contact form ID */
                                                    esc_html__('Form ID: %s', 'sb-elementor-contact-form-db'), esc_html($form_id)
                                                );
                                             ?></small>
                                        </td>
                                        <td>
                                            <?php if ($email !== '-'): ?>
                                                <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($status === 'publish'): ?>
                                                <button type="button" class="button button-secondary fdbgp-view-submission" 
                                                    data-id="<?php echo esc_attr($post_id); ?>" 
                                                    data-data="<?php echo esc_attr(json_encode($meta['data'])); ?>"
                                                    data-extra="<?php echo esc_attr(json_encode(isset($meta['extra']) ? $meta['extra'] : array())); ?>"
                                                    data-date="<?php echo esc_attr(get_the_date('Y-m-d H:i:s')); ?>"
                                                    data-permalink="<?php echo esc_attr(get_permalink($meta['extra']['submitted_on_id'])); ?>"
                                                >
                                                    <?php esc_html_e('View', 'sb-elementor-contact-form-db'); ?>
                                                </button>
                                                <?php 
                                                $trash_url = wp_nonce_url(
                                                    add_query_arg(array('action' => 'fdbgp_trash_submission', 'post_id' => $post_id)), 
                                                    'fdbgp_trash_submission_' . $post_id
                                                ); 
                                                ?>
                                                <a href="<?php echo esc_url($trash_url); ?>" class="button button-link-delete">
                                                    <?php esc_html_e('Trash', 'sb-elementor-contact-form-db'); ?>
                                                </a>
                                            <?php else: ?>
                                                <?php 
                                                $restore_url = wp_nonce_url(
                                                    add_query_arg(array('action' => 'fdbgp_restore_submission', 'post_id' => $post_id)), 
                                                    'fdbgp_restore_submission_' . $post_id
                                                ); 
                                                $delete_url = wp_nonce_url(
                                                    add_query_arg(array('action' => 'fdbgp_delete_submission', 'post_id' => $post_id)), 
                                                    'fdbgp_delete_submission_' . $post_id
                                                ); 
                                                ?>
                                                <a style="margin-top: 5px;" href="<?php echo esc_url($restore_url); ?>" class="button button-secondary">
                                                    <?php esc_html_e('Restore', 'sb-elementor-contact-form-db'); ?>
                                                </a>
                                                <a style="margin-top: 5px;" href="<?php echo esc_url($delete_url); ?>" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e('Are you sure you want to permanently delete this submission?', 'sb-elementor-contact-form-db'); ?>');">
                                                    <?php esc_html_e('Delete', 'sb-elementor-contact-form-db'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </tbody>
                        </table>
                    </form>

                    <!-- Pagination -->
                    <?php if ($submissions_query->max_num_pages > 1): ?>
                        <div class="formsdb-tablenav bottom">
                            <div class="tablenav-pages">
                                <?php 
                                echo wp_kses_post(paginate_links(array(
                                    'base' => add_query_arg('paged', '%#%'),
                                    'format' => '',
                                    'prev_text' => '&laquo;',
                                    'next_text' => '&raquo;',
                                    'total' => $submissions_query->max_num_pages,
                                    'current' => $page
                                ))); 
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php elseif($page > 1): ?>
                    <p style="clear: left;"><?php esc_html_e('Please switch to another tab.', 'sb-elementor-contact-form-db'); ?></p>
                    <div class="formsdb-tablenav bottom">
                        <div class="tablenav-pages">
                            <?php 
                            $submissions_query = $this->helper->get_all_submissions(10, 1);
                            if(!empty($submissions_query)) {
                                echo wp_kses_post(paginate_links(array(
                                    'base' => add_query_arg('paged', '%#%'),
                                    'format' => '',
                                    'prev_text' => '&laquo;',
                                    'next_text' => '&raquo;',
                                    'total' => $submissions_query->max_num_pages,
                                    'current' => $page
                                ))); 
                            }
                            ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p style="clear: left;"><?php esc_html_e('No legacy submissions found.', 'sb-elementor-contact-form-db'); ?></p>
                <?php endif; ?>

                <hr style="margin: 30px 0;">

                
                <h3><?php esc_html_e('Use the options below to export your old submissions for backup or migration.', 'sb-elementor-contact-form-db'); ?></h3>

                <p>
                    <?php 
                    printf(
                        /* translators: %d: number of old form submissions */
                        esc_html__('You have %d old form submission(s) stored in the database.', 'sb-elementor-contact-form-db'),
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                        $total_count
                    ); 
                    ?>
                </p>

                <?php if ($total_count > 0): ?>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
                        <!-- Export by Page Submitted -->
                        <div style="flex: 1; min-width: 300px;">
                            <form method="POST">
                                <p><strong><?php esc_html_e('Export by Page Submitted', 'sb-elementor-contact-form-db'); ?></strong></p>
                                <select style="margin-right: 10px; width: 200px;" name="form_name">
                                    <?php 
                                    ksort($forms);
                                    foreach ($forms as $form => $label): 
                                        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                                        $selected = isset($_REQUEST['form_name']) && sanitize_text_field(wp_unslash($_REQUEST['form_name'])) == $form ? 'selected="selected"' : '';
                                    ?>
                                        <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        <option <?php echo $selected; ?> value="<?php echo esc_attr($form); ?>">
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="submit" name="preview_page" class="button-secondary" value="<?php esc_attr_e('Preview', 'sb-elementor-contact-form-db'); ?>" />
                                <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <input name="fdbgp_old_export_nonce" type="hidden" value="<?php echo wp_create_nonce('fdbgp_old_export'); ?>" />
                            </form>
                        </div>

                        <!-- Export by Form ID -->
                        <?php if (!empty($form_ids)): ?>
                        <div style="flex: 1; min-width: 300px;">
                            <form method="POST">
                                <p><strong><?php esc_html_e('Export by Form ID', 'sb-elementor-contact-form-db'); ?></strong></p>
                                <select style="margin-right: 10px; width: 200px;" name="form_id">
                                    <?php 
                                    ksort($form_ids);
                                    foreach ($form_ids as $form): 
                                        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                                        $selected = isset($_REQUEST['form_id']) && $_REQUEST['form_id'] == $form ? 'selected="selected"' : '';
                                    ?>
                                    <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        <option <?php echo $selected; ?> value="<?php echo esc_attr($form); ?>">
                                            <?php echo esc_html($form); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="submit" name="preview_form_id" class="button-secondary" value="<?php esc_attr_e('Preview', 'sb-elementor-contact-form-db'); ?>" />
                                <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <input name="fdbgp_old_export_nonce" type="hidden" value="<?php echo wp_create_nonce('fdbgp_old_export'); ?>" />
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php $this->render_preview(); ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- View Submission Modal -->
        <div id="fdbgp-view-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100000; justify-content: center; align-items: center;">
            <div style="background: #fff; width: 600px; max-width: 90%; max-height: 90%; overflow: auto; padding: 20px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                    <h2 style="margin: 0;"><?php esc_html_e('Submission Details', 'sb-elementor-contact-form-db'); ?></h2>
                    <button type="button" class="notice-dismiss" onclick="document.getElementById('fdbgp-view-modal').style.display='none'" style="position: relative; top: 0; right: 0;"></button>
                </div>
                <div id="fdbgp-modal-content"></div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="button" onclick="document.getElementById('fdbgp-view-modal').style.display='none'"><?php esc_html_e('Close', 'sb-elementor-contact-form-db'); ?></button>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('.fdbgp-view-submission').on('click', function() {
                    var data = $(this).data('data');
                    var extra = $(this).data('extra');
                    var date = $(this).data('date');
                    var permalink = $(this).data('permalink');

                    var html = '<table class="widefat striped"><thead><tr><th>Field</th><th>Value</th></tr></thead><tbody>';
                    
                    // Add Meta Info
                    if (date) {
                         html += '<tr><td><strong><?php esc_html_e('Date of Submission', 'sb-elementor-contact-form-db'); ?></strong></td><td>' + date + '</td></tr>';
                    }
                    
                    if (typeof extra === 'object') {
                        if (extra.submitted_on) {
                             html += '<tr><td><strong><?php esc_html_e('Submitted On', 'sb-elementor-contact-form-db'); ?></strong></td><td><a target="_blank" href="' + permalink + '">' + extra.submitted_on + '</a></td></tr>';
                        }
                        if (extra.submitted_by) {
                             html += '<tr><td><strong><?php esc_html_e('Submitted By', 'sb-elementor-contact-form-db'); ?></strong></td><td>' + extra.submitted_by + '</td></tr>';
                        }
                    }

                    // Separator
                    html += '<tr><td colspan="2" style="background-color: #f0f0f1;"><strong><?php esc_html_e('Form Data', 'sb-elementor-contact-form-db'); ?></strong></td></tr>';

                    if (typeof data === 'object') {
                        $.each(data, function(key, field) {
                             html += '<tr><td><strong>' + field.label + '</strong></td><td>' + field.value + '</td></tr>';
                        });
                    }
                    
                    html += '</tbody></table>';
                    $('#fdbgp-modal-content').html(html);
                    $('#fdbgp-view-modal').css('display', 'flex');
                });
            });
        </script>
        <?php
    }

    private function render_preview() {
        if (empty($_POST['fdbgp_old_export_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fdbgp_old_export_nonce'])), 'fdbgp_old_export')) {
            return; // Invalid or missing nonce - silently exit
        }
        
        if (isset($_REQUEST['preview_page']) && isset($_REQUEST['form_name'])) {
            $form_name = sanitize_text_field(wp_unslash($_REQUEST['form_name']));
            $rows = $this->helper->get_export_rows_by_page($form_name, 50);
            $this->render_preview_table($rows, 'form_name', $form_name, __('CSV Content (by Submitted Page)', 'sb-elementor-contact-form-db'));
        } elseif (isset($_REQUEST['preview_form_id']) && isset($_REQUEST['form_id'])) {
            $form_id = sanitize_text_field(wp_unslash($_REQUEST['form_id']));
            $rows = $this->helper->get_export_rows_by_form_id($form_id, 50);
            $this->render_preview_table($rows, 'form_id', $form_id, __('CSV Content (by Form ID)', 'sb-elementor-contact-form-db'));
        }
    }

    private function render_preview_table($rows, $field_name, $field_value, $title) {
        if (empty($rows)) {
            return;
        }
        ?>
        <div style="margin-top: 30px;">
            <h3><?php echo esc_html($title); ?></h3>
            <p><?php esc_html_e('Please review the data below and press "Download CSV File" to start the download. This preview shows up to 50 submissions. The export will include all submissions.', 'sb-elementor-contact-form-db'); ?></p>
            
            <div style="margin-top: 20px; min-height: 150px; max-height: 350px; overflow: auto; margin-bottom: 10px; border: 1px solid #ddd; padding: 20px; background: #f9f9f9; font-family: monospace; font-size: 12px;">
                <?php 
                foreach ($rows as $row) {
                    echo esc_html($row) . '<br />';
                }
                ?>
            </div>

            <form method="POST">
                <input type="hidden" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($field_value); ?>" />
                <input type="submit" name="download_old_csv" class="button-primary" value="<?php esc_attr_e('Download CSV File', 'sb-elementor-contact-form-db'); ?>" />
                <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <input name="fdbgp_old_export_nonce" type="hidden" value="<?php echo wp_create_nonce('fdbgp_old_export'); ?>" />
            </form>
        </div>
        <?php
    }

    private function render_sidebar() {
        ?>
        <div class="fdbgp-card fdbgp-right">
            <div class="fdbgp-card-wrapper">
                <h2 class="fdbgp-card-title">
                    <span class="fdbgp-icon">📁</span> <?php esc_html_e('About Old Submissions', 'sb-elementor-contact-form-db'); ?>
                </h2>
    
                <div class="fdbgp-steps">
                    <div class="fdbgp-step">
                        <div class="fdbgp-step-number">ℹ️</div>
                        <div class="fdbgp-step-content">
                            <h3><?php esc_html_e('Legacy Submissions', 'sb-elementor-contact-form-db'); ?></h3>
                            <p><?php esc_html_e('These are form submissions saved automatically by plugin version 1.8.1 or earlier.', 'sb-elementor-contact-form-db'); ?></p>
                        </div>
                    </div>
    
                    <div class="fdbgp-step">
                        <div class="fdbgp-step-number">ℹ️</div>
                        <div class="fdbgp-step-content">
                            <h3><?php esc_html_e('Continue Using Old Method', 'sb-elementor-contact-form-db'); ?></h3>
                            <p><?php esc_html_e('If you don\'t want to edit many existing forms, you can keep using the old automatic saving method by keeping it enabled here.', 'sb-elementor-contact-form-db'); ?></p>
                        </div>
                    </div>
    
                    <div class="fdbgp-step">
                        <div class="fdbgp-step-number">ℹ️</div>
                        <div class="fdbgp-step-content">
                            <h3><?php esc_html_e('Enable New Submissions (Recommended)', 'sb-elementor-contact-form-db'); ?></h3>
                            <p><?php esc_html_e('If you prefer the new method, disable legacy saving from this page. Then edit your form in Elementor and enable the Save Submission action.
                            
                            New entries will appear in <a href="admin.php?page=e-form-submissions" target="_blank">Elementor → Submissions</a>.', 'sb-elementor-contact-form-db'); ?></p>
                        </div>
                    </div>
    
                    <div class="fdbgp-step">
                        <div class="fdbgp-step-number">ℹ️</div>
                        <div class="fdbgp-step-content">
                            <h3><?php esc_html_e('Export Your Old Data', 'sb-elementor-contact-form-db'); ?></h3>
                            <p><?php esc_html_e('You can export all legacy submissions to CSV format for backup or future use.', 'sb-elementor-contact-form-db'); ?></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <?php
    }
}

new FDBGP_Old_Submission_View();
