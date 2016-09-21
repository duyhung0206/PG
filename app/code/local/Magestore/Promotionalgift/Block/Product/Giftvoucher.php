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
class Magestore_Promotionalgift_Block_Product_Giftvoucher extends Magestore_Promotionalgift_Block_Product_View
{
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('promotionalgift/product/giftvoucher.phtml');
        return $this;
    }

    public function getStartFormHtml()
    {
        return $this->getBlockHtml('product.info.giftvoucher');
    }

    public function getOptionsWrapperBottomHtml()
    {
        return $this->getBlockHtml('product.info.addtocart');
    }

    public function isCartPage()
    {
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        return $currentUrl;
    }
}