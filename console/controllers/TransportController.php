<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 03.10.18
 * Time: 11:03
 */

namespace console\controllers;

use backend\models\TransportImport;
use yii\console\Controller;

class TransportController extends Controller
{
    public function actionImport(){
        $query = TransportImport::find()
            ->where(['status'=>TransportImport::STATUS_WAIT])
            ->orderBy('id')
            ->limit(500);

        /** @var TransportImport[] $imports */
        while($imports = $query->all()){
            $query->offset += $query->limit;

            foreach($imports as $import){
                $import->import();
            }
        }
    }
}