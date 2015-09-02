<?php
class Ip_OneCheckout_Block_Checkout extends Mage_Checkout_Block_Onepage
{
    
    protected function _prepareLayout()
    {
        $skin=Mage::helper('onecheckout')->getSkinCss();
        $this->getLayout()->getBlock('head')->addCss('magazento_checkout/'.$skin);		
        return parent::_prepareLayout();
    }
    
}