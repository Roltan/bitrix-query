<?php

namespace Query;

use Query\Base\BaseQuery;

/**
 * Fluent query builder для разделов инфоблока (CIBlockSection).
 *
 * Примеры:
 *
 * // Получить разделы инфоблока
 * $sections = SectionQuery::query()
 *     ->iblock(5)
 *     ->active()
 *     ->orderBy('SORT')
 *     ->get();
 *
 * // Найти раздел по ID
 * $section = SectionQuery::find(10)->fetchOne();
 *
 * // Дочерние разделы
 * $sections = SectionQuery::query()
 *     ->iblock(5)
 *     ->parent(10)
 *     ->get();
 *
 * // С подразделами (включая вложенные)
 * $sections = SectionQuery::query()
 *     ->iblock(5)
 *     ->where('LEFT_MARGIN', '>=', 10)
 *     ->where('RIGHT_MARGIN', '<=', 20)
 *     ->get();
 *
 * // Количество
 * $cnt = SectionQuery::query()->iblock(5)->active()->count();
 */
class SectionQuery extends BaseQuery
{
    // ──────────────────────────────────────────────
    // Фабричные методы
    // ──────────────────────────────────────────────

    /**
     * Найти раздел по ID.
     *
     * @param int $id ID раздела
     * @return static
     *
     * @example
     * SectionQuery::find(10)->fetchOne();
     */
    public static function find(int $id): static
    {
        $instance = new static();
        $instance->filter['ID'] = $id;
        $instance->currentId    = $id;
        return $instance;
    }

    // ──────────────────────────────────────────────
    // Удобные методы для секций
    // ──────────────────────────────────────────────

    /**
     * Фильтр по родительскому разделу.
     *
     * @param int $sectionId ID родителя
     * @return static
     *
     * @example
     * SectionQuery::query()->parent(10)->get();
     */
    public function parent(int $sectionId): static
    {
        $this->filter['SECTION_ID'] = $sectionId;
        return $this;
    }

    /**
     * Только корневые разделы (без родителя).
     *
     * @return static
     *
     * @example
     * SectionQuery::query()->root()->get();
     */
    public function root(): static
    {
        $this->filter['SECTION_ID'] = false;
        return $this;
    }

    /**
     * Включить подразделы в выборку.
     *
     * @param bool $include Включать ли вложенные разделы
     * @return static
     *
     * @example
     * ->withSubsections()
     */
    public function withSubsections(bool $include = true): static
    {
        if ($include) {
            $this->filter['INCLUDE_SUBSECTIONS'] = 'Y';
        }
        return $this;
    }

    /**
     * Фильтр по уровню вложенности (DEPTH_LEVEL).
     *
     * @param int $level Уровень вложенности
     * @return static
     *
     * @example
     * ->depth(2)
     */
    public function depth(int $level): static
    {
        $this->filter['DEPTH_LEVEL'] = $level;
        return $this;
    }

    /**
     * Фильтр по диапазону LEFT_MARGIN / RIGHT_MARGIN.
     *
     * Используется для работы с деревом разделов.
     *
     * @param int $left
     * @param int $right
     * @return static
     *
     * @example
     * ->whereBetweenMargins(10, 20)
     */
    public function whereBetweenMargins(int $left, int $right): static
    {
        $this->filter['>=LEFT_MARGIN'] = $left;
        $this->filter['<=RIGHT_MARGIN'] = $right;
        return $this;
    }

    /**
     * Прямые дочерние разделы.
     *
     * @param int $sectionId
     * @return static
     *
     * @example
     * ->childrenOf(10)
     */
    public function childrenOf(int $sectionId): static
    {
        return $this->parent($sectionId);
    }

    /**
     * Все вложенные разделы (через LEFT/RIGHT margins).
     *
     * @param int $sectionId
     * @return static
     *
     * @example
     * ->descendantsOf(10)
     */
    public function descendantsOf(int $sectionId): static
    {
        $section = static::find($sectionId)
            ->select(['LEFT_MARGIN', 'RIGHT_MARGIN'])
            ->fetchOne();

        if (!$section) {
            return $this;
        }

        return $this->whereBetweenMargins(
            (int)$section['LEFT_MARGIN'],
            (int)$section['RIGHT_MARGIN']
        );
    }

    /**
     * Получить элементы текущего раздела.
     *
     * ⚠️ Требует, чтобы текущий запрос содержал ID (через find() или filter).
     *
     * @param bool $includeSubsections Включать элементы из подразделов
     * @return ElementQuery
     *
     * @example
     * SectionQuery::find(10)->elements()->get();
     *
     * @example
     * SectionQuery::find(10)
     *     ->elements(true)
     *     ->where('ACTIVE', 'Y')
     *     ->get();
     */
    public function elements(bool $includeSubsections = false): ElementQuery
    {
        $sectionId = $this->currentId;

        if (!$sectionId && isset($this->filter['ID'])) {
            $sectionId = (int)$this->filter['ID'];
        } else if(!$sectionId and $this->count() > 0) {
            $sectionId = $this->select(['ID'])->get();
            $sectionId = array_column($sectionId, 'ID');
        }

        $query = ElementQuery::query();

        if ($sectionId > 0) {
            $query->section($sectionId, $includeSubsections);
        }

        return $query;
    }

    // ──────────────────────────────────────────────
    // Терминальные методы (write)
    // ──────────────────────────────────────────────

    /**
     * Обновить раздел.
     *
     * @param int|array $idOrFields ID или массив полей
     * @param array $fields Поля для обновления
     * @return bool
     *
     * @example
     * ->update(10, ['NAME' => 'Новое имя'])
     *
     * @example
     * SectionQuery::find(10)->update(['NAME' => 'Новое имя']);
     */
    public function update(int|array $idOrFields, array $fields = []): bool
    {
        [$id, $data] = $this->resolveIdAndFields($idOrFields, $fields);

        if ($id <= 0) {
            return false;
        }

        $bs = new \CIBlockSection();
        return (bool)$bs->Update($id, $data);
    }

    /**
     * Удалить раздел.
     *
     * @param int $id ID (если не передан — используется текущий)
     * @return bool
     *
     * @example
     * ->delete(10)
     *
     * @example
     * SectionQuery::find(10)->delete();
     */
    public function delete(int $id = 0): bool
    {
        $targetId = $id > 0 ? $id : $this->currentId;

        if ($targetId <= 0) {
            return false;
        }

        return (bool)\CIBlockSection::Delete($targetId);
    }

    /**
     * Получить один раздел.
     *
     * @return array|null
     *
     * @example
     * ->fetchOne()
     */
    public function fetchOne(): ?array
    {
        return $this->first();
    }

    /**
     * Последняя ошибка Bitrix.
     *
     * @return string
     */
    public static function getLastError(): string
    {
        $bs = new \CIBlockSection();
        return $bs->LAST_ERROR;
    }

    // ──────────────────────────────────────────────
    // Реализация BaseQuery
    // ──────────────────────────────────────────────

    /**
     * Выполнение основного запроса.
     *
     * @return \CIBlockResult
     */
    protected function executeGetList(): \CIBlockResult
    {
        return \CIBlockSection::GetList(
            $this->order ?? [],
            $this->buildFilter(),
            false,
            $this->select ?? [],
            $this->buildNavParams()
        );
    }

    /**
     * Подсчёт количества.
     *
     * @return int
     */
    protected function executeCount(): int
    {
        $result = \CIBlockSection::GetList(
            $this->order,
            $this->buildFilter(),
            false,
            ['ID'],
            false
        );

        $count = 0;
        while ($result->fetch()) $count += 1;

        return is_int($count) ? $count : 0;
    }

    // ──────────────────────────────────────────────
    // Вспомогательное
    // ──────────────────────────────────────────────

    /**
     * Определяет ID и поля для update().
     *
     * @param int|array $idOrFields
     * @param array $fields
     * @return array{0:int,1:array}
     */
    private function resolveIdAndFields(int|array $idOrFields, array $fields): array
    {
        if (is_array($idOrFields)) {
            return [(int)$this->currentId, $idOrFields];
        }

        return [(int)$idOrFields, $fields];
    }
}