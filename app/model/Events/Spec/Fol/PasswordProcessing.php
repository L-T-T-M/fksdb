<?php

namespace FKSDB\Events\Spec\Fol;

use FKSDB\Events\Machine\BaseMachine;
use FKSDB\Events\Machine\Machine;
use FKSDB\Events\Model\Holder\Holder;
use FKSDB\Events\Processings\AbstractProcessing;
use FKSDB\Logging\ILogger;
use FKSDB\Messages\Message;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;

/**
 * Class PasswordProcessing
 * *
 */
class PasswordProcessing extends AbstractProcessing {

    /**
     * @param array $states
     * @param ArrayHash $values
     * @param Machine $machine
     * @param Holder $holder
     * @param ILogger $logger
     * @param Form|null $form
     * @return void
     */
    protected function _process($states, ArrayHash $values, Machine $machine, Holder $holder, ILogger $logger, Form $form = null) {
        if (!isset($values['team'])) {
            return;
        }

        $original = $holder->getPrimaryHolder()->getModelState() != BaseMachine::STATE_INIT ? $holder->getPrimaryHolder()->getModel()->password : null;

        if (!isset($values['team']['password']) || !$values['team']['password']) {
            $result = $values['team']['password'] = $original;
        } else {
            $result = $values['team']['password'] = $this->hash($values['team']['password']);
        }

        if ($original !== null && $original != $result) {
            $logger->log(new Message(_('Set new game password.'), ILogger::INFO));
        }
    }

    /**
     * @param string $string
     * @return string
     */
    private function hash($string): string {
        return sha1($string);
    }

}
