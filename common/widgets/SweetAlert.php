<?php
namespace common\widgets;

use Yii;
use yii\web\JsExpression;
use yii\web\View;

/** @var $view View */

class SweetAlert
{
  public static function process($view) {
    $options = [];
    $session = Yii::$app->session;
    $flashes = $session->getAllFlashes();

    $alertTypes = [
      'error' => 'alert-danger',
      'success' => 'alert-success',
      'warning' => 'alert-warning',
      'info' => 'alert-info',
    ];

    $titleTranslations = [    	    	  
      	'success' => 'Успешно!',
      	'error' => 'Ошибка!',
      	'warning' => 'Предупреждение',
      	'info' => 'Информация',
    ];

    foreach ($flashes as $type => $data) {
      if (isset($alertTypes[$type])) {
        $data = (array)$data;
        foreach ($data as $i => $message) {
          /* initialize css class for each alert box */
          $options['class'] = $alertTypes[$type];

          $view->registerJs("
            swal.fire('', '{$message}', '{$type}');
          ");
        }

      }
    }
  }
}
