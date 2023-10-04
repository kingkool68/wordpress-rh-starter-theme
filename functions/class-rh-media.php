<?php
/**
 * A Helper for rendering image markup
 */
class RH_Media {

	/**
	 * References reconstructed sizes for an attachment
	 *
	 * @var array
	 */
	public static $image_sizes = null;

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
	public function setup_actions() {
		add_action( 'init', array( $this, 'action_init' ) );
	}

	/**
	 * Hook in to WordPress via filters
	 */
	public function setup_filters() {
		add_filter( 'oembed_dataparse', array( $this, 'filter_oembed_dataparse' ), 10, 3 );
		add_filter( 'oembed_dataparse', array( $this, 'filter_oembed_dataparse_lite_youtube_embed' ), 11, 3 );
		add_filter( 'embed_oembed_html', array( $this, 'filter_oembed_lite_youtube' ), 10, 3 );
		add_filter( 'oembed_result', array( $this, 'filter_oembed_lite_youtube' ), 11, 2 );
		add_filter( 'upload_mimes', array( $this, 'filter_upload_mimes' ), 10 );

		if ( defined( 'TACHYON_URL' ) && ! empty( TACHYON_URL ) ) {
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
	}

	/**
	 * Add theme support and register scripts
	 */
	public function action_init() {
		add_theme_support( 'post-thumbnails' );

		// See https://github.com/paulirish/lite-youtube-embed
		wp_register_style(
			'lite-youtube-embed',
			get_template_directory_uri() . '/assets/css/lite-youtube-embed.min.css',
			$deps  = array( 'rh' ),
			$ver   = null,
			$media = 'all'
		);

		wp_register_script(
			'lite-youtube-embed',
			get_template_directory_uri() . '/assets/js/lite-youtube-embed.js',
			$deps      = array(),
			$ver       = null,
			$in_footer = true
		);
	}

	/**
	 * Wrap video oembeds in a container to make them responsive
	 *
	 * @param string $return The returned oEmbed HTML
	 * @param object $data   A data object result from an oEmbed provider
	 * @param string $url    The URL of the content to be embedded
	 */
	public static function filter_oembed_dataparse( $return = '', $data = array(), $url = '' ) {
		if ( 'video' !== $data->type && 'rich' !== $data->type ) {
			return $return;
		}

		$width  = '';
		$height = '';
		if ( ! empty( $data->width ) ) {
			$width = absint( $data->width );
		}
		if ( ! empty( $data->height ) ) {
			$height = absint( $data->height );
		}

		// If we have one dimension then assume 16:9
		if ( ! empty( $width ) && empty( $height ) ) {
			$height = $width * ( 1080 / 1920 );
		}
		if ( empty( $width ) && ! empty( $height ) ) {
			$width = $height * ( 1920 / 1080 );
		}

		$style_attr = '';
		if ( $width > 0 && $height > 0 ) {
			$ratio      = min( $width / $height, $height / $width );
			$ratio      = $ratio * 100;
			$style_attr = RH_Helpers::build_html_attributes(
				array(
					'style' => 'padding-top: ' . $ratio . '%;',
				)
			);
		}

		if ( $data->type === 'rich' ) {
			$style_attr = '';
		}

		return '<div class="responsive-embed"' . $style_attr . '>' . $return . '</div>';
	}

	/**
	 * Change YouTube embed markup to use lite-youtube-embed web component
	 *
	 * @param string $return The returned oEmbed HTML
	 * @param object $data   A data object result from an oEmbed provider
	 * @param string $url    The URL of the content to be embedded
	 */
	public function filter_oembed_dataparse_lite_youtube_embed( $return = '', $data = array(), $url = '' ) {
		if ( 'video' !== $data->type || 'YouTube' !== $data->provider_name ) {
			return $return;
		}

		$url_parts = wp_parse_url( $url );
		// If there is no query string, then bail
		if ( empty( $url_parts['query'] ) ) {
			return $return;
		}
		parse_str( $url_parts['query'], $query_string_parts );
		// If there is no video id (i.e. ?v=abc123), then bail
		if ( empty( $query_string_parts['v'] ) ) {
			return $return;
		}
		$videoid = $query_string_parts['v'];
		unset( $query_string_parts['v'] );
		$embed_args = array(
			'videoid' => $videoid,
			'params'  => $query_string_parts,
			'title'   => $data->title,
			'url'     => $url,
		);
		return static::render_lite_youtube_embed( $embed_args );
	}

	/**
	 * Detect if the oEmbed being rendered is a lite-youtube-embed and enqueue the needed JavaScript
	 *
	 * @param string $cache The HTML contents that have been cached
	 * @param string $url The URL trying to be embedded
	 * @param array  $attr Shortcode attributes
	 */
	public function filter_oembed_lite_youtube( $cache = '', $url = '', $attr = array() ) {
		if ( str_contains( $cache, '<lite-youtube' ) ) {
			wp_enqueue_style( 'lite-youtube-embed' );
			wp_enqueue_script( 'lite-youtube-embed' );
		}
		return $cache;
	}

	/**
	 * Modify the allowed mime types of files that can be uploaded
	 *
	 * @param array $mimes The mime types to modify
	 */
	public function filter_upload_mimes( $mimes = array() ) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}

	/**
	 * Disable generating intermediate images when images are uploaded
	 *
	 * @param  array $sizes The intermediate sizes that will be generated
	 */
	public function filter_intermediate_image_sizes_advanced( $sizes = array() ) {
		// If the 'intermediate_image_sizes_advanced' filter is called during the wp_create_image_subsizes() function then return an empty array so no intermediate image sizes will be generated
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
		$image_sizes = static::get_image_sizes();
		foreach ( $image_sizes as $key => $image ) {
			$the_dimensions = wp_constrain_dimensions( $width, $height, $image['width'], $image['height'] );
			$sizes[ $key ]  = array(
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

			if ( ! empty( $imagedata['width'] ) || ! empty( $imagedata['height'] ) ) {
				$h = $imagedata['height'];
				$w = $imagedata['width'];

				list ($w, $h) = wp_constrain_dimensions( $w, $h, $_max_w, $_max_h );
				if ( $w < $imagedata['width'] || $h < $imagedata['height'] ) {
					$resized = true;
				}
			} else {
				$w = $_max_w;
				$h = $_max_h;
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

	/**
	 * Get a list of image sizes registered with WordPress
	 */
	public static function get_image_sizes() {
		if ( static::$image_sizes !== null ) {
			return static::$image_sizes;
		}

		$_wp_additional_image_sizes = wp_get_additional_image_sizes();
		$sizes                      = array();

		/*
		 * Remove filter preventing WordPress from reading the sizes, it's meant
		 * to prevent creation of intermediate files, which are not really being used.
		 */
		$intermediate_image_sizes = get_intermediate_image_sizes();
		foreach ( $intermediate_image_sizes as $s ) {
			$sizes[ $s ] = array(
				'width'  => '',
				'height' => '',
				'crop'   => false,
			);
			if ( isset( $_wp_additional_image_sizes[ $s ]['width'] ) ) {
				// For theme-added sizes.
				$sizes[ $s ]['width'] = intval( $_wp_additional_image_sizes[ $s ]['width'] );
			} else {
				// For default sizes set in options.
				$sizes[ $s ]['width'] = get_option( "{$s}_size_w" );
			}

			if ( isset( $_wp_additional_image_sizes[ $s ]['height'] ) ) {
				// For theme-added sizes.
				$sizes[ $s ]['height'] = intval( $_wp_additional_image_sizes[ $s ]['height'] );
			} else {
				// For default sizes set in options.
				$sizes[ $s ]['height'] = get_option( "{$s}_size_h" );
			}

			if ( isset( $_wp_additional_image_sizes[ $s ]['crop'] ) ) {
				// For theme-added sizes.
				$sizes[ $s ]['crop'] = $_wp_additional_image_sizes[ $s ]['crop'];
			} else {
				// For default sizes set in options.
				$sizes[ $s ]['crop'] = get_option( "{$s}_crop" );
			}
		}

		static::$image_sizes = $sizes;

		return $sizes;
	}

	/**
	 * Get a list of human friendly image size names keyed to their image size name registered with WordPress
	 */
	public static function get_image_size_names() {
		$output = array();
		$sizes  = wp_get_registered_image_subsizes();
		foreach ( $sizes as $name => $data ) {
			$pretty_name = $name;

			// Check if the name is defined as dimensions i.e. 123x456
			$dimension_name = false;
			preg_match( '/(\d+)x(\d+)/i', $pretty_name, $match );
			if ( ! empty( $match[1] ) && ! empty( $match[2] ) ) {
				$dimension_name = true;
			}

			// Display the image dimensions as a suffix i.e. (1920x1080) or (768 wide)
			$suffix = '';
			if ( ! empty( $data['width'] ) && ! empty( $data['height'] ) ) {
				$suffix = $data['width'] . 'x' . $data['height'];
				if ( ! $data['crop'] ) {
					$suffix = 'fit within ' . $suffix;
				}
			} elseif ( ! empty( $data['width'] ) ) {
				$suffix = $data['width'] . ' wide';
			} elseif ( ! empty( $data['height'] ) ) {
				$suffix = $data['height'] . ' tall';
			}

			if ( ! empty( $suffix ) && ! $dimension_name ) {
				$pretty_name  = str_replace( array( '-', '_' ), ' ', $pretty_name );
				$pretty_name  = ucwords( $pretty_name );
				$pretty_name .= ' (' . $suffix . ')';
			}

			$output[ $name ] = $pretty_name;
		}
		return $output;
	}

	/**
	 * Render an image
	 *
	 * @param array $args Arguments to modify what is rendered
	 */
	public static function render( $args = array() ) {
		$defaults           = array(
			'link_url'   => '',
			'link_attr'  => array(),
			'caption'    => '',
			'image_src'  => '',
			'image_attr' => '',
			'video_url'  => '',
			'post_id'    => 0,
			'size'       => 'large',
		);
		$context            = wp_parse_args( $args, $defaults );
		$context['post_id'] = absint( $context['post_id'] );

		$media = '';
		if ( empty( $media ) && $context['post_id'] > 0 ) {
			$media_args = array(
				'image_attr' => $context['image_attr'],
				'caption'    => $context['caption'],
				'size'       => $context['size'],
				'link_url'   => $context['link_url'],
				'link_attr'  => $context['link_attr'],
			);
			$media      = static::render_image_from_post( $context['post_id'], $media_args );
		}

		if ( empty( $media ) && ! empty( $context['image_src'] ) ) {
			$media_args = array(
				'image_src'  => $context['image_src'],
				'image_attr' => $context['image_attr'],
				'link_url'   => $context['link_url'],
				'link_attr'  => $context['link_attr'],
				'caption'    => $context['caption'],
			);
			$media      = static::render_image( $media_args );
		}

		if ( empty( $media ) && ! empty( $context['video_url'] ) ) {
			$media = wp_oembed_get( $context['video_url'] );
			if ( ! empty( $context['caption'] ) ) {
				$figure_args = array(
					'media'       => $media,
					'caption'     => $context['caption'],
					'figure_attr' => array(
						'class' => 'video-with-caption',
					),
				);
				$media       = static::render_figure( $figure_args );
			}
		}

		return $media;
	}

	/**
	 * Render image markup
	 *
	 * @param array $args Arguments to modify what is rendered
	 */
	public static function render_image( $args = array() ) {
		$defaults = array(
			'link_url'   => '',
			'link_attr'  => array(),
			'image_src'  => '',
			'image_attr' => array(),
			'image'      => '',
			'caption'    => '',
		);
		$args     = wp_parse_args( $args, $defaults );

		if ( is_array( $args['image_attr'] ) ) {
			$args['image_attr'] = RH_Helpers::build_html_attributes( $args['image_attr'] );
		}

		if ( is_array( $args['link_attr'] ) ) {
			$args['link_attr'] = RH_Helpers::build_html_attributes( $args['link_attr'] );
		}

		if ( empty( $args['image'] ) && ! empty( $args['image_src'] ) ) {
			$args['image'] = Sprig::render( 'img.twig', $args );
		}

		if ( ! empty( $args['image'] ) && ! empty( $args['link_url'] ) ) {
			$args['image'] = Sprig::render( 'linked-img.twig', $args );
		}

		if ( ! empty( $args['caption'] ) ) {
			$figure_args = array(
				'media'   => $args['image'],
				'caption' => $args['caption'],
			);
			return static::render_figure( $figure_args );
		}

		return $args['image'];
	}

	/**
	 * Render an image markup from a given post ID
	 *
	 * @param integer $post Post ID of attachment or post to get the featured image from
	 * @param array   $args Arguments to modify what is rendered
	 */
	public static function render_image_from_post( $post = 0, $args = array() ) {
		// Make sure the post to get image markup for is an attachment
		$post = get_post( $post );
		if ( empty( $post ) ) {
			return;
		}
		if ( 'attachment' !== $post->post_type ) {
			$featured_post_id = get_post_thumbnail_id( $post );
			$post             = get_post( $featured_post_id );
		}

		$defaults      = array(
			'size'       => 'large',
			'caption'    => '',
			'image_attr' => array(),
		);
		$args          = wp_parse_args( $args, $defaults );
		$args['image'] = wp_get_attachment_image(
			$post->ID,
			$args['size'],
			$icon      = false,
			$args['image_attr']
		);
		return static::render_image( $args );
	}

	/**
	 * Render video markup
	 *
	 * @param  array $args Arguments to modify what is rendered
	 * @return string      Rendered video HTML
	 */
	public static function render_video( $args = array() ) {
		$defaults = array(
			'src'         => '',
			'autoplay'    => false,
			'loop'        => false,
			'muted'       => false,
			'controls'    => true,
			'inline'      => false,
			'poster'      => '',
			'preload'     => 'metadata',
			'attrs'       => array(),
			'figure_attr' => array(
				'class' => 'wp-block-video',
			),
		);
		$args     = wp_parse_args( $args, $defaults );

		// Make sure there is a video to play
		if ( empty( $args['src'] ) ) {
			return '';
		}

		// Generate the <figure> element attributes
		$figure_attributes = $args['figure_attr'];

		// Generate the <video> element attributes
		$attributes = $args['attrs'];
		$attributes = wp_parse_args( $attributes, $args );
		unset( $attributes['attrs'] );
		unset( $attributes['figure_attr'] );
		$attributes_to_be_removed_if_false = array(
			'autoplay',
			'loop',
			'muted',
			'controls',
			'inline',
		);
		foreach ( $attributes_to_be_removed_if_false as $key ) {
			if ( $attributes[ $key ] !== true ) {
				unset( $attributes[ $key ] );
			}
		}

		$allowed_preload_values = array(
			'none',
			'metadata',
			'auto',
		);
		if ( ! in_array( $attributes['preload'], $allowed_preload_values, true ) ) {
			$attributes['preload'] = $defaults['preload'];
		}

		// Escape attributes that should be a URL
		$attributes['src']    = esc_url( $attributes['src'] );
		$attributes['poster'] = esc_url( $attributes['poster'] );

		$attribute_string        = RH_Helpers::build_html_attributes( $attributes );
		$figure_attribute_string = RH_Helpers::build_html_attributes( $figure_attributes );
		return '<figure ' . $figure_attribute_string . '><video ' . $attribute_string . '></video></figure>';
	}

	/**
	 * Render video markup from a given post ID
	 *
	 * @param integer $post Post ID of attachment
	 * @param array   $args Arguments to modify what is rendered
	 */
	public static function render_video_from_post( $post = 0, $args = array() ) {
		// Make sure the post to get video markup for is an attachment
		$post = get_post( $post );
		if ( empty( $post ) ) {
			return;
		}
		if ( ! wp_attachment_is( 'video', $post ) ) {
			return;
		}
		$defaults    = array();
		$args        = wp_parse_args( $args, $defaults );
		$args['src'] = wp_get_attachment_url( $post->ID );
		if ( empty( $args['src'] ) ) {
			return;
		}
		return static::render_video( $args );
	}

	/**
	 * Render a figure
	 *
	 * @param array $args Arguments for modifying what is rendered
	 */
	public static function render_figure( $args = array() ) {
		$defaults = array(
			'media'       => '',
			'caption'     => '',
			'figure_attr' => array(),
		);
		$context  = wp_parse_args( $args, $defaults );

		if ( is_array( $context['figure_attr'] ) ) {
			$context['figure_attr'] = RH_Helpers::build_html_attributes( $context['figure_attr'] );
		}

		return Sprig::render( 'figure.twig', $context );
	}

	/**
	 * Render the markup for a Lite YouTube Embed web component
	 *
	 * @param array $args Arguments to modify what is rendered
	 */
	public static function render_lite_youtube_embed( $args = array() ) {
		$defaults = array(
			'videoid' => '',
			'title'   => '',
			'params'  => array(),
			'url'     => '',
		);
		$context  = wp_parse_args( $args, $defaults );
		if ( ! is_array( $context['params'] ) ) {
			parse_str( $context['params'], $context['params'] );
		}
		if ( ! is_array( $context['params'] ) ) {
			$context['params'] = array();
		}

		if ( empty( $context['url'] ) && ! empty( $context['videoid'] ) ) {
			$context['url'] = add_query_arg(
				array(
					'v',
					$context['videoid'],
				),
				'https://www.youtube.com/watch'
			);
		}

		// See list of YouTube player params https://developers.google.com/youtube/player_parameters#Parameters
		$default_params    = array(
			'modestbranding' => 1,
			'rel'            => 0,
		);
		$context['params'] = wp_parse_args( $context['params'], $default_params );
		$context['params'] = build_query( $context['params'] );
		return Sprig::render( 'lite-youtube-embed.twig', $context );
	}
}
RH_Media::get_instance();
