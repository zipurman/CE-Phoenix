<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2020 osCommerce

  Released under the GNU General Public License
*/

  if (STOCK_CHECK == 'true') {
    $any_out_of_stock = false;
    foreach ($order->products as $product) {
      if (tep_check_stock($product['id'], $product['qty'])) {
        $any_out_of_stock = true;
      }
    }
    
    // Out of Stock
    if ( $any_out_of_stock && (STOCK_ALLOW_CHECKOUT != 'true') ) {
      tep_redirect(tep_href_link('shopping_cart.php'));
    }
  }
