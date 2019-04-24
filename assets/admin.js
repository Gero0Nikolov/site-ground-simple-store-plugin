var loader = "<div id='loader'>Loading...</div>";
var picked_items = [];

jQuery( document ).ready( function(){
	if ( jQuery( "#sg_ss-store" ).length > 0 ) {
		jQuery( "#sg_ss-store #store-list" ).append( loader );

		jQuery.ajax( {
			url : ajaxurl,
			type : "POST",
			data : {
				action : "sg_ss_get_products"
			},
			success : function( response ) {
				if ( response !== undefined ) {
					jQuery( "#sg_ss-store #store-list #loader" ).remove();

					result_ = JSON.parse( response );
					if ( result_ != false && result_.length > 0 ) {
						for ( key in result_ ) {
							product_ = result_[ key ];

							view = "\
							<div id='product-"+ product_.id +"' class='product'>\
								<div class='product-image' style='background-image: url("+ product_.image +");'></div>\
								<div class='product-content'>\
									<h2 class='product-name'>"+ product_.title +"</h2>\
									<div class='product-data'>\
										<div class='col'>Price: "+ product_.price +"</div>\
										<div class='col'>Quantity: "+ product_.quantity +"</div>\
									</div>\
									<div class='product-data'>\
										<div class='col'>Availability: "+ product_.availability +"</div>\
										<div class='col'>Promo Price: "+ product_.promo_price +"</div>\
									</div>\
									<div class='product-data'>\
										<div class='col'>Promo Price From: "+ product_.promo_price_from +"</div>\
										<div class='col'>Promo Price To: "+ product_.promo_price_to +"</div>\
									</div>\
									<a href='"+ product_.edit_link +"' class='preview button'>Edit</a>\
								</div>\
							</div>\
							";
							jQuery( "#sg_ss-store #store-list" ).append( view );
						}
					} else {
						alert( "You don't have any products..." );
					}
				}
			},
			error : function( response ) {
				console.log( response );
			}
		} );
	}

	if ( jQuery( "#sg_ss-mass-promo" ).length > 0 ) {
		jQuery( "#sg_ss-mass-promo #mass-promo-list" ).append( loader );

		jQuery.ajax( {
			url : ajaxurl,
			type : "POST",
			data : {
				action : "sg_ss_get_products",
				mass_promo : JSON.stringify( true )
			},
			success : function( response ) {
				if ( response !== undefined ) {
					jQuery( "#sg_ss-mass-promo #mass-promo-list #loader" ).remove();

					result_ = JSON.parse( response );
					console.log( result_ );
					if ( result_ != false && result_.products.length > 0 ) {
						for ( key in result_.products ) {
							product_ = result_.products[ key ];

							view = "\
							<div id='product-"+ product_.id +"' class='product'>\
								<div class='product-image' style='background-image: url("+ product_.image +");'></div>\
								<div class='product-content'>\
									<h2 class='product-name'>"+ product_.title +"</h2>\
									<div class='product-data'>\
										<div class='col'>Price: "+ product_.price +"</div>\
										<div class='col'>Quantity: "+ product_.quantity +"</div>\
									</div>\
									<div class='product-data'>\
										<div class='col'>Availability: "+ product_.availability +"</div>\
										<div class='col'>Promo Price: "+ product_.promo_price +"</div>\
									</div>\
									<div class='product-data'>\
										<div class='col'>Promo Price From: "+ product_.promo_price_from +"</div>\
										<div class='col'>Promo Price To: "+ product_.promo_price_to +"</div>\
									</div>\
									<button product-id='"+ product_.id +"' class='pick preview button'>Pick</a>\
								</div>\
							</div>\
							";
							jQuery( "#sg_ss-mass-promo #mass-promo-list" ).append( view );

							jQuery( "#product-"+ product_.id +" .pick" ).on( "click", function( e ){
								e.preventDefault();
								product_id = parseInt( jQuery( this ).attr( "product-id" ) );

								if ( jQuery( this ).hasClass( "pick" ) ) {
									picked_items.push( product_id );
									jQuery( this ).removeClass( "pick" ).addClass( "picked" ).html( "Picked" );
								} else if ( jQuery( this ).hasClass( "picked" ) ) {
									picked_items.splice( picked_items.indexOf( product_id ), 1 );
									jQuery( this ).removeClass( "picked" ).addClass( "pick" ).html( "Pick" );
								}
							} );

							// Add picked items if needed
							if ( typeof( result_.mass_promo_ids ) !== "undefined" && result_.mass_promo_ids.indexOf( product_.id ) > -1 ) {
								jQuery( "#product-"+ product_.id +" .pick" ).trigger( "click" );
							}
						}

						// Set Mass Promo Price
						if ( typeof( result_.mass_promo_price ) !== "undefined" ) {
							jQuery( "#sg_ss-mass-promo #mass-promo-list #mass-promo-fields #mass-promo-price" ).val( result_.mass_promo_price );
						}

						if ( typeof( result_.mass_promo_from ) !== "undefined" ) {
							jQuery( "#sg_ss-mass-promo #mass-promo-list #mass-promo-fields #mass-promo-price-from" ).val( result_.mass_promo_from );
						}

						if ( typeof( result_.mass_promo_to ) !== "undefined" ) {
							jQuery( "#sg_ss-mass-promo #mass-promo-list #mass-promo-fields #mass-promo-price-to" ).val( result_.mass_promo_to );
						}
					} else {
						alert( "You don't have any products..." );
					}
				}
			},
			error : function( response ) {
				console.log( response );
			}
		} );

		jQuery( "#sg_ss-mass-promo #mass-promo-list #mass-promo-fields #submit-mass-promo" ).on( "click", function( e ){
			e.preventDefault();

			promo_price = jQuery( "#sg_ss-mass-promo #mass-promo-list #mass-promo-fields #mass-promo-price" ).val();
			from = jQuery( "#sg_ss-mass-promo #mass-promo-list #mass-promo-fields #mass-promo-price-from" ).val();
			to = jQuery( "#sg_ss-mass-promo #mass-promo-list #mass-promo-fields #mass-promo-price-to" ).val();

			jQuery.ajax( {
				url : ajaxurl,
				type : "POST",
				data : {
					action : "sg_ss_save_mass_promo",
					picked_items : picked_items,
					price : promo_price,
					from : from,
					to : to
				},
				success : function( response ) {
					console.log( response );
					if ( response !== undefined ) {
						result_ = JSON.parse( response );
						if ( result_ == true ) {
							alert( "Mass Promo is saved!" );
							window.location.reload( true );
						} else {
							if ( result_ != false ) {
								alert( result_ );
							} else { console.log( result_ ); }
						}
					}
				},
				error : function( response ){
					cosnole.log( response );
				}
			} );
		} );
	}
} );
