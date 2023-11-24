<?php

namespace frontend\modules\cabinet\controllers;

// reference the Dompdf namespace
use common\models\Payment;
use Dompdf\Dompdf;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * InvoiceController.
 */
class InvoiceController extends DefaultController
{

  public function actionDownload($payment_id) {

    /* @var $payment Payment*/
    $payment = Payment::findOne($payment_id);
    if ($payment === null || $payment->status != Payment::STATUS_JURIDICAL_PROCESS || $payment->created_by != userId()){
      throw new NotFoundHttpException('Запрашиваемая страница не найдена.');
    }

// instantiate and use the dompdf class
    $dompdf = new Dompdf();
    
    $html = $this->renderPartial('juridical.php', ['payment' => $payment]);
    $dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
    $dompdf->setPaper('A4', 'landscape');

// Render the HTML as PDF
    $dompdf->render();

// Output the generated PDF to Browser
    $dompdf->stream('invoice_' . $payment_id);
  }

}
