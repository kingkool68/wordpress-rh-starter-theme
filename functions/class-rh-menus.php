<?php
/**
 * Clean-up WordPress menus
 */
class RH_Menus {

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
	public function setup_actions() {
		add_action( 'init', array( $this, 'action_init' ) );
	}

	/**
	 * Hook into various WordPress filters
	 */
	public function setup_filters() {
		add_filter( 'wp_nav_menu_objects', array( $this, 'filter_wp_nav_menu_objects' ), 10, 2 );
		add_filter( 'wp_nav_menu_objects', array( $this, 'filter_wp_nav_menu_objects_only_published' ), 9, 2 );
		add_filter( 'nav_menu_css_class', array( $this, 'filter_nav_menu_css_class' ), 10, 2 );
		add_filter( 'nav_menu_link_attributes', array( $this, 'filter_nav_menu_link_attributes' ), 10, 4 );

		// We don't want nav menu items to have ID attributes
		add_filter( 'nav_menu_item_id', '__return_empty_string' );
	}

	/**
	 * Register nav menus
	 */
	public function action_init() {
		register_nav_menu( 'main', 'Main Navigation' );
		register_nav_menu( 'footer', 'Footer Links' );

		wp_register_script(
			'rh-main-nav',
			get_template_directory_uri() . '/assets/js/rh-main-nav.js',
			array( 'jquery' ),
			$ver       = null,
			$in_footer = true
		);

	}

	/**
	 * Add classes to nav menus depending on different conditions
	 *
	 * @param  object $items Array of WP Nav Menu item objects
	 * @param  object $args  Arguments to modify the nav menu object
	 * @return array        Modified nav menu objects
	 */
	public function filter_wp_nav_menu_objects( $items, $args ) {
		$current_url = RH_Helpers::get_current_url();
		foreach ( $items as $item ) {
			if ( '0' === $item->menu_item_parent ) {
				$item->classes[] = 'top-level';
			}

			$children_items = static::submenu_get_children_ids( $item->ID, $items );
			if ( $children_items ) {
				$item->classes[] = 'has-children';
			}

			// Mark top level items in the Main navigation as active if the current page is a child item
			if ( $args->theme_location === 'main' && '0' === $item->menu_item_parent ) {
				if ( str_contains( $current_url, $item->url ) ) {
					$item->classes[] = 'active';
				}
			}
		}
		return $items;
	}

	/**
	 * Filter out any nav menu objects that aren't published
	 *
	 * @param  object $items Array of WP Nav Menu item objects
	 * @param  object $args  Arguments to modify the nav menu object
	 * @return array         Modified nav menu objects
	 */
	public function filter_wp_nav_menu_objects_only_published( $items, $args ) {
		foreach ( $items as $index => $item ) {
			if ( ! is_user_logged_in() && get_post_status( $item->object_id ) !== 'publish' ) {
				unset( $items[ $index ] );
			}
		}
		return $items;
	}

	/**
	 * Modify the CSS classes added to menu items
	 *
	 * @param  array  $class Existing classes to modify
	 * @param  object $item  Nav menu item
	 * @return array         Modified classes
	 */
	public function filter_nav_menu_css_class( $class = array(), $item = null ) {
		$allowed_classes = array(
			'active',
			'top-level',
			'has-children',
		);
		$new_class       = array_intersect( $item->classes, $allowed_classes );
		return $new_class;
	}

	/**
	 * Modify the link attributes of nav menu items
	 *
	 * @param array    $atts  The attributes to modify
	 * @param WP_Post  $item  The current menu item
	 * @param stdClass $args  An object of wp_nav_menu() arguments
	 * @param integer  $depth Depth of menu item. Used for padding.
	 */
	public function filter_nav_menu_link_attributes( $atts = array(), $item = null, $args = null, $depth = 0 ) {
		$atts['data-ga-category'] = 'Menu|' . $args->theme_location;
		$atts['data-ga-label']    = $item->title;
		return $atts;
	}

	/**
	 * Helper function to get a list of Post IDs from a menu item
	 *
	 * @param  [type] $id    [description]
	 * @param  [type] $items [description]
	 * @return [type]        [description]
	 */
	public static function submenu_get_children_ids( $id, $items ) {
		$ids = wp_filter_object_list( $items, array( 'menu_item_parent' => $id ), 'and', 'ID' );
		foreach ( $ids as $id ) {
			$ids = array_merge( $ids, static::submenu_get_children_ids( $id, $items ) );
		}
		return $ids;
	}

	/**
	 * Helper for getting a nested array of menu item data
	 *
	 * @param string|int|WP_Term $menu_id Menu ID, slug, name, or object to pass to wp_get_nav_menu_items()
	 * @return array                      Collection of nav menu items
	 */
	public static function get_nav_menu_data( $menu_id = '' ) {
		$items = wp_get_nav_menu_items( $menu_id );
		return $items ? static::build_nav_menu_item_tree( $items, 0 ) : null;
	}

	/**
	 * Build a tree from flat nav menu item data
	 *
	 * @link https://stackoverflow.com/a/28429487/2078474
	 *
	 * @param array   $elements  Nav item elements to loop over
	 * @param integer $parent_id Parent_ID of the current iteration to determine if children exist
	 * @return array             Collection of nav items with children data included
	 */
	public static function build_nav_menu_item_tree( array &$elements, $parent_id = 0 ) {
		$tree = array();
		foreach ( $elements as &$element ) {
			if ( strval( $parent_id ) === $element->menu_item_parent ) {
				$children = static::build_nav_menu_item_tree( $elements, $element->ID );
				if ( $children ) {
					$element->child_items = $children;
				}

				$tree[ $element->ID ] = $element;
				unset( $element );
			}
		}
		return $tree;
	}

	/**
	 * Find a menu item by URL and return a WP_Menu object along with it's ancestors
	 *
	 * @param  string $menu_location_slug The name of the menu location to process
	 * @param  string $url                The URL of the menu item to target and get the ancestor items of
	 * @return array                      List of WP_Menu items
	 */
	public static function get_ancestor_items_from_menu_by_url( $menu_location_slug = '', $url = '' ) {
		global $wp;
		if ( empty( $menu_location_slug ) ) {
			return array();
		}
		$menu_locations = get_nav_menu_locations();
		if ( empty( $menu_locations[ $menu_location_slug ] ) ) {
			return array();
		}
		$menu_id    = $menu_locations[ $menu_location_slug ];
		$menu_items = wp_get_nav_menu_items( $menu_id );
		if ( empty( $menu_items ) ) {
			return array();
		}
		// Sort the nav items by their menu_order property in DESC order
		$menu_items = wp_list_sort( $menu_items, 'menu_order', 'DESC' );

		// If we don't have a URL use the currently requested URL
		if ( empty( $url ) ) {
			$url = home_url( add_query_arg( array(), $wp->request ) );
			$url = trailingslashit( $url );
		}
		$parent_ids = array();
		$output     = array();
		foreach ( $menu_items as $item ) {
			$item_url = $item->url;
			// Ensure URLs are not relative custom links
			if ( ! empty( $item_url ) && $item_url[0] === '/' ) {
				$item_url = get_site_url() . $item_url;
			}

			// Find the menu item for the given URL
			if ( $item_url === $url ) {
				$parent_ids[] = absint( $item->menu_item_parent );
				$output[]     = $item;

			}
			if ( in_array( $item->ID, $parent_ids, true ) ) {
				$parent_ids[] = absint( $item->menu_item_parent );
				$output[]     = $item;
			}
		}

		return $output;
	}
}

RH_Menus::get_instance();
