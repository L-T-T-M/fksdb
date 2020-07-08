<?php

namespace FKSDB\Components\Forms\Controls\Autocomplete;

use FKSDB\ORM\Models\ModelContest;
use FKSDB\ORM\Models\ModelPerson;
use FKSDB\ORM\Services\ServicePerson;
use FKSDB\ORM\Tables\TypedTableSelection;
use FKSDB\YearCalculator;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
class PersonProvider implements IFilteredDataProvider {

    const PLACE = 'place';

    /**
     * @var ServicePerson
     */
    private $servicePerson;

    /**
     * @var TypedTableSelection
     */
    private $searchTable;

    /**
     * PersonProvider constructor.
     * @param ServicePerson $servicePerson
     */
    public function __construct(ServicePerson $servicePerson) {
        $this->servicePerson = $servicePerson;
        $this->searchTable = $this->servicePerson->getTable();
    }

    /**
     * Syntactic sugar, should be solved more generally.
     * @param ModelContest $contest
     * @param YearCalculator $yearCalculator
     */
    public function filterOrgs(ModelContest $contest, YearCalculator $yearCalculator) {
        $this->searchTable = $this->servicePerson->getTable()
            ->where([
                ':org.contest_id' => $contest->contest_id,
            ])
            ->where(':org.since <= ?', $yearCalculator->getCurrentYear($contest))
            ->where(':org.until IS NULL OR :org.until <= ?', $yearCalculator->getCurrentYear($contest));

    }

    /**
     * Prefix search.
     *
     * @param string $search
     * @return array
     */
    public function getFilteredItems(string $search): array {
        $search = trim($search);
        $search = str_replace(' ', '', $search);
        $this->searchTable
            ->where('family_name LIKE concat(?, \'%\') OR other_name LIKE concat(?, \'%\') OR concat(other_name, family_name) LIKE concat(?,  \'%\')', $search, $search, $search);
        return $this->getItems();
    }

    public function getItemLabel(int $id): string {
        $person = $this->servicePerson->findByPrimary($id);
        return $person->getFullName();
    }

    public function getItems(): array {
        $persons = $this->searchTable
            ->order('family_name, other_name');


        $result = [];
        /** @var ModelPerson $person */
        foreach ($persons as $person) {
            $result[] = $this->getItem($person);
        }
        return $result;
    }

    /**
     * @param ModelPerson $person
     * @return array
     */
    private function getItem(ModelPerson $person) {
        $place = null;
        $address = $person->getDeliveryAddress();
        if ($address) {
            $place = $address->getAddress()->city;
        }
        return [
            self::LABEL => $person->getFullName(),
            self::VALUE => $person->person_id,
            self::PLACE => $place,
        ];
    }

    /**
     * @param mixed $id
     */
    public function setDefaultValue($id) {
        /* intentionally blank */
    }

}
