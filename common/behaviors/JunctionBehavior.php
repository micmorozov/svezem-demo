<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 06.10.17
 * Time: 14:42
 */

namespace common\behaviors;

use \yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;

class JunctionBehavior extends Behavior
{
    public $association;

    public function events(){
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterUpdate',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate'
        ];
    }

    public function afterUpdate($event){
        foreach($this->association as $assoc){
            /* @var $class ActiveRecordInterface */
            $attrName = $assoc[0];
            $class = $assoc[1];
            $relation = $assoc[2];

            $targetAttribute = isset($assoc['targetAttribute'])?$assoc['targetAttribute']:'id';
            $extraColumns = isset($assoc['extraColumns'])?$assoc['extraColumns']:[];

            $extraColumns = $this->proccessExtraColumns($extraColumns);

            $targetModels = $class::find()
                ->where([$targetAttribute=>$this->owner->$attrName])
                ->all();

            $this->owner->unlinkAll($relation, true);

            foreach($targetModels as $tModel){
                $this->owner->link($relation, $tModel, $extraColumns);
            }
        }
    }

    /**
     * @param array $extraColumns
     * @return array mixed
     */
    protected function proccessExtraColumns($extraColumns){
        foreach($extraColumns as $column=>$value){
            if(is_callable($value)){
                $extraColumns[$column] = $value();
            }
        }
        return $extraColumns;
    }
}