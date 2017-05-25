<?php

class WCMp_Settings_Vendor_General {

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $tab;
    private $subsection;

    /**
     * Start up
     */
    public function __construct($tab, $subsection) {
        $this->tab = $tab;
        $this->subsection = $subsection;
        $this->options = get_option("wcmp_{$this->tab}_{$this->subsection}_settings_name");
        $this->settings_page_init();
    }

    /**
     * Register and add settings
     */
    public function settings_page_init() {
        global $WCMp;
        $pages = get_pages();
        $woocommerce_pages = array(wc_get_page_id('shop'), wc_get_page_id('cart'), wc_get_page_id('checkout'), wc_get_page_id('myaccount'));
        foreach ($pages as $page) {
            if (!in_array($page->ID, $woocommerce_pages)) {
                $pages_array[$page->ID] = $page->post_title;
            }
        }
        $settings_tab_options = array("tab" => "{$this->tab}",
            "ref" => &$this,
            "subsection" => "{$this->subsection}",
            "sections" => array(
                "wcmp_pages_section" => array("title" => __('WCMp Pages', $WCMp->text_domain), // Section one
                    "fields" => array(
                        "wcmp_vendor" => array('title' => __('Vendor Dashboard', $WCMp->text_domain), 'type' => 'select', 'id' => 'wcmp_vendor', 'label_for' => 'wcmp_vendor', 'name' => 'wcmp_vendor', 'options' => $pages_array, 'hints' => __('Choose your preferred page for vendor dashboard.', $WCMp->text_domain)), // Select
                        "vendor_registration" => array('title' => __('Vendor Registration', $WCMp->text_domain), 'type' => 'select', 'id' => 'vendor_registration', 'label_for' => 'vendor_registration', 'name' => 'vendor_registration', 'options' => $pages_array, 'hints' => __('Choose your preferred page for vendor Registration', $WCMp->text_domain)), // Select
                    ),
                ),
                "wcmp_vendor_general_settings_endpoint_ssection" => array(
                    "title" => __("WCMp Vendor Dashboard Endpoints", $WCMp->text_domain)
                    , "fields" => array(
                        'wcmp_vendor_announcements_endpoint' => array('title' => __('Vendor Announcements', $WCMp->text_domain), 'type' => 'text', 'id' => 'wcmp_vendor_announcements_endpoint', 'label_for' => 'wcmp_vendor_announcements_endpoint', 'name' => 'wcmp_vendor_announcements_endpoint', 'hints' => __('Set Endpoint for vendor Announcements page', $WCMp->text_domain), 'placeholder' => 'vendor-announcements'),
                        'wcmp_store_settings_endpoint' => array('title' => __('Shop Front', $WCMp->text_domain), 'type' => 'text', 'id' => 'wcmp_store_settings_endpoint', 'label_for' => 'wcmp_store_settings_endpoint', 'name' => 'wcmp_store_settings_endpoint', 'hints' => __('Set Endpoint for Shop Front page', $WCMp->text_domain), 'placeholder' => 'shop-front'),
                        'wcmp_vendor_policies_endpoint' => array('title' => __('Vendor policies', $WCMp->text_domain), 'type' => 'text', 'id' => 'wcmp_vendor_policies_endpoint', 'label_for' => 'wcmp_vendor_policies_endpoint', 'name' => 'wcmp_vendor_policies_endpoint', 'hints' => __('Set Endpoint for Vendor Plicies page', $WCMp->text_domain), 'placeholder' => 'vendor-policies'),
                        'wcmp_vendor_billing_endpoint' => array('title' => __('Vendor Billing', $WCMp->text_domain), 'type' => 'text', 'id' => 'wcmp_vendor_billing_endpoint', 'label_for' => 'wcmp_vendor_billing_endpoint', 'name' => 'wcmp_vendor_billing_endpoint', 'hints' => __('Set Endpoint for Vendor Billing page', $WCMp->text_domain), 'placeholder' => 'vendor-billing'),
                        'wcmp_vendor_shipping_endpoint' => array('title' => __('Vendor Shipping', $WCMp->text_domain), 'type' => 'text', 'id' => 'wcmp_vendor_shipping_endpoint', 'label_for' => 'wcmp_vendor_shipping_endpoint', 'name' => 'wcmp_vendor_shipping_endpoint', 'hints' => __('Set Endpoint for Vendor Shipping page', $WCMp->text_domain), 'placeholder' => 'vendor-shipping'),
                        'wcmp_vendor_report_endpoint' => array('title' => __('Vendor Report', $WCMp->text_domain), 'type' => 'text', 'id' => 'wcmp_vendor_report_endpoint', 'label_for' => 'wcmp_vendor_report_endpoint', 'name' => 'wcmp_vendor_report_endpoint', 'hints' => __('Set Endpoint for Vendor Report page', $WCMp->text_domain), 'placeholder' => 'vendor-report'),
                        "wcmp_vendor_orders_endpoint" => array('title' => __('Vendor Orders', $WCMp->text_domain), 'type' => 'text', 'id' => 'wcmp_vendor_orders_endpoint', 'label_for' => 'wcmp_vendor_orders_endpoint', 'name' => 'wcmp_vendor_orders_endpoint', 'hints' => __('Set Endpoint for vendor orders page', $WCMp->text_domain), 'placeholder' => 'vendor-orders'),
                        'wcmp_vendor_withdrawal_endpoint' => array('title' => __('Vendor Widthdrawals', $WCMp->text_domain), 'type' => 'text', 'id' => 'wcmp_vendor_withdrawal_endpoint', 'label_for' => 'wcmp_vendor_withdrawal_endpoint', 'name' => 'wcmp_vendor_withdrawal_endpoint', 'hints' => __('Set Endpoint for vendor Widthdrawals page', $WCMp->text_domain), 'placeholder' => 'vendor-withdrawal'),
                        'wcmp_transaction_details_endpoint' => array('title' => __('Transaction Details', $WCMp->text_domain), 'type' => 'text', 'id' => 'wcmp_transaction_details_endpoint', 'label_for' => 'wcmp_transaction_details_endpoint', 'name' => 'wcmp_transaction_details_endpoint', 'hints' => __('Set Endpoint for Transaction Details page', $WCMp->text_domain), 'placeholder' => 'transaction-details'),
                        'wcmp_vendor_university_endpoint' => array('title' => __('Vendor University', $WCMp->text_domain), 'type' => 'text', 'id' => 'wcmp_vendor_university_endpoint', 'label_for' => 'wcmp_vendor_university_endpoint', 'name' => 'wcmp_vendor_university_endpoint', 'hints' => __('Set Endpoint for Vendor University page', $WCMp->text_domain), 'placeholder' => 'vendor-university'),
                    )
                ),
                "wcmp_vendor_general_settings_section" => array("title" => __('Woocommerce section label', $WCMp->text_domain), // Section one
                    "fields" => array(
                        "woo_reg_section_label" => array('title' => __('Woocommerce Section Label', $WCMp->text_domain), 'type' => 'text', 'id' => 'woo_reg_section_label', 'label_for' => 'woo_reg_section_label', 'name' => 'woo_reg_section_label', 'hints' => __('Woocommerce registration section form heading.', $WCMp->text_domain)), // Checkbox
                    ),
                ),
            ),
        );

        $WCMp->admin->settings->settings_field_withsubtab_init(apply_filters("settings_{$this->tab}_{$this->subsection}_tab_options", $settings_tab_options));
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function wcmp_vendor_general_settings_sanitize($input) {
        global $WCMp;
        $new_input = array();
        $hasError = false;
        
        if(isset($input['wcmp_vendor'])){
            $new_input['wcmp_vendor'] = $input['wcmp_vendor'];
        }
        if(isset($input['vendor_registration'])){
            $new_input['vendor_registration'] = $input['vendor_registration'];
        }

        if (isset($input['woo_reg_section_label'])) {
            $new_input['woo_reg_section_label'] = sanitize_text_field($input['woo_reg_section_label']);
        }
        if (isset($input['wcmp_vendor_announcements_endpoint']) && !empty($input['wcmp_vendor_announcements_endpoint'])) {
            $new_input['wcmp_vendor_announcements_endpoint'] = sanitize_text_field($input['wcmp_vendor_announcements_endpoint']);
        }
        if (isset($input['wcmp_store_settings_endpoint']) && !empty($input['wcmp_store_settings_endpoint'])) {
            $new_input['wcmp_store_settings_endpoint'] = sanitize_text_field($input['wcmp_store_settings_endpoint']);
        }
        if (isset($input['wcmp_vendor_billing_endpoint']) && !empty($input['wcmp_vendor_billing_endpoint'])) {
            $new_input['wcmp_vendor_billing_endpoint'] = sanitize_text_field($input['wcmp_vendor_billing_endpoint']);
        }
        if (isset($input['wcmp_vendor_policies_endpoint']) && !empty($input['wcmp_vendor_policies_endpoint'])) {
            $new_input['wcmp_vendor_policies_endpoint'] = sanitize_text_field($input['wcmp_vendor_policies_endpoint']);
        }
        if (isset($input['wcmp_vendor_shipping_endpoint']) && !empty($input['wcmp_vendor_shipping_endpoint'])) {
            $new_input['wcmp_vendor_shipping_endpoint'] = sanitize_text_field($input['wcmp_vendor_shipping_endpoint']);
        }
        if (isset($input['wcmp_vendor_report_endpoint']) && !empty($input['wcmp_vendor_report_endpoint'])) {
            $new_input['wcmp_vendor_report_endpoint'] = sanitize_text_field($input['wcmp_vendor_report_endpoint']);
        }
        if (isset($input['wcmp_vendor_orders_endpoint']) && !empty($input['wcmp_vendor_orders_endpoint'])) {
            $new_input['wcmp_vendor_orders_endpoint'] = sanitize_text_field($input['wcmp_vendor_orders_endpoint']);
        }
        if (isset($input['wcmp_vendor_withdrawal_endpoint']) && !empty($input['wcmp_vendor_withdrawal_endpoint'])) {
            $new_input['wcmp_vendor_withdrawal_endpoint'] = sanitize_text_field($input['wcmp_vendor_withdrawal_endpoint']);
        }
        if (isset($input['wcmp_transaction_details_endpoint']) && !empty($input['wcmp_transaction_details_endpoint'])) {
            $new_input['wcmp_transaction_details_endpoint'] = sanitize_text_field($input['wcmp_transaction_details_endpoint']);
        }
        if (isset($input['wcmp_vendor_university_endpoint']) && !empty($input['wcmp_vendor_university_endpoint'])) {
            $new_input['wcmp_vendor_university_endpoint'] = sanitize_text_field($input['wcmp_vendor_university_endpoint']);
        }
        if (!$hasError) {
            add_settings_error(
                    "wcmp_{$this->tab}_{$this->subsection}_settings_name", esc_attr("wcmp_{$this->tab}_{$this->subsection}_settings_admin_updated"), __('Vendor Settings Updated', $WCMp->text_domain), 'updated'
            );
        }
        return apply_filters("settings_{$this->tab}_{$this->subsection}_tab_new_input", $new_input, $input);
    }

    /**
     * Print the Section text
     */
    public function wcmp_vendor_general_settings_section_info() {
        global $WCMp;
        //printf(__('Setup vendor registration field from <a href="'.  admin_url('admin.php').'?page=wcmp-setting-admin&tab=vendor&tab_section=registration">here</a>.', $WCMp->text_domain));
    }

    public function wcmp_vendor_general_settings_endpoint_ssection_info() {
        
    }

    public function wcmp_pages_section_info() {
        
    }

}
