<?php
/**
 * Common Functions
 *
 * @package whats-your-reaction
 * @subpackage Functions
 */

// Prevent direct script access.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed' );
}

/**
 * Plugin acitvation
 */
function wyr_activate() {
	wyr_install_votes_schema();
}

/**
 * Plugin deacitvation
 */
function wyr_deactivate() {}

/**
 * Plugin uninstallation
 */
function wyr_uninstall() {}

/**
 * Install table 'wyr_votes'
 */
function wyr_install_votes_schema() {
	global $wpdb;

	$current_ver    = '1.0';
	$installed_ver  = get_option( 'wyr_votes_table_version' );

	// Create table only if needed.
	if ( $installed_ver !== $current_ver ) {
		$table_name      = $wpdb->prefix . wyr_get_votes_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		vote_id bigint(20) unsigned NOT NULL auto_increment,
		post_id bigint(20) NOT NULL ,
		vote varchar(20) NOT NULL,
		author_id bigint(20) NOT NULL default '0',
  		author_ip varchar(100) NOT NULL default '',
		author_host varchar(200) NOT NULL,
		date datetime NOT NULL default '0000-00-00 00:00:00',
  		date_gmt datetime NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY (vote_id),
		KEY post_id (post_id)
	) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		update_option( 'wyr_votes_table_version', $current_ver );
	}
}

/**
 * Load stylesheets.
 */
function wyr_enqueue_styles() {
	$url = trailingslashit( wyr_get_plugin_url() ) . 'css/';

	wp_enqueue_style( 'wyr-main', $url . 'main.min.css', array(), '1.0' );
	wp_style_add_data( 'wyr-main', 'rtl', 'replace' );
}

/**
 * Load javascripts.
 */
function wyr_enqueue_scripts() {
	wp_enqueue_script( 'wyr-front', wyr_get_plugin_url() . 'js/front.js', array( 'jquery' ), '1.0', true );

	$front_config = array(
		'ajax_url'          => admin_url( 'admin-ajax.php' ),
	);

	wp_localize_script( 'wyr-front', 'wyr_front_config', wp_json_encode( $front_config ) );
}

/**
 * Return unique taxonomy name
 *
 * @return string
 */
function wyr_get_taxonomy_name() {
	return 'reaction';
}

/**
 * Register "Reactions" taxonomy
 */
function wyr_register_taxonomy() {
	$labels = array(
		'name' 				=> _x( 'Reactions', 'taxonomy general name', 'wyr' ),
		'singular_name' 	=> _x( 'Reaction', 'taxonomy singular name', 'wyr' ),
		'search_items' 		=> __( 'Search Reactions', 'wyr' ),
		'all_items' 		=> __( 'All Reactions', 'wyr' ),
		'parent_item' 		=> __( 'Parent Reaction', 'wyr' ),
		'parent_item_colon' => __( 'Parent Reaction:', 'wyr' ),
		'edit_item' 		=> __( 'Edit Reaction', 'wyr' ),
		'update_item' 		=> __( 'Update Reaction', 'wyr' ),
		'add_new_item' 		=> __( 'Add New Reaction', 'wyr' ),
		'new_item_name' 	=> __( 'New Reaction Name', 'wyr' ),
		'menu_name' 		=> __( 'Reactions', 'wyr' ),
		'view_item'         => __( 'View Reaction', 'wyr' ),
		'not_found'         => __( 'No Reactions Found', 'wy' ),
	);

	$args = array(
		'hierarchical' 		=> false,
		'labels' 			=> $labels,
		'show_ui' 			=> true,
		'show_admin_column' => true,
		'query_var' 		=> true,
		'orderby' 			=> 'slug',
		'rewrite' 			=> array(
			'slug' => 'reaction',
		),
	);

	$taxonomy_name = wyr_get_taxonomy_name();

	register_taxonomy( $taxonomy_name, array( 'post' ), $args );
}

/**
 * Return list of ordered reactions
 *
 * @return array		List of term objects.
 */
function wyr_get_reactions() {
	return get_terms( array(
		'taxonomy'		=> wyr_get_taxonomy_name(),
		'hide_empty'	=> false,
		'meta_key' 	=> 'order',
		'orderby'	=> 'meta_value_num',
		'order'		=> 'ASC',
	) );
}

/**
 * Return reaction term object by name
 *
 * @param string $name		Reaction name.
 *
 * @return bool|WP_Term
 */
function wyr_get_reaction( $name ) {
	$terms = get_terms( array(
		'taxonomy'		=> wyr_get_taxonomy_name(),
		'hide_empty'	=> false,
		'slug'			=> $name,
	) );

	if ( empty( $terms ) ) {
		return false;
	}

	return $terms[0];
}

/**
 * Return list of ordered post reactions
 *
 * @return array		List of term objects.
 */
function wyr_get_post_reactions( $post = null ) {
	$post = get_post( $post );

	return wp_get_post_terms(
		$post->ID,
		wyr_get_taxonomy_name(),
		array(
			'meta_key' 	=> 'order',
			'orderby'	=> 'meta_value_num',
			'order'		=> 'ASC',
		)
	);
}

/**
 * Check whether the reaction exists
 *
 * @param string $name		Type of reaction.
 *
 * @return bool
 */
function wyr_is_valid_reaction( $name ) {
	$terms = get_terms( array(
		'taxonomy'		=> wyr_get_taxonomy_name(),
		'hide_empty'	=> false,
		'slug'			=> $name,
	) );

	return ! empty( $terms );
}

/**
 * Hook into post content
 *
 * @param string $content		Post content.
 *
 * @return string
 */
function wyr_load_post_voting_box( $content ) {
	if ( ! apply_filters( 'wyr_load_post_voting_box', is_single() ) ) {
		return $content;
	}

	$content .= wyr_get_voting_box();

	return $content;
}

/**
 * Return voting box HTML container
 *
 * @return string
 */
function wyr_get_voting_box() {
	return do_shortcode( '[wyr_voting_box]' );
}

/**
 * Render voting box HTML container
 *
 * @return string
 */
function wyr_render_voting_box() {
	echo wyr_get_voting_box();
}

/**
 * Load a template part into a template
 *
 * @param string $slug The slug name for the generic template.
 * @param string $name The name of the specialised template.
 */
function wyr_get_template_part( $slug, $name = null ) {
	// Trim off any slashes from the slug.
	$slug = ltrim( $slug, '/' );

	if ( empty( $slug ) ) {
		return;
	}

	$parent_dir_path = trailingslashit( get_template_directory() );
	$child_dir_path  = trailingslashit( get_stylesheet_directory() );

	$files = array(
		$child_dir_path . 'whats-your-reaction/' . $slug . '.php',
		$parent_dir_path . 'whats-your-reaction/' . $slug . '.php',
		wyr_get_plugin_dir() . 'templates/' . $slug . '.php',
	);

	if ( ! empty( $name ) ) {
		array_unshift(
			$files,
			$child_dir_path . 'whats-your-reaction/' . $slug . '-' . $name . '.php',
			$parent_dir_path . 'whats-your-reaction/' . $slug . '-' . $name . '.php',
			wyr_get_plugin_dir() . 'templates/' . $slug . '-' . $name . '.php'
		);
	}

	$located = '';

	foreach ( $files as $file ) {
		if ( empty( $file ) ) {
			continue;
		}

		if ( file_exists( $file ) ) {
			$located = $file;
			break;
		}
	}

	if ( strlen( $located ) ) {
		load_template( $located, false );
	}
}

/**
 * Check whether user has already voted for a post
 *
 * @param string $type 			Vote type.
 * @param int    $post_id 		Post id.
 * @param int    $user_id 		User id.
 *
 * @return mixed		Vote type or false if not exists
 */
function wyr_user_voted( $type, $post_id = 0, $user_id = 0 ) {
	$post = get_post( $post_id );

	if ( 0 === $user_id ) {
		$user_id = get_current_user_id();
	}

	// User not logged in, guest voting disabled.
	if ( 0 === $user_id && ! wyr_guest_voting_is_enabled() ) {
		return false;
	}

	// User not logged in, guest voting enabled.
	if ( 0 === $user_id && wyr_guest_voting_is_enabled() ) {
		$vote_cookie = filter_input( INPUT_COOKIE, 'wyr_vote_' . $type . '_' . $post->ID, FILTER_SANITIZE_STRING );

		return (bool) $vote_cookie;
	}

	// User logged in.
	global $wpdb;
	$votes_table_name = $wpdb->prefix . wyr_get_votes_table_name();

	$vote = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT vote
			FROM $votes_table_name
			WHERE post_id = %d AND author_id = %d AND vote = %s
			ORDER BY vote_id DESC
			LIMIT 1",
			$post->ID,
			$user_id,
			$type
		)
	);

	return $vote;
}

/**
 * Check whether guest user can vote
 *
 * @return bool
 */
function wyr_guest_voting_is_enabled() {
	return apply_filters( 'wyr_guest_voting_is_enabled', true );
}

/**
 * Get the table name of the votes table
 *
 * @return string
 */
function wyr_get_votes_table_name() {
	return 'wyr_votes';
}

/**
 * Return votes summary
 *
 * @param int|WP_Post $post_id 			Optional. Post ID or WP_Post object. Default is global `$post`.
 *
 * @return int
 */
function wyr_get_post_votes( $post_id = 0 ) {
	$post = get_post( $post_id );

	return get_post_meta( $post->ID, '_wyr_votes', true );
}

/**
 * Register new vote for a post
 *
 * @param array $vote_arr Vote config.
 *
 * @return bool|WP_Error
 */
function wyr_vote_post( $vote_arr ) {
	$defaults = array(
		'post_id'   => get_the_ID(),
		'author_id' => get_current_user_id(),
		'type'      => '',
	);

	$vote_arr = wp_parse_args( $vote_arr, $defaults );

	global $wpdb;
	$table_name = $wpdb->prefix . wyr_get_votes_table_name();

	$post_date  = current_time( 'mysql' );
	$ip_address = wyr_get_ip_address();
	$host = gethostbyaddr( $ip_address );

	$affected_rows = $wpdb->insert(
		$table_name,
		array(
			'post_id'     => $vote_arr['post_id'],
			'vote'        => $vote_arr['type'],
			'author_id'   => $vote_arr['author_id'],
			'author_ip'   => $ip_address ? $ip_address : '',
			'author_host' => $host ? $host : '',
			'date'        => $post_date,
			'date_gmt'    => get_gmt_from_date( $post_date ),
		),
		array(
			'%d',
			'%s',
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
		)
	);

	if ( false === $affected_rows ) {
		return new WP_Error( 'wyr_insert_vote_failed', esc_html__( 'Could not insert new vote into the database!', 'wyr' ) );
	}

	$meta = wyr_update_votes_metadata( $vote_arr['post_id'] );

	// Assign post to reaction term if reached threshold.
	$reaction_threshold = apply_filters( 'wyr_reaction_threshold', 3 );
	$reaction_type 		= $vote_arr['type'];

	if ( $meta[ $reaction_type ]['count'] >= $reaction_threshold ) {
		$reaction_term = wyr_get_reaction( $reaction_type );

		wp_set_post_terms( $vote_arr['post_id'], array( $reaction_term->term_id ), wyr_get_taxonomy_name(), true );
	}

	do_action( 'wyr_vote_added', $vote_arr, $meta );

	return $meta;
}

/**
 * Return vistor IP address
 *
 * @return string
 */
function wyr_get_ip_address() {
	$http_x_forwarder_for = filter_input( INPUT_SERVER, 'HTTP_X_FORWARDED_FOR', FILTER_SANITIZE_STRING );
	$remote_addr          = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING );

	if ( empty( $http_x_forwarder_for ) ) {
		$ip_address = $remote_addr;
	} else {
		$ip_address = $http_x_forwarder_for;
	}

	if ( false !== strpos( $ip_address, ',' ) ) {
		$ip_address = explode( ',', $ip_address );
		$ip_address = $ip_address[0];
	}

	return $ip_address;
}

/**
 * Update voting stats
 *
 * @param int   $post_id            Post id.
 * @param array $meta               Current meta value.
 *
 * @return bool
 */
function wyr_update_votes_metadata( $post_id = 0, $meta = array() ) {
	$post = get_post( $post_id );

	if ( empty( $meta ) ) {
		$meta = wyr_generate_votes_metadata( $post );
	}

	if ( empty( $meta ) ) {
		return false;
	}

	foreach ( $meta as $type => $data ) {
		update_post_meta( $post->ID, '_wyr_' . $type . '_count', $data['count'] );
		update_post_meta( $post->ID, '_wyr_' . $type . '_percentage', $data['percentage'] );
	}

	update_post_meta( $post->ID, '_wyr_votes', $meta );

	return $meta;
}

/**
 * Generate voting stats
 *
 * @param int $post_id          Post id.
 *
 * @return array
 */
function wyr_generate_votes_metadata( $post_id = 0 ) {
	$post = get_post( $post_id );

	global $wpdb;
	$votes_table_name = $wpdb->prefix . wyr_get_votes_table_name();

	$votes = $wpdb->get_results(
		$wpdb->prepare(
			"
			SELECT vote, count(vote) AS cnt
			FROM $votes_table_name
			WHERE post_id = %d
			GROUP BY vote",
			$post->ID
		)
	);

	$total_votes = 0;
	$meta = array();

	foreach ( $votes as $group_data ) {
		$type = $group_data->vote;
		$cnt  = $group_data->cnt;

		$meta[ $type ] = array(
			'count' => $cnt,
		);

		$total_votes += $cnt;
	}

	// Calculate percentages.
	foreach ( $meta as $type => $data ) {
		$percentage = round( ( 100 * $data['count'] ) / $total_votes );

		$meta[ $type ]['percentage'] = $percentage;
	}

	return apply_filters( 'wyr_votes_metadata', $meta, $post->ID );
}





function wyr_render_reaction_icon( $term_id, $args = array() ) {
	echo wyr_capture_reaction_icon( $term_id, $args );
}

function wyr_capture_reaction_icon( $term_id, $args = array() ) {
	$defaults = array(
		'size' => 50,
	);

	$args = wp_parse_args( $args, $defaults );


	$icon = $term_id;
	if ( is_int( $term_id ) ) {
		$icon = get_term_meta( $term_id, 'icon', true );
	}


	$out = '';

	$class = array(
		'wyr-reaction-icon',
		'wyr-reaction-icon-' . $icon,
	);

	$out .= '<span class="'. implode( ' ', array_map( 'sanitize_html_class', $class ) ) . '">';
		$out .= '<img width="' . absint( $args['size'] ) .  '" height="' . absint( $args['size'] ) . '" src="' . esc_url( wyr_get_plugin_url() . 'images/' . $icon. '.svg' ) . '" alt="" />';
	$out .= '</span>';

	return apply_filters( 'wyr_capture_reaction_icon', $out, $term_id, $args );
}


function wyr_get_reaction_icons() {
	$icons = array(
		'angry'     => array( 'label' => __( 'Angry', 'wyr' ) ),
		'cute'      => array( 'label' => __( 'Cute', 'wyr' ) ),
		'cry'       => array( 'label' => __( 'Cry', 'wyr' ) ),
		'geeky'     => array( 'label' => __( 'Geeky', 'wyr' ) ),
		'lol'       => array( 'label' => __( 'LOL', 'wyr' ) ),
		'love'      => array( 'label' => __( 'LOVE', 'wyr' ) ),
		'omg'       => array( 'label' => __( 'OMG', 'wyr' ) ),
		'win'       => array( 'label' => __( 'WIN', 'wyr' ) ),
		'wtf'       => array( 'label' => __( 'WTF', 'wyr' ) ),
	);

	return apply_filters( 'wyr_reaction_icons', $icons );
}