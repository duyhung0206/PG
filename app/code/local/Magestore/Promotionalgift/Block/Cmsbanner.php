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
class Magestore_Promotionalgift_Block_Cmsbanner extends Mage_Core_Block_Template
{

    public function _prepareLayout()
    {
        if (!Mage::getStoreConfig('promotionalgift/general/enable')) return;
        //check rules 
        $avaibleBanner = $this->avaibleBanner();
        if ($avaibleBanner && count($avaibleBanner)) {
            $this->setTemplate('promotionalgift/cmsbanner.phtml');
        }
        parent::_prepareLayout();
    }

    public function avaibleBanner()
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        return Mage::getModel('promotionalgift/banner')->validateQuote($quote);
    }

    //render variable of static block
    public function variableCms($cmsId)
    {
        $cmsContent = Mage::getModel('cms/block')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($cmsId);
        $var = array('variable' => 'value', 'other_variable' => 'other value');
        $filterModel = Mage::getModel('cms/template_filter');
        $filterModel->setVariables($var);
        return $filterModel->filter($cmsContent->getContent());
    }
}