<?php
/**
 * A way to handle tabs within content
 */
class RH_Tabs_Block {

	/**
	 * Name of the block
	 *
	 * @var string
	 */
	public static $block_name = 'acf/rh-tabs';

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
	 * Hook into WordPress via actions
	 */
	public function setup_actions() {
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'acf/init', array( $this, 'action_acf_init' ) );
	}

	/**
	 * Hook in to WordPress via filters
	 */
	public function setup_filters() {
		add_filter( 'the_content', array( $this, 'filter_the_content' ), 5 );
	}

	/**
	 * Register block-speciifc styles and scripts
	 */
	public function action_init() {
		wp_register_style(
			'rh-tabs',
			get_template_directory_uri() . '/assets/css/tabs/tabs-block.min.css',
			$deps  = array( 'rh' ),
			$ver   = null,
			$media = 'all'
		);
	}

	/**
	 * Register Advanced Custom Fields
	 */
	public function action_acf_init() {

		// Custom fields for the block
		$args = array(
			'name'            => 'rh-tab-list',
			'title'           => 'No HUMANS: RH Tab List',
			'description'     => 'A group of tabs used to show/hide tab content',
			'render_callback' => array( $this, 'render_tab_list_from_block' ),
			'category'        => 'rh',
			'icon'            => 'warning',
			'keywords'        => array(),
		);
		acf_register_block_type( $args );

		$args = array(
			'name'            => 'rh-tabs',
			'title'           => 'RH Tabs',
			'description'     => 'A custom tabs block.',
			'render_callback' => array( $this, 'render_from_block' ),
			'category'        => 'rh',
			'icon'            => 'table-col-after',
			'keywords'        => array( 'tabs', 'tabbed', 'ui', 'container' ),
			'supports'        => array(
				'jsx' => true,
			),
		);
		acf_register_block_type( $args );

		$args = array(
			'key'      => 'tab_block_fields',
			'title'    => 'Tab Fields',
			'fields'   => array(
				array(
					'key'   => 'field_tab_block_label',
					'name'  => 'tab_block_label',
					'label' => 'Label',
					'type'  => 'text',
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'block',
						'operator' => '==',
						'value'    => static::$block_name,
					),
				),
			),
		);
		acf_add_local_field_group( $args );
	}

	/**
	 * Parse the content for Tab blocks and set up Tab List contents
	 *
	 * @param string $the_content The content to be modified
	 */
	public function filter_the_content( $the_content = '' ) {
		if ( is_admin() ) {
			return $the_content;
		}
		if ( ! has_block( static::$block_name, $the_content ) ) {
			return $the_content;
		}
		$blocks            = parse_blocks( $the_content );
		$the_tabs          = array();
		$flag              = false;
		$tab_index         = 0;
		$tab_block_indexes = array();
		$generic_tab_count = 1;
		foreach ( $blocks as $index => $block ) {
			$was_flag_true = $flag;
			if ( $block['blockName'] === static::$block_name ) {
				$flag                = true;
				$tab_block_indexes[] = $index;
			}
			if ( ! empty( $block['blockName'] ) && $block['blockName'] !== static::$block_name ) {
				$flag = false;
			}

			// Is this the first tab-block in the group?
			if ( $flag && ! $was_flag_true ) {
				$tab_index              = $index;
				$the_tabs[ $tab_index ] = array(
					'labels' => array(),
				);
			}

			if ( $flag && ! empty( $block['blockName'] ) ) {
				$tab_label = '';
				if ( ! empty( $block['attrs']['data']['tab_block_label'] ) ) {
					$tab_label = $block['attrs']['data']['tab_block_label'];
				}
				if ( empty( $tab_label ) && ! empty( $block['attrs']['data']['field_tab_block_label'] ) ) {
					$tab_label = $block['attrs']['data']['field_tab_block_label'];
				}
				if ( empty( $tab_label ) ) {
					$tab_label = 'Tab ' . $generic_tab_count;
					++$generic_tab_count;
				}
				$the_tabs[ $tab_index ]['labels'][]                        = $tab_label;
				$blocks[ $index ]['attrs']['data']['is_last_tab_in_group'] = true;
				end( $tab_block_indexes );
				$last_block_index = prev( $tab_block_indexes );
				$blocks[ $last_block_index ]['attrs']['data']['is_last_tab_in_group'] = false;
			}

			// Is this the last tab-block in the group?
			if ( ! $flag && $was_flag_true ) {
				$tab_block_indexes = array();
			}
		}
		if ( empty( $the_tabs ) ) {
			return $the_content;
		}

		// Reverse so we don't need to keep track of how many tab list blocks we inserted
		$the_tabs = array_reverse( $the_tabs, $preserve_keys = true );
		foreach ( $the_tabs as $position => $the_tab ) {
			$new_block  = array(
				'blockName'    => 'acf/rh-tab-list',
				'attrs'        => array(
					'name' => 'acf/rh-tab-list',
					'data' => array(
						'labels' => $the_tab['labels'],
					),
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array( '' ),
			);
			$first_part = array_slice( $blocks, 0, $position );
			$last_part  = array_slice( $blocks, $position );
			$blocks     = array_merge( $first_part, array( $new_block ), $last_part );
		}
		return serialize_blocks( $blocks );
	}

	/**
	 * Render tabs
	 *
	 * @param array $args Arguments to modify what is rendered
	 */
	public static function render( $args = array() ) {
		$defaults = array(
			'label'     => '',
			'is_last'   => false,
			'add_class' => array(),
		);
		$context  = wp_parse_args( $args, $defaults );

		if ( $context['is_last'] !== true ) {
			$context['is_last'] = false;
		}

		if ( ! empty( $context['add_class'] ) ) {
					$context['add_class'] = RH_Helpers::css_class( '', $context['add_class'] );
		}

		wp_enqueue_style( 'rh-tabs' );
		wp_enqueue_script( 'vana11y-tabs' );
		return Sprig::render( 'tabs.twig', $context );
	}

	/**
	 * Render a tab list
	 *
	 * @param array $args Arguments to modify what is rendered
	 */
	public static function render_tab_list( $args = array() ) {
		$defaults = array(
			'labels' => array(),
		);
		$context  = wp_parse_args( $args, $defaults );

		return Sprig::render( 'tab-list.twig', $context );
	}

	/**
	 * Tab block callback function
	 *
	 * @param   array        $block The block settings and attributes.
	 * @param   string       $content The block inner HTML (empty).
	 * @param   bool         $is_preview True during AJAX preview.
	 * @param   (int|string) $post_id The post ID this block is saved to.
	 */
	public function render_from_block( $block = array(), $content = '', $is_preview = false, $post_id = 0 ) {
		if ( $is_preview ) {
			?>
			<style>
				.tab-panel {
					background-color: #efefef;
					padding: 8px 16px;
					border-left: 2px solid #ccc;
					position: relative;
				}
				.tab-panel::before {
					content: attr(id);
					text-transform: uppercase;
					font-size: 12px;
					left: -22px;
					top: 8px;
					writing-mode: vertical-lr;
					white-space: nowrap;
					position: absolute;
					color: #aaa;
				}
			</style>
			<?php
		}
		$is_last = false;
		if ( ! empty( $block['data']['is_last_tab_in_group'] ) ) {
			$is_last = $block['data']['is_last_tab_in_group'];
		}

		if ( empty( $block['className'] ) ) {
			$block['className'] = '';
		}

		$args = array(
			'label'     => get_field( 'tab_block_label' ),
			'add_class' => $block['className'],
			'is_last'   => $is_last,
		);
		echo static::render( $args );
	}

	/**
	 * Tab list block callback function
	 *
	 * @param   array        $block The block settings and attributes.
	 * @param   string       $content The block inner HTML (empty).
	 * @param   bool         $is_preview True during AJAX preview.
	 * @param   (int|string) $post_id The post ID this block is saved to.
	 */
	public function render_tab_list_from_block( $block = array(), $content = '', $is_preview = false, $post_id = 0 ) {
		if ( $is_preview ) {
			echo '(Tab List: Delete this)';
			return;
		}
		$args = array(
			'labels' => $block['data']['labels'],
		);
		echo static::render_tab_list( $args );
	}
}
RH_Tabs_Block::get_instance();
