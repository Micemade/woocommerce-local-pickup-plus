<?php
/**
 * Create woo 'local-pickup-plus' Custom Post Type
 *
 * @return void
 */
class WCLPP_Locations_CPT {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'wclpp_post_type' ) );
		add_filter( 'manage_edit-local-pickup-plus_columns', array( $this, 'admin_columns' ) );
		add_filter( 'manage_local-pickup-plus_posts_custom_column', array( $this, 'admin_columns_content' ), 10, 2 );
		add_filter( 'enter_title_here', array( $this, 'admin_title_placeholder' ), 20, 2 );
		add_action( 'add_meta_boxes', array( $this, 'cpt_metabox' ) );
		add_action( 'save_post', array( $this, 'save_cpt' ) );
		add_filter( 'post_updated_messages', array( $this, 'cpt_update_messages' ) );
	}

	/**
	 * Register CPT 'local-pickup-plus' for locations.
	 *
	 * @return void
	 */
	public function wclpp_post_type() {
		$supports = array( 'title' );
		$labels   = array(
			'name'           => _x( 'Local Pickup Plus', 'plural', 'wc-local-pickup-plus' ),
			'singular_name'  => _x( 'Local Pickup Plus', 'singular', 'wc-local-pickup-plus' ),
			'menu_name'      => _x( 'Local Pickup Plus', 'admin menu', 'wc-local-pickup-plus' ),
			'name_admin_bar' => _x( 'Location', 'admin bar', 'wc-local-pickup-plus' ),
			'add_new'        => _x( 'Add New', 'add new', 'wc-local-pickup-plus' ),
			'add_new_item'   => __( 'Add New Location', 'wc-local-pickup-plus' ),
			'new_item'       => __( 'New Location', 'wc-local-pickup-plus' ),
			'edit_item'      => __( 'Edit Location', 'wc-local-pickup-plus' ),
			'view_item'      => __( 'View Location', 'wc-local-pickup-plus' ),
			'all_items'      => __( 'All Locations', 'wc-local-pickup-plus' ),
			'search_items'   => __( 'Search Locations', 'wc-local-pickup-plus' ),
			'not_found'      => __( 'No Location found.', 'wc-local-pickup-plus' ),
		);
		$args     = array(
			'supports'            => $supports,
			'labels'              => $labels,
			'public'              => false, // it's not public, it shouldn't have it's own permalink, and so on.
			'publicly_queryable'  => true, // you should be able to query it.
			'show_ui'             => true,
			'query_var'           => true,
			'rewrite'             => false,
			'has_archive'         => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => false,
			'hierarchical'        => false,
			'menu_icon'           => 'dashicons-location',
		);
		register_post_type( 'local-pickup-plus', $args );
	}

	/**
	 * CPT admin add column(s)
	 *
	 * @param array $columns - CPT admin columns.
	 * @return $new
	 */
	public function admin_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $value ) {
			if ( 'title' === $key ) {
				$new['local-pickup-plus-id'] = __( 'ID', 'wc-local-pickup-plus' );
			}
			$new[ $key ] = $value;
		}
		$new['checkout_visibility'] = __( 'Exclude in Checkout?', 'wc-local-pickup-plus' );
		return $new;
	}

	/**
	 * CPT admin column content.
	 *
	 * @param string $name - column name.
	 * @param int    $post_id - post id.
	 * @return void
	 */
	public function admin_columns_content( $name, $post_id ) {
		$exclude_store = (int) get_post_meta( $post_id, '_wclpp_exclude_location', true );
		switch ( $name ) {
			case 'local-pickup-plus-id':
				echo '<a href="' . esc_url( get_edit_post_link( $post_id ) ) . '">' . esc_html( $post_id ) . '</a>';
				break;
			case 'checkout_visibility':
				$visibility = ( 1 === $exclude_store ) ? __( 'Yes', 'wc-local-pickup-plus' ) : __( 'No', 'wc-local-pickup-plus' );
				echo esc_html( $visibility );
				break;
		}
	}

	/**
	 * CPT title.
	 *
	 * @param string $title - CPT title.
	 * @param object $post - post object.
	 * @return $title
	 */
	public function admin_title_placeholder( $title, $post ) {
		if ( 'local-pickup-plus' === $post->post_type ) {
			$my_title = __( 'Enter location title here', 'wc-local-pickup-plus' );
			return $my_title;
		}
		return $title;
	}

	/**
	 * Add CPT Meta box.
	 *
	 * @return void
	 */
	public function cpt_metabox() {
		add_meta_box( 'checkout-visibility', __( 'Location Visibility', 'wc-local-pickup-plus' ), array( $this, 'location_visibility' ), 'local-pickup-plus', 'side', 'high' );

		add_meta_box( 'store-fields', __( 'Location Details', 'wc-local-pickup-plus' ), array( $this, 'metabox_content' ), 'local-pickup-plus', 'normal', 'high' );
	}

	/**
	 * Add Meta box for location visibility.
	 *
	 * @param object $post - post object.
	 * @return void
	 */
	public function location_visibility( $post ) {
		// Display code/markup goes here. Don't forget to include nonces!
		$pid              = $post->ID;
		$exclude_location = get_post_meta( $pid, '_wclpp_exclude_location', true );
		// Add a nonce field so we can check for it later.
		wp_nonce_field( 'wclpp_location_save_content', 'wclpp_location_metabox_nonce' );
		?>
		<div class="container_data_metabox">
			<div class="sub_data_poker">
				<p><strong><?php esc_html_e( 'Exclude Location in checkout.', 'wc-local-pickup-plus' ); ?></strong></p>
				<input type="checkbox" name="_wclpp_exclude_location" class="form-control" <?php checked( $exclude_location, 1 ); ?> />		
			</div>
		</div>
		<?php
	}

	/**
	 * Add meta box content.
	 *
	 * @param object $post - the post object.
	 * @return void
	 */
	public function metabox_content( $post ) {
		$pid                        = $post->ID;
		$wclpp_city                 = get_post_meta( $pid, 'wclpp_city', true );
		$wclpp_phone                = get_post_meta( $pid, 'wclpp_phone', true );
		$wclpp_address              = get_post_meta( $pid, 'wclpp_address', true );
		$wclpp_shipping_cost        = get_post_meta( $pid, 'wclpp_shipping_cost', true );
		$wclpp_location_order_email = get_post_meta( $pid, 'wclpp_location_order_email', true );
		$wclpp_enable_order_email   = get_post_meta( $pid, 'wclpp_enable_order_email', true );
		$wclpp_ship_cost_category   = get_post_meta( $pid, 'wclpp_ship_cost_category', true );
		$wclpp_ship_cost_type       = get_post_meta( $pid, 'wclpp_ship_cost_type', true );
		?>
		<style>
		input.regular-text-cost {
			height: 28px;
			vertical-align: middle;
		}
		</style>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Cost/Discount', 'wc-local-pickup-plus' ); ?></th>
				<td>
					<select name='wclpp_ship_cost_category'>
						<option 
						<?php
						if ( $wclpp_ship_cost_category ) {
							if ( $wclpp_ship_cost_category == 'cost' ) {
								echo 'selected'; }
						}
						?>
						value="cost">Cost</option>
						<option 
						<?php
						if ( $wclpp_ship_cost_category ) {
							if ( $wclpp_ship_cost_category == 'discount' ) {
								echo 'selected'; }
						}
						?>
						value="discount">Discount</option>
					</select>

					<input type="text" name="wclpp_shipping_cost" size="5" placeholder="0" class="regular-text-cost" value="<?php echo $wclpp_shipping_cost; ?>">

					<select name='wclpp_ship_cost_type'>
						<option 
						<?php
						if ( $wclpp_ship_cost_type ) {
							if ( $wclpp_ship_cost_type == 'percentage' ) {
								echo 'selected'; }
						}
						?>
						value="percentage">%</option>
						<option 
						<?php
						if ( $wclpp_ship_cost_type ) {
							if ( $wclpp_ship_cost_type == 'value' ) {
								echo 'selected'; }
						}
						?>
						value="value"><?php esc_html_e( get_woocommerce_currency_symbol() ); ?></option>
					</select>
			</tr>
			<tr>
				<th><?php esc_html_e( 'City', 'wc-local-pickup-plus' ); ?></th>
				<td>
					<input type="text" name="wclpp_city" class="regular-text" value="<?php echo $wclpp_city; ?>">
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Phone', 'wc-local-pickup-plus' ); ?></th>
				<td>
					<input type="text" name="wclpp_phone" class="regular-text" value="<?php echo $wclpp_phone; ?>">
				</td>
			</tr>	
			<tr>
				<th><?php esc_html_e( 'Address', 'wc-local-pickup-plus' ); ?></th>
				<td>
					<?php
						$settings = array(
							'textarea_name' => 'wclpp_address',
							'editor_height' => 75,
						);
						wp_editor( $wclpp_address, 'wclpp_address', $settings );
						?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save the CPT.
	 *
	 * @param int $post_id - post id.
	 * @return void
	 */
	public function save_cpt( $post_id ) {
		// Check if our nonce is set.
		if ( ! isset( $_POST['wclpp_location_metabox_nonce'] ) ) {
			return; }
		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wclpp_location_metabox_nonce'], 'wclpp_location_save_content' ) ) {
			return; }
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return; }
		$checked             = isset( $_POST['_wclpp_exclude_location'] ) ? 1 : 0;
		$checked_order_email = isset( $_POST['wclpp_enable_order_email'] ) ? 1 : 0;
		update_post_meta( $post_id, '_wclpp_exclude_location', $checked );
		update_post_meta( $post_id, 'wclpp_city', sanitize_text_field( $_POST['wclpp_city'] ) );
		update_post_meta( $post_id, 'wclpp_phone', sanitize_text_field( $_POST['wclpp_phone'] ) );
		update_post_meta( $post_id, 'wclpp_address', wp_kses_data( $_POST['wclpp_address'] ) );
		update_post_meta( $post_id, 'wclpp_ship_cost_category', wp_kses_data( $_POST['wclpp_ship_cost_category'] ) );
		update_post_meta( $post_id, 'wclpp_ship_cost_type', wp_kses_data( $_POST['wclpp_ship_cost_type'] ) );
		update_post_meta( $post_id, 'wclpp_location_order_email', sanitize_text_field( $_POST['wclpp_location_order_email'] ) );
		update_post_meta( $post_id, 'wclpp_enable_order_email', $checked_order_email );
		if ( isset( $_POST['wclpp_shipping_cost'] ) ) {
			update_post_meta( $post_id, 'wclpp_shipping_cost', sanitize_text_field( $_POST['wclpp_shipping_cost'] ) );
		}
	}

	/**
	 * Messages after post update.
	 *
	 * @param array $messages - list of messages after post save.
	 * @return $messages
	 */
	public function cpt_update_messages( $messages ) {
		$post                         = get_post();
		$post_type                    = get_post_type( $post );
		$post_type_object             = get_post_type_object( $post_type );
		$messages['local-pickup-plus'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Location updated.', 'wc-local-pickup-plus' ),
			2  => __( 'Location updated.', 'wc-local-pickup-plus' ),
			3  => __( 'Location deleted.', 'wc-local-pickup-plus' ),
			4  => __( 'Location updated.', 'wc-local-pickup-plus' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Location restored to revision from %s', 'wc-local-pickup-plus' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Location published.', 'wc-local-pickup-plus' ),
			7  => __( 'Location saved.', 'wc-local-pickup-plus' ),
			8  => __( 'Location submitted.', 'wc-local-pickup-plus' ),
			9  => sprintf(
				// translators: Publish box date format, see http://php.net/date.
				__( 'Location scheduled for: <strong>%1$s</strong>.', 'wc-local-pickup-plus' ),
				date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Location draft updated.', 'wc-local-pickup-plus' ),
		);
		return $messages;
	}

}

/**
 * Start CPT creation class.
 *
 * @return class
 */
function wclpp_locations_cpt() {
	return new WCLPP_Locations_CPT();
}
wclpp_locations_cpt();
