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
class Magestore_Promotionalgift_Model_Freegiftcalendar extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('promotionalgift/freegiftcalendar');
    }

    public function getDaily()
    {
        $daily = array();
        for ($i = 0; $i < 31; $i++) {
            if ($i < 9) {
                $daily[$i] = array(
                    'label' => '0' . ($i + 1),
                    'value' => $i + 1
                );
            } else {
                $daily[$i] = array(
                    'label' => $i + 1,
                    'value' => $i + 1
                );
            }
        }
        return $daily;
    }

    public function getWeekly()
    {
        $weekly = array();
        $weekly[0] = array('label' => 'Sunday', 'value' => 'sunday');
        $weekly[1] = array('label' => 'Monday', 'value' => 'monday');
        $weekly[2] = array('label' => 'Tuesday', 'value' => 'tuesday');
        $weekly[3] = array('label' => 'Wednesday', 'value' => 'wednesday');
        $weekly[4] = array('label' => 'Thursday', 'value' => 'thursday');
        $weekly[5] = array('label' => 'Friday', 'value' => 'friday');
        $weekly[6] = array('label' => 'Saturday', 'value' => 'saturday');
        return $weekly;
    }

    public function getMonthly()
    {
        $monthly = array();
        $monthly[0] = array('label' => 'Week 1', 'value' => 1);
        $monthly[1] = array('label' => 'Week 2', 'value' => 2);
        $monthly[2] = array('label' => 'Week 3', 'value' => 3);
        $monthly[3] = array('label' => 'Week 4', 'value' => 4);
        $monthly[4] = array('label' => 'Week 5', 'value' => 5);
        return $monthly;
    }

    public function getYearly()
    {
        $yearly = array();
        $yearly[0] = array('label' => 'January', 'value' => 'january');
        $yearly[1] = array('label' => 'February', 'value' => 'february');
        $yearly[2] = array('label' => 'March', 'value' => 'march');
        $yearly[3] = array('label' => 'April', 'value' => 'april');
        $yearly[4] = array('label' => 'May', 'value' => 'may');
        $yearly[5] = array('label' => 'June', 'value' => 'june');
        $yearly[6] = array('label' => 'July', 'value' => 'july');
        $yearly[7] = array('label' => 'August', 'value' => 'august');
        $yearly[8] = array('label' => 'September', 'value' => 'september');
        $yearly[9] = array('label' => 'October', 'value' => 'october');
        $yearly[10] = array('label' => 'November', 'value' => 'november');
        $yearly[11] = array('label' => 'December', 'value' => 'december');
        return $yearly;
    }
}

?>
