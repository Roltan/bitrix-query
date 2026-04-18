<?php

namespace Query\Base\Traits;

/**
 * Методы фильтрации (WHERE-часть запроса).
 *
 * Трейт работает с полем $this->filter (массив, передаётся в GetList как $arFilter).
 * Поддерживает все префиксы операторов, которые понимает CIBlock::MkOperationFilter:
 *
 *   =   точное равенство (по умолчанию)
 *   !   не равно
 *   %   LIKE
 *   !%  NOT LIKE
 *   >   больше
 *   >=  больше или равно
 *   <   меньше
 *   <=  меньше или равно
 *   ?   полнотекстовый поиск (FULLTEXT)
 */
trait FilterTrait
{
    /**
     * Универсальное условие WHERE.
     *
     * Варианты вызова:
     *   ->where('NAME', 'Товар')               // равенство
     *   ->where('SORT', '>', 10)               // сравнение
     *   ->where('NAME', 'like', '%Товар%')     // LIKE
     *   ->where('ID', [1, 2, 3])              // IN (массив)
     */
    public function where(string $field, mixed $operatorOrValue, mixed $value = null): static
    {
        if ($value === null) {
            $this->filter[$field] = $operatorOrValue;
            return $this;
        }

        $prefix = $this->resolveOperatorPrefix((string)$operatorOrValue);
        $this->filter[$prefix . $field] = $value;
        return $this;
    }

    /**
     * Отрицание — NOT WHERE.
     *
     * ->whereNot('ACTIVE', 'N')
     * ->whereNot('ID', [1, 2, 3])
     */
    public function whereNot(string $field, mixed $value): static
    {
        $this->filter['!' . $field] = $value;
        return $this;
    }

    /**
     * WHERE field IN (values).
     *
     * ->whereIn('ID', [1, 2, 3])
     */
    public function whereIn(string $field, array $values): static
    {
        $this->filter[$field] = $values;
        return $this;
    }

    /**
     * WHERE field NOT IN (values).
     */
    public function whereNotIn(string $field, array $values): static
    {
        $this->filter['!' . $field] = $values;
        return $this;
    }

    /**
     * WHERE field BETWEEN min AND max.
     * Реализуется через два условия >= и <=.
     */
    public function whereBetween(string $field, mixed $min, mixed $max): static
    {
        $this->filter['>=' . $field] = $min;
        $this->filter['<=' . $field] = $max;
        return $this;
    }

    /**
     * WHERE field IS NULL (пустое значение).
     * Передаётся как false — Битрикс трактует это как IS NULL / пусто.
     */
    public function whereNull(string $field): static
    {
        $this->filter[$field] = false;
        return $this;
    }

    /**
     * WHERE field LIKE '%value%'.
     */
    public function whereLike(string $field, string $value): static
    {
        $this->filter['%' . $field] = $value;
        return $this;
    }

    /**
     * Фильтр по свойству инфоблока.
     *
     * ->whereProperty('PRICE', 1000)
     * ->whereProperty('PRICE', '>=', 1000)
     * ->whereProperty('COLOR', 'like', '%red%')
     */
    public function whereProperty(string $propertyCode, mixed $operatorOrValue, mixed $value = null): static
    {
        $key = 'PROPERTY_' . strtoupper($propertyCode);
        return $this->where($key, $operatorOrValue, $value);
    }

    /**
     * Только активные записи (ACTIVE = Y|N).
     */
    public function active(bool $active = true): static
    {
        $this->filter['ACTIVE'] = $active ? 'Y' : 'N';
        return $this;
    }

    /**
     * Фильтр по инфоблоку.
     */
    public function iblock(int $iblockId): static
    {
        $this->filter['IBLOCK_ID'] = $iblockId;
        return $this;
    }

    /**
     * Фильтр по секции.
     *
     * @param int|int[] $sectionId
     */
    public function section(int|array $sectionId, bool $includeSubsections = false): static
    {
        $this->filter['SECTION_ID'] = $sectionId;
        if ($includeSubsections) {
            $this->filter['INCLUDE_SUBSECTIONS'] = 'Y';
        }
        return $this;
    }

    /**
     * Элементы, активные на текущий момент (проверка ACTIVE_FROM/ACTIVE_TO).
     */
    public function activeDate(): static
    {
        $this->filter['ACTIVE_DATE'] = 'Y';
        return $this;
    }

    /**
     * Показывать историю workflow (SHOW_HISTORY = Y).
     * Нужно для получения конкретного элемента по ID в обход статуса WF.
     */
    public function withHistory(): static
    {
        $this->filter['SHOW_HISTORY'] = 'Y';
        return $this;
    }

    /**
     * Добавить произвольный кусок фильтра (raw-массив, как в GetList).
     * Escape-хатч для всего нестандартного.
     */
    public function whereRaw(array $filterPart): static
    {
        $this->filter = array_merge($this->filter, $filterPart);
        return $this;
    }

    // ──────────────────────────────────────────────
    // Внутренние хелперы
    // ──────────────────────────────────────────────

    /**
     * Преобразовать строковый оператор в префикс фильтра Битрикса.
     */
    private function resolveOperatorPrefix(string $operator): string
    {
        return match (strtolower($operator)) {
            '='              => '=',
            '!=', '!'        => '!',
            '>'              => '>',
            '>='             => '>=',
            '<'              => '<',
            '<='             => '<=',
            'like', '%'      => '%',
            '!like', '!%'    => '!%',
            'fulltext', '?'  => '?',
            default          => '=',
        };
    }
}