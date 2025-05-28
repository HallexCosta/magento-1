<?php

class OpenPix_Pix_Model_Observer
{
    use OpenPix_Pix_Trait_ExceptionMessenger;
    use OpenPix_Pix_Trait_LogMessenger;

    public function validateCustomer($customer)
    {
        $taxID = $customer["taxID"];

        $hasTaxID = isset($taxID);
        $isValidTaxIDLength = strlen($taxID) === 11;
        $isTaxIDValid = $hasTaxID && $isValidTaxIDLength;

        return $isTaxIDValid;
    }

    public function applyGiftback(Varien_Event_Observer $observer)
    {
    }

    protected function helper()
    {
        return Mage::helper("openpix_pix");
    }

    protected function orderHelper()
    {
        return Mage::helper("openpix_pix/order");
    }
}
