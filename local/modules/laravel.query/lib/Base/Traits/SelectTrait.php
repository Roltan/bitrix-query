<?php

namespace Query\Base\Traits;

/**
 * Методы выборки полей (SELECT) и группировки (GROUP BY).
 *
 * Трейт работает с полями:
 *   $this->select  — массив полей для GetList $arSelectFields
 *   $this->groupBy — массив полей для GetList $arGroupBy (false = без группировки)
 */
trait SelectTrait
{
    /**
     * Задать список полей для выборки (полностью заменяет предыдущие).
     *
     * @param array<int, string> $fields Список полей SELECT
     *
     * @return static
     *
     * @throws \InvalidArgumentException если массив пустой
     *
     * @example
     * ->select(['ID', 'NAME'])
     * ->select(['ID', 'NAME', 'PROPERTY_PRICE'])
     */
    public function select(array $fields): static
    {
        if (empty($fields)) {
            throw new \InvalidArgumentException('Select cannot be empty');
        }

        $this->select = $fields;
        return $this;
    }

    /**
     * Добавить поля к SELECT без удаления уже выбранных.
     *
     * @param string ...$fields Поля для добавления
     *
     * @return static
     *
     * @throws \InvalidArgumentException если передан пустой список
     *
     * @example
     * ->select(['ID'])->addSelect('NAME', 'SORT')
     */
    public function addSelect(string ...$fields): static
    {
        if (empty($fields)) {
            throw new \InvalidArgumentException('Select cannot be empty');
        }

        $this->select = array_values(array_unique(array_merge(
            $this->select,
            $fields
        )));
        return $this;
    }

    /**
     * Выбрать все базовые поля элемента (SELECT *).
     *
     * @return static
     *
     * @example
     * ->selectAll()
     */
    public function selectAll(): static
    {
        $this->select = ['*'];
        return $this;
    }

    /**
     * Добавить все свойства инфоблока (PROPERTY_*).
     *
     * Работает корректно только при одном IBLOCK_ID в фильтре.
     *
     * @return static
     *
     * @example
     * ->withProperties()
     */
    public function withProperties(): static
    {
        if (!in_array('PROPERTY_*', $this->select, true)) {
            $this->select = array_values(array_diff($this->select, array_filter($this->select, fn($f) => str_starts_with($f, 'PROPERTY_'))));
            $this->select[] = 'PROPERTY_*';
        }
        return $this;
    }

    /**
     * Добавить конкретные свойства инфоблока к выборке.
     *
     * @param string ...$propertyCodes Коды свойств (PRICE, COLOR и т.д.)
     *
     * @return static
     *
     * @throws \InvalidArgumentException если список пустой
     *
     * @example
     * ->withProperty('PRICE', 'COLOR')
     */
    public function withProperty(string ...$propertyCodes): static
    {
        if (empty($propertyCodes)) {
            throw new \InvalidArgumentException('Select cannot be empty');
        }
        foreach ($propertyCodes as $code) {
            $field = 'PROPERTY_' . strtoupper($code);
            if (!in_array($field, $this->select, true)) {
                $this->select[] = $field;
            }
        }
        return $this;
    }

    /**
     * GROUP BY — задать поля группировки.
     *
     * @param array<int, string> $fields Поля группировки
     *        Пустой массив означает COUNT(*)
     *
     * @return static
     *
     * @example
     * ->groupBy(['IBLOCK_ID', 'ACTIVE'])
     * ->groupBy([]) // COUNT
     */
    public function groupBy(array $fields = []): static
    {
        $this->groupBy = $fields === [] ? [] : $fields;
        return $this;
    }
}