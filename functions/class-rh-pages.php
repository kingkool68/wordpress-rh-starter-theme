<?php
/**
 * Miscellaneous page functionality
 */
class RH_Pages {

	/**
	 * Post type
	 *
	 * @var string
	 */
	public static $post_type = 'page';

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
			$instance->setup_actions();
			$instance->setup_filters();
		}
		return $instance;
	}

	/**
	 * Hook into WordPress via actions
	 */
	public function setup_actions() {
		add_action( 'acf/init', array( $this, 'action_acf_init' ) );
	}

	/**
	 * Hook into WordPress via filters
	 */
	public function setup_filters() {
		add_filter( 'body_class', array( $this, 'filter_body_class' ) );
	}

	/**
	 * Register Advanced Custom Fields
	 */
	public function action_acf_init() {
		// Custom fields for the post_type
		$args = array(
			'key'         => 'page_fields',
			'title'       => 'Page Options',
			'fields'      => array(
				array(
					'key'     => 'field_page_hide_title',
					'name'    => 'page_hide_title',
					'label'   => 'Hide Page Title',
					'type'    => 'true_false',
					'message' => 'Check this box to prevent the page title from showing',
				),
			),
			'location'    => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => static::$post_type,
					),
				),
			),
			'description' => 'General Page fields',
		);
		$args = apply_filters( 'rh/pages/page_fields_args', $args );
		acf_add_local_field_group( $args );
	}

	/**
	 * Modify page body classes
	 *
	 * @param array $class The body classes to modify
	 */
	public function filter_body_class( $class = array() ) {
		if ( is_page() ) {
			$post    = get_post();
			$class[] = 'page--' . sanitize_html_class( $post->post_name );
		}
		return $class;
	}

}
RH_Pages::get_instance();
