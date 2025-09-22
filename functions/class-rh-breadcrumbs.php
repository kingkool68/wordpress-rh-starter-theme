<?php
/**
 * Breadcrumbs Hansel and Gretel would be proud of
 */
class RH_Breadcrumbs {

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
	 * Hook into various WordPress actions
	 */
	public function setup_actions() {}

	/**
	 * Hook into various WordPress filters
	 */
	public function setup_filters() {}

	/**
	 * Render the component
	 *
	 * @param  array $args Arguments to modify what is rendered
	 */
	public static function render( $args = array() ) {
		$defaults = array(
			'separator'  => '&nbsp;',
			'aria_label' => 'breadcrumbs',
			'items'      => array(),
		);
		$context  = wp_parse_args( $args, $defaults );
		if ( empty( $context['items'] ) ) {
			return;
		}
		return Sprig::render( 'breadcrumbs.twig', $context );
	}

	/**
	 * Render breadcrumbs from a menu
	 *
	 * @param  string $menu_location_slug The name of the menu location in the theme to process the items for
	 * @param  array  $args Arguments to be passed to the render() method
	 */
	public static function render_from_menu( $menu_location_slug = '', $args = array() ) {
		$menu_items = RH_Menus::get_ancestor_items_from_menu_by_url( $menu_location_slug );
		// We want the active item last, the parent 2nd to last, grad parent 3rd to last etc.
		$menu_items = array_reverse( $menu_items );

		$items = array();
		foreach ( $menu_items as $menu_item ) {
			$items[] = (object) array(
				'text' => $menu_item->title,
				'url'  => $menu_item->url,
			);
		}
		$defaults = array(
			'items' => $items,
		);
		$args     = wp_parse_args( $args, $defaults );
		$args     = apply_filters( 'rh/breadcrumbs/render_from_menu/args', $args, $menu_location_slug );
		return static::render( $args );
	}
}
RH_Breadcrumbs::get_instance();
