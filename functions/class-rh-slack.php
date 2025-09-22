<?php
/**
 * For integrating WordPress with Slack
 */
class RH_Slack {

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
	 * Hook into various WordPress actions
	 */
	public function setup_actions() {
		add_action( 'transition_post_status', array( $this, 'action_transition_post_status' ), 10, 3 );
	}

	/**
	 * Hook into various WordPress filters
	 */
	public function setup_filters() {
		add_filter( 'rh/slack/send_message/args', array( $this, 'filter_rh_slack_send_message_args' ) );
	}

	/**
	 * Send a Slack notification when a Doc's status changes
	 *
	 * @param  string          $new_status The new status of the post
	 * @param  string          $old_status The old status of the post
	 * @param  integer|WP_Post $post The post whose status has changed
	 */
	public function action_transition_post_status( $new_status = '', $old_status = '', $post = 0 ) {
		$supported_post_types = static::get_notification_post_types();
		if ( ! in_array( $post->post_type, $supported_post_types, true ) ) {
			return;
		}

		if ( ! static::is_setup() ) {
			return;
		}

		if ( $new_status === $old_status && $new_status !== 'publish' && $new_status !== 'future' ) {
			return;
		}

		$the_post_title = get_the_title( $post );
		$the_post_title = html_entity_decode( $the_post_title );
		$the_edit_link  = get_edit_post_link( $post );
		$the_permalink  = get_permalink( $post );
		if ( get_post_status( $post ) === 'future' ) {
			$fake_post              = $post;
			$fake_post->post_status = 'publish';
			$the_permalink          = get_permalink( $fake_post );
		}

		$slack_username = 'WordPress Bot';
		$post_type_obj  = get_post_type_object( $post->post_type );
		if ( ! empty( $post_type_obj->labels->name ) ) {
			// Set the Slack username to "<post_type name> Bot"
			$slack_username = $post_type_obj->labels->name . ' Bot';
		}

		$slack_message = '';
		$slack_args    = array(
			'username'     => $slack_username,
			'unfurl_links' => false,
		);

		// Post is scheduled
		if ( $new_status === 'future' && $old_status !== 'future' ) {
			$timezone_string = get_option( 'timezone_string' );
			if ( empty( $timezone_string ) ) {
				$timezone_string = 'Etc/GMT';
			}
			$timezone      = new DateTimeZone( $timezone_string );
			$date          = new DateTime( $post->post_date, $timezone );
			$the_date_time = $date->format( 'Y-m-d g:ia T' );
			// Get the hour to dynamically update the clock emoji for the bot
			$the_hour = $date->format( 'g' );
			if ( $date->format( 'i' ) === '30' ) {
				$the_hour .= '30';
			}
			$slack_args['icon_emoji'] = ':clock' . $the_hour . ':';

			// Calculate the ISO datetime string to pass to the time conversion URL
			$date->setTimezone( new DateTimeZone( 'Etc/GMT' ) );
			$iso_date_time = $date->format( 'Ymd\THis' );

			$the_time_conversion_url = add_query_arg(
				array(
					'iso' => $iso_date_time,
					'p1'  => 137, // Los Angeles, USA
					'p2'  => 179, // New York, USA
					'p3'  => 195, // Paris, FR
				),
				'https://www.timeanddate.com/worldclock/converter.html'
			);

			$slack_message = "Scheduled <$the_permalink|$the_post_title> to go live at <$the_time_conversion_url|$the_date_time>";
		}

		// Post is unscheduled
		if ( $old_status === 'future' && $new_status !== 'publish' && $new_status !== 'future' ) {
			$slack_args['icon_emoji'] = ':no_entry_sign:';
			$slack_message            = "Unscheduled <$the_edit_link|$the_post_title>";
		}

		// Post is published
		if ( $new_status === 'publish' && $old_status !== 'publish' ) {
			$slack_message = "Published <$the_permalink|$the_post_title>";
			set_transient( 'slack_publish_' . $post->ID, true, 1 * MINUTE_IN_SECONDS );
		}

		// Post is unpublished
		if ( $old_status === 'publish' && $new_status === 'draft' ) {
			$slack_args['icon_emoji'] = ':no_entry_sign:';
			$slack_message            = "Unpublished <$the_edit_link|$the_post_title>";
		}

		// Post is trashed
		if ( $new_status === 'trash' && $old_status === 'publish' ) {
			$the_trashed_posts_url    = add_query_arg(
				array(
					'post_status' => 'trash',
					'post_type'   => $post->post_type,
				),
				admin_url( 'edit.php' )
			);
			$slack_args['icon_emoji'] = ':x:';
			$slack_message            = "Trashed <$the_trashed_posts_url|$the_post_title>";
		}

		// Post is updated
		// Why do we need to check if $_POST is not empty? See https://github.com/WordPress/gutenberg/issues/15094#issuecomment-558986406
		if ( $new_status === 'publish' && $old_status === 'publish' && ! empty( $_POST ) ) {
			// Bail if the post was recently published to avoid duplicate notifications
			if ( get_transient( 'slack_publish_' . $post->ID ) ) {
				return;
			}
			$the_revision_url = wp_get_post_revisions_url( $post );
			$slack_message    = "Updated <$the_permalink|$the_post_title> (<$the_revision_url|diff>)";
		}

		// Maybe post the Slack message?
		$slack_message = apply_filters(
			'rh/slack/post_notification/message',
			$slack_message,
			$new_status,
			$old_status,
			$post
		);
		if ( $slack_message ) {
			$slack_args = apply_filters(
				'rh/slack/post_notification/args',
				$slack_args,
				$new_status,
				$old_status,
				$post
			);
			static::send_message( $slack_message, $slack_args );
		}
	}

	/**
	 * Don't send Slack notfiications to production channels if not running in the production environment
	 *
	 * @param  array $args The Slack arguments that get sent to the Slack API call to post a message
	 * @return array       The modified Slack arguments
	 */
	public function filter_rh_slack_send_message_args( $args = array() ) {
		if ( wp_get_environment_type() !== 'production' ) {
			$args['channel'] = 'mktg-test-alerts';
		}
		return $args;
	}

	/**
	 * Get the post types that support 'slack-notifications'
	 */
	public static function get_notification_post_types() {
		$post_types = get_post_types_by_support( 'slack-notifications' );
		return apply_filters( 'rh/slack/notification_post_types', $post_types );
	}

	/**
	 * Conditional check if the Slackbot token is set
	 *
	 * @param boolean $quiet whether to display a visible error or not if the token is not set
	 * @return boolean True if the Slackbot token is set, otherwise false
	 */
	public static function is_setup( $quiet = true ) {
		$is_setup = false;
		if ( defined( 'RH_SLACKBOT_TOKEN' ) && ! empty( RH_SLACKBOT_TOKEN ) ) {
			$is_setup = true;
		}
		if ( ! $quiet && ! $is_setup ) {
			wp_die( '<code>RH_SLACKBOT_TOKEN</code> is not defined! Can\'t make requests to Slack\'s API' );
		}
		return $is_setup;
	}

	/**
	 * Send Slack chat message
	 *
	 * @link https://api.slack.com/methods/chat.postMessage
	 *
	 * @param  string $message The Slack message to be sent
	 * @param  array  $args Arguments to pass along to the Slack API. See link above.
	 */
	public static function send_message( $message = '', $args = array() ) {
		if ( ! static::is_setup() ) {
			return false;
		}

		$defaults = array(
			'channel'    => 'mktg-bots',
			'icon_emoji' => ':wordpress:',
			'username'   => 'WordPress@rh.io',
		);
		$args     = wp_parse_args( $args, $defaults );

		$args['text'] = $message;
		$args         = apply_filters( 'rh/slack/send_message/args', $args );

		$request_url  = 'https://slack.com/api/chat.postMessage';
		$request_args = array(
			'headers' => array(
				'Content-type'  => 'application/json',
				'Authorization' => 'Bearer ' . RH_SLACKBOT_TOKEN,
			),
			'body'    => wp_json_encode( $args ),
		);
		$resp         = wp_remote_post( $request_url, $request_args );
		if ( is_wp_error( $resp ) && WP_DEBUG ) {
			wp_die( $resp->get_error_message() );
		}
		$response_code      = wp_remote_retrieve_response_code( $resp );
		$response_body      = wp_remote_retrieve_body( $resp );
		$response_body_json = json_decode( $response_body );
		return $response_body_json;
	}
}
RH_Slack::get_instance();
