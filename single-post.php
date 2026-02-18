<?php
setup_postdata( get_post() );

$date = RH_Helpers::get_date_values( $post->post_date );

$context = array(
	'the_title'      => get_the_title(),
	'display_date'   => $date->display_date,
	'machine_date'   => $date->machine_date,
	'featured_image' => RH_Media::render_image_from_post(
		$post->ID,
		array(
			'link_url'   => get_the_post_thumbnail_url( $post->ID, 'original' ),
			'image_attr' => array(
				'class' => 'the-featured-image',
			),
		)
	),
	'the_content'    => apply_filters( 'the_content', get_the_content() ),
);
Sprig::out( 'single-post.twig', $context );
