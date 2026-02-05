<?php
/**
 * Controller for the purchase rules.
 *
 * @since 6.9.0
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\Controller as REST_Controller;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Admin\Page;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Admin\List_Table;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Admin\Single_Post;
use TEC\Events_Pro\Custom_Tables\V1\Models\Provisional_Post;

/**
 * Controller for the purchase rules.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules
 */
class Controller extends Controller_Contract {
	/**
	 * The action that will be fired when this Controller registers.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_tickets_plus_purchase_rules_registered';

	/**
	 * Determines if the feature is active or not.
	 *
	 * @since 6.9.0
	 *
	 * @return bool Whether the feature is active or not.
	 */
	public function is_active(): bool {
		/**
		 * Allows filtering whether the Purchase Rules feature
		 * should be active or not.
		 *
		 * For MVP, Purchase Rules are only compatible with TEC events, so there is no reason to be active if TEC is not loaded.
		 *
		 * @since 6.9.0
		 *
		 * @param bool $active Defaults to `true`.
		 */
		return (bool) apply_filters( 'tec_tickets_plus_purchase_rules_feature_active', did_action( 'tec_events_fully_loaded' ) );
	}

	/**
	 * Register the controller.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function do_register(): void {
		$this->container->singleton( Template::class );
		$this->container->singleton( List_Table::class );

		$this->container->register_on_action( 'tec_tickets_plus_purchase_rules_tables_registered', REST_Controller::class );
		$this->container->register_on_action( 'tec_tickets_plus_purchase_rules_tables_registered', Listeners::class );

		$this->container->register_on_action( 'tec_tickets_plus_purchase_rules_tables_registered', Order::class );

		if ( is_admin() ) {
			$this->container->register_on_action( 'tec_tickets_plus_purchase_rules_tables_registered', Page::class );
			$this->container->register_on_action( 'tec_tickets_plus_purchase_rules_tables_registered', Single_Post::class );
		} else {
			$this->container->register_on_action( 'tec_tickets_plus_purchase_rules_tables_registered', Frontend::class );
		}

		$this->container->register( Tables::class );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( Tables::class )->unregister();

		if ( $this->container->isBound( Order::class ) ) {
			$this->container->get( Order::class )->unregister();
		}

		if ( $this->container->isBound( Page::class ) ) {
			$this->container->get( Page::class )->unregister();
		}

		if ( $this->container->isBound( Single_Post::class ) ) {
			$this->container->get( Single_Post::class )->unregister();
		}

		if ( $this->container->isBound( Listeners::class ) ) {
			$this->container->get( Listeners::class )->unregister();
		}

		if ( $this->container->isBound( Frontend::class ) ) {
			$this->container->get( Frontend::class )->unregister();
		}
	}

	/**
	 * Maybe normalize the provisional post ID.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return int The normalized post ID.
	 */
	public static function maybe_normalize_provisional_post_id( int $post_id ): int {
		if ( ! did_action( 'tec_events_pro_custom_tables_v1_fully_activated' ) ) {
			return $post_id;
		}

		/** @var Provisional_Post $provisional_post */
		$provisional_post = tribe( Provisional_Post::class );

		if ( ! $provisional_post->is_provisional_post_id( $post_id ) ) {
			return $post_id;
		}

		$occurrence = $provisional_post->get_occurrence_row( $post_id );
		return $occurrence ? $occurrence->post_id : $post_id;
	}
}
