<?php
/**
 * Add new shipping method*
 *
 */
class WC_WCLPP_Shipping_Method extends WC_Shipping_Method {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->id                 = 'wclpp-shipping-method'; // Id for your shipping method. Should be uunique.
		$this->method_title       = __( 'Local Pickup Plus' ); // Title shown in admin.
		$this->method_description = __( 'Local Pickup Plus Method' ); // Description shown in admin.
		$this->enabled            = 'yes'; // This can be added as an setting but for this example its forced enabled.
		$this->title              = 'Local Pickup Plus'; // This can be added as an setting but for this example its forced.
		$this->init();
	}

	/**
	 * Initialize shipping method class.
	 *
	 * @return void
	 */
	public function init() {
		// Load the settings API.
		$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings.
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.
		$this->cost  = $this->get_option( 'cost' );
		$this->title = $this->get_option( 'title' );
		// Save settings in admin if you have any defined.
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Shipping method setting fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'wc-local-pickup-plus' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Local Pickup Plus', 'wc-local-pickup-plus' ),
				'default' => 'yes',
			),
			'title'   => array(
				'title'       => __( 'Title', 'wc-local-pickup-plus' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wc-local-pickup-plus' ),
				'default'     => __( 'Local Pickup', 'wc-local-pickup-plus' ),
			),
			'scheduler'   => array(
				'title'       => __( 'Include scheduler', 'wc-local-pickup-plus' ),
				'type'        => 'checkbox',
				'description' => __( 'if date scheduler should be included and required.', 'wc-local-pickup-plus' ),
				'default'     => 'yes',
			),
		);
	}

	/**
	 * Check availalilty.
	 *
	 * @param array $package - array of packages.
	 * @return boolean
	 */
	public function is_available( $package ) {
		$is_available = ( 'yes' === $this->settings['enabled'] ) ? true : false;
		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this );
	}

	/**
	 * Calculate shipping.
	 *
	 * @param array $package - array of packages.
	 * @return void
	 */
	public function calculate_shipping( $package = array() ) {
		$rate = array(
			'id'       => $this->id,
			'label'    => $this->title,
			'cost'     => ( ! empty( $this->costs ) && $this->costs_per_store != 'yes' ) ? apply_filters( 'wps_shipping_costs', $this->costs ) : 0,
			'package'  => $package,
			'calc_tax' => 'per_order',
		);
		// Register the rate.
		$this->add_rate( $rate );
	}

}

/**
 * Shipping method register.
 *
 * @param array $methods - array of shipping methods.
 * @return $methods
 */
function wclpp_shipping_method( $methods ) {
	$methods['local_pickup_plus'] = 'WC_WCLPP_Shipping_Method';
	return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'wclpp_shipping_method' );

/**
 * WCLPP Class.
 *
 * @return class.
 */
function WCLPP() {
	return new WC_WCLPP_Shipping_Method();
}
