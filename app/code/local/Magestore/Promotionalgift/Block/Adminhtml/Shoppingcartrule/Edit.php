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
 * Promotionalgift Edit Block
 *
 * @category     Magestore
 * @package     Magestore_Promotionalgift
 * @author      Magestore Developer
 */
class Magestore_Promotionalgift_Block_Adminhtml_Shoppingcartrule_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'promotionalgift';
        $this->_controller = 'adminhtml_shoppingcartrule';

        $this->_updateButton('save', 'label', Mage::helper('promotionalgift')->__('Save Rule'));
        $this->_updateButton('delete', 'label', Mage::helper('promotionalgift')->__('Delete Rule'));

        $this->_addButton('saveandcontinue', array(
            'label' => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class' => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('shoppingcartrule_content') == null)
                    tinyMCE.execCommand('mceAddControl', false, 'shoppingcartrule_content');
                else
                    tinyMCE.execCommand('mceRemoveControl', false, 'shoppingcartrule_content');
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    /**
     * get text to show in header when edit an item
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (Mage::registry('shoppingcartrule_data')
            && Mage::registry('shoppingcartrule_data')->getId()
        ) {
            return Mage::helper('promotionalgift')->__("Edit Rule '%s'", $this->htmlEscape(Mage::registry('shoppingcartrule_data')->getName())
            );
        }
        return Mage::helper('promotionalgift')->__('Add Rule');
    }

}