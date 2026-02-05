<?php
/**
 * Template to display a list of sections.
 *
 * @since 1.0.0
 *
 * @var Tribe__Template $this     Template object.
 * @var array[]         $sections Array of section settings.
 */

if ( empty( $sections ) ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-tickets-commerce-section-menu">
	<?php
	foreach ( $sections as $section ) {
		$this->template( 'settings/link', $section );
	}
	?>
</div>
