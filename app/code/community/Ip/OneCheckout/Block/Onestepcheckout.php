<?php
class Ip_OneCheckout_Block_Onestepcheckout extends Mage_Core_Block_Template
{
    
     public function getOneCheckout()     
     { 
        if (!$this->hasData('onecheckout')) {
            $this->setData('onecheckout', Mage::registry('onecheckout'));
        }
        return $this->getData('onecheckout');
        
    }
}