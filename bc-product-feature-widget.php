<?php
/*
Plugin Name: BigCommerce Product Feature
Description: Creates a widget which allows the site owner to render a product, optionally with image, title, description, and read more button.
Author: Topher
Author URI: http://topher1kenobe.com
Version: 1.1
Text Domain: bc-product-feature-widget
License: GPL
*/

/**
 * Provides a WordPress widget that renders a specified BigCommerce product
 *
 * @package BC_Product_Feature
 * @since   BC_Product_Feature 1.0
 * @author  Topher
 */

/**
 * Adds BC_Product_Feature widget.
 *
 * @class   BC_Product_Feature
 * @version 1.0.0
 * @since   1.0
 * @package BC_Product_Feature
 * @author  Topher
 */
class BC_Product_Feature extends WP_Widget {

	/**
	* Holds the data retrieved from the database
	*
	* @access private
	* @since  1.0
	* @var    object
	*/
	private $bcpf_data = null;

	/**
	* BC_Product_Feature Constructor, sets up Widget
	*
	* @access public
	* @since  1.0
	* @return void
	*/
	public function __construct() {

		//  Build out the widget details
		parent::__construct(
			'bc-product-feature-widget',
			__( 'BigCommerce Product Feature', 'bc-product-feature-widget' ),
			array( 'description' => __( 'Renders a specified BigCommerce product.', 'bc-product-feature-widget' ), )
		);

        $this->data_fetcher();

	}

	/**
	* Data fetcher
	*
	* Runs at instantiation, gets array of products using WP_Query
	*
	* @access private
	* @since  1.0
	* @return void
	*/
	private function data_fetcher() {

        $args = array (
            'post_type'      => 'bigcommerce_product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        // The Query
        $products = get_posts( $args );

		// store the data in an attribute
		$this->bcpf_data = $products;

	}

	/**
	* Data render
	*
	* Parse the data in $this->bcpf_data and turn it into HTML for front end rendering
	*
	* @access private
	* @since  1.0
	* @return string
	*/
	private function data_render( $instance = '' ) {

		// instantiate $output
		$output = '';

        // make sure we have a post_id, then get data
        if ( ! empty( $instance['bcpf-product-id'] ) ) {

            $output     = '';
            $post_id    = $instance['bcpf-product-id'];
            $product_id = get_post_meta( $post_id, 'bigcommerce_id', true );
            
            // make sure we really want things and then print them
            if ( ! empty( $instance['bcpf-show-image'] ) ) {
                $output .= do_shortcode( '[bc-component id="' . $product_id . '" type="image"]' );
            }
            if ( ! empty( $instance['bcpf-show-title'] ) ) {
                $output .= do_shortcode( '[bc-component id="' . $product_id . '" type="title"]' );
            }
            if ( ! empty( $instance['bcpf-show-description'] ) ) {
                $output .= do_shortcode( '[bc-component id="' . $product_id . '" type="description"]' );
            }
            if ( ! empty( $instance['bcpf-show-readmore'] ) ) {
                $output .= '<a class="bc-btn bc-btn--view-product" href="' . get_permalink( $post_id ) . '">' . __( 'Read More', 'bc-product-feature-widget' ) . '</a>' . "\n";
            }

        }

		return $output;
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see   WP_Widget::widget()
	 *
	 * @param array $args	  Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {

		// instantiate $output
		$output = '';

		// echo the before_widget html
		echo wp_kses_post( $args['before_widget'] );

		// filter the title
		$title	= apply_filters( 'widget_title', $instance['title'] );

		// go get the primary data
		$output .= $this->data_render( $instance );

        // optionally print the widget title
		if ( ! empty( $title ) ) {
			echo wp_kses_post( $args['before_title'] ) . esc_html( $title ) . wp_kses_post( $args['after_title'] );
		}

		// echo the widget content
		echo wp_kses_post( $output );

		// echo the after_widget html
		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Back-end widget form. Asks for: widget title via textfield, product via dropdown, checkbox choice to render product image, product title, products description, and Read More button
	 *
	 * @see   WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

		// check to see if we have a title, and if so, set it
		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = '';
		}

		// make the form for the title field in the admin
		?>
        <p>
            Choose a product and select the attributes you'd like to show in the widget.
        </p>

		<h4>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Widget Title:', 'bc-product-feature-widget' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</h4>

		<h4>

            <?php
            // make sure we have results
            if ( count( $this->bcpf_data ) > 0 ) {
                echo '<select name="' . esc_attr( $this->get_field_name( 'bcpf-product-id' ) ) . '" style="max-width: 300px;">' . "\n";
                echo '<option value="">' . __( 'Choose a Product', 'wp-featured-products' ) . '</option>' . "\n";
                foreach ( $this->bcpf_data as $key => $product ) {
                    echo '<option value="' . absint( $product->ID ) . '"' . selected( $instance['bcpf-product-id'], $product->ID, false ) . '>' . esc_html( $product->post_title ) . '</option>' . "\n";
                }
                echo '</select>' . "\n";
            } else {
                echo '<p>';
                esc_html_e( 'No products found, ', 'wp-featured-products' );
                echo '</p>';
            }
            ?>
		</h4>

		<h4><?php _e( 'Show', 'bc-product-feature-widget' ); ?>:</h4>
		<ul>
			<li>
				<input id="<?php echo $this->get_field_id( 'bcpf-show-image' ); ?>" name="<?php echo $this->get_field_name( 'bcpf-show-image' ); ?>" type="checkbox" value="1" <?php checked( '1', $instance['bcpf-show-image'], true ); ?>>
				<label for="<?php echo $this->get_field_id( 'bcpf-show-image' ); ?>"> <?php _e( 'Image', 'bc-product-feature-widget' ); ?></label>
			</li>
			<li>
				<input id="<?php echo $this->get_field_id( 'bcpf-show-title' ); ?>" name="<?php echo $this->get_field_name( 'bcpf-show-title' ); ?>" type="checkbox" value="1" <?php checked( '1', $instance['bcpf-show-title'], true ); ?>>
				<label for="<?php echo $this->get_field_id( 'bcpf-show-title' ); ?>"> <?php _e( 'Product Title', 'bc-product-feature-widget' ); ?></label>
			</li>
			<li>
				<input id="<?php echo $this->get_field_id( 'bcpf-show-description' ); ?>" name="<?php echo $this->get_field_name( 'bcpf-show-description' ); ?>" type="checkbox" value="1" <?php checked( '1', $instance['bcpf-show-description'], true ); ?>>
				<label for="<?php echo $this->get_field_id( 'bcpf-show-description' ); ?>"> <?php _e( 'Description', 'bc-product-feature-widget' ); ?></label>
			</li>
			<li>
				<input id="<?php echo $this->get_field_id( 'bcpf-show-readmore' ); ?>" name="<?php echo $this->get_field_name( 'bcpf-show-readmore' ); ?>" type="checkbox" value="1" <?php checked( '1', $instance['bcpf-show-readmore'], true ); ?>>
				<label for="<?php echo $this->get_field_id( 'bcpf-show-readmore' ); ?>"> <?php _e( 'Read More button', 'bc-product-feature-widget' ); ?></label>
			</li>

		</ul>
		
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see    WP_Widget::update()
	 *
	 * @param  array $new_instance Values just sent to be saved.
	 * @param  array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {

		// set up current instance to hold old_instance data
		$instance = $old_instance;

		// set instance to hold new instance data
		$instance['title']                  = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['bcpf-product-id']        = absint( $new_instance['bcpf-product-id'] );
		$instance['bcpf-show-image']        = absint( $new_instance['bcpf-show-image'] );
		$instance['bcpf-show-title']        = absint( $new_instance['bcpf-show-title'] );
		$instance['bcpf-show-description']  = absint( $new_instance['bcpf-show-description'] );
		$instance['bcpf-show-readmore']     = absint( $new_instance['bcpf-show-readmore'] );

		return $instance;
	}

} // class BC_Product_Feature


// register BC_Product_Feature widget
function register_bcpf_recent_essays_widget() {
	register_widget( 'BC_Product_Feature' );
}
add_action( 'widgets_init', 'register_bcpf_recent_essays_widget' );
