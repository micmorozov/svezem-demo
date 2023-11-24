<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 04.09.18
 * Time: 17:26
 */

namespace common\modules\NeuralNetwork;

use Redis;

class NrRedis extends Redis
{
    const TYPE_REGRESSOR = 'REGRESSOR';
    const TYPE_CLASSIFIER = 'CLASSIFIER';

    /**
     * @param string $key The key name holding the neural network
     * @param string $type CLASSIFIER or REGRESSOR is the network type, read this tutorial for more info.
     * @param int $inputs Number of input units
     * @param array $hiddenLayer zero or more arguments indicating the number of hidden units, one number for each layer.
     * @param int $outputs Number of outputs units
     * @param array $options NORMALIZE - Specify if you want the network to normalize your inputs. Use this if you don't know what we are talking about.
     *                       DATASET maxlen - Max number of data samples in the training dataset.
     *                       TEST maxlen - Max number of data samples in the testing dataset.
     * @return mixed
     */
    public function nrCreate($key, $type, $inputs, $hiddenLayer, $outputs, $options = []){
        $arg = array_merge(['NR.CREATE', $key, $type, $inputs], $hiddenLayer, ['->', $outputs], $options);
        return call_user_func_array([$this, 'rawCommand'], $arg);
    }

    /**
     * @param $key
     * @param array $inputs
     * @param array $outputs
     * @param array $options
     * @return mixed
     */
    public function nrObserve($key, array $inputs, array $outputs, $options = []){
        $arg = array_merge(['NR.OBSERVE', $key], $inputs, ['->'], $outputs, $options);
        return call_user_func_array([$this, 'rawCommand'], $arg);
    }

    /**
     * @param $key
     * @param array $options
     * @return mixed
     */
    public function nrTrain($key, $options = []){
        $arg = array_merge(['NR.TRAIN', $key], $options);
        return call_user_func_array([$this, 'rawCommand'], $arg);
    }

    /**
     * @param $key
     * @param array $inputs
     * @return mixed
     */
    public function nrRun($key, array $inputs){
        $arg = array_merge(['NR.RUN', $key], $inputs);
        return call_user_func_array([$this, 'rawCommand'], $arg);
    }

    /**
     * @param $key
     * @param array $inputs
     * @return mixed
     */
    public function nrClass($key, array $inputs){
        $arg = array_merge(['NR.CLASS', $key], $inputs);
        return call_user_func_array([$this, 'rawCommand'], $arg);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function nrInfo($key){
        $info = call_user_func([$this, 'rawCommand'], 'NR.INFO', $key);

        $propetryList = ['id','type','auto-normalization','training','layout','training-dataset-maxlen','training-dataset-len',
            'test-dataset-maxlen','test-dataset-len','training-total-steps','training-total-cycles','training-total-seconds',
            'dataset-error','test-error','classification-errors-perc','overfitting-detected'];

        if( !$info )
            return false;

        $info = array_filter($info, function($index){
            return $index%2!=0;
        }, ARRAY_FILTER_USE_KEY);

        return array_combine($propetryList, $info);
    }
}