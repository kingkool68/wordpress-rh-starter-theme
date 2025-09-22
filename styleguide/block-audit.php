<?php
$blocks_that_have_styleguides = array();
// Loop over all of the files in the /styleguide/ directory to extract blocknames
$directory = get_template_directory() . '/styleguide/';
$iterator  = new DirectoryIterator( $directory );
foreach ( $iterator as $file ) {
	if ( ! $file->isFile() ) {
		continue;
	}
	$parts = explode( '.', $file->getFilename() );
	// Make sure we only analyze PHP files
	if ( empty( $parts[1] ) || 'php' !== strtolower( $parts[1] ) ) {
		continue;
	}
	$the_file = file_get_contents( $file->getPathname() );
	if ( empty( $the_file ) || ! is_string( $the_file ) ) {
		continue;
	}
	// Extract the block name
	preg_match( "/'block_name'\s+=>\s'(.+)',/i", $the_file, $matches );
	if ( ! empty( $matches[1] ) ) {
		$block_name = $matches[1];
		if ( ! str_starts_with( $block_name, 'acf/' ) ) {
			$block_name = 'acf/' . $block_name;
		}
		$blocks_that_have_styleguides[ $block_name ] = '/styleguide/' . $parts[0] . '/';
	}
}

$table_data  = array();
$block_types = WP_Block_Type_Registry::get_instance()->get_all_registered();
foreach ( $block_types as $key => $block ) {
	if ( ! str_starts_with( $key, 'acf/' ) ) {
		continue;
	}
	$block_name     = $key;
	$styleguide_url = '';
	if ( ! empty( $blocks_that_have_styleguides[ $key ] ) ) {
		$styleguide_url = $blocks_that_have_styleguides[ $key ];
	}
	$table_data[] = array(
		'title'          => $block->title,
		'block_name'     => $block_name,
		'styleguide_url' => $styleguide_url,
	);
}
$table_data = wp_list_sort( $table_data, 'block_name' );

$number_of_styleguide_pages = 0;
foreach ( $table_data as $row ) {
	if ( ! empty( $row['styleguide_url'] ) ) {
		$number_of_styleguide_pages++;
	}
}

$context = array(
	'the_breadcrumbs'            => RH_Breadcrumbs::render(
		array(
			'items' => array(
				array(
					'text' => 'Styleguide',
					'url'  => get_site_url() . '/styleguide/',
				),
				array(
					'text' => 'Block Audit',
				),
			),
		)
	),
	'table_data'                 => $table_data,
	'site_url'                   => get_site_url(),
	'number_of_styleguide_pages' => $number_of_styleguide_pages,
	'total_blocks'               => count( $table_data ),
);
Sprig::out( 'styleguide-block-audit.twig', $context );
