<?php
namespace console\models;

use frontend\modules\tk\models\Tk;
use yii\base\Exception;
use yii\helpers\Json;
use Yii;

/**
 * @package app\lib
 */
abstract class BaseCompany {

    const STATUS_SUCCESS = 1;
    const STATUS_ERROR = 2;

    public $id;
    public $name;
    public $email;
    public $phone;
    public $url;
    public $code;
    public $order_link;
    public $from_city_id;
    public $from_city_name;
    public $to_city_id;
    public $to_city_name;
    public $weight;
    public $width;
    public $height;
    public $depth; // (length)
    public $volume = null;
    public $socket_id;
    public $session_timestamp;
    public $cost;
    public $res_status = self::STATUS_ERROR;

    protected $_attributes = [];
    
    protected $last_error_code = 0;
    protected $last_error_msg = 0;

    public function __construct(){
        $this->init();
    }

    public function init(){
    }

    public function parse()
    {
        throw new Exception('Method not implemented.', 500);
    }

    public function setAttributes($workload)
    {
        /** @var array $workload */
        foreach($workload as $key => $item) {
            if(!is_array($item)) {
                if(property_exists($this, $key)) {
                    $this->{$key} = $item;
                    $this->_attributes[$key] = $item;
                }
            }
            else {
                $this->setAttributes($item);
            }
        }

        if( !$this->volume ) {
            $this->volume = floatval($this->width) * floatval($this->height) * floatval($this->depth);
        }
    }

    public function getAttributes(){
        return $this->_attributes;
    }

    public function sendPubSub()
    {
        if($this->res_status == self::STATUS_SUCCESS) {
            $tk = Tk::findOne($this->id);

            Yii::$app->redis->executeCommand('PUBLISH', [
                'channel' => 'pubsub',
                'message' => Json::encode([
                    'id' => $this->id,
                    'name' => $this->name,
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'url'   => $this->url,
                    'icon' => $tk->iconPath('preview'),
                    'cost' => doubleval($this->cost),
                    'socket_id' => $this->socket_id,
                    'session_timestamp' => $this->session_timestamp
                ])
            ]);

            //echo "SUCCESS! ".$this->cost."\n";
        }
        // Произошла ошибка получения цены, но нам всё равно нужно отправить socket_id,
        // чтобы progress_bar наростился
        else {
            Yii::$app->redis->executeCommand('PUBLISH', [
                'channel' => 'pubsub',
                'message' => Json::encode([
                    'tk_fail' => true,
                    'socket_id' => $this->socket_id,
                    'session_timestamp' => $this->session_timestamp
                ])
            ]);

            /*echo "ERROR\n";
            echo "Входные данные: ";print_r($this->getAttributes());
            echo "Ошибка: "; print_r($this->getLasError());*/

            Yii::error("Входные данные: ".print_r($this->getAttributes(), 1).
                "Ошибка: ".print_r($this->getLasError(),1), 'gearman.'.get_class($this) );
        }
    }
    
    protected function checkCost(){
    	if( $this->cost != 0 ){
    		$this->res_status = self::STATUS_SUCCESS;
    		return true;
    	}
    	else{
    		$this->last_error_code = 1;
    		$this->last_error_msg = 'Не удалось определить цену';
    		 
    		return false;
    	}
    }
    
    public function getLasError(){
    	return [
    		'error' => $this->last_error_code,
    		'err_msg' => $this->last_error_msg
    	];
    }
}