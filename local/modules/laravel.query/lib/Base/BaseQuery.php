<?php

namespace Query\Base;

use Query\Base\Traits\AggregatesTrait;
use Query\Base\Traits\FilterTrait;
use Query\Base\Traits\OrderTrait;
use Query\Base\Traits\PaginationTrait;
use Query\Base\Traits\SelectTrait;

/**
 * Абстрактный базовый класс для всех query-builders над сущностями Битрикса.
 *
 * Хранит накопленное состояние запроса и определяет контракт:
 * каждый наследник обязан реализовать executeGetList() и executeCount()
 * вызывая конкретный нативный класс (CIBlockElement, CIBlockSection, и т.д.).
 *
 * Структура наследования:
 *
 *   BaseQuery  (этот класс)
 *     └── ElementQuery  — оборачивает CIBlockElement::GetList()
 *     └── SectionQuery  — оборачивает CIBlockSection::GetList()
 *     └── UserQuery     — оборачивает CUser::GetList()
 *     └── ...
 */
abstract class BaseQuery
{
    use FilterTrait;
    use OrderTrait;
    use SelectTrait;
    use PaginationTrait;
    use AggregatesTrait;

    // ──────────────────────────────────────────────
    // Состояние запроса
    // Объявлено здесь, т.к. трейты к нему обращаются.
    // ──────────────────────────────────────────────

    /** Сортировка: ['FIELD' => 'ASC|DESC'] */
    protected array $order = ['SORT' => 'ASC'];

    /** AND-условия фильтра */
    protected array $filter = [];

    /**
     * Зафиксированные OR-группы — вложенные массивы с LOGIC=OR.
     * Каждый элемент — готовый блок для слияния с $filter в buildFilter().
     */
    protected array $orGroups = [];

    /**
     * Буфер текущей открытой OR-группы.
     * Фиксируется при вызове orGroup(), orRaw() или buildFilter().
     */
    protected array $currentOrGroup = [];

    /** GROUP BY: массив полей или false (без группировки) */
    protected array|false $groupBy = false;

    /** SELECT-поля (передаётся в GetList как $arSelectFields) */
    protected array $select = [];

    /** Лимит записей (nTopCount), false = без лимита */
    protected int|false $limitValue = false;

    /** Смещение (nOffset) */
    protected int $offsetValue = 0;

    /** Параметры навигации Битрикса или false */
    protected array|false $navParams = false;

    /** ID текущего элемента — задаётся в find(), используется в update()/delete() */
    protected int|false $currentId = false;

    // ──────────────────────────────────────────────
    // Фабричный метод
    // ──────────────────────────────────────────────

    /**
     * Создать новый экземпляр билдера.
     * Использование: ElementQuery::query()->iblock(5)->active()->get()
     */
    public static function query(): static
    {
        return new static();
    }

    // ──────────────────────────────────────────────
    // Контракт — обязателен для реализации в наследнике
    // ──────────────────────────────────────────────

    /**
     * Выполнить выборку и вернуть нативный result-объект Битрикса.
     * Реализация должна вызвать GetList нативного класса с накопленными параметрами.
     */
    abstract protected function executeGetList(): \CIBlockResult;

    /**
     * Выполнить подсчёт записей и вернуть число.
     * Обычно это GetList с пустым $arGroupBy = [].
     */
    abstract protected function executeCount(): int;

    // ──────────────────────────────────────────────
    // Вспомогательные методы для наследников
    // ──────────────────────────────────────────────

    /**
     * Получить текущий фильтр (для отладки или кастомных расширений).
     */
    public function getFilter(): array
    {
        return $this->filter;
    }

    /**
     * Получить текущий порядок сортировки.
     */
    public function getOrder(): array
    {
        return $this->order;
    }

    /**
     * Получить текущий список полей SELECT.
     */
    public function getSelect(): array
    {
        return $this->select;
    }

    /**
     * Клонировать билдер — удобно когда нужно несколько вариантов запроса
     * от одной базы без мутации оригинала.
     *
     * $base = ElementQuery::query()->iblock(5)->active();
     * $all  = (clone $base)->get();
     * $cnt  = (clone $base)->count();
     */
    public function clone(): static
    {
        return clone $this;
    }
}