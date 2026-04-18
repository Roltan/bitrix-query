<?php

require __DIR__ . '/../bootstrap.php';

use Query\Tests\QueryTesterIblock;

$tester = new QueryTesterIblock(5);

// ──────────────────────────────────────────────
// TEST: filter by ID (IN)
// ──────────────────────────────────────────────

$tester->assert(
    'filter by ID IN',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->whereRaw(['ID' => $ids])
        ->select(['ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        ['ID' => $ids],
        ['ID']
    )
);

// ──────────────────────────────────────────────
// TEST: filter by ACTIVE = Y
// ──────────────────────────────────────────────

$tester->assert(
    'filter ACTIVE = Y',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->whereRaw(['ACTIVE' => 'Y'])
        ->select(['ID', 'ACTIVE'])
        ->get(),

    fn($ids) => bxGet(
        [],
        ['ACTIVE' => 'Y'],
        ['ID', 'ACTIVE']
    )
);

// ──────────────────────────────────────────────
// TEST: filter LIKE (%)
// ──────────────────────────────────────────────

$tester->assert(
    'filter NAME LIKE %TEST%',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->whereRaw(['NAME' => '%TEST%'])
        ->select(['ID', 'NAME'])
        ->get(),

    fn($ids) => bxGet(
        [],
        ['NAME' => '%TEST%'],
        ['ID', 'NAME']
    )
);

// ──────────────────────────────────────────────
// TEST: filter NOT EQUAL (!FIELD)
// ──────────────────────────────────────────────

$tester->assert(
    'filter !IBLOCK_SECTION_ID',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->whereRaw(['!IBLOCK_SECTION_ID' => false])
        ->select(['ID', 'IBLOCK_SECTION_ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        ['!IBLOCK_SECTION_ID' => false],
        ['ID', 'IBLOCK_SECTION_ID']
    )
);

// ──────────────────────────────────────────────
// TEST: multiple filters (AND)
// ──────────────────────────────────────────────

$tester->assert(
    'filter ACTIVE + IBLOCK_ID',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->whereRaw([
            'ACTIVE' => 'Y',
            'IBLOCK_ID' => 5
        ])
        ->select(['ID', 'ACTIVE', 'IBLOCK_ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        [
            'ACTIVE' => 'Y',
            'IBLOCK_ID' => 5
        ],
        ['ID', 'ACTIVE', 'IBLOCK_ID']
    )
);

// ──────────────────────────────────────────────
// TEST: empty filter (all items)
// ──────────────────────────────────────────────

$tester->assert(
    'empty filter (ALL)',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        [],
        ['ID']
    )
);

echo "\n=== FILTER TESTS DONE ===\n";