<?php
/**
 * A Helper for rendering image markup
 */
class RH_Media {

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
