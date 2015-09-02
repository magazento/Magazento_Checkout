<?php

class Ip_OneCheckout_Helper_Data extends Mage_Core_Helper_Abstract
{
   
    
    public function getConfig($value) {
        
        return trim(Mage::getStoreConfig('onecheckout/'.$value));
    }
    
    
    public function getSkinCss() {
        return Mage::getStoreConfig('onecheckout/design/skin');
    }
    public function getOneCheckoutLogin() {
        if($data=$this->getConfig('general/login')){
        return $data;
        }
        else{
        return 'I\'m a registered user';
        }
       
    }
}
   