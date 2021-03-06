<?php
/*
Plugin Name: Site Ground Simple Store
Description: This plugin will initialize a very simple store to your website. Site Ground Task No. 2
Version: 1.0
Author: GeroNikolov
Author URI: https://geronikolov.com
License: GPLv2
*/

class SG_SS {
	function __construct() {
		// Init DB
		add_action( "init", array( $this, "sg_ss_init_db" ) );

		// Register Products Type
		add_action( "init", array( $this, "sg_ss_register_products_cpt" ) );

		// Create Price, Quantity, Availability fields
		add_action( "add_meta_boxes", array( $this, "sg_ss_register_product_info_metaboxes" ), 10, 2 );

		// Add Admin Styles + Scripts
		add_action( "admin_enqueue_scripts", array( $this, "sg_ss_register_admin_js" ), "1.0.0", "true" );
        add_action( "admin_enqueue_scripts", array( $this, "sg_ss_register_admin_css" ) );

		// Add Fron Styles
		add_action( "wp_enqueue_scripts", array( $this, "sg_ss_register_front_css" ) );

		// Save Product
		add_action( "save_post", array( $this, "sg_ss_save" ) );

		// Store Page
		add_action( "admin_menu", array( $this, "sg_ss_store_pages" ) );

		// Get Store Products
		add_action( "wp_ajax_nopriv_sg_ss_get_products", array( $this, "sg_ss_get_products" ) );
		add_action( "wp_ajax_sg_ss_get_products", array( $this, "sg_ss_get_products" ) );

		// Save Mass Promo
		add_action( "wp_ajax_sg_ss_save_mass_promo", array( $this, "sg_ss_save_mass_promo" ) );

		// Custom Single Product Template
		if ( in_array( "woocommerce/woocommerce.php", apply_filters( "active_plugins", get_option( "active_plugins" ) ) ) ) {
			add_filter( "woocommerce_locate_template", array( $this, "sg_ss_custom_single_product_woo" ), 10, 3 );
		} else { add_filter( "single_template", array( $this, "sg_ss_custom_single_product" ), 10, 1 ); }

		// Delete Post Action
		add_action( "delete_post", array( $this, "sg_ss_delete_product_meta" ), 10 );
	}

	function __destruct() {}

	function sg_ss_init_db() {
		global $wpdb;
		$mass_promo_table = $wpdb->prefix ."mass_promo";
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$mass_promo_table'" ) != $mass_promo_table ) {
			$charset_collate = $wpdb->get_charset_collate();
			$sql_ = "
			CREATE TABLE $mass_promo_table (
				id INT NOT NULL AUTO_INCREMENT,
				post_id INT,
				promo_price DECIMAL(11,2),
				from_date INT,
				to_date INT,
				PRIMARY KEY(id)
			) $charset_collate;
			";
			require_once( ABSPATH . "wp-admin/includes/upgrade.php" );
			dbDelta( $sql_ );

			$indexing_sql = "CREATE INDEX post_id ON $mass_promo_table (post_id);";
			$index_status = $wpdb->query( $indexing_sql );
		}
	}

	function sg_ss_register_admin_js() {
		wp_enqueue_script( "sg_ss-admin-js", plugins_url( "/assets/admin.js" , __FILE__ ), array( "jquery" ), "1.0", true );
	}

	function sg_ss_register_admin_css( $hook ) {
		wp_enqueue_style( "sg_ss-admin-css", plugins_url( "/assets/admin.css", __FILE__ ), array(), "1.0", "screen" );
	}

	function sg_ss_register_front_css( $hook ) {
		wp_enqueue_style( "sg_ss-front-css", plugins_url( "/assets/front.css", __FILE__ ), array(), "1.0", "screen" );
	}

	function sg_ss_register_products_cpt() {
		$labels = array(
            'name'               => _x( 'Products', 'post type general name', 'sg_ss' ),
    		'singular_name'      => _x( 'Product', 'post type singular name', 'sg_ss' ),
    		'menu_name'          => _x( 'Products', 'admin menu', 'sg_ss' ),
    		'name_admin_bar'     => _x( 'Product', 'add new on admin bar', 'sg_ss' ),
    		'add_new'            => _x( 'Add New', 'product', 'sg_ss' ),
    		'add_new_item'       => __( 'Add New Product', 'sg_ss' ),
    		'new_item'           => __( 'New Product', 'sg_ss' ),
    		'edit_item'          => __( 'Edit Product', 'sg_ss' ),
    		'view_item'          => __( 'View Product', 'sg_ss' ),
    		'all_items'          => __( 'All Products', 'sg_ss' ),
    		'search_items'       => __( 'Search Product', 'sg_ss' ),
    		'parent_item_colon'  => __( 'Parent Products:', 'sg_ss' ),
    		'not_found'          => __( 'No products found.', 'sg_ss' ),
    		'not_found_in_trash' => __( 'No products found in Trash.', 'sg_ss' )
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __( 'All of your products goes here.', 'sg_ss' ),
    		'public'             => true,
    		'publicly_queryable' => true,
    		'show_ui'            => true,
    		'show_in_menu'       => true,
    		'query_var'          => true,
    		'rewrite'            => array( 'slug' => 'product' ),
    		'capability_type'    => 'post',
    		'has_archive'        => true,
    		'hierarchical'       => false,
    		'menu_position'      => null,
    		'supports'           => array( 'title', 'author', 'thumbnail' )
        );

        register_post_type( "product", $args );
	}

	function sg_ss_register_product_info_metaboxes( $post_type, $post ) {
		add_meta_box(
			"sg_ss_price",
			"Product Price",
			array( $this, "sg_ss_build_product_price_box" ),
			"product",
			"normal",
			"high"
		);

		add_meta_box(
			"sg_ss_quantity",
			"Product Quantity",
			array( $this, "sg_ss_build_product_quantity_box" ),
			"product",
			"normal",
			"high"
		);

		add_meta_box(
			"sg_ss_availability",
			"Product Availability",
			array( $this, "sg_ss_build_product_availability_box" ),
			"product",
			"normal",
			"high"
		);

		add_meta_box(
			"sg_ss_promo_price",
			"Product Promo Price & Period",
			array( $this, "sg_ss_build_product_promo_price_box" ),
			"product",
			"normal",
			"high"
		);
    }

	function sg_ss_build_product_price_box() {
		global $post;
		?>

		<input type="number" class="widefat" id="sg_ss_product-price" name="sg_ss_product_price" placeholder="Product Price" value="<?php echo !empty( $post->sg_ss_product_price ) && isset( $post->sg_ss_product_price ) ? $post->sg_ss_product_price : ""; ?>">

		<?php
	}

	function sg_ss_build_product_quantity_box() {
		global $post;
		?>

		<input type="number" class="widefat" id="sg_ss_product-quantity" name="sg_ss_product_quantity" placeholder="Product Quantity" value="<?php echo !empty( $post->sg_ss_product_quantity ) && isset( $post->sg_ss_product_quantity ) ? $post->sg_ss_product_quantity : ""; ?>">

		<?php
	}

	function sg_ss_build_product_availability_box() {
		global $post;
		?>

		<input type="number" class="widefat" id="sg_ss_product-availability" name="sg_ss_product_availability" placeholder="Product Availability" value="<?php echo !empty( $post->sg_ss_product_availability ) && isset( $post->sg_ss_product_availability ) ? $post->sg_ss_product_availability : ""; ?>">

		<?php
	}

	function sg_ss_build_product_promo_price_box() {
		global $post;
		?>

		<input type="number" class="widefat mb-1vw" id="sg_ss_product-promo-price" name="sg_ss_product_promo_price" placeholder="Product Promo Price" value="<?php echo !empty( $post->sg_ss_product_promo_price ) && isset( $post->sg_ss_product_promo_price ) ? $post->sg_ss_product_promo_price : ""; ?>">
		<input type="datetime-local" class="widefat mb-1vw" id="sg_ss_product-promo-price-from" name="sg_ss_product_promo_price_from" placeholder="Active From" value="<?php echo !empty( $post->sg_ss_product_promo_price_from ) && isset( $post->sg_ss_product_promo_price_from ) ? str_replace( " ", "T", date( "Y-m-d H:i", $post->sg_ss_product_promo_price_from ) ) : ""; ?>">
		<input type="datetime-local" class="widefat" id="sg_ss_product-promo-price-to" name="sg_ss_product_promo_price_to" placeholder="Active To" value="<?php echo !empty( $post->sg_ss_product_promo_price_to ) && isset( $post->sg_ss_product_promo_price_to ) ? str_replace( " ", "T", date( "Y-m-d H:i", $post->sg_ss_product_promo_price_to ) ) : ""; ?>">

		<?php
	}

	function sg_ss_save( $post_id ) {
		if ( isset( $_POST[ "post_type" ] ) && $_POST[ "post_type" ] == "product" ) {
			$product_ = new stdClass;
			$product_->price = isset( $_POST[ "sg_ss_product_price" ] ) && !empty( $_POST[ "sg_ss_product_price" ] ) ? floatval( $_POST[ "sg_ss_product_price" ] ) : false;
			$product_->quantity = isset( $_POST[ "sg_ss_product_quantity" ] ) && !empty( $_POST[ "sg_ss_product_quantity" ] ) ? intval( $_POST[ "sg_ss_product_quantity" ] ) : false;
			$product_->availability = isset( $_POST[ "sg_ss_product_availability" ] ) && !empty( $_POST[ "sg_ss_product_availability" ] ) ? intval( $_POST[ "sg_ss_product_availability" ] ) : false;
			$product_->promo_price = isset( $_POST[ "sg_ss_product_promo_price" ] ) && !empty( $_POST[ "sg_ss_product_promo_price" ] ) ? floatval( $_POST[ "sg_ss_product_promo_price" ] ) : false;
			$product_->sg_ss_product_promo_price_from = isset( $_POST[ "sg_ss_product_promo_price_from" ] ) && !empty( $_POST[ "sg_ss_product_promo_price_from" ] ) ? strtotime( $_POST[ "sg_ss_product_promo_price_from" ] ) : false;
			$product_->sg_ss_product_promo_price_to = isset( $_POST[ "sg_ss_product_promo_price_to" ] ) && !empty( $_POST[ "sg_ss_product_promo_price_to" ] ) ? strtotime( $_POST[ "sg_ss_product_promo_price_to" ] ) : false;

			// Product Price
			if ( $product_->price == false ) {
				$result = delete_post_meta( $post_id, "sg_ss_product_price" );
			} else {
				$result = update_post_meta( $post_id, "sg_ss_product_price", $product_->price );
			}

			// Product Quantity
			if ( $product_->quantity == false ) {
				$result = delete_post_meta( $post_id, "sg_ss_product_quantity" );
			} else {
				$result = update_post_meta( $post_id, "sg_ss_product_quantity", $product_->quantity );
			}

			// Product Availability
			if ( $product_->availability == false ) {
				$result = delete_post_meta( $post_id, "sg_ss_product_availability" );
			} else {
				$result = update_post_meta( $post_id, "sg_ss_product_availability", $product_->availability );
			}

			// Promo Price
			if ( $product_->promo_price == false ) {
				$result = delete_post_meta( $post_id, "sg_ss_product_promo_price" );
			} else {
				$result = update_post_meta( $post_id, "sg_ss_product_promo_price", $product_->promo_price );
			}

			// Promo Price From
			if ( $product_->sg_ss_product_promo_price_from == false ) {
				$result = delete_post_meta( $post_id, "sg_ss_product_promo_price_from" );
			} else {
				$result = update_post_meta( $post_id, "sg_ss_product_promo_price_from", $product_->sg_ss_product_promo_price_from );
			}

			// Promo Price To
			if ( $product_->sg_ss_product_promo_price_to == false ) {
				$result = delete_post_meta( $post_id, "sg_ss_product_promo_price_to" );
			} else {
				$result = update_post_meta( $post_id, "sg_ss_product_promo_price_to", $product_->sg_ss_product_promo_price_to );
			}
		}
	}

	function sg_ss_store_pages() {
        add_menu_page( "Store", "Store", "administrator", "sg_ss_store", array( $this, "sg_ss_store_dashboard_builder" ), "dashicons-tickets", NULL );
		add_submenu_page( "sg_ss_store", "Mass Promo", "Mass Promo", "administrator", "sg_ss_mass_promo", array( $this, "sg_ss_mass_promo_dashboard_builder" ) );
    }

	function sg_ss_store_dashboard_builder() {
        require_once plugin_dir_path( __FILE__ ) ."pages/store.php";
    }

	function sg_ss_mass_promo_dashboard_builder() {
        require_once plugin_dir_path( __FILE__ ) ."pages/mass-promo.php";
    }

	function sg_ss_get_products() {
		$response = false;

		if ( is_user_logged_in() ) {
			$is_mass_promo = isset( $_POST[ "mass_promo" ] ) && !empty( $_POST[ "mass_promo" ] ) ? json_decode( $_POST[ "mass_promo" ] ) : false;

			$args = array(
				"posts_per_page" => -1,
				"post_type" => "product",
				"post_status" => "publish",
				"orderby" => "ID",
				"order" => "DESC"
			);
			$posts_ = get_posts( $args );

			if ( !empty( $posts_ ) ) {
				$response = array();
				foreach ( $posts_ as $post_ ) {
					$product_ = new stdClass;
					$product_->id = $post_->ID;
					$product_->title = $post_->post_title;
					$product_->image = get_the_post_thumbnail_url( $post_->ID, "full" );
					$product_->image = isset( $product_->image ) && !empty( $product_->image ) ? $product_->image : "";
					$product_->price = get_post_meta( $post_->ID, "sg_ss_product_price", true );
					$product_->quantity = get_post_meta( $post_->ID, "sg_ss_product_quantity", true );
					$product_->availability = get_post_meta( $post_->ID, "sg_ss_product_availability", true );
					$product_->promo_price = get_post_meta( $post_->ID, "sg_ss_product_promo_price", true );
					$product_->promo_price_from = get_post_meta( $post_->ID, "sg_ss_product_promo_price_from", true );
					$product_->promo_price_from = isset( $product_->promo_price_from ) && !empty( $product_->promo_price_from ) ? date( "Y-m-d H:i", $product_->promo_price_from ) : "";
					$product_->promo_price_to = get_post_meta( $post_->ID, "sg_ss_product_promo_price_to", true );
					$product_->promo_price_to = isset( $product_->promo_price_to ) && !empty( $product_->promo_price_to ) ? date( "Y-m-d H:i", $product_->promo_price_to ) : "";
					$product_->edit_link = get_edit_post_link( $post_->ID );
					array_push( $response, $product_ );
				}
			}

			// Get Mass Promo items if needed
			if ( $is_mass_promo == true ) {
				global $wpdb;
				$mass_promo_table = $wpdb->prefix ."mass_promo";

				$tmp_response = $response;
				$response = new stdClass;
				$response->products = $tmp_response;

				$sql_ = "SELECT * FROM $mass_promo_table";
				$results_ = $wpdb->get_results( $sql_, OBJECT );
				if ( !empty( $results_ ) ) {
					$response->mass_promo_ids = array();
					$response->mass_promo_price = 0;
					$response->mass_promo_from = 0;
					$response->mass_promo_to = 0;

					foreach ( $results_ as $result_ ) {
						array_push( $response->mass_promo_ids, intval( $result_->post_id ) );
						if ( $response->mass_promo_price == 0 ) { $response->mass_promo_price = $result_->promo_price; }
						if ( $response->mass_promo_from == 0 ) { $response->mass_promo_from = str_replace( " ", "T", date( "Y-m-d H:i", $result_->from_date ) ); }
						if ( $response->mass_promo_to == 0 ) { $response->mass_promo_to = str_replace( " ", "T", date( "Y-m-d H:i", $result_->to_date ) ); }
					}
				}
			}
		}

		echo json_encode( $response );
		die( "" );
	}

	function sg_ss_save_mass_promo() {
		$response = false;

		if ( is_user_logged_in() ) {
			$items = isset( $_POST[ "picked_items" ] ) && !empty( $_POST[ "picked_items" ] ) && is_array( $_POST[ "picked_items" ] ) ? $_POST[ "picked_items" ] : array();
			$price = isset( $_POST[ "price" ] ) && !empty( $_POST[ "price" ] ) ? floatval( $_POST[ "price" ] ) : 0;
			$from = isset( $_POST[ "from" ] ) && !empty( $_POST[ "from" ] ) ? strtotime( $_POST[ "from" ] ) : false;
			$to = isset( $_POST[ "to" ] ) && !empty( $_POST[ "to" ] ) ? strtotime( $_POST[ "to" ] ) : false;

			global $wpdb;
			$mass_promo_table = $wpdb->prefix ."mass_promo";

			if ( !empty( $items ) && $price > 0 ) {
				// Delete All Items which are not picked
				$pure_item_ids = array();
				foreach ( $items as $item_id ) {
					$item_id = intval( $item_id );
					if ( $item_id > 0 ) {
						array_push( $pure_item_ids, $item_id );
					}
				}

				$pure_item_ids = implode( ",", $pure_item_ids );
				$sql_ = "DELETE FROM $mass_promo_table WHERE post_id NOT IN ($pure_item_ids)";
				$wpdb->query( $sql_ );

				// Save the new files OR update existing one
				$pure_item_ids = explode( ",", $pure_item_ids );
				foreach ( $pure_item_ids as $item_id ) {
					$sql_ = "SELECT post_id FROM $mass_promo_table WHERE post_id=$item_id LIMIT 1";
					$results_ = $wpdb->get_results( $sql_, OBJECT );
					if ( empty( $results_ ) ) { // Insert
						$wpdb->insert(
							$mass_promo_table,
							array(
								"post_id" => $item_id,
								"promo_price" => $price,
								"from_date" => $from != false ? $from : 0,
								"to_date" => $to != false ? $to : 0
							),
							array(
								"%d",
								"%f",
								"%d",
								"%d"
							)
						);
					} else { // Update
						$wpdb->update(
							$mass_promo_table,
							array(
								"promo_price" => $price,
								"from_date" => $from != false ? $from : 0,
								"to_date" => $to != false ? $to : 0
							),
							array(
								"post_id" => $item_id
							),
							array(
								"%d",
								"%d",
								"%d"
							)
						);
					}

					$response = true;
				}
			} elseif ( empty( $items ) ) {
				$sql_ = "DELETE FROM $mass_promo_table";
				$wpdb->query( $sql_ );
				$response = "All items from mass promo are deleted.";
			} else {
				$response = "What's the promo price?";
			}
		}

		echo json_encode( $response );
		die( "" );
	}

	function sg_ss_custom_single_product_woo( $template, $template_name, $template_path ) {
		global $post;
		if ( $post->post_type == "product" && $template_name == "single-product/price.php" ) {
			$dir = plugin_dir_path( __FILE__ ) ."pages/single-product-woo.php";
			if ( file_exists( $dir ) ) {
				$template = $dir;
			}
		}
		return $template;
	}

	function sg_ss_custom_single_product( $single ) {
		global $post;
		if ( $post->post_type == "product" ) {
			$dir = plugin_dir_path( __FILE__ ) ."pages/single-product.php";
			if ( file_exists( $dir ) ) {
				return $dir;
		    }
		} else { return $single; }
	}

	function sg_ss_delete_product_meta( $post_id ) {
		if ( get_post_type( $post_id ) == "product" ) {
			$result = delete_post_meta( $post_id, "sg_ss_product_price" );
			$result = delete_post_meta( $post_id, "sg_ss_product_quantity" );
			$result = delete_post_meta( $post_id, "sg_ss_product_availability" );
			$result = delete_post_meta( $post_id, "sg_ss_product_promo_price" );
			$result = delete_post_meta( $post_id, "sg_ss_product_promo_price_from" );
			$result = delete_post_meta( $post_id, "sg_ss_product_promo_price_to" );

			// Remove it from Mass Promo if needed
			global $wpdb;
			$mass_promo_table = $wpdb->prefix ."mass_promo";

			$wpdb->delete(
				$mass_promo_table,
				array(
					"post_id" => $post_id
				)
			);
		}
	}
}

$SG_SS = new SG_SS;
?>
