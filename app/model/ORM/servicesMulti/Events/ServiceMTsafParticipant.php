<?php

namespace ORM\ServicesMulti\Events;

use AbstractServiceMulti;
use FKSDB\ORM\Services\Events\ServiceTsafParticipant;
use ORM\IModel;
use ServiceEventParticipant;

/**
 * @author Michal Koutný <xm.koutny@gmail.com>
 */
class ServiceMTsafParticipant extends AbstractServiceMulti {

    protected $modelClassName = 'ORM\ModelsMulti\Events\ModelMTsafParticipant';
    protected $joiningColumn = 'event_participant_id';

    /**
     * ServiceMTsafParticipant constructor.
     * @param ServiceEventParticipant $mainService
     * @param ServiceTsafParticipant $joinedService
     */
    public function __construct(ServiceEventParticipant $mainService, \FKSDB\ORM\Services\Events\ServiceTsafParticipant $joinedService) {
        parent::__construct($mainService, $joinedService);
    }

    /**
     * @param IModel $model
     */
    public function dispose(IModel $model) {
        parent::dispose($model);
        $this->getMainService()->dispose($model->getMainModel());
    }

}

