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
class Magestore_Promotionalgift_Block_Adminhtml_Banner_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('promotionalgift_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('promotionalgift')->__('Ad Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label' => Mage::helper('promotionalgift')->__('Ads Settings'),
            'title' => Mage::helper('promotionalgift')->__('Ads Settings'),
            'content' => $this->getLayout()
                ->createBlock('promotionalgift/adminhtml_banner_edit_tab_form')
                ->toHtml(),
        ));

        $this->addTab('condition', array(
            'label' => Mage::helper('promotionalgift')->__('Manage Rules'),
            'title' => Mage::helper('promotionalgift')->__('Manage Rules'),
            'content' => $this->getLayout()->createBlock('promotionalgift/adminhtml_banner_edit_tab_conditions')
                ->toHtml(),
        ));
        return parent::_beforeToHtml();
    }
}