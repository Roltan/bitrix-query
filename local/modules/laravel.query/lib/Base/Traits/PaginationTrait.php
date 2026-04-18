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
     * Ограничить количество возвращаемых записей.
     *
     * ->limit(20)
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
     * Смещение от начала выборки.
     * Работает только в паре с limit().
     *
     * ->limit(20)->offset(40)  // третья страница по 20 элементов
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
     * Взять первые N записей — синтаксический сахар для limit().
     *
     * ->take(5)
     */
    public function take(int $count): static
    {
        return $this->limit($count);
    }

    /**
     * Пропустить N записей — синтаксический сахар для offset().
     *
     * ->skip(20)->take(10)
     */
    public function skip(int $count): static
    {
        return $this->offset($count);
    }

    /**
     * Постраничная навигация в стиле Битрикса (CDBResult::NavQuery).
     * Номер страницы читается из $_REQUEST[$pageParam].
     *
     * ->paginate(20)
     * ->paginate(20, pageParam: 'PAGEN_2')  // если на странице несколько списков
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
     * Навигация по конкретной странице без зависимости от $_REQUEST.
     *
     * ->forPage(3, 20)  // третья страница по 20 элементов
     */
    public function forPage(int $page, int $pageSize): static
    {
        $this->navParams  = [
            'nPageSize'    => $pageSize,
            'nCurrentPage' => max(1, $page),
        ];
        $this->limitValue  = false;
        $this->offsetValue = 0;
        return $this;
    }

    /**
     * Передать параметры навигации GetList напрямую (raw).
     * Escape-хатч для нестандартных случаев (nElementID и т.д.).
     * ⚠ Пользователь сам отвечает за корректность структуры
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
     * Собрать параметры навигации для передачи в GetList.
     *
     * @internal используется в BaseQuery::executeGetList()
     */
    protected function buildNavParams(): array|false
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