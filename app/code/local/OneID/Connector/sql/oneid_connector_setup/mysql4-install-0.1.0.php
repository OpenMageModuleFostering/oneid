<?php
$installer = $this;
$installer->startSetup();

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$setup->addAttribute('customer', OneID_Connector_Helper_Data::ONEID_UID_ATTR_KEY, array(
    'label' => 'OneID UID',
    'type' => 'text',
    'input' => 'text',
    'visible' => false,
    'required' => false,
    'default'  => ''
));

// Go get an API key and ID.
$ch = curl_init("https://keychain.oneid.com/register/");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
if ($post !== null) {
    curl_setopt($ch, CURLOPT_POST, 1);                                                                             
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);  
}
$data = curl_exec($ch);

$json = json_decode($data, true);

// Set the api key and api id.
Mage::getConfig()->saveConfig(OneID_Connector_Helper_Data::API_ID_PATH, $json["API_ID"]);
Mage::getConfig()->saveConfig(OneID_Connector_Helper_Data::API_KEY_PATH, $json["API_KEY"]);

$installer->endSetup();