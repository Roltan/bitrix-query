<?php

namespace Query\Base\Traits;

use Psr\Log\InvalidArgumentException;

/**
 * Методы сортировки (ORDER BY часть запроса).
 *
 * Трейт работает с полем $this->order (массив ['FIELD' => 'ASC|DESC']).
 */
trait OrderTrait
{
    /**
     * Задать сортировку (полная замена предыдущей).
     *
     * @param string|array<string, string> $field
     *        - string: имя поля
     *        - array: ['FIELD' => 'ASC|DESC']
     *
     * @param string $direction
     *        ASC или DESC (по умолчанию ASC)
     *
     * @throws InvalidArgumentException если direction некорректный
     *
     * @return static
     */
    public function orderBy(string|array $field, string $direction = 'ASC'): static
    {
        if(!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException('Invalid direction sort');
        }

        if (is_array($field)) {
            $this->order = array_change_key_case($field, CASE_UPPER);
        } else {
            $this->order = [strtoupper($field) => strtoupper($direction)];
        }
        return $this;
    }

    /**
     * Добавить сортировку без удаления предыдущей.
     *
     * @param string $field     Поле сортировки
     * @param string $direction ASC|DESC
     *
     * @throws InvalidArgumentException если direction некорректный
     *
     * @return static
     */
    public function addOrderBy(string $field, string $direction = 'ASC'): static
    {
        if(!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException('Invalid direction sort');
        }

        $this->order[strtoupper($field)] = strtoupper($direction);
        return $this;
    }

    /**
     * Сортировка по убыванию (синтаксический сахар).
     *
     * @param string $field
     *
     * @return static
     */
    public function orderByDesc(string $field): static
    {
        return $this->orderBy($field, 'DESC');
    }

    /**
     * Случайная сортировка.
     *
     * ⚠️ Bitrix: работает через RAND (не SQL ORDER BY RAND() напрямую)
     *
     * @return static
     */
    public function inRandomOrder(): static
    {
        $this->order = ['RAND' => 'ASC'];
        return $this;
    }

    /**
     * Сортировка по свойству инфоблока (полная замена).
     *
     * Пример:
     *   ->orderByProperty('PRICE', 'DESC')
     *
     * @param string $propertyCode Код свойства (без PROPERTY_)
     * @param string $direction    ASC|DESC
     *
     * @return static
     */
    public function orderByProperty(string $propertyCode, string $direction = 'ASC'): static
    {
        return $this->orderBy('PROPERTY_' . strtoupper($propertyCode), $direction);
    }

    /**
     * Добавить сортировку по свойству инфоблока.
     *
     * Пример:
     *   ->addOrderByProperty('PRICE', 'DESC')
     *
     * @param string $propertyCode Код свойства
     * @param string $direction    ASC|DESC
     *
     * @return static
     */
    public function addOrderByProperty(string $propertyCode, string $direction = 'ASC'): static
    {
        return $this->addOrderBy('PROPERTY_' . strtoupper($propertyCode), $direction);
    }

    /**
     * Передать сортировку "как есть" (raw режим).
     *
     * ⚠️ Полностью отключает валидацию и нормализацию.
     * Используется только для нестандартных кейсов Bitrix.
     *
     * @param array<string, string> $order
     *        Пример:
     *        [
     *            'SORT' => 'ASC',
     *            'ID'   => 'DESC'
     *        ]
     *
     * @return static
     */
    public function orderByRaw(array $order): static
    {
        $this->order = $order;
        return $this;
    }
}