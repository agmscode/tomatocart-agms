<?php
/*
  $Id: agms_cc.php $
  Avant-Garde Marketing Solutions, Inc.
  http://www.agms.com

  Copyright (c) 2003 - 2015 Avant-Garde Marketing Solutions, Inc.

  This program is free software; you can redistribute it and/or modify
  it under the terms of the MIT License (MIT) as published by the
  Free Software Foundation.
*/

/**
 * The administration side of the agms transaction payment module
 */

  class osC_Payment_agms_cc extends osC_Payment_Admin {

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

  var $_code = 'agms_cc';

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

  function osC_Payment_agms_cc() {
    global $osC_Language;

    $this->_title = $osC_Language->get('payment_agms_cc_title');
    $this->_description = $osC_Language->get('payment_agms_cc_description');
    $this->_method_title = $osC_Language->get('payment_agms_cc_method_title');
    $this->_status = (defined('MODULE_PAYMENT_AGMS_CC_STATUS') && (MODULE_PAYMENT_AGMS_CC_STATUS == '1') ? true : false);
    $this->_sort_order = (defined('MODULE_PAYMENT_AGMS_CC_SORT_ORDER') ? MODULE_PAYMENT_AGMS_CC_SORT_ORDER : null);
  }

/**
 * Checks to see if the module has been installed
 *
 * @access public
 * @return boolean
 */

  function isInstalled() {
    return (bool)defined('MODULE_PAYMENT_AGMS_CC_STATUS');
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

    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Agms Credit Card Payment Gateway', 'MODULE_PAYMENT_AGMS_CC_STATUS', '-1', 'Do you want to accept Agms Credit Card payments?', '6', '0', 'osc_cfg_set_boolean_value(array(1, -1))', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Username', 'MODULE_PAYMENT_AGMS_CC_USERNAME', '', 'The username for the Agms Payment Gateway service', '6', '0', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Password', 'MODULE_PAYMENT_AGMS_CC_PASSWORD', '', 'The password for the Agms Payment Gateway service', '6', '0', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Credit Cards', 'MODULE_PAYMENT_AGMS_CC_ACCEPTED_TYPES', '', 'Accept these credit card types for this payment method.', '6', '0', 'osc_cfg_set_credit_cards_checkbox_field', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Verify With CVC', 'MODULE_PAYMENT_AGMS_CC_VERIFY_WITH_CVC', '1', 'Verify the credit card with the billing address with the Credit Card Verification Checknumber (CVC)?', '6', '0', 'osc_cfg_use_get_boolean_value', 'osc_cfg_set_boolean_value(array(1, -1))', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Server', 'MODULE_PAYMENT_AGMS_CC_TRANSACTION_SERVER', 'Live', 'Perform transactions on the live or test server. The test server should only be used by developers with Authorize.net test accounts.', '6', '0', 'osc_cfg_set_boolean_value(array(\'Live\'))', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Method', 'MODULE_PAYMENT_AGMS_CC_TRANSACTION_METHOD', 'Authorization', 'The processing method to use for each transaction.', '6', '0', 'osc_cfg_set_boolean_value(array(\'Authorization\', \'Capture\'))', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_AGMS_CC_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_AGMS_CC_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'osc_cfg_set_order_statuses_pull_down_menu', 'osc_cfg_use_get_order_status_title', now())");
    $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('cURL Program Location', 'MODULE_PAYMENT_AGMS_CC_CURL', '/usr/bin/curl', 'The location to the cURL program application', '6', '0', now())");
  }

/**
 * Return the configuration parameter keys in an array
 *
 * @access public
 * @return array
 */

  function getKeys() {
    if (!isset($this->_keys)) {
      $this->_keys = array('MODULE_PAYMENT_AGMS_CC_STATUS',
                           'MODULE_PAYMENT_AGMS_CC_USERNAME',
                           'MODULE_PAYMENT_AGMS_CC_PASSWORD',
                           'MODULE_PAYMENT_AGMS_CC_ACCEPTED_TYPES',
                           'MODULE_PAYMENT_AGMS_CC_VERIFY_WITH_CVC',
                           'MODULE_PAYMENT_AGMS_CC_TRANSACTION_SERVER',
                           'MODULE_PAYMENT_AGMS_CC_TRANSACTION_METHOD',
                           'MODULE_PAYMENT_AGMS_CC_ORDER_STATUS_ID',
                           'MODULE_PAYMENT_AGMS_CC_SORT_ORDER',
                           'MODULE_PAYMENT_AGMS_CC_CURL');
    }

    return $this->_keys;
 }
}
?>