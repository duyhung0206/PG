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
class Magestore_Promotionalgift_Block_Adminhtml_Reportcartrule_Renderer_Product
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /* Render Grid Column*/
    //show each product in a row
    public function render(Varien_Object $row)
    {
        if ($row->getOrderId()) {
            $html = $this->getBackendProductHtmls($row->getProductIds(), $row->getProductNames());
            return sprintf('%s', $html);
        } else {
            return sprintf('%s', $row->getOrderItemNames());
        }
    }

    public function getBackendProductHtmls($productIds, $productNames)
    {
        $productHtmls = array();
        $productIds = explode(',', $productIds);
        $productNames = explode(';', $productNames);
        $count = 0;
        foreach ($productIds as $productId) {
            $productName = Mage::getModel('catalog/product')->load($productId)->getName();
            if ($productName) {
                $productUrl = $this->getUrl('adminhtml/catalog_product/edit/', array('_current' => true, 'id' => $productId));
                $productHtmls[] = '<a href="' . $productUrl . '" title="' . Mage::helper('promotionalgift')->__('View Product Detail') . '">' . $productName . '</a>';
            } else {
                $productHtmls[] = $productNames[$count];
            }
            $count++;
        }
        return implode('<br />', $productHtmls);
    }
}