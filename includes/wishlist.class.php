<?php
/**
 * Wishlists function class
 *
 * @since             1.0.0
 * @package           TInvWishlist\Wishlists
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Wishlists function class
 */
class TInvWL_Wishlist {

	/**
	 * Table name
	 *
	 * @var string
	 */
	private $table;
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	private $_n;
	/**
	 * User id
	 *
	 * @var integer
	 */
	public $user;
	/**
	 * Default name wishlist
	 *
	 * @var string
	 */
	private $default_name;
	/**
	 * Default privacy wishlist
	 *
	 * @var string
	 */
	private $default_privacy;

	/**
	 * Constructor
	 *
	 * @global wpdb $wpdb
	 *
	 * @param string $plugin_name Plugin name.
	 */
	function __construct( $plugin_name = TINVWL_PREFIX ) {
		global $wpdb;

		$this->_n              = $plugin_name;
		$this->table           = sprintf( '%s%s_%s', $wpdb->prefix, $this->_n, 'lists' );
		$this->default_name    = tinv_get_option( 'general', 'default_title' );
		$this->default_privacy = 'share';
		$this->user            = get_current_user_id();
	}

	/**
	 * Generate unique share key
	 *
	 * @global wpdb $wpdb
	 * @return string
	 */
	function unique_share_key() {
		global $wpdb;

		$sharekeys  = $wpdb->get_results( "SELECT `share_key` FROM `{$this->table}`", ARRAY_A ); // WPCS: db call ok; no-cache ok; unprepared SQL ok.
		$share_keys = array();
		foreach ( $sharekeys as $sharekey ) {
			$share_keys[] = $sharekey['share_key'];
		}

		do {
			$new_key = strtoupper( substr( md5( date( 'r' ) . mt_rand( 0, 3000 ) ), 0, 6 ) );
		} while ( array_search( $new_key, $share_keys ) ); // @codingStandardsIgnoreLine WordPress.PHP.StrictInArray.MissingTrueStrict

		return $new_key;
	}

	/**
	 * Get/Create default wishlist
	 *
	 * @param integer $user_id Can put 0.
	 *
	 * @return boolean
	 */
	function add_user_default( $user_id = 0 ) {
		if ( $wl = $this->get_by_user_default( $user_id ) ) {
			return array_shift( $wl );
		}

		$id = $this->add( '', 'default', $this->default_privacy, $user_id );
		if ( $id ) {
			$wl = $this->get( array( 'ID' => $id ) );

			return array_shift( $wl );
		}

		return false;
	}

	/**
	 * Add wishlist
	 *
	 * @global wpdb $wpdb
	 *
	 * @param mixed $data wishlist name or object.
	 * @param string $type List or default.
	 * @param string $status Public, Share, Private.
	 * @param integer $user_id Can put 0.
	 *
	 * @return boolean
	 */
	function add( $data, $type = 'list', $status = 'share', $user_id = 0 ) {
		$user_id = absint( $user_id );
		if ( empty( $user_id ) ) {
			$user_id = $this->user;
		}

		if ( empty( $user_id ) ) {
			return false;
		}

		$default = array(
			'author'    => $user_id,
			'date'      => date( 'Y-m-d H:i:s' ),
			'share_key' => $this->unique_share_key(),
			'title'     => $this->default_name,
			'type'      => 'list',
		);

		if ( ! is_array( $data ) ) {
			$data = array(
				'title'  => $data,
				'status' => $status,
				'type'   => $type,
			);
		}
		$data           = tinv_array_merge( $default, $data );
		$data           = apply_filters( 'tinvwl_wishlist_add', $data );
		$data['status'] = $this->default_privacy;

		global $wpdb;
		if ( $wpdb->insert( $this->table, $data ) ) { // @codingStandardsIgnoreLine WordPress.VIP.DirectDatabaseQuery.DirectQuery
			$id = $wpdb->insert_id;

			return $id;
		}

		return false;
	}

	/**
	 * Get default wishlist for user id
	 *
	 * @param integer $user_id Can put 0.
	 *
	 * @return array
	 */
	function get_by_user_default( $user_id = 0 ) {
		$user_id = absint( $user_id );
		if ( empty( $user_id ) ) {
			$user_id = $this->user;
		}

		$data = array(
			'author' => $user_id,
			'type'   => 'default',
		);
		if ( empty( $this->user ) || ( $data['author'] != $this->user ) ) { // WPCS: loose comparison ok.
			$data['status'] = 'public';
		}

		return $this->get( $data );
	}

	/**
	 * Get wishlist for user id
	 *
	 * @param integer $user_id Can put 0.
	 *
	 * @return array
	 */
	function get_by_user( $user_id = 0 ) {
		$user_id = absint( $user_id );
		if ( empty( $user_id ) ) {
			$user_id = $this->user;
		}
		$this->add_user_default( $user_id );
		$data = array(
			'author' => $user_id,
		);
		if ( empty( $this->user ) || ( $data['author'] != $this->user ) ) { // WPCS: loose comparison ok.
			$data['status'] = 'public';
		}

		return $this->get( $data );
	}

	/**
	 * Get wishlist by id
	 *
	 * @param integer $id id database wishlist.
	 *
	 * @return array
	 */
	function get_by_id( $id ) {
		$id = absint( $id );
		if ( empty( $id ) ) {
			return null;
		}

		$wishlists = $this->get( array( 'ID' => $id ) );
		$wishlist  = array_shift( $wishlists );

		return $wishlist;
	}

	/**
	 * Get wishlist by share key
	 *
	 * @param string $share_key Share key.
	 *
	 * @return array
	 */
	function get_by_share_key( $share_key ) {
		if ( ! preg_match( '/[a-f0-9]{6}/i', $share_key ) ) {
			return null;
		}
		$wishlists = $this->get( array( 'share_key' => $share_key ) );
		$wishlist  = array_shift( $wishlists );

		return $wishlist;
	}

	/**
	 * Get wishlist
	 *
	 * @global wpdb $wpdb
	 *
	 * @param array $data Requset.
	 *
	 * @return array
	 */
	function get( $data = array() ) {
		$default = array(
			'count'    => 10,
			'field'    => null,
			'offset'   => 0,
			'order'    => 'ASC',
			'order_by' => 'title',
			'sql'      => '',
		);

		foreach ( $default as $_k => $_v ) {
			if ( array_key_exists( $_k, $data ) ) {
				$default[ $_k ] = $data[ $_k ];
				unset( $data[ $_k ] );
			}
		}

		if ( is_array( $default['field'] ) ) {
			$default['field'] = '`' . implode( '`,`', $default['field'] ) . '`';
		} elseif ( is_string( $default['field'] ) ) {
			$default['field'] = array( 'ID', 'type', $default['field'] );
			$default['field'] = '`' . implode( '`,`', $default['field'] ) . '`';
		} else {
			$default['field'] = '*';
		}
		$sql = "SELECT {$default[ 'field' ]} FROM `{$this->table}`";

		$where = '1';
		if ( ! empty( $data ) && is_array( $data ) ) {
			foreach ( $data as $f => $v ) {
				$s = is_array( $v ) ? ' IN ' : '=';
				if ( is_array( $v ) ) {
					$v = "'" . implode( "','", $v ) . "'";
					$v = "($v)";
				} else {
					$v = "'$v'";
				}
				$data[ $f ] = sprintf( '`%s`%s%s', $f, $s, $v );
			}
			$where = ' WHERE ' . implode( ' AND ', $data );
			$sql   .= $where;
		}
		$sql .= sprintf( ' ORDER BY `%s` %s LIMIT %d,%d;', $default['order_by'], $default['order'], $default['offset'], $default['count'] );

		if ( ! empty( $default['sql'] ) ) {
			$replacer    = $replace = array();
			$replace[0]  = '{table}';
			$replacer[0] = $this->table;
			$replace[1]  = '{where}';
			$replacer[1] = $where;

			foreach ( $default as $key => $value ) {
				$i = count( $replace );

				$replace[ $i ]  = '{' . $key . '}';
				$replacer[ $i ] = $value;
			}

			$sql = str_replace( $replace, $replacer, $default['sql'] );
		}

		global $wpdb;
		$wls = $wpdb->get_results( $sql, ARRAY_A ); // WPCS: db call ok; no-cache ok; unprepared SQL ok.

		if ( empty( $wls ) ) {
			return array();
		}

		foreach ( $wls as $k => $wl ) {
			$wl['ID'] = absint( $wl['ID'] );
			if ( array_key_exists( 'author', $wl ) ) {
				$wl['author'] = absint( $wl['author'] );
			}
			if ( 'default' === $wl['type'] && empty( $wl['title'] ) ) {
				$wl['title'] = $this->default_name;
			}

			$wls[ $k ] = apply_filters( 'tinvwl_wishlist_get', $wl );
		}

		return $wls;
	}

	/**
	 * Update wishlist
	 *
	 * @global wpdb $wpdb
	 *
	 * @param integer $id id database wishlist.
	 * @param mixed $data wishlist name or object.
	 * @param string $type List or default.
	 * @param string $status Public, Share, Private.
	 *
	 * @return boolean
	 */
	function update( $id, $data, $type = 'list', $status = 'public' ) {
		if ( ! is_array( $data ) ) {
			$data = array(
				'title'  => $data,
				'status' => $status,
				'type'   => $type,
			);
		}
		$data = filter_var_array( $data, apply_filters( 'tinvwl_wishlist_fields_update', array(
			'title'  => FILTER_SANITIZE_STRING,
			'status' => FILTER_SANITIZE_STRING,
			'type'   => FILTER_SANITIZE_STRING,
		) ) );
		$data = array_filter( $data );
		$data = apply_filters( 'tinvwl_wishlist_update', $data, $id );
		if ( ! array_key_exists( 'title', $data ) ) {
			$wishlist = $this->get_by_id( $id );
			if ( 'default' === $wishlist['type'] ) {
				$data['title'] = '';
			}
		}
		global $wpdb;

		return false !== $wpdb->update( $this->table, $data, array( 'ID' => $id ) ); // WPCS: db call ok; no-cache ok; unprepared SQL ok.
	}

	/**
	 * Remove wishlist
	 *
	 * @global wpdb $wpdb
	 *
	 * @param integer $id id database wishlist.
	 *
	 * @return boolean
	 */
	public function remove( $id ) {
		$id = absint( $id );
		if ( empty( $id ) ) {
			return false;
		}
		global $wpdb;
		$result = $wpdb->delete( $this->table, array( 'ID' => $id ) ); // WPCS: db call ok; no-cache ok; unprepared SQL ok.
		if ( false !== $result ) {
			do_action( 'tinvwl_wishlist_removed', $id );
			$wlp = new TInvWL_Product();
			$wlp->remove_product_from_wl( $id );

			return true;
		}

		return false;
	}
}
