<?php

namespace Query\Base\Traits;

/**
 * Методы пагинации и ограничения выборки.
 *
 * Трейт работает с полями:
 *   $this->limitValue   — int|false (nTopCount)
 *   $this->offsetValue  — int       (nOffset)
 *   $this->navParams    — array|false (постраничная навигация Битрикса)
 *
 * Важно: limit/offset и paginate взаимоисключающие.
 * Последний вызванный побеждает.
 */
trait PaginationTrait
{
    /**
     * Ограничить количество возвращаемых записей (LIMIT).
     *
     * Пример:
     *   ->limit(20)
     *
     * ⚠ Сбрасывает paginate()
     *
     * @param int $limit
     *        Количество записей (>= 1)
     *
     * @throws \InvalidArgumentException если $limit < 1
     *
     * @return static
     */
    public function limit(int $limit): static
    {
        if ($limit < 1) {
            throw new \InvalidArgumentException('Limit must be >= 1');
        }

        $this->limitValue = $limit;
        $this->navParams  = false; // сбрасываем paginate если был
        return $this;
    }

    /**
     * Смещение (OFFSET) относительно начала выборки.
     *
     * Работает только вместе с limit().
     *
     * Пример:
     *   ->limit(20)->offset(40)
     *
     * ⚠ Bitrix реализует через nOffset
     *
     * @param int $offset
     *        Количество пропускаемых записей
     *
     * @throws \LogicException если limit() не задан
     *
     * @return static
     */
    public function offset(int $offset): static
    {
        if ($this->limitValue === false) {
            throw new \LogicException('Offset requires limit()');
        }

        $this->offsetValue = $offset;
        return $this;
    }

    /**
     * Взять первые N записей (alias для limit()).
     *
     * @param int $count
     *
     * @return static
     */
    public function take(int $count): static
    {
        return $this->limit($count);
    }

    /**
     * Пропустить N записей (alias для offset()).
     *
     * Обычно используется вместе с take():
     *   ->skip(20)->take(10)
     *
     * @param int $count
     *
     * @return static
     */
    public function skip(int $count): static
    {
        return $this->offset($count);
    }

    /**
     * Постраничная навигация (Bitrix CDBResult style).
     *
     * Использует $_REQUEST для определения страницы,
     * если параметр $page не передан явно.
     *
     * Пример:
     *   ->paginate(20)
     *   ->paginate(20, 'PAGEN_2')
     *   ->paginate(20, 'PAGEN_1', 3)
     *
     * ⚠ Сбрасывает limit/offset
     *
     * @param int      $pageSize   Количество элементов на страницу
     * @param string   $pageParam  Имя GET/REQUEST параметра страницы
     * @param int|null $page       Явный номер страницы (override $_REQUEST)
     *
     * @return static
     */
    public function paginate(int $pageSize, string $pageParam = 'PAGEN_1', ?int $page = null): static
    {
        $this->navParams  = [
            'nPageSize'    => $pageSize,
            'nCurrentPage' => $page ?? (int)($_REQUEST[$pageParam] ?? 1),
        ];
        $this->limitValue  = false; // сбрасываем limit если был
        $this->offsetValue = 0;
        return $this;
    }

    /**
     * Явная постраничная навигация без $_REQUEST.
     *
     * Пример:
     *   ->forPage(3, 20)
     *
     * @param int $page      Номер страницы (>= 1)
     * @param int $pageSize  Размер страницы
     *
     * @return static
     */
    public function forPage(int $page, int $pageSize): static
    {
        $this->navParams  = [
            'nPageSize'    => $pageSize,
            'iNumPage' => max(1, $page),
        ];
        $this->limitValue  = false;
        $this->offsetValue = 0;
        return $this;
    }

    /**
     * Передать "сырые" параметры навигации Bitrix.
     *
     * ⚠ Escape-хук: bypass всей логики pagination builder-а.
     *
     * Используется для нестандартных кейсов:
     * - nElementID
     * - кастомные навигационные параметры Bitrix
     *
     * @param array<string, mixed> $params
     *        Полный массив параметров CDBResult navigation
     *
     * @return static
     */
    public function navRaw(array $params): static
    {
        $this->navParams  = $params;
        $this->limitValue  = false;
        $this->offsetValue = 0;
        return $this;
    }

    // ──────────────────────────────────────────────
    // Внутренний хелпер — собрать $arNavStartParams
    // ──────────────────────────────────────────────

    /**
     * Собрать параметры навигации для CIBlockElement::GetList.
     *
     * Приоритет:
     *   1. paginate()/forPage() (navParams)
     *   2. limit()/offset() (nTopCount + nOffset)
     *   3. false (без ограничений)
     *
     * @internal используется в BaseQuery::executeGetList()
     *
     * @return array<string, mixed>|false
     */
    public function buildNavParams(): array|false
    {
        if ($this->navParams !== false) {
            return $this->navParams;
        }

        if ($this->limitValue !== false) {
            $params = ['nTopCount' => $this->limitValue];
            if ($this->offsetValue > 0) {
                $params['nOffset'] = $this->offsetValue;
            }
            return $params;
        }

        return false;
    }
}