<?php

$queried_object = get_queried_object();

$the_title = 'Latest From Our Blog';

$the_search_query = get_search_query();
if ( ! empty( $the_search_query ) ) {
	$the_search_query = wp_trim_words( $the_search_query, $num_words = 5, $more = '&hellip;' );
	$the_title        = 'Search Results for <em class="the-search-query">' . $the_search_query . '</em>';
}

$context = array(
	'the_title'        => apply_filters( 'the_title', $the_title ),
	'the_search_query' => $the_search_query,
	'blog_posts'       => RH_Posts::render_archive_items_from_wp_query(),
	'the_pagination'   => RH_Pagination::render_from_wp_query(),
);
Sprig::out( 'home.twig', $context );
