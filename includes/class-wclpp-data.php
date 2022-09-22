<?php
/**
 * Data Gathering function
 *
 * @param boolean $return_id - check fpr return id.
 * @return $location
 */
class WCLPP_Data {

	/**
	 * If to return ID.
	 *
	 * @var boolean
	 */
	public $return_id;

	/**
	 * Post ID
	 *
	 * @var integer
	 */
	public $post_id;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param boolean $return_id - if return_id exists.
	 * @param string  $post_id - local-pickup-plus post ID.
	 */
	public function __construct( $return_id = false, $post_id = '' ) {

		$this->get_store_locations( $return_id );
		$this->get_location_address( $post_id );
	}

	/**
	 * Getting those store locations
	 *
	 * @param boolean $return_id - if return_id exists.
	 * @return $location
	 */
	public function get_store_locations( $return_id = false ) {
		$location = array();
		$args     = array(
			'post_type'      => 'local-pickup-plus',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_exclude_store',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'   => '_exclude_store',
					'value' => '0',
				),
			),
		);
		$query    = new WP_Query( $args );
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) :
				$query->the_post();
				if ( ! $return_id ) {
					$location[ $query->post->post_title ] = $query->post->post_title;
				} else {
					$location[ get_the_ID() ] = $query->post->post_title;
				}
			endwhile;
			wp_reset_postdata();
		}
		return $location;
	}

	/**
	 * Get location address.
	 *
	 * @param int $post_id - post id.
	 * @return $address
	 */
	public function get_location_address( $post_id ) {
		$address = '';
		if ( $post_id ) {
			if ( get_post_meta( $post_id, 'wclpp_address', true ) ) {
				$address .= ' ' . get_post_meta( $post_id, 'wclpp_address', true );
			}
		}
		return $address;
	}
}
