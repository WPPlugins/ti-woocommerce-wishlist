<?php
/**
 * The Template for displaying wishlist templates overrides status on WooCOmmerce System Status page.
 *
 * @since             1.2.0
 * @package           TInvWishlist\Admin\Template
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
?>

<table class="wc_status_table widefat" cellspacing="0">
    <thead>
	<tr>
	    <th colspan="3" data-export-label="<?php _e( 'TI WooCommerce Wishlist Templates', 'ti-woocommerce-wishlist' ); ?>"><h2><?php _e( 'TI WooCommerce Wishlist Templates', 'ti-woocommerce-wishlist' ); ?><?php echo wc_help_tip( __( 'This section shows any files that are overriding the default TI WooCommerce Wishlist template pages.', 'woocommerce' ) ); ?></h2></th>
	</tr>
    </thead>
    <tbody>
	<?php
	if ( $found_files ) {
	    ?>
    	<tr>
    	    <td data-export-label="<?php _e( 'Overrides', 'ti-woocommerce-wishlist' ); ?>"><?php _e( 'Overrides', 'ti-woocommerce-wishlist' ); ?></td>
    	    <td class="help">&nbsp;</td>
    	    <td><?php echo implode( ', <br/>', $found_files ); ?></td>
    	</tr>
	<?php } else {
	    ?>
    	<tr>
    	    <td data-export-label="<?php _e( 'Overrides', 'ti-woocommerce-wishlist' ); ?>"><?php _e( 'Overrides', 'ti-woocommerce-wishlist' ); ?>:</td>
    	    <td class="help">&nbsp;</td>
    	    <td>&ndash;</td>
    	</tr>
	<?php }
	?>
    </tbody>
</table>