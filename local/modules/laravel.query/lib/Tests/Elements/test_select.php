<?php

require __DIR__ . '/../bootstrap.php';

use Query\Tests\QueryTesterIblock;

$tester = new QueryTesterIblock(5);

$tester->assert(
    'select replaces previous fields',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->select(['ID', 'NAME'])
        ->get(),

    fn($ids) => bxGet(
        [],
        ['IBLOCK_ID' => 5],
        ['ID', 'NAME']
    )
);

try {
    \Query\ElementQuery::query()
        ->select([]);
    echo "❌ empty select did not throw\n";
} catch (\InvalidArgumentException $e) {
    echo "✅ empty select throws\n";
}

$tester->assert(
    'addSelect merges fields without duplicates',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->addSelect('NAME', 'SORT')
        ->get(),

    fn($ids) => bxGet(
        [],
        ['IBLOCK_ID' => 5],
        ['ID', 'NAME', 'SORT']
    )
);

try {
    \Query\ElementQuery::query()
        ->addSelect();
    echo "❌ addSelect empty did not throw\n";
} catch (\InvalidArgumentException $e) {
    echo "✅ addSelect empty throws\n";
}

$tester->assert(
    'selectAll overrides previous select',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID', 'NAME'])
        ->selectAll()
        ->get(),

    fn($ids) => bxGet(
        [],
        ['IBLOCK_ID' => 5],
        ['*']
    )
);

$tester->assert(
    'withProperty adds PROPERTY_* fields',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->withProperty('price', 'color')
        ->get(),

    fn($ids) => bxGet(
        [],
        ['IBLOCK_ID' => 5],
        ['ID', 'PROPERTY_PRICE', 'PROPERTY_COLOR']
    )
);

$tester->assert(
    'withProperty does not duplicate fields',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID', 'PROPERTY_PRICE'])
        ->withProperty('price')
        ->get(),

    fn($ids) => bxGet(
        [],
        ['IBLOCK_ID' => 5],
        ['ID', 'PROPERTY_PRICE']
    )
);

$tester->assert(
    'withProperties replaces specific properties with PROPERTY_*',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID', 'PROPERTY_PRICE'])
        ->withProperties()
        ->get(),

    fn($ids) => bxGet(
        [],
        ['IBLOCK_ID' => 5],
        ['ID', 'PROPERTY_*']
    )
);

$tester->assert(
    'groupBy sets fields correctly',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->groupBy(['IBLOCK_ID'])
        ->get(),

    fn($ids) => bxGet(
        [],
        ['IBLOCK_ID' => 5],
        ['ID'],
        false,
        ['IBLOCK_ID']
    )
);

$tester->assert(
    'groupBy empty enables COUNT mode',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->groupBy([])
        ->count(),

    fn($ids) => count(
        bxGet([], ['IBLOCK_ID' => 5], ['ID'])
    )
);

$tester->assert(
    'selectAll overrides everything including withProperty',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->withProperty('price')
        ->selectAll()
        ->get(),

    fn($ids) => bxGet(
        [],
        ['IBLOCK_ID' => 5],
        ['*']
    )
);