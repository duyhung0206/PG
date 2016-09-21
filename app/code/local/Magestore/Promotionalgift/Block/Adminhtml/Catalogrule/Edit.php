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
class Magestore_Promotionalgift_Block_Adminhtml_Catalogrule_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'promotionalgift';
        $this->_controller = 'adminhtml_catalogrule';

        $this->_updateButton('save', 'label', Mage::helper('promotionalgift')->__('Save Rule'));
        $this->_updateButton('delete', 'label', Mage::helper('promotionalgift')->__('Delete Rule'));

        $this->_addButton('saveandcontinue', array(
            'label' => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick' => 'saveAndContinueEdit()',
            'class' => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('catalogrule_content') == null)
                    tinyMCE.execCommand('mceAddControl', false, 'catalogrule_content');
                else
                    tinyMCE.execCommand('mceRemoveControl', false, 'catalogrule_content');
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
        if (Mage::registry('catalogrule_data')
            && Mage::registry('catalogrule_data')->getId()
        ) {
            return Mage::helper('promotionalgift')->__("Edit Rule '%s'",
                $this->htmlEscape(Mage::registry('catalogrule_data')->getName())
            );
        }
        return Mage::helper('promotionalgift')->__('Add Rule');
    }
}