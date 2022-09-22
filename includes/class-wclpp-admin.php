<?php
/**
 * Admin functions for WC Local Pickup Plus
 */
class WCLPP_Admin {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'show_store_in_admin' ) );
		add_filter( 'plugin_action_links_' . WCLPP_PLUGIN_FILE, array( $this, 'settings_links' ) );
		add_action( 'admin_menu', array( $this, 'admin_submenu' ) );
	}

	/**
	 * Add selected store to billing details, admin page.
	 *
	 * @param object $order - order object.
	 * @return void
	 */
	public function show_store_in_admin( $order ) {
		$order_id = $order->get_id();
		if ( ! empty( $order_id ) ) {
			$location_name     = sanitize_text_field( get_post_meta( $order_id, '_wclpp_pickup_location_name', true ) );
			$wclpp_pickup_date = sanitize_text_field( get_post_meta( $order_id, '_wclpp_pickup_date', true ) );
			$location_id       = sanitize_text_field( get_post_meta( $order_id, '_wclpp_pickup_location_id', true ) );
			$location_address  = get_the_title( $location_id );
		}

		if ( ! empty( $location_name ) ) :
			?>
			<p>
				<strong class="title"><?php esc_html_e( 'Pickup Location', 'wc-local-pickup-plus' ) . ':'; ?></strong>
				<span class="data"><?php echo esc_html( $location_name ); ?></span>
			</p>
			<?php
		endif;
		if ( ! empty( $wclpp_pickup_date ) ) :
			?>
			<p>
				<strong class="title"><?php esc_html_e( 'Pickup Date', 'wc-local-pickup-plus' ) . ':'; ?></strong>
				<span class="data"><?php echo esc_html( $wclpp_pickup_date ); ?></span>
			</p>
			<?php
		endif;
	}

	/**
	 * Add Settingss action links
	 *
	 * @param array $links - array of links.
	 * @return $merged_links
	 */
	public function settings_links( $links ) {
		$id           = 'wclpp-shipping-method';
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=' . $id ) . '">' . esc_html__( 'Settings', 'wc-local-pickup-plus' ) . '</a>',
		);

		// Merge our new link with the default ones.
		$merged_links = array_merge( $plugin_links, $links );
		return $merged_links;
	}

	/**
	 * Add Settings links to custom post
	 *
	 * @return void
	 */
	public function admin_submenu() {
		$id = 'wclpp-shipping-method';
		add_submenu_page(
			'edit.php?post_type=local-pickup-plus',
			__( 'Settings', 'wc-local-pickup-plus' ),
			__( 'Settings', 'wc-local-pickup-plus' ),
			'edit_posts',
			'admin.php?page=wc-settings&tab=shipping&section=' . $id
		);
	}

}

/**
 * Start admin class.
 *
 * @return class
 */
function wclpp_admin() {
	return new WCLPP_Admin();
}
wclpp_admin();
