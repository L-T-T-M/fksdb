<?php

declare(strict_types=1);

namespace FKSDB\Models\Events\FormAdjustments;

use FKSDB\Components\Forms\Factories\SingleReflectionFormFactory;
use FKSDB\Models\Events\Machine\Machine;
use FKSDB\Models\Events\Model\Holder\Holder;
use FKSDB\Models\Events\Processing\Processing;
use FKSDB\Models\Exceptions\BadTypeException;
use FKSDB\Models\Transitions\Machine\AbstractMachine;
use Fykosak\Utils\Logging\Logger;
use FKSDB\Models\ORM\OmittedControlException;
use FKSDB\Models\ORM\Services\PersonInfoService;
use FKSDB\Models\Utils\FormUtils;
use Nette\Forms\Form;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;

/**
 * Creates required checkbox for whole application and then
 * sets agreed bit in all person_info containers found (even for editing).
 */
class PrivacyPolicy implements Processing, FormAdjustment
{
    use SmartObject;

    protected const CONTROL_NAME = 'privacy';
    private PersonInfoService $personInfoService;
    private SingleReflectionFormFactory $singleReflectionFormFactory;

    public function __construct(
        PersonInfoService $personInfoService,
        SingleReflectionFormFactory $singleReflectionFormFactory
    ) {
        $this->personInfoService = $personInfoService;
        $this->singleReflectionFormFactory = $singleReflectionFormFactory;
    }

    /**
     * @throws BadTypeException
     * @throws OmittedControlException
     */
    public function adjust(Form $form, Holder $holder): void
    {
        if ($holder->primaryHolder->getModelState() != AbstractMachine::STATE_INIT) {
            return;
        }

        $control = $this->singleReflectionFormFactory->createField('person_info', 'agreed');
        $control->addRule(Form::FILLED, _('You have to agree with the privacy policy before submitting.'));

        $firstSubmit = FormUtils::findFirstSubmit($form);
        $form->addComponent($control, self::CONTROL_NAME, $firstSubmit->getName());
    }

    public function process(
        array $states,
        ArrayHash $values,
        Machine $machine,
        Holder $holder,
        Logger $logger,
        ?Form $form = null
    ): ?array {
        $this->trySetAgreed($values);
        return null;
    }

    private function trySetAgreed(ArrayHash $values): void
    {
        foreach ($values as $key => $value) {
            if ($value instanceof ArrayHash) {
                $this->trySetAgreed($value);
            } elseif (isset($values[$key . '_container']) && isset($values[$key . '_container']['person_info'])) {
                $personId = $value;
                $personInfo = $this->personInfoService->findByPrimary($personId);
                if ($personInfo) {
                    $this->personInfoService->storeModel(['agreed' => 1], $personInfo);

                    $values[$key . '_container']['person_info']['agreed'] = 1;
                }
            }
        }
    }
}
