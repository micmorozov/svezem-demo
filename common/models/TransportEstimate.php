<?php

namespace common\models;
use yii\db\ActiveRecord;

class TransportEstimate extends ActiveRecord
{
	public static function tableName() {
		return '{{transport_estimate}}';
	}
}
