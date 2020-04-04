<?php
/**
 * Hooks
 *
 * @package whats-your-reaction
 * @subpackage Functions
 */

// Prevent direct script access.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed' );
}

// Init.
add_action( 'init', 'wyr_register_taxonomy', 0 );

// Post.
add_filter( 'the_content', 'wyr_load_post_voting_box' );

// Ajax.
add_action( 'wp_ajax_wyr_vote_post',        'wyr_ajax_vote_post' );
add_action( 'wp_ajax_nopriv_wyr_vote_post',	'wyr_ajax_vote_post' );

// Assets.
add_action( 'wp_enqueue_scripts', 'wyr_enqueue_styles' );
add_action( 'wp_enqueue_scripts', 'wyr_enqueue_scripts' );
