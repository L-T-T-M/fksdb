<?php

declare(strict_types=1);

namespace FKSDB\Components\Charts\Contestants;

use FKSDB\Components\Charts\Core\Chart;
use FKSDB\Models\ORM\Models\ContestModel;
use FKSDB\Models\ORM\Services\SubmitService;
use Fykosak\NetteFrontendComponent\Components\FrontEndComponent;
use Fykosak\Utils\UI\Title;
use Nette\DI\Container;

class PerYearsChart extends FrontEndComponent implements Chart
{

    private SubmitService $submitService;
    protected ContestModel $contest;

    public function __construct(Container $container, ContestModel $contest)
    {
        parent::__construct($container, 'chart.contestants.per-years');
        $this->contest = $contest;
    }

    public function injectSecondary(SubmitService $submitService): void
    {
        $this->submitService = $submitService;
    }
    /**
     * @phpstan-return array<int,array<int|'year',int>>
     */
    protected function getData(): array
    {
        $seriesQuery = $this->submitService->getTable()
            ->where('task.contest_id', $this->contest->contest_id)
            ->group('task.series, task.year')
            ->select('COUNT(DISTINCT contestant_id) AS count,task.series, task.year');

        $yearsQuery = $this->submitService->getTable()
            ->where('task.contest_id', $this->contest->contest_id)
            ->group('task.year')
            ->select('COUNT(DISTINCT contestant_id) AS count, task.year');

        $data = [];
        foreach ($seriesQuery as $row) {
            $year = (int)$row->year; // @phpstan-ignore-line
            $series = (int)$row->series; // @phpstan-ignore-line
            $data[$year] = $data[$year] ?? [];
            $data[$year][$series] = (int)$row->count; // @phpstan-ignore-line
        }
        foreach ($yearsQuery as $row) {
            $year = (int)$row->year; // @phpstan-ignore-line
            $data[$year] = $data[$year] ?? [];
            $data[$year]['year'] = (int)$row->count; // @phpstan-ignore-line
        }
        return $data;
    }

    public function getTitle(): Title
    {
        return new Title(null, _('Contestants per years'), 'fas fa-chart-line');
    }

    public function getDescription(): ?string
    {
        return null;
    }
}
