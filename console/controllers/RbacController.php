<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 09.11.17
 * Time: 10:47
 */

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class RbacController extends Controller
{
    public $adminid;

    public function options($actionID)
    {
        return ['adminid'];
    }

    public function actionInit()
    {
        if( !isset($this->adminid) ){
            $opt = $this->ansiFormat('опция --adminid', Console::FG_GREEN);
            $this->stdout("Не указан ИД админа $opt\n", Console::BOLD, Console::FG_RED);
            return ExitCode::USAGE;
        }

        $auth = Yii::$app->authManager;

        $auth->removeAllRoles();
        $auth->removeAllPermissions();

        // добавляем роль "admin"
        $admin = $auth->createRole('admin');
        $admin->description = 'Администратор';
        $auth->add($admin);
        $auth->assign($admin, $this->adminid);

        // добавляем роль "moder"
        $moder = $auth->createRole('moder');
        $moder->description = 'Модератор';
        $auth->add($moder);

        // добавляем разрешение "editUser"
        $editUser = $auth->createPermission('editUser');
        $editUser->description = 'Редактирование пользователей';
        $auth->add($editUser);

        // добавляем разрешение "editProfile"
        $editProfile = $auth->createPermission('editProfile');
        $editProfile->description = 'Редактирование профиля';
        $auth->add($editProfile);

        // добавляем разрешение "editCargo"
        $editCargo = $auth->createPermission('editCargo');
        $editCargo->description = 'Редактирование грузов';
        $auth->add($editCargo);

        // добавляем разрешение "editTransport"
        $editTransport = $auth->createPermission('editTransport');
        $editTransport->description = 'Редактирование транспорта';
        $auth->add($editTransport);

        // добавляем разрешение "editPayment"
        $editPayment = $auth->createPermission('editPayment');
        $editPayment->description = 'Редактирование платежей';
        $auth->add($editPayment);

        // добавляем разрешение "editTamplate"
        $editTamplate = $auth->createPermission('editTamplate');
        $editTamplate->description = 'Редактирование шаблонов';
        $auth->add($editTamplate);

        // добавляем разрешение "editDirectory"
        $editDirectory = $auth->createPermission('editDirectory');
        $editDirectory->description = 'Редактирование справочника';
        $auth->add($editDirectory);

        // добавляем разрешение "editCargoTags"
        $editCargoTags = $auth->createPermission('editCargoTags');
        $editCargoTags->description = 'Редактирование тегов груза';
        $auth->add($editCargoTags);

        // добавляем разрешение "editArticle"
        $editArticle = $auth->createPermission('editArticle');
        $editArticle->description = 'Редактирование статей';
        $auth->add($editArticle);


        //добавляем разрешения админу
        $auth->addChild($admin, $editUser);
        $auth->addChild($admin, $editProfile);
        $auth->addChild($admin, $editCargo);
        $auth->addChild($admin, $editTransport);
        $auth->addChild($admin, $editPayment);
        $auth->addChild($admin, $editTamplate);
        $auth->addChild($admin, $editDirectory);
        $auth->addChild($admin, $editCargoTags);
        $auth->addChild($admin, $editArticle);

        //добавляем разрешения модеру
        $auth->addChild($moder, $editCargo);
        $auth->addChild($moder, $editTransport);

        return ExitCode::OK;
    }
}