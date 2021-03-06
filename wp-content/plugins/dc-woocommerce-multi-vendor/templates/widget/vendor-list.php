<?php
/**
 * The template for displaying demo plugin content.
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/widget/vendor-list.php
 *
 * @author 		WC Marketplace
 * @package 	dc-product-vendor/Templates
 * @version     0.0.1
 */

global $WCMp;

$vendor_count = count($vendors);
if($vendor_count > 5 )	{ ?>
	<div style="height: 308px; overflow-y: scroll; width: 226px;" >
<?php } else {?>
<div style=" height: auto; width: 226px;" >
<?php }
if($vendors) {
	foreach($vendors as $vendors_key => $vendor) { 
		if(!$vendor->image) $vendor->image = $WCMp->plugin_url . 'assets/images/WP-stdavatar.png';
		?>
		<div style=" width: 100%; margin-bottom: 5px; clear: both; display: block;">
			<div style=" width: 25%;  display: inline;">		
			<img width="50" height="50" class="vendor_img" style="display: inline;" src=<?php echo $vendor->image ?> id="vendor_image_display">
			</div>
			<div style=" width: 75%;  display: inline;  padding: 10px;">
					<a href="<?php echo esc_attr( $vendor->permalink ); ?>">
						<?php echo $vendor->user_data->display_name; ?>
					</a>
			</div>
		</div>
	<?php } 
}?>
</div>