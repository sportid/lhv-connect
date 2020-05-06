<?php

namespace Mihkullorg\LhvConnect;

use GuzzleHttp\Client;
use Mihkullorg\LhvConnect\Requests\AccountStatementRequest;
use Mihkullorg\LhvConnect\Requests\DeleteMessageInInbox;
use Mihkullorg\LhvConnect\Requests\HeartbeatGetRequest;
use Mihkullorg\LhvConnect\Requests\MerchantPaymentReportRequest;
use Mihkullorg\LhvConnect\Requests\PaymentInitiationRequest;
use Mihkullorg\LhvConnect\Requests\RetrieveMessageFromInbox;
use Psr\Http\Message\ResponseInterface;

class LhvConnect
{
    private $client;
    private $configuration;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
        $this->client = new Client([
            'base_uri' => $this->configuration['url'],
        ]);

    }

    /**
     * Test request. Tests the connection to the server
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public function makeHeartbeatGetRequest()
    {
        $request = new HeartbeatGetRequest($this->client, $this->configuration);

        return $request->sendRequest();
    }

    public function makeHeartbeatPostRequest()
    {
        //TODO
    }

    /**
     * Retrieve all the messages from the inbox
     * Deletes all the retrieved messages from the inbox
     *
     * @return array
     */
    public function getAllMessages()
    {
        $messages = [];

        while (true) {
            $message = $this->makeRetrieveMessageFromInboxRequest();

            if (!isset($message->getHeaders()['Content-Length']) || $message->getHeader('Content-Length')[0] == 0) {
                break;
            }

            $this->makeDeleteMessageInInboxRequest($message);

            array_push($messages, $message);
        }

        return $messages;
    }

    /**
     * @return ResponseInterface
     */
    public function makeRetrieveMessageFromInboxRequest()
    {
        $request = new RetrieveMessageFromInbox($this->client, $this->configuration);

        return $request->sendRequest();
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param ResponseInterface $message
     * @return ResponseInterface
     */
    public function makeDeleteMessageInInboxRequest(ResponseInterface $message)
    {
        $id = $message->getHeader('Message-Response-Id')[0];
        $request = new DeleteMessageInInbox($this->client, $this->configuration, null, [], $id);

        return $request->sendRequest();
    }

    /**
     * @param $payments
     * @return string
     */
    public function getPaymentInitiationXML($payments, $timeZone = 'Europe/Tallinn')
    {
        $request = new PaymentInitiationRequest($this->client, $this->configuration, $payments);

        return $request->getXML($timeZone);
    }

    // Here for legacy purposes
    public function makePaymentInitiationRequest(string $filePath)
    {
        return $this->makeSignedPaymentInitiationRequest($filePath);
    }

    public function makeSignedPaymentInitiationRequest(string $filePath)
    {
        return $this->sendPaymentInitiationRequest($filePath, 'application/vnd.etsi.asic-e+zip');
    }

    public function makeUnsignedPaymentInitiationRequest(string $filePath)
    {
        return $this->sendPaymentInitiationRequest($filePath, 'application/xml');
    }

    /**
     * @param $filePath
     * @param $contentType
     * @return ResponseInterface
     */
    protected function sendPaymentInitiationRequest(string $filePath, string $contentType)
    {
        $body = fopen($filePath, 'r');

        $headers = [
            'Content-Type' => $contentType
        ];

        $request = new PaymentInitiationRequest($this->client, $this->configuration, [], $body, $headers);

        return $request->sendRequest();
    }
}
