<?php

require __DIR__ . '/../bootstrap.php';

use Query\Tests\QueryTesterIblock;

$tester = new QueryTesterIblock(5);

$tester->assert(
    'orderBy overwrites previous order',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->orderBy('ID', 'DESC')
        ->orderBy('SORT', 'ASC')
        ->get(),

    fn($ids) => bxGet(
        ['SORT' => 'ASC'],
        ['IBLOCK_ID' => 5],
        ['ID']
    )
);

$tester->assert(
    'orderBy array normalizes keys to uppercase',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->orderBy([
            'id' => 'desc',
            'sort' => 'asc'
        ])
        ->get(),

    fn($ids) => bxGet(
        [
            'ID' => 'DESC',
            'SORT' => 'ASC'
        ],
        ['IBLOCK_ID' => 5],
        ['ID']
    )
);

$tester->assert(
    'addOrderBy merges multiple fields',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->orderBy('ID', 'ASC')
        ->addOrderBy('SORT', 'DESC')
        ->get(),

    fn($ids) => bxGet(
        [
            'ID' => 'ASC',
            'SORT' => 'DESC'
        ],
        ['IBLOCK_ID' => 5],
        ['ID']
    )
);

$tester->assert(
    'addOrderBy overrides same field',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->addOrderBy('SORT', 'ASC')
        ->addOrderBy('SORT', 'DESC')
        ->get(),

    fn($ids) => bxGet(
        [
            'SORT' => 'DESC'
        ],
        ['IBLOCK_ID' => 5],
        ['ID']
    )
);

$tester->assert(
    'orderByDesc works correctly',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->orderByDesc('SORT')
        ->get(),

    fn($ids) => bxGet(
        ['SORT' => 'DESC'],
        ['IBLOCK_ID' => 5],
        ['ID']
    )
);

$tester->assert(
    'orderByProperty maps to PROPERTY_ prefix',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->orderByProperty('price', 'DESC')
        ->get(),

    fn($ids) => bxGet(
        ['PROPERTY_PRICE' => 'DESC'],
        ['IBLOCK_ID' => 5],
        ['ID']
    )
);

$tester->assert(
    'addOrderByProperty merges property sorting',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->orderByProperty('price', 'ASC')
        ->addOrderByProperty('rating', 'DESC')
        ->get(),

    fn($ids) => bxGet(
        [
            'PROPERTY_PRICE' => 'ASC',
            'PROPERTY_RATING' => 'DESC'
        ],
        ['IBLOCK_ID' => 5],
        ['ID']
    )
);

//$tester->assert(
//    'inRandomOrder returns same items in different order',
//    fn($ids) => (function () {
//        $result = \Query\ElementQuery::query()
//            ->iblock(5)
//            ->select(['ID'])
//            ->inRandomOrder()
//            ->get();
//
//        return array_column($result, 'ID');
//    })(),
//
//    fn($ids) => (function () {
//        $result = bxGet(
//            [],
//            ['IBLOCK_ID' => 5],
//            ['ID']
//        );
//
//        return array_column($result, 'ID');
//    })()
//);

$tester->assert(
    'orderByRaw passes order as-is',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->orderByRaw([
            'SORT' => 'DESC',
            'ID' => 'ASC'
        ])
        ->get(),

    fn($ids) => bxGet(
        [
            'SORT' => 'DESC',
            'ID' => 'ASC'
        ],
        ['IBLOCK_ID' => 5],
        ['ID']
    )
);

$tester->assert(
    'orderBy resets previous state completely',
    fn($ids) => \Query\ElementQuery::query()
        ->iblock(5)
        ->select(['ID'])
        ->orderBy('SORT', 'ASC')
        ->orderBy('ID', 'DESC')
        ->get(),

    fn($ids) => bxGet(
        ['ID' => 'DESC'],
        ['IBLOCK_ID' => 5],
        ['ID']
    )
);