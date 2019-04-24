# site-ground-simple-store-plugin
Site Ground Simple Store Plugin Task - All the requirements are covered excluding React :(

# Table of Contents:
1. Assets folder:
  1. Front.scss
  2. Admin.scss
  3. Admin.js
2. Pages:
  1. Mass-Promo.php
  2. Single-Product-WOO.php (Used in case WooCommerce is active)
  3. Single-Product.php (Default Single Product Post)
  4. Store.php
3. SG-SS.php (Plugin Main File)

# Setup
1. Place the plugin folder in the wp-content/plugins/ folder
2. Go to WP-Admin and start it.
  1. Upon start it'll create the Custom Post Type - Product
  2. Upon start it'll create a custom table called - wp_prefix_mass_promo

# Usage
1. Go to Product from the sidebar of the Admin
  1. There you'll find:
    1. Product price
    2. Product quantity
    3. Product availability
    4. Product Promo Price
    5. Product Promo Price From
    6. Product Promo Price To (Note: If you don't fill the expiration of the promotion it can stay active forever)
2. In the sidebar of the Admin you'll also find a new page called - Store:
  1. Store Page:
    1. List of all PUBLISHED items with all of their data.
    2. Edit button for quick transition to the editing screen.
  2. Mass Promo Sub Page of Store:
    1. List of all PUBLISHED items with their data.
    2. Pick / Unpick action to choose which products should receive Mass Promotion.
    3. Choose promo price and from - to date. (Note: If you don't fill the expiration of the promotion it can stay active forever)
    4. To remove products from Mass Promo just UNPICK them and click Save.
3. Product Preview:
  1. If WooCommerce is active you should fill the product price in the Woo field in order to see the fields.
    1.1. Since Woo is calling the template part - woocommerce/woocommerce.php only when the default fields are filled.
    1.2. Woo fields are not related to the prices which you'll put in the SG_SS plugin fields, WOO is used only to visualize the
    prices in the propper part of the page when Woo is active.
  2. If only SG_SS is active you'll see the default Single-Product.php template.  
4. Product Deletion:
  1. Upon removal from TRASH the plugin will take care of the product meta create by SG_SS and it'll remove the Mass Promotion setup
  from the wp_prefix_mass_promo table as well.

# Closing remarks
It was very interesting task. If there is some issue with the setup please let me know, however it should work with just Plug & Play.
Happy Easter!
