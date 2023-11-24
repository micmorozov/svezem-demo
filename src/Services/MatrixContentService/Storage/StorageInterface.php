<?php

namespace Svezem\Services\MatrixContentService\Storage;

use Svezem\Services\MatrixContentService\Essence\EssenceInterface;

interface StorageInterface
{
    /**
     * Стартуем транзакцию
     * @return bool
     */
    public function beginTransaction();

    /**
     * Завершаем транзакцию
     * @return bool
     */
    public function commitTransaction();

    /**
     * Очищаем хранилище
     * @return bool
     */
    public function clearAll(string $essenceKey);

    /**
     * Увеличиваем счетчик в хранилище
     * @param string $key
     * @param float $value
     * @return float
     */
    public function incr(string $essenceKey, string $fieldKey, float $value);

    /**
     * Возвращает значение ключа для сущности
     * @param string $key
     * @return string
     */
    public function get(string $essenceKey, string $fieldKey): string;
}