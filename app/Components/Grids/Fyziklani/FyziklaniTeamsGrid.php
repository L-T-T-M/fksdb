<?php

namespace FKSDB\Components\Grids\Fyziklani;

use Nette\Diagnostics\Debugger;
use \NiftyGrid\DataSource\NDataSource;
use ORM\Models\Events\ModelFyziklaniTeam;
use ORM\Services\Events\ServiceFyziklaniTeam;
use \FKSDB\Components\Grids\BaseGrid;

/**
 *
 * @author Michal Červeňák
 * @author Lukáš Timko
 */
class FyziklaniTeamsGrid extends BaseGrid {
    /**
     * @var ServiceFyziklaniTeam
     */
    private $serviceFyziklaniTeam;
    /**
     * @var integer
     */
    private $eventId;

    /**
     * FyziklaniTeamsGrid constructor.
     * @param integer $eventId
     * @param ServiceFyziklaniTeam $serviceFyziklaniTeam
     */
    public function __construct($eventId, ServiceFyziklaniTeam $serviceFyziklaniTeam) {
        $this->serviceFyziklaniTeam = $serviceFyziklaniTeam;
        $this->eventId = $eventId;
        parent::__construct();
    }

//    public function isSearchable() {
//        return $this->searchable;
//    }
//
//    public function setSearchable($searchable) {
//        $this->searchable = $searchable;
//    }

    protected function configure($presenter) {
        parent::configure($presenter);
        $this->paginate = false;
        $this->addColumn('name', _('Název týmu'));
        $this->addColumn('e_fyziklani_team_id', _('ID týmu'));
        $this->addColumn('points', _('Počet bodů'));

        $this->addColumn('room', _('Místnost'))->setRenderer(function ($row) {
            /**
             * @var $row ModelFyziklaniTeam
             */
            $position = $row->getPosition();
            if (!$position) {
                return '-';
            }
            $room = $position->getRoom();
            return $room->name;
        });
        $this->addColumn('category', _('Kategorie'));
        $that = $this;
        $this->addButton('edit', null)->setClass('btn btn-xs btn-success')->setLink(function ($row) use ($presenter) {
            return $presenter->link(':Fyziklani:Close:team', [
                'id' => $row->e_fyziklani_team_id,
                'eventID' => $this->eventId
            ]);
        })->setText(_('Uzavřít bodování'))->setShow(function ($row) use ($that) {
            /**
             * @var $row ModelFyziklaniTeam
             */
            return $row->hasOpenSubmit();
        });
        $teams = $this->serviceFyziklaniTeam->findParticipating($this->eventId);//->where('points',NULL);
        $this->setDataSource(new NDataSource($teams));
    }
}
