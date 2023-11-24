<?php
use common\models\PaymentSystem;
use common\models\Profile;
use common\models\ServiceRate;
use yii\bootstrap\Html;
use yii\helpers\Url;

/** @var integer $payment_id */
/** @var integer $profile_id */
/** @var ServiceRate[] $serviceRates */
/** @var PaymentSystem[] $paymentSystems */
/** @var Profile[] $profiles */
$selection = $profile_id ? "#profile-" . $profile_id . "-styler" : null;
$this->title = "Личный кабинет - Платные услуги";
?>

<h2>Платные услуги</h2>

<div class="bl-white messages">
  <div class="item-service">
    <div class="icon-service">
      <img src="/images/icon-sms.png" alt="">
    </div>
    <div class="info-service">
      <p class="title">
        <b>Всегда в курсе событий – получайте смс уведомления о поступлении предложений и других важных событиях</b>
      </p>
      <p>
        С этой услугой вся важная информация касающаяся Вашего профиля будет продублирована на Ваш мобильный телефон
      </p>
      <p class="number-step">
        <b>1. Выберите профиль для отправки смс уведомлений</b>
      </p>
      <div class="select-block">
        <select id="dd-profile-id" class="selectbox">
          <?php if(count($profiles)): ?>
            <?php foreach($profiles as $profile): ?>
              <option id="profile-<?= $profile->id ?>" value="<?= $profile->id ?>"><?= e($profile->name); ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>

        <div class="container-select">
          <?php if(count($profiles)): ?>
            <?php foreach($profiles as $profile): ?>
              <div class="select-item profile-<?= $profile->id ?>">
                <div class="item-left">
                  <p>
                    <b><?= $profile->type == Profile::TYPE_SENDER ? "Профиль отправителя" : "Профиль перевозчика"?></b>
                  </p>
                  <p>
                    <?= $profile->contact_phone?>
                  </p>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <p class="number-step">
        <b>2. Выберите тариф</b>
      </p>
      <div class="select-block service-rates">
        <select id="dd-service-rates" class="selectbox">
          <?php if(count($serviceRates)): ?>
            <?php foreach($serviceRates as $service_rate): ?>
              <option id="sr-<?= $service_rate->id ?>" value="<?= $service_rate->id ?>"></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div class="container-select">
          <?php if(count($serviceRates)): ?>
            <?php foreach($serviceRates as $service_rate): ?>
              <div class="select-item sr-<?= $service_rate->id?>">
                <p>
                  <b><?= $service_rate->amount?> смс сообщений</b>
                  (1 смс = <b><?= number_format($service_rate->price/$service_rate->amount, 2, '.', ' ')?> руб.</b>)
                </p>
                <p>
                  Стоимость: <b class="price-blue"><span class="price-value"><?= number_format($service_rate->price, 2, '.', ' ')?></span> руб.</b>
                </p>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <p class="number-step">
        <b>3. Выберите способ оплаты</b>
      </p>
      <div class="select-block">
        <select id="dd-payment-types" class="selectbox">
          <?php if(count($paymentSystems)): ?>
            <?php foreach($paymentSystems as $payment_system): ?>
              <option id="ps-<?= $payment_system->code ?>" value="<?= $payment_system->code ?>" data-rate="<?= $payment_system->rate ?>"></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div class="container-select">
          <?php if(count($paymentSystems)): ?>
            <?php foreach($paymentSystems as $payment_system): ?>
              <div class="select-item ps-<?= $payment_system->code?>">
                <p>
                  <b><?= $payment_system->name?></b>
                </p>
                <p>
                  К оплате
                  <b class="price-blue"><span class="price-value"></span> <?= $payment_system->currency_short_name?></b>
                </p>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      <div>
        <?= Html::submitButton('Оплатить', ['class' => 'btn btn-blue', 'id'=>'paybtn']) ?> <div class="pjax-loader-blue"></div>
      </div>
      <div id="payment-container"></div>
    </div>
  </div>
</div>

<?php
$this->registerJs("
  $(document).on('change', 'select', function(e){
    updateForm();
  });

  $(function() {
    if ('" . $selection . "' != ''){
      $(this).find('" . $selection . "').trigger('click');
    }
    else {
      $('#dd-profile-id').closest('div.select-block').find('li.selected').trigger('click');
    }
    updateForm();
  });

  function updateForm(){
    var price = $('div.service-rates li.selected').find('span.price-value').html();

    $.each($('#dd-payment-types option'), function( i, item ) {
      var converted_price = price;
      var rate = $(item).data('rate');
      if (rate != 1){
        converted_price = converted_price.replace(/ /g,'');
        converted_price = Math.round(parseFloat(converted_price) / parseFloat(rate) * 100 ) / 100;
        converted_price = converted_price.toString().split( /(?=(?:\d{3})+$)/ ).join(' ');
      }
      $('#ps-' + $(item).val() + '-styler span.price-value').html(converted_price);
      $('div.ps-' + $(item).val() + ' span.price-value').html(converted_price);
    });
  }
  
   $('#paybtn').on('click', function(sender){      
      if($(this).hasClass('btn')){
        $(this).prop('disabled', true);
        $(this).addClass('btn-grey');
      }
      
      // Показываем лоадер
      $('div.pjax-loader-blue').css('display', 'inline-block');
      
      var form_id = $('#dd-payment-types').find('option:selected').val();
      var service_rate_id = $('#dd-service-rates').find('option:selected').val();
      var profile_id = $('#dd-profile-id').find('option:selected').val();
      
      $.get('" . Url::toRoute(['/cabinet/service/get-ajax-form']) . "', {
          form_id: form_id,      
          service_rate_id: service_rate_id,
          profile_id: profile_id
        })
        .done(function( data ) {
          $('#payment-container').html(data);
        });
    });
");
