<?php

class OneID_Connector_Helper_Data extends Mage_Core_Helper_Abstract {
    
    const ONEID_UID_ATTR_KEY = "oneid_uid";
  
    const API_ENDPOINT_PATH = 'OneID/endpoint/host';
    const KEYCHAIN_ENDPOINT_PATH = 'OneID/endpoint/helper_host';
    
    const VERIFIED_EMAIL_REQUIRED_PATH = 'OneID/oneid_authentication/verified_email_required';
    
    const API_ID_PATH = 'OneID/endpoint/api_id';
    const API_KEY_PATH = 'OneID/endpoint/api_key';
    const AUTH_PURCHASE_AMOUNT_PATH = 'OneID/auth_purchase/auth_amount';
    const AUTH_PURCHASE_ENABLED='OneID/auth_purchase/enabled';
    
    const REFERRAL_CODE = "magerefcode"; // prob need to toss this into magento admin
    
    public function getButton($container_class="") {
        $button = '<div class="'.$container_class.' oneid-api-ready"><img class="oneidlogin" style="margin-left:6px; margin-top:-4px" 
            return_to="'. $this->_getUrl("/") .'" 
            ref="' . self::REFERRAL_CODE . '" 
            id="oneidlogin" 
            CHALJ=\'{"NONCE":"' . $this->getNonce() . '","ATTR":"'.$this->getLoginATTR().'", "CALLBACK":"'. $this->_getUrl("oneid/connector/authenticate",array('_secure'=>true)) . '" }\' 
            src="' . $this->getEndPoint() . '/images/oneid_signin.png" 
            onclick="OneId.login()" /></div>';
        return $button;
    }
    
    public function getLoginCHALJ(){
      return '{"attr":"'.$this->getLoginATTR().'", "callback":"'. $this->_getUrl("oneid/connector/authenticate",array('_secure'=>true)) . '" }' ;
    }
    
    public function getLinkCHALJ(){
      return '{"callback":"'. $this->_getUrl("oneid/connector/authenticate",array('_secure'=>true)) . '" }' ;
    }
    
    public function getCheckoutLoginCHALJ(){
      return '{"nonce":"' . $this->getNonce() . '","attr":"'.$this->getLoginATTR().'", "callback":"'. $this->_getUrl("oneid/connector/authenticate",array('_secure'=>true)) . '" }';
    }
    
    public function getAOneIdButton($email){
        $button = '<div><img class="oneidlogin" style="margin-left:6px; margin-top:-4px" 
            return_to="'. $this->_getUrl("/") .'" ref="' . self::REFERRAL_CODE . '"
            id="getAOneIdButton" CHALJ=\'{"NONCE":"' . $this->getNonce() . '","ATTR":"'.$this->getLoginATTR().'", "CALLBACK":"'. $this->_getUrl("oneid/connector/authenticate").'" }\' 
            src="' . $this->getEndPoint() . '/images/oneid_signin.png" 
            onclick="OneId.createOneId(\''.$email.'\', \''.$this->getReferralCode().'\')" /></div>';
        return $button;
    }
    
    public function getCreateOneIdAttrs() {
        $customer =   Mage::getSingleton('customer/session')->getCustomer();
        $data = array(
            "personal_info" => array(
              "first_name" => $this->_escapeQuote($customer->getFirstname()),
              "last_name"  => $this->_escapeQuote($customer->getLastname()),
              "email" => $this->_escapeQuote($customer->getEmail())
            )
        );
        
        
        $customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultBilling();
        if ($customerAddressId){
               $address = Mage::getModel('customer/address')->load($customerAddressId);
               $street = $address->getStreet();
               $data["address"] = array("billing" => array());
               $data["address"]["billing"]["street"] = $this->_escapeQuote( $street[0]);
               $data["address"]["billing"]["street2"] = count($street) == 2 ? $this->_escapeQuote($street[1]) : "";
               $data["address"]["billing"]["state"] = $address->getRegionId();
               $data["address"]["billing"]["city"] = $this->_escapeQuote($address->getCity());
               $data["address"]["billing"]["zip"] = $address->getPostcode();
               $data["address"]["billing"]["phone"] = $address->getTelephone();
        }
        
        $customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultShipping();
        if ($customerAddressId){
               $address = Mage::getModel('customer/address')->load($customerAddressId);
               
               if (!$data["address"]) {
                    $data["address"] = array();
               }
        
               $street = $address->getStreet();
               $data["address"]["shipping"] = array();
               $data["address"]["shipping"]["street"] = $this->_escapeQuote( $street[0]);
               $data["address"]["shipping"]["street2"] = count($street) == 2 ? $this->_escapeQuote($street[1]) : "";
               $data["address"]["shipping"]["state"] = $address->getRegionId();
               $data["address"]["shipping"]["city"] = $this->_escapeQuote($address->getCity());
               $data["address"]["shipping"]["zip"] = $address->getPostcode();
               $data["address"]["shipping"]["phone"] = $address->getTelephone();
        }
        
        $orders = Mage::getResourceModel('sales/order_collection')
            ->addAttributeToSelect('*')
            ->joinAttribute('shipping_firstname', 'order_address/firstname', 'shipping_address_id', null, 'left')
            ->joinAttribute('shipping_lastname', 'order_address/lastname', 'shipping_address_id', null, 'left')
            ->addAttributeToFilter('customer_id', Mage::getSingleton('customer/session')->getCustomer()->getId())
            ->addAttributeToFilter('state', array('in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()))
            ->addAttributeToSort('created_at', 'desc')
            ->setPageSize('1')
            ->load();
        
        if (count($orders)){
          foreach($orders as $order){
            // Payment info
            $payment = $order->getPayment();
            $payment_info = $payment->getMethodInstance()->getInfoInstance();
            $data["payment"] = array();
            $data["payment"]["cc_type"] = $payment_info->getCcType();
            $data["payment"]["cc_number"] = $payment_info->getCcNumber();
            $data["payment"]["cc_exp_mo"] = $payment_info->getCcExpMonth();
            $data["payment"]["cc_exp_yr"] = $payment_info->getCcExpYear();
            $data["payment"]["cc_name_on_card"] = $this->_escapeQuote($payment_info->getCcOwner());
          }
        }
        
        
        
        return json_encode($data);
    }
    
    
    public function getBasicCreatOneIdInfoFromCustomer() { 
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $data = array(
            "personal_info" => array(
              "first_name" => $this->_escapeQuote($customer->getFirstname()),
              "last_name"  => $this->_escapeQuote($customer->getLastname()),
              "email" => $this->_escapeQuote($customer->getEmail())
            )
        );
        
        return json_encode($data);
    }
    
    public function getCreateOneIdAttrsFromOrder($order) {
        $data = array(
            "personal_info" => array(
                "first_name" => $this->_escapeQuote($order->getCustomerFirstname()),
                "last_name" => $this->_escapeQuote($order->getCustomerLastname()),
                "email" => $this->_escapeQuote($order->getCustomerEmail())
            )
        );
      
      $address = $order->getBillingAddress();
      
      $data["address"] = array("billing" => array());
      
      $street = $address->getStreet();
      $data["address"]["billing"]["street"] = $this->_escapeQuote( $street[0]);
      $data["address"]["billing"]["street2"] = count($street) == 2 ?  $this->_escapeQuote($street[1]) : "";
      $data["address"]["billing"]["state"] = $address->getRegionId();
      $data["address"]["billing"]["city"] = $this->_escapeQuote($address->getCity());
      $data["address"]["billing"]["zip"] = $address->getPostcode();
      $data["address"]["billing"]["phone"] = $address->getTelephone();
      $data["personal_info"]["primary_phone"] = $address->getTelephone();
      
      $address = $order->getShippingAddress();    
      if ($address) {
        if (!$data["address"]){
            $data["address"] = array();
        }
        $data["address"]["shipping"] = array();

          
        $street = $address->getStreet();
        $data["address"]["shipping"]["street"] = $this->_escapeQuote( $street[0]);
        $data["address"]["shipping"]["street2"] = count($street) == 2 ?  $this->_escapeQuote($street[1]) : "";
        $data["address"]["shipping"]["state"] = $address->getRegionId();
        $data["address"]["shipping"]["city"] = $this->_escapeQuote($address->getCity());
        $data["address"]["shipping"]["zip"] = $address->getPostcode();
        $data["address"]["shipping"]["phone"] = $address->getTelephone();
      }
      
      
      // Payment info
      $data["payment"] = array();
      $payment = $order->getPayment();
      $payment_info = $payment->getMethodInstance()->getInfoInstance();
      $data["payment"]["cc_type"] = $payment_info->getCcType();
      $data["payment"]["cc_number"] = $payment_info->getCcNumber();
      $data["payment"]["cc_exp_mo"] = $payment_info->getCcExpMonth();
      $data["payment"]["cc_exp_yr"] = $payment_info->getCcExpYear();
      $data["payment"]["cc_name_on_card"] = $this->_escapeQuote($payment_info->getCcOwner());
      
      
      return json_encode($data);
      
    }   
    
    private function _escapeQuote($str){
      return htmlspecialchars( $str, ENT_QUOTES );
    }
    
    public function getCustomerByOneIDUID($uid) {
      $customer = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
      $collection = $customer->getCollection()
                ->addFieldToFilter('oneid_uid',$uid);
      
      foreach ($collection as $customer){
        return $customer;
      }
      
      return null;
    }
    
    public function assignOrdersToCustomer($customer) {
      $orders_collection = Mage::getModel('sales/order')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->getCollection()
                ->addFieldToFilter('customer_email',$customer->getEmail() )
                ->addFieldToFilter('customer_id', array('null' => true));
      
      foreach ($orders_collection as $order) {
        $order->setCustomer($customer);
        $order->save();
      }
      return;
    }
    
    public function getLoginATTR(){
        return "personal_info[email] personal_info[first_name] personal_info[last_name]";
    }
    
    private function getReferralCode(){
        return self::REFERRAL_CODE;
    }
    
    public function getLoginSelect() {
        $loginSelect = '<div><select class="oneidlogin_select" style="float:right" ></select></div>';
        $loginSelect .= '<script type="text/javascript"> oneIdExtern.registerApiReadyFunction ( function() {OneId.fillLoginDropDown();}); </script>';
        return $loginSelect;
    }

    public function getCheckoutLoginButton() {
        $button = '<div><img class="oneidlogin" style="margin-left:6px; margin-top:-4px" 
            return_to="'. $this->_getUrl("/checkout/onepage/") .'" 
            ref="' . self::REFERRAL_CODE . '" 
            id="oneidlogin" 
            CHALJ=\'{"NONCE":"' . $this->getNonce() . '","ATTR":"'.$this->getLoginATTR().'", "CALLBACK":"'. $this->_getUrl("oneid/connector/authenticate", array('_secure'=>true)) . '" }\' 
            src="' . $this->getEndPoint() . '/images/oneid_signin.png" 
            onclick="OneId.login()"></div>';
        return $button;
    }

    public function getCheckoutCombo() {
        return "";
        if (Mage::isInstalled() && Mage::getSingleton('customer/session')->isLoggedIn()) {
            return "";
        } else { 
            return '<img class="oneidlogin" style="margin-left:6px; margin-top:-4px" id="oneidlogin" CHALJ=\'{"NONCE":"' . $this->getNonce() . '","ATTR":"'.$this->getLoginATTR().'"}\' callback="'. $this->_getUrl("oneid/connector/authenticate",array('_secure'=>true)) . '" src="' . $this->getEndPoint() . '/images/1id_SignIn_flat-190.png" onclick="OneId.login()">';
        }
    }
    public function getPromo() {
        return "";
//       return '<img class="oneidlogin" style="margin-left:6px; margin-top:-4px" id="oneidlogin"  src="' . $this->getEndPoint() . '/images/1id_Register-180.png" onclick="OneId.login()">';
        
    }
    public function getAccuFillButtonSrc() {
      return $this->getEndPoint() . '/images/oneid_accufill.png';
    }
    
    public function getCheckoutAttr(){
        return "personal_info[email] personal_info[first_name] personal_info[last_name] address.billing[street] address.billing[street2] address.billing[city] address.billing[state] address.billing[zip] address.billing[phone] payment[cc_type] payment[cc_number] payment[cc_exp_mo] payment[cc_exp_yr] payment[cc_verify] payment[cc_name_on_card]";
    }
    
    public function getChalj($method=null, $quote=null) {
        if (!$method) {
            $method=$this->getLoginATTR();
        }
        else if ($method == "secureTransaction"){
            if ($quote && $this->getAuthRequired() && $this->getAuthAmount() < $quote->getGrandTotal()) {
                 return '{"nonce":"' . $this->getNonce() . '", "auth_level" : "OOBPIN"}'; 
            } else {
                 return '{"nonce":"' . $this->getNonce() . '", "auth_level" : "ON"}'; 
            }
        }
        
        return '{"nonce":"' . $this->getNonce() . '"}'; 
    }
    
    public function getCheckoutChalj(){
        return '{"NONCE":"' . $this->getNonce() . '"}';
    }
    
    public function getEndPoint() {
        return (string)Mage::getStoreConfig(self::API_ENDPOINT_PATH);
    }
    
    public function getKeychainHost(){
      return (string)Mage::getStoreConfig(self::KEYCHAIN_ENDPOINT_PATH);
    }
    
    public function getVerifiedEmailRequired(){
      return (string)Mage::getStoreConfig(self::VERIFIED_EMAIL_REQUIRED_PATH);
    }
    
    public function getApiId() {
        return (string)Mage::getStoreConfig(self::API_ID_PATH);
    }
    
    public function getApiKey() {
        return (string)Mage::getStoreConfig(self::API_KEY_PATH);
    }
    
    public function getAuthAmount() {
        return Mage::getStoreConfig(self::AUTH_PURCHASE_AMOUNT_PATH);
    }
    
    public function getAuthRequired() {
        return Mage::getStoreConfig(self::AUTH_PURCHASE_ENABLED);
    }
    
    public function getPromptUserOnDashboard() {
        return Mage::getStoreConfig(self::PROMPT_CREATE_ONEID_ON_DASHBOARD); 
    }
    
    public function getAuthPurchaseMessage($quote) {
            return sprintf("Approve the purchase of %s ?", Mage::helper("core")->formatCurrency($quote->getGrandTotal(), false));
    }
    
    public function validateAuthPurchaseResponse($data, $quote) {
        return true; // isset($data) && $data->auth_msg_md5 == md5($this->getAuthPurchaseMessage($quote));
    }
    
    public function isQuoteAuthRequired($quote) {
        $enabled = $this->getAuthRequired();
        if (!$enabled) {
            return false;
        }
        
        $quote_amount = $quote->getGrandTotal();
        $min_auth = $this->getAuthAmount();
        
        return $quote_amount >= $min_auth;
    }
    
    public function getNonce() {
        $data = $this->_callOneIdKeychain("make_nonce");
        return $data["nonce"];
    } 
    
    public function validateOneIDResponse($responseData) {
        $validateData = array();
        $validateData["uid"] = $responseData['uid'];
        
        if ( $this->getVerifiedEmailRequired() ) {
          $validateData["attr_claim_tokens"] = $responseData['attr_claim_tokens'] ? $responseData['attr_claim_tokens'] : array();
        }
        
        if ($responseData['nonces']) {
            $validateData["nonces"] = $responseData['nonces'];
        }
        
        $response =  $this->_callOneIdKeychain("validate", $validateData);
        
        if (is_null($response)) {
            return array("isValid" => 0,
                         "errorcode" => 500,
                         "error" => "OneID Validate service is down.");
        }
                
        $isValid = $response['errorcode'] == 0;
        
        foreach ($response['attr_claims'] as $attr => $claimValid) {
          $isValid = $isValid && $claimValid;
          
          if (!$claimValid) {
            $response["error"].= " ";
            $response["error"].= "Claim for attribute " . $attr ." didn't verify";
          }
        }
        $response["isValid"] = $isValid;
        
        return $response;
        
    }
    
    private function _callOneIdKeychain($method, $data=null){
        $oneid_server = $this->getKeychainHost();
        $oneid_api_id = $this->getApiId();
        $oneid_api_key = $this->getApiKey();
        
        $scope = "/keychain";
        //$scope = "";
        $ch = curl_init($oneid_server . $scope. "/" . $method);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $oneid_api_id . ":" . $oneid_api_key);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        $json = curl_exec($ch);
        curl_close($ch);
        return json_decode($json, true);
    }
}
