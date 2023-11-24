<?php
/**
 * Created by PhpStorm.
 * User: Морозов Михаил
 * Date: 01.07.2016
 * Time: 15:59
 */

namespace common\components;

class User extends \yii\web\User
{
	protected function afterLogin($identity, $cookieBased, $duration){
		parent::afterLogin($identity, $cookieBased, $duration);

		// Сохраняем время последнего входа в систему
		$this->identity->touch('lastlogin');
	}
}