<?php
/**
 * Helper functions that do various things
 */
class RH_Helpers {

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
	 * Simplify generating taxonomy labels by only needing to enter a singular and plural verison
	 *
	 * @param  string $singular  The singular version of the taxonomy label
	 * @param  string $plural    The plural version of the taxonomy label
	 * @param  array  $overrides Specific labels to override that might not fit this pattern
	 * @return array             Taxonomy labels
	 */
	public static function generate_taxonomy_labels( $singular = '', $plural = '', $overrides = array() ) {
		$lc_plural   = strtolower( $plural );
		$uc_plural   = ucwords( $lc_plural );
		$lc_singular = strtolower( $singular );
		$uc_singular = ucwords( $lc_singular );

		$labels = array(
			'name'                       => $uc_plural,
			'singular_name'              => $uc_singular,
			'menu_name'                  => $uc_plural,
			'all_items'                  => 'All ' . $uc_plural,
			'parent_item'                => 'Parent ' . $uc_singular,
			'parent_item_colon'          => 'Parent ' . $uc_singular . ':',
			'new_item_name'              => 'New ' . $uc_singular . ' Name',
			'add_new_item'               => 'Add New ' . $uc_singular,
			'edit_item'                  => 'Edit ' . $uc_singular,
			'update_item'                => 'Update ' . $uc_singular,
			'view_item'                  => 'View ' . $uc_singular,
			'separate_items_with_commas' => 'Separate ' . $lc_plural . ' with commas',
			'add_or_remove_items'        => 'Add or remove ' . $lc_plural,
			'choose_from_most_used'      => 'Choose from the most used',
			'popular_items'              => 'Popular ' . $uc_plural,
			'search_items'               => 'Search ' . $uc_plural,
			'not_found'                  => 'Not Found',
			'no_terms'                   => 'No ' . $lc_plural,
			'items_list'                 => ucfirst( $lc_plural ) . ' list',
			'items_list_navigation'      => ucfirst( $lc_plural ) . ' list navigation',
		);
		return wp_parse_args( $overrides, $labels );
	}

	/**
	 * Simplify generating post type labels by only needing to enter a singular and plural verison
	 *
	 * @param  string $singular  The singular version of the post type label
	 * @param  string $plural    The plural version of the post type label
	 * @param  array  $overrides Specific labels to override that might not fit this pattern
	 * @return array             Post type labels
	 */
	public static function generate_post_type_labels( $singular = '', $plural = '', $overrides = array() ) {
		$lc_plural   = strtolower( $plural );
		$uc_plural   = ucwords( $lc_plural );
		$lc_singular = strtolower( $singular );
		$uc_singular = ucwords( $lc_singular );

		$labels = array(
			'name'                  => $uc_plural,
			'singular_name'         => $uc_singular,
			'menu_name'             => $uc_plural,
			'name_admin_bar'        => $uc_singular,
			'archives'              => $uc_singular . ' Archives',
			'attributes'            => $uc_singular . ' Attributes',
			'parent_item_colon'     => 'Parent ' . $uc_singular . ':',
			'all_items'             => 'All ' . $uc_plural,
			'add_new_item'          => 'Add New ' . $uc_singular,
			'add_new'               => 'Add New',
			'new_item'              => 'New ' . $uc_singular,
			'edit_item'             => 'Edit ' . $uc_singular,
			'update_item'           => 'Update ' . $uc_singular,
			'view_item'             => 'View ' . $uc_singular,
			'view_items'            => 'View ' . $uc_plural,
			'search_items'          => 'Search ' . $uc_singular,
			'not_found'             => 'Not found',
			'not_found_in_trash'    => 'Not found in Trash',
			'featured_image'        => 'Featured Image',
			'set_featured_image'    => 'Set featured image',
			'remove_featured_image' => 'Remove featured image',
			'use_featured_image'    => 'Use as featured image',
			'insert_into_item'      => 'Insert into ' . $lc_singular,
			'uploaded_to_this_item' => 'Uploaded to this ' . $lc_singular,
			'items_list'            => ucfirst( $lc_plural ) . ' list',
			'items_list_navigation' => ucfirst( $lc_plural ) . ' list navigation',
			'filter_items_list'     => 'Filter ' . $lc_plural . ' list',
		);
		return wp_parse_args( $overrides, $labels );
	}

	/**
	 * Calculate various formats for a given date
	 *
	 * @param  string $date The date to convert to other formats
	 * @return object       The date in other formats
	 */
	public static function get_date_values( $date = '' ) {
		if ( empty( $date ) ) {
			return (object) array(
				'machine_date'     => '',
				'display_date'     => '',
				'display_time'     => '',
				'display_datetime' => '',
			);
		}
		$timezone_string = get_option( 'timezone_string' );
		if ( empty( $timezone_string ) ) {
			$timezone_string = 'Etc/GMT';
		}
		$timezone = new DateTimeZone( $timezone_string );
		$date     = new DateTime( $date, $timezone );
		return (object) array(
			'machine_date'     => $date->format( DATE_W3C ),
			'display_date'     => $date->format( get_option( 'date_format' ) ),
			'display_time'     => $date->format( get_option( 'time_format' ) ),
			'display_datetime' => $date->format( get_option( 'date_format' ) ) . ' ' . $date->format( get_option( 'time_format' ) ),
		);
	}

	/**
	 * Generate a string of HTML attributes
	 *
	 * @link https://gist.github.com/mcaskill/0177f151e39b94ee2629f06d72c4b65b
	 *
	 * @param   array         $attr      Associative array of attribute names and values.
	 * @param   callable|null $callback  Callback function to escape values for HTML attributes.
	 *                                   Defaults to `htmlspecialchars()`.
	 * @return  string                    Returns a string of HTML attributes.
	 */
	public static function build_html_attributes( array $attr, callable $callback = null ) {
		if ( ! count( $attr ) ) {
			return '';
		}
		$html = array_map(
			function ( $val, $key ) use ( $callback ) {
				if ( is_bool( $val ) ) {
					return ( $val ? $key : '' );
				} elseif ( isset( $val ) ) {
					if ( $val instanceof Closure ) {
						$val = $val();
					} elseif ( $val instanceof JsonSerializable ) {
						$val = wp_json_encode(
							$val->jsonSerialize(),
							( JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
						);
					} elseif ( is_callable( array( $val, 'toArray' ) ) ) {
						$val = $val->toArray();
					} elseif ( is_callable( array( $val, '__toString' ) ) ) {
						$val = strval( $val );
					}
					if ( is_array( $val ) ) {
						if ( function_exists( 'is_blank' ) ) {
							$filter = function ( $var ) {
								return ! is_blank( $var );
							};
						} else {
							$filter = function ( $var ) {
								return ! empty( $var ) || is_numeric( $var );
							};
						}
						$val = implode( ' ', array_filter( $val, $filter ) );
					}
					if ( is_callable( $callback ) ) {
						$val = call_user_func( $callback, $val );
					} elseif ( function_exists( 'esc_attr' ) ) {
						$val = esc_attr( $val );
					} else {
						$val = htmlspecialchars( $val, ENT_QUOTES );
					}
					if ( is_string( $val ) ) {
						return sprintf( '%1$s="%2$s"', $key, $val );
					}
				}
			},
			$attr,
			array_keys( $attr )
		);
		return implode( ' ', $html );
	}

	/**
	 * Build a CSS class string
	 *
	 * @param string|array $css_class The CSS class names to start with
	 * @param string|array $to_add CSS class names to add
	 * @param string|array $to_remove CSS class names to remove
	 */
	public static function css_class( $css_class = '', $to_add = array(), $to_remove = array() ) {
		if ( ! is_array( $css_class ) ) {
			$css_class = explode( ' ', $css_class );
		}
		if ( ! is_array( $to_add ) ) {
			$to_add = explode( ' ', $to_add );
		}
		if ( ! is_array( $to_remove ) ) {
			$to_remove = explode( ' ', $to_remove );
		}

		if ( ! empty( $to_add ) ) {
			$css_class = array_merge( $css_class, $to_add );
		}

		if ( ! empty( $to_remove ) ) {
			$css_class = array_diff( $css_class, $to_remove );
		}

		$css_class = array_filter( $css_class ); // Remove empty items
		$css_class = implode( ' ', $css_class );
		return trim( $css_class );
	}

	/**
	 * Convert an HTML string to a plain text version
	 *
	 * @param  string  $html              The HTML to convert to plain text
	 * @param  boolean $convert_newlines Whether to replace end of line characters with carriage returns
	 * @return string                    Plain version of the string
	 */
	public static function html2plain( $html = '', $convert_newlines = false ) {
		$html = wp_kses( $html, array() );
		// Now that we've removed some HTML elements we need to de-dupe new lines characters to remove ugly large gaps in the text
		$html = preg_replace( '#\R{3,}#', PHP_EOL . PHP_EOL, $html );
		if ( $convert_newlines ) {
			$html = str_replace( PHP_EOL, "\\r\\n", $html );
		}

		return $html;
	}

	/**
	 * Get the IP address of the client requesting the page based off of various headers
	 */
	public static function get_client_ip() {
		$ip = wp_unslash( $_SERVER['REMOTE_ADDR'] );
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = wp_unslash( $_SERVER['HTTP_CLIENT_IP'] );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		}
		$ip = filter_var( $ip, FILTER_VALIDATE_IP );
		return $ip;
	}

	/**
	 * Get the current URL of the request as detected by WordPress
	 */
	public static function get_current_url() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		global $wp;
		$current_url = home_url( add_query_arg( array(), $wp->request ) );
		$current_url = trailingslashit( $current_url );
		if ( ! empty( $_GET ) ) {
			$current_url = add_query_arg( $_GET, $current_url );
		}
		return $current_url;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Base64 encode a string in a URL safe way
	 *
	 * @param string $string The string to encode
	 */
	public static function base64_encode_url( $string ) {
		$string = gzdeflate( $string );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return str_replace( array( '+', '/', '=' ), array( '-', '_', '' ), base64_encode( $string ) );
	}

	/**
	 * Base64 decode a string in a URL safe way
	 *
	 * @param string $string The string to decode
	 */
	public static function base64_decode_url( $string ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$string = base64_decode( str_replace( array( '-', '_' ), array( '+', '/' ), $string ) );
		return gzinflate( $string );
	}

	/**
	 * Join a string with a natural language conjunction at the end
	 *
	 * @link https://stackoverflow.com/a/25057951/1119655
	 *
	 * @param   array  $list         The list of strings to join
	 * @param   string $conjunction  The word to use to separate the last item
	 * @param   string $separator    The character to separate each item with
	 *
	 * @return  string               The joined string
	 */
	public static function natural_language_join( $list = array(), $conjunction = 'and', $separator = ', ' ) {
		$last = array_pop( $list );
		if ( $list ) {
			return implode( $separator, $list ) . ' ' . $conjunction . ' ' . $last;
		}
		return $last;
	}

	/**
	 * Remove http:// or https:// from a given URL
	 *
	 * @param  string $url The URL to be modified
	 * @return string      The modified URL
	 */
	public static function strip_http_from_url( $url = '' ) {
		$url = str_replace( array( 'http://', 'https://' ), '', $url );
		return $url;
	}
}
RH_Helpers::get_instance();
