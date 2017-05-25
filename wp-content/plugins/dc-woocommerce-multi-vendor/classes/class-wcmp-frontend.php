<?php

/**
 * WCMp Frontend Class
 *
 * @version		2.2.0
 * @package		WCMp
 * @author 		WC Marketplace
 */
class WCMp_Frontend {

    public $wcmp_shipping_fee_cost = 0;
    public $pagination_sale = array();
    public $give_tax_to_vendor = false;
    public $give_shipping_to_vendor = false;

    public function __construct() {
        //enqueue scripts
        add_action('wp_enqueue_scripts', array(&$this, 'frontend_scripts'));
        //enqueue styles
        add_action('wp_enqueue_scripts', array(&$this, 'frontend_styles'));
        add_action('woocommerce_archive_description', array(&$this, 'product_archive_vendor_info'), 10);
        add_filter('body_class', array(&$this, 'set_product_archive_class'));
        add_action('template_redirect', array(&$this, 'template_redirect'));
        add_action('woocommerce_checkout_order_processed', array(&$this, 'wcmp_checkout_order_processed'), 30, 3);
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_vendor_msg_in_thank_you_page'), 100);
        $this->give_tax_to_vendor = get_wcmp_vendor_settings('give_tax', 'payment');
        $this->give_shipping_to_vendor = get_wcmp_vendor_settings('give_shipping', 'payment');
        //add_action( 'woocommerce_flat_rate_shipping_add_rate', array($this, 'add_vendor_shipping_rate'), 10, 2 );
        add_action('wcmp_vendor_register_form', array(&$this, 'wcmp_vendor_register_form_callback'));
        add_action('woocommerce_register_post', array(&$this, 'wcmp_validate_extra_register_fields'), 10, 3);
        add_action('woocommerce_created_customer', array(&$this, 'wcmp_save_extra_register_fields'), 10, 3);
    }

    /**
     * Save the extra register fields.
     *
     * @param  int  $customer_id Current customer ID.
     *
     * @return void
     */
    function wcmp_save_extra_register_fields($customer_id) {
        global $WCMp;
        if (isset($_POST['wcmp_vendor_fields']) && isset($_POST['pending_vendor'])) {

            if (isset($_FILES['wcmp_vendor_fields'])) {
                $attacment_files = $_FILES['wcmp_vendor_fields'];
                $files = array();
                $count = 0;
                if (!empty($attacment_files) && is_array($attacment_files)) {
                    foreach ($attacment_files['name'] as $key => $attacment) {
                        foreach ($attacment as $key_attacment => $value_attacment) {
                            $files[$count]['name'] = $value_attacment;
                            $files[$count]['type'] = $attacment_files['type'][$key][$key_attacment];
                            $files[$count]['tmp_name'] = $attacment_files['tmp_name'][$key][$key_attacment];
                            $files[$count]['error'] = $attacment_files['error'][$key][$key_attacment];
                            $files[$count]['size'] = $attacment_files['size'][$key][$key_attacment];
                            $files[$count]['field_key'] = $key;
                            $count++;
                        }
                    }
                }
                $upload_dir = wp_upload_dir();
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                if (!function_exists('wp_handle_upload')) {
                    require_once( ABSPATH . 'wp-admin/includes/file.php' );
                }
                foreach ($files as $file) {
                    $uploadedfile = $file;
                    $upload_overrides = array('test_form' => false);
                    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
                    if ($movefile && !isset($movefile['error'])) {
                        $filename = $movefile['file'];
                        $filetype = wp_check_filetype($filename, null);
                        $attachment = array(
                            'post_mime_type' => $filetype['type'],
                            'post_title' => $file['name'],
                            'post_content' => '',
                            'post_status' => 'inherit',
                            'guid' => $movefile['url']
                        );
                        $attach_id = wp_insert_attachment($attachment, $movefile['file']);
                        $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
                        wp_update_attachment_metadata($attach_id, $attach_data);
                        $_POST['wcmp_vendor_fields'][$file['field_key']]['value'][] = $attach_id;
                    }
                }
            }
            $wcmp_vendor_fields = $_POST['wcmp_vendor_fields'];
            $user_data = get_userdata($customer_id);
            $user_name = $user_data->user_login;
            $user_email = $user_data->user_email;


            // Create post object
            $my_post = array(
                'post_title' => $user_name,
                'post_status' => 'publish',
                'post_author' => 1,
                'post_type' => 'wcmp_vendorrequest'
            );

            // Insert the post into the database
            $register_vendor_post_id = wp_insert_post($my_post);
            update_post_meta($register_vendor_post_id, 'user_id', $customer_id);
            update_post_meta($register_vendor_post_id, 'username', $user_name);
            update_post_meta($register_vendor_post_id, 'email', $user_email);
            update_post_meta($register_vendor_post_id, 'wcmp_vendor_fields', $wcmp_vendor_fields);
            update_user_meta($customer_id, 'wcmp_vendor_registration_form_id', $register_vendor_post_id);
            $WCMp->user->wcmp_woocommerce_created_customer_notification();
        }
    }

    /**
     * Validate the extra register fields.
     *
     * @param  string $username          Current username.
     * @param  string $email             Current email.
     * @param  object $validation_errors WP_Error object.
     *
     * @return void
     */
    function wcmp_validate_extra_register_fields($username, $email, $validation_errors) {
        $wcmp_vendor_registration_form_data = get_option('wcmp_vendor_registration_form_data');
        if (isset($_POST['g-recaptcha-response']) && empty($_POST['g-recaptcha-response'])) {
            $validation_errors->add('recaptcha is not validate', __('Please Verify  Recaptcha', 'woocommerce'));
        }
        if (isset($_FILES['wcmp_vendor_fields'])) {
            $attacment_files = $_FILES['wcmp_vendor_fields'];
            if (!empty($attacment_files) && is_array($attacment_files)) {
                foreach ($attacment_files['name'] as $key => $value) {
                    $file_type = array();
                    foreach ($wcmp_vendor_registration_form_data[$key]['fileType'] as $key1 => $value1) {
                        if ($value1['selected']) {
                            array_push($file_type, $value1['value']);
                        }
                    }
                    foreach ($attacment_files['type'][$key] as $file_key => $file_value) {
                        if (!empty($attacment_files['name'][$key][$file_key])) {
                            if (!in_array($file_value, $file_type)) {
                                $validation_errors->add('file type error', __('Please Upload valid file', 'woocommerce'));
                            }
                        }
                    }
                    foreach ($attacment_files['size'][$key] as $file_size_key => $file_size_value) {
                        if (!empty($wcmp_vendor_registration_form_data[$key]['fileSize'])) {
                            if ($file_size_value > $wcmp_vendor_registration_form_data[$key]['fileSize']) {
                                $validation_errors->add('file size error', __('File upload limit exceeded', 'woocommerce'));
                            }
                        }
                    }
                }
            }
        }
    }

    function wcmp_vendor_register_form_callback() {
        global $WCMp;
        $wcmp_vendor_registration_form_data = get_option('wcmp_vendor_registration_form_data');
        $WCMp->template->get_template('vendor_registration_form.php', array('wcmp_vendor_registration_form_data' => $wcmp_vendor_registration_form_data));
    }

    public function display_vendor_msg_in_thank_you_page($order_id) {
        global $wpdb, $WCMp;
        $order = wc_get_order($order_id);
        $items = $order->get_items('line_item');
        $vendor_array = array();
        $author_id = '';
        $customer_support_details_settings = get_option('wcmp_general_customer_support_details_settings_name');
        $is_csd_by_admin = '';
        foreach ($items as $item_id => $item) {
            $product_id = wc_get_order_item_meta($item_id, '_product_id', true);
            if ($product_id) {
                $author_id = wc_get_order_item_meta($item_id, '_vendor_id', true);
                if (empty($author_id)) {
                    $product_vendors = get_wcmp_product_vendors($product_id);
                    if (isset($product_vendors) && (!empty($product_vendors))) {
                        $author_id = $product_vendors->id;
                    } else {
                        $author_id = get_post_field('post_author', $product_id);
                    }
                }
                if (isset($vendor_array[$author_id])) {
                    $vendor_array[$author_id] = $vendor_array[$author_id] . ',' . $item['name'];
                } else {
                    $vendor_array[$author_id] = $item['name'];
                }
            }
        }
        if (!empty($vendor_array)) {
            echo '<div style="clear:both">';
            $can_vendor_add_message_on_email_and_thankyou_page = apply_filters('can_vendor_add_message_on_email_and_thankyou_page', true);
            $is_customer_support_details = apply_filters('is_customer_support_details', true);
            if (isset($customer_support_details_settings['can_vendor_add_message_on_email_and_thankyou_page']) && $can_vendor_add_message_on_email_and_thankyou_page) {
                $WCMp->template->get_template('vendor_message_to_buyer.php', array('vendor_array' => $vendor_array, 'capability_settings' => $customer_support_details_settings, 'customer_support_details_settings' => $customer_support_details_settings));
            } elseif (get_wcmp_vendor_settings('is_customer_support_details', 'general') == 'Enable') {
                $WCMp->template->get_template('customer_support_details_to_buyer.php', array('vendor_array' => $vendor_array, 'capability_settings' => $customer_support_details_settings, 'customer_support_details_settings' => $customer_support_details_settings));
            }
            echo "</div>";
        }
    }

    /**
     *
     * 
     */
    function add_vendor_shipping_rate($method, $rate) {
        $found_shipping_classes = $method->find_shipping_classes($package);
        $highest_class_cost = 0;

        foreach ($found_shipping_classes as $shipping_class => $products) {
            // Also handles BW compatibility when slugs were used instead of ids
            $shipping_class_term = get_term_by('slug', $shipping_class, 'product_shipping_class');
            $class_cost_string = $shipping_class_term && $shipping_class_term->term_id ? $method->get_option('class_cost_' . $shipping_class_term->term_id, $method->get_option('class_cost_' . $shipping_class, '')) : '0';

            if ($class_cost_string === '') {
                continue;
            }
            $vendor_id = get_woocommerce_term_meta($shipping_class_term->term_id, 'vendor_id', true);
            $has_costs = true;
            $class_cost = $method->evaluate_cost($class_cost_string, array(
                'qty' => array_sum(wp_list_pluck($products, 'quantity')),
                'cost' => array_sum(wp_list_pluck($products, 'line_total'))
            ));

            if ($method->type === 'class') {
                $new_rate = $rate;
                $new_rate['id'] = 'flat_shipping_' . $vendor_id; // Append a custom ID.
                $new_rate['label'] = 'Vendor Shipping'; // Rename to 'Rushed Shipping'.
                $new_rate['cost'] = $class_cost; // Add $2 to the cost.
                // Add it to WC.
                $method->add_rate($new_rate);
            }
        }
    }

    /**
     * WCMp Calculate shipping for order
     *
     * @support flat rate per item 
     * @param int $order_id
     * @param object $order_posted
     * @return void
     */
    function wcmp_checkout_order_processed($order_id, $order_posted, $order) {
        global $wpdb, $WCMp;
        $shipping_method = $order->get_shipping_methods();
        if (version_compare(WC_VERSION, '2.6.0', '>=') && !empty($shipping_method)) {
            foreach ($shipping_method as $key => $method) {
                $method_id = $method['method_id'];
                break;
            }
            $method_arr = explode(':', $method_id);

            if (count($method_arr) >= 2) {
                $method_name = $method_arr[0];
                $method_instance = $method_arr[1];

                if (!empty($method_name) && $method_name == 'flat_rate') {

                    $woocommerce_shipping_method_settings = get_option('woocommerce_' . $method_name . '_' . $method_instance . '_settings');
                    $line_items = $order->get_items('line_item');
                    if ($woocommerce_shipping_method_settings['type'] == 'class') {
                        if (!empty($line_items)) {
                            foreach ($line_items as $item_id => $item) {
                                $shipping_item_qty = 0;
                                $product = $order->get_product_from_item($item);
                                $shipping_class = $product->get_shipping_class_id();
                                if (empty($shipping_class) || !isset($item['vendor_id']))
                                    continue;
                                $vendor_id = $item['vendor_id'];
                                if ($this->give_tax_to_vendor == 'Enable') {
                                    wc_add_order_item_meta($item_id, '_give_tax_to_vendor', 1);
                                } else {
                                    wc_add_order_item_meta($item_id, '_give_tax_to_vendor', 0);
                                }

                                $class_cost_string = $woocommerce_shipping_method_settings['class_cost_' . $shipping_class]; //$shipping_class ? $wc_flat_rate->get_option( 'class_cost_' . $shipping_class, '' ) : $wc_flat_rate->get_option( 'no_class_cost', '' );								
                                if (isset($shipping_classes[$vendor_id])) {
                                    $shipping_classes[$vendor_id] = array(
                                        'string' => $class_cost_string,
                                        'qty' => $item['qty'] + $shipping_classes[$vendor_id]['qty'],
                                        'cost' => $order->get_line_subtotal($item) + $shipping_classes[$vendor_id]['cost']
                                    );
                                } else {
                                    $shipping_classes[$vendor_id] = array(
                                        'string' => $class_cost_string,
                                        'qty' => $item['qty'],
                                        'cost' => $order->get_line_subtotal($item)
                                    );
                                }
                            }
                            $shipping = $order->get_items('shipping');
                            if (!empty($shipping)) {
                                foreach ($shipping as $shipping_id => $value) {
                                    $order_item_shipping = new WC_Order_Item_Shipping($shipping_id);
                                    $shipping_taxes = $order_item_shipping->get_taxes();
                                    $tax_rates = array();
                                    foreach ($shipping_taxes['total'] as $tax_class_id => $tax_amount) {
                                        $tax_rates[$tax_class_id] = $tax_amount;
                                    }
                                    foreach ($shipping_classes as $key => $shipping_class) {
                                        $cost_item_id = $this->calculate_flat_rate_shipping_cost($shipping_class['string'], array(
                                            'qty' => $shipping_class['qty'],
                                            'cost' => $shipping_class['cost']
                                        ));
                                        $flat_shipping_per_vendor = wc_get_order_item_meta($shipping_id, 'vendor_cost_' . $key, true);
                                        if (!$flat_shipping_per_vendor) {
                                            wc_add_order_item_meta($shipping_id, 'vendor_cost_' . $key, round($cost_item_id, 2));
                                            $flat_shipping_per_vendor = $cost_item_id;
                                        }
                                        $shipping_vendor_taxes = array();
                                        foreach ($tax_rates as $tax_class_id => $tax_rate) {
                                            $shipping_vendor_taxes[$tax_class_id] = round($tax_rate, 2);
                                        }
                                        $tax_shipping_per_vendor = wc_get_order_item_meta($shipping_id, 'vendor_tax_' . $key, true);
                                        if (!$tax_shipping_per_vendor) {
                                            wc_add_order_item_meta($shipping_id, 'vendor_tax_' . $key, $shipping_vendor_taxes);
                                        }
                                    }
                                    if ($this->give_shipping_to_vendor == 'Enable') {
                                        wc_add_order_item_meta($shipping_id, '_give_shipping_to_vendor', 1);
                                    } else {
                                        wc_add_order_item_meta($shipping_id, '_give_shipping_to_vendor', 0);
                                    }
                                }
                            }
                        }
                    }
                }
            } else { // Deprecated shipping method
                if ($order->has_shipping_method('legacy_flat_rate')) {
                    $woocommerce_flat_rate_settings = get_option('woocommerce_flat_rate_settings');
                    $line_items = $order->get_items('line_item');
                    if ($woocommerce_flat_rate_settings['enabled'] == 'yes') {
                        if ($woocommerce_flat_rate_settings['type'] == 'class') {
                            if (!empty($line_items)) {
                                foreach ($line_items as $item_id => $item) {
                                    $shipping_item_qty = 0;
                                    //$wc_flat_rate = new WC_Shipping_Flat_Rate();
                                    $product = $order->get_product_from_item($item);
                                    $shipping_class = $product->get_shipping_class_id();
                                    if (empty($shipping_class) || !isset($item['vendor_id']))
                                        continue;
                                    if ($this->give_tax_to_vendor == 'Enable') {
                                        wc_add_order_item_meta($item_id, '_give_tax_to_vendor', 1);
                                    } else {
                                        wc_add_order_item_meta($item_id, '_give_tax_to_vendor', 0);
                                    }
                                    $vendor_id = $item['vendor_id'];
                                    $class_cost_string = $woocommerce_flat_rate_settings['class_cost_' . $shipping_class];
                                    if (isset($shipping_classes[$vendor_id])) {
                                        $shipping_classes[$vendor_id] = array(
                                            'string' => $class_cost_string,
                                            'qty' => $item['qty'] + $shipping_classes[$vendor_id]['qty'],
                                            'cost' => $order->get_line_subtotal($item) + $shipping_classes[$vendor_id]['cost']
                                        );
                                    } else {
                                        $shipping_classes[$vendor_id] = array(
                                            'string' => $class_cost_string,
                                            'qty' => $item['qty'],
                                            'cost' => $order->get_line_subtotal($item)
                                        );
                                    }
                                }
                                $shipping = $order->get_items('shipping');
                                foreach ($shipping as $shipping_id => $value) {
                                    $order_item_shipping = new WC_Order_Item_Shipping($shipping_id);
                                    $shipping_taxes = $order_item_shipping->get_taxes();
                                    $tax_rates = array();
                                    foreach ($shipping_taxes['total'] as $tax_class_id => $tax_amount) {
                                        $tax_rates[$tax_class_id] = $tax_amount;
                                    }
                                    foreach ($shipping_classes as $key => $shipping_class) {
                                        $cost_item_id = $this->calculate_flat_rate_shipping_cost($shipping_class['string'], array(
                                            'qty' => $shipping_class['qty'],
                                            'cost' => $shipping_class['cost']
                                        ));
                                        $flat_shipping_per_vendor = wc_get_order_item_meta($shipping_id, 'vendor_cost_' . $key, true);
                                        if (!$flat_shipping_per_vendor) {
                                            wc_add_order_item_meta($shipping_id, 'vendor_cost_' . $key, round($cost_item_id, 2));
                                            $flat_shipping_per_vendor = $cost_item_id;
                                        }
                                        $shipping_vendor_taxes = array();
                                        foreach ($tax_rates as $tax_class_id => $tax_rate) {
                                            $shipping_vendor_taxes[$tax_class_id] = round($tax_rate, 2);
                                        }
                                        $tax_shipping_per_vendor = wc_get_order_item_meta($shipping_id, 'vendor_tax_' . $key, true);
                                        if (!$tax_shipping_per_vendor) {
                                            wc_add_order_item_meta($shipping_id, 'vendor_tax_' . $key, $shipping_vendor_taxes);
                                        }
                                    }
                                    if ($this->give_shipping_to_vendor == 'Enable') {
                                        wc_add_order_item_meta($shipping_id, '_give_shipping_to_vendor', 1);
                                    } else {
                                        wc_add_order_item_meta($shipping_id, '_give_shipping_to_vendor', 0);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($order->has_shipping_method('legacy_international_delivery')) {
                $woocommerce_international_delivery_settings = get_option('woocommerce_international_delivery_settings');
                $line_items = $order->get_items('line_item');

                if ($woocommerce_international_delivery_settings['enabled'] == 'yes') {
                    if ($woocommerce_international_delivery_settings['type'] == 'class') {
                        if (!empty($line_items)) {
                            $item_id = false;
                            foreach ($line_items as $item_id => $item) {
                                //$wc_international_flat_rate = new WC_Shipping_International_Delivery();
                                $product = $order->get_product_from_item($item);
                                $shipping_class = $product->get_shipping_class_id();
                                if (empty($shipping_class) || !isset($item['vendor_id']))
                                    continue;
                                if ($this->give_tax_to_vendor == 'Enable') {
                                    wc_add_order_item_meta($item_id, '_give_tax_to_vendor', 1);
                                } else {
                                    wc_add_order_item_meta($item_id, '_give_tax_to_vendor', 0);
                                }
                                $vendor_id = $item['vendor_id'];
                                $class_cost_string = $woocommerce_international_delivery_settings['class_cost_' . $shipping_class]; //$shipping_class ? $wc_flat_rate->get_option( 'class_cost_' . $shipping_class, '' ) : $wc_flat_rate->get_option( 'no_class_cost', '' );								
                                if (isset($shipping_classes[$vendor_id])) {
                                    $shipping_classes[$vendor_id] = array(
                                        'string' => $class_cost_string,
                                        'qty' => $item['qty'] + $shipping_classes[$vendor_id]['qty'],
                                        'cost' => $order->get_line_subtotal($item) + $shipping_classes[$vendor_id]['cost']
                                    );
                                } else {
                                    $shipping_classes[$vendor_id] = array(
                                        'string' => $class_cost_string,
                                        'qty' => $item['qty'],
                                        'cost' => $order->get_line_subtotal($item)
                                    );
                                }
                            }
                            $shipping = $order->get_items('shipping');
                            foreach ($shipping as $shipping_id => $value) {
                                $order_item_shipping = new WC_Order_Item_Shipping($shipping_id);
                                $shipping_taxes = $order_item_shipping->get_taxes();
                                $tax_rates = array();
                                foreach ($shipping_taxes['total'] as $tax_class_id => $tax_amount) {
                                    $tax_rates[$tax_class_id] = $tax_amount;
                                }
                                foreach ($shipping_classes as $key => $shipping_class) {
                                    $cost_item_id = $this->calculate_flat_rate_shipping_cost($shipping_class['string'], array(
                                        'qty' => $shipping_class['qty'],
                                        'cost' => $shipping_class['cost']
                                    ));
                                    $flat_shipping_per_vendor = wc_get_order_item_meta($shipping_id, 'vendor_cost_' . $key, true);
                                    if (!$flat_shipping_per_vendor) {
                                        wc_add_order_item_meta($shipping_id, 'vendor_cost_' . $key, round($cost_item_id, 2));
                                        $flat_shipping_per_vendor = $cost_item_id;
                                    }
                                    $shipping_vendor_taxes = array();
                                    foreach ($tax_rates as $tax_class_id => $tax_rate) {
                                        $shipping_vendor_taxes[$tax_class_id] = round($tax_rate, 2);
                                    }
                                    $tax_shipping_per_vendor = wc_get_order_item_meta($shipping_id, 'vendor_tax_' . $key, true);
                                    if (!$tax_shipping_per_vendor) {
                                        wc_add_order_item_meta($shipping_id, 'vendor_tax_' . $key, $shipping_vendor_taxes);
                                    }
                                }
                                if ($this->give_shipping_to_vendor == 'Enable') {
                                    wc_add_order_item_meta($shipping_id, '_give_shipping_to_vendor', 1);
                                } else {
                                    wc_add_order_item_meta($shipping_id, '_give_shipping_to_vendor', 0);
                                }
                            }
                        }
                    }
                }
            }
        }

        $vendor_shipping_array = get_post_meta($order_id, 'dc_pv_shipped', true);
        $mark_ship = 0;
        $items = $order->get_items('line_item');

        foreach ($items as $order_item_id => $item) {
            $comm_pro_id = $product_id = $item['product_id'];
            $variation_id = $item['variation_id'];
            if ($variation_id) {
                $comm_pro_id = $variation_id;
            }
            if ($product_id) {
                $product_vendors = get_wcmp_product_vendors($product_id);
                if ($product_vendors) {
                    if (isset($product_vendors->id) && is_array($vendor_shipping_array)) {
                        if (in_array($product_vendors->id, $vendor_shipping_array)) {
                            $mark_ship = 1;
                        }
                    }

                    $insert_query = $wpdb->query($wpdb->prepare("INSERT INTO `{$wpdb->prefix}wcmp_vendor_orders` ( order_id, commission_id, vendor_id, shipping_status, order_item_id, product_id )
													 VALUES
													 ( %d, %d, %d, %s, %d, %d ) ON DUPLICATE KEY UPDATE `created` = now()", $order_id, 0, $product_vendors->id, $mark_ship, $order_item_id, $comm_pro_id));
                }
            }
        }
    }

    /**
     * Get shipping fee
     *
     * Now deprecated
     */
    function get_fee($fee, $total) {
        $woocommerce_flat_rate_settings = get_option('woocommerce_flat_rate_settings');
        if (strstr($fee, '%')) {
            $fee = ( $total / 100 ) * str_replace('%', '', $fee);
        }
        if (!empty($woocommerce_flat_rate_settings['minimum_fee']) && $woocommerce_flat_rate_settings['minimum_fee'] > $fee) {
            $fee = $woocommerce_flat_rate_settings['minimum_fee'];
        }
        return $fee;
    }

    /**
     * Add frontend scripts
     * @return void
     */
    function frontend_scripts() {
        global $WCMp;
        $frontend_script_path = $WCMp->plugin_url . 'assets/frontend/js/';
        $frontend_script_path = str_replace(array('http:', 'https:'), '', $frontend_script_path);
        $pluginURL = str_replace(array('http:', 'https:'), '', $WCMp->plugin_url);
        $suffix = defined('WCMP_SCRIPT_DEBUG') && WCMP_SCRIPT_DEBUG ? '' : '.min';
        if (is_vendor_dashboard()) {
            wp_enqueue_script('wcmp_frontend_vdashboard_js', $frontend_script_path . 'wcmp_vendor_dashboard' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        }
        // Enqueue your frontend javascript from here
        wp_enqueue_script('frontend_js', $frontend_script_path . 'frontend' . $suffix . '.js', array('jquery'), $WCMp->version, true);

        if (is_vendor_order_by_product_page()) {
            wp_enqueue_script('vendor_order_by_product_js', $frontend_script_path . 'vendor_order_by_product' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        }

        if (is_single()) {
            wp_enqueue_script('simplepopup_js', $frontend_script_path . 'simplepopup' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        }

        wp_register_script('gmaps-api', '//maps.google.com/maps/api/js?sensor=false&amp;language=en', array('jquery'));
        wp_register_script('gmap3', $frontend_script_path . 'gmap3.min.js', array('jquery', 'gmaps-api'), '6.0.0', false);
        if (is_tax('dc_vendor_shop') || is_singular('product')) {
            wp_enqueue_script('gmap3');
        }
        if (is_vendor_page()) {
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-tabs');
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script('wcmp_new_vandor_dashboard_js', $frontend_script_path . '/vendor_dashboard' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        }
        if (is_tax('dc_vendor_shop')) {
            $queried_object = get_queried_object();
            if (isset($queried_object->term_id) && !empty($queried_object)) {
                $vendor = get_wcmp_vendor_by_term($queried_object->term_id);
                $vendor_id = $vendor->id;
            }

            wp_enqueue_script('wcmp_seller_review_rating_js', $frontend_script_path . '/vendor_review_rating' . $suffix . '.js', array('jquery'), $WCMp->version, true);
            $vendor_review_rating_msg_array = array(
                'rating_error_msg_txt' => __('Please rate the vendor', $WCMp->text_domain),
                'review_error_msg_txt' => __('Please review your vendor and minimum 10 Character required', $WCMp->text_domain),
                'review_success_msg_txt' => __('Your review submitted successfully', $WCMp->text_domain),
                'review_failed_msg_txt' => __('Error in system please try again later', $WCMp->text_domain),
                'ajax_url' => trailingslashit(get_admin_url()) . 'admin-ajax.php',
                'vendor_id' => $vendor_id ? $vendor_id : ''
            );
            wp_localize_script('wcmp_seller_review_rating_js', 'wcmp_review_rating_msg', $vendor_review_rating_msg_array);
        }
        if (is_singular('product')) {
            wp_enqueue_script('wcmp_single_product_multiple_vendors', $frontend_script_path . '/single-product-multiple-vendors' . $suffix . '.js', array('jquery'), $WCMp->version, true);
        }
        // Enqueue popup script
        wp_enqueue_script('popup_js', $frontend_script_path . 'wcmp-popup' . $suffix . '.js', array('jquery'), $WCMp->version, true);
    }

    /**
     * Add frontend styles
     * @return void
     */
    function frontend_styles() {
        global $WCMp;
        $frontend_style_path = $WCMp->plugin_url . 'assets/frontend/css/';
        $frontend_style_path = str_replace(array('http:', 'https:'), '', $frontend_style_path);
        $suffix = defined('WCMP_SCRIPT_DEBUG') && WCMP_SCRIPT_DEBUG ? '' : '.min';



        if (is_tax('dc_vendor_shop')) {
            wp_enqueue_style('frontend_css', $frontend_style_path . 'frontend' . $suffix . '.css', array(), $WCMp->version);
        }

        wp_enqueue_style('product_css', $frontend_style_path . 'product' . $suffix . '.css', array(), $WCMp->version);

        if (is_vendor_order_by_product_page()) {
            wp_enqueue_style('vendor_order_by_product_css', $frontend_style_path . 'vendor_order_by_product' . $suffix . '.css', array(), $WCMp->version);
        }

        $link_color = isset($WCMp->vendor_caps->frontend_cap['catalog_colorpicker']) ? $WCMp->vendor_caps->frontend_cap['catalog_colorpicker'] : '#000000';
        $hover_link_color = isset($WCMp->vendor_caps->frontend_cap['catalog_hover_colorpicker']) ? $WCMp->vendor_caps->frontend_cap['catalog_hover_colorpicker'] : '#000000';

        $custom_css = "
                .by-vendor-name-link:hover{
                        color: {$hover_link_color} !important;
                }
                .by-vendor-name-link{
                        color: {$link_color} !important;
                }";
        wp_add_inline_style('product_css', $custom_css);
        if (is_vendor_page()) {
            wp_enqueue_style('dashicons');
            wp_enqueue_style('jquery-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
            wp_enqueue_style('wcmp_new_vandor_dashboard_css', $frontend_style_path . 'vendor_dashboard' . $suffix . '.css', array(), $WCMp->version);
            wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css', array(), $WCMp->version);
        }
        if (is_tax('dc_vendor_shop')) {
            $current_theme = get_option('template');
            if ($current_theme == 'storefront') {
                wp_enqueue_style('wcmp_review_rating', $frontend_style_path . 'review_rating_storefront' . $suffix . '.css', array(), $WCMp->version);
            } else {
                wp_enqueue_style('wcmp_review_rating', $frontend_style_path . 'review_rating' . $suffix . '.css', array(), $WCMp->version);
            }
        }
        wp_enqueue_style('multiple_vendor', $frontend_style_path . 'multiple-vendor' . $suffix . '.css', array(), $WCMp->version);
    }

    /**
     * Add html for vendor taxnomy page
     * @return void
     */
    function product_archive_vendor_info() {
        global $WCMp;
        if (is_tax('dc_vendor_shop')) {
            // Get vendor ID
            $vendor_id = get_queried_object()->term_id;
            // Get vendor info
            $vendor = get_wcmp_vendor_by_term($vendor_id);
            $image = '';
            $image = $vendor->image;
            if (!$image)
                $image = $WCMp->plugin_url . 'assets/images/WP-stdavatar.png';
            $description = $vendor->description;

            $address = '';

            if ($vendor->city) {
                $address = $vendor->city . ', ';
            }
            if ($vendor->state) {
                $address .= $vendor->state . ', ';
            }
            if ($vendor->country) {
                $address .= $vendor->country;
            }
            $WCMp->template->get_template('archive_vendor_info.php', array('vendor_id' => $vendor->id, 'banner' => $vendor->banner, 'profile' => $image, 'description' => stripslashes($description), 'mobile' => $vendor->phone, 'location' => $address, 'email' => $vendor->user_data->user_email));
        }
    }

    /**
     * Add 'woocommerce' class to body tag for vendor pages
     *
     * @param  arr $classes Existing classes
     * @return arr          Modified classes
     */
    public function set_product_archive_class($classes) {
        if (is_tax('dc_vendor_shop')) {

            // Add generic classes
            $classes[] = 'woocommerce';
            $classes[] = 'product-vendor';

            // Get vendor ID
            $vendor_id = get_queried_object()->term_id;

            // Get vendor info
            $vendor = get_wcmp_vendor_by_term($vendor_id);

            // Add vendor slug as class
            if ('' != $vendor->slug) {
                $classes[] = $vendor->slug;
            }
        }
        return $classes;
    }

    /**
     * template redirect function
     * @return void
     */
    function template_redirect() {
        //redirect to my account or vendor dashbord page if user loggedin
        if (is_user_logged_in() && is_page_vendor_registration()) {
            if (is_user_wcmp_vendor(get_current_user_id())) {
                wp_safe_redirect(get_permalink(wcmp_vendor_dashboard_page_id()));
            } else {
                wp_safe_redirect(get_permalink(wc_get_page_id('myaccount')));
            }
            exit();
        }
    }

    /**
     * Calculate order falt rate shipping
     *
     * @support WC 2.4
     */
    public function evaluate_flat_shipping_cost($sum, $args = array()) {
        include_once( WC()->plugin_path() . '/includes/shipping/flat-rate/includes/class-wc-eval-math.php' );

        add_shortcode('fee', array($this, 'wcmp_shipping_fee_calculation'));
        $this->wcmp_shipping_fee_cost = $args['cost'];

        $sum = rtrim(ltrim(do_shortcode(str_replace(
                                        array(
            '[qty]',
            '[cost]'
                                        ), array(
            $args['qty'],
            $args['cost']
                                        ), $sum
                        )), "\t\n\r\0\x0B+*/"), "\t\n\r\0\x0B+-*/");

        remove_shortcode('fee', array($this, 'wcmp_shipping_fee_calculation'));

        return $sum ? WC_Eval_Math::evaluate($sum) : 0;
    }

    /**
     * Calculate order flat rate shipping
     *
     * @support WC 2.6
     */
    public function calculate_flat_rate_shipping_cost($sum, $args = array()) {
        include_once( WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php' );
        $WC_Shipping_Flat_Rate = new WC_Shipping_Flat_Rate();
        // Allow 3rd parties to process shipping cost arguments
        $args = apply_filters('woocommerce_evaluate_shipping_cost_args', $args, $sum, $this);
        $locale = localeconv();
        $decimals = array(wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point']);
        $this->fee_cost = $args['cost'];

        // Expand shortcodes
        add_shortcode('fee', array($WC_Shipping_Flat_Rate, 'fee'));

        $sum = do_shortcode(str_replace(
                        array(
            '[qty]',
            '[cost]'
                        ), array(
            $args['qty'],
            $args['cost']
                        ), $sum
        ));

        remove_shortcode('fee', array($WC_Shipping_Flat_Rate, 'fee'));

        // Remove whitespace from string
        $sum = preg_replace('/\s+/', '', $sum);

        // Remove locale from string
        $sum = str_replace($decimals, '.', $sum);

        // Trim invalid start/end characters
        $sum = rtrim(ltrim($sum, "\t\n\r\0\x0B+*/"), "\t\n\r\0\x0B+-*/");

        // Do the math
        return $sum ? WC_Eval_Math::evaluate($sum) : 0;
    }

    /**
     * Calculate flat rate shipping fee
     *
     * @support WC 2.4
     */
    public function wcmp_shipping_fee_calculation($atts) {
        $atts = shortcode_atts(array(
            'percent' => '',
            'min_fee' => ''
                ), $atts);

        $calculated_fee = 0;

        if ($atts['percent']) {
            $calculated_fee = $this->wcmp_shipping_fee_cost * ( floatval($atts['percent']) / 100 );
        }
        if ($atts['min_fee'] && $calculated_fee < $atts['min_fee']) {
            $calculated_fee = $atts['min_fee'];
        }

        return $calculated_fee;
    }

}
