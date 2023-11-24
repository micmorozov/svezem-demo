<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 26.03.18
 * Time: 12:41
 */

namespace console\controllers;

use mohorev\file\UploadImageBehavior;
use Yii;
use common\models\CargoCategory;
use common\models\City;
use common\models\Profile;
use common\models\Transport;
use common\models\TransportEstimate;
use frontend\modules\account\models\SignupForm;
use yii\behaviors\BlameableBehavior;
use yii\console\Controller;
use yii\console\ExitCode;
use common\models\User;
use yii\helpers\FileHelper;

class AvitoController extends Controller
{
    protected $keyList = [
        'phone',
        'contact_person',
        'type',
        'price',
        'city',
        'img_links',
        'description'
    ];

    public function actionParse($fileName){
        $filePath = __DIR__."/avito/$fileName";
        $delimiter = ';';

        $file = fopen($filePath, "r") or exit("Unable to open file!");

        $counter = 0;
        while(!feof($file))
        {
            $line = fgets($file);
            $csvLine = explode($delimiter, $line);
            $csvLine = $this->combine($this->keyList,$csvLine);

            //избавляемся от кавычек
            $csvLine = array_map(function($item) {
                return trim($item, " \"'\n");
            }, $csvLine);

            //print_r($csvLine);die;

            if( !$csvLine ) continue;

            if( !$this->save($csvLine) ){
                Yii::error(print_r($csvLine,1), 'AvitoController.Parse');
                Yii::getLogger()->flush(true);
            }
            else{
                $counter++;
                echo "count: ".$counter."\n";
            }

        }
        fclose($file);

        return ExitCode::OK;
    }

    protected function save($csvLine){
        $signup = new SignupForm();
        $signup->email = $csvLine['phone'];

        //фразу "Контактное лицо:" удаляем из строки
        //$contact_person = preg_replace("/Контактное лицо:/", '', $csvLine['contact_person']);

        $signup->contact_person = $csvLine['contact_person'];

        /** @var City $city */
        $city = City::findOne(['title_ru'=>$csvLine['city']]);
        if( !$city ){
            echo "Не найден город ".$csvLine['city']."\n";
            Yii::error("Не найден город ".$csvLine['city'], 'AvitoController.Parse');
            return false;
        }

        $signup->city_id = $city->id;

        $signup->types = $this->getType($csvLine['type']);

        //создание транспорта
        $transport = new Transport();
        $transport->cityFromIds = $city->id;
        $transport->cityToIds = $city->id;
        $this->loadTransport($transport, $csvLine);
        if( !$transport->validate() ) {
            Yii::error(print_r($transport->getErrors(),1), 'AvitoController.Parse');
            return false;
        }

        //Устанавливаем шаблон отправки сообщения регистрации пользователя
        User::$createUserTemplate = ['tpl'=>"newTransportFreeSubscribe", "params"=>['transport'=>$transport]];

        if( !$user = $signup->signup() ) {
            Yii::error(print_r($signup->getErrors(), 1), 'AvitoController.Parse');
            return false;
        }

        $profile = $user->createTransporterProfile($signup->types, [
            'contact_person' => $signup->contact_person,
            'city_id' => $signup->city_id
        ]);

        if( !$profile ){
            return false;
        }

        //поле created_by устанавливается при помощи Behavior
        //чтобы задать его, не авторизуя пользователя,
        //задаем значение ИД найденного/созданного пользователя
        /** @var BlameableBehavior $Behavior */
        $Behavior = $transport->getBehavior('BlameableBehavior');
        if( $Behavior )
            $Behavior->value = $profile->created_by;
        $transport->profile_id = $profile->id;

        if( !$transport->save() ){
            Yii::error(print_r($transport->getErrors(), 1), 'AvitoController.Parse');
            return false;
        }

        //загружаем фото
        $this->uploadImage($transport, $csvLine);

        return true;
    }

    protected function combine($keys, $values){
        $diff = count($values) - count($keys);

        if( $diff < 0 )
            return false;

        if( $diff > 0 ){
            for($i=0; $i<$diff;$i++){
                $keys[] = 'ext'.$i;
            }
        }

        return array_combine($keys, $values);
    }

    protected function getType($type){
        $result = Profile::TYPE_TRANSPORTER_PRIVATE;
        switch($type){
            case 'Компания':
            case 'Магазин':
                $result = Profile::TYPE_TRANSPORTER_JURIDICAL;
                break;

            case 'Контактное лицо':
                $result = Profile::TYPE_TRANSPORTER_PRIVATE;
                break;
        }

        return $result;
    }

    /**
     * @param Transport $transport
     * @param array $csvLine
     */
    protected function loadTransport(&$transport, $csvLine){
        $transport->description = $csvLine['description'];
        $csvLine['price'] = $csvLine['price'] > 0 ? $csvLine['price'] : 300;
        $transport->price_from = $csvLine['price'];

        if( $csvLine['price'] <= 30 )
            $estimateName = 'км';
        elseif( $csvLine['price'] > 2000 )
            $estimateName = 'услугу';
        else
            $estimateName = 'час';

        //Стоимость перевозки за
        /** @var TransportEstimate $estimate */
        $estimate = TransportEstimate::findOne(['name'=>$estimateName]);
        $transport->payment_estimate = $estimate->id;

        //Устанавливаем Способ погрузки
        /** @var CargoCategory $laodMethod */
        $laodMethod = CargoCategory::findOne(['category'=>'Другое']);
        $transport->loadMethodIds = [$laodMethod->id];

        //Устанавливаем категорию "Импорт"
        $category1 = CargoCategory::findOne(['category'=>'Перевозка вещей']);
        $category2 = CargoCategory::findOne(['category'=>'Перевозка с грузчиками']);
        $transport->cargoCategoryIds = [$category1->id, $category2->id];

        //Устанавливаем Вид автотранспорта
        $transportTypeId = CargoCategory::findOne(['category'=>'Перевозка газелью']);
        $transport->transportTypeId = $transportTypeId->id;
    }

    /**
     * @param Transport $transport
     * @param $csvLine
     */
    protected function uploadImage($transport, $csvLine){
        /** @var UploadImageBehavior $behavior */
        $behavior = $transport->getBehavior('images');

        $imgs = explode(', ', $csvLine['img_links']);

        if( !isset( $imgs[0] ) || $imgs[0] == '' )
            return ;

        $downloadPath = $imgs[0];
        $basename = basename($downloadPath);

        $uploadDir = Yii::getAlias('@frontend/web/uploads/transport/'.$transport->id);
        FileHelper::createDirectory($uploadDir);

        $uploadPath = $uploadDir."/".$basename;

        file_put_contents($uploadPath, fopen($downloadPath, 'r'));

        $transport->image = $basename;

        $path = $behavior->getUploadPath($behavior->attribute);

        foreach ($behavior->thumbs as $profile => $config) {
            $thumbPath = $behavior->getThumbUploadPath($behavior->attribute, $profile);
            if ($thumbPath !== null) {
                if (!FileHelper::createDirectory(dirname($thumbPath))) {
                    Yii::error("Directory specified in 'thumbPath' attribute doesn't exist or cannot be created.", 'AvitoController.Parse');
                }
                if (!is_file($thumbPath)) {
                    $behavior->generateImageThumb($config, $path, $thumbPath);
                }
            }
        }

        Transport::updateAll(['image'=>$transport->image], "id=".$transport->id);
    }
}