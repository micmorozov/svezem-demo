<?php

namespace common\models;

use yii\db\ActiveQuery;

interface FromToLocationInterface
{
    public function getCityFrom(): ActiveQuery;

    public function getCityTo(): ActiveQuery;
}