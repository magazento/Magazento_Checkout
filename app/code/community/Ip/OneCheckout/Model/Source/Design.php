<?php

class Ip_OneCheckout_Model_Source_Design {

    public function toOptionArray() {
        return array(
            array('value' => 'blue_box.css', 'label' => Mage::helper('onecheckout')->__('Blue Box')),
            array('value' => 'default_white.css', 'label' => Mage::helper('onecheckout')->__('Clean White')),
        );
    }

}