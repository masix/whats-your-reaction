<?php
/**
 * Admin Functions
 *
 * @package whats-your-reaction
 * @subpackage Functions
 */

// Prevent direct script access.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed' );
}

/**
 * Load stylesheets.
 */
function wyr_admin_enqueue_styles() {
	$url = trailingslashit( wyr_get_plugin_url() ) . 'includes/admin/css/';

	wp_enqueue_style( 'wyr-admin-main', $url . 'main.css', array(), '1.0' );
}

/**
 * Load javascripts.
 */
function wyr_admin_enqueue_scripts() {}

/**
 * Add custom fields to "Add New Reaction" screen
 */
function wyr_taxonomy_add_form_fields() {
	?>
	<div class="form-field term-icon-wrap">
		<label for="reaction-icon"><?php esc_html_e( 'Icon', 'wyr' ); ?></label>

		<?php
			$icons = wyr_get_reaction_icons();
			$index = 0;
		?>

		<ul class="wyr-icon-items">
			<?php foreach ( $icons as $icon_id => $icon_args ) : ?>
				<li class="wyr-icon-item">
					<label>
						<?php wyr_render_reaction_icon( $icon_id, array( 'size' => 40 ) ); ?>
						<span><?php echo esc_html( $icon_args['label'] ); ?></span>
						<input type="radio" name="icon" value="<?php echo esc_attr( $icon_id ); ?>" <?php checked( ! $index ); ?>  autocomplete="off" />
					</label>
				</li>
				<?php $index++; ?>
			<?php endforeach; ?>
		</ul>

	</div>
	<div class="form-field term-order-wrap">
		<label for="reaction-order"><?php echo esc_html_x( 'Order', 'label', 'wyr' ); ?></label>

		<input type="number" name="order" value="0" size="10" />
	</div>
	<?php
}

/**
 * Add custom fields to "Edit Reaction" screen
 *
 * @param WP_Term $term			Term object.
 */
function wyr_taxonomy_edit_form_fields( $term ) {
	$term_id	= $term->term_id;
	$icon 		= get_term_meta( $term_id, 'icon', true );
	$icons 		= wyr_get_reaction_icons();
	$order		= absint( get_term_meta( $term_id, 'order', true ) );
	$disabled   = get_term_meta( $term_id, 'disabled', true );
	?>
	<tr class="form-field term-icon-wrap">
		<th scope="row">
			<label for="icon"><?php echo esc_html_x( 'Icon', 'term field label', 'wyr' ); ?></label>
		</th>
		<td>
			<ul class="wyr-icon-items">
				<?php foreach ( $icons as $icon_id => $icon_args ) : ?>
					<li class="wyr-icon-item">
						<label>
							<?php wyr_render_reaction_icon( $icon_id, array( 'size' => 40 ) ); ?>
							<span><?php echo esc_html( $icon_args['label'] ); ?></span>
							<input type="radio" name="icon" value="<?php echo esc_attr( $icon_id ); ?>" <?php checked( $icon_id, $icon ); ?>  autocomplete="off" />
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		</td>
	</tr>
	<tr class="form-field term-icon-wrap">
		<th scope="row">
			<label for="order"><?php echo esc_html_x( 'Order', 'term field label', 'wyr' ); ?></label>
		</th>
		<td>
			<input type="text" name="order" value="<?php echo esc_attr( $order ); ?>">
		</td>
	</tr>
	<tr class="form-field term-active-wrap">
		<th scope="row">
			<label for="active"><?php echo esc_html_x( 'Disabled', 'term field label', 'wyr' ); ?></label>
		</th>
		<td>
			<input type="checkbox" name="disabled" value="standard"<?php checked( $disabled, 'standard' ); ?>>
		</td>
	</tr>
	<?php
}

function wyr_taxonomy_save_custom_form_fields( $term_id ) {
	$icon 		= filter_input( INPUT_POST, 'icon', FILTER_SANITIZE_STRING );
	$order 		= filter_input( INPUT_POST, 'order', FILTER_SANITIZE_NUMBER_INT );
	$disabled	= filter_input( INPUT_POST, 'disabled', FILTER_SANITIZE_STRING );

	if ( $icon ) {
		update_term_meta( $term_id, 'icon', $icon );
	}

	if ( ! $order ) {
		$order = count( wyr_get_reactions() ) + 1;
	}

	update_term_meta( $term_id, 'order', $order );
	update_term_meta( $term_id, 'disabled', $disabled ? $disabled : '' );
}

/**
 * Register new columns
 *
 * @param array $columns		List of columns.
 *
 * @return array
 */
function wyr_taxonomy_add_columns( $columns ) {
	$new_columns = array(
		'order' => _x( 'Order', 'taxonomy column name', 'wyr' ),
		'icon' 	=> _x( 'Icon', 'taxonomy column name', 'wyr' ),
	);

	$columns = array_merge( $new_columns, $columns );

	$columns['active'] = _x( 'Active?', 'taxonomy column name', 'wyr' );

	return $columns;
}

/**
 * Display custom columns content
 *
 * @param string $content			Column content.
 * @param string $column_name		Column name.
 * @param int    $term_id			Term id.
 *
 * @return string
 */
function wyr_taxonomy_display_custom_columns_content( $content, $column_name, $term_id ) {
	switch ( $column_name ) {
		case 'order':
			$content = get_term_meta( $term_id, 'order', true );
			break;

		case 'icon':
			$content = wyr_capture_reaction_icon( $term_id, array( 'size' => 30 ) );
			break;

		case 'active':
			$content = 'standard' === get_term_meta( $term_id, 'disabled', true ) ? 'no' : 'yes';
			break;
	}

	return $content;
}

/**
 * Chamge terms query args
 *
 * @param array $args			Query args.
 * @param array $taxonomies		Taxonomies.
 *
 * @return array
 */
function wyr_taxonomy_change_term_list_order( $args, $taxonomies ) {
	$taxonomy = wyr_get_taxonomy_name();

	if ( ! is_admin() || ! in_array( $taxonomy, $taxonomies, true ) ) {
		return $args;
	}

	$args['meta_key']	= 'order';
	$args['orderby'] 	= 'meta_value_num';
	$args['order'] 		= 'ASC';

	return $args;
}