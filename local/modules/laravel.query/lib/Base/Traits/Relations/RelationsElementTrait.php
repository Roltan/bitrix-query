<?php

namespace Query\Base\Traits\Relations;

/**
 * Трейт связей — eager loading связанных элементов инфоблока.
 *
 * Аналог with() / load() в Laravel Eloquent, адаптированный под
 * архитектуру свойств Битрикса (тип свойства "E" — Элемент).
 *
 * ──────────────────────────────────────────────────────────────
 * КАК РАБОТАЕТ withElement('BRAND')
 * ──────────────────────────────────────────────────────────────
 *
 * 1. Основной запрос выполняется как обычно и возвращает items[].
 *
 * 2. Из каждого item извлекается значение свойства BRAND_ID:
 *      - одиночное свойство → PROPERTY_BRAND_ID_VALUE = '42'
 *      - множественное      → PROPERTY_BRAND_ID_VALUE = ['42', '43']
 *
 * 3. Все уникальные ID собираются в один массив → [42, 43, ...]
 *
 * 4. Выполняется ОДИН дополнительный запрос:
 *      ElementQuery::query()->whereIn('ID', [42, 43, ...])->get()
 *    (плюс callback если передан — можно добавить select, iblock, и т.д.)
 *
 * 5. Результаты раскладываются обратно по items:
 *      - одиночное свойство → item['BRAND'] = ['ID' => 42, 'NAME' => '...']
 *      - множественное      → item['BRAND'] = [['ID' => 42, ...], ['ID' => 43, ...]]
 *
 * Итого: N+1 проблемы НЕТ — всегда 1 дополнительный запрос на связь,
 * независимо от количества основных элементов.
 *
 * ──────────────────────────────────────────────────────────────
 * ПРИМЕРЫ
 * ──────────────────────────────────────────────────────────────
 *
 * // Простой случай — одиночное свойство BRAND_ID
 * $items = ElementQuery::query()
 *     ->iblock(5)
 *     ->withElement('BRAND')
 *     ->get();
 *
 * // $items[0]['BRAND'] => ['ID' => 42, 'NAME' => 'Nike', ...]
 *
 * ──────────────────────────────────────────────────────────────
 *
 * // Множественное свойство TAGS_ID
 * $items = ElementQuery::query()
 *     ->iblock(5)
 *     ->withElement('TAGS')
 *     ->get();
 *
 * // $items[0]['TAGS'] => [['ID' => 1, 'NAME' => 'Sale'], ['ID' => 2, 'NAME' => 'New']]
 *
 * ──────────────────────────────────────────────────────────────
 *
 * // С настройкой вложенного запроса через callback
 * $items = ElementQuery::query()
 *     ->iblock(5)
 *     ->withElement('BRAND', function (ElementQuery $q) {
 *         $q->select(['ID', 'NAME', 'PROPERTY_LOGO'])
 *           ->active();
 *     })
 *     ->get();
 *
 * ──────────────────────────────────────────────────────────────
 *
 * // Несколько связей одновременно
 * $items = ElementQuery::query()
 *     ->iblock(5)
 *     ->withElement('BRAND')
 *     ->withElement('CATEGORY')
 *     ->get();
 *
 * // $items[0]['BRAND']    => [...]
 * // $items[0]['CATEGORY'] => [...]
 */
trait RelationsElementTrait
{
    /**
     * Зарегистрировать eager loading связанного элемента инфоблока.
     *
     * @param string $relationName Имя связи — код свойства БЕЗ суффикса _ID.
     *                                     Метод ищет свойство с кодом {$relationName}_ID
     *                                     и кладёт результат в ключ {$relationName}.
     *
     * @param callable|null $callback Опциональная настройка вложенного ElementQuery.
     *                                     Принимает экземпляр ElementQuery, возвращает void.
     *
     *                                     function (ElementQuery $query): void {
     *                                         $query->select(['ID', 'NAME'])->active();
     *                                     }
     *
     * @return static
     */
    public function withElement(string $relationName, ?callable $callback = null): static
    {
        $this->relations[] = [
            'relation' => strtoupper($relationName),
            'property' => strtoupper($relationName) . '_ID',
            'callback' => $callback,
            'origin' => 'element'
        ];

        $this->withProperty($relationName . '_ID');

        return $this;
    }

    // ──────────────────────────────────────────────
    // Внутренние методы — вызываются из AggregatesTrait
    // ──────────────────────────────────────────────

    /**
     * Применить все зарегистрированные relations к массиву items.
     *
     * Вызывается в get() ПОСЛЕ того как основной запрос выполнен.
     * Каждая связь — один дополнительный запрос (не N запросов).
     *
     * @param array[] $items Результат основного запроса (get())
     * @return array[]         Тот же массив с добавленными ключами связей
     */
    protected function loadRelationsElements(array $items): array
    {
        if (empty($items) || empty($this->relations)) {
            return $items;
        }

        $relations = array_filter($this->relations, function ($item) {
            return $item['origin'] === 'element';
        });

        foreach ($relations as $relation) {
            $items = $this->loadSingleRelation($items, $relation);
        }

        return $items;
    }

    /**
     * Загрузить одну связь и вшить данные в items.
     *
     * @param array[] $items
     * @param array{relation: string, property: string, callback: callable|null} $relation
     * @return array[]
     */
    private function loadSingleRelation(array $items, array $relation): array
    {
        $relationKey = $relation['relation'];   // 'BRAND'
        $propertyCode = $relation['property'];   // 'BRAND_ID'
        $callback = $relation['callback'];

        // Ключи которые Битрикс создаёт для свойства в результате GetList
        // Одиночное:   PROPERTY_{CODE}_VALUE
        // Множественное тоже PROPERTY_{CODE}_VALUE, но значение — массив
        $valueKey = 'PROPERTY_' . $propertyCode . '_VALUE';

        // ── Шаг 1: собрать все уникальные ID из всех items ──────────────

        // Map: itemIndex → array of related IDs (всегда массив для единообразия)
        $itemRelatedIds = [];
        $allIds = [];

        foreach ($items as $index => $item) {
            if (!array_key_exists($valueKey, $item)) {
                $itemRelatedIds[$index] = [];
                continue;
            }

            $raw = $item[$valueKey];

            // Нормализуем к массиву: одиночное свойство — строка/число,
            // множественное — уже массив от Битрикса (CIBlockResult::Fetch)
            $ids = $this->normalizePropertyIds($raw);

            $itemRelatedIds[$index] = $ids;

            foreach ($ids as $id) {
                $allIds[$id] = $id;
            }
        }

        if (empty($allIds)) {
            // Нет ID — проставляем пустые значения и выходим
            foreach ($items as $index => &$item) {
                $item[$relationKey] = [];
            }
            unset($item);
            return $items;
        }

        // ── Шаг 2: один запрос за всеми связанными элементами ───────────

        /** @var \Query\ElementQuery $query */
        $query = \Query\ElementQuery::query()->whereIn('ID', array_values($allIds));

        // Добавляем свойство в select если задан явный select
        // (чтобы не получить пустой результат из-за отсутствия полей)
        if ($callback !== null) {
            $callback($query);
        }

        $related = $query->get();

        // Индексируем по ID для быстрого доступа
        // Один ID → один элемент (свойство типа E всегда ссылается на конкретный элемент)
        $relatedById = [];
        foreach ($related as $relatedItem) {
            $relatedById[(int)$relatedItem['ID']] = $relatedItem;
        }

        // ── Шаг 3: вшить данные обратно в items ─────────────────────────

        foreach ($items as $index => &$item) {
            $ids = $itemRelatedIds[$index];

            if (empty($ids)) {
                // Нет связанных — null для одиночного, [] для множественного
                $item[$relationKey] = $this->isMultiplePropertyValue($item[$valueKey] ?? null)
                    ? []
                    : null;
                continue;
            }

            if (!is_array($item[$valueKey])) {
                // Одиночное свойство — кладём элемент напрямую (или null если не найден)
                $id = reset($ids);
                $item[$relationKey] = $relatedById[$id] ?? null;
            } else {
                // Множественное — массив элементов в том же порядке что были ID
                $item[$relationKey] = array_values(
                    array_filter(
                        array_map(
                            fn(int $id) => $relatedById[$id] ?? null,
                            $ids
                        )
                    )
                );
            }
        }
        unset($item);

        return $items;
    }

    /**
     * Нормализовать значение свойства к массиву целых ID.
     *
     * Битрикс возвращает:
     *   - одиночное незаполненное: false / '' / null
     *   - одиночное заполненное:  '42' (строка)
     *   - множественное пустое:   false
     *   - множественное заполненное: ['42', '43'] (массив строк)
     *
     * @param mixed $raw
     * @return int[]
     */
    private function normalizePropertyIds(mixed $raw): array
    {
        if ($raw === false || $raw === null || $raw === '') {
            return [];
        }

        $values = is_array($raw) ? $raw : [$raw];

        $ids = [];
        foreach ($values as $v) {
            $id = (int)$v;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Определить является ли значение свойства множественным.
     * Используется для корректного типа пустого значения (null vs []).
     *
     * @param mixed $raw
     * @return bool
     */
    private function isMultiplePropertyValue(mixed $raw): bool
    {
        return is_array($raw);
    }
}