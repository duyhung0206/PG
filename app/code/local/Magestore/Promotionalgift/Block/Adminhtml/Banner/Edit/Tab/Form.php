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
class Magestore_Promotionalgift_Block_Adminhtml_Banner_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{

    public function getModel()
    {
        return Mage::registry('banner_data');
    }

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $model = $this->getModel();

        if (Mage::getSingleton('adminhtml/session')->getBannerData()) {
            $data = Mage::getSingleton('adminhtml/session')->getBannerData();
        } elseif (Mage::registry('banner_data')) {
            $data = $model->getData();
        }
        $fieldset = $form->addFieldset('banner_form', array(
            'legend' => Mage::helper('promotionalgift')->__('Ad Settings')
        ));

        $fieldset->addField('name', 'text', array(
            'label' => Mage::helper('promotionalgift')->__('Ad Name'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'name',
        ));
        $fieldset->addField('cmsblock', 'select', array(
            'name' => 'cmsblock',
            'label' => Mage::helper('promotionalgift')->__('CMS Block'),
            'values' => Mage::getModel('cms/block')->getCollection()
                ->addFieldToFilter('is_active', 1)
                ->toOptionArray(),
            'value' => $this->getCmsPageId()
        ));
        $fieldset->addField('is_cart', 'select', array(
            'name' => 'is_cart',
            'label' => Mage::helper('promotionalgift')->__('Appear on Shopping Cart'),
            'values' => Mage::getSingleton('promotionalgift/status')->getOptionHash(),
        ));
        $fieldset->addField('is_onepage', 'select', array(
            'name' => 'is_onepage',
            'label' => Mage::helper('promotionalgift')->__('Appear on Checkout Page'),
            'values' => Mage::getSingleton('promotionalgift/status')->getOptionHash(),
        ));
         if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('website_ids', 'multiselect', array(
                'name' => 'website_ids[]',
                'label' => Mage::helper('promotionalgift')->__('Websites'),
                'title' => Mage::helper('promotionalgift')->__('Websites'),
                'required' => true,
                'values' => Mage::getSingleton('adminhtml/system_config_source_website')->toOptionArray(),
            ));
        } else {
            $fieldset->addField('website_ids', 'hidden', array(
                'name' => 'website_ids[]',
                'value' => Mage::app()->getStore(true)->getWebsiteId()
            ));
            $data['website_ids'] = Mage::app()->getStore(true)->getWebsiteId();
        }

        $customerGroups = Mage::getResourceModel('customer/group_collection')
            ->load()->toOptionArray();

        $found = false;
        foreach ($customerGroups as $group) {
            if ($group['value'] == 0) {
                $found = true;
            }
        }
        if (!$found) {
            array_unshift($customerGroups, array('value' => 0,
                'label' => Mage::helper('promotionalgift')->__('NOT LOGGED IN')));
        }

        $fieldset->addField('customer_group_ids', 'multiselect', array(
            'name' => 'customer_group_ids[]',
            'label' => Mage::helper('promotionalgift')->__('Customer Groups'),
            'title' => Mage::helper('promotionalgift')->__('Customer Groups'),
            'required' => true,
            'values' => $customerGroups,
        ));
        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        $fieldset->addField('from_date', 'date', array(
            'name' => 'from_date',
            'label' => Mage::helper('promotionalgift')->__('Start Date'),
            'title' => Mage::helper('promotionalgift')->__('Start Date'),
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'time' => false,
            'format' => $dateFormatIso
        ));
        $fieldset->addField('to_date', 'date', array(
            'name' => 'to_date',
            'label' => Mage::helper('promotionalgift')->__('End Date'),
            'title' => Mage::helper('promotionalgift')->__('End Date'),
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'time' => false,
            'format' => $dateFormatIso
        ));
         $gift_calendar = $fieldset->addField('banner_calendar', 'select', array(
            'name' => 'banner_calendar',
            'label' => Mage::helper('promotionalgift')->__('Event Calendar'),
            'title' => Mage::helper('promotionalgift')->__('Event Calendar'),
            'note' => Mage::helper('promotionalgift')->__('Auto-enable this ad on selected time within the period set above (e.g. every Monday, Oct 1 - Oct 25'),
            'options' => array(
                'all' => Mage::helper('promotionalgift')->__('All Days'),
                'weekly' => Mage::helper('promotionalgift')->__('Day of week'),
                'daily' => Mage::helper('promotionalgift')->__('Day of month'),
                'monthly' => Mage::helper('promotionalgift')->__('Week of month'),
                'yearly' => Mage::helper('promotionalgift')->__('Month of year'),
            )));

        //daily
        $daily = Mage::getModel('promotionalgift/freegiftcalendar')->getDaily();
        $dailyfield = $fieldset->addField('daily', 'multiselect', array(
            'name' => 'daily[]',
            'title' => Mage::helper('promotionalgift')->__('Day of month'),
            'values' => $daily,
        ));
        //weekly
        $weekly = Mage::getModel('promotionalgift/freegiftcalendar')->getWeekly();
        $weeklyfield = $fieldset->addField('weekly', 'multiselect', array(
            'name' => 'weekly[]',
            'title' => Mage::helper('promotionalgift')->__('Day of week'),
            'values' => $weekly,
        ));
        //monthly
        $monthly = Mage::getModel('promotionalgift/freegiftcalendar')->getMonthly();
        $monthlyfield = $fieldset->addField('monthly', 'multiselect', array(
            'name' => 'monthly[]',
            'title' => Mage::helper('promotionalgift')->__('Week of month'),
            'values' => $monthly,
        ));
        //yearly
        $yearly = Mage::getModel('promotionalgift/freegiftcalendar')->getYearly();
        $yearlyfield = $fieldset->addField('yearly', 'multiselect', array(
            'name' => 'yearly[]',
            'title' => Mage::helper('promotionalgift')->__('Month of year'),
            'values' => $yearly,
        ));

        $fieldset->addField('priority', 'text', array(
            'name' => 'priority',
            'label' => Mage::helper('promotionalgift')->__('Priority'),
            'note' => Mage::helper('promotionalgift')->__('The smaller the value, the higher the priority.'),
        ));
        $fieldset->addField('status', 'select', array(
            'label' => Mage::helper('promotionalgift')->__('Status'),
            'name' => 'status',
            'values' => Mage::getSingleton('promotionalgift/status')->getOptionHash(),
        ));

        $form->setValues($data);
        $this->setChild('form_after', $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
            ->addFieldMap($gift_calendar->getHtmlId(), $gift_calendar->getName())
            ->addFieldMap($dailyfield->getHtmlId(), $dailyfield->getName())
            ->addFieldMap($weeklyfield->getHtmlId(), $weeklyfield->getName())
            ->addFieldMap($monthlyfield->getHtmlId(), $monthlyfield->getName())
            ->addFieldMap($yearlyfield->getHtmlId(), $yearlyfield->getName())
            ->addFieldDependence(
                $dailyfield->getName(), $gift_calendar->getName(), 'daily')
            ->addFieldDependence(
                $weeklyfield->getName(), $gift_calendar->getName(), 'weekly')
            ->addFieldDependence(
                $monthlyfield->getName(), $gift_calendar->getName(), 'monthly')
            ->addFieldDependence(
                $yearlyfield->getName(), $gift_calendar->getName(), 'yearly')
        );
        return parent::_prepareForm();
    }

}
