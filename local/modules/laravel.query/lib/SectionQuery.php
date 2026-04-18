<?php

namespace Query;

use Query\Base\BaseQuery;

/**
 * Fluent query builder для разделов инфоблока (CIBlockSection).
 *
 * Скелет — демонстрирует как добавить новую сущность.
 * Достаточно унаследоваться от BaseQuery и реализовать
 * executeGetList() + executeCount() вызывая нужный нативный класс.
 *
 * Примеры использования:
 *
 *   $sections = SectionQuery::query()
 *       ->iblock(5)
 *       ->active()
 *       ->where('DEPTH_LEVEL', 1)
 *       ->orderBy('SORT')
 *       ->get();
 *
 *   $id = SectionQuery::create([
 *       'IBLOCK_ID' => 5,
 *       'NAME'      => 'Новый раздел',
 *   ]);
 *
 *   SectionQuery::find(10)->update(['NAME' => 'Переименован']);
 *   SectionQuery::find(10)->delete();
 */
class SectionQuery extends BaseQuery
{
    // ──────────────────────────────────────────────
    // Фабричные методы
    // ──────────────────────────────────────────────

    public static function find(int $id): static
    {
        $instance = new static();
        $instance->filter['ID'] = $id;
        $instance->currentId    = $id;
        return $instance;
    }

    public static function create(array $fields): int|false
    {
        $section = new \CIBlockSection();
        $id      = $section->Add($fields);
        return $id > 0 ? (int)$id : false;
    }

    // ──────────────────────────────────────────────
    // Специфичные для разделов методы фильтрации
    // ──────────────────────────────────────────────

    /**
     * Фильтр по уровню вложенности.
     *
     * ->depthLevel(1)  — только корневые разделы
     */
    public function depthLevel(int $level): static
    {
        $this->filter['DEPTH_LEVEL'] = $level;
        return $this;
    }

    /**
     * Фильтр по глобальной активности (GLOBAL_ACTIVE = Y).
     */
    public function globalActive(): static
    {
        $this->filter['GLOBAL_ACTIVE'] = 'Y';
        return $this;
    }

    /**
     * Дочерние разделы указанного раздела.
     */
    public function childOf(int $sectionId): static
    {
        $this->filter['SECTION_ID'] = $sectionId;
        return $this;
    }

    // ──────────────────────────────────────────────
    // Write-методы
    // ──────────────────────────────────────────────

    public function update(int|array $idOrFields, array $fields = []): bool
    {
        $id   = is_array($idOrFields) ? (int)$this->currentId : (int)$idOrFields;
        $data = is_array($idOrFields) ? $idOrFields : $fields;

        if ($id <= 0) {
            return false;
        }

        $section = new \CIBlockSection();
        return (bool)$section->Update($id, $data);
    }

    public function delete(int $id = 0): bool
    {
        $targetId = $id > 0 ? $id : $this->currentId;

        if ($targetId <= 0) {
            return false;
        }

        return (bool)\CIBlockSection::Delete($targetId);
    }

    // ──────────────────────────────────────────────
    // Реализация контракта BaseQuery
    // ──────────────────────────────────────────────

    protected function executeGetList(): \CIBlockResult
    {
        return \CIBlockSection::GetList(
            $this->order,
            $this->filter,
            false,          // bIncCnt — количество элементов в разделе
            $this->select,
            $this->buildNavParams() ?: false
        );
    }

    protected function executeCount(): int
    {
        // CIBlockSection::GetList не поддерживает COUNT через groupBy=[],
        // поэтому считаем через GetCount
        return (int)\CIBlockSection::GetCount($this->filter);
    }
}