<?php
/**
 * Clean up the admin area to reduce clutter and distractions
 */
class RH_Admin {
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
		add_action( 'wp_dashboard_setup', array( $this, 'action_wp_dashboard_setup' ), 999 );
		add_action( 'wp_before_admin_bar_render', array( $this, 'action_wp_before_admin_bar_render' ), 0 );
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ), 100 );
	}

	/**
	 * Hook into various WordPress filters
	 */
	public function setup_filters() {
	}

	/**
	 * Remove Dashboard widgets we don't want
	 */
	public function action_wp_dashboard_setup() {
		global $wp_meta_boxes;
		$widgets = array(
			'normal' => array(
				// 'dashboard_activity',
				'wpseo-dashboard-overview',        // Yoast SEO
				'monsterinsights_reports_widget',
				'semperplugins-rss-feed',         // SEO News widget from All in One SEO
				'wpforms_reports_widget_lite',    // WP Forms
			),
			'side'   => array(
				'dashboard_primary',
				'dashboard_quick_press',
			),
		);
		foreach ( $widgets as $priotity => $keys ) {
			foreach ( $keys as $key ) {
				if ( isset( $wp_meta_boxes['dashboard'][ $priotity ]['core'][ $key ] ) ) {
					unset( $wp_meta_boxes['dashboard'][ $priotity ]['core'][ $key ] );
				}
			}
		}
	}

	/**
	 * Remove admin bar items that we don't need
	 */
	public function action_wp_before_admin_bar_render() {
		global $wp_admin_bar;

		// Remove comments because we don't have comments
		$wp_admin_bar->remove_menu( 'comments' );

		// Remove the Customizer menu
		$wp_admin_bar->remove_menu( 'customize' );
	}

	/**
	 * Remove admin menus that we don't need
	 */
	public function action_admin_menu() {
		global $menu;
		unset( $menu[25] ); // Comments

		// Move the Revisions menu item to the bottom
		foreach ( $menu as $key => $item ) {
			if ( $item[0] === 'Revisions' ) {
				unset( $menu[ $key ] );
				$menu['99.51998'] = $item;
			}
		}
	}
}
RH_Admin::get_instance();
