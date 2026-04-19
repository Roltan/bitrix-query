<?php

namespace Query\Tests;

class QueryTesterIblock
{
    private int $iblockId;
    private string $prefix;

    public function __construct(int $iblockId, string $prefix = 'QB_TEST_')
    {
        $this->iblockId = $iblockId;
        $this->prefix   = $prefix . uniqid('_');
    }

    /**
     * Создать тестовые элементы
     *
     * @param int $count
     * @return int[] ID элементов
     */
    public function createElements(int $count = 5): array
    {
        $el = new \CIBlockElement();
        $ids = [];

        for ($i = 1; $i <= $count; $i++) {
            $id = $el->Add([
                'IBLOCK_ID' => $this->iblockId,
                'NAME'      => $this->prefix . "_$i",
                'SORT'      => rand(100, 500),
                'ACTIVE'    => 'Y',
            ]);

            if (!$id) {
                throw new \RuntimeException($el->LAST_ERROR);
            }

            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * Выполнить сравнение QueryBuilder vs Bitrix
     *
     * @param callable $qbCallback fn($query, $ids)
     * @param callable $bxCallback fn($ids)
     */
    public function assert(string $name, callable $qbCallback, callable $bxCallback): void
    {
        $ids = $this->createElements();

        try {
            $qbResult = $qbCallback($ids);
            $bxResult = $bxCallback($ids);

            $qbResult = $this->normalizeAny($qbResult);
            $bxResult = $this->normalizeAny($bxResult);

            if ($qbResult === $bxResult) {
                echo "✅ {$name}\n";
            } else {
                echo "❌ {$name}\n";
                var_dump($qbResult);
                var_dump($bxResult);
            }

        } finally {
            $this->cleanup($ids);
        }
    }
    private function normalizeAny($value)
    {
        // null / bool / int / string — как есть
        if (!is_array($value)) {
            return is_numeric($value) ? (int)$value : $value;
        }

        // массив
        return $this->normalizeArray($value);
    }

    private function normalizeArray(array $rows): array
    {
        return array_map(function ($row) {

            // если это НЕ массив (например pluck → [1,2,3])
            if (!is_array($row)) {
                return is_numeric($row) ? (int)$row : $row;
            }

            // обычная строка результата Bitrix
            return array_map(function ($value) {
                return is_numeric($value) ? (int)$value : $value;
            }, $row);

        }, $rows);
    }

    private function cleanup(array $ids): void
    {
        foreach ($ids as $id) {
            \CIBlockElement::Delete($id);
        }
    }
}