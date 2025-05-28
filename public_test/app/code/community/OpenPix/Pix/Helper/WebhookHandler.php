<?php

class OpenPix_Pix_Helper_WebhookHandler extends Mage_Core_Helper_Abstract
{
    use OpenPix_Pix_Trait_LogMessenger;
    use OpenPix_Pix_Trait_ExceptionMessenger;

    /**
     * Handle receive webhooks
     *
     * @param string $body
     *
     * @return bool
     */
    public function handle($body)
    {
        $this->logWebhook("OpenPix handle");
        $data = json_decode($body, true);

        $event = $data['event'];
        $evento = $data['evento'];

        $this->logWebhook("event = $event");
        $this->logWebhook("evento = $evento");

        if ($evento === 'teste_webhook' || $event === 'teste_webhook') {

            $this->handleTestWebhook($data);
            return;
        }

        if ($event === 'magento1-configure') {
            $this->handleIntegrationConfiguration($data);
            return;
        }

        if (
            $event === 'OPENPIX:TRANSACTION_RECEIVED' ||
            $event === 'OPENPIX:CHARGE_COMPLETED'
        ) {
            $this->handleWebhookOrderUpdate($body);
            return;
        }
    }

    public function handleWebhookOrderUpdate($body) {
        try {
            $jsonBody = json_decode($body, true);

            if ($this->isPixDetachedPayload($jsonBody)) {
                $this->logWebhook(
                    "OpenPix WebApi::ProcessWebhook Pix Detached"
                );

                return [
                    "error" => null,
                    "success" =>
                        "Pix Detached with endToEndId: " .
                        $jsonBody["pix"]["endToEndId"],
                ];
            }

            if (!$this->isValidWebhookPayload($jsonBody)) {
                $this->logWebhook(
                    "OpenPix WebApi::ProcessWebhook Invalid Payload: " .
                        $jsonBody
                );

                return ["error" => "Invalid Payload", "success" => null];
            }
        } catch (Exception $e) {
            $this->logWebhook(
                "Fail when interpreting webhook JSON: " . $e->getMessage()
            );
            return false;
        }

        $charge = $jsonBody["charge"];
        $pix = $jsonBody["pix"];

        return $this->chargePaid($charge, $pix);
    }

    public function handleIntegrationConfiguration($data)
    {
        $appID = $data['appID'];
        $alreadyHasAppID = !empty(Mage::helper("openpix_pix")->getAppID());

        $this->logWebhook("OpenPix handleIntegrationConfiguration");
        $this->logWebhook($appID);

        if ($alreadyHasAppID) {
            header('HTTP/1.1 400 Bad Request');
            $response = [
                'message' => __('App ID already configured', 'openpix'),
            ];
            echo json_encode($response);
            exit();
        }

        if (empty($appID)) {
            header('HTTP/1.1 400 Bad Request');
            $response = [
                'message' => __('App ID is required', 'openpix'),
            ];
            echo json_encode($response);
            exit();
        }

        Mage::app()->cleanCache();
        Mage::getModel("core/config")->saveConfig(
            "payment/openpix_pix/app_ID",
            $appID
        );

        header('HTTP/1.1 200 OK');
        $response = [
            'message' => 'success',
        ];
        echo json_encode($response);
        exit();
    }
    public function handleTestWebhook($jsonBody)
    {
        $this->logWebhook("handleTestWebhook");
        header('HTTP/1.1 200 OK');
        $response = [
            'message' => 'success',
        ];
        echo json_encode($response);
        exit();
    }

    public function isPixDetachedPayload($jsonBody)
    {
        if (!isset($jsonBody["pix"])) {
            return false;
        }

        if (
            isset($jsonBody["charge"]) &&
            isset($jsonBody["charge"]["correlationID"])
        ) {
            return false;
        }

        return true;
    }

    public function isValidWebhookPayload($jsonBody)
    {
        if (
            !isset($jsonBody["charge"]) ||
            !isset($jsonBody["charge"]["correlationID"])
        ) {
            return false;
        }

        if (
            !isset($jsonBody["pix"]) ||
            !isset($jsonBody["pix"]["endToEndId"])
        ) {
            return false;
        }

        return true;
    }

    public function chargePaid($charge, $pix)
    {
        $this->logWebhook("OpenPix::chargePaid Start");

        $order = $this->getOrder($charge);

        $this->logWebhook("OpenPix::chargePaid order" . json_encode($order));

        if (!$order) {
            $this->logWebhook("OpenPix::chargePaid Order Not Found");

            return ["error" => "Order Not Found", "success" => null];
        }

        $hasEndToEndId = $this->hasEndToEndId($order);

        if ($hasEndToEndId) {
            $this->logWebhook("OpenPix::chargePaid Order Already Invoiced");

            return ["error" => "Order Already Invoiced", "success" => null];
        }

        return $this->createInvoice($order, $pix);
    }

    public function getOrder($charge)
    {
        if (!isset($charge["correlationID"])) {
            return false;
        }

        $order = $this->getOrderByCorrelationID($charge["correlationID"]);

        if (!$order || !$order->getId()) {
            $this->logWebhook(
                "OpenPix Webhook - No order was found to invoice: " .
                    $charge["correlationID"]
            );
            return false;
        }

        return $order;
    }

    private function getOrderByCorrelationID($correlationID)
    {
        if (!$correlationID) {
            return false;
        }

        $order = Mage::getModel("sales/order")
            ->getCollection()
            ->addAttributeToSelect("*")
            ->addFieldToFilter("openpix_correlationid", $correlationID)
            ->getFirstItem();

        if (!$order) {
            return false;
        }

        return $order;
    }

    public function hasEndToEndId($order)
    {
        $hasEndToEndId = $order->getData("openpix_endtoendid");

        if (isset($hasEndToEndId)) {
            return true;
        }

        return false;
    }

    /**
     * Tenta criar um 'fatura' no Magento
     * Uma invoice registra o histórico de tentativas e pagamentos em um pedido
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return bool
     */
    public function createInvoice($order, $pix)
    {
        $orderId = $order->getId();
        if (!$orderId) {
            return ["error" => "Order Not Found", "success" => null];
        }

        if (!$order->canInvoice()) {
            $this->logWebhook(
                __(
                    sprintf(
                        "Impossible to generate invoice for order %s.",
                        $order->getId()
                    )
                )
            );

            return [
                "error" => sprintf(
                    "Impossible to generate invoice for order %s.",
                    $order->getId()
                ),
                "success" => null,
            ];
        }

        $this->logWebhook("Generating invoice for the order " . $orderId);

        $order
            ->setState(Mage_Sales_Model_Order::STATE_PROCESSING)
            ->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
        $invoice = Mage::getModel(
            "sales/service_order",
            $order
        )->prepareInvoice();

        $invoice->setRequestedCaptureCase(
            Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE
        );
        $invoice->register();
        $transactionSave = Mage::getModel("core/resource_transaction")
            ->addObject($invoice)
            ->addObject($invoice->getOrder());

        $transactionSave->save();

        $invoice->sendEmail(true, "");

        $order->setOpenpixEndtoendid($pix["endToEndId"]);

        $order->addStatusHistoryComment(
            "The payment was confirmed by OpenPix and the order is being processed",
            false
        );
        $order->save();

        $this->logWebhook(
            "The payment was confirmed by OpenPix and the order is being processed"
        );
        return [
            "error" => null,
            "success" =>
                "The payment was confirmed by OpenPix and the order is being processed",
        ];
    }
}
