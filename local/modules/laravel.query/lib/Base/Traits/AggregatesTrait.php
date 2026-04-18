<?php

namespace Query\Base\Traits;

/**
 * Агрегирующие и терминальные методы — выполняют запрос и возвращают результат.
 *
 * Трейт требует наличия метода executeGetList() в классе-потребителе (BaseQuery).
 * Все методы здесь — конечные точки цепочки.
 */
trait AggregatesTrait
{
    /**
     * Вернуть все результаты как массив массивов.
     *
     * @return array[]
     */
    public function get(): array
    {
        $res   = $this->executeGetList();
        $items = [];

        while ($row = $res->Fetch()) {
            $items[] = $row;
        }

        return $items;
    }

    /**
     * Вернуть первый элемент или null.
     */
    public function first(): ?array
    {
        // Временно ставим limit(1) не меняя оригинальный limitValue
        $originalLimit  = $this->limitValue;
        $originalNav    = $this->navParams;

        $this->limitValue = 1;
        $this->navParams  = false;

        $res = $this->executeGetList();
        $row = $res->Fetch() ?: null;

        // Восстанавливаем
        $this->limitValue = $originalLimit;
        $this->navParams  = $originalNav;

        return $row ?: null;
    }

    /**
     * Вернуть количество записей.
     * Использует GetList с пустым $arGroupBy = [] — Битрикс вернёт COUNT.
     */
    public function count(): int
    {
        $result = $this->executeCount();
        return is_int($result) ? $result : 0;
    }

    /**
     * Проверить существование хотя бы одной записи.
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Проверить что записей нет.
     */
    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    /**
     * Обработать все результаты чанками по $size элементов.
     * Полезно для массовой обработки без загрузки всего в память.
     *
     * ElementQuery::query()
     *     ->iblock(5)
     *     ->active()
     *     ->chunk(100, function(array $items) {
     *         foreach ($items as $item) { ... }
     *     });
     *
     * Callback может вернуть false чтобы прервать обход.
     *
     * @param int $size Размер чанка
     * @param callable(array<int, array<string, mixed>>, int): (bool|null) $callback
     *         Callback получает:
     *           - array $items — массив элементов (каждый элемент — ассоциативный массив)
     *           - int   $page  — номер текущей страницы (с 1)
     *
     *         Если callback возвращает false — обход прерывается.
     *
     * @return bool false если прервано callback'ом, иначе true
     */
    public function chunk(int $size, callable $callback): bool
    {
        $page = 1;

        do {
            $items = $this->forPage($page, $size)->get();

            if (empty($items)) {
                break;
            }

            if ($callback($items, $page) === false) {
                return false;
            }

            $page++;

        } while (count($items) === $size);

        return true;
    }

    /**
     * Вернуть нативный объект CIBlockResult без итерирования.
     * Escape-хатч для совместимости со старым кодом.
     */
    public function getResult(): \CIBlockResult
    {
        return $this->executeGetList();
    }

    /**
     * Вернуть массив значений одного поля (как pluck в Laravel).
     *
     * ElementQuery::query()->iblock(5)->pluck('NAME')
     * // => ['Товар 1', 'Товар 2', ...]
     *
     * ElementQuery::query()->iblock(5)->pluck('NAME', 'ID')
     * // => [42 => 'Товар 1', 43 => 'Товар 2', ...]
     */
    public function pluck(string $valueField, string $keyField = ''): array
    {
        // Добавляем нужные поля в select если они там не указаны
        $originalSelect = $this->select;

        if (!empty($this->select)) {
            $needed = array_filter(
                [$valueField, $keyField ?: null],
                fn($f) => $f !== null && !in_array($f, $this->select, true)
            );
            if (!empty($needed)) {
                $this->select = array_merge($this->select, $needed);
            }
        }

        $rows  = $this->get();
        $result = [];

        foreach ($rows as $row) {
            $val = $row[$valueField] ?? null;
            if ($keyField !== '' && isset($row[$keyField])) {
                $result[$row[$keyField]] = $val;
            } else {
                $result[] = $val;
            }
        }

        $this->select = $originalSelect;

        return $result;
    }
}