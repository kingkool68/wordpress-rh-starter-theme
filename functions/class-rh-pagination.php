<?php
/**
 * Methods for handling pagination data and formatting
 */
class RH_Pagination {
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
	 * Render pagination
	 *
	 * @param  array $args Arguments to modify what is rendered
	 * @return string       HTML
	 */
	public static function render( $args = array() ) {
		$data     = static::get_data( $args );
		$defaults = array(
			'next_url'           => $data->next_url,
			'next_page_num'      => $data->next_page_num,
			'next_text'          => 'Next <span class="screen-reader-text">Page</span>',
			'next_icon'          => '',
			'next_icon_slug'     => 'right-arrow',
			'previous_url'       => $data->previous_url,
			'previous_text'      => 'Previous <span class="screen-reader-text">Page</span>',
			'previous_icon'      => '',
			'previous_icon_slug' => 'right-arrow',
			'previous_page_num'  => $data->previous_page_num,
			'current'            => $data->current_page,
			'links'              => $data->links,
			'total_pages_num'    => $data->total_pages_num,
			'total_pages_url'    => $data->total_pages_url,
			'start_num'          => $data->start_num,
		);
		$context  = wp_parse_args( $args, $defaults );
		if ( empty( $context['next_icon'] ) && ! empty( $context['next_icon_slug'] ) ) {
			$context['next_icon'] = RH_SVG::get_icon( $context['next_icon_slug'] );
		}
		if ( empty( $context['previous_icon'] ) && ! empty( $context['previous_icon_slug'] ) ) {
			$context['previous_icon'] = RH_SVG::get_icon( $context['previous_icon_slug'] );
		}

		return Sprig::render( 'pagination.twig', $context );
	}

	/**
	 * Render pagination from a WP Query
	 *
	 * @param  WP_Query $the_query The query object to use
	 * @param  array    $args       Arguments to modify what is rendered
	 * @return string              HTML
	 * @throws Exception           If $the_query is not a WP_Query object
	 */
	public static function render_from_wp_query( $the_query = false, $args = array() ) {
		global $wp_query;
		if ( ! $the_query ) {
			$the_query = $wp_query;
		}
		if ( ! $the_query instanceof WP_Query ) {
			throw new Exception( '$the_query is not a WP_Query object!' );
		}
		if ( empty( $the_query->max_num_pages ) || $the_query->max_num_pages <= 1 ) {
			return;
		}
		$args['total_pages'] = $the_query->max_num_pages;
		return static::render( $args );
	}

	/**
	 * Get normalized data needed for rendering pagination via a wp_query
	 *
	 * @param  WP_Query $the_query The query object to use
	 * @param  array    $args       Arguments to modify what is rendered
	 * @return object              Normalized pagination data
	 * @throws Exception           If $the_query is not a WP_Query object
	 */
	public static function get_data_from_wp_query( $the_query = false, $args = array() ) {
		global $wp_query;
		if ( ! $the_query ) {
			$the_query = $wp_query;
		}
		if ( ! $the_query instanceof WP_Query ) {
			throw new Exception( '$the_query is not a WP_Query object!' );
		}
		$args['total_pages'] = $the_query->max_num_pages;
		return static::get_data( $args );
	}

	/**
	 * Get normalized data needed for rendering pagination
	 *
	 * @param  array $args The data to normalize
	 * @return object       Normalized pagination data
	 */
	public static function get_data( $args = array() ) {
		global $wp_rewrite;
		$output = array(
			'total_pages_num'   => 0,
			'total_pages_url'   => false,
			'current_page'      => 1,
			'start_num'         => 1,
			'next_url'          => false,
			'next_page_num'     => -1,
			'previous_url'      => false,
			'previous_page_num' => 1,
			'links'             => array(),
		);

		// Setting up default values based on the current URL
		$current_page = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
		$pagenum_link = html_entity_decode( get_pagenum_link() );
		$url_parts    = explode( '?', $pagenum_link );

		// Append the format placeholder to the base URL
		$pagenum_link = trailingslashit( $url_parts[0] ) . '%_%';

		// URL base depends on permalink settings
		$format = '';
		if ( $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ) {
			$format = 'index.php';
		}
		if ( $wp_rewrite->using_permalinks() ) {
			$format .= user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' );
		} else {
			$format .= '?paged=%#%';
		}
		$defaults = array(
			'base'         => $pagenum_link, // http://example.com/all_posts.php%_% : %_% is replaced by format (below)
			'format'       => $format, // ?page=%#% : %#% is replaced by the page number
			'total_pages'  => 1,
			'current_page' => $current_page,
			'show_all'     => false,
			'range'        => 4,  // How many pagination links to show (should be an odd number)
			'add_args'     => array(), // Array of query args to add
		);
		$args     = wp_parse_args( $args, $defaults );

		// Who knows what else people pass in $args
		$output['total_pages_num'] = intval( $args['total_pages'] );
		if ( $output['total_pages_num'] < 2 ) {
			$output = apply_filters( 'rh/pagination/get_data', $output, $args );
			return (object) $output;
		}
		$current_page           = absint( $args['current_page'] );
		$total_pages_num        = absint( $args['total_pages'] );
		$output['current_page'] = $current_page;
		$range                  = intval( $args['range'] );
		// Out of bounds?  Make it the default
		if ( $range < 1 ) {
			$range = $defaults['range'];
		}
		if ( ! is_array( $args['add_args'] ) ) {
			$args['add_args'] = array();
		}

		// Merge additional query vars found in the original URL into 'add_args' array
		if ( isset( $url_parts[1] ) ) {
			// Find the format argument
			$format       = explode( '?', str_replace( '%_%', $args['format'], $args['base'] ) );
			$format_query = isset( $format[1] ) ? $format[1] : '';
			wp_parse_str( $format_query, $format_args );

			// Find the query args of the requested URL
			wp_parse_str( $url_parts[1], $url_query_args );

			// Remove the format argument from the array of query arguments, to avoid overwriting custom format
			foreach ( $format_args as $format_arg => $format_arg_value ) {
				unset( $url_query_args[ $format_arg ] );
			}
			$args['add_args'] = array_merge( $args['add_args'], urlencode_deep( $url_query_args ) );
		}

		$output['total_pages_url'] = static::get_pagination_link( $output['total_pages_num'], $args['base'], $args['format'], $args['add_args'] );

		$previous_page_num = $current_page - 1;
		if ( $previous_page_num > 0 ) {
			$output['previous_page_num'] = $previous_page_num;
			$output['previous_url']      = static::get_pagination_link( $previous_page_num, $args['base'], $args['format'], $args['add_args'] );
		}
		$next_page_num = $current_page + 1;
		if ( $next_page_num <= $total_pages_num ) {
			$output['next_page_num'] = $next_page_num;
			$output['next_url']      = static::get_pagination_link( $next_page_num, $args['base'], $args['format'], $args['add_args'] );
		}
		if ( $current_page > ( $total_pages_num - $range ) ) {
			// We're near the end
			$start                     = max( $total_pages_num - $range + 1, 1 );
			$end                       = $total_pages_num;
			$output['total_pages_url'] = '';
		} elseif ( $current_page < $range ) {
			// We're near the beginning
			$start = 1;
			$end   = $range;
		} else {
			// The rest
			$start = $current_page - floor( $range / 2 );
			$end   = $current_page + floor( $range / 2 );
		}
		for ( $i = $start; $i <= $end; $i++ ) {
			$is_current = false;
			if ( intval( $i ) === intval( $current_page ) ) {
				$is_current = true;
			}
			$output['links'][] = (object) array(
				'num'        => intval( $i ),
				'url'        => static::get_pagination_link( $i, $args['base'], $args['format'], $args['add_args'] ),
				'is_current' => $is_current,
			);
		}
		$output['range']     = $range;
		$output['start_num'] = intval( $start );
		$output              = apply_filters( 'rh/pagination/get_data', $output, $args );
		return (object) $output;
	}

	/**
	 * Get an individual pagination link
	 *
	 * @param  integer $num        The pagination number
	 * @param  string  $base       Base URL for pagination
	 * @param  string  $format     The pagination format
	 * @param  array   $query_args Query arguments to append to the end of a link
	 * @param  string  $fragment   Fragment to add to the end of a link
	 * @return string              The link URL
	 */
	public static function get_pagination_link( $num = 0, $base = '', $format = '', $query_args = array(), $fragment = '' ) {
		if ( 1 === $num ) {
			$format = '';
		}
		$link = str_replace( '%_%', $format, $base );
		$link = str_replace( '%#%', $num, $link );

		if ( $query_args ) {
			$link = add_query_arg( $query_args, $link );
		}
		return $link .= $fragment;
	}
}

RH_Pagination::get_instance();
