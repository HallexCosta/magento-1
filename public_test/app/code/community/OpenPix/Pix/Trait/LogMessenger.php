<?php

trait OpenPix_Pix_Trait_LogMessenger
{
    /**
     * Grava os logs no arquivo definido $local
     *
     * @param string   $message, $local , int|null $level
     */
    public function log(
        $message,
        $local = "openpix_exception.log",
        $level = null
    ) {
        Mage::log($message, $level, $local);
    }

    /**
     * Grava o histórico de Webhooks recebidos retornando um Status Code HTTP
     *
     * @param string   $message, int|null $level
     *
     * @return  bool
     */
    public function logWebhook($message, $level = null)
    {
        $this->log($message, "openpix_webhooks.log", $level);

        switch ($level) {
            case 4:
                http_response_code(422);
                return false;
                break;
            case 5:
                return false;
                break;
            default:
                return true;
                break;
        }
    }
}
