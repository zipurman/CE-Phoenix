<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2020 osCommerce

  Released under the GNU General Public License
*/

  class order {
    var $info, $totals, $products, $customer, $delivery, $content_type;

    function __construct($order_id = '') {
      $this->info = [];
      $this->totals = [];
      $this->products = [];
      $this->customer = [];
      $this->delivery = [];

      if (tep_not_null($order_id)) {
        $this->query($order_id);
      } else {
        $this->cart();
      }
    }

    function query($order_id) {
      $order_id = tep_db_prepare_input($order_id);

      $order_query = tep_db_query("select customers_id, customers_name, customers_company, customers_street_address, customers_suburb, customers_city, customers_postcode, customers_state, customers_country, customers_telephone, customers_email_address, customers_address_format_id, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, billing_name, billing_company, billing_street_address, billing_suburb, billing_city, billing_postcode, billing_state, billing_country, billing_address_format_id, payment_method, cc_type, cc_owner, cc_number, cc_expires, currency, currency_value, date_purchased, orders_status, last_modified from orders where orders_id = '" . (int)$order_id . "'");
      $order = tep_db_fetch_array($order_query);

      $totals_query = tep_db_query("select title, text from orders_total where orders_id = '" . (int)$order_id . "' order by sort_order");
      while ($totals = tep_db_fetch_array($totals_query)) {
        $this->totals[] = [
          'title' => $totals['title'],
          'text' => $totals['text'],
        ];
      }

      $order_total_query = tep_db_query("select text from orders_total where orders_id = '" . (int)$order_id . "' and class = 'ot_total'");
      $order_total = tep_db_fetch_array($order_total_query);

      $shipping_method_query = tep_db_query("select title from orders_total where orders_id = '" . (int)$order_id . "' and class = 'ot_shipping'");
      $shipping_method = tep_db_fetch_array($shipping_method_query);

      $order_status_query = tep_db_query("select orders_status_name from orders_status where orders_status_id = '" . $order['orders_status'] . "' and language_id = '" . (int)$_SESSION['languages_id'] . "'");
      $order_status = tep_db_fetch_array($order_status_query);

      $this->info = [
        'currency' => $order['currency'],
        'currency_value' => $order['currency_value'],
        'payment_method' => $order['payment_method'],
        'cc_type' => $order['cc_type'],
        'cc_owner' => $order['cc_owner'],
        'cc_number' => $order['cc_number'],
        'cc_expires' => $order['cc_expires'],
        'date_purchased' => $order['date_purchased'],
        'orders_status' => $order_status['orders_status_name'],
        'last_modified' => $order['last_modified'],
        'total' => strip_tags($order_total['text']),
        'shipping_method' => ((substr($shipping_method['title'], -1) == ':') ? substr(strip_tags($shipping_method['title']), 0, -1) : strip_tags($shipping_method['title'])),
      ];

      $this->customer = [
        'id' => $order['customers_id'],
        'name' => $order['customers_name'],
        'company' => $order['customers_company'],
        'street_address' => $order['customers_street_address'],
        'suburb' => $order['customers_suburb'],
        'city' => $order['customers_city'],
        'postcode' => $order['customers_postcode'],
        'state' => $order['customers_state'],
        'country' => ['title' => $order['customers_country']],
        'format_id' => $order['customers_address_format_id'],
        'telephone' => $order['customers_telephone'],
        'email_address' => $order['customers_email_address'],
      ];

      $this->delivery = [
        'name' => trim($order['delivery_name']),
        'company' => $order['delivery_company'],
        'street_address' => $order['delivery_street_address'],
        'suburb' => $order['delivery_suburb'],
        'city' => $order['delivery_city'],
        'postcode' => $order['delivery_postcode'],
        'state' => $order['delivery_state'],
        'country' => ['title' => $order['delivery_country']],
        'format_id' => $order['delivery_address_format_id'],
      ];

      if (empty($this->delivery['name']) && empty($this->delivery['street_address'])) {
        $this->delivery = false;
      }

      $this->billing = [
        'name' => $order['billing_name'],
        'company' => $order['billing_company'],
        'street_address' => $order['billing_street_address'],
        'suburb' => $order['billing_suburb'],
        'city' => $order['billing_city'],
        'postcode' => $order['billing_postcode'],
        'state' => $order['billing_state'],
        'country' => ['title' => $order['billing_country']],
        'format_id' => $order['billing_address_format_id'],
      ];

      $index = 0;
      $orders_products_query = tep_db_query("select orders_products_id, products_id, products_name, products_model, products_price, products_tax, products_quantity, final_price from orders_products where orders_id = '" . (int)$order_id . "'");
      while ($orders_products = tep_db_fetch_array($orders_products_query)) {
        $this->products[$index] = [
          'qty' => $orders_products['products_quantity'],
          'id' => $orders_products['products_id'],
          'name' => $orders_products['products_name'],
          'model' => $orders_products['products_model'],
          'tax' => $orders_products['products_tax'],
          'price' => $orders_products['products_price'],
          'final_price' => $orders_products['final_price'],
        ];

        $subindex = 0;
        $attributes_query = tep_db_query("select products_options, products_options_values, options_values_price, price_prefix from orders_products_attributes where orders_id = '" . (int)$order_id . "' and orders_products_id = '" . (int)$orders_products['orders_products_id'] . "'");
        if (tep_db_num_rows($attributes_query)) {
          while ($attributes = tep_db_fetch_array($attributes_query)) {
            $this->products[$index]['attributes'][$subindex] = [
              'option' => $attributes['products_options'],
              'value' => $attributes['products_options_values'],
              'prefix' => $attributes['price_prefix'],
              'price' => $attributes['options_values_price'],
            ];

            $subindex++;
          }
        }

        $this->info['tax_groups']["{$this->products[$index]['tax']}"] = '1';

        $index++;
      }
    }

    function cart() {
      global $currencies;

      $this->content_type = $_SESSION['cart']->get_content_type();

      if ( ($this->content_type != 'virtual') && !$_SESSION['sendto'] ) {
        $_SESSION['sendto'] = $GLOBALS['customer']->get('default_sendto');
      }

      $customer_address_query = tep_db_query("select c.customers_firstname, c.customers_lastname, c.customers_telephone, c.customers_email_address, ab.entry_company, ab.entry_street_address, ab.entry_suburb, ab.entry_postcode, ab.entry_city, ab.entry_zone_id, z.zone_name, co.countries_id, co.countries_name, co.countries_iso_code_2, co.countries_iso_code_3, co.address_format_id, ab.entry_state from customers c, address_book ab left join zones z on (ab.entry_zone_id = z.zone_id) left join countries co on (ab.entry_country_id = co.countries_id) where c.customers_id = '" . (int)$_SESSION['customer_id'] . "' and ab.customers_id = '" . (int)$_SESSION['customer_id'] . "' and c.customers_default_address_id = ab.address_book_id");
      $customer_address = tep_db_fetch_array($customer_address_query);

      if (!empty($_SESSION['sendto']) && is_array($_SESSION['sendto'])) {
        $shipping_address = [
          'entry_firstname' => $_SESSION['sendto']['firstname'],
          'entry_lastname' => $_SESSION['sendto']['lastname'],
          'entry_company' => $_SESSION['sendto']['company'],
          'entry_street_address' => $_SESSION['sendto']['street_address'],
          'entry_suburb' => $_SESSION['sendto']['suburb'],
          'entry_postcode' => $_SESSION['sendto']['postcode'],
          'entry_city' => $_SESSION['sendto']['city'],
          'entry_zone_id' => $_SESSION['sendto']['zone_id'],
          'zone_name' => $_SESSION['sendto']['zone_name'],
          'entry_country_id' => $_SESSION['sendto']['country_id'],
          'countries_id' => $_SESSION['sendto']['country_id'],
          'countries_name' => $_SESSION['sendto']['country_name'],
          'countries_iso_code_2' => $_SESSION['sendto']['country_iso_code_2'],
          'countries_iso_code_3' => $_SESSION['sendto']['country_iso_code_3'],
          'address_format_id' => $_SESSION['sendto']['address_format_id'],
          'entry_state' => $_SESSION['sendto']['zone_name'],
        ];
      } elseif (is_numeric($_SESSION['sendto'])) {
        $shipping_address_query = tep_db_query("select ab.entry_firstname, ab.entry_lastname, ab.entry_company, ab.entry_street_address, ab.entry_suburb, ab.entry_postcode, ab.entry_city, ab.entry_zone_id, z.zone_name, ab.entry_country_id, c.countries_id, c.countries_name, c.countries_iso_code_2, c.countries_iso_code_3, c.address_format_id, ab.entry_state from address_book ab left join zones z on (ab.entry_zone_id = z.zone_id) left join countries c on (ab.entry_country_id = c.countries_id) where ab.customers_id = '" . (int)$_SESSION['customer_id'] . "' and ab.address_book_id = '" . (int)$_SESSION['sendto'] . "'");
        $shipping_address = tep_db_fetch_array($shipping_address_query);
      } else {
        $shipping_address = [
          'entry_firstname' => null,
          'entry_lastname' => null,
          'entry_company' => null,
          'entry_street_address' => null,
          'entry_suburb' => null,
          'entry_postcode' => null,
          'entry_city' => null,
          'entry_zone_id' => null,
          'zone_name' => null,
          'entry_country_id' => null,
          'countries_id' => null,
          'countries_name' => null,
          'countries_iso_code_2' => null,
          'countries_iso_code_3' => null,
          'address_format_id' => 0,
          'entry_state' => null,
        ];
      }

      if (!empty($_SESSION['billto']) && is_array($_SESSION['billto'])) {
        $billing_address = [
          'entry_firstname' => $_SESSION['billto']['firstname'],
          'entry_lastname' => $_SESSION['billto']['lastname'],
          'entry_company' => $_SESSION['billto']['company'],
          'entry_street_address' => $_SESSION['billto']['street_address'],
          'entry_suburb' => $_SESSION['billto']['suburb'],
          'entry_postcode' => $_SESSION['billto']['postcode'],
          'entry_city' => $_SESSION['billto']['city'],
          'entry_zone_id' => $_SESSION['billto']['zone_id'],
          'zone_name' => $_SESSION['billto']['zone_name'],
          'entry_country_id' => $_SESSION['billto']['country_id'],
          'countries_id' => $_SESSION['billto']['country_id'],
          'countries_name' => $_SESSION['billto']['country_name'],
          'countries_iso_code_2' => $_SESSION['billto']['country_iso_code_2'],
          'countries_iso_code_3' => $_SESSION['billto']['country_iso_code_3'],
          'address_format_id' => $_SESSION['billto']['address_format_id'],
          'entry_state' => $_SESSION['billto']['zone_name'],
        ];
      } else {
        $billing_address_query = tep_db_query("select ab.entry_firstname, ab.entry_lastname, ab.entry_company, ab.entry_street_address, ab.entry_suburb, ab.entry_postcode, ab.entry_city, ab.entry_zone_id, z.zone_name, ab.entry_country_id, c.countries_id, c.countries_name, c.countries_iso_code_2, c.countries_iso_code_3, c.address_format_id, ab.entry_state from address_book ab left join zones z on (ab.entry_zone_id = z.zone_id) left join countries c on (ab.entry_country_id = c.countries_id) where ab.customers_id = '" . (int)$_SESSION['customer_id'] . "' and ab.address_book_id = '" . (int)$_SESSION['billto'] . "'");
        $billing_address = tep_db_fetch_array($billing_address_query);
      }

      if ($this->content_type == 'virtual') {
        $tax_address = [
          'entry_country_id' => $billing_address['entry_country_id'],
          'entry_zone_id' => $billing_address['entry_zone_id'],
        ];
      } else {
        $tax_address = [
          'entry_country_id' => $shipping_address['entry_country_id'],
          'entry_zone_id' => $shipping_address['entry_zone_id'],
        ];
      }

      $this->info = [
        'order_status' => DEFAULT_ORDERS_STATUS_ID,
        'currency' => $_SESSION['currency'],
        'currency_value' => $currencies->currencies[$_SESSION['currency']]['value'],
        'payment_method' => $_SESSION['payment'],
        'cc_type' => '',
        'cc_owner' => '',
        'cc_number' => '',
        'cc_expires' => '',
        'shipping_method' => $_SESSION['shipping']['title'],
        'shipping_cost' => $_SESSION['shipping']['cost'],
        'subtotal' => 0,
        'tax' => 0,
        'tax_groups' => [],
        'comments' => ($_SESSION['comments'] ?? ''),
      ];

      if (isset($GLOBALS[$_SESSION['payment']]) && is_object($GLOBALS[$_SESSION['payment']])) {
        if (isset($GLOBALS[$_SESSION['payment']]->public_title)) {
          $this->info['payment_method'] = $GLOBALS[$_SESSION['payment']]->public_title;
        } else {
          $this->info['payment_method'] = $GLOBALS[$_SESSION['payment']]->title;
        }

        if ( isset($GLOBALS[$_SESSION['payment']]->order_status) && is_numeric($GLOBALS[$_SESSION['payment']]->order_status) && ($GLOBALS[$_SESSION['payment']]->order_status > 0) ) {
          $this->info['order_status'] = $GLOBALS[$_SESSION['payment']]->order_status;
        }
      }

      $this->customer = [
        'firstname' => $customer_address['customers_firstname'],
        'lastname' => $customer_address['customers_lastname'],
        'company' => $customer_address['entry_company'],
        'street_address' => $customer_address['entry_street_address'],
        'suburb' => $customer_address['entry_suburb'],
        'city' => $customer_address['entry_city'],
        'postcode' => $customer_address['entry_postcode'],
        'state' => ((tep_not_null($customer_address['entry_state'])) ? $customer_address['entry_state'] : $customer_address['zone_name']),
        'zone_id' => $customer_address['entry_zone_id'],
        'country' => ['id' => $customer_address['countries_id'], 'title' => $customer_address['countries_name'], 'iso_code_2' => $customer_address['countries_iso_code_2'], 'iso_code_3' => $customer_address['countries_iso_code_3']],
        'format_id' => $customer_address['address_format_id'],
        'telephone' => $customer_address['customers_telephone'],
        'email_address' => $customer_address['customers_email_address'],
      ];

      $this->delivery = [
        'firstname' => $shipping_address['entry_firstname'],
        'lastname' => $shipping_address['entry_lastname'],
        'company' => $shipping_address['entry_company'],
        'street_address' => $shipping_address['entry_street_address'],
        'suburb' => $shipping_address['entry_suburb'],
        'city' => $shipping_address['entry_city'],
        'postcode' => $shipping_address['entry_postcode'],
        'state' => ((tep_not_null($shipping_address['entry_state'])) ? $shipping_address['entry_state'] : $shipping_address['zone_name']),
        'zone_id' => $shipping_address['entry_zone_id'],
        'country' => [
          'id' => $shipping_address['countries_id'],
          'title' => $shipping_address['countries_name'],
          'iso_code_2' => $shipping_address['countries_iso_code_2'],
          'iso_code_3' => $shipping_address['countries_iso_code_3'],
        ],
        'country_id' => $shipping_address['entry_country_id'],
        'format_id' => $shipping_address['address_format_id'],
      ];

      $this->billing = [
        'firstname' => $billing_address['entry_firstname'],
        'lastname' => $billing_address['entry_lastname'],
        'company' => $billing_address['entry_company'],
        'street_address' => $billing_address['entry_street_address'],
        'suburb' => $billing_address['entry_suburb'],
        'city' => $billing_address['entry_city'],
        'postcode' => $billing_address['entry_postcode'],
        'state' => ((tep_not_null($billing_address['entry_state'])) ? $billing_address['entry_state'] : $billing_address['zone_name']),
        'zone_id' => $billing_address['entry_zone_id'],
        'country' => [
          'id' => $billing_address['countries_id'],
          'title' => $billing_address['countries_name'],
          'iso_code_2' => $billing_address['countries_iso_code_2'],
          'iso_code_3' => $billing_address['countries_iso_code_3'],
        ],
        'country_id' => $billing_address['entry_country_id'],
        'format_id' => $billing_address['address_format_id'],
      ];

      $index = 0;
      $products = $_SESSION['cart']->get_products();
      for ($i=0, $n=sizeof($products); $i<$n; $i++) {
        $this->products[$index] = [
          'qty' => $products[$i]['quantity'],
          'name' => $products[$i]['name'],
          'model' => $products[$i]['model'],
          'tax' => tep_get_tax_rate($products[$i]['tax_class_id'], $tax_address['entry_country_id'], $tax_address['entry_zone_id']),
          'tax_description' => tep_get_tax_description($products[$i]['tax_class_id'], $tax_address['entry_country_id'], $tax_address['entry_zone_id']),
          'price' => $products[$i]['price'],
          'final_price' => $products[$i]['price'] + $_SESSION['cart']->attributes_price($products[$i]['id']),
          'weight' => $products[$i]['weight'],
          'id' => $products[$i]['id'],
        ];

        if ($products[$i]['attributes']) {
          $subindex = 0;
          foreach ($products[$i]['attributes'] as $option => $value) {
            $attributes_query = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from products_options  popt, products_options_values poval, products_attributes pa where pa.products_id = '" . (int)$products[$i]['id'] . "' and pa.options_id = '" . (int)$option . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . (int)$value . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . (int)$_SESSION['languages_id'] . "' and poval.language_id = '" . (int)$_SESSION['languages_id'] . "'");
            $attributes = tep_db_fetch_array($attributes_query);

            $this->products[$index]['attributes'][$subindex] = [
              'option' => $attributes['products_options_name'],
              'value' => $attributes['products_options_values_name'],
              'option_id' => $option,
              'value_id' => $value,
              'prefix' => $attributes['price_prefix'],
              'price' => $attributes['options_values_price'],
            ];

            $subindex++;
          }
        }

        $shown_price = $currencies->calculate_price($this->products[$index]['final_price'], $this->products[$index]['tax'], $this->products[$index]['qty']);
        $this->info['subtotal'] += $shown_price;

        $products_tax = $this->products[$index]['tax'];
        $products_tax_description = $this->products[$index]['tax_description'];
        if (DISPLAY_PRICE_WITH_TAX == 'true') {
          $this->info['tax'] += $shown_price - ($shown_price / (($products_tax < 10) ? "1.0" . str_replace('.', '', $products_tax) : "1." . str_replace('.', '', $products_tax)));
          if (isset($this->info['tax_groups']["$products_tax_description"])) {
            $this->info['tax_groups']["$products_tax_description"] += $shown_price - ($shown_price / (($products_tax < 10) ? "1.0" . str_replace('.', '', $products_tax) : "1." . str_replace('.', '', $products_tax)));
          } else {
            $this->info['tax_groups']["$products_tax_description"] = $shown_price - ($shown_price / (($products_tax < 10) ? "1.0" . str_replace('.', '', $products_tax) : "1." . str_replace('.', '', $products_tax)));
          }
        } else {
          $this->info['tax'] += ($products_tax / 100) * $shown_price;
          if (isset($this->info['tax_groups']["$products_tax_description"])) {
            $this->info['tax_groups']["$products_tax_description"] += ($products_tax / 100) * $shown_price;
          } else {
            $this->info['tax_groups']["$products_tax_description"] = ($products_tax / 100) * $shown_price;
          }
        }

        $index++;
      }

      if (DISPLAY_PRICE_WITH_TAX == 'true') {
        $this->info['total'] = $this->info['subtotal'] + $this->info['shipping_cost'];
      } else {
        $this->info['total'] = $this->info['subtotal'] + $this->info['tax'] + $this->info['shipping_cost'];
      }
    }
  }
?>
