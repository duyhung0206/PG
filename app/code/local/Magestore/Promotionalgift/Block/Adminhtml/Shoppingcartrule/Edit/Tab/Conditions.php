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
class Magestore_Promotionalgift_Block_Adminhtml_Shoppingcartrule_Edit_Tab_Conditions extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        if (Mage::getSingleton('adminhtml/session')->getFormData()) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData();
            $model = Mage::getModel('promotionalgift/shoppingcartrule')
                ->load($data['rule_id'])
                ->setData($data);
            Mage::getSingleton('adminhtml/session')->setFormData(null);
        } elseif (Mage::registry('shoppingcartrule_data')) {
            $model = Mage::registry('shoppingcartrule_data');
            $data = $model->getData();
        }

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('rule_');


        $giftfieldset = $form->addFieldset('shoppingcartrule_form', array(
            'legend' => Mage::helper('promotionalgift')->__('Promotional Gift Information')
        ));

        $giftfieldset->addField('number_item_free', 'text', array(
            'name' => 'number_item_free',
            'label' => Mage::helper('promotionalgift')->__('Number of selectable gift items'),
            'title' => Mage::helper('promotionalgift')->__('Number of selectable gift items'),
            'required' => false,
            'note' => Mage::helper('promotionalgift')->__('Maximum number of gift items customers can select among provided ones '),
        ));

        $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
            ->setTemplate('promo/fieldset.phtml')
            ->setNewChildUrl($this->getUrl('adminhtml/promo_quote/newConditionHtml/form/rule_conditions_fieldset'));

        $fieldset = $form->addFieldset('conditions_fieldset', array('legend' => Mage::helper('promotionalgift')->__('Apply the rule only if the following conditions are met (leave blank for all orders)')))->setRenderer($renderer);
        $fieldset->addField('conditions', 'text', array(
            'name' => 'conditions',
            'label' => Mage::helper('promotionalgift')->__('Conditions'),
            'title' => Mage::helper('promotionalgift')->__('Conditions'),
            'required' => true,
        ))->setRule($model)->setRenderer(Mage::getBlockSingleton('rule/conditions'));

        $form->setValues($data);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}