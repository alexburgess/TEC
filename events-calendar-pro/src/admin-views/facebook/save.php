<?php
/**
 * View: Facebook Settings Save Facebook App.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/admin-views/facebook/save.php
 *
 * See more documentation about our views templating system.
 *
 * @since 7.0.0 Migrated to Events Pro from Events Virtual.
 * @since 7.7.6 Changed a <div> to a <fieldset> to match the new admin UI styling.
 *
 * @version 7.7.6
 *
 * @link    http://evnt.is/1aiy
 *
 * @var URL $url An instance of the URL handler.
 */

?>
<fieldset class="tribe-settings-facebook-application__connect-container tribe-field tribe-field--text">
	<button
		class="tribe-settings-facebook-application__connect-button button-primary"
		type="button"
		data-ajax-save-url="<?php echo $url->to_save_facebook_app_link(); ?>"
	>
		<span>
			<?php echo esc_html_x( 'Save Facebook App', 'Save a Facebook App ID and App Secret.', 'tribe-events-calendar-pro' ); ?>
		</span>
	</button>
</fieldset>
