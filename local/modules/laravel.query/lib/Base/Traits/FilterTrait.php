<?php

namespace Query\Base\Traits;

/**
 * Методы фильтрации (WHERE-часть запроса).
 *
 * Трейт работает с двумя полями базового класса:
 *   $this->filter   — AND-условия (ключ => значение)
 *   $this->orGroups — массив OR-групп, каждая группа — массив условий
 *
 * Итоговый фильтр для GetList собирается методом buildFilter():
 *
 *   [
 *     'IBLOCK_ID' => 5,           // AND-условия
 *     'ACTIVE'    => 'Y',
 *     [                           // OR-группа (вложенный массив с LOGIC=OR)
 *       'LOGIC' => 'OR',
 *       'NAME'  => 'Товар А',
 *       '=CODE' => 'tovar-b',
 *     ],
 *   ]
 *
 * Поддерживаемые префиксы операторов (CIBlock::MkOperationFilter):
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
    // ──────────────────────────────────────────────
    // AND-условия
    // ──────────────────────────────────────────────

    /**
     * Универсальное AND-условие фильтрации.
     *
     * Поддерживает 3 режима:
     *
     * 1) where('NAME', 'Товар')
     *    → NAME = 'Товар'
     *
     * 2) where('SORT', '>', 10)
     *    → >SORT = 10
     *
     * 3) where('NAME', 'like', '%Товар%')
     *    → %NAME = '%Товар%'
     *
     * @param string $field
     * @param mixed $operatorOrValue
     * @param mixed|null $value
     *
     * @return static
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
     * NOT условие.
     *
     * whereNot('ACTIVE', 'Y')
     * → !ACTIVE = 'Y'
     *
     * @param string $field
     * @param mixed $value
     * @return static
     */
    public function whereNot(string $field, mixed $value): static
    {
        $this->filter['!' . $field] = $value;
        return $this;
    }

    /**
     * WHERE IN условие.
     *
     * whereIn('ID', [1,2,3])
     * → ID = [1,2,3]
     *
     * @param string $field
     * @param array<int, mixed> $values
     * @return static
     */
    public function whereIn(string $field, array $values): static
    {
        $this->filter[$field] = $values;
        return $this;
    }

    /**
     * WHERE field NOT IN.
     *
     * whereNotIn('ID', [1,2,3])
     * → !ID = [1,2,3]
     *
     * @param string $field
     * @param array<int, mixed> $values
     * @return static
     */
    public function whereNotIn(string $field, array $values): static
    {
        $this->filter['!' . $field] = $values;
        return $this;
    }

    /**
     * Диапазон значений.
     *
     * whereBetween('PRICE', 100, 500)
     * → >=PRICE = 100 AND <=PRICE = 500
     *
     * @param string $field
     * @param mixed $min
     * @param mixed $max
     * @return static
     */
    public function whereBetween(string $field, mixed $min, mixed $max): static
    {
        $this->filter['>=' . $field] = $min;
        $this->filter['<=' . $field] = $max;
        return $this;
    }

    /**
     * Проверка на пустое значение.
     *
     * whereNull('DATE')
     *
     * @param string $field
     * @return static
     */
    public function whereNull(string $field): static
    {
        $this->filter[$field] = false;
        return $this;
    }

    /**
     * LIKE-условие (частичное совпадение).
     *
     * Преобразуется в фильтр Bitrix:
     *   '%FIELD' => 'value'
     *
     * Пример:
     *   ->whereLike('NAME', 'iphone')
     *   => NAME LIKE '%iphone%'
     *
     * @param string $field  Поле инфоблока
     * @param string $value  Поисковая строка (без %)
     *
     * @return static
     */
    public function whereLike(string $field, string $value): static
    {
        $this->filter['%' . $field] = $value;
        return $this;
    }

    /**
     * Фильтр по свойству инфоблока.
     *
     * Поддержка:
     * - equals
     * - comparison operators
     * - like
     *
     * Примеры:
     * whereProperty('PRICE', 100)
     * whereProperty('PRICE', '>=', 100)
     *
     * @param string $propertyCode
     * @param mixed $operatorOrValue
     * @param mixed|null $value
     * @return static
     */
    public function whereProperty(string $propertyCode, mixed $operatorOrValue, mixed $value = null): static
    {
        return $this->where('PROPERTY_' . strtoupper($propertyCode), $operatorOrValue, $value);
    }

    // ──────────────────────────────────────────────
    // OR-условия
    // ──────────────────────────────────────────────

    /**
     * OR условие.
     *
     * ВАЖНО: создаёт отдельную OR-группу.
     *
     * orWhere('NAME', 'A')
     * → OR(NAME = A)
     *
     * @param string $field
     * @param mixed $operatorOrValue
     * @param mixed|null $value
     * @return static
     */
    public function orWhere(string $field, mixed $operatorOrValue, mixed $value = null): static
    {
        $condition = $this->buildConditionPair($field, $operatorOrValue, $value);

        $this->orGroups[] = [
            'LOGIC' => 'OR',
            $condition[0] => $condition[1],
        ];

        return $this;
    }

    /**
     * Группировка OR условий через callback.
     *
     * Пример:
     *
     * orGroup(function($q) {
     *     $q->where('NAME', 'A')
     *       ->where('CODE', 'B');
     * })
     *
     * → (NAME = A OR CODE = B)
     *
     * @param callable(static): void $callback
     * @return static
     */
    public function orGroup(callable $callback): static
    {
        $builder = new static();

        // наследуем текущие фильтры (если нужно)
        $builder->filter = [];

        $callback($builder);

        $this->orGroups[] = [
            'LOGIC' => 'OR',
            ...$builder->filter,
        ];

        return $this;
    }

    /**
     * OR-условие по свойству инфоблока.
     *
     * Делегирует в orWhere(), автоматически добавляя префикс PROPERTY_.
     *
     * Примеры:
     *   ->orWhereProperty('COLOR', 'red')
     *   ->orWhereProperty('PRICE', '>=', 1000)
     *
     * @param string $propertyCode     Код свойства (без PROPERTY_)
     * @param mixed  $operatorOrValue  Оператор (=, !=, >, <, like, и т.д.) или значение
     * @param mixed  $value            Значение (если указан оператор)
     *
     * @return static
     */
    public function orWhereProperty(string $propertyCode, mixed $operatorOrValue, mixed $value = null): static
    {
        return $this->orWhere('PROPERTY_' . strtoupper($propertyCode), $operatorOrValue, $value);
    }

    /**
     * Добавить готовый OR-блок (raw массив Bitrix).
     *
     * @param array<string, mixed> $orBlock
     * @return static
     */
    public function orRaw(array $orBlock): static
    {
        if (!isset($orBlock['LOGIC'])) {
            $orBlock['LOGIC'] = 'OR';
        }

        $this->orGroups[] = $orBlock;

        return $this;
    }

    // ──────────────────────────────────────────────
    // Удобные шорткаты
    // ──────────────────────────────────────────────

    /**
     * Установить инфоблок.
     *
     * @param int $iblockId
     * @return static
     */
    public function iblock(int $iblockId): static
    {
        $this->filter['IBLOCK_ID'] = $iblockId;
        return $this;
    }

    /**
     * Только активные элементы.
     *
     * @param bool $active
     * @return static
     */
    public function active(bool $active = true): static
    {
        $this->filter['ACTIVE'] = $active ? 'Y' : 'N';
        return $this;
    }

    /**
     * Фильтр по секции.
     *
     * @param int|array<int> $sectionId
     * @param bool $includeSubsections
     * @return static
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
     * Ограничить выборку только активными по датам элементами.
     *
     * Добавляет в фильтр:
     *   ACTIVE_DATE = 'Y'
     *
     * @return static
     */
    public function activeDate(): static
    {
        $this->filter['ACTIVE_DATE'] = 'Y';
        return $this;
    }

    /**
     * Включить элементы из истории workflow.
     *
     * Добавляет в фильтр:
     *   SHOW_HISTORY = 'Y'
     *
     * Используется для получения элементов,
     * которые находятся в изменённых/архивных версиях.
     *
     * @return static
     */
    public function withHistory(): static
    {
        $this->filter['SHOW_HISTORY'] = 'Y';
        return $this;
    }

    /**
     * Добавить "сырой" кусок фильтра Bitrix (array как в CIBlock::GetList).
     *
     * ⚠️ МЕРДЖИТСЯ С ТЕКУЩИМ фильтром без проверки конфликтов.
     *
     * @param array<string, mixed> $filterPart
     *
     * Пример:
     *   ->whereRaw([
     *       '>ID' => 10,
     *       'ACTIVE' => 'Y',
     *   ])
     *
     * @return static
     */
    public function whereRaw(array $filterPart): static
    {
        $this->filter[] = $filterPart;
        return $this;
    }

    // ──────────────────────────────────────────────
    // Сборка итогового фильтра
    // ──────────────────────────────────────────────

    /**
     * Собирает итоговый фильтр для CIBlockElement::GetList.
     *
     * Возвращает структуру:
     * [
     *   'IBLOCK_ID' => 5,
     *   'ACTIVE' => 'Y',
     *   [
     *     'LOGIC' => 'OR',
     *     ...
     *   ]
     * ]
     *
     * @internal используется только внутри Query Builder
     *
     * @return array<string, mixed>
     */
    protected function buildFilter(): array
    {
        // Фиксируем незакрытую открытую OR-группу если есть
        $this->closeCurrentOrGroup();

        if (empty($this->orGroups)) {
            return $this->filter;
        }

        // Склеиваем: сначала AND-условия, потом OR-группы как вложенные массивы
        return array_merge($this->filter, $this->orGroups);
    }

    // ──────────────────────────────────────────────
    // Внутренние хелперы
    // ──────────────────────────────────────────────

    /**
     * Преобразует SQL-like оператор в формат Bitrix фильтра.
     *
     * '='   → '='
     * '!='  → '!'
     * 'like'→ '%'
     *
     * @param string $operator
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    private function resolveOperatorPrefix(string $operator): string
    {
        return match (strtolower($operator)) {
            '='             => '=',
            '!=', '!'       => '!',
            '>'             => '>',
            '>='            => '>=',
            '<'             => '<',
            '<='            => '<=',
            'like', '%'     => '%',
            '!like', '!%'   => '!%',
            'fulltext', '?' => '?',
            default         => throw new \InvalidArgumentException("Unknown operator: $operator"),
        };
    }

    /**
     * Преобразует условие в формат Bitrix filter pair.
     *
     * @param string $field
     * @param mixed $operatorOrValue
     * @param mixed $value
     *
     * @return array{string, mixed}
     */
    private function buildConditionPair(string $field, mixed $operatorOrValue, mixed $value): array
    {
        if ($value === null) {
            return [$field, $operatorOrValue];
        }
        $prefix = $this->resolveOperatorPrefix((string)$operatorOrValue);
        return [$prefix . $field, $value];
    }

    /**
     * Добавить условие в текущую открытую OR-группу.
     * Текущая открытая группа хранится в $this->currentOrGroup.
     */
    private function pushToCurrentOrGroup(array $condition): void
    {
        if (!isset($this->currentOrGroup['LOGIC'])) {
            $this->currentOrGroup['LOGIC'] = 'OR';
        }
        [$key, $val] = $condition;
        $this->currentOrGroup[$key] = $val;
    }

    /**
     * Зафиксировать текущую OR-группу в $this->orGroups и сбросить буфер.
     */
    private function closeCurrentOrGroup(): void
    {
        if (!empty($this->currentOrGroup)) {
            $this->orGroups[] = $this->currentOrGroup;
            $this->currentOrGroup = [];
        }
    }

    protected function buildOrGroup(): array
    {
        $group = ['LOGIC' => 'OR'];

        foreach ($this->filter as $k => $v) {
            $group[$k] = $v;
        }

        $this->filter = []; // очищаем локальный контекст группы

        return $group;
    }
}