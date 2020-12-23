<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2014 osCommerce

  Released under the GNU General Public License
*/

  class OSCOM_PayPal_PS_Cfg_ewp_private_key {
    var $default = '';
    var $title;
    var $description;
    var $sort_order = 800;

    function __construct() {
      global $OSCOM_PayPal;

      $this->title = $OSCOM_PayPal->getDef('cfg_ps_ewp_private_key_title');
      $this->description = $OSCOM_PayPal->getDef('cfg_ps_ewp_private_key_desc');
    }

    function getSetField() {
      $input = tep_draw_input_field('ewp_private_key', OSCOM_APP_PAYPAL_PS_EWP_PRIVATE_KEY, 'id="inputPsEwpPrivateKey"');

      $result = <<<EOT
<h5>{$this->title}</h5>
<p>{$this->description}</p>

<div class="mb-3">{$input}</div>
EOT;

      return $result;
    }
  }
?>
