<?php

/**
 * Add to wishlists shortcode and hooks
 *
 * @since             1.0.0
 * @package           TInvWishlist\Public
 */
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Add to wishlists shortcode and hooks
 */
class TInvWL_Public_AddToWishlist {

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	private $_n;

	/**
	 * Global product
	 *
	 * @var object
	 */
	private $product;

	/**
	 * This wishlists and product
	 *
	 * @var array
	 */
	private $wishlist;

	/**
	 * This class
	 *
	 * @var \TInvWL_Public_AddToWishlist
	 */
	protected static $_instance = null;

	/**
	 * Get this class object
	 *
	 * @param string $plugin_name Plugin name.
	 * @return \TInvWL_Public_AddToWishlist
	 */
	public static function instance( $plugin_name = TINVWL_PREFIX ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $plugin_name );
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 *
	 * @param string $plugin_name Plugin name.
	 */
	function __construct( $plugin_name ) {
		$this->_n = $plugin_name;
		$this->define_hooks();
	}

	/**
	 * Defined shortcode and hooks
	 */
	function define_hooks() {
		switch ( tinv_get_option( 'add_to_wishlist', 'position' ) ) {
			case 'before':
				add_action( 'tinvwl_before_add_to_cart_button', 'tinvwl_view_addto_html' );
				add_action( 'tinvwl_single_product_summary', 'tinvwl_view_addto_htmlout' );
				add_action( 'woocommerce_before_add_to_cart_button', 'tinvwl_view_addto_html', 0 );
				add_action( 'woocommerce_single_product_summary', 'tinvwl_view_addto_htmlout', 29 );
				break;
			case 'after':
				add_action( 'tinvwl_after_add_to_cart_button', 'tinvwl_view_addto_html' );
				add_action( 'tinvwl_single_product_summary', 'tinvwl_view_addto_htmlout' );
				add_action( 'woocommerce_after_add_to_cart_button', 'tinvwl_view_addto_html', 0 );
				add_action( 'woocommerce_single_product_summary', 'tinvwl_view_addto_htmlout', 31 );
				break;
		}

		add_action( 'wp_loaded', array( $this, 'add_to_wishlist' ), 0 );
	}

	/**
	 * Action add product to wishlist
	 *
	 * @return boolean
	 */
	function add_to_wishlist() {
		if ( is_null( filter_input( INPUT_POST, 'tinv_wishlist_id' ) ) ) {
			return false;
		} else {
			remove_action( 'init', 'woocommerce_add_to_cart_action' );
			remove_action( 'wp_loaded', 'WC_Form_Handler::add_to_cart_action', 20 );
		}
		ob_start();
		$post = filter_input_array( INPUT_POST, array(
			'tinv_wishlist_id'	 => FILTER_VALIDATE_INT,
			'tinv_wishlist_name' => FILTER_SANITIZE_STRING,
			'product_id'		 => FILTER_VALIDATE_INT,
			'product_variation'	 => FILTER_VALIDATE_INT,
			'product_type'		 => FILTER_SANITIZE_STRING,
		) );

		$wlp		 = null;
		$wishlist	 = null;
		$data		 = array( 'msg' => array() );
		if ( is_user_logged_in() ) {
			$wl			 = new TInvWL_Wishlist( $this->_n );
			$wishlist	 = $wl->add_user_default();
			$wishlist	 = apply_filters( 'tinvwl_addtowishlist_wishlist', $wishlist );
			if ( empty( $wishlist ) ) {
				$data[ 'status' ] = false;
				ob_clean();
				wp_send_json( $data );
			}
			$wlp = new TInvWL_Product( $wishlist, $this->_n );
		} else {
			$wlp = TInvWL_Product_Local::instance( $this->_n );
		}

		$status = true;
		if ( empty( $post[ 'product_id' ] ) ) {
			$status = false;
		} else {
			$post[ 'product_type' ]	 = apply_filters( $this->_n . '_addtowishlist_modify_type', $post[ 'product_type' ], $post );
			$post					 = apply_filters( 'tinvwl_addtowishlist_prepare', $post );
			switch ( $post[ 'product_type' ] ) {
				case 'group':
				case 'grouped' :
					$product = $wlp->product_data( $post[ 'product_id' ] );
					if ( empty( $product ) ) {
						$status = false;
					} else {
						$variations	 = $product->get_children();

						foreach ( $variations as $variation_id ) {
							$quantity		 = 1;
							$allowed_adding	 = count( $wlp->get_wishlist( array(
								'product_id'	 => $post[ 'product_id' ],
								'variation_id'	 => $variation_id,
								'external'		 => false,
							) ) );
							if ( $allowed_adding ) {
								$data[ 'msg' ][] = tinv_get_option( 'add_to_wishlist', 'text_already_in' );
								$status			 = false;
							} elseif ( $wlp->add_product( apply_filters( 'tinvwl_addtowishlist_add', array(
								'product_id'	 => $post[ 'product_id' ],
								'variation_id'	 => $variation_id,
								'quantity'		 => $quantity,
							) ) ) ) {
								$data[ 'msg' ][] = tinv_get_option( 'add_to_wishlist', 'text_added_to' );
								do_action( $this->_n . '_changed_wishlist', 1, $wishlist, $post[ 'product_id' ], $variation_id, $quantity );
							} else {
								$status = false;
							}
						}
					}
					break;
				case 'variable' :
				case 'variation' :

					if ( $post[ 'product_variation' ] ) {
						$variation_id = $post[ 'product_variation' ];
					} else {
						$variation_id = absint( filter_input( INPUT_POST, 'formvariation_id', FILTER_VALIDATE_INT ) );
					}
					$quantity = 1;
					if ( empty( $variation_id ) ) {
						$status			 = false;
						$data[ 'msg' ][] = __( 'Product option is not selected!', 'ti-woocommerce-wishlist' );
					} else {
						$allowed_adding = count( $wlp->get_wishlist( array(
							'product_id'	 => $post[ 'product_id' ],
							'variation_id'	 => $variation_id,
							'external'		 => false,
						) ) );
						if ( $allowed_adding ) {
							$data[ 'msg' ][] = tinv_get_option( 'add_to_wishlist', 'text_already_in' );
							$status			 = false;
						} elseif ( $wlp->add_product( apply_filters( 'tinvwl_addtowishlist_add', array(
							'product_id'	 => $post[ 'product_id' ],
							'quantity'		 => $quantity,
							'variation_id'	 => $variation_id,
						) ) ) ) {
							$data[ 'msg' ][] = tinv_get_option( 'add_to_wishlist', 'text_added_to' );
							do_action( $this->_n . '_changed_wishlist', 1, $wishlist, $post[ 'product_id' ], $variation_id, $quantity );
						} else {
							$status = false;
						}
					}
					break;
				case 'simple' :
				default:
					$quantity		 = 1;
					$allowed_adding	 = count( $wlp->get_wishlist( array(
						'product_id' => $post[ 'product_id' ],
						'external'	 => false,
					) ) );
					if ( $allowed_adding ) {
						$data[ 'msg' ][] = tinv_get_option( 'add_to_wishlist', 'text_already_in' );
						$status			 = false;
					} elseif ( $wlp->add_product( apply_filters( 'tinvwl_addtowishlist_add', array(
						'product_id' => $post[ 'product_id' ],
						'quantity'	 => $quantity,
					) ) ) ) {
						$data[ 'msg' ][] = tinv_get_option( 'add_to_wishlist', 'text_added_to' );
						do_action( $this->_n . '_changed_wishlist', 1, $wishlist, $post[ 'product_id' ], 0, $quantity );
					} else {
						$status = false;
					}
					break;
			}
		}
		$data[ 'status' ]		 = $status;
		$data[ 'wishlist_url' ]	 = tinv_url_wishlist_default();
		if ( ! empty( $wishlist ) ) {
			$data[ 'wishlist_url' ] = tinv_url_wishlist( $wishlist[ 'ID' ] );
		}

		if ( $status && tinv_get_option( 'add_to_wishlist', 'redirect' ) && tinv_get_option( 'page', 'wishlist' ) ) {
			$data[ 'redirect' ] = $data[ 'wishlist_url' ];
		}

		$product			 = wc_get_product( $post[ 'product_id' ] );
		$data[ 'wishlists' ] = wp_json_encode( $this->user_wishlist( $product, $wlp ) );

		$data[ 'icon' ]	 = $data[ 'status' ] ? 'icon_big_heart_check' : 'icon_big_times';
		$data[ 'msg' ]	 = array_unique( $data[ 'msg' ] );
		$data[ 'msg' ]	 = implode( '<br>', $data[ 'msg' ] );
		if ( ! empty( $data[ 'msg' ] ) ) {
			$data[ 'msg' ] = tinv_wishlist_template_html( 'ti-addedtowishlist-dialogbox.php', $data );
		}
		$data = apply_filters( $this->_n . '_addtowishlist_return_ajax', $data );
		ob_clean();
		wp_send_json( $data );
	}

	/**
	 * Check exists product in user wishlists
	 *
	 * @param object $product Product object.
	 * @param object $wlp Product class, used for local products.
	 * @return array
	 */
	function user_wishlist( $product, $wlp = null ) {
		$available_variations = $product->get_children();

		$this->wishlist	 = $wishlists		 = array();

		if ( is_user_logged_in() ) {
			$wl			 = new TInvWL_Wishlist( $this->_n );
			$wishlists[] = $wl->add_user_default();

			foreach ( $wishlists as $key => $wishlist ) {
				$wlp		 = new TInvWL_Product( $wishlist, $this->_n );
				$wishlist	 = array(
					'ID'	 => $wishlist[ 'ID' ],
					'title'	 => $wishlist[ 'title' ],
					'url'	 => tinv_url_wishlist( $wishlist[ 'ID' ] ),
				);
				if ( ! empty( $available_variations ) && is_array( $available_variations ) ) {
					$wishlist[ 'in' ] = array();
					foreach ( $available_variations as $variation ) {
						if ( $wlp->check_product( ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->id : ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() ) ), $variation ) ) {
							$wishlist[ 'in' ][] = $variation;
						}
					}
					if ( empty( $wishlist[ 'in' ] ) ) {
						$wishlist[ 'in' ] = false;
					}
				} else {
					$wishlist[ 'in' ] = (bool) $wlp->check_product( ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->id : ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() ) ) );
				}
				$wishlists[ $key ] = $wishlist;
			}
		} else {
			if ( empty( $wlp ) ) {
				$wlp = TInvWL_Product_Local::instance( $this->_n );
			}
			$wishlist = array(
				'ID'	 => 0,
				'title'	 => tinv_get_option( 'general', 'default_title' ),
			);
			if ( ! empty( $available_variations ) && is_array( $available_variations ) ) {
				$wishlist[ 'in' ] = array();
				foreach ( $available_variations as $variation ) {
					if ( $wlp->check_product( ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->id : ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() ) ), $variation ) ) {
						$wishlist[ 'in' ][] = $variation;
					}
				}
				if ( empty( $wishlist[ 'in' ] ) ) {
					$wishlist[ 'in' ] = false;
				}
			} else {
				$wishlist[ 'in' ] = (bool) $wlp->check_product( ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->id : ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() ) ) );
			}
			$wishlists[ 0 ] = $wishlist;
		}
		$wishlists		 = apply_filters( 'tinvwl_addtowishlist_preparewishlists', $wishlists, $product );
		$this->wishlist	 = $wishlists;
		return $wishlists;
	}

	/**
	 * Create add button if simple product out stock
	 *
	 * @global object $product
	 */
	function htmloutput_out() {
		global $product;

		if ( $product ) {
			if ( apply_filters( 'tinvwl_allow_addtowishlist_single_product_summary', ( $product->is_purchasable() && 'simple' === ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->product_type : $product->get_type() ) && ! $product->is_in_stock() ) ) ) {
				$this->htmloutput();
			}
		}
	}

	/**
	 * Output page
	 *
	 * @global object $product
	 * @param array   $attr Array parameter for shortcode.
	 * @param boolean $is_shortcode Shortcode or action.
	 * @return boolean
	 */
	function htmloutput( $attr = array(), $is_shortcode = false ) {
		global $product;

		$this->product	 = $product;
		$position		 = tinv_get_option( 'add_to_wishlist', 'position' );
		if ( $is_shortcode ) {
			$position		 = 'shortcode';
			$product_id		 = absint( $attr[ 'product_id' ] );
			$variation_id	 = absint( $attr[ 'variation_id' ] );
			if ( 'product_variation' == get_post_type( $product_id ) ) { // WPCS: loose comparison ok.
				$variation_id	 = $product_id;
				$product_id		 = wp_get_post_parent_id( $variation_id );
			}

			$product_data = wc_get_product( $variation_id ? $variation_id : $product_id );

			if ( $product_data && 'trash' !== ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->post->post_status : ($product_post = get_post( $product->get_id() )) ? $product_post->post_status : '' ) ) {
				$this->product = $product_data;
			} else {
				return '';
			}
		}

		$wishlists = $this->user_wishlist( $this->product );

		add_action( 'tinv_wishlist_addtowishlist_button', array( $this, 'button' ) );

		$data = array(
			'class_postion'			 => sprintf( 'tinvwl-%s-add-to-cart', $position ),
			'product'				 => $this->product,
			'TInvWishlist'			 => $wishlists,
			'button_icon'			 => tinv_get_option( 'add_to_wishlist', 'icon' ),
			'add_to_wishlist'		 => tinv_get_option( 'add_to_wishlist', 'text' ),
			'browse_in_wishlist'	 => tinv_get_option( 'add_to_wishlist', 'text_browse' ),
			'product_in_wishlist'	 => tinv_get_option( 'add_to_wishlist', 'text_already_in' ),
			'product_to_wishlist'	 => tinv_get_option( 'add_to_wishlist', 'text_added_to' ),
		);
		tinv_wishlist_template( 'ti-addtowishlist.php', $data );
	}

	/**
	 * Create button
	 *
	 * @param boolean $echo Return or output.
	 */
	function button( $echo = true ) {
		$content	 = apply_filters( 'tinvwl_wishlist_button_before', '' );
		$text		 = tinv_get_option( 'add_to_wishlist', 'text' );
		$icon		 = tinv_get_option( 'add_to_wishlist', 'icon' );
		$icon_class	 = '';
		if ( empty( $text ) ) {
			$icon_class = ' no-txt';
		} else {
			$content .= '<div class="tinv-wishlist-clear"></div>';
		}
		if ( ! empty( $icon ) ) {
			$icon_upload = tinv_get_option( 'add_to_wishlist', 'icon_upload' );
			if ( 'custom' === $icon && ! empty( $icon_upload ) ) {
				$text = sprintf( '<img src="%s" /> %s', esc_url( $icon_upload ), $text );
			}
			$icon = 'tinvwl-icon-' . $icon;
			if ( 'custom' !== $icon ) {
				$icon .= ' icon-' . tinv_get_option( 'add_to_wishlist', 'icon_style' );
			}
		}
		$icon .= $icon_class;
		foreach ( $this->wishlist as $value ) {
			if ( $value[ 'in' ] ) {
				$icon .= ' tinvwl-product-in-list';
				break;
			}
		}
		if ( 'button' == tinv_get_option( 'add_to_wishlist', 'type' ) ) { // WPCS: loose comparison ok.
			$content .= sprintf( '<a class="tinvwl_add_to_wishlist_button %s button" tinv-wl-list="%s" tinv-wl-product="%s" tinv-wl-productvariation="%s" tinv-wl-producttype="%s">%s</a>', $icon, htmlspecialchars( wp_json_encode( $this->wishlist ) ), ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $this->product->id : ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() ) ), ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $this->product->variation_id : ( $this->product->is_type( 'variation' ) ? $this->product->get_id() : 0 ) ), ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $this->product->product_type : $this->product->get_type() ), $text );
		} else {
			$content .= sprintf( '<a class="tinvwl_add_to_wishlist_button %s" tinv-wl-list="%s" tinv-wl-product="%s" tinv-wl-productvariation="%s" tinv-wl-producttype="%s" href="javascript:void(0);">%s</a>', $icon, htmlspecialchars( wp_json_encode( $this->wishlist ) ), ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $this->product->id : ( $this->product->is_type( 'variation' ) ? $this->product->get_parent_id() : $this->product->get_id() ) ), ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $this->product->variation_id : ( $this->product->is_type( 'variation' ) ? $this->product->get_id() : 0 ) ), ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $this->product->product_type : $this->product->get_type() ), $text );
		}
		$content .= apply_filters( 'tinvwl_wishlist_button_after', '' );

		if ( ! empty( $text ) ) {
			$content .= '<div class="tinv-wishlist-clear"></div>';
		}

		echo apply_filters( 'tinvwl_wishlist_button', $content ); // WPCS: xss ok.
	}

	/**
	 * Shortcode basic function
	 *
	 * @global object $product
	 * @param array $atts Array parameter from shortcode.
	 * @return string
	 */
	function shortcode( $atts = array() ) {
		global $product;

		$default = array(
			'product_id'	 => 0,
			'variation_id'	 => 0,
		);
		if ( $product ) {
			$default = array(
				'product_id'	 => ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->id : ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() ) ),
				'variation_id'	 => ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->variation_id : ( $product->is_type( 'variation' ) ? $product->get_id() : 0 ) ),
			);
		}
		$atts = shortcode_atts( $default, $atts );

		ob_start();
		$this->htmloutput( $atts, true );
		return ob_get_clean();
	}

}
