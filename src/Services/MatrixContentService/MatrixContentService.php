<?php

namespace Svezem\Services\MatrixContentService;

use common\models\CargoCategory;
use common\models\LocationInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Svezem\Services\MatrixContentService\Cache\Hash;
use Svezem\Services\MatrixContentService\Cache\HashAware;
use Svezem\Services\MatrixContentService\Essence\CargoEssence;
use Svezem\Services\MatrixContentService\Essence\EssenceInterface;
use Svezem\Services\MatrixContentService\Essence\TkEssence;
use Svezem\Services\MatrixContentService\Essence\TransportEssence;
use Svezem\Services\MatrixContentService\Storage\StorageInterface;

class MatrixContentService
{
    use HashAware;

    /** @var StorageInterface  */
    private $storage;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(StorageInterface $storage, LoggerInterface $logger = null)
    {
        $this->storage = $storage;
        $this->logger = $logger ?? new NullLogger();
        $this->setHash(new Hash());
    }

    public function isEnoughContent(string $tpl,
        LocationInterface $locationFrom = null,
        LocationInterface $locationTo = null,
        CargoCategory $cat = null): bool
    {
        // перевозка между городами
        if($tpl == 'intercity-view'){
            $sum = (int)$this->getContent(new CargoEssence(), $locationFrom, $locationTo, $cat) +
                (int)$this->getContent(new TransportEssence(), $locationFrom, $locationTo, $cat);
            return $sum >= 3;
        }elseif($tpl == 'intercity-category-view'){ // Перевозка категории между городами
            $sum = (int)$this->getContent(new CargoEssence(), $locationFrom, $locationTo, $cat);
            return $sum >= 3;
        }elseif($tpl == 'cargo-transportation-view'){
            $sum = (int)$this->getContent(new TransportEssence(), $locationFrom, $locationTo, $cat) +
                (int)$this->getContent(new CargoEssence(), $locationFrom, $locationTo, $cat);
            return $sum >= 3;
        }

        //если результаты поиска на странице будут по одной из сущностей
        if($essence = $this->getContentEssence($tpl)){
            $sum = (int)$this->getContent($essence, $locationFrom, $locationTo, $cat);
        } //иначе берем сумму по всем сущностям
        else{
            $sum = (int)$this->getContent(new CargoEssence(), $locationFrom, $locationTo, $cat) +
                (int)$this->getContent(new TransportEssence(), $locationFrom, $locationTo, $cat) +
                (int)$this->getContent(new TkEssence(), $locationFrom, $locationTo, $cat);
        }

        return $sum >= 3;
    }

    /**
     * Достаточно ли контетна по указанному шаблону,
     * при котором город является отправкой или назначением
     * @param $tpl
     * @param LocationInterface $location
     * @param CargoCategory $cat
     * @return bool
     */
    public function isEnoughContentAnyDirection(string $tpl, LocationInterface $location = null, CargoCategory $cat = null): bool
    {
        return $this->getContentAnyDirectionByTpl($tpl, $location, $cat) >= 3;
    }

    /**
     * Возвращает контент для шаблона и параметров
     * @param string $tpl
     * @param LocationInterface|null $location
     * @param CargoCategory|null $cat
     * @return string
     */
    public function getContentAnyDirectionByTpl(string $tpl, LocationInterface $location = null, CargoCategory $cat = null): string
    {
        if($tpl == 'cargo-transportation-view'){
            $trCount = (int)$this->getContent(new TransportEssence(), $location, null, $cat) +
                (int)$this->getContent(new TransportEssence(), null, $location, $cat);
            if($trCount >= 3) {
                return $trCount;
            }

            $cargoCount = (int)$this->getContent(new CargoEssence(), $location, null, $cat) +
                (int)$this->getContent(new CargoEssence(), null, $location, $cat) -
                // Что бы точнее посчитать наличие контента надо отнять количество груза из города в этот же город
                // т.к. этот груз уже посчитан выше
                (int)$this->getContent(new CargoEssence(), $location, $location, $cat);

            return $cargoCount;
        }

        //если результаты поиска на странице будут по одной из сущностей
        if($essence = $this->getContentEssence($tpl)){
            $sum = (int)$this->getContent($essence, $location, null, $cat) +
                (int)$this->getContent($essence, null, $location, $cat);
        } //иначе берем сумму по всем сущностям
        else{
            $sum = (int)$this->getContent(new CargoEssence(), $location, null, $cat) +
                (int)$this->getContent(new TransportEssence(), $location, null, $cat) +
                (int)$this->getContent(new TkEssence(), $location, null, $cat) +
                (int)$this->getContent(new CargoEssence(), null, $location, $cat) +
                (int)$this->getContent(new TransportEssence(), null, $location, $cat);
        }

        return $sum;
    }

    /**
     * Получение кол-ва контента по сущности
     * @param string $essence - сущность (cargo, transport, tk)
     * @param LocationInterface $locationFrom
     * @param LocationInterface $locationTo
     * @param CargoCategory $cat
     * @return string
     */
    public function getContent(EssenceInterface $essence,
        LocationInterface $locationFrom = null,
        LocationInterface $locationTo = null,
        CargoCategory $cat = null):?string
    {
        $this->logger->debug('getContent', [
            $essence->getEssence(),
            $locationFrom ? $locationFrom->getCode() : null,
            $locationTo ? $locationTo->getCode() : null,
            $cat ? $cat->id : null
        ]);

        $field = $this->getFieldKey($locationFrom, $locationTo, $cat);
        if(!$field) {
            $this->logger->debug('No content field');

            return null;
        }

        $hash = $this->getHash();
        $essenceHash = $hash->getKeyHash($this->getEssenceKey($essence));
        $fieldHash = $hash->getKeyHash($field);

        $this->logger->debug('Storage request', [$essenceHash, $fieldHash]);

        $content = $this->storage->get($essenceHash, $fieldHash);

        $this->logger->debug('Storage response', [$content]);

        return $content;
    }

    /**
     * При создании тегов необходимо проверять достаточно ли контента на страницах куда они ведут
     * По шаблону определяем по какой из сущностей проверять наличие контента
     *
     * Пример:
     *      transport-search-inside-city-view
     *      Сущность: transport
     *      На страницах будут отображаться только результаты поиска транспорта
     *
     *      cargo-transportation-list
     *      На страницах будут отображаться результаты поиска транспорта, грузов и тк
     * @param $tpl
     * @return EssenceInterface|null
     */
    private function getContentEssence(string $tpl):?EssenceInterface
    {
        //если результаты поиска на странице будут по одной из сущностей
        if(preg_match("/^(transport|cargo|tk)-search/", $tpl, $match)){
            switch ($match[1]){
                case 'transport':
                    return new TransportEssence();

                case 'cargo':
                    return new CargoEssence();

                case 'tk':
                    return new TkEssence();
            }
        } else
            return null;
    }

    public function incrValue(EssenceInterface $essence,
        LocationInterface $from = null,
        LocationInterface $to = null,
        CargoCategory $cat = null,
        $value = 0)
    {
        $fieldKey = $this->getFieldKey($from, $to, $cat);
        if(!$fieldKey)
            return false;

        $hash = $this->getHash();
        return $this->storage->incr($hash->getKeyHash($this->getEssenceKey($essence)), $hash->getKeyHash($fieldKey), $value);
    }

    public function beginTransaction()
    {
        return $this->storage->beginTransaction();
    }

    public function commitTransaction()
    {
        return $this->storage->commitTransaction();
    }

    public function clearAll(EssenceInterface $essence)
    {
        return $this->storage->clearAll($this->getHash()->getKeyHash($this->getEssenceKey($essence)));
    }

    /**
     * @param $essence
     * @param LocationInterface $from
     * @param LocationInterface $to
     * @param CargoCategory $cat
     * @return array|string
     */
    private function getFieldKey(LocationInterface $from = null, LocationInterface $to = null, CargoCategory $cat = null):array
    {
        $field = [];

        if($from) {
            $className = join('', array_slice(explode('\\', get_class($from)), -1));
            $field[] = "{$className}From{$from->getId()}";
        }

        if($to) {
            $className = join('', array_slice(explode('\\', get_class($to)), -1));
            $field[] = "{$className}To{$to->getId()}";
        }

        if($cat) {
            $field[] = "Cat{$cat->id}";
        }

        return $field;
    }

    /**
     * @param $essence
     * @return string
     */
    private function getEssenceKey(EssenceInterface $essence):array
    {
        return [
            'MatrixContent',
            $essence->getEssence()
        ];
    }
}
