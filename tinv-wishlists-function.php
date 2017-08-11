<?php
/**
 * Basic function for plugin
 *
 * @since             1.0.0
 * @package           TInvWishlist
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( function_exists( 'spl_autoload_register' ) ) {

	/**
	 * Autoloader class. If no function spl_autoload_register, then all the files will be required
	 *
	 * @param string $_class Required class name.
	 *
	 * @return boolean
	 */
	function autoload_tinv_wishlist( $_class ) {
		$preffix = 'TInvWL';
		$ext     = '.php';
		$class   = explode( '_', $_class );
		$object  = array_shift( $class );
		if ( $preffix !== $object ) {
			return false;
		}
		if ( empty( $class ) ) {
			$class = array( $preffix );
		}
		$basicclass = $class;
		array_unshift( $class, 'includes' );
		$classs = array(
			TINVWL_PATH . strtolower( implode( DIRECTORY_SEPARATOR, $basicclass ) ),
			TINVWL_PATH . strtolower( implode( DIRECTORY_SEPARATOR, $class ) ),
		);
		foreach ( $classs as $class ) {
			foreach ( array( '.class', '.helper' ) as $suffix ) {
				$filename = $class . $suffix . $ext;
				if ( file_exists( $filename ) ) {
					require_once $filename;

					return true;
				}
			}
		}

		return false;
	}

	spl_autoload_register( 'autoload_tinv_wishlist' );
}

if ( ! function_exists( 'tinv_array_merge' ) ) {

	/**
	 * Function to merge arrays with replacement options
	 *
	 * @param array $array1 Array.
	 * @param array $_ Array.
	 *
	 * @return array
	 */
	function tinv_array_merge( $array1, $_ = null ) {
		if ( ! is_array( $array1 ) ) {
			return $array1;
		}
		$args = func_get_args();
		array_shift( $args );
		foreach ( $args as $array2 ) {
			if ( is_array( $array2 ) ) {
				foreach ( $array2 as $key => $value ) {
					$array1[ $key ] = $value;
				}
			}
		}

		return $array1;
	}
}

if ( ! function_exists( 'tinv_get_option_defaults' ) ) {

	/**
	 * Extract default options from settings class
	 *
	 * @param string $category Name category settings.
	 *
	 * @return array
	 */
	function tinv_get_option_defaults( $category ) {
		$dir = TINVWL_PATH . 'admin/settings/';
		if ( ! file_exists( $dir ) || ! is_dir( $dir ) ) {
			return array();
		}
		$files = scandir( $dir );
		foreach ( $files as $key => $value ) {
			if ( preg_match( '/\.class\.php$/i', $value ) ) {
				$files[ $key ] = preg_replace( '/\.class\.php$/i', '', $value );
			} else {
				unset( $files[ $key ] );
			}
		}
		$defaults = array();
		foreach ( $files as $file ) {
			$class         = 'TInvWL_Admin_Settings_' . ucfirst( $file );
			$class         = new $class( '', '' );
			$class_methods = get_class_methods( $class );
			foreach ( $class_methods as $method ) {
				if ( preg_match( '/_data$/i', $method ) ) {
					$settings = $class->get_defaults( $class->$method() );
					$defaults = tinv_array_merge( $defaults, $settings );
				}
			}
		}
		if ( 'all' === $category ) {
			return $defaults;
		}
		if ( array_key_exists( $category, $defaults ) ) {
			return $defaults[ $category ];
		}

		return array();
	}
}

if ( ! function_exists( 'tinv_get_option' ) ) {

	/**
	 * Extract options from database or default array settings.
	 *
	 * @param string $category Name category settings.
	 * @param string $option Name paremetr. If is empty string, then function return array category settings.
	 *
	 * @return mixed
	 */
	function tinv_get_option( $category, $option = '' ) {
		$prefix = TINVWL_PREFIX . '-';
		$values = get_option( $prefix . $category, array() );
		if ( empty( $values ) ) {
			$values = tinv_get_option_defaults( $category );
		}
		if ( empty( $option ) ) {
			return $values;
		} else {
			if ( array_key_exists( $option, $values ) ) {
				return $values[ $option ];
			} else {
				$values = tinv_get_option_defaults( $category );
				if ( array_key_exists( $option, (array) $values ) ) {
					return $values[ $option ];
				}
			}
		}

		return null;
	}
}

if ( ! function_exists( 'tinv_style' ) ) {

	/**
	 * Get style for custom style
	 *
	 * @param string $selector Selector style.
	 * @param string $element Attribute name.
	 *
	 * @return string
	 */
	function tinv_style( $selector = '', $element = '' ) {
		$key    = md5( $selector . '||' . $element );
		$values = get_option( TINVWL_PREFIX . '-style_options', array() );
		if ( empty( $values ) ) {
			return '';
		}
		if ( array_key_exists( $key, $values ) ) {
			return $values[ $key ];
		}

		return '';
	}
}

if ( ! function_exists( 'tinv_update_option' ) ) {

	/**
	 * Update options in database.
	 *
	 * @param string $category Name category settings.
	 * @param string $option Name paremetr. If is empty string, then function update array category settings.
	 * @param mixed $value Value option.
	 *
	 * @return boolean
	 */
	function tinv_update_option( $category, $option = '', $value = false ) {
		$prefix = TINVWL_PREFIX . '-';
		if ( empty( $option ) ) {
			if ( is_array( $value ) ) {
				update_option( $prefix . $category, $value );

				return true;
			}
		} else {
			$values = get_option( $prefix . $category, array() );

			$values[ $option ] = $value;
			update_option( $prefix . $category, $values );

			return true;
		}

		return false;
	}
}

if ( ! function_exists( 'tinv_wishlist_template' ) ) {

	/**
	 * The function overwrites the method output templates woocommerce
	 *
	 * @param string $template_name Name file template.
	 * @param array $args Array variable in template.
	 * @param string $template_path Customization path.
	 */
	function tinv_wishlist_template( $template_name, $args = array(), $template_path = '' ) {
		if ( function_exists( 'wc_get_template' ) ) {
			wc_get_template( $template_name, $args, $template_path );
		} else {
			woocommerce_get_template( $template_name, $args, $template_path );
		}
	}
}

if ( ! function_exists( 'tinv_wishlist_locate_template' ) ) {

	/**
	 * Overwrites path for email and other template
	 *
	 * @param string $template_name Requered Template file.
	 * @param string $template_path Template path.
	 * @param string $default_path Template default path.
	 *
	 * @return string
	 */
	function tinv_wishlist_locate_template( $template_name, $template_path = '', $default_path = '' ) {
		if ( ! $template_path ) {
			$template_path = WC()->template_path();
		}

		if ( ! $default_path ) {
			$default_path = TINVWL_PATH . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
		}

		// Look within passed path within the theme - this is priority.
		$template = locate_template( array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
		) );

		// Get default template.
		if ( ! $template && file_exists( $default_path . $template_name ) ) {
			$template = $default_path . $template_name;
		}

		// Return what we found.
		return apply_filters( 'tinvwl_locate_template', $template, $template_name, $template_path );
	}
}

if ( ! function_exists( 'tinv_wishlist_template_html' ) ) {

	/**
	 * The function overwrites the method return templates woocommerce
	 *
	 * @param string $template_name Name file template.
	 * @param array $args Array variable in template.
	 * @param string $template_path Customization path.
	 *
	 * @return string
	 */
	function tinv_wishlist_template_html( $template_name, $args = array(), $template_path = '' ) {
		ob_start();
		tinv_wishlist_template( $template_name, $args, $template_path );

		return ob_get_clean();
	}
}

if ( ! function_exists( 'tinv_wishlist_get_item_data' ) ) {

	/**
	 * Extract meta attributes for product
	 *
	 * @param object $product Object selected product.
	 * @param boolean $flat Return text or template.
	 *
	 * @return string
	 */
	function tinv_wishlist_get_item_data( $product, $flat = false ) {
		$item_data      = array();
		$variation_id   = version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->variation_id : ( $product->is_type( 'variation' ) ? $product->get_id() : 0 );
		$variation_data = version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->variation_data : ( $product->is_type( 'variation' ) ? wc_get_product_variation_attributes( $product->get_id() ) : array() );
		if ( ! empty( $variation_id ) && is_array( $variation_data ) ) {
			foreach ( $variation_data as $name => $value ) {
				if ( '' === $value ) {
					continue;
				}

				$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );

				// If this is a term slug, get the term's nice name.
				if ( taxonomy_exists( $taxonomy ) ) {
					$term = get_term_by( 'slug', $value, $taxonomy ); // @codingStandardsIgnoreLine WordPress.VIP.RestrictedFunctions.get_term_by
					if ( ! is_wp_error( $term ) && $term && $term->name ) {
						$value = $term->name;
					}
					$label = wc_attribute_label( $taxonomy );

					// If this is a custom option slug, get the options name.
				} else {
					$value              = apply_filters( 'woocommerce_variation_option_name', $value );
					$product_attributes = $product->get_attributes();

					if ( isset( $product_attributes[ str_replace( 'attribute_', '', $name ) ] ) ) {
						$label = wc_attribute_label( ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $product_attributes[ str_replace( 'attribute_', '', $name ) ]['name'] : str_replace( 'attribute_', '', $name ) ) );
					} else {
						$label = $name;
					}
				}
				$item_data[] = array(
					'key'   => $label,
					'value' => $value,
				);
			}
		}

		// Filter item data to allow 3rd parties to add more to the array.
		$item_data = apply_filters( 'tinv_wishlist_get_item_data', $item_data, $product );

		// Format item data ready to display.
		foreach ( $item_data as $key => $data ) {
			// Set hidden to true to not display meta on cart.
			if ( ! empty( $data['hidden'] ) ) {
				unset( $item_data[ $key ] );
				continue;
			}
			$item_data[ $key ]['key']     = ! empty( $data['key'] ) ? $data['key'] : $data['name'];
			$item_data[ $key ]['display'] = ! empty( $data['display'] ) ? $data['display'] : $data['value'];
		}

		// Output flat or in list format.
		if ( 0 < count( $item_data ) ) {
			ob_start();
			if ( $flat ) {
				foreach ( $item_data as $data ) {
					echo esc_html( $data['key'] ) . ': ' . wp_kses_post( $data['display'] ) . '<br>';
				}
			} else {
				tinv_wishlist_template( 'ti-wishlist-item-data.php', array( 'item_data' => $item_data ) );
			}

			return ob_get_clean();
		}

		return '';
	}
}

if ( ! function_exists( 'tinv_wishlist_get' ) ) {

	/**
	 * Return Wishlist by id or share key
	 *
	 * @param mixed $id Integer wishlist ID, or Share Key wishlist.
	 * @param boolean $toend Switches to the extract the default or guest wishlist.
	 *
	 * @return array
	 */
	function tinv_wishlist_get( $id = '', $toend = true ) {
		$wl       = new TInvWL_Wishlist();
		$wishlist = null;
		if ( empty( $id ) ) {
			$id = get_query_var( 'tinvwlID', null );
		}

		if ( ! empty( $id ) ) {
			if ( is_integer( $id ) ) {
				$wishlist = $wl->get_by_id( $id );
			}
			if ( empty( $wishlist ) ) {
				$wishlist = $wl->get_by_share_key( $id );
			}

			if ( is_array( $wishlist ) ) {
				$wishlist['is_owner'] = false;
				if ( is_user_logged_in() ) {
					$wishlist['is_owner'] = get_current_user_id() == $wishlist['author']; // WPCS: loose comparison ok.
				}
			}
		} elseif ( is_user_logged_in() && $toend ) {
			$wishlist = $wl->add_user_default();

			$wishlist['is_owner'] = true;
		} elseif ( $toend ) {
			$wishlist = array(
				'ID'        => 0,
				'author'    => 0,
				'date'      => '',
				'title'     => tinv_get_option( 'general', 'default_title' ),
				'status'    => 'private',
				'type'      => 'default',
				'share_key' => '',
				'is_owner'  => true,
			);
		}

		return $wishlist;
	}
}

if ( ! function_exists( 'tinv_url_wishlist_default' ) ) {

	/**
	 * Return the default wishlist url
	 *
	 * @return string
	 */
	function tinv_url_wishlist_default() {
		$page = apply_filters( 'wpml_object_id', tinv_get_option( 'page', 'wishlist' ), 'page', true ); // @codingStandardsIgnoreLine WordPress.Variables.GlobalVariables.OverrideProhibited
		if ( empty( $page ) ) {
			return '';
		}
		$link = get_permalink( $page );

		return $link;
	}
}

if ( ! function_exists( 'tinv_url_wishlist' ) ) {

	/**
	 * Return the wishlist url by id or share key
	 *
	 * @param mixed $id Integer wishlist ID, or Share Key wishlist.
	 * @param integer $paged Page.
	 * @param boolean $full Return full url or shroted url for logged in user.
	 *
	 * @return string
	 */
	function tinv_url_wishlist( $id = '', $paged = 1, $full = true ) {
		$paged = absint( $paged );
		$paged = 1 < $paged ? $paged : 1;
		$page  = tinv_get_option( 'page', 'wishlist' ); // @codingStandardsIgnoreLine WordPress.Variables.GlobalVariables.OverrideProhibited
		if ( empty( $page ) ) {
			return '';
		}

		$link     = tinv_url_wishlist_default();
		$wishlist = tinv_wishlist_get( $id, false );
		if ( empty( $wishlist ) ) {
			if ( 1 < $paged ) {
				$link = add_query_arg( 'paged', $paged, $link );
			}

			return $link;
		}
		$full_link = $link;

		$share_key = $wishlist['share_key'];

		if ( get_option( 'permalink_structure' ) ) {
			$suffix = '';
			if ( preg_match( '/([^\?]+)\?*?(.*)/i', $full_link, $_full_link ) ) {
				$full_link = $_full_link[1];
				$suffix    = $_full_link[2];
			}
			if ( ! preg_match( '/\/$/', $full_link ) ) {
				$full_link .= '/';
			}
			$full_link .= $share_key . '/' . $suffix;
		} else {
			$full_link .= add_query_arg( 'tinvwlID', $share_key, $full_link );
		}

		if ( $full ) {
			$link = $full_link;
		} else {
			if ( ! ( 'default' === $wishlist['type'] && $wishlist['is_owner'] ) ) {
				$link = $full_link;
			}
		}

		if ( 1 < $paged ) {
			$link = add_query_arg( 'paged', $paged, $link );
		}

		return $link;
	}
}

if ( ! function_exists( 'tinv_wishlist_status' ) ) {

	/**
	 * Check status free or premium plugin and disable free
	 *
	 * @global string $status
	 * @global string $page
	 * @global string $s
	 *
	 * @param string $transient Plugin transient name.
	 *
	 * @return string
	 */
	function tinv_wishlist_status( $transient ) {
		if ( TINVWL_LOAD_FREE === $transient ) {
			TInvWL_PluginExtend::deactivate_self( TINVWL_LOAD_FREE );

			return 'plugins.php';
		}
		if ( TINVWL_LOAD_PREMIUM === $transient ) {
			if ( is_plugin_active( TINVWL_LOAD_FREE ) ) {
				TInvWL_PluginExtend::deactivate_self( TINVWL_LOAD_FREE );
				if ( ! function_exists( 'wp_create_nonce' ) ) {
					return 'plugins.php';
				}

				global $status, $page, $s;
				$redirect = 'plugins.php?';
				$redirect .= http_build_query( array(
					'action'        => 'activate',
					'plugin'        => $transient,
					'plugin_status' => $status,
					'paged'         => $page,
					's'             => $s,
				) );
				$redirect = esc_url_raw( add_query_arg( '_wpnonce', wp_create_nonce( 'activate-plugin_' . $transient ), $redirect ) );

				return $redirect;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'tinvwl_body_classes' ) ) {

	/**
	 * Add custom class
	 *
	 * @param array $classes Current classes.
	 *
	 * @return array
	 */
	function tinvwl_body_classes( $classes ) {
		if ( tinv_get_option( 'style', 'customstyle' ) ) {
			$classes[] = 'tinvwl-theme-style';
		} else {
			$classes[] = 'tinvwl-custom-style';
		}

		return $classes;
	}

	add_filter( 'body_class', 'tinvwl_body_classes' );
}

if ( ! function_exists( 'tinvwl_rocket_reject_uri' ) ) {

	/**
	 * Disable cache for WP Rocket
	 *
	 * @param array $uri URI.
	 *
	 * @return array
	 */
	function tinvwl_rocket_reject_uri( $uri = array() ) {
		$id        = tinv_get_option( 'page', 'wishlist' );
		$pages     = array( $id );
		$languages = apply_filters( 'wpml_active_languages', array(), array(
			'skip_missing' => 0,
			'orderby'      => 'code'
		) );
		if ( ! empty( $languages ) ) {
			foreach ( $languages as $l ) {
				$pages[] = apply_filters( 'wpml_object_id', $id, 'page', true, $l['language_code'] );
			}
			$pages = array_unique( $pages );
		}
		$pages = array_filter( $pages );
		if ( ! empty( $pages ) ) {
			foreach ( $pages as $page ) {
				$uri[] = str_replace( get_site_url(), '', get_permalink( $page ) );
			}
		}

		return $uri;
	}

	add_filter( 'rocket_cache_reject_uri', 'tinvwl_rocket_reject_uri' );
}

if ( ! function_exists( 'tinvwl_rocket_reject_cookies' ) ) {

	/**
	 * Disable cache for WP Rocket
	 *
	 * @param array $cookies Cookies.
	 *
	 * @return array
	 */
	function tinvwl_rocket_reject_cookies( $cookies = array() ) {
		$cookies[] = 'tinv_wishlist';

		return $cookies;
	}

	add_filter( 'rocket_cache_reject_cookies', 'tinvwl_rocket_reject_cookies' );
}

if ( ! function_exists( 'tinvwl_supercache_reject_uri' ) ) {

	/**
	 * Disable cache for WP Super Cache
	 *
	 * @global array $cache_rejected_uri
	 *
	 * @param string $buffer Intercepted the output of the page.
	 *
	 * @return string
	 */
	function tinvwl_supercache_reject_uri( $buffer ) {
		global $cache_rejected_uri;
		if ( ! is_null( $cache_rejected_uri ) && is_array( $cache_rejected_uri ) ) {
			$id        = tinv_get_option( 'page', 'wishlist' );
			$pages     = array( $id );
			$languages = apply_filters( 'wpml_active_languages', array(), array(
				'skip_missing' => 0,
				'orderby'      => 'code'
			) );
			if ( ! empty( $languages ) ) {
				foreach ( $languages as $l ) {
					$pages[] = apply_filters( 'wpml_object_id', $id, 'page', true, $l['language_code'] );
				}
				$pages = array_unique( $pages );
			}
			$pages = array_filter( $pages );
			if ( ! empty( $pages ) ) {
				foreach ( $pages as $page ) {
					$cache_rejected_uri[] = str_replace( get_site_url(), '', get_permalink( $page ) );
				}
			}
		}

		return $buffer;
	}

	add_filter( 'wp_cache_ob_callback_filter', 'tinvwl_supercache_reject_uri' );
}

if ( ! function_exists( 'tinvwl_w3total_reject_uri' ) ) {

	/**
	 * Disable cache for W3 Total Cache
	 */
	function tinvwl_w3total_reject_uri() {
		if ( ! function_exists( 'w3tc_pgcache_flush' ) || ! function_exists( 'w3_instance' ) ) {
			return;
		}
		$id        = tinv_get_option( 'page', 'wishlist' );
		$pages     = array( $id );
		$languages = apply_filters( 'wpml_active_languages', array(), array(
			'skip_missing' => 0,
			'orderby'      => 'code'
		) );
		if ( ! empty( $languages ) ) {
			foreach ( $languages as $l ) {
				$pages[] = apply_filters( 'wpml_object_id', $id, 'page', true, $l['language_code'] );
			}
			$pages = array_unique( $pages );
		}
		$pages = array_filter( $pages );
		if ( ! empty( $pages ) ) {
			foreach ( $pages as $i => $page ) {
				$pages[ $i ] = preg_replace( "/^\//", '', str_replace( get_site_url(), '', get_permalink( $page ) ) ); // @codingStandardsIgnoreLine Squiz.Strings.DoubleQuoteUsage.NotRequired
			}
		}
		$pages = array_unique( $pages );
		$pages = array_filter( $pages );

		$config   = w3_instance( 'W3_Config' );
		if ( ! empty( $pages ) ) {

			$sections = array( 'dbcache.reject.uri', 'pgcache.reject.uri' );
			foreach ( $sections as $section ) {
				$settings = array_map( 'trim', $config->get_array( $section ) );
				$changed  = false;
				foreach ( $pages as $page ) {
					if ( ! in_array( $page, $settings ) ) { // @codingStandardsIgnoreLine WordPress.PHP.StrictInArray.MissingTrueStrict
						$settings[] = $page;
						$changed    = true;
					}
				}
				if ( $changed ) {
					$config->set( $section, $settings );
					$config->save();
				}
			}
		}

		$settings = array_map( 'trim', $config->get_array( 'pgcache.reject.cookie' ) );
		if ( ! in_array( 'tinv_wishlist', $settings ) ) { // @codingStandardsIgnoreLine WordPress.PHP.StrictInArray.MissingTrueStrict
			$settings[] = 'tinv_wishlist';
			$config->set( 'pgcache.reject.cookie', $settings );
			$config->save();
		}
	}

	add_action( 'admin_init', 'tinvwl_w3total_reject_uri' );
}

if ( ! function_exists( 'gf_productaddon_support' ) ) {

	/**
	 * Add supports WooCommerce - Gravity Forms Product Add-Ons
	 */
	function gf_productaddon_support() {
		if ( ! class_exists( 'woocommerce_gravityforms' ) ) {
			return false;
		}
		if ( ! function_exists( 'gf_productaddon_text_button' ) ) {

			/**
			 * Change text for button add to cart
			 *
			 * @param string $text_add_to_card Text "Add to cart".
			 * @param array $wl_product Wishlist product.
			 * @param object $product WooCommerce Product.
			 *
			 * @return string
			 */
			function gf_productaddon_text_button( $text_add_to_card, $wl_product, $product ) {
				$gravity_form_data = get_post_meta( ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->id : ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() ) ), '_gravity_form_data', true );

				return ( $gravity_form_data ) ? __( 'Select options', 'woocommerce' ) : $text_add_to_card;
			}

			add_filter( 'tinvwl_wishlist_item_add_to_card', 'gf_productaddon_text_button', 10, 3 );
		}

		if ( ! function_exists( 'gf_productaddon_run_action_button' ) ) {

			/**
			 * Check for make redirect to url
			 *
			 * @param boolean $need Need redirect or not.
			 * @param object $product WooCommerce Product.
			 *
			 * @return boolean
			 */
			function gf_productaddon_run_action_button( $need, $product ) {
				$gravity_form_data = get_post_meta( ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->id : ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() ) ), '_gravity_form_data', true );

				return ( $gravity_form_data ) ? true : $need;
			}

			add_filter( 'tinvwl_product_add_to_cart_need_redirect', 'gf_productaddon_run_action_button', 10, 2 );
		}

		if ( ! function_exists( 'gf_productaddon_action_button' ) ) {

			/**
			 * Redirect url
			 *
			 * @param string $url Redirect URL.
			 * @param object $product WooCommerce Product.
			 *
			 * @return string
			 */
			function gf_productaddon_action_button( $url, $product ) {
				$gravity_form_data = get_post_meta( ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->id : ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() ) ), '_gravity_form_data', true );

				return ( $gravity_form_data ) ? $product->get_permalink() : $url;
			}

			add_filter( 'tinvwl_product_add_to_cart_redirect_url', 'gf_productaddon_action_button', 10, 2 );
		}
	}

	add_action( 'init', 'gf_productaddon_support' );
}

if ( ! function_exists( 'tinvwl_shortcode_addtowishlist' ) ) {
	/**
	 * Shortcode Add To Wishlist
	 *
	 * @param array $atts Array parameter from shortcode.
	 *
	 * @return string
	 */
	function tinvwl_shortcode_addtowishlist( $atts = array() ) {
		$class = TInvWL_Public_AddToWishlist::instance();

		return $class->shortcode( $atts );
	}

	add_shortcode( 'ti_wishlists_addtowishlist', 'tinvwl_shortcode_addtowishlist' );
}

if ( ! function_exists( 'tinvwl_shortcode_view' ) ) {
	/**
	 * Shortcode view Wishlist
	 *
	 * @param array $atts Array parameter from shortcode.
	 *
	 * @return string
	 */
	function tinvwl_shortcode_view( $atts = array() ) {
		$class = TInvWL_Public_Wishlist_View::instance();

		return $class->shortcode( $atts );
	}

	add_shortcode( 'ti_wishlistsview', 'tinvwl_shortcode_view' );
}

if ( ! function_exists( 'tinvwl_view_addto_html' ) ) {
	/**
	 * Show button Add to Wishlsit
	 */
	function tinvwl_view_addto_html() {
		$class = TInvWL_Public_AddToWishlist::instance();
		$class->htmloutput();
	}
}

if ( ! function_exists( 'tinvwl_view_addto_htmlout' ) ) {
	/**
	 * Show button Add to Wishlsit, if product is not purchasable
	 */
	function tinvwl_view_addto_htmlout() {
		$class = TInvWL_Public_AddToWishlist::instance();
		$class->htmloutput_out();
	}
}

if ( ! function_exists( 'tinvwl_clean_url' ) ) {
	/**
	 * Clear esc_url to original
	 *
	 * @param string $good_protocol_url Cleared URL.
	 * @param string $original_url Original URL.
	 *
	 * @return string
	 */
	function tinvwl_clean_url( $good_protocol_url, $original_url ) {
		return $original_url;
	}
}

if ( ! function_exists( 'tinvwl_add_to_cart_need_redirect' ) ) {
	/**
	 * Check if the product is third-party, or has another link added to the cart then redirect to the product page.
	 *
	 * @param boolean $redirect Default value to redirect.
	 * @param \WC_Product $product Product data.
	 * @param string $redirect_url Current url for redirect.
	 *
	 * @return boolean
	 */
	function tinvwl_add_to_cart_need_redirect( $redirect, $product, $redirect_url ) {
		if ( $redirect ) {
			return true;
		}
		if ( 'external' === ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->product_type : $product->get_type() ) ) {
			return true;
		}

		$need_url_data = array_filter( array_merge( array(
			'variation_id' => ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->variation_id : ( $product->is_type( 'variation' ) ? $product->get_id() : 0 ) ),
			'add-to-cart'  => ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $product->id : ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() ) ),
		), array_map( 'urlencode', ( version_compare( WC_VERSION, '3.0.0', '<' ) ? ( is_array( $product->variation_data ) ? $product->variation_data : array() ) : array() ) ) ) );
		$need_url      = apply_filters( 'woocommerce_product_add_to_cart_url', remove_query_arg( 'added-to-cart', add_query_arg( $need_url_data ) ), $product );
		$need_url_full = apply_filters( 'woocommerce_product_add_to_cart_url', remove_query_arg( 'added-to-cart', add_query_arg( $need_url_data, $product->get_permalink() ) ), $product );
		add_filter( 'clean_url', 'tinvwl_clean_url', 10, 2 );
		$_redirect_url = apply_filters( 'tinvwl_product_add_to_cart_redirect_url', $product->add_to_cart_url(), $product );
		remove_filter( 'clean_url', 'tinvwl_clean_url', 10 );
		if ( $_redirect_url !== $need_url && $_redirect_url !== $need_url_full ) {
			return true;
		}

		return $redirect;
	}

	add_filter( 'tinvwl_product_add_to_cart_need_redirect', 'tinvwl_add_to_cart_need_redirect', 10, 3 );
}

if ( ! function_exists( 'tinvwl_wpml_product_get' ) ) {

	/**
	 * Change product data if product need translate
	 *
	 * @param array $product Wishlistl product.
	 *
	 * @return array
	 */
	function tinvwl_wpml_product_get( $product ) {
		if ( array_key_exists( 'data', $product ) ) {
			$_product_id   = $product_id = $product['product_id'];
			$_variation_id = $variation_id = $product['variation_id'];
			$_product_id   = apply_filters( 'wpml_object_id', $_product_id, 'product', true );
			if ( ! empty( $_variation_id ) ) {
				$_variation_id = apply_filters( 'wpml_object_id', $_variation_id, 'product', true );
			}
			if ( $_product_id !== $product_id || $_variation_id !== $variation_id ) {
				$product['data'] = wc_get_product( $variation_id ? $_variation_id : $_product_id );
			}
		}

		return $product;
	}

	add_filter( 'tinvwl_wishlist_product_get', 'tinvwl_wpml_product_get' );
}

if ( ! function_exists( 'tinvwl_wpml_filter_link' ) ) {

	function tinvwl_wpml_filter_link( $full_link, $l ) {
		$share_key = get_query_var( 'tinvwlID', null );
		if ( ! empty( $share_key ) ) {
			if ( get_option( 'permalink_structure' ) ) {
				$suffix = '';
				if ( preg_match( '/([^\?]+)\?*?(.*)/i', $full_link, $_full_link ) ) {
					$full_link = $_full_link[1];
					$suffix    = $_full_link[2];
				}
				if ( ! preg_match( '/\/$/', $full_link ) ) {
					$full_link .= '/';
				}
				$full_link .= $share_key . '/' . $suffix;
			} else {
				$full_link .= add_query_arg( 'tinvwlID', $share_key, $full_link );
			}
		}

		return $full_link;
	}

	add_filter( 'WPML_filter_link', 'tinvwl_wpml_filter_link', 10, 2 );
}

if ( ! function_exists( 'tinvwl_gift_card_add' ) ) {

	/**
	 * Support WooCommerce - Gift Cards
	 * Redirect to page gift card, if requires that customers enter a name and email when purchasing a Gift Card.
	 *
	 * @param boolean $redirect Default value to redirect.
	 * @param \WC_Product $product Product data.
	 *
	 * @return boolean
	 */
	function tinvwl_gift_card_add( $redirect, $product ) {
		if ( $redirect ) {
			return true;
		}
		$is_required_field_giftcard = get_option( 'woocommerce_enable_giftcard_info_requirements' );

		if ( 'yes' == $is_required_field_giftcard ) { // WPCS: loose comparison ok.
			$is_giftcard = get_post_meta( $product->get_id(), '_giftcard', true );
			if ( 'yes' == $is_giftcard ) { // WPCS: loose comparison ok.
				return true;
			}
		}

		return $redirect;
	}

	add_filter( 'tinvwl_product_add_to_cart_need_redirect', 'tinvwl_gift_card_add', 20, 2 );
}

if ( ! function_exists( 'tinvwl_gift_card_add_url' ) ) {

	/**
	 * Support WooCommerce - Gift Cards
	 * Redirect to page gift card, if requires that customers enter a name and email when purchasing a Gift Card.
	 *
	 * @param string $redirect_url Default value to redirect.
	 * @param \WC_Product $product Product data.
	 *
	 * @return boolean
	 */
	function tinvwl_gift_card_add_url( $redirect_url, $product ) {
		$is_required_field_giftcard = get_option( 'woocommerce_enable_giftcard_info_requirements' );

		if ( 'yes' == $is_required_field_giftcard ) { // WPCS: loose comparison ok.
			$is_giftcard = get_post_meta( $product->get_id(), '_giftcard', true );
			if ( 'yes' == $is_giftcard ) { // WPCS: loose comparison ok.
				return $product->get_permalink();
			}
		}

		return $redirect_url;
	}

	add_filter( 'tinvwl_product_add_to_cart_redirect_url', 'tinvwl_gift_card_add_url', 20, 2 );
}

// Create a helper function for easy SDK access.
if ( ! function_exists( 'tinvwl_fs' ) ) {

	function tinvwl_fs() {
		global $tinvwl_fs;

		if ( ! isset( $tinvwl_fs ) ) {
			// Include Freemius SDK.
			require_once dirname( __FILE__ ) . '/freemius/start.php';

			$tinvwl_fs = fs_dynamic_init( array(
				'id'                  => '839',
				'slug'                => 'ti-woocommerce-wishlist',
				'type'                => 'plugin',
				'public_key'          => 'pk_1944d351ab27040c8f65c72d1e7e7',
				'is_premium'          => false,
				'has_premium_version' => false,
				'has_addons'          => false,
				'has_paid_plans'      => false,
				'menu'                => array(
					'slug'       => 'tinvwl',
					'first-path' => 'admin.php?page=tinvwl' . ( get_option( TINVWL_PREFIX . '_wizard' ) ? '' : '-wizard' ),
					'account'    => false,
					'support'    => false,
				),
			) );
		}

		return $tinvwl_fs;
	}

	// Init Freemius.
	tinvwl_fs();
}

function tinvwl_fs_custom_connect_message_on_update(
	$message, $user_first_name, $plugin_title, $user_login, $site_link,
	$freemius_link
) {
	return sprintf(
		__fs( 'hey-x' ) . '<br>' .
		__( 'Please help us improve %2$s! If you opt-in, some data about your usage of %2$s will be sent to %5$s. If you skip this, that\'s okay! %2$s will still work just fine.', 'ti-woocommerce-wishlist' ), $user_first_name, '<b>' . $plugin_title . '</b>', '<b>' . $user_login . '</b>', $site_link, $freemius_link
	);
}

tinvwl_fs()->add_filter( 'connect_message_on_update', 'tinvwl_fs_custom_connect_message_on_update', 10, 6 );

function tinvwl_fs_custom_connect_message(
	$message, $user_first_name, $plugin_title, $user_login, $site_link,
	$freemius_link
) {
	return sprintf(
		__fs( 'hey-x' ) . '<br>' .
		__( 'Allow %6$s to collect some usage data with %5$s to make the plugin even more awesome. If you skip this, that\'s okay! %2$s will still work just fine.', 'ti-woocommerce-wishlist' ), $user_first_name, '<b>' . __( 'WooCommerce Wishlist Plugin', 'ti-woocommerce-wishlist' ) . '</b>', '<b>' . $user_login . '</b>', $site_link, $freemius_link, '<b>' . __( 'TemplateInvaders', 'ti-woocommerce-wishlist' ) . '</b>'
	);
}

tinvwl_fs()->add_filter( 'connect_message', 'tinvwl_fs_custom_connect_message', 10, 6 );

tinvwl_fs()->add_action( 'after_uninstall', 'uninstall_tinv_wishlist' );

if ( ! function_exists( 'tinvwl_rating_notice_template' ) ) {

	function tinvwl_rating_notice_template( $output, $key, $message ) {

		TInvWL_View::view( 'notice-rating', array(
			'name'    => 'rating',
			'message' => $message,
			'key'     => $key,
		) );

		return '';
	}

	add_filter( 'tinv_notice_rating', 'tinvwl_rating_notice_template', 10, 3 );
}

if ( ! function_exists( 'tinvwl_rating_notice_hide' ) ) {

	function tinvwl_rating_notice_hide() {
		$data = filter_input( INPUT_GET, 'ti-redirect' );
		if ( $data ) {
			wp_redirect( 'https://wordpress.org/support/plugin/ti-woocommerce-wishlist/reviews/#new-post' );
		}
	}

	add_action( 'tinv_notice_hide_rating', 'tinvwl_rating_notice_hide' );
}

if ( ! function_exists( 'tinvwl_rating_notice_trigger_30' ) ) {

	function tinvwl_rating_notice_trigger_30() {
		$tw       = new TInvWL_Wishlist();
		$wishlist = $tw->get( array(
			'count'    => 1,
			'order_by' => 'date',
		) );
		$wishlist = array_shift( $wishlist );
		if ( empty( $wishlist ) ) {
			return false;
		}
		$date = $wishlist['date'];
		$date = mysql2date( 'G', $date );
		$date = floor( ( time() - $date ) / DAY_IN_SECONDS );
		$step = floor( $date / 30 );
		if ( 0 >= $step ) {
			return false;
		}

		return $step;
	}
}

if ( ! function_exists( 'tinvwl_set_utm' ) ) {

	/**
	 * Set UTM sources.
	 *
	 */
	function tinvwl_set_utm() {

		//Set a source.
		$source = get_option( TINVWL_PREFIX . '_utm_source' );
		if ( ! $source ) {
			$source = defined( 'TINVWL_PARTNER' ) ? TINVWL_PARTNER : 'wordpress_org';
			update_option( TINVWL_PREFIX . '_utm_source', $source );
		}

		define( 'TINVWL_UTM_SOURCE', $source );

		//Set a medium.
		$medium = get_option( TINVWL_PREFIX . '_utm_medium' );
		if ( ! $medium ) {
			$medium = defined( 'TINVWL_PARTNER' ) ? 'integration' : 'organic';
			update_option( TINVWL_PREFIX . '_utm_medium', $medium );
		}

		define( 'TINVWL_UTM_MEDIUM', $medium );

		//Set a campaign.
		$campaign = get_option( TINVWL_PREFIX . '_utm_campaign' );
		if ( ! $campaign ) {
			$campaign = defined( 'TINVWL_PARTNER' ) ? ( defined( 'TINVWL_CAMPAIGN' ) ? TINVWL_CAMPAIGN : TINVWL_PARTNER ) : 'organic';
			update_option( TINVWL_PREFIX . '_utm_campaign', $campaign );
		}

		define( 'TINVWL_UTM_CAMPAIGN', $campaign );
	}
}
