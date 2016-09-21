<?php
/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Promotionalgift
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */
class Magestore_Promotionalgift_Model_System_Config_Source_Showfreegift
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'cart', 'label' => Mage::helper('promotionalgift')->__('On Cart')),
            array('value' => 'onepage', 'label' => Mage::helper('promotionalgift')->__('On Checkout')),
            array('value' => 'both', 'label' => Mage::helper('promotionalgift')->__('On both Cart and Checkout')),
            array('value' => 'hide', 'label' => Mage::helper('promotionalgift')->__('Hide it')),
        );
    }
}