<?php
require_once("Mage/Checkout/controllers/OnepageController.php");

class OneID_Connector_OnepageController extends Mage_Checkout_OnepageController
{
        /**
     * save checkout billing address
     */
    public function saveBillingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
//            $postData = $this->getRequest()->getPost('billing', array());
//            $data = $this->_filterPostData($postData);
            $data = $this->getRequest()->getPost('billing', array());
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

            if (isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }
            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

            if (!isset($result['error'])) {
                /* check quote for virtual */
                if ($this->getOnepage()->getQuote()->isVirtual()) {
                    $result['goto_section'] = 'payment';
                    //ONEID change
                    // Don't return updated payment section
                    
                } elseif (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                    $result['goto_section'] = 'shipping_method';
                    $result['update_section'] = array(
                        'name' => 'shipping-method',
                        'html' => $this->_getShippingMethodsHtml()
                    );

                    $result['allow_sections'] = array('shipping');
                    $result['duplicateBillingInfo'] = 'true';
                } else {
                    $result['goto_section'] = 'shipping';
                }
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }
    
        /**
     * Create order action
     */
    public function saveOrderAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        
        if (Mage::helper("OneID")->isQuoteAuthRequired($this->getOnepage()->getQuote())) {
            $data = $this->getRequest()->getPost('oneIDPurchaseAuthData', false);            
            
            $data = json_decode($data, true);
            $validateResponse = Mage::helper("OneID")->validateOneIDResponse($data); 
            if (!$validateResponse['isValid']) {
                    Mage::log("Validating OneID Response returned this error :" . $validateResponse['error']);
                    $this->getResponse()->setBody( json_encode($validateResponse) );
                    return;
            }
            
            
            if (!Mage::helper("OneID")->validateAuthPurchaseResponse($data, $this->getOnepage()->getQuote()) ) {
                $result['success'] = false;
                $result['error'] = true;
                $result['error_messages'] = "Required authorization from OneID.";
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return; 
            }
            
        }

        return parent::saveOrderAction();
    }
}