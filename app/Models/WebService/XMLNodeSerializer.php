<?php

declare(strict_types=1);

namespace FKSDB\Models\WebService;

/**
 * @template T
 */
interface XMLNodeSerializer
{
    public const EXPORT_FORMAT_1 = 1;
    public const EXPORT_FORMAT_2 = 2;

    /**
     * @param T $dataSource
     */
    public function fillNode($dataSource, \DOMNode $node, \DOMDocument $doc, int $formatVersion): void;
}
