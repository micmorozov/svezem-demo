<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 13.02.19
 * Time: 15:44
 */

namespace common\modules\Notify;

use common\modules\Notify\models\NotifyRule;
use yii\web\Controller;
use yii\web\Response;
use Yii;

class MessageController extends Controller
{
    public function actionGet($page)
    {
        $rules = NotifyRule::find()
            ->where(['page' => $page])
            ->orWhere(['page' => NotifyRule::ANY_PAGE])
            ->all();

        $resp = [];
        foreach ($rules as $rule) {
            if (!$rule->suitable) {
                continue;
            }

            $resp[] = [
                'options' => [
                    'message' => $rule->message,
                    'url' => $rule->url
                ],
                'settings' => [
                    'type' => $rule->type,
                    'delay' => $rule->delay
                ],
                'data' => [
                    'id' => $rule->id
                ]
            ];
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        return $resp;
    }
}