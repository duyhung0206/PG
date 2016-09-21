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
class Magestore_Promotionalgift_Block_Cart_Configurable_Item extends Mage_Checkout_Block_Cart_Item_Renderer_Configurable
{

    public function getOptionList()
    {
        $options = parent::getOptionList();
        $item = $this->getItem();
        $quoteId = Mage::getModel('checkout/cart')->getQuote()->getId();

        $shoppingQuote = Mage::getModel('promotionalgift/shoppingquote')
            ->getCollection()
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('item_id', $item->getId())
            ->getFirstItem();
        if ($shoppingQuote->getId()) {
            $options[] = array(
                'label' => Mage::helper('promotionalgift')->__('Promotional Gift'),
                'value' => $this->htmlEscape($shoppingQuote->getMessage()),
            );
            return $options;
        }

        $quotes = Mage::getModel('promotionalgift/quote')
            ->getCollection()
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('item_id', $item->getId())// ->getFirstItem()
        ;
        if (count($quotes) > 0) {
            foreach ($quotes as $quote) {
                if ($quote->getMessage()) {
                    $options[] = array(
                        'label' => Mage::helper('promotionalgift')->__('Promotional Gift'),
                        'value' => $this->htmlEscape($quote->getMessage()),
                    );
                    break;
                }
            }
        }
        return $options;
    }

    public function getDeleteUrl()
    {
        if ($this->hasDeleteUrl()) {
            return $this->getData('delete_url');
        }

        $url = Mage::getUrl('checkout/cart');
        return $this->getUrl(
            'checkout/cart/delete', array(
                'id' => $this->getItem()->getId(),
                Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => Mage::helper('core/url')->urlEncode($url)
            )
        );
    }
}
