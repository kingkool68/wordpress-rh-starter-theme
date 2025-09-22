<?php
global $wp;
$current_url = home_url( add_query_arg( array(), $wp->request ) );

$default_args = array(
	'current_page' => 1,
	'total_pages'  => 20,

	// Need to fake the base permalink structure
	'base'         => $current_url . '/%_%',
	'format'       => 'page/%#%/',
);

$start_args = wp_parse_args( array(), $default_args );

$middle_args = wp_parse_args(
	array(
		'current_page' => floor( $default_args['total_pages'] / 2 ),
	),
	$default_args
);

$end_args = wp_parse_args(
	array(
		'current_page' => $default_args['total_pages'],
	),
	$default_args
);

$nav_args = array(
	'next'         => 'The Next Chapter',
	'next_url'     => 'https://example.com',
	'previous'     => 'The Previous Chapter',
	'previous_url' => 'https://example.com',
);
$context  = array(
	'pagination_start'  => RH_Pagination::render( $start_args ),
	'pagination_middle' => RH_Pagination::render( $middle_args ),
	'pagination_end'    => RH_Pagination::render( $end_args ),
);
Sprig::out( 'styleguide-pagination.twig', $context );
