<?php
/**
 * The template for displaying vendor dashboard
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/vendor-shipping.php
 *
 * @author 		WC Marketplace
 * @package 	WCMp/Templates
 * @version   2.2.0
 */
if (!defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}
global $woocommerce, $WCMp, $wpdb;

$vendor_user_id = get_current_user_id();
$vendor_data = get_wcmp_vendor($vendor_user_id);
if ($vendor_data) :
    
    $vendor_shipping_data = get_user_meta($vendor_user_id, 'vendor_shipping_data', true);
    ?>
    <form name="vendor_shipping_form" class="wcmp_shipping_form" method="post">
        <table class="shipping_table">
            <tbody>
                <?php
                if (version_compare(WC_VERSION, '2.6.0', '>=')) {
                    $shipping_class_id = get_user_meta($vendor_user_id, 'shipping_class_id', true);
                    if (!empty($shipping_class_id)) {
                        $term_shipping_obj = get_term_by('id', $shipping_class_id, 'product_shipping_class');
                    }
                    if (!(isset($term_shipping_obj) && isset($term_shipping_obj->term_id) && !empty($term_shipping_obj))) {
                        $shipping_term = wp_insert_term($vendor_data->user_data->user_login . '-' . $vendor_user_id, 'product_shipping_class');
                        if (!is_wp_error($shipping_term)) {
                            $shipping_term_id = $shipping_term['term_id'];
                            update_user_meta($vendor_user_id, 'shipping_class_id', $shipping_term['term_id']);
                            add_woocommerce_term_meta($shipping_term['term_id'], 'vendor_id', $vendor_user_id);
                            add_woocommerce_term_meta($shipping_term['term_id'], 'vendor_shipping_origin', get_option('woocommerce_default_country'));
                        }
                    } else {
                        $shipping_class_id = get_user_meta($vendor_user_id, 'shipping_class_id', true);
                        if (empty($shipping_class_id)) {
                            $sterm = get_term_by('slug', $vendor_data->user_data->user_login . '-' . $vendor_user_id, 'product_shipping_class');
                            if (isset($sterm->term_id) && !empty($sterm->term_id)) {
                                $shipping_class_id = $sterm->term_id;
                                update_user_meta($vendor_user_id, 'shipping_class_id', $shipping_class_id);
                            }
                        }
                        update_woocommerce_term_meta($shipping_class_id, 'vendor_id', $vendor_user_id);
                        update_woocommerce_term_meta($shipping_class_id, 'vendor_shipping_origin', get_option('woocommerce_default_country'));
                        $shipping_term_id = $shipping_class_id;
                    }
                    $term_shipping_obj = get_term_by('id', $shipping_class_id, 'product_shipping_class');

                    $raw_zones = $wpdb->get_results("SELECT zone_id, zone_name, zone_order FROM {$wpdb->prefix}woocommerce_shipping_zones order by zone_order ASC;");
                    $rest_world = new stdClass();
                    $rest_world->zone_id = '0';
                    $rest_world->zone_name = 'Rest of the World';
                    $rest_world->zone_order = '';
                    $raw_zones = array_merge($raw_zones, array($rest_world));
                    $methods = array();
                    foreach ($raw_zones as $raw_zone) {
                        $zone = new WC_Shipping_Zone($raw_zone);
                        $raw_methods_sql = "SELECT method_id, method_order, instance_id, is_enabled FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE zone_id = %d AND is_enabled = 1 order by method_order ASC;";
                        $raw_methods = $wpdb->get_results($wpdb->prepare($raw_methods_sql, $zone->get_id()));
                        foreach ($raw_methods as $raw_method) {
                            if ($raw_method->method_id == 'flat_rate') {
                                echo '<tr><td><h2>Shipping Zone : ' . $zone->get_zone_name() . '</h2></td></tr>';
                                $shipping_classes = WC()->shipping->get_shipping_classes();
                                foreach ($shipping_classes as $shipping_class) {
                                    if (!isset($shipping_class->term_id)) {
                                        continue;
                                    }
                                    if ($shipping_class->term_id != get_user_meta($vendor_user_id, 'shipping_class_id', true)) {
                                        continue;
                                    }
                                    $class = "class_cost_" . $shipping_class->term_id;
                                    $option_name = "woocommerce_" . $raw_method->method_id . "_" . $raw_method->instance_id . "_settings";
                                    $shipping_details = get_option($option_name);
                                    ?>
                                    <tr>
                                        <td><label><?php echo sprintf(__('Enter Shipping Cost for : "%s" Shipping Class', 'woocommerce'), esc_html($shipping_class->name)); ?></label></td>
                                    </tr>
                                    <tr>
                                        <td><input name="vendor_shipping_data[<?php echo $option_name . '_' . $class; ?>]" class="no_input" readonly type="text" step="0.01" value='<?php echo isset($shipping_details[$class]) ? $shipping_details[$class] : ''; ?>' /></td>
                                    </tr>
                                    <tr>
                                        <td class="hints">
                                            <div>
                                                <div class="aar"></div>
                                                <?php _e('Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>. Supports the following placeholders: <code>[qty]</code> = number of items, <code>[cost]</code> = cost of items,<br><code>[fee percent="10" min_fee="20"]</code> = Percentage based fee.', $WCMp->text_domain); ?> <br><br>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                        }
                    }

                    $class = "class_cost_" . $shipping_term_id;
                    $woocommerce_flat_rate_settings = get_option('woocommerce_flat_rate_settings');
                    if ($woocommerce_flat_rate_settings['enabled'] == 'yes') {
                        ?>
                        <tr><td><strong><?php _e('"Flat Rate (Legacy)" is deprecated in woocommerce 2.6.0 and will be removed in future versions - we recommend disabling it and instead setting up a new rate within your Shipping Zones.', $WCMp->text_domain); ?></strong></td></tr>
                        <tr>
                            <td><label><?php _e('Enter Shipping Amount for "Flat Rate (Legacy)" :', $WCMp->text_domain); ?></label></td>
                        </tr>
                        <tr>
                            <td><input name="vendor_shipping_data[shipping_amount]" class="no_input" readonly type="text" step="0.01" value='<?php echo isset($woocommerce_flat_rate_settings[$class]) ? $woocommerce_flat_rate_settings[$class] : ''; ?>' /></td>
                        </tr>
                        </tr>
                        <tr><td class="hints">
                                <div>
                                    <div class="aar"></div>
                                    <?php _e('Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>. Supports the following placeholders: <code>[qty]</code> = number of items, <code>[cost]</code> = cost of items,<br><code>[fee percent="10" min_fee="20"]</code> = Percentage based fee.', $WCMp->text_domain); ?> <br><br>
                                </div>
                            </td></tr>
                        <?php
                    }
                    $woocommerce_international_delivery_settings = get_option('woocommerce_international_delivery_settings');
                    if ($woocommerce_international_delivery_settings['enabled'] == 'yes') {
                        ?>
                        <tr><td><strong><?php _e('"International Flat Rate (Legacy)" is deprecated in woocommerce 2.6.0 and will be removed in future versions - we recommend disabling it and instead setting up a new rate within your Shipping Zones.', $WCMp->text_domain); ?></strong></td></tr>
                        <tr>
                            <td><label><?php _e('Enter Shipping Amount for "International Flat Rate (Legacy)" :', $WCMp->text_domain); ?></label></td>
                        </tr>
                        <tr>
                            <td><input class="no_input" readonly name="vendor_shipping_data[international_shipping_amount]" type="text" step="0.01" value='<?php echo isset($woocommerce_international_delivery_settings[$class]) ? $woocommerce_international_delivery_settings[$class] : ''; ?>' /></td>
                        </tr>
                        <tr><td class="hints">
                                <div>
                                    <div class="aar"></div>
                                    <?php _e('Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>. Supports the following placeholders: <code>[qty]</code> = number of items, <code>[cost]</code> = cost of items,<br><code>[fee percent="10" min_fee="20"]</code> = Percentage based fee.', $WCMp->text_domain); ?> <br><br>
                                </div></td></tr>
                        <?php
                    }
                } ?>					
                <?php do_action('wcmp_before_shipping_form_end_vendor_dashboard'); ?>

            </tbody>
        </table>
        <div class="action_div">
            <button class="wcmp_orange_btn" name="shipping_save">Save Options</button>
        </div>
        <div class="clear"></div>
    </form>
<?php endif; ?>