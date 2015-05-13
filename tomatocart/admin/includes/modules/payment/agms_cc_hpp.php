<?php
/*
  $Id: agms_cc_hpp.php $
  Avant-Garde Marketing Solutions, Inc.
  http://www.agms.com

  Copyright (c) 2003 - 2015 Avant-Garde Marketing Solutions, Inc.

  This program is free software; you can redistribute it and/or modify
  it under the terms of the MIT License (MIT) as published by the
  Free Software Foundation.
*/

  /**
   * The administration side of the AGMS HPP payment module
   */

  class osC_Payment_agms_cc_hpp extends osC_Payment_Admin {

  /**
   * The administrative title of the payment module
   *
   * @var string
   * @access private
   */
  var $_title;

  /**
   * The code of the payment module
   *
   * @var string
   * @access private
   */

  var $_code = 'agms_cc_hpp';

  /**
   * The developers name
   *
   * @var string
   * @access private
   */

  var $_author_name = 'agms';

  /**
   * The developers address
   *
   * @var string
   * @access private
   */

  var $_author_www = 'http://onlinepaymentprocessing.com';

  /**
   * The status of the module
   *
   * @var boolean
   * @access private
   */

  var $_status = false;

  /**
   * Constructor
   */

  function osC_Payment_agms_cc_hpp() {
    global $osC_Language;

    $this->_title = $osC_Language->get('payment_agms_cc_hpp_title');
    $this->_description = $osC_Language->get('payment_agms_cc_hpp_description');
    $this->_method_title = $osC_Language->get('payment_agms_cc_hpp_method_title');
    $this->_status = (defined('MODULE_PAYMENT_AGMS_CC_HPP_STATUS') && (MODULE_PAYMENT_AGMS_CC_HPP_STATUS == '1') ? true : false);
    $this->_sort_order = (defined('MODULE_PAYMENT_AGMS_CC_HPP_SORT_ORDER') ? MODULE_PAYMENT_AGMS_CC_HPP_SORT_ORDER : null);
  }

  /**
   * Checks to see if the module has been installed
   *
   * @access public
   * @return boolean
   */

  function isInstalled() {
    return (bool)defined('MODULE_PAYMENT_AGMS_CC_HPP_STATUS');
  }

  /**
   * Installs the module
   *
   * @access public
   * @see osC_Payment_Admin::install()
   */

  function install() {
    global $osC_Database;

    parent::install();

    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Agms Credit Card HPP Payment Gateway', 'MODULE_PAYMENT_AGMS_CC_HPP_STATUS', '-1', 'Do you want to accept Agms Credit Card payments?', '6', '0', 'osc_cfg_set_boolean_value(array(1, -1))', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Username', 'MODULE_PAYMENT_AGMS_CC_HPP_USERNAME', '', 'The username for the Agms Payment Gateway service', '6', '0', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Password', 'MODULE_PAYMENT_AGMS_CC_HPP_PASSWORD', '', 'The password for the Agms Payment Gateway service', '6', '0', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Credit Cards', 'MODULE_PAYMENT_AGMS_CC_HPP_ACCEPTED_TYPES', '', 'Accept these credit card types for this payment method.', '6', '0', 'osc_cfg_set_credit_cards_checkbox_field', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Verify With CVC', 'MODULE_PAYMENT_AGMS_CC_HPP_VERIFY_WITH_CVC', '1', 'Verify the credit card with the billing address with the Credit Card Verification Checknumber (CVC)?', '6', '0', 'osc_cfg_use_get_boolean_value', 'osc_cfg_set_boolean_value(array(1, -1))', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Server', 'MODULE_PAYMENT_AGMS_CC_HPP_TRANSACTION_SERVER', 'Live', 'Perform transactions on the live server. ', '6', '0', 'osc_cfg_set_boolean_value(array(\'Live\'))', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Method', 'MODULE_PAYMENT_AGMS_CC_HPP_TRANSACTION_METHOD', 'Authorization', 'The processing method to use for each transaction.', '6', '0', 'osc_cfg_set_boolean_value(array(\'Capture\'))', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_AGMS_CC_HPP_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_AGMS_CC_HPP_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'osc_cfg_set_order_statuses_pull_down_menu', 'osc_cfg_use_get_order_status_title', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('cURL Program Location', 'MODULE_PAYMENT_AGMS_CC_HPP_CURL', '/usr/bin/curl', 'The location to the cURL program application', '6', '0', now())");
  }

  /**
   * Return the configuration parameter keys in an array
   *
   * @access public
   * @return array
   */

  function getKeys() {
    if (!isset($this->_keys)) {
      $this->_keys = array('MODULE_PAYMENT_AGMS_CC_HPP_STATUS',
                           'MODULE_PAYMENT_AGMS_CC_HPP_USERNAME',
                           'MODULE_PAYMENT_AGMS_CC_HPP_PASSWORD',
                           'MODULE_PAYMENT_AGMS_CC_HPP_ACCEPTED_TYPES',
                           'MODULE_PAYMENT_AGMS_CC_HPP_VERIFY_WITH_CVC',
                           'MODULE_PAYMENT_AGMS_CC_HPP_TRANSACTION_SERVER',
                           'MODULE_PAYMENT_AGMS_CC_HPP_TRANSACTION_METHOD',
                           'MODULE_PAYMENT_AGMS_CC_HPP_ORDER_STATUS_ID',
                           'MODULE_PAYMENT_AGMS_CC_HPP_SORT_ORDER',
                           'MODULE_PAYMENT_AGMS_CC_HPP_CURL');
    }

    return $this->_keys;
  }
}
?>
