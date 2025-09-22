<?php
/**
 * Security modifications
 */
class RH_Security {
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
		add_action( 'after_setup_theme', array( $this, 'action_after_setup_theme' ), 0 );
		add_action( 'acf/init', array( $this, 'action_acf_init' ) );
		add_action( 'send_headers', array( $this, 'action_send_headers' ) );
		add_action( 'template_redirect', array( $this, 'action_template_redirect' ) );

		// Remove the <meta name="generator" content="WordPress X.X" /> from the <head>
		// remove_action( 'wp_head', 'wp_generator' );
	}

	/**
	 * Hook into various WordPress filters
	 */
	public function setup_filters() {
		add_filter( 'rest_endpoints', array( $this, 'filter_rest_endpoints' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'filter_rewrite_rules_array' ) );
	}

	/**
	 * Respond to requests for /.well-known/security.txt
	 */
	public function action_after_setup_theme() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$request_path = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$request_path = parse_url( $request_path, PHP_URL_PATH );
		$request_path = strtolower( $request_path );

		if ( $request_path === '/.well-known/security.txt' ) {
			header( 'content-type: text/plain; charset=UTF-8' );
			$policy_url = get_site_url() . '/vulnerability-disclosure-policy/';
			$policy_url = esc_url( $policy_url );

			echo "Contact: $policy_url
Expires: 2025-12-30T23:00:00.000Z
Acknowledgments: $policy_url
Preferred-Languages: en
Policy: $policy_url
";
			die();
		}
	}

	/**
	 * Disable the ACF shortcode which can be used to leak ACF data
	 *
	 * @link https://www.advancedcustomfields.com/blog/acf-6-0-3-release-security-changes-to-the-acf-shortcode-and-ui-improvements/
	 */
	public function action_acf_init() {
		acf_update_setting( 'enable_shortcode', false );
	}

	/**
	 * Enables the HTTP Strict Transport Security (HSTS) header
	 */
	public function action_send_headers() {
		header( 'Strict-Transport-Security: max-age=' . 1 * YEAR_IN_SECONDS . '; includeSubDomains; preload' );
	}

	/**
	 * Redirect /security.txt to /.well-known/security.txt
	 */
	public function action_template_redirect() {
		global $wp;
		if ( strtolower( $wp->request ) === 'security.txt' ) {
			$redirect_url = get_site_url() . '/.well-known/security.txt';
			if ( ! empty( $_GET ) ) {
				$redirect_url = add_query_arg( $_GET, $redirect_url );
			}
			wp_safe_redirect( $redirect_url, $status = 301 );
			die();
		}
	}

	/**
	 * Remove default WP JSON API routes dealing with lisitng users
	 *
	 * @param array $endpoints The endpoints to modify
	 */
	public function filter_rest_endpoints( $endpoints = array() ) {
		$keys_to_remove = array(
			'/wp/v2/users',
			'/wp/v2/users/(?P<id>[\\d]+)',
			'/oembed/1.0/embed',
		);
		foreach ( $keys_to_remove as $key ) {
			unset( $endpoints[ $key ] );
		}
		return $endpoints;
	}

	/**
	 * Remove all rewrite rules related to embeds
	 *
	 * @param array $rules WordPress rewrite rules.
	 * @return array Rewrite rules without embeds rules.
	 */
	public function filter_rewrite_rules_array( $rules ) {
		foreach ( $rules as $rule => $rewrite ) {
			if ( false !== strpos( $rewrite, 'embed=true' ) ) {
				unset( $rules[ $rule ] );
			}
		}
		return $rules;
	}
}
RH_Security::get_instance();
