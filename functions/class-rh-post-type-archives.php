<?php
/**
 * Make post type archives editable by storing data in a page using the same post_name as the post type archive
 */
class RH_Post_Type_Archives {

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
			$instance->setup_actions();
		}
		return $instance;
	}

	/**
	 * Hook into WordPress via actions
	 */
	public function setup_actions() {
		add_action( 'wp_before_admin_bar_render', array( $this, 'action_wp_before_admin_bar_render' ) );
	}

	/**
	 * Add an edit button to the admin bar for editing post type archive pages
	 */
	public function action_wp_before_admin_bar_render() {
		global $wp_admin_bar;
		global $wp_query;
		if ( is_post_type_archive() ) {
			$post = static::get_post_type_archive_post();
			if ( empty( $post->ID ) ) {
				return;
			}
			$title = 'Edit';
			$obj   = get_post_type_object( get_post_type() );
			if ( ! empty( $obj->labels->archives ) ) {
				$title .= ' ' . $obj->labels->archives;
			}
			$wp_admin_bar->add_menu(
				array(
					'parent' => false, // use 'false' for a root menu
					'id'     => 'edit', // Important so the pencil icon shows up
					'title'  => $title,
					'href'   => get_edit_post_link( $post ),
					'meta'   => false,
				)
			);
		}
	}

	/**
	 * Get the post object matching the post type archive slug
	 *
	 * @param  string $post_type      The post type archive to get data for
	 * @param  string $post_post_type The post type of the WP_Post object to get data for
	 * @return boolean|WP_Post       The WP_Post object if found or false
	 */
	public static function get_post_type_archive_post( $post_type = '', $post_post_type = 'page' ) {
		if ( empty( $post_type ) ) {
			$post_type = get_post_type();
		}
		if ( empty( $post_type ) ) {
			return;
		}
		$post_type_object = get_post_type_object( $post_type );
		$slug             = $post_type_object->name;
		if ( ! empty( $post_type_object->has_archive ) && is_string( $post_type_object->has_archive ) ) {
			$slug = $post_type_object->has_archive;
		}
		if ( ! empty( $slug ) ) {
			$args  = array(
				'name'           => $slug,
				'post_type'      => $post_post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
			);
			$query = new WP_Query( $args );
			if ( ! empty( $query->posts[0] ) ) {
				return $query->posts[0];
			}
		}
		return false;
	}
}

RH_Post_Type_Archives::get_instance();
