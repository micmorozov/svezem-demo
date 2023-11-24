<?php
/**
 * Класс по работе с изображениями транспорта и товаров
 * Сохраняет картинки в указанную папку, создает превьюшки
 */
namespace common\helpers;

use yii\helpers\FileHelper;
use yii\imagine\Image;
use yii\web\UploadedFile;

class ImageHelper{

	/**
	 * Сохраняет загруженную через UploadedFile картинку в указанную папку и делает превью в папку thmb с указанным размером
	 *
	 * @param UploadedFile $sourceFile - Исходный файл с изображением
	 * @param $destFolder - Директория куда надо сохранить файл
	 * @param $thmbSizes - Массив с размерами превьюшек вида ['width'=>..., 'height'=>..., 'quality'=>...]
	 * @return mixed
	 */
	public static function previewUploadedImage($sourceFile, $destFolder, $thmbSizes){
		if ($sourceFile) {
			if(!FileHelper::createDirectory($destFolder)) return false;

			// Если файлов несколько в директории, то новому присваивается уникальное имя, иначе main
			$files = FileHelper::findFiles($destFolder, ['recursive' => false]);
			if (count($files)) {
				$fileName = uniqid(time(), true) . '.' . $sourceFile->extension;
			} else {
				$fileName = 'main.'. $sourceFile->extension;
			}

			// Сохраняем исходный файл и создаем превьюшки
			$destFile = $destFolder . DIRECTORY_SEPARATOR . $fileName;
			if ($sourceFile->saveAs($destFile) && static::previewImage($destFile, $thmbSizes))
				return $fileName;
		}

		return false;
	}

	/**
	 * Делаем превьюшки из исходной картинки. Вторым параметром передается массив размеров
	 * @param $srcImgFile Исходный файл с картинкой
	 * @param $thmbSizes Массив с размерами превьюшек ['width'=>..., 'height'=>..., 'quality'=>...]
	 * @return boolean Удалось ли создать превьюшки
	 */
	public static function previewImage($srcImgFile, $thmbSizes){
		$fnDir = pathinfo($srcImgFile, PATHINFO_DIRNAME);

		// Директория файлов превьюшек
		$thmbFolder = FileHelper::normalizePath($fnDir . DIRECTORY_SEPARATOR . 'thmb');
		if(!FileHelper::createDirectory($thmbFolder)) return false;

		foreach($thmbSizes as $thmbSize){
			$thmbFile = static::getThmbFilename($srcImgFile, $thmbSize);
			Image::thumbnail($srcImgFile, $thmbSize['width'], $thmbSize['height'])->save($thmbFolder . DIRECTORY_SEPARATOR . $thmbFile, ['quality' => $thmbSize['quality']]);
		}

		return true;
	}

	/**
	 * Строим имя превьюшки на основе имени главного файла и размера превьюшки
	 * @param $srcFilename
	 * @param $thmbSize
	 */
	public static function getThmbFilename($srcImgFile, $thmbSize){
		return pathinfo($srcImgFile, PATHINFO_FILENAME) .  "-" . $thmbSize['width'] . 'x' . $thmbSize['height'] . '.' . pathinfo($srcImgFile, PATHINFO_EXTENSION);
	}

	/**
	 * Удаляем исходную фотку и превьюшки
	 * @param $imgFile
	 */
	public static function removeImage($imgFile){
		if (is_file($imgFile)) {
			unlink($imgFile);

			$thmbDir = pathinfo($imgFile, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . 'thmb';
			if (is_dir($thmbDir)) {
				$files = FileHelper::findFiles($thmbDir, [
					'only' => [pathinfo($imgFile, PATHINFO_FILENAME) . '-*'],
					'recursive' => false
				]);

				foreach ($files as $file) {
					unlink($file);
				}

				return true;
			}
		}

		return false;
	}
}