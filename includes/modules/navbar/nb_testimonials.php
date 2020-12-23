<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2020 osCommerce

  Released under the GNU General Public License
*/

  class nb_testimonials extends abstract_block_module {

    const CONFIG_KEY_BASE = 'MODULE_NAVBAR_TESTIMONIALS_';

    public $group = 'navbar_modules_left';

    function getOutput() {
      $tpl_data = ['group' => $this->group, 'file' => __FILE__];
      include 'includes/modules/block_template.php';
    }

    protected function get_parameters() {
      return [
        'MODULE_NAVBAR_TESTIMONIALS_STATUS' => [
          'title' => 'Enable Module',
          'value' => 'True',
          'desc' => 'Do you want to add the module to your Navbar?',
          'set_func' => "tep_cfg_select_option(['True', 'False'], ",
        ],
        'MODULE_NAVBAR_TESTIMONIALS_CONTENT_PLACEMENT' => [
          'title' => 'Content Placement Group',
          'value' => 'Left',
          'desc' => 'Where should the module be loaded?  Lowest is loaded first, per Group.',
          'set_func' => "tep_cfg_select_option(['Home', 'Left', 'Center', 'Right'], ",
        ],
        'MODULE_NAVBAR_TESTIMONIALS_SORT_ORDER' => [
          'title' => 'Sort Order',
          'value' => '535',
          'desc' => 'Sort order of display. Lowest is displayed first.',
        ],
      ];
    }

  }
