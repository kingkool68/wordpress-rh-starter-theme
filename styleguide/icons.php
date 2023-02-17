<?php

$context = array(
	'icons' => RH_SVG::get_all_icons(),
);
Sprig::out( 'styleguide-icons.twig', $context );
