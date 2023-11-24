<?php
use common\models\PaymentSystem;
use common\models\ServiceRate;
use yii\bootstrap\Html;
use yii\helpers\Url;

/** @var integer $payment_id */
/** @var string $service_type */
/** @var ServiceRate[] $serviceRatesInfinite */
/** @var ServiceRate[] $serviceRatesAdditional */
/** @var PaymentSystem[] $paymentSystems */

$selection = $service_type ? "#service-type-" . $service_type . "-styler" : null;
$this->title = "Личный кабинет - Платные услуги";
?>

<h2>Платные услуги</h2>

<div class="bl-white messages">
  <div class="item-service">
    <div class="icon-service">
      <img src="/images/icon-otkliki.png" alt="">
    </div>
    <div class="info-service">
      <p class="title">
        <b>Делайте больше предложений – получайте больше клиентов</b>
      </p>
      <p>
        Если Вам недостаточно базовых откликов, чтобы предложить свои услуги потенциальным клиентам, купите нужное количество или снимите ограничения на необходимый срок.
      </p>
      <p class="number-step">
        <b>1. Выберите тип услуги</b>
      </p>
      <div class="select-block">
        <select id="dd-service-type" class="selectbox">
          <option id="service-type-additional" value="additional">additional</option>
          <option id="service-type-infinite" value="infinite">infinite</option>
        </select>
        <div class="container-select">
          <div class="select-item service-type-additional">
            <div class="item-left">
              <p>
                <b>Разовые отклики</b>
              </p>
              <p>
                Позволяет разместить купленное количество откликов сверх лимита
              </p>
            </div>
          </div>
          <div class="select-item service-type-infinite">
            <div class="item-left">
              <p>
                <b>Отклики без ограничений</b>
              </p>
              <p>
                Снимает ограничение количества откликов на определенное время
              </p>
            </div>
          </div>
        </div>
      </div>

      <p class="number-step">
        <b>2. Выберите тариф</b>
      </p>
      <div class="select-block service-rates additional active">
        <select class="selectbox">
          <?php if(count($serviceRatesAdditional)): ?>
            <?php foreach($serviceRatesAdditional as $service_rate): ?>
              <option id="sr-ad-<?= $service_rate->id ?>" value="<?= $service_rate->id ?>"></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div class="container-select">
          <?php if(count($serviceRatesAdditional)): ?>
            <?php foreach($serviceRatesAdditional as $service_rate): ?>
              <div class="select-item sr-ad-<?= $service_rate->id?>">
                <p>
                  <b>+ <?= $service_rate->amount?> откликов</b>
                  (1 отклик = <b><?= number_format($service_rate->price/$service_rate->amount, 2, '.', ' ')?> руб.</b>)
                </p>
                <p>
                  Стоимость: <b class="price-blue"><span class="price-value"><?= number_format($service_rate->price, 2, '.', ' ')?></span> руб.</b>
                </p>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      <div class="select-block service-rates infinite">
        <select class="selectbox">
          <?php if(count($serviceRatesInfinite)): ?>
            <?php foreach($serviceRatesInfinite as $service_rate): ?>
              <option id="sr-inf-<?= $service_rate->id ?>" value="<?= $service_rate->id ?>"></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <div class="container-select">
          <?php if(count($serviceRatesInfinite)): ?>
            <?php foreach($serviceRatesInfinite as $service_rate): ?>
              <div class="select-item sr-inf-<?= $service_rate->id?>">
                <p>
                  <b>Неограниченные отклики на <?= $service_rate->amount?> д.</b>
                  (стоимость <b><?= number_format($service_rate->price/$service_rate->amount, 2, '.', ' ')?> руб. в день</b>)
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
$this->registerCss('
  div.service-rates{
      display: none;
  }

  div.service-rates.active {
      display: block;
  }
');

$this->registerJs("

  $(document).on('change', '#dd-service-type', function(e){
    var type = $(this).find('option:selected').val();
    $('div.service-rates').removeClass('active');
    $('div.service-rates.' + type).addClass('active');
  });

  $(document).on('change', 'select', function(e){
    updateForm();
  });
  
  $('#paybtn').on('click', function(sender){      
    if($(this).hasClass('btn')){
      $(this).prop('disabled', true);
      $(this).addClass('btn-grey');
    }
    
    // Показываем лоадер
    $('div.pjax-loader-blue').css('display', 'inline-block');
     
    var form_id = $('#dd-payment-types').find('option:selected').val();    
    var service_rate_id = $('div.service-rates.active').find('option:selected').val();
    
   $.get('" . Url::toRoute(['/cabinet/service/get-ajax-form']) . "', {
      form_id: form_id,      
      service_rate_id: service_rate_id,
      profile_id: " . $profile_id . "
    })
    .done(function( data ) {    
      $('#payment-container').html(data);
    });
  });

  $(function() {
    if ('" . $selection . "' != ''){
      $(this).find('" . $selection . "').trigger('click');
    }
    else {
      $('#dd-service-type').closest('div.select-block').find('li.selected').trigger('click');
    }
    updateForm();
  });

  function updateForm(){
    var price = $('div.service-rates.active li.selected').find('span.price-value').html();

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
");
