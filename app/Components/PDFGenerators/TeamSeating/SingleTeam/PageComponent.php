<?php

declare(strict_types=1);

namespace FKSDB\Components\PDFGenerators\TeamSeating\SingleTeam;

use FKSDB\Components\PDFGenerators\TeamSeating\SeatingPageComponent;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\ORM\Models\Fyziklani\TeamModel2;

/**
 * @phpstan-extends SeatingPageComponent<TeamModel2,array<never>>
 */
class PageComponent extends SeatingPageComponent
{
    /**
     * @throws BadTypeException
     */
    final public function render($row, array $params = []): void
    {
        if (!$row instanceof TeamModel2) {
            throw new BadTypeException(TeamModel2::class, $row);
        }
        $place = $row->getPlace();

        $this->template->render(__DIR__ . DIRECTORY_SEPARATOR . 'layout.single.latte', [
            'rests' => $row->getScheduleRest(),
            'team' => $row,
            'room' => $place ? $place->sector() : null,
            'event' => $row->event,
            'sector' => $place ? $place->sector() : null,
            'showBigNav' => true,
        ]);
    }
}
