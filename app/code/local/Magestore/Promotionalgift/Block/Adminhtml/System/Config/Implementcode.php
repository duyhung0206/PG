<?php

class Magestore_Promotionalgift_Block_Adminhtml_System_Config_Implementcode extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element){
        $layout  =  Mage::helper('promotionalgift')->returnlayout();
        $block = Mage::helper('promotionalgift')->returnblock();
        $text =  Mage::helper('promotionalgift')->returntext();
        $template = Mage::helper('promotionalgift')->returntemplate();
        return '
<input id="promotionalgift_template-state" type="hidden" value="1" name="config_state[promotionalgift_template]">
<fieldset id="promotionalgift_template" class="config collapseable" style="">
    <div id="messages" class="div-mess-promotionalgift">
        <ul class="messages mess-megamennu">
            <li class="notice-msg notice-promotionalgift">
                <ul>
                    <li>
                    '.$text.'
                    </li>				
                </ul>
            </li>
        </ul>
    </div>
    <br/>  
    <div id="messages" class="div-mess-promotionalgift">
        <ul class="messages mess-megamennu">
            <li class="notice-msg notice-promotionalgift">
                <ul>
                    <li>
                    '.Mage::helper('promotionalgift')->__('Option 1: Add the code below to a CMS Page or a Static Block').'
                    </li>
                </ul>
            </li>
        </ul>
    </div>
        <ul>
            <li>
                <code>
                '.$block.'
                </code>	
            </li>
        </ul>     
    <br/>
    <div id="messages" class="div-mess-promotionalgift">
       <ul class="messages mess-megamennu">
            <li class="notice-msg notice-promotionalgift">
                <ul>
                    <li>
                    '.Mage::helper('promotionalgift')->__('Option 2: Add the code below to a template file').'
                    </li>
                </ul>
            </li>
        </ul>
    </div>
    <ul>
        <li>
            <code>
            &lt;?php echo'.$template.' ?&gt;
            </code>	
        </li>
    </ul>
    <br/>
    <div id="messages" class="div-mess-promotionalgift">
        <ul class="messages mess-megamennu">
            <li class="notice-msg notice-promotionalgift">
                <ul>
                    <li>
                    '.Mage::helper('promotionalgift')->__('Option 3: Add the code below to a layout file').'
                    </li>
                </ul>
            </li>
        </ul>
    </div>
    <ul>
        <li>
            <code>
            '.$layout.'
            </code>	
        </li>
    </ul>
</fieldset>';
    }
}
