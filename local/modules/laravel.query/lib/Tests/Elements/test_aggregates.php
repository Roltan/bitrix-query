<?php

require __DIR__ . '/../bootstrap.php';

use Query\Tests\QueryTesterIblock;

$tester = new QueryTesterIblock(5);

$tester->assert(
    'first returns single element',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->orderBy('ID', 'ASC')
        ->first(),

    fn($ids) => (
    $rows = bxGet(
        ['ID' => 'ASC'],
        [],
        ['ID'],
        ['nTopCount' => 1]
    )
    ) ? ($rows[0] ?? null) : null
);

$tester->assert(
    'first does not affect subsequent get',
    fn($ids) => (function () {
        $q = \Query\ElementQuery::query()
            ->iblock(5)
            ->select(['ID'])
            ->orderBy('ID', 'ASC');

        $q->first(); // вызываем first()

        return $q->get(); // потом обычный get()
    })(),

    fn($ids) => bxGet(
        ['ID' => 'ASC'],
        ['IBLOCK_ID' => '5'],
        ['ID']
    )
);

$tester->assert(
    'count matches bx count',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->whereIn('ID', $ids)
        ->count(),

    fn($ids) => count(
        bxGet(
            [],
            ['ID' => $ids],
            ['ID']
        )
    )
);

$tester->assert(
    'exists true',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->whereIn('ID', $ids)
        ->exists(),

    fn($ids) => !empty(
    bxGet(
        [],
        ['ID' => $ids],
        ['ID']
    )
    )
);

$tester->assert(
    'doesntExist true for empty result',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->where('ID', -1) // гарантированно нет
        ->doesntExist(),

    fn($ids) => empty(
    bxGet(
        [],
        ['ID' => -1],
        ['ID']
    )
    )
);

$tester->assert(
    'pluck NAME',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID', 'NAME'])
        ->pluck('NAME'),

    fn($ids) => array_map(
        fn($row) => $row['NAME'] ?? null,
        bxGet([], ['IBLOCK_ID' => 5], ['ID', 'NAME'])
    )
);

$tester->assert(
    'pluck NAME keyed by ID',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID', 'NAME'])
        ->pluck('NAME', 'ID'),

    fn($ids) => (function () {
        $rows = bxGet([], ['IBLOCK_ID' => 5], ['ID', 'NAME']);
        $result = [];

        foreach ($rows as $row) {
            if (isset($row['ID'])) {
                $result[$row['ID']] = $row['NAME'] ?? null;
            }
        }

        return $result;
    })()
);

$tester->assert(
    'pluck adds missing select fields',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID']) // NAME нет в select
        ->pluck('NAME'),

    fn($ids) => array_map(
        fn($row) => $row['NAME'] ?? null,
        bxGet([], ['IBLOCK_ID' => 5], ['ID', 'NAME'])
    )
);

$tester->assert(
    'chunk processes all items',
    fn($ids) => (function () {
        $collected = [];

        \Query\ElementQuery::query()
            ->iblock(5)
            ->select(['ID'])
            ->orderBy('ID', 'ASC')
            ->chunk(2, function ($items) use (&$collected) {
                foreach ($items as $item) {
                    $collected[] = $item['ID'];
                }
            });

        return $collected;
    })(),

    fn($ids) => array_map(
        fn($row) => $row['ID'],
        bxGet(['ID' => 'ASC'], ['IBLOCK_ID' => 5], ['ID'])
    )
);

$tester->assert(
    'chunk stops on false',
    fn($ids) => (function () {
        $collected = [];

        \Query\ElementQuery::query()
            ->iblock(5)
            ->select(['ID'])
            ->orderBy('ID', 'ASC')
            ->chunk(2, function ($items, $page) use (&$collected) {
                foreach ($items as $item) {
                    $collected[] = $item['ID'];
                }

                return $page < 2; // остановка после 2 страницы
            });

        return $collected;
    })(),

    fn($ids) => array_slice(
        array_map(
            fn($row) => $row['ID'],
            bxGet(['ID' => 'ASC'], ['IBLOCK_ID' => 5], ['ID'])
        ),
        0,
        4 // 2 страницы по 2 элемента
    )
);
