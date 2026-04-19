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
     * Агрегирующие и терминальные методы — выполняют запрос и возвращают результат.
     *
     * ⚠️ ВАЖНО:
     * Все методы этого трейта являются "terminal operations"
     * (завершают цепочку построения запроса).
     *
     * Требует:
     *   - executeGetList(): \CIBlockResult
     *   - executeCount(): int
     *
     * Работает поверх состояния BaseQuery:
     *   - filter
     *   - select
     *   - order
     *   - pagination
     */
    public function get(): array
    {
        $res = $this->executeGetList();
        $items = [];

        while ($row = $res->Fetch()) {
            $items[] = $row;
        }

        if (!empty($this->relations)) {
            $items = $this->loadRelationsElements($items);
        }

        return $items;
    }

    /**
     * Получить первый элемент выборки.
     *
     * ⚠️ Временно применяет limit(1) без изменения исходного состояния билдера.
     *
     * @return array<string, mixed>|null
     *         Первый найденный элемент или null, если записей нет.
     */
    public function first(): ?array
    {
        // Временно ставим limit(1) не меняя оригинальный limitValue
        $originalLimit = $this->limitValue;
        $originalNav = $this->navParams;

        $this->limitValue = 1;
        $this->navParams = false;

        $res = $this->executeGetList();
        $row = $res->Fetch() ?: null;

        // Восстанавливаем
        $this->limitValue = $originalLimit;
        $this->navParams = $originalNav;

        if ($row === null || $row === false) {
            return null;
        }

        // Relations для первого элемента тоже загружаем
        if (!empty($this->relations)) {
            $items = $this->loadRelationsElements([$row]);
            return $items[0] ?? null;
        }

        return $row ?: null;
    }

    /**
     * Получить количество записей по текущему фильтру.
     *
     * @return int
     *         Количество записей (COUNT)
     */
    public function count(): int
    {
        $result = $this->executeCount();
        return is_int($result) ? $result : 0;
    }

    /**
     * Проверить наличие хотя бы одной записи.
     *
     * @return bool
     *         true — если записи существуют
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Проверить отсутствие записей.
     *
     * @return bool
     *         true — если записей нет
     */
    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    /**
     * Обработать результаты чанками (batch processing).
     *
     * Используется для обработки больших выборок без загрузки всей таблицы в память.
     *
     * Пример:
     *   ElementQuery::query()
     *       ->iblock(5)
     *       ->active()
     *       ->chunk(100, function (array $items, int $page): bool {
     *           foreach ($items as $item) {
     *               // обработка
     *           }
     *
     *           return true; // вернуть false для остановки
     *       });
     *
     * @param int $size
     *        Размер одного чанка (количество элементов за итерацию)
     *
     * @param callable(array<int, array<string, mixed>>, int): (bool|null) $callback
     *        Callback принимает:
     *          - array $items : элементы текущего чанка
     *          - int   $page  : номер страницы (начинается с 1)
     *
     *        Возврат:
     *          - false → остановить обработку
     *          - true|null → продолжить
     *
     * @return bool
     *         false — если выполнение было прервано callback'ом
     *         true  — если обработка завершена полностью
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
     * Вернуть нативный CIBlockResult (Bitrix API).
     *
     * ⚠️ Escape-хук для legacy-кода.
     *
     * @return \CIBlockResult
     */
    public function getResult(): \CIBlockResult
    {
        return $this->executeGetList();
    }

    /**
     * Извлечь значения одного поля (аналог Laravel pluck()).
     *
     * Пример:
     *   pluck('NAME')
     *   => ['Товар 1', 'Товар 2']
     *
     *   pluck('NAME', 'ID')
     *   => [42 => 'Товар 1', 43 => 'Товар 2']
     *
     * @param string $valueField
     *        Поле, значения которого нужно извлечь
     *
     * @param string $keyField
     *        Поле, которое будет ключом массива (опционально)
     *
     * @return array<int|string, mixed>
     *         Массив значений или key-value структура
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

        $rows = $this->get();
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