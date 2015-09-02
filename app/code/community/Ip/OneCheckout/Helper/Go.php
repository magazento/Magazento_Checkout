<?php

class Ip_OneCheckout_Helper_Go extends Mage_Core_Helper_Abstract
{

    
    public function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }


    public function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }
    
    
}