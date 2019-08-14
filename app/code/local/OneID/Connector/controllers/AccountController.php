<?php

require_once 'Mage/Customer/controllers/AccountController.php';

class OneID_Connector_AccountController extends Mage_Customer_AccountController {
    
    public function loginAction() {
        parent::loginAction();
    }
    
    public function loginPostAction(){
        parent::loginPostAction();
        $session = Mage::getSingleton('customer/session');
        
        if ($session->getCustomer()->getId()){
            if ($session->getData("oneid_uid")){
                $customer = $session->getCustomer();
                $customer->setData("oneid_uid", $session->getData("oneid_uid"));
                $session->unsetData("oneid_uid");
                $customer->save();
            }
        }
        else{
            // Login failed, redirect
            $this->_redirect("oneid/account/login",array('_secure'=>true));
        }
        
    }
}