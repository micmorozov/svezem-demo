<?php


namespace Svezem\Services\PaymentService\Classes;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

class Request
{
    /** @var ClientInterface $client */
    private $httpClient;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(ClientInterface $client, LoggerInterface $logger)
    {
        $this->httpClient = $client;
        $this->logger = $logger;
    }

    public function post(string $url, array $req=[]): array
    {
        $this->logger->debug("POST req: {$url}", $req);

        $response = $this->httpClient->post($url, [
            'form_params' => $req
        ]);

        $resContent = $response->getBody()->getContents();

        $this->logger->debug("POST res: {$resContent}");

        return json_decode($resContent, true);
    }

    public function get(string $url, array $query=[]): array
    {
        $this->logger->debug("GET req: {$url}", $query);

        $response = $this->httpClient->get($url, [
            'query' => $query
        ]);

        $this->logger->debug("GET res: {$response->getBody()->getContents()}");

        return json_decode($response->getBody()->getContents(), true);
    }
}