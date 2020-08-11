<?php

namespace FKSDB\Tests\PresentersTests\PublicModule;

$container = require '../../bootstrap.php';

use FKSDB\Authentication\LoginUserStorage;
use FKSDB\Tests\ModelTests\DatabaseTestCase;
use Nette\Application\IPresenter;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\TextResponse;
use Nette\DI\Container;
use Nette\Application\UI\ITemplate;
use FKSDB\Modules\PublicModule\RegisterPresenter;
use Tester\Assert;

class RegisterPresenterTest extends DatabaseTestCase {

    private IPresenter $fixture;

    /**
     * RegisterPresenterTest constructor.
     * @param Container $container
     */
    public function __construct(Container $container) {
        parent::__construct($container);
    }

    protected function setUp() {
        parent::setUp();

        $presenterFactory = $this->getContainer()->getByType(IPresenterFactory::class);
        $this->fixture = $presenterFactory->createPresenter('Public:Register');
        $this->fixture->autoCanonicalize = false;

        $this->getContainer()->getByType(LoginUserStorage::class)->setPresenter($this->fixture);
    }

    public function testDispatch() {
        $request = new Request('Public:Register', 'GET', [
            'action' => 'contest',
            'lang' => 'en',
        ]);

        $response = $this->fixture->run($request);
        Assert::type(TextResponse::class, $response);

        $source = $response->getSource();
        Assert::type(ITemplate::class, $source);

        $html = (string)$source;
        Assert::contains('Select contest', $html);
    }

    public function testForm() {
        $request = new Request('Public:Register', 'GET', [
            'action' => 'contestant',
            'contestId' => 1,
            'year' => 1,
            'lang' => 'en',
        ]);

        $response = $this->fixture->run($request);
        Assert::type(TextResponse::class, $response);
        /** @var ITemplate $source */
        $source = $response->getSource();
        Assert::type(ITemplate::class, $source);

        $html = $source->__toString();
        Assert::contains('contestant application', $html);
    }
}

$testCase = new RegisterPresenterTest($container);
$testCase->run();
