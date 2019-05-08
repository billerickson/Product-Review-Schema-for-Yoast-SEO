<?php
/**
 * Plugin Name: Product Review Schema for Yoast SEO
 * Description: Adds additional schema data to articles you've marked as product reviews. Requires Yoast SEO
 * Version:     1.0
 * Author:      Bill Erickson
 * Author URI:  http://www.billerickson.net
 * License:     MIT
 * License URI: http://www.opensource.org/licenses/mit-license.php
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main BE_Product_Review_Schema class
 *
 * @since 1.0.0
 * @package BE_Product_Review_Schema
 */
class BE_Product_Review_Schema {

	/**
	 * Primary constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		load_plugin_textdomain( 'be-product-review-schema-for-yoast-seo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		add_filter( 'wpseo_schema_graph_pieces', array( $this, 'schema_piece' ) , 20, 2 );

		if( apply_filters( 'be_product_review_schema_metabox', true ) ) {
			add_action( 'add_meta_boxes', array( $this, 'metabox_register' )         );
			add_action( 'save_post',      array( $this, 'metabox_save'     ),  1, 2  );
		}
	}

	/**
	 * Adds Schema pieces to our output.
	 *
	 * @param array                 $pieces  Graph pieces to output.
	 * @param \WPSEO_Schema_Context $context Object with context variables.
	 *
	 * @return array $pieces Graph pieces to output.
	 */
	public function schema_piece( $pieces, $context ) {
		require_once( plugin_dir_path( __FILE__ ) . '/class-be-product-review.php' );
		$pieces[] = new BE_Product_Review( $context );
		return $pieces;
	}

	/**
	 * Register the metabox
	 *
	 * @since 1.6.0
	 */
	function metabox_register() {

		// Allow devs to control what post types this is allowed on
		$post_types = apply_filters( 'be_product_review_schema_post_types', array( 'post' ) );

		// Add metabox for each post type found
		foreach ( $post_types as $post_type ) {
			add_meta_box( 'be-product-review', 'Product Review', array( $this, 'metabox_render' ), $post_type, 'normal', 'high' );
		}
	}

	/**
	 * Output the metabox
	 *
	 * @since 1.6.0
	 */
	function metabox_render() {

		// Security nonce
		wp_nonce_field( 'be_product_review', 'be_product_review_nonce' );

		$fields = $this->metabox_fields();

		echo '<table class="form-table cmb_metabox"><tbody>';

		foreach( $fields as $field ) {
			$current = get_post_meta( get_the_ID(), $field['key'], true );
			echo '<tr>';

			if( !empty( $field['label'] ) )
				echo '<th style="width: 18%"><label for="' . sanitize_key( $field['key'] ) . '">' . esc_html( $field['label'] ) . '</label></th>';

			echo '<td>';
			switch( $field['type'] ) {
				case 'checkbox':
					echo '<input type="checkbox"' . $this->field_id_name( $field ) . checked( 'on', $current, false ) . ' />';
					break;
				case 'text':
					echo '<input type="text"' . $this->field_id_name( $field ) . ' class="widefat" value="' . esc_html( $current ) . '" />';
					break;
				case 'textarea':
					echo '<textarea' . $this->field_id_name( $field ) . ' rows="5">' . esc_html( $current ) . '</textarea>';
					break;
				case 'number':
					echo '<input type="number" min="1" max="5"' . $this->field_id_name( $field ) . ' class="widefat" value="' . esc_attr( $current ) . '" />';
			}

			if( !empty( $field['description'] ) )
				echo '<p style="color: #AAA; font-style: italic; font-size: 14px;">' . esc_html( $field['description'] ) . '</p>';

			echo '</td>';

			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Metabox fields
	 *
	 * @since 1.0.0
	 * @return array $fields
	 */
	function metabox_fields() {
		return array(
			array(
				'key' => 'be_product_review_include',
				'type' => 'checkbox',
				'label' => 'Include Product Review',
			),
			array(
				'key' => 'be_product_review_name',
				'type' => 'text',
				'label' => 'Product Name',
				'description' => 'If empty, post title will be used',
			),
			array(
				'key' => 'be_product_review_rating',
				'type' => 'number',
				'label' => 'Rating',
				'description' => 'Rating between 1 and 5',
			),
			array(
				'key' => 'be_product_review_summary',
				'type' => 'textarea',
				'label' => 'Summary',
				'description' => 'If empty, post excerpt will be used',
			),
			array(
				'key' => 'be_product_review_body',
				'type' => 'textarea',
				'label' => 'Full Review',
				'description' => 'If empty (which is recommended), post content will be used',
			)
		);
	}

	/**
	 * Field ID and Name
	 *
	 * @since 1.0.0
	 *
	 * @param array $field
	 * @return string
	 */
	function field_id_name( $field = array() ) {
		if( !empty( $field['key'] ) )
		 return ' id="' . sanitize_key( $field['key'] ) . '" name="' . sanitize_key( $field['key'] ) . '"';
	}

	/**
	 * Handle metabox saves
	 *
	 * @since 1.6.0
	 */
	function metabox_save( $post_id, $post ) {

		// Security check
		if ( ! isset( $_POST['be_product_review_nonce'] ) || ! wp_verify_nonce( $_POST['be_product_review_nonce'], 'be_product_review' ) ) {
			return;
		}

		// Bail out if running an autosave, ajax, cron.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		// Bail out if the user doesn't have the correct permissions to update the slider.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = $this->metabox_fields();
		foreach( $fields as $field ) {

			if( ! isset( $_POST[ $field['key'] ] ) ) {
				delete_post_meta( $post_id, $field['key'] );

			} else {
				$value = $_POST[ $field['key'] ];
				switch( $field['type'] ) {
					case 'checkbox':
						$value = 'on' === $value ? $value : false;
						break;

					case 'text':
					case 'textarea':
					case 'number':
						$value = esc_html( $value );
						break;

					default:
						$value = false;
				}

				if( empty( $value ) )
					delete_post_meta( $post_id, $field['key'] );
				else
					update_post_meta( $post_id, $field['key'], $value );
			}
		}
	}
}

new BE_Product_Review_Schema;
