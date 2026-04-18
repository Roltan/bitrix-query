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
     * Универсальное AND-условие.
     *
     * ->where('NAME', 'Товар')               // равенство
     * ->where('SORT', '>', 10)               // сравнение
     * ->where('NAME', 'like', '%Товар%')     // LIKE
     * ->where('ID', [1, 2, 3])              // IN
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
     * WHERE field NOT.
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
     */
    public function whereBetween(string $field, mixed $min, mixed $max): static
    {
        $this->filter['>=' . $field] = $min;
        $this->filter['<=' . $field] = $max;
        return $this;
    }

    /**
     * WHERE field IS NULL / пусто.
     */
    public function whereNull(string $field): static
    {
        $this->filter[$field] = false;
        return $this;
    }

    /**
     * WHERE field LIKE value.
     */
    public function whereLike(string $field, string $value): static
    {
        $this->filter['%' . $field] = $value;
        return $this;
    }

    /**
     * Фильтр по свойству инфоблока (AND).
     *
     * ->whereProperty('PRICE', 1000)
     * ->whereProperty('PRICE', '>=', 1000)
     * ->whereProperty('COLOR', 'like', '%red%')
     */
    public function whereProperty(string $propertyCode, mixed $operatorOrValue, mixed $value = null): static
    {
        return $this->where('PROPERTY_' . strtoupper($propertyCode), $operatorOrValue, $value);
    }

    // ──────────────────────────────────────────────
    // OR-условия
    // ──────────────────────────────────────────────

    /**
     * Добавить OR-условие.
     *
     * Несколько вызовов orWhere подряд попадают в ОДНУ OR-группу:
     *
     *   ->where('IBLOCK_ID', 5)
     *   ->orWhere('NAME', 'Товар А')
     *   ->orWhere('NAME', 'Товар Б')
     *
     * Генерирует:
     *   IBLOCK_ID = 5 AND (NAME = 'Товар А' OR NAME = 'Товар Б')
     *
     * Чтобы начать новую независимую OR-группу — используй orGroup().
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
     * OR-группа через callback (Laravel-style)
     *
     * ->orGroup(function($q) {
     *     $q->where('NAME', 'A')
     *       ->where('CODE', 'b');
     * })
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
     * ->orWhereProperty('COLOR', 'red')
     * ->orWhereProperty('COLOR', '!=', 'blue')
     */
    public function orWhereProperty(string $propertyCode, mixed $operatorOrValue, mixed $value = null): static
    {
        return $this->orWhere('PROPERTY_' . strtoupper($propertyCode), $operatorOrValue, $value);
    }

    /**
     * Передать готовый OR-блок напрямую — для сложных случаев.
     *
     * ->orRaw(['LOGIC' => 'OR', 'NAME' => 'А', '=CODE' => 'b'])
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
     * Фильтр по инфоблоку.
     */
    public function iblock(int $iblockId): static
    {
        $this->filter['IBLOCK_ID'] = $iblockId;
        return $this;
    }

    /**
     * Только активные записи.
     */
    public function active(bool $active = true): static
    {
        $this->filter['ACTIVE'] = $active ? 'Y' : 'N';
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
     * Элементы активные на текущий момент (ACTIVE_FROM / ACTIVE_TO).
     */
    public function activeDate(): static
    {
        $this->filter['ACTIVE_DATE'] = 'Y';
        return $this;
    }

    /**
     * Показывать историю workflow.
     */
    public function withHistory(): static
    {
        $this->filter['SHOW_HISTORY'] = 'Y';
        return $this;
    }

    /**
     * Добавить произвольный кусок фильтра (raw-массив, как в GetList).
     */
    public function whereRaw(array $filterPart): static
    {
        $this->filter = array_merge($this->filter, $filterPart);
        return $this;
    }

    // ──────────────────────────────────────────────
    // Сборка итогового фильтра
    // ──────────────────────────────────────────────

    /**
     * Собрать итоговый $arFilter для передачи в GetList.
     *
     * Склеивает AND-условия ($this->filter) и все OR-группы ($this->orGroups)
     * в один массив, который Битрикс умеет парсить.
     *
     * @internal вызывается из BaseQuery::executeGetList()
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
     * Преобразовать оператор в префикс фильтра Битрикса.
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
     * Собрать пару [ключ => значение] для одного условия.
     *
     * @return array{string, mixed}  [filterKey, value]
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