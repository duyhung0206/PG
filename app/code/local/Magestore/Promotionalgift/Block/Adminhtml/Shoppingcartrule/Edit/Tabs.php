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

/**
 * Promotionalgift Edit Tabs Block
 *
 * @category    Magestore
 * @package     Magestore_Promotionalgift
 * @author      Magestore Developer
 */
class Magestore_Promotionalgift_Block_Adminhtml_Shoppingcartrule_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('promotionalgift_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('promotionalgift')->__('Shopping Cart Rule Information'));
    }

    /**
     * prepare before render block to html
     *
     * @return Magestore_Promotionalgift_Block_Adminhtml_Shoppingcartrule_Edit_Tabs
     */
    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label' => Mage::helper('promotionalgift')->__('Rule Information'),
            'title' => Mage::helper('promotionalgift')->__('Rule Information'),
            'content' => $this->getLayout()
                ->createBlock('promotionalgift/adminhtml_shoppingcartrule_edit_tab_form')
                ->toHtml(),
        ));

        $this->addTab('condition', array(
            'label' => Mage::helper('promotionalgift')->__('Conditions'),
            'title' => Mage::helper('promotionalgift')->__('Conditions'),
            'content' => $this->getLayout()->createBlock('promotionalgift/adminhtml_shoppingcartrule_edit_tab_conditions')->toHtml(),
        ));

        $this->addTab('account_section', array(
            'label' => Mage::helper('promotionalgift')->__('Gift Items'),
            'title' => Mage::helper('promotionalgift')->__('Gift Items'),
            'url' => $this->getUrl('*/*/giftitem', array(
                '_current' => true,
                'id' => $this->getRequest()->getParam('id'),
                'store' => $this->getRequest()->getParam('store')
            )),
            'class' => 'ajax',
        ));

        return parent::_beforeToHtml();
    }
}