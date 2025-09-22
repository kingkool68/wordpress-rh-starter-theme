<?php
$defaults = array(
	'link_url'   => '',
	'link_attr'  => array(),
	'image_src'  => 'https://dummyimage.com/800x485.png',
	'caption'    => 'This is a caption.',
	'image_attr' => array(
		'alt'   => 'This is alt text!',
		'class' => array( 'foo', 'bar' ),
	),
);

$basic_args      = wp_parse_args( array(), $defaults );
$no_caption_args = wp_parse_args(
	array(
		'caption' => '',
	),
	$defaults
);
$with_link_args  = wp_parse_args(
	array(
		'link_url'  => 'https://example.com',
		'link_attr' => array(
			'class'  => array( 'figure-link', 'foo' ),
			'target' => '_blank',
		),
	),
	$defaults
);
$context         = array(
	'basic'      => RH_Media::render( $basic_args ),
	'no_caption' => RH_Media::render( $no_caption_args ),
	'with_link'  => RH_Media::render( $with_link_args ),
);
Sprig::out( 'styleguide-images.twig', $context );
