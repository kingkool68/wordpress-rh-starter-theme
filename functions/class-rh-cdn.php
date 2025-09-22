<?php
/**
 * CDN makes our site go Zooooooom!
 */
class RH_CDN {

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
			if ( static::is_cdn_enabled() ) {
				$instance->setup_actions();
				$instance->setup_filters();
			}
		}
		return $instance;
	}

	/**
	 * Hook into WordPress via actions
	 */
	public function setup_actions() {
		add_action( 'send_headers', array( $this, 'action_send_headers' ), 1 );
	}

	/**
	 * Hook in to WordPress via filters
	 */
	public function setup_filters() {
		add_filter( 'upload_dir', array( $this, 'filter_upload_dir' ) );
		add_filter( 'render_block', array( $this, 'filter_render_block' ), 10, 2 );

		$filters = array(
			'includes_url',
			'plugins_url',
			'theme_root_uri',
			'script_loader_src',
			'rh/cache_busting_path/base_url', // Modify what we consider a base URL so we can append cache-busting time stamp
		);
		foreach ( $filters as $filter ) {
			add_filter( $filter, array( static::get_instance(), 'replace_with_cdn_url' ), 10, 1 );
		}
	}

	/**
	 * If the CDN is enabled add a link HTTP header to preconnect to that URL so the page can start downloading resources as quickly as possible.
	 *
	 * @see https://andydavies.me/blog/2019/03/22/improving-perceived-performance-with-a-link-rel-equals-preconnect-http-header/
	 */
	public function action_send_headers() {
		header( 'link: <' . esc_url( RH_CDN_URL ) . '>; rel=preconnect; crossorigin' );
	}

	/**
	 * Modify the upload directory base URL
	 *
	 * @param array $data The Upload directory data to modify
	 */
	public function filter_upload_dir( $data = array() ) {
		$data['baseurl'] = static::replace_with_cdn_url( $data['baseurl'] );
		return $data;
	}

	/**
	 * Replace URLs in the core WP Image block markup with the CDN URL
	 *
	 * @param string $block_content The markup to modify
	 * @param array  $block Details about the block being rendered
	 */
	public function filter_render_block( $block_content = '', $block = array() ) {
		if ( $block['blockName'] === 'core/image' ) {
			$cdn_url       = untrailingslashit( RH_CDN_URL );
			$site_url      = get_site_url();
			$block_content = str_ireplace( $site_url, $cdn_url, $block_content );
		}

		return $block_content;
	}

	/**
	 * Transform a given URL with the CDN version
	 *
	 * @param string $url The URL to transform to a CDN version
	 */
	public static function replace_with_cdn_url( $url = '' ) {
		if ( ! static::is_cdn_enabled() ) {
			return $url;
		}
		if ( empty( $url ) ) {
			return $url;
		}
		$cdn_url  = untrailingslashit( RH_CDN_URL );
		$site_url = get_site_url();
		$url      = str_ireplace( $site_url, $cdn_url, $url );
		return $url;
	}

	/**
	 * Conditional to determine if a CDN is enabled for the current environment
	 *
	 * @return boolean
	 */
	public static function is_cdn_enabled() {
		return ( defined( 'RH_CDN_URL' ) && ! empty( RH_CDN_URL ) );
	}
}
RH_CDN::get_instance();
