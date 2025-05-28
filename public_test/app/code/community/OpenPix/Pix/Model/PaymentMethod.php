<?php

class OpenPix_Pix_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    use OpenPix_Pix_Trait_ExceptionMessenger;
    use OpenPix_Pix_Trait_LogMessenger;

    protected $_canOrder = true;
    protected $_isInitializeNeeded = true;
    protected $_isGateway = true;
    protected $_allowCurrencyCode = ["BRL"];

    protected $_code = "openpix_pix";
    protected $_formBlockType = "openpix_pix/form";
    protected $_infoBlockType = "openpix_pix/info";

    public function getInformation()
    {
        return $this->getConfigData("information");
    }

    public function getMethodName()
    {
        return "openpix_pix";
    }

    /**
     * Method that will be executed instead of magento's authorize default
     * workflow
     *
     * @param string $paymentAction
     * @param Varien_Object $stateObject
     *
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function initialize($paymentAction, $stateObject)
    {
        $this->stateObject = $stateObject;

        $payment = $this->getInfoInstance();
        $this->authorize($payment, $payment->getOrder()->getBaseTotalDue());
    }

    public function authorize(Varien_Object $payment, $amount)
    {
        $this->log("OpenPix New Oder - Order Start ");
        try {
            if ($this->canOrder()) {
                $order = $this->getInfoInstance()->getOrder();
                $orderIncrementId = $order->getIncrementId();

                try {
                    $payload = $this->helper()->handlePayloadCharge(
                        $orderIncrementId
                    );
                } catch (Exception $e) {
                    $this->log(
                        "OpenPix - handlePayloadCharge Error " .
                            $e->getMessage()
                    );
                    $this->error($e->getMessage());
                    return false;
                }

                $address = ! empty($payload['customer']['address'])
                    ? $payload['customer']['address']
                    : [];

                $isAddressValid = is_array($address)
                    && ! empty($address['zipcode'])
                    && ! empty($address['street'])
                    && ! empty($address['number'])
                    && ! empty($address['neighborhood'])
                    && ! empty($address['city'])
                    && ! empty($address['state'])
                    && ! empty($address['country']);

                // Ignore the address if it is invalid.
                // At least for Normal Pix payment method.
                // Woovi Parcelado will send the address.
                if (! $isAddressValid) {
                    unset($payload['customer']['address']);
                }

                $this->log(
                    "OpenPix New Oder - API Payload " . json_encode($payload)
                );

                try {
                    $responseBody = $this->helper()->handleCreateCharge(
                        $payload
                    );
                } catch (Exception $e) {
                    $this->log(
                        "OpenPix - handleCreateCharge Error " . $e->getMessage()
                    );
                    $this->error($e->getMessage());
                    return false;
                }

                $this->log(
                    "OpenPix New Oder - API Response" . json_encode($payload)
                );

                try {
                    $responseCharge = $this->helper()->handleResponseCharge(
                        $responseBody,
                        $orderIncrementId,
                        $payment
                    );
                    $this->log(
                        "OpenPix - Response Charge " .
                            json_encode($responseCharge)
                    );
                } catch (Exception $e) {
                    $this->log(
                        "OpenPix - handleResponseCharge Error " .
                            $e->getMessage()
                    );
                    $this->error($e->getMessage());
                    return false;
                }

                // the additional information is from Magento Payment Model
                // There is no realtion with additionalInfo from pix
                $this->log(
                    "OpenPix - Response Charge as Additional Information " .
                        json_encode($responseCharge["additional"])
                );
                $this->log("OpenPix - Order End ");

                return $this;
            }

            $this->log("OpenPix New Oder - Cannot Order");

            return $this;
        } catch (Exception $e) {
            $this->log("OpenPix - Payment Method: Error " . $e->getMessage());
            $this->error($e->getMessage());
        }
    }

    protected function helper()
    {
        return Mage::helper("openpix_pix/order");
    }
}
