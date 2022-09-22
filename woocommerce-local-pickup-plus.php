<?php
/*
Plugin Name: WooCommerce Local Pickup Plus
Description: The WooCommerce Local Pickup Plus plugin is shipping method for WooCommerce allows your customers to pick up their purchased products at the store location(s).
Version:     1.0.0
Author:      Micemade
Author URI:  http://www.micemade.com/
Text Domain: wc-local-pickup-plus
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Version of the plugin.
if ( ! defined( 'WC_LOCAL_PICKUP_PLUS_VERSION' ) ) {
	define( 'WC_LOCAL_PICKUP_PLUS_VERSION', '1.0.0' );
}
// Directory of the plugin.
if ( ! defined( 'WC_LOCAL_PICKUP_PLUS_DIR' ) ) {
	define( 'WC_LOCAL_PICKUP_PLUS_DIR', plugin_dir_path( __FILE__ ) );
}
// Directory of the plugin.
if ( ! defined( 'WC_LOCAL_PICKUP_PLUS_URL' ) ) {
	define( 'WC_LOCAL_PICKUP_PLUS_URL', plugin_dir_url( __FILE__ ) );
}
// Plugin file.
if ( ! defined( 'WCLPP_PLUGIN_FILE' ) ) {
	define( 'WCLPP_PLUGIN_FILE', plugin_basename( __FILE__ ) );
}

/**
 * Load all files and classes.
 * depending on if WooCommerce is active.
 * @return void
 */
function wclpp_load() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		require WC_LOCAL_PICKUP_PLUS_DIR . '/includes/class-wclpp-post-type.php';
		require WC_LOCAL_PICKUP_PLUS_DIR . '/includes/class-wclpp-shipping-method.php';
		require WC_LOCAL_PICKUP_PLUS_DIR . '/includes/class-wclpp-checkout.php';
		require WC_LOCAL_PICKUP_PLUS_DIR . '/includes/class-wclpp-data.php';
		require WC_LOCAL_PICKUP_PLUS_DIR . '/includes/class-wclpp-admin.php';
	} else {
		add_action( 'admin_notices', 'wclpp_woocommerce_missing_wc_notice' );
		return;
	}
}
add_action( 'plugins_loaded', 'wclpp_load', 999 );

if ( ! function_exists( 'wclpp_woocommerce_missing_wc_notice' ) ) {
	/**
	 * WooCommerce not active notice.
	 *
	 * @return void
	 */
	function wclpp_woocommerce_missing_wc_notice() {
		// Translators: the link to WooCommerce site.
		echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Local Pickup Plus for WooCommerce requires WooCommerce to be installed and active. You can download %s here.', 'wc-local-pickup-plus' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
	}
}

