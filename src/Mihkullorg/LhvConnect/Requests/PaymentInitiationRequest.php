<?php

namespace Mihkullorg\LhvConnect\Requests;

use Mihkullorg\LhvConnect\XMLGenerator;

class PaymentInitiationRequest extends FullRequest
{
    protected $url = "payment";
    protected $method = "POST";

    /**
     * Return the xml as a string
     *
     * @return string
     */
    public function getXML($timezone = 'Europe/Tallinn')
    {
        $defaultTimeZone = date_default_timezone_get();
        date_default_timezone_set('Europe/Tallinn');
        $xml = XMLGenerator::paymentInitiationXML($this->data, $this->configuration);
        date_default_timezone_set($defaultTimeZone);

        return $xml;
    }
}
