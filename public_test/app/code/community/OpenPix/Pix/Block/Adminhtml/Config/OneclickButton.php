<?php

class OpenPix_Pix_Block_Adminhtml_Config_OneclickButton extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate("openpix/config/oneclickButton.phtml");
    }

    /**
     * @return string
     */
    protected function _getElementHtml($element)
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getAjaxPrepareOneclickUrl()
    {
        return Mage::getSingleton("adminhtml/url")->getUrl("openpix/adminhtml_ajax/prepareOpenPixOneclick");
    }

    /**
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock("adminhtml/widget_button")
            ->setData([
                "id" => "openpix_pix_oneclick_button",
                "label" => $this->helper("adminhtml")->__("Connect the extension"),
                "onclick" => "openpixPrepareOneclick()",
            ]);

        return $button->toHtml();
    }
}