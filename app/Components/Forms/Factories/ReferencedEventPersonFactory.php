<?php

namespace FKSDB\Components\Forms\Factories;

use FKSDB\Components\Forms\Controls\Autocomplete\PersonProvider;
use ModelPerson;
use Nette\Forms\Controls\HiddenField;
use Persons\IModifialibityResolver;
use Persons\IVisibilityResolver;
use Persons\ReferencedPersonHandlerFactory;
use ServiceFlag;
use ServicePerson;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
class ReferencedEventPersonFactory extends AbstractReferencedPersonFactory {

    /**
     * @var PersonAccommodationFactory
     */
    private $personAccommodationFactory;
    /**
     * @var integer
     */
    private $eventId;

    function __construct(
        PersonAccommodationFactory $personAccommodationFactory,
        AddressFactory $addressFactory,
        FlagFactory $flagFactory,
        ServicePerson $servicePerson,
        PersonFactory $personFactory,
        ReferencedPersonHandlerFactory $referencedPersonHandlerFactory,
        PersonProvider $personProvider,
        ServiceFlag $serviceFlag,
        PersonInfoFactory $personInfoFactory,
        PersonHistoryFactory $personHistoryFactory
    ) {
        parent::__construct($addressFactory,
            $flagFactory,
            $servicePerson,
            $personFactory,
            $referencedPersonHandlerFactory,
            $personProvider,
            $serviceFlag,
            $personInfoFactory,
            $personHistoryFactory);
        $this->personAccommodationFactory = $personAccommodationFactory;
    }

    public function setEventId($eventId) {
        $this->eventId = $eventId;
    }

    public function createReferencedPerson($fieldsDefinition, $acYear, $searchType, $allowClear, IModifialibityResolver $modifiabilityResolver, IVisibilityResolver $visibilityResolver, $e = 0) {
        return parent::createReferencedPerson($fieldsDefinition, $acYear, $searchType, $allowClear, $modifiabilityResolver, $visibilityResolver, $this->eventId); // TODO: Change the autogenerated stub
    }


    public function createField($sub, $fieldName, $acYear, HiddenField $hiddenField = null, $metadata = []) {
        if ($sub === 'person_accommodation') {
            $control = $this->personAccommodationFactory->createMatrixSelect($this->eventId);
            $this->appendMetadata($control, $hiddenField, $fieldName, $metadata);
            return $control;
        }
        return parent::createField($sub, $fieldName, $acYear, $hiddenField);
    }

    protected function getPersonValue(ModelPerson $person = null, $sub, $field, $acYear, $options, $metaData) {
        if (!$person) {
            return null;
        }
        if ($sub === 'person_accommodation') {
            return $person->getAccommodationByEventId($this->eventId);
        }
        return parent::getPersonValue($person, $sub, $field, $acYear, $options, $metaData);

    }

}

