<?php
/**
 * Handle anything around general JavaScripts and CSS stylesheets
 */
class RH_Scripts_And_Styles {

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
		add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'action_enqueue_block_editor_assets' ) );
		add_action( 'wp_default_scripts', array( $this, 'action_wp_default_scripts' ) );

		// We want styles to come after scripts in the <head>
		// See https://speakerdeck.com/csswizardry/get-your-head-straight?slide=39
		remove_action( 'wp_head', 'wp_print_styles', 8 );
		add_action( 'wp_head', 'wp_print_styles', 9 );

		// Don't render inline global styles
		if ( ! is_admin() ) {
			remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
			remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
		}
	}

	/**
	 * Hook into WordPress via filters
	 */
	public function setup_filters() {
		add_filter( 'script_loader_src', array( $this, 'filter_cache_busting_file_src' ) );
		add_filter( 'style_loader_src', array( $this, 'filter_cache_busting_file_src' ) );
		add_filter( 'script_loader_tag', array( $this, 'filter_script_loader_tag' ), 10, 3 );

		// Remove default block styles
		remove_filter( 'render_block', 'wp_render_layout_support_flag', 10, 2 );
		remove_filter( 'render_block', 'wp_render_elements_support', 10, 2 );
	}

	/**
	 * Register various scripts and stylesheets
	 */
	public function action_init() {
		wp_register_style(
			'rh',
			get_template_directory_uri() . '/assets/css/rh.min.css',
			array(),
			null,
			'all'
		);

		wp_register_script(
			'rh-editor',
			get_template_directory_uri() . '/assets/js/admin/editor.js',
			$deps      = array( 'jquery' ),
			$ver       = null,
			$in_footer = true
		);

		// Don't load the WP Embed script. See https://wordpress.stackexchange.com/a/285907/2744
		wp_deregister_script( 'wp-embed' );

		// Improve perceived performance navigating between pages. See https://instant.page/
		wp_register_script(
			'instant.page',
			get_template_directory_uri() . '/assets/js/instant.page.js',
			$deps      = array(),
			$ver       = null,
			$in_footer = true
		);

		wp_register_script(
			'vana11y-tabs',
			get_template_directory_uri() . '/assets/js/vana11y-tabs.js',
			$deps      = array(),
			$ver       = null,
			$in_footer = true
		);

		if ( defined( 'GA_TRACKING_ID' ) && ! empty( GA_TRACKING_ID ) ) {
			wp_register_script(
				'google-analytics',
				esc_url( 'https://www.googletagmanager.com/gtag/js?id=' . GA_TRACKING_ID ),
				$deps      = array(),
				$ver       = null,
				$in_footer = false
			);
		}
	}

	/**
	 * Enqueue the main stylesheet at the right time
	 * NOTE: This needs to happen later than init hook otherwise WordPress admin css doesn't load
	 */
	public function action_wp_enqueue_scripts() {
		wp_enqueue_style( 'rh' );

		wp_enqueue_script( 'google-analytics' );

		// Don't load the default block library CSS since we don't use it
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'classic-theme-styles' );

		// Remove frontend assets from the Automatic Upload Images plugin
		wp_dequeue_style( 'automatic-upload-images' );
		wp_dequeue_script( 'automatic-upload-images' );

		wp_enqueue_script( 'instant.page' );
	}

	/**
	 * Enqueue assets when the block editor is loaded
	 */
	public function action_enqueue_block_editor_assets() {
		wp_enqueue_script( 'rh-editor' );
	}

	/**
	 * Dequeue jQuery Migrate on the frontend
	 *
	 * @link https://wordpress.stackexchange.com/a/291711/2744
	 *
	 * @param WP_Script $scripts The scripts to modify
	 */
	public function action_wp_default_scripts( $scripts ) {
		if ( ! is_admin() && ! empty( $scripts->registered['jquery'] ) ) {
			$scripts->registered['jquery']->deps = array_diff(
				$scripts->registered['jquery']->deps,
				array( 'jquery-migrate' )
			);
		}
	}

	/**
	 * Replace the `ver` query arg with the file's last modified timestamp
	 *
	 * @param  string $src URL to a file
	 * @return string      Modified URL to a file
	 */
	public function filter_cache_busting_file_src( $src = '' ) {
		global $wp_scripts;
		// If $wp_scripts hasn't been initialized then bail.
		if ( ! $wp_scripts instanceof WP_Scripts ) {
			return $src;
		}

		// Check if script lives on this domain. Can't rewrite external scripts, they won't work.
		$base_url = apply_filters( 'rh/cache_busting_path/base_url', $wp_scripts->base_url, $src );
		if ( ! strstr( $src, $base_url ) ) {
			return $src;
		}

		// Remove the 'ver' query var: ?ver=0.1
		$src   = remove_query_arg( 'ver', $src );
		$regex = '/' . preg_quote( $base_url, '/' ) . '/';
		$path  = preg_replace( $regex, '', $src );

		// If the folder starts with wp- then we can figure out where it lives on the filesystem
		$file = null;
		if ( strstr( $path, '/wp-' ) ) {
			$file = untrailingslashit( ABSPATH ) . $path;
		}
		if ( ! file_exists( $file ) ) {
			return $src;
		}

		$time_format     = apply_filters( 'rh/cache_busting_path/time_format', 'Y-m-d_G-i' );
		$modified_time   = filemtime( $file );
		$timezone_string = get_option( 'timezone_string' );
		if ( empty( $timezone_string ) ) {
			$timezone_string = 'Etc/GMT';
		}

		$dt = new DateTime( '@' . $modified_time );
		$dt->setTimeZone( new DateTimeZone( $timezone_string ) );
		$time = $dt->format( $time_format );
		$src  = add_query_arg( 'ver', $time, $src );
		return $src;
	}

	/**
	 * Load certain scripts asynchronously so they aren't render blocking
	 *
	 * @param string $tag    The script HTML about to be rendered
	 * @param string $handle The handle of the script being called to render
	 * @param string $src    The `src` attribute of the script tag being rendered
	 */
	public function filter_script_loader_tag( $tag = '', $handle = '', $src = '' ) {
		$script_handles_that_should_load_async = array(
			'google-analytics',
		);
		if ( in_array( $handle, $script_handles_that_should_load_async, true ) ) {
			$tag = str_replace( "src='", "async src='", $tag );
		}
		return $tag;
	}
}

RH_Scripts_And_Styles::get_instance();
