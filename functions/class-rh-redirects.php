<?php
/**
 * Handles odd redirects we need to account for
 */
class RH_Redirects {

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
	 * Hook in to WordPress via actions
	 */
	public function setup_actions() {
		add_action( 'template_redirect', array( $this, 'action_template_redirect_disable_search_rss_feeds' ), 10 );
	}

	/**
	 * Hook in to WordPress via filters
	 */
	public function setup_filters() {}

	/**
	 * Redirect requests for a feed of search results back to the search result page.
	 * Spammers are abusing this for SEO purposes.
	 */
	public function action_template_redirect_disable_search_rss_feeds() {
		if ( ! is_search() || ! is_feed() ) {
			return;
		}
		$current_url  = RH_Helpers::get_current_url();
		$redirect_url = explode( 'feed/', $current_url );
		if ( ! empty( $redirect_url[0] ) ) {
			$redirect_url = $redirect_url[0];
		}
		if ( ! empty( $redirect_url ) ) {
			wp_redirect( $redirect_url, $status = 301 );
			die();
		}
	}
}
RH_Redirects::get_instance();
