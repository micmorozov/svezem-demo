<?php
use common\models\Payment;
use common\models\Setting;
use common\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $user User */
/* @var $topPayments Payment[] */
/* @var $minPrices array */
$user = Yii::$app->user->identity;
$this->title = "Личный кабинет - платные услуги";
?>

<h2>Платные услуги</h2>

<div class="paid-services">
  <div class="item-service">
    <div class="icon-service">
      <img src="/images/icon-top.png" alt="">
    </div>
    <div class="info-service">
      <p class="title"><b>Закрепить в топ.</b> <span>Больше просмотров - больше откликов и предложений</span></p>

      <p>Объявления отмеченные данной услугой будут размещены в первых местах поиска по
        соответствующему направлению перевозки.</p>

      <p>
        <a href="<?= Url::toRoute(['/cabinet/service/top']); ?>" class="btn btn-blue">Заказать услугу</a>
        <b class="price">от <?= round($minPrices['top'], 2)?> руб в сутки</b>
      </p>
      <?php if (count($topCargo) + count($topTransport) > 0): ?>
        <div class="fixed-adv-wrap">
          <p>Закреплено объявлений: <?= count($topCargo) + count($topTransport) ?>
            <a href="javascript://" class="pull-ads">(показать)</a>
          </p>

          <div class="fixed-adv">
            <?php
            foreach ($topCargo as $cargo) {
              echo $this->render('_top_item_cargo', ['model' => $cargo]);
            }

            foreach ($topTransport as $transport) {
              echo $this->render('_top_item_transport', ['model' => $transport]);
            }
            ?>
          </div>
        </div>
      <?php else: ?>
        <!-- div class="fixed-adv-wrap">
          <p>Закреплено объявлений: 0</p>
        </div -->
      <?php endif; ?>
    </div>
  </div>
  <div class="item-service">
    <div class="icon-service">
      <img src="/images/icon-sms.png" alt="">
    </div>
    <div class="info-service">
      <p class="title"><b>СМС уведомления.</b> <span>Будь всегда в курсе событий</span></p>

      <p>С этой услугой вся важная информация касающаяся Вашего профиля будет продублирована на Ваш мобильный телефон</p>

      <p>
        <a href="<?= Url::toRoute(['/cabinet/service/sms']); ?>" class="btn btn-blue">Заказать услугу</a>
        <b class="price">от <?= round($minPrices['sms-notify'], 2)?> руб за смс</b>
      </p>

      <?php
      $sender_free_sms_untill = 0;
      if( $user->senderProfile ){
        $sender_free_sms_untill = strtotime($user->senderProfile->free_sms_untill);
      }
      if ($user->senderProfile && $user->senderProfile->contact_phone != '' && ( $user->senderProfile->sms_amount || $sender_free_sms_untill > time() )): ?>
        <div class="item-fixed-adv">
          <div class="item-left">
            <p>
              <span>Профиль отправителя:</span>
              <?= $user->senderProfile->contact_phone ?>
            </p>
          </div>
          <div class="item-right">
            <b>Остаток: <?= $user->senderProfile->sms_amount ?> смс</b>
            <?php if( $sender_free_sms_untill > time() ): ?>
              <b>(бесплатно до <?= date('d.m.Y', $sender_free_sms_untill) ?>)</b>
            <?php endif ?>
            <?= Html::a('ПОПОЛНИТЬ', ['/cabinet/service/sms', 'profile_id' => $user->senderProfile->id], ['class' => 'btn btn-blue']) ?>
          </div>
        </div>
      <?php endif; ?>
      <?php
      $transporter_free_sms_untill = 0;
      if( $user->transporterProfile ){
        $transporter_free_sms_untill = strtotime($user->transporterProfile->free_sms_untill);
      }
      ?>
      <?php if ($user->transporterProfile && $user->transporterProfile->contact_phone != '' && ($user->transporterProfile->sms_amount || $transporter_free_sms_untill > time())): ?>
        <div class="item-fixed-adv">
          <div class="item-left">
            <p>
              <span>Профиль перевозчика:</span>
              <?= $user->transporterProfile->contact_phone ?>
            </p>
          </div>
          <div class="item-right">
            <b>Остаток: <?= $user->transporterProfile->sms_amount ?> смс</b>
            <?php if( $transporter_free_sms_untill > time() ): ?>
              <b>(бесплатно до <?= date('d.m.Y', $transporter_free_sms_untill) ?>)</b>
            <?php endif ?>
            <?= Html::a('ПОПОЛНИТЬ', ['/cabinet/service/sms', 'profile_id' => $user->transporterProfile->id], ['class' => 'btn btn-blue']) ?>
          </div>
        </div>
      <?php endif; ?>


    </div>
  </div>
  <div class="item-service">
    <div class="icon-service">
      <img src="/images/icon-otkliki.png" alt="">
    </div>
    <div class="info-service">
      <p class="title"><b>Больше откликов.</b> <span>Делай больше предложений своим заказчикам</span></p>

      <p>Если Вам недостаточно базовых откликов, чтобы предложить свои услуги потенциальным клиентам, купите нужное количество или снимите ограничения на необходимый срок.</p>

      <p>
        <a href="<?= Url::toRoute(['/cabinet/service/offers']); ?>" class="btn btn-blue">Заказать услугу</a>
        <b class="price">
          от <?= round($minPrices['additional-answers'], 2)?> руб за отклик
          <br>
          от <?= round($minPrices['infinite-answers'], 2)?> руб в сутки
        </b>
      </p>

      <?php if ($user->transporterProfile): ?>
        <p>
          Максимум бесплатных откликов: <?= Setting::getValueByCode('offer-limit') ?>,
          использовано откликов: <?= count($user->transporterProfile->getActiveOffers()) ?>,
          остаток бесплатных откликов: <?= $user->transporterProfile->free_offers ?>
        </p>

        <?php if($user->transporterProfile->paid_offers > 0):?>
          <div class="item-fixed-adv">
            <div class="item-left">
              <p>
                <span>Платные, разовые отклики:</span>
              </p>
            </div>
            <div class="item-right">
              <b>Остаток: <?= $user->transporterProfile->paid_offers ?> шт</b>
              <?= Html::a('Пополнить', ['/cabinet/service/offers', 'service_type' => 'additional'], ['class' => 'btn btn-blue']) ?>
            </div>
          </div>
        <?php endif;?>

        <?php if($user->transporterProfile->infinite_until > time()):?>
          <div class="item-fixed-adv">
            <div class="item-left">
              <p>
                <span>Отклики без ограничений:</span>
              </p>
            </div>
            <div class="item-right">
              <b><?= isset($user->transporterProfile->infinite_until) ? "До " . Yii::$app->formatter->asDate($user->transporterProfile->infinite_until, 'dd.MM.y') : "Не включено" ?></b>
              <?= Html::a('Продлить', ['/cabinet/service/offers', 'service_type' => 'infinite'], ['class' => 'btn btn-blue']) ?>
            </div>
          </div>
        <?php endif;?>

      <?php endif; ?>
    </div>
  </div>
</div>
