<?php

use common\models\Profile;
use frontend\modules\cabinet\models\UserEditForm;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\widgets\ListView;

/* @var $this yii\web\View */
/* @var $dataProvider ActiveDataProvider */
/* @var $model UserEditForm */

$this->title = "Личный кабинет - настройки профиля";
?>
<main class="content">
    <div class="container">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b>Настройки профиля</b></h1>
        </div>
        <div class="bl-white profile-settings">
            <div class="main-profile">
                <?= $this->render('_user_edit', ['model' => $model]); ?>
                <div class="form-text">
                    <div class="form-text">
                        <?php if (!Yii::$app->user->identity->senderProfile) : ?>
                            <?= Html::a('Добавить профиль отправителя',
                                ['/cabinet/profile/create', 'type' => Profile::TYPE_SENDER]
                            ); ?>
                        <?php endif; ?>
                        <?php if (!Yii::$app->user->identity->transporterProfile) : ?>
                            <?= Html::a('Добавить профиль перевозчика',
                                ['/cabinet/profile/create', 'type' => Profile::TYPE_TRANSPORTER_NOT_SPECIFIED]
                            ); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?= ListView::widget([
                    'dataProvider' => $dataProvider,
                    'itemView' => '_profile_item',
                    'itemOptions' => [
                        'tag' => false
                    ],
                    'options' => [
                        'tag' => 'div',
                    ],
                    'layout' => '{items}',
                    'emptyText' => false
                ]);
                ?>
            </div>
        </div>
</main>
<?php

$this->registerCss("
  div.input {
    display: none;
  }
  div.selectbox {
    width: 165px;
  }
  #profile-transportertypeids {
    float: left;
  }
");

$this->registerJs("
    $(document).on('click', 'div.output a.change-field', function (){
        $('div.input').hide();
        $('div.output').show();
        $(this).parent().hide();
        $(this).parent().siblings().show();
        return false;
    });

    $(document).on('click', 'div.input button', function(event){
        if($(this).parent().find('div.form-group').hasClass('has-error')){
            return false;
        }
        //$(this).parent().hide();
        //$(this).parent().siblings().show();
    });

    $(document).on('click', '#input-image', function (){
        $(this).parent().find('#profile-imagefile').trigger('click');
        return false;
    });

	$(document).on('click', '.cancel', function (){
        $('div.input').hide();
        $('div.output').show();
		
        return false;
    });
		
    $(document).on('submit', 'form.profile-form', function(){
        var filename = $(this).find('#profile-imagefile').val();
        if(filename != ''){
            var extension = filename.split('.').pop().toLowerCase();
            if ($.inArray(extension, ['jpg', 'jpeg', 'bmp', 'png']) == -1){
                alert('Выбранный файл не является изображением или имеет неверное расширение');
                return false;
            }
        }
    });
");
?>

<?php if (Yii::$app->request->get('new_user', false)): ?>
    <!-- Event snippet for Регистрация conversion page -->
    <script>
        gtag('event', 'conversion', {'send_to': 'AW-879809190/KPpnCMuhu4ABEKalw6MD'});
    </script>
<?php endif; ?>
