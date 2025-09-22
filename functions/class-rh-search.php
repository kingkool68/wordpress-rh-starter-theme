<?php
/**
 * Improving WordPress' search capabilities
 */
class RH_Search {

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
	 * Hook in to WordPress via actions
	 */
	public function setup_actions() {}

	/**
	 * Hook in to WordPress via filters
	 */
	public function setup_filters() {}

	/**
	 * Extract the locations of the search query within a full text
	 *
	 * @param array  $words The words to search within the full text for
	 * @param string $full_text The ful ltext to search through
	 */
	public static function extract_locations( $words = array(), $full_text = '' ) {
		$locations = array();
		foreach ( $words as $word ) {
			$wordlen = strlen( $word );
			$loc     = stripos( $full_text, $word );
			while ( $loc !== false ) {
				$locations[] = $loc;
				$loc         = stripos( $full_text, $word, $loc + $wordlen );
			}
		}
		$locations = array_unique( $locations );
		sort( $locations );

		return $locations;
	}

	/**
	 * Work out which is the most relevant portion to display.
	 * This is done by looping over each match and finding
	 * the smallest distance between two found strings.
	 * The idea being that the closer the terms are the better match
	 * the snippet would be. When checking for matches we only change the location
	 * if there is a better match. The only exception is where
	 * we have only two matches in which case we just take the first as will be equally distant.
	 *
	 * @param array   $locations The locations
	 * @param integer $prev_count The previous count
	 */
	public static function determine_snippet_location( $locations = array(), $prev_count = 0 ) {
		if ( empty( $locations ) ) {
			return 0;
		}
		// If we only have 1 match we dont actually do the for loop so set to the first
		$startpos     = $locations[0];
		$loc_count    = count( $locations );
		$smallestdiff = PHP_INT_MAX;

		// If we only have 2 skip as its probably equally relevant
		if ( $loc_count > 2 ) {
			// skip the first as we check 1 behind
			for ( $i = 1; $i < $loc_count; $i++ ) {
				if ( $i === $loc_count - 1 ) { // at the end
					$diff = $locations[ $i ] - $locations[ $i - 1 ];
				} else {
					$diff = $locations[ $i + 1 ] - $locations[ $i ];
				}

				if ( $smallestdiff > $diff ) {
					$smallestdiff = $diff;
					$startpos     = $locations[ $i ];
				}
			}
		}

		$startpos = 0;
		if ( $startpos > $prev_count ) {
			$startpos = $startpos - $prev_count;
		}
		return $startpos;
	}

	/**
	 * Undocumented function
	 *
	 * @link https://boyter.org/2013/04/building-a-search-result-extract-generator-in-php/
	 *
	 * @param array   $words The words to search for
	 * @param string  $full_text The full text to search through
	 * @param integer $excerpt_length The desired search excerpt length in characters
	 * @param integer $prev_count How many characters to display before the match for context
	 * @param string  $indicator A trailing character
	 */
	public static function get_search_excerpt( $words = array(), $full_text = '', $excerpt_length = 300, $prev_count = 50, $indicator = '...' ) {

		$textlength = strlen( $full_text );
		if ( $textlength <= $excerpt_length ) {
			return $full_text;
		}

		$locations = array();
		foreach ( $words as $word ) {
			$wordlen = strlen( $word );
			$loc     = stripos( $full_text, $word );
			while ( $loc !== false ) {
				$locations[] = $loc;
				$loc         = stripos( $full_text, $word, $loc + $wordlen );
			}
		}
		$locations = array_unique( $locations );
		sort( $locations );

		$startpos = static::determine_snippet_location( $locations, $prev_count );

		// if we are going to snip too much...
		if ( $textlength - $startpos < $excerpt_length ) {
			$startpos = $startpos - ( $textlength - $startpos ) / 2;
		}

		$relevant_text = substr( $full_text, $startpos, $excerpt_length );

		// check to ensure we dont snip the last word if thats the match
		if ( $startpos + $excerpt_length < $textlength ) {
			$relevant_text = substr( $relevant_text, 0, strrpos( $relevant_text, ' ' ) ) . $indicator; // remove last word
		}

		// If we trimmed from the front add ...
		if ( $startpos !== 0 ) {
			$relevant_text = $indicator . substr( $relevant_text, strpos( $relevant_text, ' ' ) + 1 ); // remove first word
		}

		return $relevant_text;
	}

	/**
	 * Get the search excerpt for a given post
	 *
	 * @param integer $post The post to fetch the content from
	 * @param array   $args Args to modify how the search excerpt works
	 */
	public static function get_search_excerpt_by_post( $post = 0, $args = array() ) {
		$post = get_post( $post );

		$defaults             = array(
			'search_query' => get_search_query(),
		);
		$args                 = wp_parse_args( $args, $defaults );
		$args['search_query'] = trim( $args['search_query'] );
		$highlight_words      = array( $args['search_query'] );
		$args['search_query'] = explode( ' ', $args['search_query'] );
		$highlight_words      = array_merge( $highlight_words, $args['search_query'] );
		if ( empty( $args['search_query'] ) ) {
			return $post->post_excerpt;
		}

		$text = apply_filters( 'the_content', $post->post_content );
		$text = wp_strip_all_tags( $text );

		$better_excerpt = static::get_search_excerpt( $args['search_query'], $text );
		unset( $text );
		$better_excerpt = static::highlight_string( $better_excerpt, $highlight_words );
		return $better_excerpt;
	}

	/**
	 * Highlight one or more words in a string respecting case
	 *
	 * @param string $the_string The string to match words in
	 * @param array  $words The words to match in $the_string
	 */
	public static function highlight_string( $the_string = '', $words = array() ) {
		if ( ! is_array( $words ) ) {
			$words = explode( ' ', $words );
		}
		if ( ! is_array( $words ) || ! is_string( $the_string ) || empty( $words ) ) {
			return false;
		}

			$words = implode( '|', $words );
			return preg_replace( '@\b(' . $words . ')\b@si', '<mark>$1</mark>', $the_string );
	}
}
RH_Search::get_instance();
