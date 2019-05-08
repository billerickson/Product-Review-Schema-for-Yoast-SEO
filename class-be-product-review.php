<?php
/**
 * Class BE_Review
 */
class BE_Product_Review extends \WPSEO_Schema_Article implements \WPSEO_Graph_Piece {
	/**
	 * A value object with context variables.
	 *
	 * @var WPSEO_Schema_Context
	 */
	private $context;

	/**
	 * Product_Rating constructor.
	 *
	 * @param WPSEO_Schema_Context $context Value object with context variables.
	 */
	public function __construct( WPSEO_Schema_Context $context ) {
		parent::__construct( $context );
		$this->context   = $context;
	}

	/**
	 * Determines whether or not a piece should be added to the graph.
	 *
	 * @return bool
	 */
	public function is_needed() {
		$post_types = apply_filters( 'be_product_review_schema_post_types', array( 'post' ) );
		if( is_singular( $post_types ) ) {
			$display = get_post_meta( $this->context->id, 'be_product_review_include', true );
			if( 'on' === $display ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Adds our Team Member's Person piece of the graph.
	 *
	 * @return array $graph Person Schema markup
	 */
	public function generate() {
		$post          = get_post( $this->context->id );
		$comment_count = get_comment_count( $this->context->id );

		$name = get_post_meta( $this->context->id, 'be_product_review_name', true );
		if( empty( $name ) )
			$name = get_the_title();

		$rating = get_post_meta( $this->context->id, 'be_product_review_rating', true );
		if( ! is_numeric( $rating ) )
			$rating = 1;

		$summary = get_post_meta( $this->context->id, 'be_product_review_summary', true );
		if( empty( $summary ) )
			$summary = get_the_excerpt( $post );

		$body = get_post_meta( $this->context->id, 'be_product_review_body', true );
		if( empty( $body ) )
			$body = $post->post_content;

		$data          = array(
			'@type'            => 'Review',
			'@id'              => $this->context->canonical . '#product-review',
			'isPartOf'         => array( '@id' => $this->context->canonical . WPSEO_Schema_IDs::ARTICLE_HASH ),
			'itemReviewed'     => array(
					'@type'    => 'Product',
					'image'    => array(
						'@id'  => $this->context->canonical . WPSEO_Schema_IDs::PRIMARY_IMAGE_HASH,
					),
					'name'     => 'Product Name',
			),
			'reviewRating'     => array(
				'@type'        => 'Rating',
				'ratingValue'  => esc_attr( $rating ),
			),
			'name'         => wp_strip_all_tags( $name ),
			'description' => wp_strip_all_tags( $summary ),
			'reviewBody'  => wp_kses_post( $body ),
			'author'           => array(
				'@id'  => get_author_posts_url( get_the_author_meta( 'ID' ) ),
				'name' => get_the_author_meta( 'display_name', $post->post_author ),
			),
			'publisher'        => array( '@id' => $this->get_publisher_url() ),
			'datePublished'    => mysql2date( DATE_W3C, $post->post_date_gmt, false ),
			'dateModified'     => mysql2date( DATE_W3C, $post->post_modified_gmt, false ),
			'commentCount'     => $comment_count['approved'],
			'mainEntityOfPage' => $this->context->canonical . WPSEO_Schema_IDs::WEBPAGE_HASH,
		);
		$data = apply_filters( 'be_review_schema_data', $data, $this->context );

		return $data;
	}

	/**
	 * Determine the proper publisher URL.
	 *
	 * @return string
	 */
	private function get_publisher_url() {
		if ( $this->context->site_represents === 'person' ) {
			return $this->context->site_url . WPSEO_Schema_IDs::PERSON_HASH;
		}

		return $this->context->site_url . WPSEO_Schema_IDs::ORGANIZATION_HASH;
	}
}