<?php
/**
 * WP Archive Plugin
 *
 * @package WP_Archive_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WP_Archive_Plugin
 *
 * @package WP_Archive_Plugin
 */
class WP_Archive_Plugin_Data_Lookup {

	const TRANSIENT__ALL_POST_DATA = 'all_post_data';

	/**
	 * Get all WordPress blog posts from the DB.
	 *
	 * Store them in cache.
	 *
	 * If cache exists, return from cache.
	 *
	 * @return array
	 */
	public function get_post_data() {

		$transient = get_transient( self::TRANSIENT__ALL_POST_DATA );

		if ( false !== $transient ) {
			return $transient;
		}

		$data = [];

		$wp_posts = get_posts(
			[
				'numberposts' => -1,
				'post_type'   => 'post',
			]
		);

		if ( empty( $wp_posts ) ) {
			return $data;
		}

		/**
		 * @var WP_Post $wp_post
		 */
		foreach ( $wp_posts as $key => $wp_post ) {

			$post_id   = $wp_post->ID;
			$date_time = new DateTime( $wp_post->post_date );

			$data[ $key ]['title']          = $wp_post->post_title;
			$data[ $key ]['slug']           = $wp_post->post_name;
			$data[ $key ]['date']           = $wp_post->post_date;
			$data[ $key ]['timestamp']      = $date_time->getTimestamp();
			$data[ $key ]['author']['id']   = $wp_post->post_author;
			$data[ $key ]['author']['name'] = get_the_author_meta( 'display_name', $wp_post->post_author );
			$data[ $key ]['comment_count']  = $wp_post->comment_count;

			$data[ $key ]['permalink'] = get_permalink( $wp_post );

			$data[ $key ]['tags']       = $this->get_tag_data( $post_id );
			$data[ $key ]['categories'] = $this->get_category_data( $post_id );

		}

		set_transient( self::TRANSIENT__ALL_POST_DATA, $data, 60 * 60 * 24 );

		return $data;
	}

	/**
	 * Clear the cached data - useful when deleting, creating, or updating a post.
	 */
	public function flush_post_data_transient() {
		delete_transient( self::TRANSIENT__ALL_POST_DATA );
	}

	/**
	 * Given a post id, get all the tag information about that post.
	 *
	 * @param int $post_id a post id.
	 * @return array
	 */
	private function get_tag_data( $post_id ) {
		$formatted_tags = [];

		$wp_tags = get_the_tags( $post_id );

		if ( empty( $wp_tags ) ) {
			return $formatted_tags;
		}

		foreach ( $wp_tags as $key => $wp_tag ) {

			$formatted_tags[ $key ]['tag_id']      = $wp_tag->term_id;
			$formatted_tags[ $key ]['name']        = $wp_tag->name;
			$formatted_tags[ $key ]['slug']        = $wp_tag->slug;
			$formatted_tags[ $key ]['description'] = $wp_tag->description;
			$formatted_tags[ $key ]['count']       = $wp_tag->count;

			$formatted_tags[ $key ]['permalink'] = get_tag_link( $wp_tag->term_id );

		}

		return $formatted_tags;
	}

	/**
	 * Given a post id, get all the category information about that post.
	 *
	 * @param int $post_id a post id.
	 * @return array
	 */
	private function get_category_data( $post_id ) {
		$formatted_categories = [];

		$wp_categories = get_the_category( $post_id );

		if ( empty( $wp_categories ) ) {
			return $formatted_categories;
		}

		foreach ( $wp_categories as $key => $wp_category ) {

			$formatted_categories[ $key ]['category_id'] = $wp_category->term_id;
			$formatted_categories[ $key ]['name']        = $wp_category->name;
			$formatted_categories[ $key ]['slug']        = $wp_category->slug;
			$formatted_categories[ $key ]['description'] = $wp_category->description;
			$formatted_categories[ $key ]['count']       = $wp_category->count;

			$formatted_categories[ $key ]['permalink'] = get_category_link( $wp_category->term_id );

		}

		return $formatted_categories;
	}

}
