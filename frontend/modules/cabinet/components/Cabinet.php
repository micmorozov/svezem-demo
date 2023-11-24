<?php

namespace frontend\modules\cabinet\components;

use common\components\bookingService\Service;
use common\models\Cargo;
use common\models\CargoBookingLog;
use frontend\modules\cabinet\models\CargoBookingComment;
use micmorozov\yii2\gearman\Dispatcher;
use Yii;
use yii\caching\TagDependency;
use yii\db\Expression;
use yii\helpers\Html;

class Cabinet
{
    public static function booking($cargo_id, $userid = null)
    {
        if(is_null($userid))
            $userid = Yii::$app->user->id;

        $success = false;
        $err_msg = '';

        $bookingService = new Service($userid);
       /* $dayLimit = $bookingService->checkDayLimit();

        if ( !$dayLimit) {
            return [
                'error' => 'Суточный лимит бронирований исчерпан, чтобы забронировать заказ, дождитесь следующих суток или смените тариф<br>'.Html::a('Сменить тариф',
                        '//'.Yii::getAlias('@domain').'/cargo/booking/')
            ];
        }*/

        $trans = Yii::$app->db->beginTransaction();

        $rows = Cargo::updateAll([
            'status' => Cargo::STATUS_WORKING,
            'booking_by' => $userid,
            'booking_at' => time()
        ], [
            'and',
            ['id' => $cargo_id],
            ['status' => Cargo::STATUS_ACTIVE]
        ]);

        if ($rows == 1) {
            $log = new CargoBookingLog();
            $log->cargo_id = $cargo_id;
            $log->state = Cargo::STATUS_WORKING;
            $log->created_by = $userid;
            if ( !$log->save()) {
                Yii::error("Не удалось добавить запись в лог бронирования", 'CargoBookingLog');
                $err_msg = 'Произошла ошибка при бронировании груза';
            } else {
                //Уменьшаем количествой броней
                if ($bookingService->countReduce()) {
                    $success = true;
                } else {
                    $err_msg = 'Лимит заказов в работе исчерпан, выполните/отмените заказ или смените тариф, чтобы забронировать еще.<br>'.Html::a('Сменить тариф',
                            '//'.Yii::getAlias('@domain').'/cargo/booking/');
                }
            }
        } else {
            $err_msg = 'Груз уже забронирован';
        }

        if ($success) {
            $trans->commit();

            Cargo::updateElk($cargo_id);

            return Cargo::findOne($cargo_id);
        } else {
            $trans->rollBack();
            return [
                'error' => $err_msg
            ];
        }
    }

    public static function save($cargo_id, $price)
    {
        $trans = Yii::$app->db->beginTransaction();

        $cargo = Cargo::find()
            ->where([
                'and',
                ['id' => $cargo_id],
                ['status' => Cargo::STATUS_WORKING],
                ['booking_by' => Yii::$app->user->id],
            ])->one();

        if ( !$cargo) {
            return [
                'error' => 'Не удалось выполнить действие'
            ];
        }

        $cargo->scenario = Cargo::SCENARIO_BOOKING_SAVE;
        $cargo->status = Cargo::STATUS_DONE;
        $cargo->booking_price = $price;

        if ( !$cargo->save()) {
            $trans->rollBack();

            return [
                'error' => current($cargo->getErrors())[0]
            ];
        }

        $log = new CargoBookingLog();
        $log->cargo_id = $cargo_id;
        $log->state = Cargo::STATUS_DONE;
        $log->price = $price;
        if ( !$log->save()) {
            Yii::error("Не удалось добавить запись в лог бронирования", 'CargoBookingLog');
            $trans->rollBack();

            return [
                'error' => 'Произошла ошибка при сохранении'
            ];
        }

        $bookingService = new Service(Yii::$app->user->id);
        $bookingService->countIncrease();

        $trans->commit();

        return Cargo::findOne($cargo_id);
    }

    public static function cancel($cargo_id)
    {
        $success = false;
        $err_msg = '';

        $bookingService = new Service(Yii::$app->user->id);

        $trans = Yii::$app->db->beginTransaction();

        // При отмене бронирования груз должен появиться вверху списка грузов как новый только если он не сильно устарел
        // По таким грузам партнерам будет отправлено уведомление
        $createdExpr = new Expression('IF(created_at<'.strtotime("-".Cargo::DAYS_ACTUAL." days").',created_at,'.time().')');
        $rows = Cargo::updateAll([
            'status' => Cargo::STATUS_ACTIVE,
           // 'created_at' => $createdExpr,
            'booking_by' => null,
            'booking_at' => null,
        ], [
            'and',
            ['id' => $cargo_id],
            ['status' => Cargo::STATUS_WORKING],
            ['booking_by' => Yii::$app->user->id],
        ]);

        if ($rows == 1) {
            $log = new CargoBookingLog();
            $log->cargo_id = $cargo_id;
            $log->state = Cargo::STATUS_ACTIVE;
            if ( !$log->save()) {
                Yii::error("Не удалось добавить запись в лог бронирования", 'CargoBookingLog');
                $err_msg = 'Произошла ошибка при отмене бронирования';

            } else {
                $bookingService->countIncrease();
                $success = true;
            }
        } else {
            $err_msg = 'Не удалось выполнить действие';
        }

        if ($success) {
            // При отмене, если нет своих комментариев, надо написать, что этот груз уже брали.
            $userComment = CargoBookingComment::find()->where([
                'cargo_id' => $cargo_id,
                'created_by' => Yii::$app->user->id
            ])->count();

            if ( !$userComment) {
                $userComment = new CargoBookingComment();
                $userComment->cargo_id = $cargo_id;
                $userComment->created_by = Yii::$app->user->id;
                $userComment->comment = 'Вы бронировали этот груз ранее';
                if ( !$userComment->save()) {
                    Yii::error("Не удалось добавить запись в CargoBookingComment", 'CargoBookingLog');
                }
            }

            $trans->commit();

            // Заново уведомляем перевозчиков о том, что появился груз
            Yii::$app->gearman->getDispatcher()->background("notifyCarrier", [
                'cargo_id' => $cargo_id,
                'booking_only' => 1, // Уведомляем только перевозчиков, имеющих доступ к бронированию грузов
                'repeat_by' => Yii::$app->user->id // Повторить отправку из за юзера
            ], Dispatcher::HIGH);

            //очищаем кэш поиска
            TagDependency::invalidate(Yii::$app->cache, 'cargoSearchCache');

            Cargo::updateElk($cargo_id);

            return Cargo::findOne($cargo_id);
        } else {
            $trans->rollBack();

            return [
                'error' => $err_msg
            ];
        }
    }

    public static function edit($cargo_id, $price)
    {
        $trans = Yii::$app->db->beginTransaction();

        $cargo = Cargo::find()
            ->where([
                'and',
                ['id' => $cargo_id],
                ['status' => Cargo::STATUS_DONE],
                ['booking_by' => Yii::$app->user->id],
            ])->one();

        if ( !$cargo) {
            $trans->rollBack();

            return [
                'error' => 'Не удалось выполнить действие'
            ];
        }

        $cargo->scenario = Cargo::SCENARIO_BOOKING_SAVE;
        $cargo->booking_price = $price;

        if ( !$cargo->save()) {
            $trans->rollBack();

            return [
                'error' => current($cargo->errors)
            ];
        }

        $log = new CargoBookingLog();
        $log->cargo_id = $cargo_id;
        $log->state = Cargo::STATUS_DONE;
        $log->price = $price;
        if ( !$log->save()) {
            Yii::error("Не удалось добавить запись в лог бронирования", 'CargoBookingLog');
            $trans->rollBack();

            return [
                'error' => 'Произошла ошибка при сохранении'
            ];
        }

        $trans->commit();

        return Cargo::findOne($cargo_id);
    }

    public static function commentSave($cargo_id, $comment)
    {
        // Если коммент есть, то сохраняем, иначе удаляем запись
        if ($comment) {
            $userComment = CargoBookingComment::findOne(['cargo_id' => $cargo_id, 'created_by' => Yii::$app->user->id]);
            if ( !$userComment) {
                $userComment = new CargoBookingComment();
            }

            $userComment->cargo_id = $cargo_id;
            $userComment->created_by = Yii::$app->user->id;
            $userComment->comment = $comment;

            if ( !$userComment->save()) {
                return [
                    'error' => current($userComment->errors)
                ];
            }
        } else {
            CargoBookingComment::deleteAll(['cargo_id' => $cargo_id, 'created_by' => Yii::$app->user->id]);
        }

        return $comment;
    }
}
