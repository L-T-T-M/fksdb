<?php

namespace Events\Processings;

use Events\Machine\Machine;
use Events\Model\Holder\Holder;
use Events\SubmitProcessingException;
use Nette\Application\UI\Form;
use Nette\ArrayHash;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 * 
 * @author Michal Koutný <michal@fykos.cz>
 */
interface IProcessing {

    /**
     * @param array $states
     * @param Form $form
     * @param ArrayHash $values
     * @param Machine $machine
     * @param Holder $holder
     * @return null|array[machineName] => new state
     * @throws SubmitProcessingException
     */
    public function process($states, Form $form, ArrayHash $values, Machine $machine, Holder $holder);
}

