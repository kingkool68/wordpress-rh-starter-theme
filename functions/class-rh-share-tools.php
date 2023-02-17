<?php
/**
 * Share Tools feature
 */
class RH_Share_Tools {
	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
		}
		return $instance;
	}

	/**
	 * Get various social media URLs for sharing
	 *
	 * @param array $args Arguments to modify the share URLs
	 */
	public static function get_data( $args = array() ) {
		$post         = get_post();
		$default_text = get_the_title( $post->ID );
		$default_url  = get_permalink( $post->ID );
		$default_args = array(
			'url'           => $default_url,

			'twitter_url'   => '',
			'twitter_text'  => $default_text,
			'twitter_via'   => 'RH_',

			'facebook_url'  => '',

			'linkedin_url'  => '',

			'email_url'     => '',
			'email_text'    => $default_text,
			'email_subject' => $default_text,
			'email_body'    => '',
		);
		$args         = wp_parse_args( $args, $default_args );

		$urls_to_check = array(
			'twitter_url',
			'facebook_url',
			'linkedin_url',
			'email_url',
		);
		foreach ( $urls_to_check as $key ) {
			if ( empty( $args[ $key ] ) && false !== $args[ $key ] ) {
				$args[ $key ] = $args['url'];
			}
		}

		if ( empty( $args['email_body'] ) ) {
			$args['email_body'] = $args['email_text'] . "\n\n" . $args['email_url'];
		}

		$args['twitter_share_url'] = add_query_arg(
			array(
				'url'  => rawurlencode( $args['twitter_url'] ),
				'text' => rawurlencode( $args['twitter_text'] ),
				'via'  => rawurlencode( $args['twitter_via'] ),
			),
			'https://twitter.com/share'
		);

		$args['facebook_share_url'] = add_query_arg(
			array(
				'u' => rawurlencode( $args['facebook_url'] ),
			),
			'https://www.facebook.com/sharer/sharer.php'
		);

		if ( $args['linkedin_url'] ) {
			$args['linkedin_share_url'] = add_query_arg(
				array(
					'url'  => rawurlencode( $args['linkedin_url'] ),
					'mini' => true,
				),
				'https://www.linkedin.com/shareArticle'
			);
		}

		if ( $args['email_url'] ) {
			$args['email_share_url'] = add_query_arg(
				array(
					'subject' => rawurlencode( $args['email_subject'] ),
					'body'    => rawurlencode( $args['email_body'] ),
				),
				'mailto:'
			);
		}
		return $args;
	}

	/**
	 * Render the share tools
	 *
	 * @param  array $args Arguments to modify what is rendered
	 * @return string       HTML
	 */
	public static function render( $args = array() ) {
		$data_args    = static::get_data( $args );
		$default_args = array(
			'label'              => 'Share',
			'url'                => '',

			'twitter_url'        => '',
			'twitter_text'       => '',
			'twitter_via'        => 'RH_',
			'twitter_icon_name'  => 'twitter',
			'twitter_icon'       => '',

			'facebook_url'       => '',
			'facebook_icon_name' => 'facebook',
			'facebook_icon'      => '',

			'linkedin_url'       => '',
			'linkedin_icon_name' => 'linkedin',
			'linkedin_icon'      => '',

			'email_url'          => '',
			'email_text'         => '',
			'email_subject'      => '',
			'email_body'         => '',
			'email_icon_name'    => 'email',
			'email_icon'         => '',
		);
		$data_args    = wp_parse_args( $data_args, $default_args );
		$args         = wp_parse_args( $args, $data_args );

		var_dump( $args );

		if ( empty( $args['twitter_icon'] ) && ! empty( $args['twitter_icon_name'] ) ) {
			$args['twitter_icon'] = RH_SVG::get_icon( $args['twitter_icon_name'] );
		}

		if ( empty( $args['facebook_icon'] ) && ! empty( $args['facebook_icon_name'] ) ) {
			$args['facebook_icon'] = RH_SVG::get_icon( $args['facebook_icon_name'] );
		}

		if ( empty( $args['linkedin_icon'] ) && ! empty( $args['linkedin_icon_name'] ) ) {
			$args['linkedin_icon'] = RH_SVG::get_icon( $args['linkedin_icon_name'] );
		}

		if ( empty( $args['email_icon'] ) && ! empty( $args['email_icon_name'] ) ) {
			$args['email_icon'] = RH_SVG::get_icon( $args['email_icon_name'] );
		}

		return Sprig::render( 'share-tools.twig', $args );
	}
}

RH_Share_Tools::get_instance();
