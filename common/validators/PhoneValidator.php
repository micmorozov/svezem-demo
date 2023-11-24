<?php
namespace common\validators;

use Yii;
use yii\validators\Validator;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;
use Exception;


/**
 * Phone validator class that validates phone numbers for given 
 * country and formats.
 * Country codes and attributes value should be ISO 3166-1 alpha-2 codes
 * @property string $countryAttribute The country code attribute of model
 * @property string $country The country is fixed
 * @property bool $strict If country is not set or selected adds error
 * @property bool $format If phone number is valid formats value with internation phone number 
 */
class PhoneValidator extends Validator
{
    public $strict=false;
    public $countryAttribute;
    public $country;
    public $format=true;
    public $min = 3;

    public function validateAttribute($model, $attribute)
    {
        if( $this->skipOnEmpty && strlen($model->$attribute) < $this->min )
            return true;

        // if countryAttribute is set
        if(!isset($country) && isset($this->countryAttribute)){
            $countryAttribute=$this->countryAttribute;
            $country=$model->$countryAttribute;
        }

        // if country is fixed
        if(!isset($country) && isset($this->country)){
            $country=$this->country;
        }
   	
        // if none select from our models with best effort
        if(!isset($country) && isset($model->country_code))
    		$country=$model->country_code;

        if(!isset($country) && isset($model->country))
            $country=$model->country;
        

        // if none and strict
    	if(!isset($country) && $this->strict){
    		 $this->addError($model, $attribute, Yii::t('app','Для проверки номера необходим код страны'));
    		 return false;
    	}

        $phoneUtil = PhoneNumberUtil::getInstance();

        $phoneNumber = $model->$attribute;

        //Ориентируемся на РФ
        //удаляем все кроме цифр
        $phoneNumber = preg_replace("/[^0-9]/", '', $phoneNumber);
        //8 в начале заменяем на 7
        $phoneNumber = preg_replace("/^8/", '7', $phoneNumber);
        //дописываем +
        $phoneNumber = "+".$phoneNumber;

        if(!isset($country)){
            //ЗАГЛУШКА
            //если код страны не задан, определяем его средствами библиотеки
            try {
                $numberProto = $phoneUtil->parse($phoneNumber, '');
                $regionCode = $numberProto->getCountryCode();
                $country = $phoneUtil->getRegionCodeForCountryCode($regionCode);
            }
            catch(Exception $e){
                $this->addError($model, $attribute, Yii::t('app', 'Номер телефона указан некорректно'));
                return false;
            }

            //утанавливаем у модели подходящий код страны
            if( isset($countryAttribute) )
                $model->$countryAttribute = $country;

            /*
        	$model->$attribute = $this->phoneFormat($model->$attribute);
        	
            return true;*/
        }

    	try {
				$numberProto = $phoneUtil->parse($phoneNumber, $country);
                if($phoneUtil->isValidNumber($numberProto)){
                    if($this->format==true)
                    
                	//$model->$attribute = $phoneUtil->format($numberProto, PhoneNumberFormat::INTERNATIONAL);
                    
                    $model->$attribute = $this->phoneFormat($phoneNumber);

                	return true;
                }
                else{
                	$this->addError($model, $attribute, Yii::t('app','Номер телефона указан некорректно'));
                	return false;
                }

        } catch (NumberParseException $e) {
        	$this->addError($model, $attribute, Yii::t('app','Неверный формат телефона'));
        } catch (Exception $e) {
            $this->addError($model, $attribute, Yii::t('app','Неверный формат телефона или код страны'));
        }   
    }

    public function validateValue($phoneNumber){
        $phoneUtil = PhoneNumberUtil::getInstance();

        //Ориентируемся на РФ
        //удаляем все кроме цифр
        $phoneNumber = preg_replace("/[^0-9]/", '', $phoneNumber);
        //8 в начале заменяем на 7
        $phoneNumber = preg_replace("/^8/", '7', $phoneNumber);
        //дописываем +
        $phoneNumber = "+".$phoneNumber;

        //ЗАГЛУШКА
        //если код страны не задан, определяем его средствами библиотеки
        try {
            $numberProto = $phoneUtil->parse($phoneNumber, '');
            $regionCode = $numberProto->getCountryCode();
            $country = $phoneUtil->getRegionCodeForCountryCode($regionCode);
        }
        catch(Exception $e){
            //$this->addError($model, $attribute, \Yii::t('app', 'Номер телефона указан некорректно'));
            return ['Номер телефона указан некорректно', []];
        }

        try {
            $numberProto = $phoneUtil->parse($phoneNumber, $country);
            if($phoneUtil->isValidNumber($numberProto)){
                return null;
            }
            else{
                return ['Номер телефона указан некорректно', []];
            }

        } catch (NumberParseException $e) {
            return ['Неверный формат телефона', []];
        } catch (Exception $e) {
            return ['Неверный формат телефона или код страны', []];
        }
    }

    /**
     * Преобразование телефона
     * @param unknown $phone
     * @return mixed
     */
    protected function phoneFormat($phone){
    	//убираем лишние символы
    	$phone = preg_replace("/[^0-9]/", "", $phone);
    	 
    	//если первая 8, то меняем на 7
    	$phone = preg_replace("/^8/", "7", $phone);
    	
    	return $phone;
    }
}