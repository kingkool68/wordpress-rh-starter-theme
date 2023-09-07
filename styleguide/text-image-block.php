<?php
$default_image = RH_Media::render(
	array(
		'image_src'  => 'https://dummyimage.com/1024x512.png',
		'image_attr' => array(
			'width'  => 1024,
			'height' => 512,
		),
	)
);

$default_args = array(
	'image'            => $default_image,
	'image_alignment'  => 'left',
	'image_proportion' => '',
	'bg_color'         => 'gray',
	'kicker'           => 'Kicker',
	'headline'         => 'Headline',
	'headline_url'     => 'https://example.com',
);

$basic_args         = wp_parse_args( array(), $default_args );
$right_aligned_args = wp_parse_args(
	array(
		'image_alignment' => 'right',
	),
	$default_args
);

$args = array(
	'block_name'           => 'rh-text-image',
	'the_title'            => 'Text Image Block',
	'the_description'      => 'Derp.',
	'examples'             => array(
		'basic'         => RH_Text_Image_Block::render( $basic_args ),
		'right_aligned' => RH_Text_Image_Block::render( $right_aligned_args ),
	),
	'block_directory_name' => 'text-image-block',
);
get_template_part( 'styleguide', 'block', $args );
