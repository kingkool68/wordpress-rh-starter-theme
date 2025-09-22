<?php
/**
 * General Block Settings
 */
class RH_Blocks {
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
		add_action( 'init', array( $this, 'action_init' ), 101 ); // After \Syntax_Highlighting_Code_Block\init() runs
	}

	/**
	 * Hook in to WordPress via filters
	 */
	public function setup_filters() {
		add_filter( 'block_categories_all', array( $this, 'filter_block_categories_all' ), 10, 1 );

		// Disable all frontend styles of the Syntax Highlighting Code block
		add_filter( 'syntax_highlighting_code_block_styling', '__return_false' );
	}

	/**
	 * Set the default behavior for code blocks
	 */
	public function action_init() {
		$block_type                                     = WP_Block_Type_Registry::get_instance()->get_registered( 'core/code' );
		$block_type->attributes['wrapLines']['default'] = true;
	}

	/**
	 * Add RH_ as a block category
	 *
	 * @param array $categories The categories to modify
	 */
	public function filter_block_categories_all( $categories = array() ) {
		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'rh',
					'title' => 'RH',
					'icon'  => 'wordpress',
				),
			)
		);
	}

	/**
	 * Get a list of fields associated with a given ACF block
	 *
	 * @param  string $block_name The name of the block to get the ACF fields for
	 * @return array              List of field data for the given block or empty if not found
	 */
	public static function get_acf_fields_for_block( $block_name = '' ) {
		// Make sure the block name starts with "acf/"
		if ( ! str_starts_with( $block_name, 'acf/' ) ) {
			$block_name = 'acf/' . $block_name;
		}
		$acf_group_data = acf_get_local_store( 'groups' )->get_data();
		foreach ( $acf_group_data as $data ) {
			$locations = $data['location'];
			foreach ( $locations as $location ) {
				if ( empty( $location ) || ! is_array( $location ) ) {
					continue;
				}
				foreach ( $location as $group ) {
					if ( empty( $group['param'] ) || empty( $group['value'] ) ) {
						continue;
					}
					if ( $group['param'] === 'block' && $group['value'] === $block_name ) {
						$key = $data['key'];
						return acf_get_fields( $key );
					}
				}
			}
		}
		return array();
	}
}
RH_Blocks::get_instance();
