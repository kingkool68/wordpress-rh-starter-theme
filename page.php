<?php
$the_title  = '';
$hide_title = get_field( 'page_hide_title' );
if ( ! $hide_title ) {
	$the_title = apply_filters( 'the_title', get_the_title() );
}

$flash_message = '';
if ( isset( $_GET['thank-you'] ) ) {
	$flash_message = 'Thanks for reaching out. We\'ll be in touch very soon!';
}

$context = array(
	'flash_message'     => $flash_message,
	'the_title'         => $the_title,
	'the_content'       => apply_filters( 'the_content', get_the_content() ),
	'after_the_content' => Sprig::do_action( 'rh/page/after_the_content' ),
	'after_the_page' => Sprig::do_action( 'rh/page/after_the_page' ),
);
Sprig::out( 'page.twig', $context );
