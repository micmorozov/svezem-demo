<?php
/** @var Cargo[] $model */

use common\models\Cargo;

foreach($models as $model){
    echo $this->render('/search/_cargo_item', ['model'=>$model]);
}