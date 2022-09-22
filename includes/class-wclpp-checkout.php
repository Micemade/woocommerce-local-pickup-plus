<?php
/**
 * Class for checkout local pickup.
 */
class WCLPP_Checkout {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'woocommerce_cart_totals_before_order_total', array( $this, 'checkout_table_row_locations' ) );
		//add_action( 'woocommerce_after_shipping_calculator', array( $this, 'checkout_table_row_locations' ) );
		add_action( 'woocommerce_review_order_after_shipping', array( $this, 'checkout_table_row_locations' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'location_validate_checkout' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_pickup_shipping_to_cart' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'location_save_order_meta' ) );
		add_filter( 'woocommerce_email_order_meta_keys', array( $this, 'update_order_email' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'order_details' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
	}

	/**
	 * Create row with locations select.
	 *
	 * @return void
	 */
	public function checkout_table_row_locations() {

		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;
		if ( 'yes' === WCLPP()->settings['enabled'] ) {
			$wclpp_chosen_shipping = $this->get_chosen_shipping_method();
			$post_data             = $this->validate_ajax_request();
			if ( $wclpp_chosen_shipping === WCLPP()->id && is_cart() ) {
				?>
				<script>
					jQuery('.woocommerce-shipping-destination, .woocommerce-shipping-calculator').css( 'display', 'none' );
				</script>
				<?php
			}

			// echo '<pre class="mm-debug">';
			// print_r( WC()->session->get( 'wclpp_pickup_id' ) );
			// echo '</pre>';

			if (
				$wclpp_chosen_shipping === WCLPP()->id && is_checkout() ||
				$wclpp_chosen_shipping === WCLPP()->id && is_cart()
				) {
				$pickup_store = ! empty( $post_data['wclpp-pickup-location'] ) ? $post_data['wclpp-pickup-location'] : '';
				if ( empty( $pickup_store ) ) {
					$wclpp_pick_id = WC()->session->get( 'wclpp_pickup_id' );
					if ( $wclpp_pick_id ) {
						$pickup_store = $wclpp_pick_id;
					}
				}
				if ( empty( $pickup_store ) ) {
					$pickup_store = get_user_meta( $user_id, '_wclpp_pickup_location_id', true );
				}
				if ( ! empty( $pickup_store ) ) {
					WC()->session->set( 'wclpp_pickup_id', $pickup_store );
				}
				?>
				<tr class="shipping-pickup-store">

					<?php echo apply_filters( 'wclpp_locations_row_start', $this->inner_wrap_start() ); // WPCS: xss ok. ?>

						<select class='wclpp-pickup-location-select' name='wclpp-pickup-location'>
							<option value="" class='wclpp-pickup-null' 
								<?php
								if ( $pickup_store ) {
									echo 'disabled';
								}
								?>
								>
								<?php esc_html_e( 'Select a store location', 'wc-local-pickup-plus' ); ?>
							</option>
							<?php
							$wclpp_data = new WCLPP_Data();
							foreach ( $wclpp_data->get_store_locations( true ) as $post_id => $store ) {
									$pickup_ship_cost = get_post_meta( $post_id, 'wclpp_shipping_cost', true );
								if ( empty( $pickup_ship_cost ) ) {
									$pickup_ship_cost == 0; }
									$exclude_location = (int) get_post_meta( $post_id, '_wclpp_exclude_location', true );
									$selected         = $post_id == $pickup_store ? ' selected="selected"' : '';
								if ( 0 === $exclude_location ) :
									echo '<option data-cost="' . esc_attr( $pickup_ship_cost ) . '" value="' . esc_attr( $post_id ) . '" ' . esc_attr( $selected ) . ' data-address="' . esc_attr( $wclpp_data->get_location_address( $post_id ) ) . '">' . esc_html( $store ) . '</option>';
									endif;
							}
							?>
						</select>

						<?php if ( ! empty( $pickup_store ) ) { ?>
							<?php if ( empty( $post_data['wclpp-pickup-location'] ) ) { ?>
							<script>
								jQuery(document).ready(function($){
									$(document.body).trigger('update_checkout');
								});
							</script>
							<?php } ?>

						<div class='wclpp-pickup-info'>
							<span class="label-address"><?php echo esc_html( apply_filters( 'wclpp_locations_label_address', __( 'Address:', 'wc-local-pickup-plus' ) ) ); ?></span>
							<span class="content-address"><?php echo wp_kses_post( $wclpp_data->get_location_address( $pickup_store ) ); ?></span>
						</div>

							<?php if ( 'yes' === WCLPP()->settings['scheduler'] ) { ?>
								<div class='wclpp-pickup-date'>
									<span class='label-date'><?php echo esc_html( apply_filters( 'wclpp_locations_label_date', __( 'Schedule a pickup date', 'wc-local-pickup-plus' ) ) ); ?></span>
									<input readonly type='text' id='pickupdate' name='wclpp-pickup-date'>
								</div>

								<script>
								jQuery(document).ready(function($){
									var dates = $("#pickupdate").datepicker({
									minDate: "0",
									maxDate: "+2Y",
									defaultDate: "+1w",
									dateFormat: 'dd.mm.yy',
									numberOfMonths: 1,
									onSelect: function(date) {
											for(var i = 0; i < dates.length; ++i) {
											if(dates[i].id < this.id)
												$(dates[i]).datepicker('option', 'maxDate', date);
											else if(dates[i].id > this.id)
												$(dates[i]).datepicker('option', 'minDate', date);
											}
										}
									});
								});
								</script>
							<?php } // end if scheduler (calendar). ?>

						<?php } // end if !empty pickup_store. ?>

						<?php echo apply_filters( 'wclpp_locations_row_end', $this->inner_wrap_end() ); // WPCS: xss ok. ?>

					</td>
				</tr>

				<script>
					jQuery(document).ready(function($){
						if( typeof document.body === 'undefined' ) {
							return;
						}

						$( '.wclpp-pickup-location-select' ).on( 'change', function() {
							var loc_id  = this.value,
								trigger = "<?php echo is_checkout() ? 'update_checkout' : 'updated_wc_div'; ?>";

							if( loc_id ){
								$(document.body).trigger( trigger );
							}
							console.log(trigger);
						});
					});
				</script>

				<?php
			} // end if $wclpp_chosen_shipping === WCLPP()->id && is_checkout() ||$wclpp_chosen_shipping === WCLPP()->id && is_cart()
		}
	}

	/**
	 * Pickup Validation
	 *
	 * @return void
	 */
	public function location_validate_checkout() {
		if ( isset( $_POST['wclpp-pickup-location'] ) && empty( $_POST['wclpp-pickup-location'] ) ) {
			wc_add_notice( __( 'You must either choose a store or use other shipping method', 'wc-local-pickup-plus' ), 'error' );
		}

		if ( isset( $_POST['wclpp-pickup-date'] ) && empty( $_POST['wclpp-pickup-date'] ) ) {
			wc_add_notice( __( 'You must choose a pickup date', 'wc-local-pickup-plus' ), 'error' );
		}
	}

	/**
	 * Add store shipping cost to cart amount
	 *
	 * @param object $cart - the cart object.
	 * @return void
	 */
	public function add_pickup_shipping_to_cart( $cart ) {
		global $woocommerce;
		$post_data        = $this->validate_ajax_request();
		$chosen_shipping  = $this->get_chosen_shipping_method();
		$pickup_ship_cost = 0;
		if ( ! empty( $post_data['wclpp-pickup-location'] ) ) :
			$pickup_ship_cost = get_post_meta( $post_data['wclpp-pickup-location'], 'wclpp_shipping_cost', true );
		endif;
		if ( ! empty( $post_data['wclpp-pickup-location'] ) ) :
			$pickup_ship_title = get_the_title( $post_data['wclpp-pickup-location'] );
		endif;
		if ( isset( $pickup_ship_cost ) && $pickup_ship_cost > 0 && $chosen_shipping === WCLPP()->id ) :
			$amount         = $pickup_ship_cost;
			$ship_cost_cat  = get_post_meta( $post_data['wclpp-pickup-location'], 'wclpp_ship_cost_category', true );
			$ship_cost_type = get_post_meta( $post_data['wclpp-pickup-location'], 'wclpp_ship_cost_type', true );
			if ( 'cost' === $ship_cost_cat ) :
				if ( 'value' === $ship_cost_type ) :
					$woocommerce->cart->add_fee(
						apply_filters( 'wclpp_store_pickup_cost_label', sprintf( __( 'Pickup Location Cost', 'wc-local-pickup-plus' ) ) ),
						$amount,
						true,
						''
					);
				endif;
				if ( 'percentage' === $ship_cost_type ) :
					$percent   = $amount;
					$surcharge = ( $cart->cart_contents_total + $cart->shipping_total ) * $percent / 100;
					$cart->add_fee( __( 'Pickup Location Cost', 'wc-local-pickup-plus' ) . " ($percent%)", $surcharge, false );
				endif;
			endif;
			if ( 'discount' === $ship_cost_cat ) :
				if ( 'value' === $ship_cost_type ) :
					$woocommerce->cart->add_fee(
						apply_filters( 'wclpp_store_pickup_cost_label', sprintf( __( 'Pickup Location Discount', 'wc-local-pickup-plus' ) ) ),
						-$amount,
						true,
						''
					);
				endif;
				if ( 'percentage' === $ship_cost_type ) :
					$percent   = $amount;
					$surcharge = ( $cart->cart_contents_total + $cart->shipping_total ) * $percent / 100;
					$cart->add_fee( __( 'Pickup Location Discount', 'wc-local-pickup-plus' ) . " ($percent%)", -$surcharge, false );
				endif;
			endif;
		endif;
	}

	/**
	 * Save the custom field.
	 *
	 * @param string $order_id - order number.
	 * @return void
	 */
	public function location_save_order_meta( $order_id ) {
		$current_user = wp_get_current_user();
		$user_id      = $current_user->ID;

		if ( isset( $_POST['wclpp-pickup-location'] ) && ! empty( $_POST['wclpp-pickup-location'] ) ) {

			$pickup_location_id   = sanitize_text_field( $_POST['wclpp-pickup-location'] );
			$pickup_location_name = get_the_title( $pickup_location_id );

			update_post_meta( $order_id, '_wclpp_pickup_location_id', $pickup_location_id );
			update_user_meta( $user_id, '_wclpp_pickup_location_id', $pickup_location_id );

			if ( $pickup_location_name ) {
				update_post_meta( $order_id, '_wclpp_pickup_location_name', $pickup_location_name );
				update_user_meta( $user_id, '_wclpp_pickup_location_name', $pickup_location_name );
			}

			if ( isset( $_POST['wclpp-pickup-date'] ) && ! empty( $_POST['wclpp-pickup-date'] ) ) {
				$pickup_date = sanitize_text_field( $_POST['wclpp-pickup-date'] );
				update_post_meta( $order_id, '_wclpp_pickup_date', $pickup_date );
				update_user_meta( $user_id, '_wclpp_pickup_date', $pickup_date );
			}
		}

	}

	/**
	 * Add local pickup location field to order emails
	 *
	 * @param array $keys - array keys.
	 * @return $keys
	 */
	public function update_order_email( $keys ) {
		$location = __( 'Pickup Location', 'wc-local-pickup-plus' );
		$date     = __( 'Pickup Date', 'wc-local-pickup-plus' );
		// Email fields.
		$keys[ $location ] = '_wclpp_pickup_location_name';
		$keys[ $date ]     = '_wclpp_pickup_date';
		return $keys;
	}

	/**
	 * Add local pickup location detail on order confirmation page
	 *
	 * @param object $order - object with order details.
	 * @return void
	 */
	public function order_details( $order ) {
		$order = new WC_Order( $order->get_id() );

		$meta   = get_post_meta( $order->get_id() );
		$loc_id = isset( $meta['_wclpp_pickup_location_id'] ) ? $meta['_wclpp_pickup_location_id'][0] : '';

		if ( $loc_id ) {
			echo '<h2>' . esc_html__( 'Pickup Details', 'wc-local-pickup-plus' ) . '</h2>';
			echo '<p>' . esc_html( get_the_title( $loc_id ) ) . '<br>' . wp_kses_post( get_post_meta( $loc_id, 'wclpp_address', true ) ) . '</p>';
		}
	}

	/**
	 * Validate ajax request
	 *
	 * @return void
	 */
	public function validate_ajax_request() {
		if ( ! $_POST || ( is_admin() && ! is_ajax() ) ) {
			return;
		}
		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $post_data );
			return $post_data;
		} else {
			return $_POST;
		}
	}

	/**
	 * Add CSS/JS
	 *
	 * @return void
	 */
	public function enqueue_scripts_styles() {
		if ( is_checkout() ) {
			wp_register_script( 'wclpp-js', WC_LOCAL_PICKUP_PLUS_URL . 'assets/js/pickup.min.js', array(), '1.0.0', true );
			wp_enqueue_script( 'wclpp-js' );
			wp_enqueue_style( 'wclpp-styles', WC_LOCAL_PICKUP_PLUS_URL . 'assets/css/pickup-style.min.css' );
			wp_enqueue_style( 'wclpp-jquery-ui-css', WC_LOCAL_PICKUP_PLUS_URL . 'assets/css/jquery-ui.css' );
			wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery' ) );
		}
	}

	/**
	 * Get choosen shipping method
	 *
	 * @return $chosen_methods
	 */
	private function get_chosen_shipping_method() {
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
		return $chosen_methods[0];
	}

	/**
	 * Checkout row content wrap start
	 */
	private function inner_wrap_start() {
		return '<th><strong>' . esc_html__( 'Local pickup', 'wc-local-pickup-plus' ) . '</strong></th>
	<td>';
	}

	/**
	 * Checkout row content wrap end
	 */
	private function inner_wrap_end() {
		return '</td>';
	}

}

/**
 * Start checkout class.
 *
 * @return class
 */
function wclpp_checkout() {
	return new WCLPP_Checkout();
}
wclpp_checkout();
