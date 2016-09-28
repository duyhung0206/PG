<?php

class Magestore_Promotionalgift_Model_Mysql4_Limitcustomer_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('promotionalgift/limitcustomer');
    }
}