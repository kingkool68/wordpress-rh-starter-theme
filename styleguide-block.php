<?php
// add_filter( 'wp_robots', 'wp_robots_no_robots' );
add_filter(
	'rank_math/frontend/robots',
	function ( $robots ) {
		$robots['follow'] = 'nofollow';
		$robots['index']  = 'noindex';
		return $robots;
	}
);

$defaults = array(
	'block_name'           => '',
	'the_title'            => '',
	'the_description'      => '',
	'examples'             => array(),
	'files'                => array(),
	'block_directory_name' => '',
	'before_content'       => '',
);
$args     = wp_parse_args( $args, $defaults );

$posts_containing_block = array();
if ( ! empty( $args['block_name'] ) ) {
	if ( ! str_starts_with( $args['block_name'], 'acf/' ) ) {
		$args['block_name'] = 'acf/' . $args['block_name'];
	}
	$posts_containing_block_query_args = array(
		'post_type'              => 'any',
		'post_status'            => 'publish',
		'posts_per_page'         => 999,
		'orderby'                => 'relevance',
		's'                      => $args['block_name'],

		// For performance
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	);
	$posts_containing_block_query      = get_posts( $posts_containing_block_query_args );
	foreach ( $posts_containing_block_query as $found_post ) {
		$post_type_obj            = get_post_type_object( $found_post->post_type );
		$posts_containing_block[] = array(
			'title'           => apply_filters( 'the_title', $found_post->post_title ),
			'url'             => get_permalink( $found_post ),
			'post_type_label' => $post_type_obj->labels->singular_name,
			'post_id'         => '<a href="' . esc_url( get_edit_post_link( $found_post ) ) . '">' . $found_post->ID . '</a>',
		);
	}

	// $fields = RH_Blocks::get_acf_fields_for_block( $args['block_name'] );
}

// Process source files
$source_files = array();
if ( ! is_array( $args['files'] ) ) {
	$args['files'] = array( $args['files'] );
}

// Check if source files aren't provided but a block directory is
if ( empty( $args['files'] ) && ! empty( $args['block_directory_name'] ) ) {
	$dir_path  = get_template_directory() . '/blocks/' . $args['block_directory_name'] . '/';
	$directory = new RecursiveDirectoryIterator( $dir_path );
	$filter    = new RecursiveCallbackFilterIterator(
		$directory,
		function ( $current ) {
			// Skip hidden files and directories.
			if ( $current->getFilename()[0] === '.' ) {
				return false;
			}
			if ( $current->getExtension() === '' || $current->getExtension() === 'php' ) {
				// return true;
			}
			return true;
		}
	);
	$iterator  = new RecursiveIteratorIterator( $filter );
	foreach ( $iterator as $file ) {
		$the_file = $file->getPathname();
		// Make the file path relative to the theme directory
		$the_file        = str_replace( get_template_directory(), '', $the_file );
		$args['files'][] = $the_file;
	}
}

// Add the styleguide file to the end of the files list
$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
if ( ! empty( $backtrace ) ) {
	foreach ( $backtrace as $item ) {
		if ( str_contains( $item['file'], '/styleguide/' ) ) {
			$theme_relative_path = str_replace( get_template_directory(), '', $item['file'] );
			$args['files'][]     = $theme_relative_path;
			break;
		}
	}
}

if ( ! empty( $args['files'] ) ) {
	$github_branch = 'main';
	if ( wp_get_environment_type() === 'staging' ) {
		$github_branch = 'staging';
	}
	$theme_path = str_replace( untrailingslashit( ABSPATH ), '', get_template_directory() );
	foreach ( $args['files'] as $file_path ) {
		$file_path      = ltrim( $file_path, '/' );
		$new_file       = (object) array(
			'relative_path' => $file_path,
			'root_path'     => trailingslashit( $theme_path ) . $file_path,
			'github_url'    => 'https://github.com/kingkool68/wordpress-rh-starter-theme/blob/' . $github_branch . '/' . $file_path,
			'extension'     => pathinfo( $file_path, PATHINFO_EXTENSION ),
		);
		$source_files[] = $new_file;
	}
}

$context = array(
	'the_breadcrumbs'        => RH_Breadcrumbs::render(
		array(
			'items' => array(
				array(
					'text' => 'Styleguide',
					'url'  => get_site_url() . '/styleguide/',
				),
				array(
					'text' => $args['the_title'],
				),
			),
		)
	),
	'the_title'              => apply_filters( 'the_title', $args['the_title'] ),
	'the_description'        => apply_filters( 'the_content', $args['the_description'] ),
	'examples'               => $args['examples'],
	'posts_containing_block' => $posts_containing_block,
	'is_user_logged_in'      => is_user_logged_in(),
	'wp_login_url'           => wp_login_url( $redirect = RH_Helpers::get_current_url() ),
	'source_files'           => $source_files,
	'before_content'         => $args['before_content'],
);
Sprig::out( 'styleguide-block.twig', $context );
