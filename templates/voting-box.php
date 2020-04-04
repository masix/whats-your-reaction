<?php
/**
 * Voting Box Template Part
 *
 * @package whats-your-reaction
 * @subpackage Functions
 */

?>

<aside class="wyr-reactions">
	<h2 class="g1-beta g1-beta-2nd"><?php esc_html_e( 'What\'s your reaction?', 'wyr' ) ?></h2>

	<?php
	// Common for all reactions.
	$wyr_reactions 	= wyr_get_reactions();
	$wyr_votes 		= wyr_get_post_votes();
	$wyr_post 		= get_post();
	$wyr_author_id	= get_current_user_id();
	$wyr_nonce 		= wp_create_nonce( 'wyr-vote-post' );
	?>
	<ul class="wyr-reaction-items">
	<?php foreach ( $wyr_reactions as $wyr_reaction ) : ?>
		<?php
		// Reaction id.
		$wyr_reaction_id = $wyr_reaction->slug;

		// Reaction CSS classes.
		$wyr_reaction_classes = array(
			'wyr-reaction',
			'wyr-reaction-' . $wyr_reaction_id,
		);

		if ( wyr_user_voted( $wyr_reaction_id ) ) {
			$wyr_reaction_classes[] = 'wyr-reaction-voted';
		}

		$wyr_reaction_value 	 = isset( $wyr_votes[ $wyr_reaction_id ] ) ? $wyr_votes[ $wyr_reaction_id ]['count'] : 0;
		$wyr_reaction_percentage = isset( $wyr_votes[ $wyr_reaction_id ] ) ? $wyr_votes[ $wyr_reaction_id ]['percentage'] : 0;
		?>
		<li class="wyr-reaction-item">
			<a class="<?php echo implode( ' ', array_map( 'sanitize_html_class', $wyr_reaction_classes ) ); ?>" data-wyr-nonce="<?php echo esc_attr( $wyr_nonce ); ?>" data-wyr-post-id="<?php echo absint( $wyr_post->ID ); ?>" data-wyr-author-id="<?php echo absint( $wyr_author_id ); ?>" data-wyr-reaction="<?php echo esc_attr( $wyr_reaction_id ); ?>">
				<?php wyr_render_reaction_icon( $wyr_reaction->term_id, array( 'size' => 50 ) ); ?>

				<div class="wyr-reaction-track">
					<div class="wyr-reaction-value"><?php echo absint( $wyr_reaction_value ); ?></div>
					<div class="wyr-reaction-bar" style="height: <?php echo absint( $wyr_reaction_percentage ); ?>%;">
					</div>
				</div>
				<div class="wyr-reaction-button"><strong class="wyr-reaction-label"><?php echo esc_html( $wyr_reaction->name ); ?></strong></div>
			</a>
		</li>
	<?php endforeach; ?>
	</ul>
</aside>

