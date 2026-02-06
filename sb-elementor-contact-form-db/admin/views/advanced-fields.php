<?php
// Ensure the file is being accessed through the WordPress admin area
if (!defined('ABSPATH')) {
    die;
}

require_once 'settings.php';
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound	
$settings_page = new FDBGP_Settings_Page();

if (! function_exists('get_plugins')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$enabled_elements = get_option('cfkef_enabled_elements', array());


// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$popular_elements = array('range_slider');
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$updated_elements = array('country_code');

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$first_plugin = 'formsdb';


// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$form_elements = array(


    'whatsapp_redirect' => array(
        'label' => __('Whatsapp Redirect', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/whatsapp-redirect-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/whatsapp-redirection-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/whatsapp-icon-min.svg',
        'pro' => true
    ),

    'range_slider' => array(
        'label' => __('Range Slider', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/range-slider-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/range-slider-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/range-slider-min.svg',
        'pro' => true
    ),
    'calculator_field' => array(
        'label' => __('Calculator Field', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/calculator-for-elementor/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/calculator-field-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/calculator-field-min.svg',
        'pro' => true
    ),
    'rating_field' => array(
        'label' => __('Rating Field', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/rating-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/rating-field-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/rating-field-min.svg',
        'pro' => true
    ),
    'signature_field' => array(
        'label' => __('Signature Field', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/signature-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/signature-field-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/signature.svg',
        'pro' => true
    ),
    'image_radio' => array(
        'label' => __('Image Radio', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/image-radio-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/add-image-radio-field/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/image-radio-min.svg',
        'pro' => true
    ),
    'radio_checkbox_styler' => array(
        'label' => __('Radio & Checkbox Styler', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/checkbox-radio-styles-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/style-radio-checkbox-elementor-form/?utm_source=cfkef_plugin&&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/radio-styler-min.svg',
        'pro' => true
    ),
    'label_styler' => array(
        'label' => __('Label Styler', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/label-styler-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/label-styler-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/label-style-min.svg',
        'pro' => true
    ),
    'select2' => array(
        'label' => __('Select2', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/select2-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/select-field-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/select2-field-min.svg',
        'pro' => true
    ),
    'WYSIWYG' => array(
        'label' => __('WYSIWYG', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/wysiwyg-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/add-wysiwyg-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/WYSIWYG-min.svg',
        'pro' => true
    ),
    'confirm_dialog' => array(
        'label' => __('Confirm Dialog Box', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/confirm-dialog-box-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/elementor-form-confirm-dialog-popup/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/dialog-box-min.svg',
        'pro' => true
    ),
    'restrict_date' => array(
        'label' => __('Restrict Date', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/restrict-date-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/restrict-date-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/restrict-date-min.svg',
        'pro' => true
    ),
    'currency_field' => array(
        'label' => __('Currency Field', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/currency-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/add-currency-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/currency-field-min.svg',
        'pro' => true
    ),
    'month_week_field' => array(
        'label' => __('Month/Week Field', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/month-week-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/add-month-week/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/month-week-field-min.svg',
        'pro' => true
    ),
    'cloudflare_recaptcha' => array(
        'label' => __('Cloudflare Turnstile', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/cloudflare-turnstile-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/add-cloudflare-turnstile-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/cloudflare-icon-min.svg',
        'pro' => true
    ),

    'h_recaptcha' => array(
        'label' => __('hCAPTCHA', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/hcaptcha-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/add-hcaptcha-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/hcaptcha-icon-min.svg',
        'pro' => true
    ),
    'toggle_field' => array(

        'label' => __('Toggle Field', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/toggle-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/toggle-field-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/toggle-field.svg',
        'pro' => true,
    ),


    'conditional_mailchimp' => array(
        'label' => __('Conditional MailChimp', 'sb-elementor-contact-form-db'),
        'demo' => str_replace('utm_source=', 'utm_source=' . esc_attr($first_plugin),'https://docs.coolplugins.net/doc/conditional-mailchimp-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . esc_attr($first_plugin),'https://coolformkit.com/features/conditional-mailchimp-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/mailchimp-logo.svg',
        'pro' => true,
        'pro_link' => 'https://coolplugins.net/cool-formkit-for-elementor-forms/?utm_source=formsdb&utm_medium=inside&utm_campaign=get-pro&utm_content=plugins-dashboard/'
    ),
);

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$condition_plugin_features = array(
    'conditional_logic' => array(
        'label' => __('Conditional Logic', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/elementor-form-conditional-fields/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/conditional-logic-1-min.svg'
    ),

    'submit_condition' => array(
        'label' => __('Submit Conditions', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/elementor-form-submit-button-conditions/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/conditional-button-min.svg',
        'pro' => true
    ),


    'redirect_conditionaly' => array(
        'label' => __('Redirect Conditionaly', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/conditional-redirect-elementor-form-on-submit/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/redirect-conditionally-min.svg',
        'pro' => true
    ),


    'email_conditionaly' => array(
        'label' => __('Email Conditionaly', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/conditional-email-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/conditional-email-1-min.svg',
        'pro' => true

    ),


    'multicondtion_or_logic' => array(
        'label' => __('Multiple OR Conditions', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/and-or-conditional-logic-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/or-condition.svg',
        'pro' => true

    ),


    'more_operators' => array(
        'label' => __('More Operators', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/elementor-form-conditional-logic-operators/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/more-opreators.svg',
        'pro' => true

    ),

    




);

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$country_field_features = array(
    'country_code' => array(
        'label' => __('Country code', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/country-code-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/country-code-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/country-code-min.svg'
    ),


    'country_state' => array(
        'label' => __('State Field', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/country-and-state-field-for-elementor-form/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/plugin/cool-formkit-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/state-field.svg',
        'pro' => true
    ),

    'auto_select_country' => array(
        'label' => __('Auto Detect Country', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/plugin/cool-formkit-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/auto-detect.svg',
        'pro' => true

    ),

);


// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$form_mask_features = array(
    'form_input_mask' => array(
        'label' => __('Field Masking', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/input-masks-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/input-masks-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/input-mask-min.svg'
    ),
    'hello_plus_support' => array(
        'label' => __('Hello Plus Support', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/plugin/cool-formkit-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/hello-plus-support.svg',
        'pro' => true
    ),

    'advanced_fields' => array(
        'label' => __('Advanced Fields', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/plugin/cool-formkit-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/advanced-field.svg',
        'pro' => true
    ),

);


// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$input_form_mask_features = array(
    'form_input_mask' => array(
        'label' => __('Input Mask', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/input-masks-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=demo&utm_content=plugins-dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/doc/input-masks-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/input-mask-min.svg'
    ),
    'hello_plus_support' => array(
        'label' => __('Hello Plus Support', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://docs.coolplugins.net/plugin/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/hello-plus-support.svg',
        'pro' => true
    ),

    'advanced_fields' => array(
        'label' => __('Advanced Fields', 'sb-elementor-contact-form-db'),
        'how_to' => str_replace('utm_source=', 'utm_source=' . $first_plugin, 'https://coolformkit.com/features/?utm_source=&utm_medium=inside&utm_campaign=demo&utm_content=dashboard'),
        'demo' => str_replace('utm_source=cfkef_plugin', 'utm_source=' . $first_plugin, 'https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=dashboard'),
        'icon' => FDBGP_PLUGIN_URL . 'assets/icons/advanced-field.svg',
        'pro' => true
    ),

);




?>


<div id="cfkef-loader" style="display: none;">
    <div class="cfkef-loader-overlay"></div>
    <div class="cfkef-loader-spinner"></div>
</div>

<form method="post" action="options.php">

    <?php settings_fields('cfkef_form_elements_group'); ?>
    <?php do_settings_sections('cfkef_form_elements_group'); ?>

    <div class="fdbgp-wrapper">

        <div class="fdbgp-content">

            <div class="fdbgp-promo">
                <div class="fdbgp-box fdbgp-left">
                    <div class="wrapper-container">
                        <div class="wrapper-header">
                            <div class="cfkef-save-all">
                                <div class="cfkef-title-desc">
                                    <h2><?php esc_html_e('Unlock Advanced Form Fields & Features with Cool FormKit', 'sb-elementor-contact-form-db'); ?></h2>
                                </div>
    
                                <div class="cfkef-save-controls">
    
                                    <a target="_blank" href="https://coolformkit.com/pricing/?utm_source=<?php echo esc_attr($first_plugin) ?>&utm_medium=inside&utm_campaign=get_pro&utm_content=dashboard" class="button">Get Cool FormKit</a>
                                </div>
    
                            </div>
                        </div>
    
                        <div class="wrapper-body">
    
    
                            <p>Cool FormKit adds powerful fields and features to Elementor forms, helping you build smarter and more interactive forms.</p>
    
    
                            <div class="cfkef-form-element-box">
                                <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
                                <?php foreach ($form_elements as $key => $element): ?>
                                    <div class="cfkef-form-element-card">
                                        <div class="cfkef-form-element-info">
                                            <img src="<?php echo esc_url($element['icon']) ?>" alt="Color Field">
                                            <h4>
                                                <?php echo esc_html($element['label']); ?>
                                                <?php if (!empty($element['pro'])): ?>
                                                    <span class="cfkef-label-popular"><a href="<?php echo esc_url($element['how_to']) ?>" target="_blank"><?php esc_html_e('Pro', 'sb-elementor-contact-form-db'); ?></a></span>
                                                <?php endif; ?>
    
                                                
                                            </h4>
                                            <div>
                                                <a href="<?php echo esc_url($element['demo']) ?>" title="Documentation" target="_blank" rel="noreferrer">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                        <path fill="#000" d="M21 11V3h-8v2h4v2h-2v2h-2v2h-2v2H9v2h2v-2h2v-2h2V9h2V7h2v4zM11 5H3v16h16v-8h-2v6H5V7h6z" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                        <label class="cfkef-toggle-switch" style="opacity: 0.5; ">
                                            <input type="checkbox" name="cfkef_enabled_elements[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $enabled_elements)); ?> class="cfkef-element-toggle"
                                                <?php disabled(!empty($element['pro'])); ?>>
                                            <?php if (!empty($element['pro'])): ?>
                                                <a href="<?php echo esc_url($element['how_to']) ?>" target="_blank">
                                                    <span class="cfkef-slider round"></span>
                                                </a>
                                            <?php else: ?>
                                                <span class="cfkef-slider round"></span>
                                            <?php endif; ?>
    
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
    
    
                        </div>
                    </div>
                    <?php $settings_page->render_review_request(); ?>
                </div>

                <div class="fdbgp-right">
                    
                    <div class="fdbgp-card">
                        <?php
                        // Check if Cool Formkit plugin is active (only cool-formkit, not extensions)
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                        $is_cool_formkit_active = is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' );
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                        $is_extensions_active = is_plugin_active( 'extensions-for-elementor-form/extensions-for-elementor-form.php' );
                        
                        if ( ! $is_cool_formkit_active ) :
                        ?>
                        <div class="fdbgp-card-wrapper cool-formkit-card" style="margin-bottom:20px;">
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
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                        $cf_plugin_file = 'conditional-fields-for-elementor-form/class-conditional-fields-for-elementor-form.php';
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                        $cf_pro_plugin_file = 'conditional-fields-for-elementor-form-pro/class-conditional-fields-for-elementor-form-pro.php';
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                        $extensions_plugin_file = 'extensions-for-elementor-form/extensions-for-elementor-form.php';
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                        $is_cf_plugin_active = is_plugin_active( $cf_plugin_file ) || is_plugin_active( $cf_pro_plugin_file );
                        
                        // Check if extensions plugin is installed (even if not active)
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                        $all_plugins = get_plugins();
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
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
                                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound	
                                $all_plugins = get_plugins();
                                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound	
                                $is_cf_pro_installed = isset($all_plugins[$cf_pro_plugin_file]);
                                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound	
                                $is_cf_free_installed = isset($all_plugins[$cf_plugin_file]);
                                
                                // Use pro plugin if it exists, otherwise use free
                                if ( $is_cf_pro_installed ) {
                                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound	
                                    $plugin_file = $cf_pro_plugin_file;
                                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound	
                                    $plugin_slug = 'conditional-fields-for-elementor-form-pro';
                                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound	
                                    $action = 'activate';
                                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound	
                                    $button_text = __('Activate Pro', 'sb-elementor-contact-form-db');
                                } else {
                                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound	
                                    $plugin_file = $cf_plugin_file;
                                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound	
                                    $plugin_slug = 'conditional-fields-for-elementor-form';
                                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound	
                                    $action = $is_cf_free_installed ? 'activate' : 'install';
                                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound	
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
                </div>
            </div>
        </div>
        <div>

</form>