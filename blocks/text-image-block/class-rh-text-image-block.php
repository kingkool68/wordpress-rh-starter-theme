<?php
/**
 * A block with an image and text on the side
 */
class RH_Text_Image_Block {

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
			$instance->setup_actions();
		}
		return $instance;
	}

	/**
	 * Hook into WordPress via actions
	 */
	public function setup_actions() {
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'acf/init', array( $this, 'action_acf_init' ) );
	}

	/**
	 * Register block-speciifc styles and scripts
	 */
	public function action_init() {
		wp_register_style(
			'rh-text-image-block',
			get_template_directory_uri() . '/assets/css/text-image-block/text-image-block.min.css',
			$deps  = array( 'rh' ),
			$ver   = null,
			$media = 'all'
		);

		wp_register_script(
			'rh-text-image-block',
			get_template_directory_uri() . '/blocks/text-image-block/rh-text-image.js',
			$deps      = array(),
			$ver       = null,
			$in_footer = true
		);
	}

	/**
	 * Register Advanced Custom Fields
	 */
	public function action_acf_init() {

		// Custom fields for the block
		$args = array(
			'name'            => 'rh-text-image',
			'title'           => 'RH Text / Image',
			'description'     => 'A custom Text / Image block . ',
			'render_callback' => array( $this, 'render_from_block' ),
			'category'        => 'rh',
			'icon'            => 'align-left',
			'keywords'        => array( 'text / image', 'text image' ),
		);
		acf_register_block_type( $args );

		$args = array(
			'key'      => 'text_image_block_fields',
			'title'    => 'Call to Action Block Fields',
			'fields'   => array(
				array(
					'key'           => 'field_text_image_block_image_id',
					'name'          => 'text_image_block_image_id',
					'label'         => 'Image',
					'type'          => 'image',
					'return_format' => 'id',
				),
				array(
					'key'     => 'field_text_image_block_image_alignment',
					'name'    => 'text_image_block_image_alignment',
					'label'   => 'Image Alignment',
					'type'    => 'select',
					'choices' => array(
						'left'  => 'Left',
						'right' => 'Right',
					),
				),
				array(
					'key'        => 'field_text_image_block_image_proportion',
					'name'       => 'text_image_block_image_proportion',
					'label'      => 'Image Proportion',
					'type'       => 'select',
					'choices'    => array(
						'one-third'  => '1/3',
						'half'       => '1/2',
						'two-thirds' => '2/3',
					),
					'allow_null' => true,
				),
				array(
					'key'        => 'field_text_image_block_bg',
					'name'       => 'text_image_block_bg',
					'label'      => 'Background Color',
					'type'       => 'select',
					'choices'    => array(
						'gray' => 'Gray',
					),
					'allow_null' => true,
				),
				array(
					'key'   => 'field_text_image_block_kicker',
					'name'  => 'text_image_block_kicker',
					'label' => 'Kicker',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_text_image_block_headline',
					'name'  => 'text_image_block_headline',
					'label' => 'Headline',
					'type'  => 'text',
				),
				array(
					'key'          => 'field_text_image_block_headline_url',
					'name'         => 'text_image_block_headline_url',
					'label'        => 'Headline URL',
					'instructions' => 'Link the headline and image to a URL',
					'type'         => 'text',
				),
				array(
					'key'          => 'field_text_image_block_description',
					'name'         => 'text_image_block_description',
					'label'        => 'Description',
					'type'         => 'wysiwyg',
					'toolbar'      => 'basic',
					'media_upload' => false,
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'block',
						'operator' => ' == ',
						'value'    => 'acf/rh-text-image',
					),
				),
			),
		);
		acf_add_local_field_group( $args );
	}

	/**
	 * Render a Text Image component
	 *
	 * @param array $args Arguments to modify what is rendered
	 */
	public static function render( $args = array() ) {
		$defaults = array(
			'image'            => '',
			'image_alignment'  => 'left',
			'image_proportion' => '',
			'bg_color'         => '',
			'kicker'           => '',
			'headline'         => '',
			'headline_url'     => '',
			'description'      => '',
		);
		$context  = wp_parse_args( $args, $defaults );

		if ( $context['image_alignment'] !== 'right' ) {
			$context['image_alignment'] = $defaults['image_alignment'];
		}

		$context['kicker']      = apply_filters( 'the_title', $context['kicker'] );
		$context['headline']    = apply_filters( 'the_title', $context['headline'] );
		$context['description'] = apply_filters( 'the_content', $context['description'] );

		wp_enqueue_style( 'rh-text-image-block' );
		wp_enqueue_script( 'rh-text-image-block' );
		return Sprig::render( 'text-image-block.twig', $context );
	}

	/**
	 * Call to Action block callback function
	 *
	 * @param   array        $block The block settings and attributes.
	 * @param   string       $content The block inner HTML (empty).
	 * @param   bool         $is_preview True during AJAX preview.
	 * @param   (int|string) $post_id The post ID this block is saved to.
	 */
	public function render_from_block( $block = array(), $content = '', $is_preview = false, $post_id = 0 ) {
		$image_id   = get_field( 'text_image_block_image_id' );
		$image_args = array();

		$image_size = get_field( 'text_image_block_image_size' );
		if ( ! empty( $image_size ) ) {
			$image_args['size'] = $image_size;
		}

		$headline_url = get_field( 'text_image_block_headline_url' );
		if ( $is_preview ) {
			$headline_url = '';
		}
		if ( ! empty( $headline_url ) ) {
			$image_args['link_url'] = $headline_url;
		}

		$args = array(
			'image'            => RH_Media::render_image_from_post( $image_id, $image_args ),
			'image_alignment'  => get_field( 'text_image_block_image_alignment' ),
			'image_proportion' => get_field( 'text_image_block_image_proportion' ),
			'image_size'       => get_field( 'text_image_block_image_size' ),
			'bg_color'         => get_field( 'text_image_block_bg' ),
			'kicker'           => get_field( 'text_image_block_kicker' ),
			'headline'         => get_field( 'text_image_block_headline' ),
			'headline_url'     => $headline_url,
			'description'      => get_field( 'text_image_block_description' ),
		);

		if ( empty( $args['headline'] ) && $is_preview ) {
			echo '( Fill in Text / Image Block Details )';
			return;
		}
		echo static::render( $args );
	}

}
RH_Text_Image_Block::get_instance();
