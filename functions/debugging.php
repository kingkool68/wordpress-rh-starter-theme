<?php
/**
 * Debugging functions to make life easier
 *
 * @package Russell Heimlich
 */

if ( ! function_exists( 'wp_dump' ) ) :
	/**
	 * Dump variables preserving whitespace and escaping HTML so they are easier to read.
	 */
	function wp_dump() {
		$is_xdebug = false;
		if ( function_exists( 'xdebug_dump_superglobals' ) ) {
			$is_xdebug = true;
		}
		foreach ( func_get_args() as $arg ) {
			// If xdebug is installed let it do it's own thing...
			if ( $is_xdebug ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
				var_dump( $arg );
				continue;
			}
			echo '<xmp>';
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
			var_dump( $arg );
			echo '</xmp>';
		}
	}
endif;

if ( ! function_exists( 'wp_log' ) ) :
	/**
	 * A better error_log() function
	 */
	function wp_log() {
		foreach ( func_get_args() as $arg ) {
			if ( is_array( $arg ) || is_object( $arg ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log(
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
					print_r( $arg, true )
				);
			} else {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $arg );
			}
		}
	}
endif;
