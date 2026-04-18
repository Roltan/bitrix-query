<?php

require __DIR__ . '/../bootstrap.php';

use Query\Tests\QueryTesterIblock;

$tester = new QueryTesterIblock(5);

// ──────────────────────────────────────────────
// TEST: select *
// ──────────────────────────────────────────────

$tester->assert(
    'select all',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->selectAll()
        ->get(),

    fn($ids) => bxGet(
        [],
        [],
        ['*']
    )
);

// ──────────────────────────────────────────────
// TEST: select specific fields
// ──────────────────────────────────────────────

$tester->assert(
    'select ID + NAME',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID', 'NAME'])
        ->get(),

    fn($ids) => bxGet(
        [],
        [],
        ['ID', 'NAME']
    )
);

// ──────────────────────────────────────────────
// TEST: order ASC
// ──────────────────────────────────────────────

$tester->assert(
    'order by SORT ASC',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID', 'SORT'])
        ->orderBy('SORT')
        ->get(),

    fn($ids) => bxGet(
        ['SORT' => 'ASC'],
        [],
        ['ID', 'SORT']
    )
);

// ──────────────────────────────────────────────
// TEST: order DESC
// ──────────────────────────────────────────────

$tester->assert(
    'order by SORT DESC',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID', 'SORT'])
        ->orderBy('SORT', 'DESC')
        ->get(),

    fn($ids) => bxGet(
        ['SORT' => 'DESC'],
        [],
        ['ID', 'SORT']
    )
);

// ──────────────────────────────────────────────
// TEST: limit
// ──────────────────────────────────────────────

$tester->assert(
    'limit 2',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->limit(2)
        ->get(),

    fn($ids) => bxGet(
        [],
        [],
        ['ID'],
        ['nTopCount' => 2]
    )
);

echo "\n=== BASIC TESTS DONE ===\n";