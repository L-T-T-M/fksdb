<?php

declare(strict_types=1);

namespace FKSDB\Components\DataTest;

use FKSDB\Components\Grids\Components\Renderer\RendererItem;
use FKSDB\Models\Exceptions\NotImplementedException;
use Fykosak\NetteORM\Model;
use Fykosak\Utils\Logging\MemoryLogger;
use Fykosak\Utils\Logging\Message;
use Nette\Utils\Html;

/**
 * @template TModel of Model
 */
trait TestGridTrait
{
    protected DataTestFactory $dataTestFactory;

    final public function injectTest(DataTestFactory $dataTestFactory): void
    {
        $this->dataTestFactory = $dataTestFactory;
    }

    protected function addTests(string $section): void
    {
        /** @phpstan-var array<string,Test<TModel>> $tests */
        $tests = $this->dataTestFactory->getTests($section);
        foreach ($tests as $id => $test) {
            /** @phpstan-var RendererItem<TModel> $item */
            $item = new RendererItem(
                $this->container,
                function (Model $person) use ($test): Html {
                    $logger = new MemoryLogger();
                    /** @phpstan-var TModel $person */
                    $test->run($logger, $person);
                    return self::createHtmlLog($logger);
                },
                $test->getTitle()
            );
            $this->addColumn($item, 'test_' . $id);
        }
    }

    /**
     * @throws NotImplementedException
     */
    private static function createHtmlLog(MemoryLogger $logger): Html
    {
        $container = Html::el('span');
        $messages = $logger->getMessages();
        if (count($messages)) {
            foreach ($messages as $log) {
                $container->addHtml(self::createHtmlIcon($log));
            }
        } else {
            $container->addHtml(
                Html::el('span')
                    ->addAttributes([
                        'class' => 'text-success',
                        'title' => _('No errors found'),
                    ])
                    ->addHtml(
                        Html::el('i')->addAttributes([
                            'class' => 'fas fa-check',
                        ])
                    )
            );
        }
        return $container;
    }

    /**
     * @throws NotImplementedException
     */
    private static function mapLevelToIcon(Message $message): string
    {
        switch ($message->level) {
            case Message::LVL_ERROR:
                return 'fas fa-times';
            case Message::LVL_WARNING:
                return 'fas fa-warning';
            case Message::LVL_INFO:
                return 'fas fa-info';
            case Message::LVL_SUCCESS:
                return 'fas fa-check';
            default:
                throw new NotImplementedException(\sprintf('Level "%s" is not supported', $message->level));
        }
    }

    /**
     * @throws NotImplementedException
     */
    private static function createHtmlIcon(Message $message): Html
    {
        return Html::el('span')
            ->addAttributes([
                'class' => 'text-' . $message->level,
                'title' => $message->text,
            ])->addHtml(
                Html::el('i')
                    ->addAttributes([
                        'class' => self::mapLevelToIcon($message),
                    ])
            );
    }
}
