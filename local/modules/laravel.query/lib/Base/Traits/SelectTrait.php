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
     * Задать список полей для выборки, полностью заменяя предыдущий.
     *
     * ->select(['ID', 'NAME', 'SORT'])
     * ->select(['ID', 'NAME', 'PROPERTY_PRICE', 'PROPERTY_COLOR'])
     *
     * Если не вызывать — GetList вернёт все базовые поля (поведение по умолчанию).
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
     * Добавить поля к SELECT не затирая предыдущие.
     *
     * ->select(['ID', 'NAME'])->addSelect('SORT', 'PROPERTY_PRICE')
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
     * Это поведение GetList по умолчанию — метод для явного обозначения намерения.
     */
    public function selectAll(): static
    {
        $this->select = ['*'];
        return $this;
    }

    /**
     * Выбрать все свойства инфоблока (PROPERTY_*).
     * Работает только если в фильтре задан один конкретный IBLOCK_ID.
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
     * Добавить конкретные свойства к выборке.
     *
     * ->withProperty('PRICE', 'COLOR', 'BRAND')
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
     * ->groupBy(['IBLOCK_ID', 'ACTIVE'])
     *
     * Пустой массив [] означает «только COUNT» (GetList вернёт число).
     */
    public function groupBy(array $fields = []): static
    {
        $this->groupBy = $fields === [] ? [] : $fields;
        return $this;
    }
}