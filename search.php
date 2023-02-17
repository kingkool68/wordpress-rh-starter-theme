<?php
global $wp_query;
$result_label = 'results';
if ( 1 === intval( $wp_query->found_posts ) ) {
	$result_label = 'result';
}

$escaped = false;
$context = array(
	'site_url'           => get_site_url(),
	'the_search_results' => RH_Posts::render_archive_items_from_wp_query(),
	// 'the_search_query'   => get_search_query( $escaped ),
	'results_found'      => number_format( $wp_query->found_posts ),
	'result_label'       => $result_label,
	'pagination'         => RH_Pagination::render_from_wp_query(),
);

Sprig::out( 'search.twig', $context );
