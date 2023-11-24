<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 18.09.18
 * Time: 11:54
 */

namespace common\modules\NeuralNetwork;

use common\modules\NeuralNetwork\ngram\Unigram;
use Yii;

class NeuralNetwork
{
    const CARGO_NEURAL_BUILD_PROGRESS_KEY = 'cargo_neural_build_progress';

    const VOCABULAR_KEY = 'vocabular';
    const NGRAM_WORD = 'word';
    const WORD_INDEX_KEY = 'word_index';
    const CATEGORY_INDEX_KEY = 'category_index';
    const NN_KEY = 'neural_net';

    public $ngram_type = self::NGRAM_WORD;

    /**
     * @return NrRedis
     */
    static function getRedis(){
        return Yii::$app->redisNeural;
    }
    
    public function removeVocabular(){
        self::getRedis()->del(self::VOCABULAR_KEY);
    }

    public function createVocabular($texts){
        foreach($texts as $text){
            $words = $this->getNgram($text);

            $stemWords = [];
            foreach($words as $word){
                self::getRedis()->rawCommand('zAdd', self::VOCABULAR_KEY, "INCR", 1, $word);
            }
        }
    }

    public function normalizeVocabular(){
        self::getRedis()->zRemRangeByScore(self::VOCABULAR_KEY, '-inf', 3);
    }

    public function getNgram($text, $type = null){
        $type = !$type ? $this->ngram_type : $type;

        $words = [];

        if($type == self::NGRAM_WORD){
            $words = Unigram::getNram($text);
        }

        return $words;
    }

    public function createVocabularVector(){
        $arr = self::getRedis()->zRange(self::VOCABULAR_KEY, 0, -1);

        foreach($arr as $index => $word){
            self::getRedis()->hSet(self::WORD_INDEX_KEY, $word, $index);
        }

        self::getRedis()->del(self::VOCABULAR_KEY);
    }

    public function createCategotyIndex($categories){
        foreach($categories as $index => $category){
            self::getRedis()->hSet(self::CATEGORY_INDEX_KEY, $category, $index);
        }
    }

    public function removeCategoryIndex(){
        self::getRedis()->del(self::CATEGORY_INDEX_KEY);
    }

    public function createNN(){
        $input = self::getRedis()->hLen(self::WORD_INDEX_KEY);
        $hiddenLayers = round($input*2/3);
        $output = self::getRedis()->hLen(self::CATEGORY_INDEX_KEY);

        self::getRedis()->nrCreate(self::NN_KEY, NrRedis::TYPE_CLASSIFIER, $input, [$hiddenLayers], $output, [
            'NORMALIZE', 'DATASET', 500, 'TEST', 100]);
    }

    public function removeNN(){
        self::getRedis()->delete(self::NN_KEY);
    }

    public function getVectorByText($text){
        $words = $this->getNgram($text);

        $word_indexs = self::getRedis()->hMGet(self::WORD_INDEX_KEY, $words);

        $input = self::getRedis()->hLen(self::WORD_INDEX_KEY);
        $vector = array_fill(0, $input, 0);

        if(is_array($word_indexs)){
            foreach($word_indexs as $index){
                $vector[$index] = 1;
            }
        }

        return $vector;
    }

    public function getCategoryByName($name){
        return self::getRedis()->hGet(self::CATEGORY_INDEX_KEY, $name);
    }

    public function observe($input, $output){
        self::getRedis()->nrObserve(self::NN_KEY, $input, $output);
    }

    public function run($input){
        return self::getRedis()->nrRun(self::NN_KEY, $input);
    }

    public function train(){
        self::getRedis()->nrTrain(self::NN_KEY, ['AUTOSTOP']);
    }

    static public function setProgress($curProgress = 0, $status = ''){
        self::getRedis()->hMSet(self::CARGO_NEURAL_BUILD_PROGRESS_KEY, [
            'curProgress' => $curProgress,
            'status' => $status
        ]);
    }

    static public function removeProgress(){
        self::getRedis()->del(self::CARGO_NEURAL_BUILD_PROGRESS_KEY);
    }

    /**
     * @return array
     */
    static public function getStatus(){
        $info = self::getRedis()->nrInfo(self::NN_KEY);

        $status = [
            'complete' => 0,
            'curProgress' => 0,
            'status' => 'Нейросеть не создана',
            'words' => null,
            'category' => null,
            'hiddenLayout' => []
        ];

        if( $info ){
            if( $info['training'] == 1 ){
                $status['status'] = 'Обучение нейросети';
            }
            elseif( $info['training-total-steps'] == 0 ){
                $status = array_merge($status, self::getRedis()->hGetAll(self::CARGO_NEURAL_BUILD_PROGRESS_KEY));
                $status['status'] = isset($status['status'])?$status['status']:'нейросеть не обучена';
            }
            else{
                $status =[
                    'complete' => 1,
                    'status' => 'нейросеть обучена',
                    'words' => array_shift($info['layout']),
                    'category' => array_pop($info['layout']),
                    'hiddenLayout' => $info['layout']
                ];
            }
        }
        else{
            $status = array_merge($status, self::getRedis()->hGetAll(self::CARGO_NEURAL_BUILD_PROGRESS_KEY));
        }

        return $status;
    }

    public function result($input){
        $status = self::getStatus();

        if( $status['complete'] == 1 ){
            $index = self::getRedis()->nrClass(self::NN_KEY, $input);
            return $this->getCategoryByIndex($index);
        }
        else{
            return false;
        }
    }

    public function getCategoryByIndex($index){
        $categories = self::getRedis()->hGetAll(self::CATEGORY_INDEX_KEY);

        $res = array_filter($categories, function($cat) use($index){
            return $cat == $index;
        });

        $category = key($res);

        return isset($category)?$category:'';
    }

    public function info(){
        return self::getRedis()->nrInfo(self::NN_KEY);
    }

    public function clear(){
        $this->removeNN();
        $this->removeVocabular();
        $this->removeCategoryIndex();
    }
}