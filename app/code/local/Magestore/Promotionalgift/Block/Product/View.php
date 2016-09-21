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
class Magestore_Promotionalgift_Block_Product_View extends Mage_Catalog_Block_Product_View
{

    public function _prepareLayout()
    {
        $this->setTemplate('promotionalgift/product/view.phtml');
        return parent::_prepareLayout();
    }

    public function getJsItems()
    {
        if (!$this->hasData('js_items')) {
            $jsItems = array();
            if ($headBlock = $this->getLayout()->getBlock('head')) {
                $designPackage = Mage::getDesign();
                $baseJsUrl = Mage::getBaseUrl('js');
                $mergeCallback = Mage::getStoreConfigFlag('dev/js/merge_files') ? array(Mage::getDesign(), 'getMergedJsUrl') : null;
                foreach ($headBlock->getData('items') as $item) {
                    $name = $item['name'];
                    if ($item['type'] == 'js') {
                        $jsItems[] = $mergeCallback ? Mage::getBaseDir() . DS . 'js' . DS . $name : $baseJsUrl . $name;
                    }
                    if ($item['type'] == 'skin_js') {
                        $jsItems[] = $mergeCallback ? $designPackage->getFilename($name, array('_type' => 'skin')) : $designPackage->getSkinUrl($name, array());
                    }
                }
            }
            $this->setData('js_items', $jsItems);
        }
        return $this->getData('js_items');
    }

    public function getStartFormHtml()
    {
        return '';
    }

    public function getOptionsWrapperHtml()
    {
        return $this->getBlockHtml('product.info.options.wrapper');
    }

    public function getOptionsWrapperBottomHtml()
    {
        $block = $this->getLayout()->getBlock('product.info.options.wrapper.bottom');
        $block->unsetChild('product.info.addtocart');
        $block->unsetChild('product.info.addto');
        return $block->toHtml('product.info.options.wrapper.bottom');
    }

    public function getEndFormHtml()
    {
        return '';
    }

    public function isEditItem()
    {
        return $this->getFullActionName() == 'checkout_cart_configure';
    }

    public function isCartPage()
    {
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        return $currentUrl;
    }

    public function getFullActionName($delimiter = '_')
    {
        $request = $this->getRequest();
        return $request->getRequestedRouteName() . $delimiter .
        $request->getRequestedControllerName() . $delimiter .
        $request->getRequestedActionName();
    }

    public function getUrlImage()
    {
        return $this->getSkinUrl('images/promotionalgift/loading.gif');
    }

    public function getSubmitUrl($product, $additional = array())
    {
        if (version_compare(Mage::getVersion(), '1.5.0.0', '>=')) {
            return parent::getSubmitUrl($product, $additional);
        }
        return $this->getAddToCartUrl($product, $additional);
    }

}
