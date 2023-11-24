<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 30.07.18
 * Time: 16:56
 */

namespace frontend\modules\payment\controllers;

use common\components\mixplat\MixplatException;
use common\models\Payment;
use common\models\Service;
use common\models\ServiceRate;
use Exception;
use frontend\modules\payment\helpers\PaymentHelper;
use frontend\modules\subscribe\models\Subscribe;
use Svezem\Services\PaymentService\Classes\PaymentStatus;
use Svezem\Services\PaymentService\Gates\CallbackDataInterface;
use Svezem\Services\PaymentService\Gates\Sberbank\Input\CallbackData;
use Svezem\Services\PaymentService\Gates\Sberbank\SberbankGate;
use Svezem\Services\PaymentService\PaymentService;
use Yii;
use yii\base\ErrorException;
use yii\base\ExitException;
use yii\base\InvalidArgumentException;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ResultController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionSberbank()
    {
        $paymentGate = Yii::$container->get(PaymentService::class, [Yii::$container->get(SberbankGate::class)]);

        // Проверка отправителя
        /** @var CallbackDataInterface $callbackData */
        $callbackData = new CallbackData(Yii::$app->request->get());
        if(!$paymentGate->checkSumValidate($callbackData)){
            throw new Exception('Checksum incorrect');
        }

        // Проверка статуса платежа
        /** @var PaymentStatus $paymentStatus */
        $paymentStatus = $callbackData->getStatus();
        if($paymentStatus->isSuccess()){
            $this->successPay($callbackData->getId());
        }else{
            $this->failPay($callbackData->getId());
        }
    }

    public function actionUnitpaycallback(){
        $req = Yii::$app->request->get();

        /*if( $req['method'] == 'check' ){
            $reply['result'] = 'Ok';
            return json_encode($reply);
        }*/

        $params = $req['params'];

        Yii::$app->response->format = Response::FORMAT_JSON;

        // Делаем предварительные проверки верности платежа
        if($params['projectId'] != Yii::$app->unitpay->projectId){
            Yii::error("projectId не верно указан get=".print_r($req,1), 'ResultController.Unitpaycallback');

            $reply['error'] = 'Ошибка в параметрах запроса';
            return $reply;
        }

        try {
            Yii::$app->unitpay->checkHandlerRequest();
        }catch(Exception $e){
            Yii::error("checkHandlerRequest выбросил исключение get=".print_r($req,1), 'ResultController.Unitpaycallback');

            $reply['error'] = 'Ошибка в параметрах запроса';
            return $reply;
        }

        $id = (int)$params['account'];

        $payment = Payment::findOne($id);

        if(!$payment){
            Yii::error("Платеж не найден id={$id} get=".print_r($req,1), 'ResultController.Unitpaycallback');

            $reply['error'] = 'Платеж не найден';
            return $reply;
        }

        if(in_array($payment->status, [Payment::STATUS_PAID, Payment::STATUS_REFUSED])){
            $reply['result'] = 'Запрос успешно обработан';
            return $reply;
        }

        // сверка суммы
        if($params['orderSum'] != $payment->amount){
            Yii::error("orderSum не верен get=".print_r($req,1), 'ResultController.Unitpaycallback');

            $reply['error'] = 'Ошибка в параметрах запроса';
            return $reply;
        }

        // сверка валюты
        if($params['orderCurrency'] != Yii::$app->unitpay->orderCurrency){
            Yii::error("orderCurrency не верен get=".print_r($req,1), 'ResultController.Unitpaycallback');

            $reply['error'] = 'Ошибка в параметрах запроса';
            return $reply;
        }

        if($req['method'] == 'check'){}
        elseif($req['method'] == 'error'){
            Payment::updateAll([
                'status' => Payment::STATUS_REFUSED
            ],[
                'id' => $payment->id,
                'status' => [Payment::STATUS_PENDING]
            ]);
        }
        elseif($req['method'] == 'pay') {
            $this->successPay($payment->id);
        }else {
            Yii::error("Неизвестный тип запроса req=" . print_r($_POST, 1), 'ResultController.Unitpaycallback');
            $reply['error'] = 'Ошибка в параметрах запроса';
            return $reply;
        }

        $reply['result'] = 'Запрос успешно обработан';

        return $reply;
    }

    /**
     * Обработка успешного платежа
     * @param $payment_id
     */
    protected function successPay(int $payment_id){
        Payment::updateAll([
            'status' => Payment::STATUS_PAID
        ],[
            'id' => $payment_id
        ]);

        Yii::$app->gearman->getDispatcher()->background("paymentProcess", [
            'payment_id' => $payment_id
        ]);
    }

    /**
     * Обработка неуспешного платежа
     * @param int $payment_id ИД платежа
     */
    private function failPay(int $payment_id)
    {
        Payment::updateAll([
            'status' => Payment::STATUS_REFUSED
        ],[
            'id' => $payment_id,
            'status' => [Payment::STATUS_PENDING]
        ]);
    }

    public function actionPayyBillingSubscribe(){
//        $post = Yii::$app->request->post();
//
//        if( !isset($post['phone'], $post['pay']) )
//            throw new InvalidArgumentException('Ошибка аргументов');
//
//        $subscribe = Subscribe::findOne(['phone'=>$post['phone']]);
//
//        if( !$subscribe ){
//            throw new ErrorException('Подписка по указанному номеру не найдена');
//        }
//
//        $count = Service::getCountByPrice(2, $post['pay']);
//
//        if( Yii::$app->payy->checkHashBilling() ){
//            //10 - payy
//            $payment = PaymentHelper::createPayment(10, [
//                [
//                    'service_id' => 2,
//                    'count' => $count,
//                    'object_id' => $subscribe->id
//                ]
//            ]);
//
//            if( $payment ){
//                $this->successPay($payment->id);
//                exit("Подписка пополнена на $count сообщений");
//            }
//            else{
//                throw new ErrorException('Не удалось создать платеж');
//            }
//        }
//        else{
//            throw new ErrorException('Ошибка hash');
//        }
    }

    /**
     * Экшен отвечает на обращение MixPlat при проверки
     * и подтверждении платежа
     * @throws ExitException
     * @throws \yii\db\Exception
     */
    public function actionMixplatBillingSubscribe(){
        $postDataRaw = file_get_contents("php://input");

        try
        {
            $post = json_decode($postDataRaw, true);
        }
        catch(Exception $e)
        {
            Yii::$app->mixplat->returnError($e->getMessage());
            Yii::$app->end();
        }

        //Yii::info($post, 'mixplat');

        if( !isset($post['request']) ){
            Yii::$app->mixplat->returnError("Missing parameters 'request'");
            Yii::$app->end();
        }

        $subscribe = Subscribe::findOne(['phone'=>$post['phone']]);

        if( !$subscribe ){
            Yii::$app->mixplat->returnError('Подписка по указанному номеру не найдена');
            Yii::$app->end();
        }

        if( $post['request'] == 'check' ){
            try
            {
                Yii::$app->mixplat->processCheck();
            }
            catch (MixplatException $error)
            {
                Yii::$app->mixplat->returnError($error->getMessage());
                Yii::$app->end();
            }

            if( !$this->checkPrice(10, $post['amount']/100) ){
                Yii::$app->mixplat->returnError('Price is invalid');
                Yii::$app->end();
            }

            Yii::$app->mixplat->returnOk();
            Yii::$app->end();
        }

        if( $post['request'] == 'status' && $post['status'] == 'success'){
            try
            {
                Yii::$app->mixplat->processStatus();
            }
            catch (MixplatException $error)
            {
                Yii::$app->mixplat->returnError($error->getMessage());
                Yii::$app->end();
            }

            if( !$this->checkPrice(10, $post['amount']/100) ){
                Yii::$app->mixplat->returnError('Price is invalid');
                Yii::$app->end();
            }

            // 2 - СМС рассылка
            $count = Service::getCountByPrice(Service::SMS_NOTIFY, $post['amount']/100);

            $rate = ServiceRate::findOne(['service_id' => Service::SMS_NOTIFY]);

            if( !$rate ){
                Yii::$app->mixplat->returnError('Server error');
                Yii::$app->end();
            }

            //10 - mixplat
            $payment = PaymentHelper::createPayment(10, [
                [
                    'service_rate_id' => $rate->id,
                    'count' => $count,
                    'object_id' => $subscribe->id
                ]
            ]);

            if( $payment ){
                $this->successPay($payment->id);
            }
            else{
                Yii::$app->mixplat->returnError('Не удалось создать платеж');
                Yii::$app->end();
            }

            Yii::$app->mixplat->returnOk();
            Yii::$app->end();
        }

        Yii::$app->mixplat->returnError('Undefined query');
    }

    /**
     * Проверяем переданную сумму с ценой за N СМС
     * @param $count - кол-во СМС
     * @param $recieved_price - полученная сумма
     * @return bool
     */
    protected function checkPrice($count, $recieved_price){
        $expect_price = Service::getPriceByCount(Service::SMS_NOTIFY, $count);

        if( $expect_price === doubleval($recieved_price) ){
            $result = true;
        }
        else{
            $result = false;
            //Yii::info("Цена не совпала. Ожидаемая цена: {$expect_price} mixplat: {$recieved_price}", 'mixplat');
        }

        return $result;
    }
}
