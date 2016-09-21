<?php

class Magestore_Promotionalgift_Helper_Report_Giftorder extends Mage_Adminhtml_Helper_Dashboard_Abstract
{
    protected function _initCollection()
    {
        $data = $this->_collection = Mage::getResourceModel('promotionalgift/sale_collection')
            ->prepareGiftOrder($this->getParam('period'), 0, 0);
        if ($this->getParam('store'))
            $this->_collection->addFieldToFilter('store_id', $this->getParam('store'));

        $this->_collection->load();
    }
}