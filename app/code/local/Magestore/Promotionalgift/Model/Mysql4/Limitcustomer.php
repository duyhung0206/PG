<?php

class Magestore_Promotionalgift_Model_Mysql4_Limitcustomer extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('promotionalgift/limitcustomer', 'item_id');
    }
}