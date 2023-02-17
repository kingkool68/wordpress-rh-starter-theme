<?php
// Provide a short circuit to prevent showing the footer in certain conditions
$show_header = apply_filters( 'rh/header/show_header', true );
if ( ! $show_header ) {
	return;
}

// Provide a way to disable showing the main nav in certain situations
$show_nav      = apply_filters( 'rh/header/show_nav', true );
$main_nav      = '';
$main_nav_ctas = '';
if ( $show_nav ) {
	$main_nav_args = array(
		'theme_location' => 'main',
		'container'      => false,
		'items_wrap'     => '%3$s', // Prevents wrapping in any markup
		'echo'           => false,
	);
	$main_nav      = wp_nav_menu( $main_nav_args );
	wp_enqueue_script( 'rh-main-nav' );
}

$context = array(
	'site_url'      => get_site_url(),
	'logo'          => '',
	'main_nav'      => $main_nav,
	'close_icon'    => RH_SVG::get_icon( 'close' ),
	'menu_icon'     => RH_SVG::get_icon( 'menu' ),
);
Sprig::out( 'header.twig', $context );
