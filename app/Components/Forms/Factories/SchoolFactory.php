<?php

namespace FKSDB\Components\Forms\Factories;

use FKSDB\Components\Forms\Containers\ModelContainer;
use FKSDB\Components\Forms\Controls\Autocomplete\AutocompleteSelectBox;
use FKSDB\Components\Forms\Controls\Autocomplete\SchoolProvider;
use Nette\Forms\ControlGroup;
use Nette\Forms\Form;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 * 
 * @author Michal Koutný <michal@fykos.cz>
 */
class SchoolFactory {

    /**
     * @var SchoolProvider
     */
    private $schoolProvider;

    function __construct(SchoolProvider $schoolProvider) {
        $this->schoolProvider = $schoolProvider;
    }

    /**
     * @param type $options
     */
    public function createSchool($options = 0, ControlGroup $group = null) {
        $container = new ModelContainer();
        $container->setCurrentGroup($group);

        $container->addText('name_full', 'Plný název')
                ->setOption('description', 'Úplný nezkrácený název školy.');

        $container->addText('name', 'Název')
                ->addRule(Form::FILLED, 'Název je povinný.')
                ->setOption('description', 'Název na obálku.');

        $container->addText('name_abbrev', 'Zkrácený název')
                ->addRule(Form::FILLED, 'Zkrácený název je povinný.')
                ->setOption('description', 'Název krátký do výsledkovky.');

        $container->addText('email', 'Kontaktní e-mail')
                ->addCondition(Form::FILLED)
                ->addRule(Form::EMAIL);

        $container->addText('ic', 'IČ')
                ->addRule(Form::MAX_LENGTH, 'Délka IČ je omezena na 8 znaků.', 8);

        $container->addText('izo', 'IZO')
                ->addRule(Form::MAX_LENGTH, 'Délka IZO je omezena na 32 znaků.', 32);

        $container->addCheckbox('active', 'Aktivní záznam')
                ->setDefaultValue(true);

        $container->addText('note', 'Poznámka');

        //$container->addHidden('school_id');

        return $container;
    }

    public function createSchoolSelect() {
        $schoolElement = new AutocompleteSelectBox(true, 'Škola');
        $schoolElement->setDataProvider($this->schoolProvider);
        return $schoolElement;
    }

}
