<?php

class OneID_Connector_ConnectorController extends Mage_Core_Controller_Front_Action {

    public function removeOneidAction(){
      $session = Mage::getSingleton('customer/session');
      $customer = $session->getCustomer();
      $customer->setData("oneid_uid", null);
      $customer->save();
      
      Mage::getSingleton('core/session')->addSuccess("Your accounts are no longer linked and we have removed this website’s information from your OneID.");
      
      $redirectUrl =  Mage::getUrl("customer/account");
      $this->_redirectUrl($redirectUrl);
      
    }
    
    public function authenticateFromSuccessAction($jsonInput=null) {
        self::authenticateAction($jsonInput);
        
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if ($customer->getId()){
          Mage::helper("OneID")->assignOrdersToCustomer($customer);
        }

    }
    

  
    public function authenticateAction($jsonInput = null) {
        
        // Read in our JSON post
        if ($jsonInput == null){
            // Check to see if data was POSTED.
            $postData = Mage::app()->getRequest()->getPost();
            if (count($postData) && isset($postData['json_data'])){
                $jsonInput = $postData['json_data'];
            }
            else {
                // Read from php input.
                $handle = fopen('php://input', 'r');
                $jsonInput = fgets($handle);
            }
        }
        Mage::log($jsonInput);
        $decoded = json_decode($jsonInput, true);

     
        $validateResponse = Mage::helper("OneID")->validateOneIDResponse($decoded); 
        if (!$validateResponse['isValid']) {
            Mage::log("Validating OneID Response returned this error :". $validateResponse['error']);
            Mage::app()->getResponse()->setBody(json_encode($validateResponse));
            return;
        }
        
        $session = Mage::getSingleton('customer/session');
        $uid = $decoded['uid'];
        
        try {
            // Is user logged into Magento?
            $customer = $session->getCustomer();
            if ($customer->getId()){  // If they're logged in               
                if ($customer->getOneidUid()) { // And they have a OneID linked here
                    if($customer->getOneidUid() != $uid){ // And it's not the UID that we're generating.                        
                        // Return an exception because that's not good.
                        $error_return = array("error" => "A different OneID is already linked to this account");                    
                        Mage::app()->getResponse()->setBody(json_encode($error_return));
                        return;
                    }
                }
                else{ // There is no OneId link, set one up.
                    $customer->setData("oneid_uid", $uid);
                    $customer->save();
                    Mage::getSingleton('core/session')->addSuccess("OneID is successfully linked!");
                }
            }
            else {
                $email = $decoded["attr"]['email']["email"];

                if (!$email){
                    $url = $this->_getLoginPostRedirect();
                    $msg = "Sorry, a verified email address supplied by OneID is required to login!";
                    $response = array("error" => $msg, "errorcode" => "-999");
                    Mage::app()->getResponse()->setBody(json_encode($response));
                    return;
                }


                
                // Has the user used OneID to login here before?
                $customer = Mage::helper("OneID")->getCustomerByOneIDUID($uid);
                
                if ($customer && $customer->getId()) { // User exists and is linked by OneID.
                    
                   if (!$customer->getId()) { // If customer does not exist, create one
                        $customer->setData('firstname', $decoded["attr"]["name"]['first_name']);
                        $customer->setData('lastname',  $decoded["attr"]["name"]['last_name']);
                        $customer->setData('email', $email);
                        $customer->setData("oneid_uid", $uid);
                        $customer->save();
                    }
                    $saveAcct=false;
                    if (!$customer->getFirstname()) {
                        $customer->setData('firstname', $decoded["attr"]["name"]['first_name']);
                        $saveAcct=true;
                    }
                    if (!$customer->getLastname()){
                        $customer->setData('lastname', $decoded["attr"]["name"]['last_name']);
                        $saveAcct=true;
                    }
                    if (!$customer->getOneidUid()){
                        $customer->setData("oneid_uid", $uid);
                        $saveAcct=true;
                    }
                    if ($saveAcct){
                        $customer->save();
                    } 
                }
                else {
                    // Try and load Magento customer by email provided by OneID.
                    $customer = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
                    $customer->loadByEmail($email);
                    
                    if ($customer && $customer->getId()){
                        // Now check if this customer exists in Magento.
                        // If customer exists, we need to verify that this OneID user is the customer
                        // by making them login to Magento the traditional way.
                        $session->setData("oneid_uid", $uid);

                        $url = Mage::getUrl("oneid/account/login",array('_secure'=>true));
                        $response = array("error" => 'success', "url" => $url);
                        Mage::app()->getResponse()->setBody(json_encode($response));
                        return; 
                    }
                    else{
                        // Create new account, with data provided by OneID.
                        
                        $customer->setData('firstname', $decoded["attr"]["name"]['first_name']);
                        $customer->setData('lastname', $decoded["attr"]["name"]['last_name']);
                        $customer->setData('email', $email);
                        $customer->setData("oneid_uid", $uid);
                        $customer->save();     
                        Mage::getSingleton('core/session')->addSuccess("Your new account has been linked to your OneID!");
                    }
                }
                   
            }
        } catch (Mage_Core_Exception $e) {
            Mage::log("exception " . $e);
            $url = $this->_getLoginPostRedirect();
            $response = array("error" => $e->getMessage(), "errorcode" => "-999");
            Mage::app()->getResponse()->setBody(json_encode($response));
            return;
        }
        
        // Log the customer in at Magento.
        if ($customer->getId()) {
            $session->setCustomerAsLoggedIn($customer);
        }
        
        // Redirect user to success page.
        $url = $this->_getLoginPostRedirect();
        $response = array("error" => 'success', "url" => $url);
        Mage::app()->getResponse()->setBody(json_encode($response));
    }
   /**
     * Retrieve customer session model object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession() {
      return Mage::getSingleton('customer/session');
    }
    
    
    /*
     * Borrowed from AccountController.php
     */
    protected function _getLoginPostRedirect()
    {
        $session = $this->_getSession();

        if (!$session->getBeforeAuthUrl() || $session->getBeforeAuthUrl() == Mage::getBaseUrl()) {

            // Set default URL to redirect customer to
            $session->setBeforeAuthUrl(Mage::getUrl("/"));
            // Redirect customer to the last page visited after logging in
            if ($session->isLoggedIn()) {
                if (!Mage::getStoreConfigFlag('customer/startup/redirect_dashboard')) {
                    $referer = $this->getRequest()->getParam(Mage_Customer_Helper_Data::REFERER_QUERY_PARAM_NAME);
                    if ($referer) {
                        $referer = Mage::helper('core')->urlDecode($referer);
                        if ($this->_isUrlInternal($referer)) {
                            $session->setBeforeAuthUrl($referer);
                        }
                    }
                } else if ($session->getAfterAuthUrl()) {
                    $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
                }
            } else {
                $session->setBeforeAuthUrl(Mage::helper('customer')->getLoginUrl());
            }
        } else if ($session->getBeforeAuthUrl() == Mage::helper('customer')->getLogoutUrl()) {
            $session->setBeforeAuthUrl(Mage::helper('customer')->getDashboardUrl());
        } else {
            if (!$session->getAfterAuthUrl()) {
                $session->setAfterAuthUrl($session->getBeforeAuthUrl());
            }
            if ($session->isLoggedIn()) {
                $session->setBeforeAuthUrl($session->getAfterAuthUrl(true));
            }
        }
        $url = $session->getBeforeAuthUrl(true);
        
        return $url;
    }
}

