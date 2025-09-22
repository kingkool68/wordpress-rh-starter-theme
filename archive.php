<?php
$context = array(
	'archive_items' => RH_Posts::render_archive_items_from_wp_query(),
	'pagination'    => RH_Pagination::render_from_wp_query(),
);
Sprig::out( 'archive.twig', $context );
