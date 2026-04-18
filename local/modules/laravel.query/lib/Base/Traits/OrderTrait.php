<?php

namespace Query\Base\Traits;

/**
 * Методы сортировки (ORDER BY часть запроса).
 *
 * Трейт работает с полем $this->order (массив ['FIELD' => 'ASC|DESC']).
 */
trait OrderTrait
{
    /**
     * Задать сортировку, полностью заменяя предыдущую.
     *
     * ->orderBy('SORT')                             // ASC по умолчанию
     * ->orderBy('DATE_CREATE', 'DESC')
     * ->orderBy(['SORT' => 'ASC', 'ID' => 'DESC'])  // несколько полей сразу
     */
    public function orderBy(string|array $field, string $direction = 'ASC'): static
    {
        if (is_array($field)) {
            $this->order = array_change_key_case($field, CASE_UPPER);
        } else {
            $this->order = [strtoupper($field) => strtoupper($direction)];
        }
        return $this;
    }

    /**
     * Добавить поле сортировки не затирая предыдущие.
     *
     * ->orderBy('SORT')->addOrderBy('ID', 'DESC')
     */
    public function addOrderBy(string $field, string $direction = 'ASC'): static
    {
        $this->order[strtoupper($field)] = strtoupper($direction);
        return $this;
    }

    /**
     * Сортировка по убыванию — синтаксический сахар.
     *
     * ->orderByDesc('DATE_CREATE')
     */
    public function orderByDesc(string $field): static
    {
        return $this->orderBy($field, 'DESC');
    }

    /**
     * Случайная сортировка (RAND).
     */
    public function inRandomOrder(): static
    {
        $this->order = ['RAND' => 'ASC'];
        return $this;
    }

    /**
     * Сортировка по полю свойства инфоблока.
     *
     * ->orderByProperty('PRICE', 'DESC')
     */
    public function orderByProperty(string $propertyCode, string $direction = 'ASC'): static
    {
        return $this->addOrderBy('PROPERTY_' . strtoupper($propertyCode), $direction);
    }

    /**
     * Передать массив сортировки напрямую — полная замена.
     * Escape-хатч для нестандартных случаев.
     */
    public function orderByRaw(array $order): static
    {
        $this->order = $order;
        return $this;
    }
}