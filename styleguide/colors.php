<?php

$file = get_template_directory() . '/assets/scss/base/_colors.scss';

$context = array(
	'colors' => WP_Styleguide::get_sass_colors( $file ),
);
Sprig::out( 'styleguide-colors.twig', $context );
