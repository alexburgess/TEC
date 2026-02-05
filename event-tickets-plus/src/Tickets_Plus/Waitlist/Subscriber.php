<?php
/**
 * The factory class to interact with a single Subscriber.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */

namespace TEC\Tickets_Plus\Waitlist;

use TEC\Common\StellarWP\DB\DB;
use InvalidArgumentException;
use RuntimeException;
use Exception;
use TEC\Tickets_Plus\Waitlist\Tables\Waitlist_Subscribers as Subscribers_Table;
use TEC\Tickets_Plus\Waitlist\Tables\Waitlist_Pending_Users as Pending_Subscribers_Table;
use DateTime;
use DateTimeInterface;

/**
 * Class Subscriber.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */
class Subscriber {
	/**
	 * The status of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @var int
	 */
	public const STATUS_CREATED = 0;

	/**
	 * The status of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @var int
	 */
	public const STATUS_NOTIFIED = 1;

	/**
	 * The action that will be fired to delete a subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const DELETE_ACTION = 'tec_tickets_plus_waitlist_delete_subscriber';

	/**
	 * The valid keys for a subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @var array
	 */
	protected const VALID_KEYS = [
		'waitlist_user_id' => true,
		'post_id'          => true,
		'waitlist_id'      => true, // could be unavailable.
		'status'           => true,
		'wp_user_id'       => true,
		'fullname'         => true,
		'email'            => true,
		'meta'             => true,
		'created'          => true,
	];

	/**
	 * The placeholders for the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @var array
	 */
	protected const PLACEHOLDERS = [
		'post_id'     => '%d',
		'waitlist_id' => '%d',
		'status'      => '%d',
		'wp_user_id'  => '%d',
		'fullname'    => '%s',
		'email'       => '%s',
		'meta'        => '%s',
		'created'     => '%s',
	];

	/**
	 * The "live" data of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @var array
	 */
	protected array $data = [];

	/**
	 * The original data of the subscriber.
	 *
	 * Compared against the "live" data to determine what update is needed if any.
	 *
	 * @since 6.2.0
	 *
	 * @var array
	 */
	protected array $original_data = [];

	/**
	 * Subscriber constructor.
	 *
	 * @since 6.2.0
	 *
	 * @param array $subscriber_array The subscriber data.
	 * @throws InvalidArgumentException If the key is invalid.
	 */
	public function __construct( array $subscriber_array = [] ) {
		foreach ( $subscriber_array as $key => $value ) {
			if ( ! $this->is_valid_key( $key ) ) {
				throw new InvalidArgumentException( 'Invalid key: ' . $key );
			}

			$this->original_data[ $key ] = $value;
		}

		if ( isset( $this->original_data['meta'] ) ) {
			if ( is_string( $this->original_data['meta'] ) ) {
				json_decode( $this->original_data['meta'] );
				$this->original_data['meta'] = json_last_error() !== JSON_ERROR_NONE ? wp_json_encode( $this->original_data['meta'] ) : $this->original_data['meta'];
			} else {
				$this->original_data['meta'] = wp_json_encode( $this->original_data['meta'] );
			}
		}

		if ( ! empty( $this->original_data['created'] ) && ! is_numeric( $this->original_data['created'] ) && ! is_object( $this->original_data['created'] ) ) {
			$this->original_data['created'] = strtotime( $this->original_data['created'] );
		}

		$this->data = $this->original_data;
	}

	/**
	 * Get the ID of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return int
	 */
	public function get_id(): int {
		return (int) ( $this->data['waitlist_user_id'] ?? 0 );
	}

	/**
	 * Get the post ID of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return int|null
	 */
	public function get_post_id(): ?int {
		$id = $this->data['post_id'] ?? null;

		return $id && $id > 0 ? (int) $id : null;
	}

	/**
	 * Get the waitlist ID of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return int|null
	 */
	public function get_waitlist_id(): ?int {
		$id = $this->data['waitlist_id'] ?? null;

		return $id && $id > 0 ? (int) $id : null;
	}

	/**
	 * Get the waitlist of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return ?Waitlist
	 */
	public function get_waitlist(): ?Waitlist {
		$id = $this->get_waitlist_id();

		return $id ? tribe( Waitlists::class )->get( $id ) : null;
	}

	/**
	 * Get the status of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return int
	 */
	public function get_status(): int {
		return (int) ( $this->data['status'] ?? 0 );
	}

	/**
	 * Get the created date of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return ?int
	 */
	public function get_wp_user_id(): ?int {
		$id = $this->data['wp_user_id'] ?? null;

		return $id && $id > 0 ? (int) $id : null;
	}

	/**
	 * Get the full name of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return string
	 */
	public function get_fullname(): ?string {
		return $this->data['fullname'] ?? null;
	}

	/**
	 * Get the email of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return string
	 */
	public function get_email(): ?string {
		return $this->data['email'] ?? null;
	}

	/**
	 * Get the unsubscribe URL of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return string
	 */
	public function get_unsubscribe_url(): string {
		$meta = $this->get_meta() ?? [];

		if ( ! isset( $meta['unsubscribe_hash'] ) ) {
			return '';
		}

		$data = [
			'id'     => $this->get_id(),
			'hash'   => $meta['unsubscribe_hash'],
			'action' => Triggers::UNSUBSCRIBE_WAITLIST_ACTION,
		];

		return add_query_arg(
			$data,
			admin_url( '/admin-post.php' )
		);
	}

	/**
	 * Get the meta of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return ?array
	 */
	public function get_meta(): ?array {
		return empty( $this->data['meta'] ) || ! is_string( $this->data['meta'] ) ? null : json_decode( $this->data['meta'], true );
	}

	/**
	 * Get the raw meta of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return string|null
	 */
	protected function get_meta_raw(): ?string {
		return $this->data['meta'] ?? null;
	}

	/**
	 * Get the created timestamp of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return int
	 */
	public function get_created(): int {
		$created = $this->data['created'] ?? null;
		if ( ! $created ) {
			$this->data['created'] = time();
		}

		return (int) $this->data['created'];
	}

	/**
	 * Set the post ID of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @param int $post_id The post ID to set.
	 *
	 * @return Subscriber
	 * @throws InvalidArgumentException If the post ID is invalid.
	 */
	public function set_post_id( int $post_id ): self {
		if ( $post_id < 1 ) {
			throw new InvalidArgumentException( 'Invalid post ID for subscriber.' );
		}

		$this->data['post_id'] = $post_id;
		return $this;
	}

	/**
	 * Set the waitlist ID of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @param int $waitlist_id The waitlist ID to set.
	 *
	 * @return Subscriber
	 * @throws InvalidArgumentException If the waitlist ID is invalid.
	 */
	public function set_waitlist_id( int $waitlist_id ): self {
		if ( $waitlist_id < 1 ) {
			throw new InvalidArgumentException( 'Invalid waitlist ID for subscriber.' );
		}

		$this->data['waitlist_id'] = $waitlist_id;
		return $this;
	}

	/**
	 * Set the status of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @param int $status The status to set.
	 *
	 * @return Subscriber
	 * @throws InvalidArgumentException If the status is invalid.
	 */
	public function set_status( int $status ): self {
		if ( ! in_array( $status, [ self::STATUS_CREATED, self::STATUS_NOTIFIED ], true ) ) {
			throw new InvalidArgumentException( 'Invalid status for the subscriber.' );
		}

		$this->data['status'] = $status;
		return $this;
	}

	/**
	 * Set the WP user ID of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @param int $wp_user_id The WP user ID to set.
	 *
	 * @return Subscriber
	 * @throws InvalidArgumentException If the WP user ID is invalid.
	 */
	public function set_wp_user_id( int $wp_user_id ): self {
		if ( $wp_user_id < 1 ) {
			return $this;
		}

		$this->data['wp_user_id'] = $wp_user_id;

		$wp_user = get_user_by( 'ID', $wp_user_id );

		if ( $wp_user ) {
			$this->set_fullname( $wp_user->display_name );
			$this->set_email( $wp_user->user_email );
		}
		return $this;
	}

	/**
	 * Set the full name of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @param string $fullname The full name to set.
	 *
	 * @return Subscriber
	 * @throws InvalidArgumentException If the full name is invalid.
	 */
	public function set_fullname( string $fullname = '' ): self {
		if ( ! $fullname ) {
			return $this;
		}

		$this->data['fullname'] = $fullname;
		return $this;
	}

	/**
	 * Set the email of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @param string $email The email to set.
	 *
	 * @return Subscriber
	 * @throws InvalidArgumentException If the email is invalid.
	 */
	public function set_email( string $email = '' ): self {
		if ( ! $email ) {
			return $this;
		}

		if ( ! is_email( $email ) ) {
			throw new InvalidArgumentException( 'Invalid email for subscriber.' );
		}

		$this->data['email'] = $email;
		return $this;
	}

	/**
	 * Set the meta of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @param array $meta  The meta to set.
	 * @param bool  $merge Whether to merge the meta or not.
	 *
	 * @return Subscriber
	 */
	public function set_meta( array $meta, bool $merge = true ): self {
		$this->data['meta'] = wp_json_encode( array_merge( $merge ? ( $this->get_meta() ?? [] ) : [], $meta ) );
		return $this;
	}

	/**
	 * Set the created timestamp of the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @param int $created The created timestamp to set.
	 *
	 * @return Subscriber
	 * @throws InvalidArgumentException If the created timestamp is invalid.
	 */
	public function set_created( int $created ): self {
		if ( $created < 1 ) {
			$created = time();
		}

		$this->data['created'] = $created;
		return $this;
	}

	/**
	 * Convert a subscriber to an array.
	 *
	 * @since 6.2.0
	 *
	 * @param bool $raw Whether to return the raw data or not.
	 *
	 * @return array
	 */
	public function to_array( bool $raw = false ): array {
		return [
			'waitlist_user_id' => $this->get_id(),
			'post_id'          => $this->get_post_id(),
			'waitlist_id'      => $this->get_waitlist_id(),
			'status'           => $this->get_status(),
			'wp_user_id'       => $this->get_wp_user_id(),
			'fullname'         => $this->get_fullname(),
			'email'            => $this->get_email(),
			'meta'             => $raw ? $this->get_meta_raw() : $this->get_meta(),
			'created'          => $this->get_created(),
		];
	}

	/**
	 * Save the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @param bool $force Whether to force the save or not.
	 *
	 * @return int The ID of the subscriber.
	 */
	public function save( bool $force = false ): int {
		$method = $this->get_id() ? 'update' : 'insert';

		$this->{$method}( $force );

		$this->original_data = $this->data;

		return $this->get_id();
	}

	/**
	 * Notify the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return bool Whether the subscriber was notified or not.
	 */
	public function notify(): bool {
		try {
			/**
			 * Fires before a waitlist subscriber is notified.
			 *
			 * @since 6.2.0
			 *
			 * @param Subscriber $subscriber The subscriber.
			 */
			do_action( 'tec_tickets_plus_waitlist_subscriber_pre_notify', $this );

			$meta = $this->get_meta() ?? [];

			$meta['notified'] = ( $meta['notified'] ?? 0 ) + 1;
			if ( ! isset( $meta['notified_ts'] ) || ! is_array( $meta['notified_ts'] ) ) {
				$meta['notified_ts'] = [];
			}
			$meta['notified_ts'][] = time();

			/**
			 * Filter the meta before a waitlist subscriber is notified.
			 *
			 * @since 6.2.0
			 *
			 * @param array      $meta       The meta.
			 * @param Subscriber $subscriber The subscriber.
			 *
			 * @return array
			 */
			$meta = (array) apply_filters( 'tec_tickets_plus_waitlist_subscriber_notify_meta', $meta, $this );

			$this->set_meta( $meta, false )->set_status( self::STATUS_NOTIFIED )->save();

			/**
			 * Fires after a waitlist subscriber is notified.
			 *
			 * @since 6.2.0
			 *
			 * @param Subscriber $subscriber The subscriber.
			 */
			do_action( 'tec_tickets_plus_waitlist_subscriber_post_notify', $this );

			return true;
		} catch ( Exception $e ) {
			do_action(
				'tribe_log',
				'error',
				'Subscriber failed to be notified!',
				[
					'method'     => __CLASS__,
					'error'      => $e->getMessage(),
					'subscriber' => $this->to_array(),
				]
			);

			return false;
		}
	}

	/**
	 * Insert the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 * @throws RuntimeException If the insert fails.
	 */
	protected function insert(): void {
		$this->validate();

		$this->set_unsubscribe_hash();

		$to_save = $this->to_array( true );

		unset( $to_save['waitlist_user_id'], $to_save['waitlist_id'] );

		$to_save['created'] = DateTime::createFromFormat( 'U', $to_save['created'] )->format( 'Y-m-d H:i:s' );

		$result = DB::insert(
			Subscribers_Table::table_name( true ),
			$to_save,
			[
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
			],
		);

		if ( ! $result ) {
			throw new RuntimeException( 'Failed to insert subscriber.' );
		}

		$this->data['waitlist_user_id'] = (int) DB::last_insert_id();

		$to_save = $this->to_array();

		unset( $to_save['post_id'], $to_save['status'], $to_save['meta'] );

		$to_save['created'] = DateTime::createFromFormat( 'U', $to_save['created'] )->format( 'Y-m-d H:i:s' );

		$result = DB::insert(
			Pending_Subscribers_Table::table_name( true ),
			$to_save,
			[
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
			],
		);

		if ( ! $result ) {
			throw new RuntimeException( 'Failed to insert subscriber.' );
		}
	}

	/**
	 * Update the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @param bool $force Whether to force the update or not.
	 *
	 * @return void
	 * @throws RuntimeException If the update fails.
	 */
	protected function update( $force = false ): void {
		$this->validate( $force );

		if ( ! $this->get_id() ) {
			throw new RuntimeException( 'Cannot update a subscriber without an ID.' );
		}

		$id = $this->get_id();

		$pre_created  = $this->original_data['created'] ?? null;
		$post_created = $this->data['created'] ?? null;

		$clone_data          = $this->data;
		$clone_original_data = $this->original_data;

		unset( $clone_data['created'], $clone_original_data['created'] );

		$to_be_updated = $force ? $this->data : array_merge( array_diff_assoc( $clone_data, $clone_original_data ), [ 'created' => $post_created ] );

		unset( $to_be_updated['waitlist_user_id'], $to_be_updated['waitlist_id'] );

		if ( ! empty( $to_be_updated ) ) {
			$to_be_updated['created'] = $post_created instanceof DateTimeInterface ? $post_created->format( 'Y-m-d H:i:s' ) : $pre_created->format( 'Y-m-d H:i:s' );

			$result = DB::update(
				Subscribers_Table::table_name( true ),
				$to_be_updated,
				[ Subscribers_Table::uid_column() => $id ],
				$this->placeholders_from_array( array_keys( $to_be_updated ) ),
				[ '%d' ]
			);

			if ( ! $result ) {
				throw new RuntimeException( 'Failed to update the subscriber.' );
			}
		}

		if ( self::STATUS_NOTIFIED === $this->get_status() ) {
			DB::delete(
				Pending_Subscribers_Table::table_name( true ),
				[ Pending_Subscribers_Table::uid_column() => $id ],
				[ '%d' ]
			);
			return;
		}

		$to_be_updated = $force ? $this->data : array_diff_assoc( $this->data, $this->original_data );

		unset( $to_be_updated['waitlist_user_id'], $to_be_updated['post_id'], $to_be_updated['status'], $to_be_updated['meta'] );

		if ( empty( $to_be_updated ) ) {
			return;
		}

		if ( ! empty( $to_be_updated['created'] ) ) {
			$to_be_updated['created'] = DateTime::createFromFormat( 'U', $to_be_updated['created'] )->format( 'Y-m-d H:i:s' );
		}

		$result = DB::update(
			Pending_Subscribers_Table::table_name( true ),
			$to_be_updated,
			[ Pending_Subscribers_Table::uid_column() => $id ],
			$this->placeholders_from_array( array_keys( $to_be_updated ) ),
			[ '%d' ]
		);

		if ( ! $result ) {
			throw new RuntimeException( 'Failed to update the subscriber.' );
		}
	}

	/**
	 * Delete the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function delete(): void {
		$id = $this->get_id();

		if ( ! $id ) {
			return;
		}

		DB::delete(
			Subscribers_Table::table_name( true ),
			[ Subscribers_Table::uid_column() => $id ],
			[ '%d' ]
		);

		DB::delete(
			Pending_Subscribers_Table::table_name( true ),
			[ Pending_Subscribers_Table::uid_column() => $id ],
			[ '%d' ]
		);
	}

	/**
	 * Validate the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @param bool $force Whether this is a force update validation.
	 *
	 * @return void
	 * @throws InvalidArgumentException If the subscriber is invalid.
	 */
	protected function validate( $force = false ) {
		if ( ! $this->get_post_id() ) {
			throw new InvalidArgumentException( 'Post ID is required to create a subscriber.' );
		}

		if ( ! $force && ! isset( $this->data['waitlist_id'] ) ) {
			throw new InvalidArgumentException( 'Subscriber needs a waitlist id to be created' );
		}

		if ( ! in_array( $this->get_status(), [ self::STATUS_CREATED, self::STATUS_NOTIFIED ], true ) ) {
			throw new InvalidArgumentException( 'Invalid status for subscriber.' );
		}

		if ( ! empty( $this->data['meta'] ) && ! is_string( $this->data['meta'] ) ) {
			$this->set_meta( $this->data['meta'], false );
		}

		if ( ! $force && empty( $this->data['fullname'] ) ) {
			throw new InvalidArgumentException( 'Subscriber must have a full name to be created.' );
		}

		if ( ! $force && empty( $this->data['email'] ) ) {
			throw new InvalidArgumentException( 'Subscriber must have an email to be created.' );
		}
	}

	/**
	 * Get the placeholders for an array of keys.
	 *
	 * @since 6.2.0
	 *
	 * @param array $keys The keys to get the placeholders for.
	 *
	 * @return array
	 */
	protected function placeholders_from_array( array $keys ): array {
		$placeholders = [];

		foreach ( $keys as $key ) {
			$placeholders[] = static::PLACEHOLDERS[ $key ];
		}

		return $placeholders;
	}

	/**
	 * Check if a key is valid for the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @param string $key The key to check.
	 *
	 * @return bool
	 */
	protected function is_valid_key( string $key ): bool {
		return isset( self::VALID_KEYS[ $key ] );
	}

	/**
	 * Set the unsubscribe hash for the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	protected function set_unsubscribe_hash(): void {
		$meta = $this->get_meta() ?? [];

		$meta['unsubscribe_hash'] = md5( microtime() . wp_json_encode( $this->to_array() ) );
		$this->set_meta( $meta, false );
	}
}
