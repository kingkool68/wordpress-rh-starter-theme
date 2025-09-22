<?php
/**
 * Helpers for rendering SVGs inline
 */
class RH_SVG {

	/**
	 * Cache of data after querying for all SVG files
	 * on the filesystem so it is only performed once per request max
	 *
	 * @var array
	 */
	private static $all_svg_cache = array();

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
		}
		return $instance;
	}

	/**
	 * Helper function for fetching SVG icons
	 *
	 * @param  string $icon  Name of the SVG file in the icons directory
	 * @param array  $args Arguments to modify the defaults passed to static::get_svg()
	 *
	 * @return string        Inline SVG markup
	 */
	public static function get_icon( $icon = '', $args = array() ) {
		if ( ! $icon ) {
			return;
		}
		$path     = get_template_directory() . '/assets/icons/' . $icon . '.svg';
		$defaults = array(
			'css_class' => 'icon icon-' . $icon,
		);
		$args     = wp_parse_args( $args, $defaults );
		return static::get_svg( $path, $args );
	}

	/**
	 * Read all of the SVG files in the /assets/icons/ directory
	 *
	 * @return array Objects contaning the label and contents of all Icons SVGs
	 */
	public static function get_all_icons() {
		$directory = get_template_directory() . '/assets/icons/';
		$cache_key = 'icons';
		$callback  = array( __CLASS__, 'get_icon' );
		return static::get_all_svgs( $directory, $cache_key, $callback );
	}

	/**
	 * Generic helper to modify the markup for a given path to an SVG
	 *
	 * @param  string $path  Absolute path to the SVG file
	 * @param  array  $args  Args to modify attributes of the SVG
	 * @return string        Inline SVG markup
	 */
	public static function get_svg( $path = '', $args = array() ) {
		if ( ! $path ) {
			return;
		}
		$defaults = array(
			'role'          => 'img',
			'css_class'     => '',
			'add_css_class' => '',
		);
		$args     = wp_parse_args( $args, $defaults );

		if ( ! empty( $args['add_css_class'] ) ) {
			if ( ! is_array( $args['add_css_class'] ) ) {
				$args['add_css_class'] = explode( ' ', $args['add_css_class'] );
			}
			if ( ! is_array( $args['css_class'] ) ) {
				$args['css_class'] = explode( ' ', $args['css_class'] );
			}

			$args['css_class'] = array_merge( $args['css_class'], $args['add_css_class'] );
		}

		if ( is_array( $args['css_class'] ) ) {
			$args['css_class'] = array_unique( $args['css_class'], SORT_STRING );
			$args['css_class'] = implode( ' ', $args['css_class'] );
		}
		if ( file_exists( $path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$svg = file_get_contents( $path );
			// Strip the width and height attributes so size can be scaled via CSS font-size
			// $svg = preg_replace( '/\s(width|height)="[\d\.]+"/i', '', $svg );
			$svg = str_replace( '<svg ', '<svg class="' . esc_attr( $args['css_class'] ) . '" role="' . esc_attr( $args['role'] ) . '" ', $svg );
			return $svg;
		}
	}

	/**
	 * Get all of the SVG files for a given directory
	 *
	 * @param  string $directory Directory to search for SVGs in
	 * @param  string $cache_key Key to use to read/set the cache
	 * @param  string $callback  Callback used to fetch the SVG contents
	 * @return array             Objects containing the SVG contents and label
	 */
	public static function get_all_svgs( $directory = '', $cache_key = '', $callback = '' ) {
		if (
			! empty( $cache_key ) &&
			! empty( static::$all_svg_cache[ $cache_key ] )
		) {
			return static::$all_svg_cache[ $cache_key ];
		}
		$svgs = array();
		if ( ! $directory || ! file_exists( $directory ) ) {
			return $svgs;
		}
		if ( ! is_callable( $callback ) ) {
			$callback = array( __CLASS__, 'get_svg' );
		}

		$url_search    = get_template_directory();
		$url_replace   = get_template_directory_uri();
		$url_directory = str_replace( $url_search, $url_replace, $directory );

		$iterator = new DirectoryIterator( $directory );
		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}
			$parts = explode( '.', $file->getFilename() );
			if ( empty( $parts[1] ) || 'svg' !== strtolower( $parts[1] ) ) {
				continue;
			}
			$filename          = $parts[0];
			$svg               = $callback( $filename );
			$svgs[ $filename ] = (object) array(
				'svg'   => $svg,
				'label' => $filename,
				'url'   => $url_directory . $file->getFilename(),
			);
		}
		ksort( $svgs );
		if ( ! empty( $cache_key ) ) {
			static::$all_svg_cache[ $cache_key ] = $svgs;
		}
		return $svgs;
	}
}

RH_SVG::get_instance();
