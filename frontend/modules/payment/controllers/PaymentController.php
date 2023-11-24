<?php

namespace frontend\modules\payment\controllers;

use common\models\Payment;
use common\models\Service;
use frontend\modules\cabinet\models\JuridicalForm;
use GuzzleHttp\Client;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * PaymentController.
 */
class PaymentController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action){
        $this->enableCsrfValidation = false;

        return parent::beforeAction($action);
    }

    /**
     * @param int $payment_id
     * @param string $redirect_url
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionSuccess($payment_id, $redirect_url){
        $payment = Payment::findOne($payment_id);

        if( !$payment){
            throw new BadRequestHttpException();
        }

        $messageTye = 'error';

        if($payment->status == Payment::STATUS_PENDING){
            $message = 'Платеж находится в обработке';
        } elseif ($payment->status == Payment::STATUS_REFUSED) {
            $message = 'Не удалось выполнить платеж';
        } else {
            $messageTye = 'success';

            $message = "Платеж выполнен";

            if(count($payment->paymentDetails) == 1){
                $detail = $payment->paymentDetails[0];

                switch($detail->service_id){
                    case Service::SMS_NOTIFY:
                        $message = "Вам начислено {$detail->count} уведомлений на новые грузы";
                        break;

                    case Service::BOOKING_START:
                    case Service::BOOKING_BUSINESS:
                    case Service::BOOKING_PROFI:
                        $message = 'Вы перешли на тариф "'.$detail->service->name.'"';
                        break;
                }
            }
        }

        Yii::$app->session->setFlash($messageTye, $message);
        return $this->redirect($redirect_url);

        /*if($payment_id === null) {
            $post = Yii::$app->request->post();
            if(isset($post['InvId'])) {
                $payment_id = $post['InvId'];
            } else {
                throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
            }
        }

        /** @var $payment PaymentService */
        /*$payment = PaymentService::findOne($payment_id);
        if($payment === null) {
            throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
        }
        if($payment->status == PaymentService::STATUS_PAID) {
            $message = "Услуга '".$payment->serviceRate->service->name."' успешно оплачена.";
        } elseif($payment->status == PaymentService::STATUS_REFUSED) {
            $message = "Оплата услуги отменена";
        } else {
            $message = "Платеж находится в обработке";
        }
        Yii::$app->session->setFlash(($payment->status == PaymentService::STATUS_PAID) ? 'success' : 'error', $message);
        return $this->redirect(Url::previous());*/
    }

    public function actionFail($payment_id, $redirect_url){
        $payment = Payment::findOne($payment_id);

        if( !$payment){
            throw new BadRequestHttpException();
        }

        $message = "Не удалось выполнить платеж";

        if(count($payment->paymentDetails) == 1){
            $detail = $payment->paymentDetails[0];

            switch($detail->service_id){
                case Service::SMS_NOTIFY:
                    $message = "Не удалось оплатить уведомления на новые грузы";
                    break;
            }
        }

        Yii::$app->session->setFlash('error', $message);
        return $this->redirect($redirect_url);
    }

    public function actionQiwiCallback(){
        $post = Yii::$app->request->post();
        $headers = Yii::$app->request->headers;

        $response_headers = Yii::$app->response->headers;
        $response_headers->add('Content-Type', 'text/xml');
        $response_headers->add('charset', 'UTF-8');

        if( !isset($headers['X-Api-Signature']) || !isset($post['bill_id'])){
            Yii::error('Не указан номер платежа');
            echo '5';
            die;
        }

        /** @var $payment Payment */
        $payment = Payment::findOne($post['bill_id']);
        if($payment === null){
            Yii::error('Платёж с данным номером не найден');
            echo '5';
            die;
        }
        // Проверяем что бы платеж небыл еще обработан
        if($payment->status != Payment::STATUS_PENDING){
            Yii::error('Платёж с данным номером уже обработан');
            echo '5';
            die;
        }

        $hash = base64_encode(
            hash_hmac("sha1",
                $payment->amount.'|'
                .$post['bill_id'].'|'
                .$post['ccy'].'|'
                .$post['command'].'|'
                .$post['comment'].'|'
                .$post['error'].'|'
                .$post['prv_name'].'|'
                .$post['status'].'|'
                .$post['user'],
                Yii::$app->qiwi->rest_password,
                true
            )
        );

        if($hash != $headers['X-Api-Signature']){
            // тут содержится код на случай, если верификация не пройдена
            $payment->status = Payment::STATUS_REFUSED;
            $payment->save();
            Yii::error('Хеш не совпадает');
            echo '151';
            die;
        }

        // тут код на случай, если проверка прошла успешно
        $payment->wallet_number = preg_replace('/([^0-9])/', '', $post['user']);
        $payment->status = Payment::STATUS_PAID;
        $payment->save();

        echo '0';
    }

    public function actionRobokassaCallback(){
        $post = Yii::$app->request->post();
        if( !isset($post['InvId'])){
            Yii::error('Не указан номер платежа');
        } else{
            /** @var $payment Payment */
            $payment = Payment::findOne($post['InvId']);
            if($payment === null){
                Yii::error('Платёж с данным номером не найден');
            } else
                // Проверяем что бы платеж небыл еще обработан
                if($payment->status != Payment::STATUS_PENDING){
                    Yii::error('Платёж с данным номером уже обработан');
                } else{
                    $md5 = strtolower(md5($post['OutSum'].":".$post['InvId'].":".Yii::$app->robokassa->password2));
                    if($md5 == strtolower($post['SignatureValue'])){
                        if($post['OutSum'] == $payment->amount){
                            echo 'OK'.$post['InvId'];
                            $payment->status = Payment::STATUS_PAID;
                            $payment->save();
                        } else{
                            $payment->status = Payment::STATUS_REFUSED;
                            $payment->save();
                            Yii::error('Неверные параметры платежа');
                        }
                    } else{
                        $payment->status = Payment::STATUS_REFUSED;
                        $payment->save();
                        Yii::error('Хеш не совпадает');
                    }
                }
        }
    }

    public function actionWebmoneyCallback(){
        $post = Yii::$app->request->post();
        if(isset($post['LMI_PREREQUEST']) && $post['LMI_PREREQUEST'] == 1){ # Prerequest
            if(isset($post['LMI_PAYMENT_NO'])){ # step 3
                /** @var $payment Payment */
                $payment = Payment::findOne($post['LMI_PAYMENT_NO']);
                if($payment === null){
                    Yii::error('Платёж с данным номером не найден');
                } else
                    // Проверяем что бы платеж небыл еще обработан
                    if($payment->status != Payment::STATUS_PENDING){
                        Yii::error('Платёж с данным номером уже обработан');
                    } else{
                        switch($payment->paymentSystem->code){
                            case 'wmr' :
                                $wallet = Yii::$app->wmr->wallet;
                                $price = $payment->amount;
                                break;
                            case 'wmz' :
                                $wallet = Yii::$app->wmz->wallet;
                                $price = $payment->amount;
                                break;
                            default:
                                $wallet = "";
                                $price = 0;
                        }
                        if($post['LMI_PAYEE_PURSE'] == $wallet && $post['LMI_PAYMENT_AMOUNT'] == $price){ # step 5
                            echo 'YES'; # if everything is ok,  give ok to transaction
                        } else{ # step 5
                            $payment->status = Payment::STATUS_REFUSED;
                            $payment->save();
                            Yii::error('Неверные параметры платежа 1');
                        }
                    }
            } else{ # step 3
                Yii::error('Не указан номер платежа');
            }
        } else{ #  PaymentService notification
            if(isset($post['LMI_PAYMENT_NO'])){ # Check ticket, step 11
                /** @var $payment Payment */
                $payment = Payment::findOne($post['LMI_PAYMENT_NO']);
                if($payment === null){
                    Yii::error('Платёж с данным номером не найден');
                } else
                    // Проверяем что бы платеж небыл еще обработан
                    if($payment->status != Payment::STATUS_PENDING){
                        Yii::error('Платёж с данным номером уже обработан');
                    } else{
                        switch($payment->paymentSystem->code){
                            case 'wmr' :
                                $wallet = Yii::$app->wmr->wallet;
                                $secret = Yii::$app->wmr->secret;
                                $price = $payment->amount;
                                break;
                            case 'wmz' :
                                $wallet = Yii::$app->wmz->wallet;
                                $secret = Yii::$app->wmz->secret;
                                $price = $payment->amount;
                                break;
                            default:
                                $wallet = "";
                                $secret = "";
                                $price = 0;
                        }
                        # Create check string
                        $chkstring = $post['LMI_PAYEE_PURSE'].$post['LMI_PAYMENT_AMOUNT'].$post['LMI_PAYMENT_NO'].
                            $post['LMI_MODE'].$post['LMI_SYS_INVS_NO'].$post['LMI_SYS_TRANS_NO'].$post['LMI_SYS_TRANS_DATE'].
                            $secret.$post['LMI_PAYER_PURSE'].$post['LMI_PAYER_WM'];
                        $sha256 = strtoupper(hash('sha256', $chkstring));

                        if($sha256 == $post['LMI_HASH']){ # checksum is correct
                            if($post['LMI_PAYEE_PURSE'] == $wallet && $post['LMI_PAYMENT_AMOUNT'] == $price){  # payment params correct, step 15

                                $payment->wallet_number = $post['LMI_PAYER_PURSE'];
                                $payment->status = Payment::STATUS_PAID;
                                $payment->save();
                            } else{ # step 15
                                $payment->status = Payment::STATUS_REFUSED;
                                $payment->save();
                                Yii::error('Неверные параметры платежа 2');
                            }
                        } else{
                            $payment->status = Payment::STATUS_REFUSED;
                            $payment->save();
                            Yii::error('Хеш не совпадает');
                        }
                    }
            } else{ # step 11
                Yii::error('Не указан номер платежа');
            }
        }
    }

    public function actionYandexCallback(){
        $secret_key = Yii::$app->yandex->secret_key;
        $post = Yii::$app->request->post();

        if( !isset($post['label'])){
            Yii::error('Не указан номер платежа');
            throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
        }

        /** @var $payment Payment */
        $payment = Payment::findOne($post['label']);
        if($payment === null){
            Yii::error('Платёж с данным номером не найден');
            throw new BadRequestHttpException('Что-то пошло не так. Сообщите нам. Спасибо за сотрудничество.');
        } else
            // Проверяем что бы платеж небыл еще обработан
            if($payment->status != Payment::STATUS_PENDING){
                Yii::error('Платёж с данным номером уже обработан');
                throw new BadRequestHttpException('Что-то пошло не так. Сообщите нам. Спасибо за сотрудничество.');
            }

        $sha1 = sha1(
            $post['notification_type']
            .'&'.$post['operation_id']
            .'&'.$post['amount']
            .'&643&'.$post['datetime']
            .'&'.$post['sender']
            .'&'.$post['codepro']
            .'&'.$secret_key
            .'&'.$post['label']
        );

        // возможно некоторые из нижеперечисленных параметров вам пригодятся
        // $_POST['operation_id'] - номер операция
        // $_POST['amount'] - количество денег, которые поступят на счет получателя
        // $_POST['withdraw_amount'] - количество денег, которые будут списаны со счета покупателя
        // $_POST['datetime'] - тут понятно, дата и время оплаты
        // $_POST['sender'] - если оплата производится через Яндекс Деньги, то этот параметр содержит номер кошелька покупателя
        // $_POST['label'] - лейбл, который мы указывали в форме оплаты
        // $_POST['email'] - email покупателя (доступен только при использовании https://)

        if($sha1 != $post['sha1_hash'] || $payment->amount != $post['withdraw_amount']){
            // тут содержится код на случай, если верификация не пройдена
            $payment->status = Payment::STATUS_REFUSED;
            $payment->save();
            Yii::error('Хеш не совпадает');
            throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
        }

        // тут код на случай, если проверка прошла успешно
        $payment->wallet_number = $post['sender'];
        $payment->status = Payment::STATUS_PAID;
        if( !$payment->save()){
            Yii::trace(serialize($payment->errors));
        }
    }

    public function actionWmerCallback(){
        $payment_username = Yii::$app->wmer->login;
        $payment_password = md5(Yii::$app->wmer->payment_password);

        $post = Yii::$app->request->post();
        if( !isset($post['PAYMENT_ORDER_ID'])){
            Yii::error('Не указан номер платежа');
            echo "__NO__";
            die;
        }

        /** @var $payment Payment */
        $payment = Payment::findOne($post['PAYMENT_ORDER_ID']);

        // Проверяем что бы платеж небыл еще обработан
        if($payment->status != Payment::STATUS_PENDING){
            Yii::error('Платёж с данным номером уже обработан');
            echo "__NO__";
            die;
        }

        if(isset($post['PREVIEW']) && $post['PREVIEW']){  // Обработка предварительного запроса
            if($payment === null){
                Yii::error('Платёж с данным номером не найден');
                echo "__NO__";
                die;
            }
            if($post['PAYMENT_AMOUNT'] != $payment->amount){
                Yii::error('Неверные параметры платежа');
                $payment->status = Payment::STATUS_REFUSED;
                $payment->save();
                echo "__NO__";
                die;
            }
            echo "__YES__";
        } else{

            $hash_pattern = 'SYSTEM_NAME::payment_username::payment_password::PAYMENT_ORDER_ID::PAYMENT_STATUS::PAYMENT_AMOUNT::PAYMENT_DESCRIPTION::RESULT_URL::SUCCESS_URL::FAIL_URL';
            $hash_source = '';
            $e = explode('::', $hash_pattern);

            for($i = 0, $s = sizeof($e); $i < $s; ++$i){
                if($e[$i] == 'payment_password'){
                    $hash_source .= $payment_password;
                } elseif($e[$i] == 'payment_username'){
                    $hash_source .= $payment_username;
                } else{
                    $hash_source .= strval($post[$e[$i]]);
                }
                if($i + 1 < $s){
                    $hash_source .= '::';
                }
            }

            foreach($_REQUEST as $k => $v){
                if(preg_match('~^param_[a-z0-9]+~', $k)) $hash_source .= '::'.$v;
            }

            $hash = md5($hash_source);
            if($hash != $_REQUEST['SIGN'] || $_REQUEST['PAYMENT_AMOUNT'] != $payment->amount){
                Yii::error('Хеш не совпадает');
                $payment->status = Payment::STATUS_REFUSED;
                $payment->save();
                echo "__NO__";
                die;
            }

            if($_REQUEST['PAYMENT_STATUS'] != 2 && $_REQUEST['PAYMENT_STATUS'] != 3){
                $payment->status = Payment::STATUS_REFUSED;
                $payment->save();
                echo "__NO__";
                die;
            }

            // Необходимые действия по обработке платежа
            $payment->status = Payment::STATUS_PAID;
            $payment->save();
            echo "__YES__";
        }
    }

    public function actionPaypalCallback(){
        $raw_post_data = file_get_contents('php://input');
//    $raw_post_data = 'mc_gross=19.95&protection_eligibility=Eligible&address_status=confirmed&payer_id=LPLWNMTBWMFAY&tax=0.00&address_street=1+Main+St&payment_date=20%3A12%3A59+Jan+13%2C+2009+PST&payment_status=Completed&charset=windows-1252&address_zip=95131&first_name=Test&mc_fee=0.88&address_country_code=US&address_name=Test+User&notify_version=2.6&custom=&payer_status=verified&address_country=United+States&address_city=San+Jose&quantity=1&verify_sign=AtkOfCXbDm2hu0ZELryHFjY-Vb7PAUvS6nMXgysbElEn9v-1XcmSoGtf&payer_email=gpmac_1231902590_per%40paypal.com&txn_id=61E67681CH3238416&payment_type=instant&last_name=User&address_state=CA&receiver_email=gpmac_1231902686_biz%40paypal.com&payment_fee=0.88&receiver_id=S8XGHLYDW9T3S&txn_type=express_checkout&item_name=&mc_currency=USD&item_number=&residence_country=US&test_ipn=1&handling_amount=0.00&transaction_subject=&payment_gross=19.95&shipping=0.00';
        $raw_post_array = explode('&', $raw_post_data);
        $myPost = array();
        foreach($raw_post_array as $keyval){
            $keyval = explode('=', $keyval);
            if(count($keyval) == 2)
                $myPost[$keyval[0]] = urldecode($keyval[1]);
        }

        if( !isset($myPost['item_number'])){
            Yii::error('Не указан номер платежа');
            throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
        }
        /** @var $payment Payment */
        $payment = Payment::findOne($myPost['item_number']);
        if($payment === null){
            Yii::error('Платёж с данным номером не найден');
            throw new BadRequestHttpException('Что-то пошло не так. Сообщите нам. Спасибо за сотрудничество.');
        }

        // Проверяем что бы платеж небыл еще обработан
        if($payment->status != Payment::STATUS_PENDING){
            Yii::error('Платёж с данным номером уже обработан');
            throw new BadRequestHttpException('Что-то пошло не так. Сообщите нам. Спасибо за сотрудничество.');
        }

        // read the post from PayPal system and add 'cmd'
        $req = 'cmd=_notify-validate';
        foreach($myPost as $key => $value){
            $value = urlencode($value);
            $req .= "&$key=$value";
        }

//    $paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
        $paypal_url = "https://www.paypal.com/cgi-bin/webscr";

        $client = new Client();
        $response = $client->post($paypal_url, [
            'query' => $req
        ]);

        if($response->getStatusCode() == 200){
            $res = $response->getBody()->getContents();

            if(strcmp($res, "VERIFIED") == 0){
                // check whether the payment_status is Completed
                if($myPost['payment_status'] == 'Completed' && $myPost['receiver_email'] == Yii::$app->paypal->login && $myPost['mc_gross'] == $payment->amount){
                    $payment->status = Payment::STATUS_PAID;
                    $payment->wallet_number = $myPost['payer_email'];
                    $payment->save();
                } else{
                    $payment->status = Payment::STATUS_REFUSED;
                    $payment->save();
                    Yii::error('Неверные параметры платежа');
                }

                // assign posted variables to local variables
                //$item_name = $_POST['item_name'];
                //$item_number = $_POST['item_number'];
                //$payment_status = $_POST['payment_status'];
                //$payment_amount = $_POST['mc_gross'];
                //$payment_currency = $_POST['mc_currency'];
                //$txn_id = $_POST['txn_id'];
                //$receiver_email = $_POST['receiver_email'];
                //$payer_email = $_POST['payer_email'];

            } else if(strcmp($res, "INVALID") == 0){
                // Business logic here which deals with invalid IPN messages
                Yii::error('Верификация IPN не пройдена');
                $payment->status = Payment::STATUS_REFUSED;
                $payment->save();
            }
        }

    }

    public function actionJuridical(){
        $form = new JuridicalForm();

        if($form->load(Yii::$app->request->post()) && $form->process()){
            Yii::$app->session->setFlash('success', 'Счёт создан. Квитанцию можно распечатать ниже.');
            return $this->redirect(['/cabinet/payments/view', 'id' => $form->payment_id]);
        }

        throw new BadRequestHttpException('Запрашиваемая страница не найдена');
    }

    public function actionUnitpayCallback(){

    }
}
