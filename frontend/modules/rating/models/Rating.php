<?php
namespace frontend\modules\rating\models;

use frontend\modules\rating\Module;
use frontend\modules\rating\storages\StorageInterface;
use yii\base\Model;
use Yii;

class Rating extends Model{

    public $id;
    public $score;
    public $sum;
    public $readOnly;

    /** @var $storage StorageInterface */
    protected $storage;

    public function rules(){
        return [
            ['score', 'integer', 'min'=>1, 'max'=>5, 'skipOnEmpty'=>false]
        ];
    }

    public function init(){
        /** @var Module $ratingModule */
        $ratingModule = Yii::$app->getModule('rating');

        $this->storage = $ratingModule->storage;

        parent::init();
    }

    public function getRatingId(){
        return $this->id;
    }

    protected function fetch(){
        $voteSum = $this->storage->get($this->getRatingId());

        $this->score = $voteSum['score'];
        $this->sum = $voteSum['sum'];

        $this->score = sprintf('%.2f', $this->score);

        //Значения по умолчанию
        if( $this->score == 0 ){
            $this->score = 5;
            $this->sum = 1;
        }

        $this->readOnly = $this->storage->isSet($this->getRatingId());
    }

    public function save(){
        if( !$this->validate() )
            return false;

        if( $this->storage->isSet($this->getRatingId()) ){
            $this->addError('score', 'Голос уже учтен');
            return false;
        }

        $res = $this->storage->save($this->getRatingId(), $this->score);

        if( !$res ){
            $this->addError('score', 'Не удалось сохранить голос');
            return false;
        }

        //Обновляем модель
        $this->score = $res['score'];
        $this->score = sprintf('%.2f', $this->score);
        $this->sum = $res['sum'];
        $this->readOnly = true;

        return true;
    }

    static public function find($id){
        $model = new self();
        $model->id = $id;
        $model->fetch();
        return $model;
    }
}