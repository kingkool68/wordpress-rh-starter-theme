<?php
$default_image = RH_Media::render(
	array(
		'image_src' => 'https://dummyimage.com/370x250.png',
	)
);
$default_item  = array(
	'title' => 'A Post Archive Item Title',
	'date'  => 'now',
	'url'   => 'https://example.com',
	'image' => $default_image,
);

$basic_args = wp_parse_args( array(), $default_item );

$basic_items = array();
for ( $i = 0; $i < 5; $i++ ) {
	$basic_items[] = RH_Posts::render_archive_item( $basic_args );
}

$context = array(
	'basic' => implode( "\n", $basic_items ),
);
Sprig::out( 'styleguide-post-archive-items.twig', $context );
