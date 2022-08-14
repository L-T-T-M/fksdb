<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Columns\Tables\Event;

use FKSDB\Models\Exceptions\NotImplementedException;
use FKSDB\Models\ORM\Models\LoginModel;
use Fykosak\NetteORM\Exceptions\CannotAccessModelException;
use FKSDB\Models\ORM\Columns\ColumnFactory;
use FKSDB\Models\ORM\MetaDataFactory;
use FKSDB\Models\ORM\Models\PersonModel;
use FKSDB\Models\ORM\ReferencedAccessor;
use FKSDB\Models\ValuePrinters\EventRolePrinter;
use Fykosak\NetteORM\Model;
use FKSDB\Models\ORM\Models\EventModel;
use Nette\Security\User;
use Nette\Utils\Html;
use Tracy\Debugger;

class EventRole extends ColumnFactory
{
    private User $user;

    public function __construct(User $user, MetaDataFactory $metaDataFactory)
    {
        parent::__construct($metaDataFactory);
        $this->user = $user;
    }

    /**
     * @throws CannotAccessModelException
     * @throws NotImplementedException
     */
    protected function createHtmlValue(Model $model): Html
    {
        try {
            $person = ReferencedAccessor::accessModel($model, PersonModel::class);
        } catch (CannotAccessModelException$exception) {
            /** @var LoginModel $login */
            $login = $this->user->getIdentity();
            $person = $login->person;
        }
        /** @var EventModel $event */
        $event = ReferencedAccessor::accessModel($model, EventModel::class);
        return (new EventRolePrinter())($person, $event);
    }

    protected function resolveModel(Model $modelSingle): ?Model
    {
        return $modelSingle; // need to be original model because of referenced access
    }
}
