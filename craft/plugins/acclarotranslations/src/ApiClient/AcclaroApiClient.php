<?php

namespace Craft\AcclaroTranslations\ApiClient;

use Guzzle\Http\Client;
use Exception;

class AcclaroApiClient
{
    const PRODUCTION_URL = 'https://my.acclaro.com/api2';

    const SANDBOX_URL = 'https://apisandbox.acclaro.com/api2';

    protected $loggingEnabled = false;

    public function __construct(
        $apiToken,
        $sandboxMode = false,
        Client $client = null
    ) {
        $this->client = $client ?: new Client();

        $this->client->setBaseUrl(
            $sandboxMode ? self::SANDBOX_URL : self::PRODUCTION_URL
        );

        $this->client->setUserAgent('Craft');

        //$this->client->setDefaultOption('proxy', '127.0.0.1:8888');
        //$this->client->setDefaultOption('verify', false);

        $this->client->setDefaultHeaders(array(
            'Authorization' => sprintf('Bearer %s', $apiToken),
            'Accept' => 'application/json',
        ));
    }

    public function getClient()
    {
        return $this->client;
    }

    public function logRequest($request, $endpoint)
    {
        $tempPath = CRAFT_STORAGE_PATH.'runtime/temp/acclarotranslations';

        if (!is_dir($tempPath)) {
            mkdir($tempPath);
        }

        $filename = 'api-request-'.$endpoint.'-'.date('YmdHis').'.txt';

        $filePath = $tempPath.'/'.$filename;

        $handle = fopen($filePath, 'w+');

        fwrite($handle, (string) $request);

        fclose($handle);
    }

    public function logResponse($response, $endpoint)
    {
        $tempPath = CRAFT_STORAGE_PATH.'runtime/temp/acclarotranslations';

        if (!is_dir($tempPath)) {
            mkdir($tempPath);
        }

        $filename = 'api-response-'.$endpoint.'-'.date('YmdHis').'.txt';

        $filePath = $tempPath.'/'.$filename;

        $handle = fopen($filePath, 'w+');

        fwrite($handle, (string) $response);

        fclose($handle);
    }

    public function request($method, $endpoint, $query = array(), $files = array())
    {
        $request = $this->client->$method($endpoint, array(), $query);

        if ($this->loggingEnabled) {
            $this->logRequest($request, $endpoint);
        }

        if ($files) {
            foreach ($files as $key => $file) {
                $request->addPostFile($key, $file);
            }
        }

        try {
            $response = $request->send();
        } catch (Exception $e) {
            //@TODO
            //var_dump($e);
            return null;
        }

        if ($this->loggingEnabled) {
            $this->logResponse($response, $endpoint);
        }

        if (!$response->isSuccessful()) {
            //@TODO
            return null;
        }

        $responseJson = $response->json();

        if (empty($responseJson['success'])) {
            //@TODO
            return null;
        }

        if (!isset($responseJson['data'])) {
            return null;
        }

        // is assoc?
        if (is_array($responseJson['data']) && $responseJson['data'] === array_values($responseJson['data'])) {
            return array_map(function($row) {
                return (object) $row;
            }, $responseJson['data']);
        }

        return (object) $responseJson['data'];
    }

    public function get($endpoint, $query = array())
    {
        return $this->request('get', $endpoint, $query);
    }

    public function post($endpoint, $query = array(), $files = array())
    {
        return $this->request('post', $endpoint, $query, $files);
    }

    public function getAccount()
    {
        return $this->get('GetAccount');
    }

    public function createOrder($name, $comments, $dueDate, $craftOrderId, $wordCount)
    {
        return $this->post('CreateOrder', array(
            'name' => $name,
            'comments' => $comments,
            'duedate' => $dueDate,
            'clientref' => $craftOrderId,
            'delivery' => 'none',
            'estwordcount' => $wordCount,
        ));
    }

    public function requestOrderCallback($orderId, $url)
    {
        return $this->post('RequestOrderCallback', array(
            'orderid' => $orderId,
            'url' => $url,
        ));
    }

    public function getOrder($orderId)
    {
        return $this->post('GetOrder', array(
            'orderid' => $orderId,
        ));
    }

    public function getFileInfo($orderId)
    {
        return $this->post('GetFileInfo', array(
            'orderid' => $orderId,
        ));
    }

    public function simulateOrderComplete($orderId)
    {
        return $this->post('SimulateOrderComplete', array(
            'orderid' => $orderId,
        ));
    }

    public function submitOrder($orderId)
    {
        return $this->post('SubmitOrder', array(
            'orderid' => $orderId,
        ));
    }

    public function addReviewUrl($orderId, $fileId, $url)
    {
        return $this->post('AddReviewURL', array(
            'orderid' => $orderId,
            'fileid' => $fileId,
            'url' => $url,
        ));
    }

    public function sendSourceFile($orderId, $sourceLanguage, $targetLanguage, $craftOrderId, $sourceFile)
    {
        return $this->post('SendSourceFile', array(
            'orderid' => $orderId,
            'sourcelang' => $sourceLanguage,
            'targetlang' => $targetLanguage,
            'clientref' => $craftOrderId,
        ), array(
            'file' => $sourceFile,
        ));
    }

    public function getFileStatus($orderId, $fileId)
    {
        return $this->post('GetFileStatus', array(
            'orderid' => $orderId,
            'fileid' => $fileId,
        ));
    }

    public function getFile($orderId, $fileId)
    {
        $endpoint = 'GetFile';

        $request = $this->client->post($endpoint, array(), array(
            'orderid' => $orderId,
            'fileid' => $fileId,
        ));

        if ($this->loggingEnabled) {
            $this->logRequest($request, $endpoint);
        }

        try {
            $response = $request->send();
        } catch (Exception $e) {
            //@TODO
            //var_dump($e);
            return null;
        }

        if ($this->loggingEnabled) {
            $this->logResponse($response, $endpoint);
        }

        if (!$response->isSuccessful()) {
            //@TODO
            return null;
        }

        return $response->getBody();
    }

    public function requestFileCallback($orderId, $fileId, $url)
    {
        return $this->post('RequestFileCallback', array(
            'orderid' => $orderId,
            'fileid' => $fileId,
            'url' => $url,
        ));
    }
}
