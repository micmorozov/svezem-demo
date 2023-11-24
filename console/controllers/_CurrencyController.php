<?php
/**
 * Created by PhpStorm.
 * Date: 14.07.2016
 * Time: 13:50
 */

namespace console\controllers;

use yii\console\Controller;
use common\models\PaymentSystem;
use console\models\CBRAgent;

class _CurrencyController extends Controller{

	public function actionUpdateRates(){
		$cbr = new CBRAgent();

		if ($cbr->load()){
			PaymentSystem::updateAll(['rate' => $cbr->get('USD')],['code' => 'wmz']);
		}
	}
}