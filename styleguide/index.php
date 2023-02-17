<?php
$context = array(
	'site_name' => get_bloginfo( 'name' ),
);
Sprig::out( 'styleguide-index.twig', $context );
