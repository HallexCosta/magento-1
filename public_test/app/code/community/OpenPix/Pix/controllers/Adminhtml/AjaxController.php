<?php

class OpenPix_Pix_Adminhtml_AjaxController extends Mage_Adminhtml_Controller_Action
{
    public function prepareOpenPixOneclickAction()
    {
        // Remove current App ID

        Mage::app()->cleanCache();
        Mage::getModel("core/config")->saveConfig(
            "payment/openpix_pix/app_ID",
            ""
        );

        // Determine redirect URL

        $webhookUrl = Mage::getUrl("openpix/webhook");
        $platformUrl = Mage::helper("openpix_pix/config")->getOpenPixPlatformUrl();
        $newPlatformIntegrationUrl = $platformUrl . "/home/applications/magento1/add/oneclick?website=" . $webhookUrl;

        $result = json_encode([
            "webhook_url" => $webhookUrl,
            "redirect_url" => $newPlatformIntegrationUrl,
        ]);

        $response = Mage::app()->getResponse();

        $response->setHeader("Content-type", "application/json");
        $response->setBody($result);
    }
}