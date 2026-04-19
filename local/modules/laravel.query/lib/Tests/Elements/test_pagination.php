<?php

require __DIR__ . '/../bootstrap.php';

use Query\Tests\QueryTesterIblock;

$tester = new QueryTesterIblock(5);

$tester->assert(
    'limit returns correct count',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->limit(2)
        ->get(),

    fn($ids) => array_slice(
        bxGet([], ['IBLOCK_ID' => 5], ['ID']),
        0,
        2
    )
);

try {
    \Query\ElementQuery::query()
        ->limit(0);
    echo "❌ limit validation failed\n";
} catch (\InvalidArgumentException $e) {
    echo "✅ limit validation works\n";
}

try {
    \Query\ElementQuery::query()
        ->offset(10);

    echo "❌ offset without limit did not throw\n";
} catch (\LogicException $e) {
    echo "✅ offset requires limit enforced\n";
}

$tester->assert(
    'limit + offset works correctly',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->orderBy('ID', 'ASC')
        ->limit(2)
        ->offset(2)
        ->get(),

    fn($ids) => array_slice(
        bxGet(['ID' => 'ASC'], ['IBLOCK_ID' => 5], ['ID']),
        2,
        2
    )
);

$tester->assert(
    'take is alias of limit',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->take(3)
        ->get(),

    fn($ids) => array_slice(
        bxGet([], ['IBLOCK_ID' => 5], ['ID']),
        0,
        3
    )
);

$tester->assert(
    'skip is alias of offset with limit',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->limit(10)
        ->skip(3)
        ->get(),

    fn($ids) => array_slice(
        bxGet([], ['IBLOCK_ID' => 5], ['ID']),
        3,
        10
    )
);

$tester->assert(
    'paginate returns correct page',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->orderBy('ID', 'ASC')
        ->paginate(2, 'PAGEN_1', 2)
        ->get(),

    fn($ids) => array_slice(
        bxGet(['ID' => 'ASC'], ['IBLOCK_ID' => 5], ['ID']),
        2,
        2
    )
);

$tester->assert(
    'paginate overrides limit',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->limit(5)
        ->paginate(2, 'PAGEN_1', 1)
        ->get(),

    fn($ids) => array_slice(
        bxGet([], ['IBLOCK_ID' => 5], ['ID']),
        0,
        2
    )
);

$tester->assert(
    'paginate resets offset',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->limit(10)
        ->offset(3)
        ->paginate(2, 'PAGEN_1', 2)
        ->get(),

    fn($ids) => array_slice(
        bxGet([], ['IBLOCK_ID' => 5], ['ID']),
        2,
        2
    )
);

$tester->assert(
    'forPage works correctly',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->orderBy('ID', 'ASC')
        ->forPage(2, 2)
        ->get(),

    fn($ids) => array_slice(
        bxGet(['ID' => 'ASC'], ['IBLOCK_ID' => 5], ['ID']),
        2,
        2
    )
);

$tester->assert(
    'navRaw bypasses pagination logic',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->navRaw([
            'nTopCount' => 3
        ])
        ->get(),

    fn($ids) => array_slice(
        bxGet([], ['IBLOCK_ID' => 5], ['ID']),
        0,
        3
    )
);

$tester->assert(
    'paginate > limit > offset priority',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->limit(10)
        ->offset(2)
        ->paginate(2, 'PAGEN_1', 1)
        ->get(),

    fn($ids) => array_slice(
        bxGet([], ['IBLOCK_ID' => 5], ['ID']),
        0,
        2
    )
);