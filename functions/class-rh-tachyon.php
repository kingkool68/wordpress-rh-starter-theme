<?php
/**
 * Modifying behavior of the Tachyon plugin and on-demand image resizing infrastructure
 */
class RH_Tachyon {

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
			$instance->setup_actions();
			$instance->setup_filters();
		}
		return $instance;
	}

	/**
	 * Hook in to WordPress via filters
	 */
	public function setup_actions() {}

	/**
	 * Hook in to WordPress via filters
	 */
	public function setup_filters() {
		// Bail if the TACHYON_URL constant isn't set up
		if ( ! defined( 'TACHYON_URL' ) || empty( TACHYON_URL ) ) {
			return;
		}

		// Remove intermediate sizes only if we're uploading an image
		add_filter( 'intermediate_image_sizes_advanced', array( $this, 'filter_intermediate_image_sizes_advanced' ), 999 );
		add_filter( 'wp_update_attachment_metadata', array( $this, 'filter_wp_update_attachment_metadata' ), 10, 2 );
		add_filter( 'image_downsize', array( $this, 'filter_image_resize' ), 5, 3 );

		// Tachyon modifications
		add_filter( 'tachyon_remove_size_attributes', '__return_false' );
		add_filter( 'tachyon_disable_in_admin', '__return_false' );
		add_filter( 'tachyon_override_image_downsize', '__return_true' );
		add_filter(
			'tachyon_pre_args',
			function ( $args ) {
				if ( ! empty( $args['resize'] ) ) {
					$parts     = explode( ',', $args['resize'] );
					$args['w'] = $parts[0];
					unset( $args['resize'] );
				}

				return $args;
			}
		);
	}

	/**
	 * Disable generating intermediate images when images are uploaded
	 *
	 * @param  array $sizes The intermediate sizes that will be generated
	 */
	public function filter_intermediate_image_sizes_advanced( $sizes = array() ) {
		/**
		 * If the 'intermediate_image_sizes_advanced' filter is called during the
		 * wp_create_image_subsizes() function then return an empty array so
		 * no intermediate image sizes will be generated
		 */
		$backtrace = wp_debug_backtrace_summary( null, 0, false );
		$needle    = array_search( 'wp_create_image_subsizes', $backtrace, true );
		if ( $needle !== false ) {
			return array();
		}
		return $sizes;
	}

	/**
	 * Generate our own image sizes for attachment meta data
	 *
	 * @param  array   $data    The attachment metadata to modify
	 * @param  integer $post_id The ID of the attachment post being updated
	 */
	public function filter_wp_update_attachment_metadata( $data = array(), $post_id = 0 ) {
		$width         = $data['width'];
		$height        = $data['height'];
		$file_name     = basename( $data['file'] );
		$upload_dir    = wp_upload_dir();
		$absolute_path = $upload_dir['basedir'] . '/' . $data['file'];
		$file_size     = $data['filesize'];
		$mime_type     = wp_check_filetype_and_ext( $absolute_path, $file_name );

		$sizes       = array();
		$image_sizes = RH_Media::get_image_sizes();
		foreach ( $image_sizes as $key => $image ) {
			if ( $image['crop'] ) {
				if ( $width < $image['width'] && $height < $image['height'] ) {
					continue;
				}
				$the_dimensions = array(
					$image['width'],
					$image['height'],
				);
			} else {
				$the_dimensions = wp_constrain_dimensions( $width, $height, $image['width'], $image['height'] );
			}
			$sizes[ $key ] = array(
				'file'      => $file_name,
				'width'     => $the_dimensions[0],
				'height'    => $the_dimensions[1],
				'mime-type' => $mime_type['type'],
				'filesize'  => $file_size,
			);
		}
		$data['sizes'] = $sizes;
		return $data;
	}

	/**
	 * Image resizing service.  Takes place of image_downsize().
	 *
	 * @param bool         $ignore Unused.
	 * @param int          $id Attachment ID for image.
	 * @param array|string $size Optional, default is 'medium'. Size of image, either array or string.
	 * @return bool|array False on failure, array on success.
	 * @see image_downsize()
	 */
	public function filter_image_resize( $ignore = false, $id = 0, $size = '' ) {
		global $_wp_additional_image_sizes, $post;

		// Don't bother resizing non-image (and non-existent) attachment.
		// We fallthrough to core's image_downsize but that bails as well.
		$is_img = wp_attachment_is_image( $id );
		if ( ! $is_img ) {
			return false;
		}

		$content_width = isset( $GLOBALS['content_width'] ) ? $GLOBALS['content_width'] : null;
		$crop          = false;
		$args          = array();

		// For resize requests coming from an image's attachment page, override
		// the supplied $size and use the user-defined $content_width if the
		// theme-defined $content_width has been manually passed in.
		if ( is_attachment() && $id === $post->ID ) {
			if ( is_array( $size ) && ! empty( $size ) && isset( $GLOBALS['content_width'] ) && $size[0] == $GLOBALS['content_width'] ) {
				$size = array( $content_width, $content_width );
			}
		}

		if ( 'tellyworth' == $size ) { // 'full' is reserved because some themes use it (see image_constrain_size_for_editor)
			$_max_w = 4096;
			$_max_h = 4096;
		} elseif ( 'thumbnail' == $size ) {
			$_max_w = get_option( 'thumbnail_size_w' );
			$_max_h = get_option( 'thumbnail_size_h' );
			if ( ! $_max_w && ! $_max_h ) {
				$_max_w = 128;
				$_max_h = 96;
			}
			if ( get_option( 'thumbnail_crop' ) ) {
				$crop = true;
			}
		} elseif ( 'medium' == $size ) {
			$_max_w = get_option( 'medium_size_w' );
			$_max_h = get_option( 'medium_size_h' );
			if ( ! $_max_w && ! $_max_h ) {
				$_max_w = 300;
				$_max_h = 300;
			}
		} elseif ( 'large' == $size ) {
			$_max_w = get_option( 'large_size_w' );
			$_max_h = get_option( 'large_size_h' );
		} elseif ( is_array( $size ) ) {
			$_max_w = $size[0] ?? 0;
			$_max_h = $size[1] ?? 0;
			$w      = $_max_w;
			$h      = $_max_h;
		} elseif ( ! empty( $_wp_additional_image_sizes[ $size ] ) ) {
			$_max_w = $_wp_additional_image_sizes[ $size ]['width'];
			$_max_h = $_wp_additional_image_sizes[ $size ]['height'];
			$w      = $_max_w;
			$h      = $_max_h;
			$crop   = $_wp_additional_image_sizes[ $size ]['crop'];
		} elseif ( $content_width > 0 ) {
			$_max_w = $content_width;
			$_max_h = 0;
		} else {
			$_max_w = 1024;
			$_max_h = 0;
		}

		// Constrain default image sizes to the theme's content width, if available.
		if ( $content_width > 0 && in_array( $size, array( 'thumbnail', 'medium', 'large' ) ) ) {
			$_max_w = min( $_max_w, $content_width );
		}

		$resized = false;
		$img_url = wp_get_attachment_url( $id );

		/**
		 * Filter the original image Photon-compatible parameters before changes are
		 *
		 * @param array|string $args Array of Photon-compatible arguments.
		 * @param string $img_url Image URL.
		 */
		$args = apply_filters( 'vip_go_image_resize_pre_args', $args, $img_url );

		if ( ! $crop ) {
			$imagedata = wp_get_attachment_metadata( $id );
			$w         = $_max_w;
			$h         = $_max_h;
			if ( ! empty( $imagedata['width'] ) || ! empty( $imagedata['height'] ) ) {
				$h = $imagedata['height'];
				$w = $imagedata['width'];

				list ($w, $h) = wp_constrain_dimensions( $w, $h, $_max_w, $_max_h );
				if ( $w < $imagedata['width'] || $h < $imagedata['height'] ) {
					$resized = true;
				}
			}
		}

		if ( $crop ) {
			$constrain = false;

			$imagedata = wp_get_attachment_metadata( $id );
			if ( $imagedata ) {
				$w = $imagedata['width'] ?? 0;
				$h = $imagedata['height'] ?? 0;
			}

			if ( empty( $w ) ) {
				$w = $_max_w;
			}

			if ( empty( $h ) ) {
				$h = $_max_h;
			}

			// If the image width is bigger than the allowed max, scale it to match
			if ( $w >= $_max_w ) {
				$w = $_max_w;
			} else {
				$constrain = true;
			}

			// If the image height is bigger than the allowed max, scale it to match
			if ( $h >= $_max_h ) {
				$h = $_max_h;
			} else {
				$constrain = true;
			}

			if ( $constrain ) {
				list( $w, $h ) = wp_constrain_dimensions( $w, $h, $_max_w, $_max_h );
			}

			$args['w'] = $w;
			$args['h'] = $h;

			$args['crop'] = '1';
			$resized      = true;
		} elseif ( 'full' != $size ) {
			// we want users to be able to resize full size images with tinymce.
			// the image_add_wh() filter will add the ?w= query string at display time.
			$args['w'] = $w;
			$resized   = true;
		}

		if ( is_array( $args ) ) {
			// Convert values that are arrays into strings
			foreach ( $args as $arg => $value ) {
				if ( is_array( $value ) ) {
					$args[ $arg ] = implode( ',', $value );
				}
			}
			// Encode values
			// See http://core.trac.wordpress.org/ticket/17923
			$args = rawurlencode_deep( $args );
		}
		$img_url = add_query_arg( $args, $img_url );

		return array( $img_url, $w, $h, $resized );
	}
}
RH_Tachyon::get_instance();
