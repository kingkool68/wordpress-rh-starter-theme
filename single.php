<?php
setup_postdata( get_post() );

$date_values = RH_Helpers::get_date_values( $post->post_date );
$context     = array(
	'the_title'        => get_the_title(),
	'the_content'      => apply_filters( 'the_content', get_the_content() ),
	'display_date'     => $date_values->display_date,
	'display_datetime' => $date_values->display_datetime,
	'machine_date'     => $date_values->machine_date,
);
Sprig::out( 'single.twig', $context );
