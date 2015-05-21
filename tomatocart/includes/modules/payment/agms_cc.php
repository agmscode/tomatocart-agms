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

  class osC_Payment_agms_cc extends osC_Payment {
    var $_title,
        $_code = 'agms_cc',
        $_status = false,
        $_sort_order,
        $_order_status;

    // class constructor
    function osC_Payment_agms_cc() {
      global $osC_Database, $osC_Language, $osC_ShoppingCart;

      $this->_title = $osC_Language->get('payment_agms_cc_title');
      $this->_method_title = $osC_Language->get('payment_agms_cc_method_title');
      $this->_sort_order = MODULE_PAYMENT_AGMS_CC_SORT_ORDER;
      $this->_status = ((MODULE_PAYMENT_AGMS_CC_STATUS == '1') ? true : false);

      $this->_order_status = (int)MODULE_PAYMENT_AGMS_CC_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_AGMS_CC_ORDER_STATUS_ID : (int)ORDERS_STATUS_PAID;

      if ($this->_status === true) {
        if ((int)MODULE_PAYMENT_AGMS_CC_ZONE > 0) {
          $check_flag = false;

          $Qcheck = $osC_Database->query('select zone_id from :table_zones_to_geo_zones where geo_zone_id = :geo_zone_id and zone_country_id = :zone_country_id order by zone_id');
          $Qcheck->bindTable(':table_zones_to_geo_zones', TABLE_ZONES_TO_GEO_ZONES);
          $Qcheck->bindInt(':geo_zone_id', MODULE_PAYMENT_AGMS_CC_ZONE);
          $Qcheck->bindInt(':zone_country_id', $osC_ShoppingCart->getBillingAddress('country_id'));
          $Qcheck->execute();

          while ($Qcheck->next()) {
            if ($Qcheck->valueInt('zone_id') < 1) {
              $check_flag = true;
              break;
            } elseif ($Qcheck->valueInt('zone_id') == $osC_ShoppingCart->getBillingAddress('zone_id')) {
              $check_flag = true;
              break;
            }
          }

          if ($check_flag == false) {
            $this->_status = false;
          }
        }
      }
    }

    function getJavascriptBlock() {
      global $osC_Language, $osC_CreditCard;

      $osC_CreditCard = new osC_CreditCard();

      $js = '  if (payment_value == "' . $this->_code . '") {' . "\n" .
            '    var agms_cc_owner = document.checkout_payment.agms_cc_owner.value;' . "\n" .
            '    var agms_cc_number = document.checkout_payment.agms_cc_number.value;' . "\n" .
            '    agms_cc_number = agms_cc_number.replace(/[^\d]/gi, "");' . "\n";

      if (MODULE_PAYMENT_AGMS_CC_VERIFY_WITH_CVC == '1') {
        $js .= '    var agms_cc_cvc = document.checkout_payment.agms_cc_cvc.value;' . "\n";
      }

      if (CFG_CREDIT_CARDS_VERIFY_WITH_JS == '1') {
        $js .= '    var agms_cc_type_match = false;' . "\n";
      }

      $js .= '    if (agms_cc_owner == "" || agms_cc_owner.length < ' . CC_OWNER_MIN_LENGTH . ') {' . "\n" .
             '      error_message = error_message + "' . sprintf($osC_Language->get('payment_agms_cc_js_credit_card_owner'), CC_OWNER_MIN_LENGTH) . '\n";' . "\n" .
             '      error = 1;' . "\n" .
             '    }' . "\n";

      $has_type_patterns = false;

      if ( (CFG_CREDIT_CARDS_VERIFY_WITH_JS == '1') && (osc_empty(MODULE_PAYMENT_AGMS_CC_ACCEPTED_TYPES) === false) ) {
        foreach (explode(',', MODULE_PAYMENT_AGMS_CC_ACCEPTED_TYPES) as $type_id) {
          if ($osC_CreditCard->typeExists($type_id)) {
            $has_type_patterns = true;

            $js .= '    if ( (agms_cc_type_match == false) && (agms_cc_number.match(' . $osC_CreditCard->getTypePattern($type_id) . ') != null) ) { ' . "\n" .
                   '      agms_cc_type_match = true;' . "\n" .
                   '    }' . "\n";
          }
        }
      }

      if ($has_type_patterns === true) {
        $js .= '    if ((agms_cc_type_match == false) || (mod10(agms_cc_number) == false)) {' . "\n" .
               '      error_message = error_message + "' . $osC_Language->get('payment_agms_cc_js_credit_card_not_accepted') . '\n";' . "\n" .
               '      error = 1;' . "\n" .
               '    }' . "\n";
      } else {
        $js .= '    if (agms_cc_number == "" || agms_cc_number.length < ' . CC_NUMBER_MIN_LENGTH . ') {' . "\n" .
               '      error_message = error_message + "' . sprintf($osC_Language->get('payment_agms_cc_js_credit_card_number'), CC_NUMBER_MIN_LENGTH) . '\n";' . "\n" .
               '      error = 1;' . "\n" .
               '    }' . "\n";
      }

      if (MODULE_PAYMENT_AGMS_CC_VERIFY_WITH_CVC == '1') {
        $js .= '    if (agms_cc_cvc == "" || agms_cc_cvc.length < 3) {' . "\n" .
               '      error_message = error_message + "' . sprintf($osC_Language->get('payment_agms_cc_js_credit_card_cvc'), 3) . '\n";' . "\n" .
               '      error = 1;' . "\n" .
               '    }' . "\n";
      }

      $js .= '  }' . "\n";

      return $js;
    }

    function selection() {
      global $osC_Database, $osC_Language, $osC_ShoppingCart;

      for ($i=1; $i<13; $i++) {
        $expires_month[] = array('id' => sprintf('%02d', $i), 'text' => strftime('%B',mktime(0,0,0,$i,1)));
      }

      $year = date('Y');
      for ($i=$year; $i < $year+10; $i++) {
        $expires_year[] = array('id' => $i, 'text' => strftime('%Y',mktime(0,0,0,1,1,$i)));
      }

      $selection = array('id' => $this->_code,
                         'module' => $this->_method_title,
                         'fields' => array(array('title' => $osC_Language->get('payment_agms_cc_credit_card_owner'),
                                                 'field' => osc_draw_input_field('agms_cc_owner', $osC_ShoppingCart->getBillingAddress('firstname') . ' ' . $osC_ShoppingCart->getBillingAddress('lastname'), 'style="margin:5px 0;"')),
                                           array('title' => $osC_Language->get('payment_agms_cc_credit_card_number'),
                                                 'field' => osc_draw_input_field('agms_cc_number'), '', 'style="margin:5px 0;"'),
                                           array('title' => $osC_Language->get('payment_agms_cc_credit_card_expires'),
                                                 'field' => osc_draw_pull_down_menu('agms_cc_expires_month', $expires_month, null, 'style="margin:5px 0;"') . '&nbsp;' . osc_draw_pull_down_menu('agms_cc_expires_year', $expires_year, null, 'style="margin:5px 0;"'))));

     if (MODULE_PAYMENT_AGMS_CC_VERIFY_WITH_CVC == '1') {
       $selection['fields'][] = array('title' => $osC_Language->get('payment_agms_cc_credit_card_cvc'),
                                      'field' => osc_draw_input_field('agms_cc_cvc', null, 'size="5" maxlength="4" style="margin:5px 0;"'));
     }

      return $selection;
    }


    function pre_confirmation_check() {
      $this->_verifyData();
    }

    function confirmation() {
      global $osC_Language, $osC_CreditCard;

      $confirmation = array('title' => $this->_method_title,
                            'fields' => array(array('title' => $osC_Language->get('payment_agms_cc_credit_card_owner'),
                                                    'field' => $osC_CreditCard->getOwner()),
                                              array('title' => $osC_Language->get('payment_agms_cc_credit_card_number'),
                                                    'field' => $osC_CreditCard->getSafeNumber()),
                                              array('title' => $osC_Language->get('payment_agms_cc_credit_card_expires'),
                                                    'field' => $osC_CreditCard->getExpiryMonth() . ' / ' . $osC_CreditCard->getExpiryYear())));

      if (MODULE_PAYMENT_AGMS_CC_VERIFY_WITH_CVC == '1') {
        $confirmation['fields'][] = array('title' => $osC_Language->get('payment_agms_cc_credit_card_cvc'),
                                          'field' => $osC_CreditCard->getCVC());
      }

      return $confirmation;
    }

    function process_button() {
      global $osC_CreditCard;

      $fields = osc_draw_hidden_field('agms_cc_owner', $osC_CreditCard->getOwner()) .
                osc_draw_hidden_field('agms_cc_expires_month', $osC_CreditCard->getExpiryMonth()) .
                osc_draw_hidden_field('agms_cc_expires_year', $osC_CreditCard->getExpiryYear()) .
                osc_draw_hidden_field('agms_cc_number', $osC_CreditCard->getNumber());

      if (MODULE_PAYMENT_AGMS_CC_VERIFY_WITH_CVC == '1') {
        $fields .= osc_draw_hidden_field('agms_cc_cvc', $osC_CreditCard->getCVC());
      }

      return $fields;
    }

    function process() {
      global $osC_Currencies, $osC_ShoppingCart, $messageStack, $osC_Customer, $osC_Tax, $osC_CreditCard;

      $this->_verifyData();

      $orders_id = osC_Order::insert();

      $params = array('GatewayUserName' => substr(MODULE_PAYMENT_AGMS_CC_USERNAME, 0, 20),
                      'GatewayPassword' => substr(MODULE_PAYMENT_AGMS_CC_PASSWORD, 0, 16),
                      'TransactionType' => 'sale',
                      'PaymentType' => 'creditcard',
                      'FirstName' => substr($osC_ShoppingCart->getBillingAddress('firstname'), 0, 50),
                      'LastName' => substr($osC_ShoppingCart->getBillingAddress('lastname'), 0, 50),
                      'Company' => substr($osC_ShoppingCart->getBillingAddress('company'), 0, 50),
                      'Address' => substr($osC_ShoppingCart->getBillingAddress('street_address'), 0, 60),
                      'City' => substr($osC_ShoppingCart->getBillingAddress('city'), 0, 40),
                      'State' => substr($osC_ShoppingCart->getBillingAddress('state'), 0, 40),
                      'Zip' => substr($osC_ShoppingCart->getBillingAddress('postcode'), 0, 20),
                      'Country' => substr($osC_ShoppingCart->getBillingAddress('country_iso_code_2'), 0, 60),
                      'PONumber' => substr($osC_Customer->getID(), 0, 20),
                      'IPAddress' => osc_get_ip_address(),
                      'OrderId' => $order_id,
                      'Email' => substr($osC_Customer->getEmailAddress(), 0, 255),
                      'OrderDescription' => substr(STORE_NAME, 0, 255),
                      'Amount' => substr($osC_Currencies->formatRaw($osC_ShoppingCart->getTotal()), 0, 15),
                      'CCNumber' => $osC_CreditCard->getNumber(),
                      'CCExpDate' => $osC_CreditCard->getExpiryMonth() . substr((string)$osC_CreditCard->getExpiryYear(), -2));
      
      if (ACCOUNT_TELEPHONE > -1) {
        $params['Phone'] = $osC_ShoppingCart->getBillingAddress('telephone_number');
      }

      if (MODULE_PAYMENT_AGMS_CC_VERIFY_WITH_CVC == '1') {
        $params['CVV'] = $osC_CreditCard->getCVC();
      }

      if ($osC_ShoppingCart->hasShippingAddress()) {
        $params['ShippingFirstLame'] = substr($osC_ShoppingCart->getShippingAddress('firstname'), 0, 50);
        $params['ShippingLastName'] = substr($osC_ShoppingCart->getShippingAddress('lastname'), 0, 50);
        $params['ShippingCompany'] = substr($osC_ShoppingCart->getShippingAddress('company'), 0, 50);
        $params['ShippingAddress'] = substr($osC_ShoppingCart->getShippingAddress('street_address'), 0, 60);
        $params['ShippingCity'] = substr($osC_ShoppingCart->getShippingAddress('city'), 0, 40);
        $params['ShippingState'] = substr($osC_ShoppingCart->getShippingAddress('zone_code'), 0, 40);
        $params['ShippingZip'] = substr($osC_ShoppingCart->getShippingAddress('postcode'), 0, 20);
        $params['ShippingCountry'] = substr($osC_ShoppingCart->getShippingAddress('country_iso_code_2'), 0, 60);
      }

      $shipping_tax = ($osC_ShoppingCart->getShippingMethod('cost')) * ($osC_Tax->getTaxRate($osC_ShoppingCart->getShippingMethod('tax_class_id'), $osC_ShoppingCart->getTaxingAddress('country_id'), $osC_ShoppingCart->getTaxingAddress('zone_id')) / 100);
      $total_tax = $osC_ShoppingCart->getTax() - $shipping_tax;

      if ($total_tax > 0) {
        $params['Tax'] = $osC_Currencies->formatRaw($total_tax);
      }


      $post_string =  $this->_buildRequestBody($params, "ProcessTransaction");
      $headers = $this->_buildRequestHeader("ProcessTransaction");

      switch (MODULE_PAYMENT_AGMS_CC_TRANSACTION_SERVER) {
        default:
          $gateway_url = 'https://gateway.agms.com/roxapi/agms.asmx';
          break;
      }

      $response = $this->sendTransactionToGateway($gateway_url, $post_string, $headers);

      $error = false;

      if (!empty($response)) {
          $transaction_response = $this->_parseResponse($response, "ProcessTransaction");
      } else {
        $error = "Unable to Process Transaction";
      }

      if($transaction_response['STATUS_CODE'] == 1){
        $error = false;
      }
      else {
        $error = $transaction_response['STATUS_MSG'];
      }

      if ($error != false) {
        osC_Order::remove($orders_id);

        osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'checkout&error=' . $error, 'SSL'));
      }else {
        osC_Order::process($orders_id, $this->_order_status, $transaction_response);
      }
    }

    function get_error() {
      global $osC_Language;

      $error = false;

      if (isset($_GET['error'])) {
        switch ($_GET['error']) {
          case 'invalid_expiration_date':
            $error_message = $osC_Language->get('payment_agms_cc_error_invalid_exp_date');
            break;

          case 'expired':
            $error_message = $osC_Language->get('payment_agms_cc_error_expired');
            break;

          case 'declined':
            $error_message = $osC_Language->get('payment_agms_cc_error_declined');
            break;

          case 'cvc':
            $error_message = $osC_Language->get('payment_agms_cc_error_cvc');
            break;

          default:
            $error_message = $osC_Language->get('payment_agms_cc_error_general');
            break;
        }

        $error = array('title' => $osC_Language->get('payment_agms_cc_error_title'),
                       'error' => $error_message);
      }

      return $error;
    }

    function _verifyData() {
      global $osC_Language, $messageStack, $osC_CreditCard;

      $osC_CreditCard = new osC_CreditCard($_POST['agms_cc_number'], $_POST['agms_cc_expires_month'], $_POST['agms_cc_expires_year']);
      $osC_CreditCard->setOwner($_POST['agms_cc_owner']);

      if (MODULE_PAYMENT_AGMS_CC_VERIFY_WITH_CVC == '1') {
        $osC_CreditCard->setCVC($_POST['agms_cc_cvc']);
      }

      if (($result = $osC_CreditCard->isValid(MODULE_PAYMENT_AGMS_CC_ACCEPTED_TYPES)) !== true) {
        $error = '';

        switch ($result) {
          case -2:
            $error = $osC_Language->get('payment_agms_cc_error_invalid_exp_date');
            break;

          case -3:
            $error = $osC_Language->get('payment_agms_cc_error_expired');
            break;

          case -5:
            $error = $osC_Language->get('payment_agms_cc_error_not_accepted');
            break;

          default:
            $error = $osC_Language->get('payment_agms_cc_error_general');
            break;
        }

        if ($messageStack->size('checkout_payment') > 0) {
          $messageStack->reset();
        }

        $messageStack->add_session('checkout_payment', $error, 'error');
      }
    }

    /**
    * Convert array to xml string
    * @param $request, $op
    * @return string
    */
    function _buildRequestBody($request, $op='ProcessTransaction')
    {
      /*
       * Resolve object parameters
       */
      switch ($op) {
          case 'ProcessTransaction':
              $param = 'objparameters';
              break;
          case 'ReturnHostedPaymentSetup':
              $param = 'objparameters';
              break;
      }
      $xmlHeader = '<?xml version="1.0" encoding="utf-8"?>
    <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xmlns:xsd="http://www.w3.org/2001/XMLSchema"
       xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
    <' . $op . ' xmlns="https://gateway.agms.com/roxapi/">
    <' . $param . '>';
      $xmlFooter = '</' . $param . '>
    </' . $op . '>
    </soap:Body>
    </soap:Envelope>';
      $xmlBody = '';
      foreach ($request as $key => $value) {
          $xmlBody = $xmlBody . "<$key>$value</$key>";
      }
      $payload = $xmlHeader . $xmlBody . $xmlFooter;
      return $payload;
    }

    /**
    * Builds header for the Request
    * @param $op
    * @return array
    */
    function _buildRequestHeader($op='ProcessTransaction')
    {
      return array(
          'Accept: application/xml',
          'Content-Type: text/xml; charset=utf-8',
          'SOAPAction: https://gateway.agms.com/roxapi/' . $op,
          'User-Agent: AGMS Tomatocart Pluggin',
          'X-ApiVersion: 3'
      );
    }

    /**
    * Parse response from Agms Gateway
    * @param $response, $op
    * @return array
    */
    function _parseResponse($response, $op)
    {
      $xml = new SimpleXMLElement($response);
      $xml = $xml->xpath('/soap:Envelope/soap:Body');
      $xml = $xml[0];
      $data = json_decode(json_encode($xml));
      $opResponse = $op . 'Response';
      $opResult = $op . 'Result';
      $arr = $this->_object2array($data->$opResponse->$opResult);
      return $arr;
    }


    /**
    * Convert object to array
    * @param $data
    * @return array
    */
    function _object2array($data)
    {
      if (is_array($data) || is_object($data)) {
          $result = array();
          foreach ($data as $key => $value) {
              $result[$key] = $this->_object2array($value);
          }
          return $result;
      }
      return $data;
    }
  }
?>