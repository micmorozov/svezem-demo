<?php

namespace frontend\modules\payment\controllers;

use common\models\Payment;
use common\models\PaymentRequisites;
use common\models\PaymentSystem;
use Endroid\QrCode\QrCode;
use frontend\modules\payment\components\QRPayment;
use ReflectionException;
use Yii;
use yii\base\InvalidConfigException;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

class JuridicalController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@']
                    ]
                ],
            ],
        ];
    }

    /**
     * @param $payment
     * @return string|Response
     * @throws BadRequestHttpException
     */
    public function actionRequisites($payment)
    {
        $payment = Payment::find()
            ->where([
                'and',
                ['id' => $payment],
                ['created_by' => Yii::$app->user->id],
                ['status' => Payment::STATUS_PENDING],
                ['payment_system_id' => PaymentSystem::SYS_JURIDICAL]
            ])
            ->one();

        if ( !$payment) {
            throw new BadRequestHttpException();
        }

        $model = PaymentRequisites::findOne(['userid' => $payment->created_by]);

        if ( !$model) {
            $model = new PaymentRequisites();
            $model->userid = $payment->created_by;
        }

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(Url::toRoute(['/payment/juridical/show', 'payment' => $payment->id]));
        }

        return $this->render('requisites', [
            'model' => $model
        ]);
    }

    public function actionShow($payment)
    {
        $payment = Payment::find()
            ->where([
                'and',
                ['id' => $payment],
                ['status' => Payment::STATUS_PENDING],
                ['created_by' => Yii::$app->user->id],
                ['payment_system_id' => PaymentSystem::SYS_JURIDICAL]
            ])
            ->one();

        if ( !$payment) {
            throw new BadRequestHttpException();
        }

        $requisites = PaymentRequisites::findOne($payment->created_by);

        if ( !$requisites) {
            throw new BadRequestHttpException();
        }

        $qr = $this->getQR($payment, $requisites, 210);

        return $this->render('show', [
            'payment' => $payment,
            'qr' => base64_encode($qr)
        ]);
    }

    public function actionReceipt($payment)
    {
        $payment = Payment::find()
            ->where([
                'and',
                ['id' => $payment],
                ['status' => Payment::STATUS_PENDING],
                ['created_by' => Yii::$app->user->id],
                ['payment_system_id' => PaymentSystem::SYS_JURIDICAL]
            ])
            ->one();

        if ( !$payment) {
            throw new BadRequestHttpException();
        }

        $requisites = PaymentRequisites::findOne($payment->created_by);

        if ( !$requisites) {
            throw new BadRequestHttpException();
        }

        $qr = $this->getQR($payment, $requisites, 145);

        return $this->renderPartial('receipt', [
            'payment' => $payment,
            'requisites' => $requisites,
            'qr' => base64_encode($qr)
        ]);
    }

    /**
     * @param $payment
     * @param $requisites
     * @param $size
     * @return string
     * @throws ReflectionException
     * @throws InvalidConfigException
     */
    private function getQR($payment, $requisites, $size)
    {
        $companyRequisites = Yii::$app->params['requisites'];

        $qr_payment = new QRPayment();
        $qr_payment->name = $companyRequisites['organization'];
        $qr_payment->personalAcc = $companyRequisites['account'];
        $qr_payment->bankName = $companyRequisites['bank'];
        $qr_payment->bic = $companyRequisites['bic'];
        $qr_payment->correspAcc = $companyRequisites['corr_account'];
        $qr_payment->sum = $payment->amount*100;
        $qr_payment->purpose = 'Оплата услуг svezem.ru по счету '. $payment->id. ' от '.Yii::$app->formatter->asDate($payment->created_at);
        $qr_payment->payeeINN = $companyRequisites['inn'];
        $qr_payment->payerINN = $requisites->inn;
        $qr_payment->persAcc = sprintf('%1$08d', $payment->id);
        $qr_payment->kpp = $companyRequisites['kpp'];
        $qr_payment->techCode = QRPayment::TECH_CODE_OTHER_SERVICES;

        $qrCode = new QrCode($qr_payment);
        $qrCode->setSize($size);

        $qrCode->setWriterByName('png');
        $qrCode->setMargin(0);
        $qrCode->setEncoding('UTF-8');
        $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
        $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
        $qrCode->setLogoSize(150, 200);
        $qrCode->setRoundBlockSize(true);
        $qrCode->setValidateResult(false);
        $qrCode->setWriterOptions(['exclude_xml_declaration' => true]);

        return $qrCode->writeString();
    }
}
