<?php

if ( defined( 'WP_CLI' ) && WP_CLI ) :
	/**
	 * WP CLI commands
	 */
	class RH_CLI extends WP_CLI_Command {

		/**
		 * Export a WP_Query to CSV
		 */
		public function query_posts_to_csv() {
			$args      = array(
				'post_type'      => RH_Posts::$post_type,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'date_query'     => array(
					array(
						'after' => '2022-01-01 00:00:01',
					),
				),
			);
			$the_query = new WP_Query( $args );
			$data      = array();
			$data[]    = array( 'url', 'post_date', 'post_title' );
			foreach ( $the_query->posts as $post ) {
				$data[] = array(
					get_permalink( $post ),
					$post->post_date,
					$post->post_title,
				);
			}

			$upload_dir = wp_upload_dir();
			$filename   = $upload_dir['basedir'] . '/2022-posts.csv';
			$fp         = fopen( $filename, 'a+' );
			foreach ( $data as $row ) {
				fputcsv( $fp, $row );
			}
			fclose( $fp );
		}
	}

	WP_CLI::add_command( 'rh', 'RH_CLI' );

endif;
