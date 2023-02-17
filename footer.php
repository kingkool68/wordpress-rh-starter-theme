<?php
// Provide a short circuit to prevent showing the footer in certain conditions
$show_footer = apply_filters( 'rh/footer/show_footer', true );
if ( ! $show_footer ) {
	return;
}

$context = array(
	'site_url'   => get_site_url(),
	'site_title' => get_bloginfo( 'name' ),
	'year'       => gmdate( 'Y' ),
);
Sprig::out( 'footer.twig', $context );
