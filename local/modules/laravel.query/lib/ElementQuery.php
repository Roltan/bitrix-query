<?php

namespace Query;

use Query\Base\BaseQuery;

/**
 * Fluent query builder для элементов инфоблока (CIBlockElement).
 *
 * Примеры использования:
 *
 *   // Выборка
 *   $items = ElementQuery::query()
 *       ->iblock(5)
 *       ->active()
 *       ->where('NAME', 'like', '%Товар%')
 *       ->whereProperty('PRICE', '>=', 1000)
 *       ->section(12, includeSubsections: true)
 *       ->select(['ID', 'NAME', 'PROPERTY_PRICE'])
 *       ->orderBy('SORT')
 *       ->limit(20)
 *       ->get();
 *
 *   // Первый элемент
 *   $item = ElementQuery::query()->iblock(5)->where('CODE', 'my-slug')->first();
 *
 *   // Поиск по ID
 *   $item = ElementQuery::find(42)->fetchOne();
 *
 *   // Количество
 *   $cnt = ElementQuery::query()->iblock(5)->active()->count();
 *
 *   // Пагинация
 *   $items = ElementQuery::query()->iblock(5)->paginate(20)->get();
 *
 *   // Чанки
 *   ElementQuery::query()->iblock(5)->chunk(100, function(array $items) {
 *       foreach ($items as $item) { ... }
 *   });
 *
 *   // Создание
 *   $id = ElementQuery::create([
 *       'IBLOCK_ID'       => 5,
 *       'NAME'            => 'Новый элемент',
 *       'ACTIVE'          => 'Y',
 *       'PROPERTY_VALUES' => ['PRICE' => 1500],
 *   ]);
 *
 *   // Обновление
 *   ElementQuery::find(42)->update(['NAME' => 'Переименован']);
 *   ElementQuery::query()->update(42, ['NAME' => 'Переименован']);
 *
 *   // Удаление
 *   ElementQuery::find(42)->delete();
 *   ElementQuery::query()->delete(42);
 *
 *   // Свойства
 *   ElementQuery::find(42)->setProperties(['PRICE' => 1500]);
 *
 *   // Нативный CIBlockResult (совместимость со старым кодом)
 *   $res = ElementQuery::query()->iblock(5)->getResult();
 *   while ($row = $res->Fetch()) { ... }
 */
class ElementQuery extends BaseQuery
{
    // ──────────────────────────────────────────────
    // Фабричные методы
    // ──────────────────────────────────────────────

    /**
     * Создать билдер с предустановленным фильтром по ID.
     * Возвращает экземпляр билдера — можно цеплять update()/delete()/fetchOne().
     */
    public static function find(int $id): static
    {
        $instance = new static();
        $instance->filter['ID']           = $id;
        $instance->filter['SHOW_HISTORY'] = 'Y';
        $instance->currentId              = $id;
        return $instance;
    }

    /**
     * Создать новый элемент инфоблока.
     *
     * @param  array    $fields  Поля элемента. Обязательные: IBLOCK_ID, NAME.
     * @return int|false         ID нового элемента или false при ошибке.
     */
    public static function create(array $fields): int|false
    {
        $el = new \CIBlockElement();
        $id = $el->Add($fields);
        return $id > 0 ? (int)$id : false;
    }

    // ──────────────────────────────────────────────
    // Терминальные write-методы
    // ──────────────────────────────────────────────

    /**
     * Обновить элемент.
     *
     * Варианты вызова:
     *   ElementQuery::find(42)->update(['NAME' => 'Новое'])
     *   ElementQuery::query()->update(42, ['NAME' => 'Новое'])
     *
     * @return bool
     */
    public function update(int|array $idOrFields, array $fields = []): bool
    {
        [$id, $data] = $this->resolveIdAndFields($idOrFields, $fields);

        if ($id <= 0) {
            return false;
        }

        $el = new \CIBlockElement();
        return (bool)$el->Update($id, $data);
    }

    /**
     * Удалить элемент.
     *
     * Варианты вызова:
     *   ElementQuery::find(42)->delete()
     *   ElementQuery::query()->delete(42)
     */
    public function delete(int $id = 0): bool
    {
        $targetId = $id > 0 ? $id : $this->currentId;

        if ($targetId <= 0) {
            return false;
        }

        return (bool)\CIBlockElement::Delete($targetId);
    }

    /**
     * Установить значения свойств элемента.
     *
     * Варианты вызова:
     *   ElementQuery::find(42)->setProperties(['PRICE' => 1500])
     *   ElementQuery::query()->setProperties(42, 5, ['PRICE' => 1500])
     *
     * @param  int|array  $idOrProps      ID элемента или массив свойств (при вызове через find())
     * @param  int|array  $iblockIdOrProps ID инфоблока или массив свойств
     * @param  array      $props          Массив свойств (при явном указании ID и IBLOCK_ID)
     */
    public function setProperties(int|array $idOrProps, int|array $iblockIdOrProps = 0, array $props = []): bool
    {
        if (is_array($idOrProps)) {
            // find(42)->setProperties(['PRICE' => 1500])
            $id       = $this->currentId;
            $iblockId = (int)($this->filter['IBLOCK_ID'] ?? 0);
            $data     = $idOrProps;
        } else {
            // query()->setProperties(42, 5, ['PRICE' => 1500])
            $id       = $idOrProps;
            $iblockId = is_int($iblockIdOrProps) ? $iblockIdOrProps : 0;
            $data     = is_array($iblockIdOrProps) ? $iblockIdOrProps : $props;
        }

        if ($id <= 0 || $iblockId <= 0) {
            return false;
        }

        \CIBlockElement::SetPropertyValues($id, $iblockId, $data);
        return true;
    }

    /**
     * Получить один элемент как массив (для использования после find()).
     *
     * ElementQuery::find(42)->fetchOne()
     * // => ['ID' => 42, 'NAME' => '...', ...]  или null
     */
    public function fetchOne(): ?array
    {
        return $this->first();
    }

    /**
     * Получить последнюю ошибку CIBlockElement.
     * Полезно после неудачного create() или update().
     */
    public static function getLastError(): string
    {
        $el = new \CIBlockElement();
        return $el->LAST_ERROR;
    }

    // ──────────────────────────────────────────────
    // Реализация контракта BaseQuery
    // ──────────────────────────────────────────────

    /**
     * Вызвать CIBlockElement::GetList() с накопленными параметрами.
     */
    protected function executeGetList(): \CIBlockResult
    {
        return \CIBlockElement::GetList(
            $this->order,
            $this->filter,
            $this->groupBy,
            $this->buildNavParams(),
            $this->select
        );
    }

    /**
     * Вызвать GetList в режиме подсчёта (пустой groupBy = COUNT).
     */
    protected function executeCount(): int
    {
        $result = \CIBlockElement::GetList(
            $this->order,
            $this->filter,
            [],     // пустой массив — Битрикс вернёт число
            false,
            $this->select
        );

        return is_int($result) ? $result : 0;
    }

    // ──────────────────────────────────────────────
    // Вспомогательные методы
    // ──────────────────────────────────────────────

    /**
     * Разобрать аргументы update() — определить откуда берётся ID.
     *
     * @return array{0: int, 1: array}
     */
    private function resolveIdAndFields(int|array $idOrFields, array $fields): array
    {
        if (is_array($idOrFields)) {
            return [(int)$this->currentId, $idOrFields];
        }
        return [(int)$idOrFields, $fields];
    }
}