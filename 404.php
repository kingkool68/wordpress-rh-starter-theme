<?php
$context = array(
	'img_url' => get_template_directory_uri() . '/assets/img',
	'home_url' => get_site_url(),
);
Sprig::out( '404.twig', $context );
