<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 14.08.18
 * Time: 9:11
 */

namespace frontend\modules\info\controllers;

use common\behaviors\NoSubdomain;
use yii\web\Controller;

class LegalController extends Controller
{
    public function behaviors()
    {
        return [
            NoSubdomain::class
        ];
    }

    public function actionAdvice()
    {
        return $this->render('advice');
    }

    public function actionPublicOffer()
    {
        return $this->render('public-offer');
    }

    public function actionPrivacyPolicy()
    {
        return $this->render('privacy-policy');
    }

    public function actionCargoOwner()
    {
        return $this->render('cargo-owner');
    }
}
