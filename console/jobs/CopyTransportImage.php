<?php
/**
 * Копируем картинки из одной папки в другую заодно меняя их размер
 *
 * Параметры для запуска:
 * trasport_id - ИД транспорта для которого копируем картинки
 * source - Исходная папка
 * destination - Конечная папка
 */
namespace console\jobs;

use GearmanJob;
use micmorozov\yii2\gearman\JobBase;
use common\models\Transport;
use common\models\TransportImage;
use common\helpers\ImageHelper;
use yii\helpers\FileHelper;

class CopyTransportImage extends JobBase
{
	public function execute(GearmanJob $job = null)
	{
		$workload = $this->getWorkload($job);
		if(!$workload) return;

		$transport = Transport::findOne($workload['trasport_id']);
		if(!$transport) return;


		$tempDirectory = $workload['source'];
		$permDirectory = $workload['destination'];
		FileHelper::createDirectory($permDirectory);

		// Удаляем картинки, помеченные на удаление
		foreach ($transport->transportImages as $image) {
			if ($image->status == TransportImage::STATUS_DELETED) {
				ImageHelper::removeImage($permDirectory . DIRECTORY_SEPARATOR . $image->image);
				$image->delete();
			}
		}
		unset($transport->transportImages);

		// Переносим картинки из временного хранилища
		if (is_dir($tempDirectory)) {
			$files = FileHelper::findFiles($tempDirectory, ['recursive' => false]);

			foreach ($files as $file) {
				$transportImage = new TransportImage();
				$transportImage->image = basename($file);
				$transportImage->transport_id = $transport->id;
				if (preg_match('/^main./', basename($file))) {
					$uid = uniqid(time(), true);
					$transportImage->image = $uid . '.' . pathinfo($file, PATHINFO_EXTENSION);
					if (!$transport->getMainImage()) {
						$transportImage->type = TransportImage::TYPE_MAIN;
					}
				}
				copy($file, $permDirectory . DIRECTORY_SEPARATOR . $transportImage->image);
				$transportImage->save();
			}

			FileHelper::removeDirectory($tempDirectory);
		}

		// Если главного изображения нет - назначаем главным первое попавшееся
		if (count($transport->transportImages) && !$transport->getMainImage()) {
			$firstImage = $transport->getFirstImage();
			$firstImage->updateAttributes(['type' => TransportImage::TYPE_MAIN]);
		}
	}
}