<?php

class Ip_OneCheckout_Model_Source_Checkbox extends Varien_Object
{
    const STATUS_ENABLED	= 1;
    const STATUS_DISABLED	= 0;
    const STATUS_ENABLED_CHECKED	= 2;

    static public function toOptionArray()
    {
        return array(
            self::STATUS_ENABLED            => Mage::helper('onecheckout')->__('Show (Allow registration)'),
            self::STATUS_DISABLED           => Mage::helper('onecheckout')->__('Hide (Only guest orders)'),
        );
    }
}