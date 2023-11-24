<?php
/**
 * Created by PhpStorm.
 * User: andrei
 * Date: 27.10.18
 * Time: 12:48
 */

namespace frontend\modules\payment\widget;

use yii\base\Widget;

/**
 * Class PromoPayment
 * @package frontend\widgets
 */
class PromoPayment extends Widget
{
    public $label;
    public $url = null;
    public $mainServices;
    public $services = [];
    public $item_id;

    /** @var bool Выбор только одной услуги */
    public $onlyOne = false;

    /**
     * Только авторизованный пользователь
     * @var bool $authUserOnly
     */
    public $authUserOnly = false;

    /**
     * @inheritdoc
     */
    public function run()
    {
        PromoPaymentAsset::register($this->view);
        //start должен вызываться имеено здесь, а не в скрипте
        //иначе, если блок вставлен в pjax, не будут обновляться цены
        $this->view->registerJs('if(window.PromoPayment)PromoPayment.start();');

        return $this->render('promo-payment', [
            'label' => $this->label,
            'mainServices' => $this->mainServices,
            'services' => $this->services,
            'url' => $this->url,
            'item_id' => $this->item_id,
            'onlyOne' => $this->onlyOne,
            'authUserOnly' => $this->authUserOnly
        ]);
    }
}
