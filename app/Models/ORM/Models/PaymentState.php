<?php

declare(strict_types=1);

namespace FKSDB\Models\ORM\Models;

use FKSDB\Models\ORM\Columns\Types\EnumColumn;
use FKSDB\Models\Utils\FakeStringEnum;
use Nette\Utils\Html;

class PaymentState extends FakeStringEnum implements EnumColumn
{
    public const WAITING = 'waiting'; // waiting for confirm payment
    public const RECEIVED = 'received'; // payment received
    public const CANCELED = 'canceled'; // payment canceled
    public const IN_PROGRESS = 'in_progress';
    public const INIT = 'init'; // virtual state for correct ORM

    public function badge(): Html
    {
        return Html::el('span')->addAttributes(['class' => 'badge bg-' . $this->getBehaviorType()])->addText(
            $this->label()
        );
    }

    public function label(): string
    {
        switch ($this->value) {
            case self::IN_PROGRESS:
                return _('New payment');
            case self::WAITING:
                return _('Waiting for paying');
            case self::RECEIVED:
                return _('Payment received');
            default:
            case self::CANCELED:
                return _('Payment canceled');
        }
    }

    public function getBehaviorType(): string
    {
        switch ($this->value) {
            case self::IN_PROGRESS:
                return 'primary';
            case self::WAITING:
                return 'warning';
            case self::RECEIVED:
                return 'success';
            default:
            case self::CANCELED:
                return 'secondary';
        }
    }

    public static function cases(): array
    {
        return [
            new self(self::WAITING),
            new self(self::RECEIVED),
            new self(self::CANCELED),
            new self(self::NEW),
            new self(self::INIT),
        ];
    }
}
