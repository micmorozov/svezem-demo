<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 01.02.19
 * Time: 11:58
 */

namespace common\behaviors;

use ImageOptimizer\OptimizerFactory;
use Yii;

/**
 * Class UploadImageBehavior
 * @package common\behaviors
 */
class UploadImageBehavior extends \mohorev\file\UploadImageBehavior
{
    /**
     * @param $config
     * @param $path
     * @param $thumbPath
     */
    public function generateImageThumb($config, $path, $thumbPath){
        parent::generateImageThumb($config, $path, $thumbPath);

        //После создания превью сжимаем его
        $factory = new OptimizerFactory([
            'jpegoptim_options' => ['--max=10']
        ]);

        $optimizer = $factory->get();
        $filepath = Yii::getAlias($thumbPath);

        $optimizer->optimize($filepath);
    }
}