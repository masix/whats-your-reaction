<?php
/**
 * Admin Hooks
 *
 * @package whats-your-reaction
 * @subpackage Hooks
 */

// Prevent direct script access.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed' );
}

// Edit.
add_action( 'reaction_add_form_fields', 'wyr_taxonomy_add_form_fields' );
add_action( 'reaction_edit_form_fields', 'wyr_taxonomy_edit_form_fields' );

// Save.
add_action( 'create_reaction', 'wyr_taxonomy_save_custom_form_fields', 10, 2 );
add_action( 'edited_reaction', 'wyr_taxonomy_save_custom_form_fields', 10, 2 );

// List view.
add_filter( 'manage_edit-reaction_columns', 'wyr_taxonomy_add_columns' );
add_filter( 'manage_reaction_custom_column', 'wyr_taxonomy_display_custom_columns_content', 10, 3 );
add_action( 'get_terms_args', 'wyr_taxonomy_change_term_list_order', 10, 2 );

// Assets.
add_action( 'admin_enqueue_scripts', 'wyr_admin_enqueue_styles' );
add_action( 'admin_enqueue_scripts', 'wyr_admin_enqueue_scripts' );
