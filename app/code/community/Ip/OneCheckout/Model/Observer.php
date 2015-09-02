<?php

class Ip_OneCheckout_Model_Observer{
    public function OneCheckoutRedirect($observer) {
        if (Mage::helper('onecheckout')->getConfig('general/active')) {
	          Mage::app()->getResponse()->setRedirect(Mage::getUrl("checkout/go"));
        }
    }	
}