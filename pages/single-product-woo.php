<?php
global $wpdb;
$mass_promo_table = $wpdb->prefix ."mass_promo";

$post_id = get_the_ID();

$product_ = new stdClass;
$product_->price = get_post_meta( $post_id, "sg_ss_product_price", true );

// Check if the product has mass promo for it
$sql_ = "SELECT * FROM $mass_promo_table WHERE post_id=$post_id LIMIT 1";
$results_ = $wpdb->get_results( $sql_, OBJECT );
if ( !empty( $results_ ) ) {
	$promo = $results_[ 0 ];
	$product_->promo_price = $promo->promo_price;
	$product_->promo_price_from = $promo->from_date;
	$product_->promo_price_to = $promo->to_date;
} else {
	$product_->promo_price = get_post_meta( $post_id, "sg_ss_product_promo_price", true );
	$product_->promo_price_from = get_post_meta( $post_id, "sg_ss_product_promo_price_from", true );
	$product_->promo_price_to = get_post_meta( $post_id, "sg_ss_product_promo_price_to", true );
}

$date_serial = strtotime( date( "Y-m-d H:i" ) );
$in_promo = false;

if (
	!empty( $product_->promo_price ) && ( $product_->promo_price_from <= $date_serial && $product_->promo_price_to >= $date_serial ) ||
	!empty( $product_->promo_price ) && $product_->promo_price_from <= $date_serial && empty( $product_->promo_price_to )
) {
	$in_promo = true;
}
?>
<div class="label">Price: </div>
<div class="price">
	<div class="normal <?php echo $in_promo ? "disable" : ""; ?>"><?php echo $product_->price; ?></div>
	<?php
	if ( $in_promo ) {
		?>

		<div class="promo"><?php echo $product_->promo_price; ?></div>

		<?php
	}
	?>
</div>
