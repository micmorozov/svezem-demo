<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 21.08.18
 * Time: 15:24
 */

namespace common\components\mixplat;

use Exception;
use yii\base\Component;
use Yii;

class MixPlatComponent extends Component
{
    /**
     * API URL
     */
    const API_URL = 'https://api.mixplat.com/';

    /**
     * @var int
     */
    public $serviceId;

    /**
     * @var string
     */
    public $secretKey;

    /**
     * @return mixed
     * @throws MixplatException
     */
    public function getPostData()
    {
        $postDataRaw = file_get_contents("php://input");

        if (empty($postDataRaw))
        {
            throw new MixplatException('Empty post data.');
        }

        try
        {
            $postData = json_decode($postDataRaw, true);
        }
        catch (Exception $e)
        {
            throw new MixplatException('Wrong post_json format. ' . $e->getMessage());
        }

        return $postData;
    }


    /**
     * @param $method string
     * @param array $parameters
     * @return array
     * @throws MixplatException
     */
    private function request($method, $parameters = array())
    {
        $context = stream_context_create
        (
            array
            (
                'http' => array
                (
                    'method'  => 'POST',
                    'timeout' => 30,
                    'header'  => 'Content-type: application/json',
                    'content' => json_encode($parameters)
                )
            )
        );

        $response = @file_get_contents(self::API_URL . $method, false, $context);

        if (empty($response))
        {
            throw new MixplatException('Empty server response.');
        }

        $responseParameters = json_decode($response, true);

        if (empty($responseParameters))
        {
            throw new MixplatException('Empty response parameters.');
        }

        if (isset($responseParameters['result']) && $responseParameters['result'] != 'ok')
        {
            $errorString = "Request error: " . $responseParameters['result'];
            $errorString .= isset($responseParameters['message']) ?  ' (' . $responseParameters['message'] . ')' : '';

            throw new MixplatException($errorString);
        }

        return $responseParameters;

    }


    /**
     * Method ping
     * @return array
     * @throws MixplatException
     */
    public function requestPing()
    {
        return $this->request('ping');
    }


    /**
     * Method create_payment
     * @param string $phoneNumber
     * @param int $amount
     * @param string $currency
     * @param string $externalId
     * @param string $description
     * @param string $successMessage
     * @param string $customData
     * @param int $test
     * @return array
     * @throws MixplatException
     */
    public function requestCreatePayment($phoneNumber, $amount, $currency, $externalId, $description, $successMessage, $customData, $test = 0)
    {
        $signature = md5($this->serviceId . $phoneNumber . $amount . $currency . $externalId . $test . $this->secretKey);

        $parameters = array();
        $parameters['service_id'] = $this->serviceId;
        $parameters['phone'] = $phoneNumber;
        $parameters['amount'] = $amount;
        $parameters['currency'] = $currency;
        $parameters['description'] = $description;
        $parameters['external_id'] = $externalId;
        $parameters['success_message'] = $successMessage;
        $parameters['custom_data'] = $customData;
        $parameters['test'] = $test;

        $parameters['signature'] = $signature;

        return $this->request('mc/create_payment', $parameters);
    }

    /**
     * Method get_payment
     * @param string $paymentId
     * @param string $paymentExternalId
     * @return array
     * @throws MixplatException
     */
    public function requestGetPayment($paymentId, $paymentExternalId = '')
    {
        $signature = md5($this->serviceId . $paymentId . $paymentExternalId . $this->secretKey);

        $parameters = array();
        $parameters['service_id'] = $this->serviceId;
        $parameters['id'] = $paymentId;
        $parameters['external_id'] = $paymentExternalId;
        $parameters['signature'] = $signature;

        return $this->request('mc/get_payment', $parameters);
    }


    /**
     * Method create_service
     * @param string $serviceTitle
     * @param string $serviceUrl
     * @param string $serviceDescription
     * @param string $serviceSecretKey
     * @param string $serviceStatusUrl
     * @param string $serviceCheckUrl
     * @return array
     * @throws MixplatException
     */
    public function requestCreateService($serviceTitle, $serviceUrl, $serviceDescription, $serviceSecretKey, $serviceStatusUrl, $serviceCheckUrl)
    {
        $signature = md5($this->serviceId . $serviceSecretKey . $serviceUrl . $this->secretKey);

        $parameters = array();
        $parameters['company_id'] = $this->serviceId;
        $parameters['title'] = $serviceTitle;
        $parameters['url'] = $serviceUrl;
        $parameters['description'] = $serviceDescription;
        $parameters['secret_key'] = $serviceSecretKey;
        $parameters['url_status'] = $serviceStatusUrl;
        $parameters['url_check'] = $serviceCheckUrl;
        $parameters['signature'] = $signature;

        return $this->request('create_service', $parameters);
    }

    /**
     * Method get_service
     * @return array
     * @throws MixplatException
     */
    public function requestGetService()
    {
        $signature = md5($this->serviceId . $this->secretKey);

        $parameters = array();
        $parameters['service_id'] = $this->serviceId;
        $parameters['signature'] = $signature;

        return $this->request('get_service', $parameters);
    }

    /**
     * Method phone_information
     * @param string $phoneNumber
     * @return array
     * @throws MixplatException
     */
    public function requestPhoneInformation($phoneNumber)
    {
        $signature = md5($this->serviceId . $phoneNumber . $this->secretKey);

        $parameters = array();
        $parameters['service_id'] = $this->serviceId;
        $parameters['phone'] = $phoneNumber;
        $parameters['signature'] = $signature;

        return $this->request('mc/phone_information', $parameters);
    }

    /**
     * Method register
     * @param string $periodFrom
     * @param string $periodTo
     * @param int $allPayments
     * @return array
     * @throws MixplatException
     */
    public function requestRegister($periodFrom, $periodTo, $allPayments = 0)
    {
        $signature = md5($this->serviceId . $periodFrom . $periodTo . $this->secretKey);

        $parameters = array();
        $parameters['company_id'] = $this->serviceId;
        $parameters['period_from'] = $periodFrom;
        $parameters['period_to'] = $periodTo;
        $parameters['all'] = $allPayments;
        $parameters['signature'] = $signature;

        return $this->request('register', $parameters);
    }


    /**
     * Return result = ok
     */
    public function returnOk()
    {
        echo json_encode(array('result' => 'ok'));
    }

    /**
     * Return result = error
     * @param string $errorText
     */
    public function returnError($errorText = '')
    {
        echo json_encode(array('result' => 'Error. ' . $errorText));
    }

    /**
     * Method status
     * @return mixed
     * @throws MixplatException
     */
    public function processStatus()
    {
        $postData = $this->getPostData();

        $paramId = $postData['id'];
        $paramExternalId = $postData['external_id'];
        $paramServiceId = $postData['service_id'];
        $paramStatus = $postData['status'];
        $paramStatusExtended = $postData['status_extended'];
        $paramPhone = $postData['phone'];
        $paramCurrency = $postData['currency'];
        $paramAmount = $postData['amount'];
        $paramAmountMerchant = $postData['amount_merchant'];
        $paramTest = $postData['test'];
        $paramSignature = $postData['signature'];

        $signature = md5($paramId . $paramExternalId . $paramServiceId .
            $paramStatus . $paramStatusExtended . $paramPhone .
            $paramAmount . $paramAmountMerchant . $paramCurrency .
            $paramTest . $this->secretKey);

        if ($signature !== $paramSignature) {
            throw new MixplatException('Wrong signature.');
        }

        return $postData;
    }

    /**
     * Method check
     * @return mixed
     * @throws MixplatException
     */
    public function processCheck()
    {
        $postData = $this->getPostData();

        $paramId = $postData['id'];
        $paramServiceId = $postData['service_id'];
        $paramPhone = $postData['phone'];
        $paramAmount = $postData['amount'];
        $paramSignature = $postData['signature'];

        $signature = md5($paramId . $paramServiceId . $paramPhone . $paramAmount . $this->secretKey);

        if ($signature !== $paramSignature) {
            throw new MixplatException('Wrong signature.');
        }

        return $postData;
    }
}