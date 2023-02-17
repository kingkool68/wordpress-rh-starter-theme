<?php
/**
 * Functionality specific to non-production environments
 */
class RH_Staging {
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
	public function setup_actions() {}

	/**
	 * Hook into WordPress via actions
	 */
	public function setup_filters() {
		add_filter( 'pre_option_blog_public', array( $this, 'filter_pre_option_blog_public' ) );
		// add_filter( 'robots_txt', array( $this, 'filter_robots_txt' ), 0, 2 );
	}

	/**
	 * Set the 'blog_public' option to always be false so the site isn't indexed by search engines
	 *
	 * @param boolean $value The value of the blog_public option
	 */
	public function filter_pre_option_blog_public( $value = false ) {
		// Needs to be a string because Rank Math SEO expects a string using a strict comparison
		// See Sitemap_Index->add_sitemap_directive() method
		return '0';
	}

	/**
	 * Disallow robots from crawling the entire site
	 *
	 * @param string  $output The robots.txt output
	 * @param boolean $public Whether the site is public or not
	 */
	public function filter_robots_txt( $output = '', $public = true ) {
		$output = str_replace( 'Disallow: /wp-admin/', 'Disallow: /', $output );
		return $output;
	}
}
RH_Staging::get_instance();
