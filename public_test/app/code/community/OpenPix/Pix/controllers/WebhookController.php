<?php

class OpenPix_Pix_WebhookController extends Mage_Core_Controller_Front_Action
{
    use OpenPix_Pix_Trait_LogMessenger;
    use OpenPix_Pix_Trait_ExceptionMessenger;

    public function validateWebhook($data, $body) {
        $signature = $this->getRequest()->getHeader("x-webhook-signature");

        if (!$signature || !$this->validateRequest($body, $signature)) {
            header('HTTP/1.2 400 Bad Request');
            $response = [
                'error' => 'Invalid Webhook signature.',
            ];
            $this->logWebhook(sprintf("Invalid Webhook signature"), Zend_Log::ERR);
            $this->logWebhook(sprintf("Signature: %s", $signature), Zend_Log::DEBUG);
            $this->logWebhook($data);
            echo json_encode($response);
            exit();
        }

        if (!$this->isValidWebhookPayload($data)) {
            header('HTTP/1.2 400 Bad Request');
            $response = [
                'error' => 'Invalid Webhook Payload',
            ];
            $this->logWebhook(sprintf("Invalid Webhook Payload"), Zend_Log::ERR);
            $this->logWebhook($data);
            echo json_encode($response);
            exit();
        }

        if ($this->isPixDetachedPayload($data)) {
            header('HTTP/1.1 200 OK');

            $response = [
                'message' => 'Pix Detached',
            ];
            $this->logWebhook(sprintf("Invalid Webhook Payload"), Zend_Log::ERR);
            $this->logWebhook($data);
            echo json_encode($response);
            exit();
        }
    }
    public function isValidWebhookPayload($data)
    {
        if (!isset($data['event']) || empty($data['event'])) {
            if (!isset($data['evento']) || empty($data['evento'])) {
                return false;
            }
        }

        // @todo remove it and update evento to event

        return true;
    }
    public function isPixDetachedPayload($data)
    {
        if (!isset($data['pix'])) {
            return false;
        }

        if (isset($data['charge']) && isset($data['charge']['correlationID'])) {
            return false;
        }

        return true;
    }
    /**
     * Webhook Route
     */
    public function indexAction()
    {
        $this->logWebhook(sprintf("---------------Start webhook---------------"), Zend_Log::INFO);

        $handler = Mage::helper("openpix_pix/webhookHandler");

        $body = file_get_contents("php://input");
        $data = json_decode($body, true);

        $this->validateWebhook($data, $body);
        
        $result = $handler->handle($body);

        $this->logWebhook(
            sprintf("Webhook result " . json_encode($result)),
            Zend_Log::INFO
        );

        if (isset($result["error"])) {
            header("HTTP/1.2 400 Bad Request");
            $response = [
                "error" => $result["error"],
            ];
            echo json_encode($response);
            exit();
        }

        header("HTTP/1.1 200 OK");

        $response = [
            "success" => $result["success"],
        ];
        echo json_encode($response);
        exit();
    }

    public function verifySignature($payload, $signature)
    {
        $publicKey = Mage::helper("openpix_pix/config")->getOpenPixKey();

        $verify = openssl_verify(
            $payload,
            base64_decode($signature),
            base64_decode($publicKey),
            'sha256WithRSAEncryption'
        );

        $log = [
            "signature" => $signature,
            "payload" => $payload,
            "isValid" => $verify,
            "publicKey" => $publicKey,
        ];
        $this->logWebhook(
            sprintf(
                "\nSignature: %s\nPayload: %s\nisValid: %s\npublicKey: %s",
                $signature, $payload, $verify == 1 ? "true" : "false", $publicKey
            ),
            Zend_Log::INFO
        );
        return $verify;
    }

    /**
     * Validate webhook authorization
     *
     * @return bool
     */
    protected function validateRequest($payload)
    {

        $signatureHeader = $this->getRequest()->getHeader("x-webhook-signature");

        $isValid = $this->verifySignature($payload, $signatureHeader);


        return $isValid;
    }
}
