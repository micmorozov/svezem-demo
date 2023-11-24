<?php

namespace frontend\modules\info\controllers;

use common\behaviors\NoSubdomain;
use common\models\LocationInterface;
use common\models\Service;
use Yii;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class SitemapController extends Controller
{
    public function actionIndex(LocationInterface $location = null)
    {
        /** @var LocationInterface $domainCity */
        $domainCity = Yii::$app->getBehavior('geo')->domainCity;
        if($domainCity){
            return Yii::$app->getResponse()->redirect('https://'.Yii::getAlias('@domain') .
                Url::toRoute(['/info/sitemap/index', 'location' => $domainCity]), 301, false);
        }

        $sitemapList = [];
        if($location) {
            $sitemapList = @file(__DIR__ . '/../sitemaps/' . $location->getCode() . '.txt');
        }

        if(!$sitemapList){
            throw new NotFoundHttpException('Страница не найдена');
        }

        return $this->render('index', [
            'sitemapList' => $sitemapList
        ]);
    }
}
