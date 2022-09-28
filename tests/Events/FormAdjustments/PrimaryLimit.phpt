<?php

declare(strict_types=1);

namespace FKSDB\Tests\Events\FormAdjustments;

// phpcs:disable
$container = require '../../Bootstrap.php';

// phpcs:enable
use FKSDB\Models\ORM\Services\EventParticipantService;
use Nette\Application\Request;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Template;
use Tester\Assert;
use Tester\DomQuery;

class PrimaryLimit extends ResourceAvailabilityTestCase
{

    public function testDisplay(): void
    {
        $request = new Request('Public:Application', 'GET', [
            'action' => 'default',
            'lang' => 'cs',
            'contestId' => (string)1,
            'year' => (string)1,
            'eventId' => (string)$this->event->event_id,
        ]);

        $response = $this->fixture->run($request);
        Assert::type(TextResponse::class, $response);
        /** @var TextResponse $response */
        $source = $response->getSource();
        Assert::type(Template::class, $source);

        $html = (string)$source;
        $dom = DomQuery::fromHtml($html);
        Assert::true((bool)$dom->xpath('//input[@name="participant[accomodation]"][@disabled="disabled"]'));
    }

    public function testRegistration(): void
    {
        $request = $this->createPostRequest([
            'participant' => [
                'person_id' => "__promise",
                'person_id_container' => [
                    '_c_compact' => " ",
                    'person' => [
                        'other_name' => "František",
                        'family_name' => "Dobrota",
                    ],
                    'person_info' => [
                        'email' => "ksaad@kalo.cz",
                        'id_number' => "1231354",
                        'born' => "2014-09-15",
                    ],
                    'post_contact_p' => [
                        'address' => [
                            'target' => "jkljhkjh",
                            'city' => "jkhlkjh",
                            'postal_code' => "64546",
                            'country_iso' => "",
                        ],
                    ],
                ],
                'accomodation' => "1",
                'e_dsef_group_id' => "1",
                'lunch_count' => "3",
                'message' => "",
            ],
            'privacy' => "on",
            'c_a_p_t_cha' => "pqrt",
            '__init__applied' => "Přihlásit účastníka",
        ]);

        $response = $this->fixture->run($request);
        Assert::type(RedirectResponse::class, $response);

        Assert::equal(
            2,
            (int)$this->getContainer()
                ->getByType(EventParticipantService::class)
                ->getTable()
                ->where(['event_id' => $this->event->event_id])
                ->sum('accomodation')
        );
    }

    protected function getCapacity(): int
    {
        return 2;
    }
}

// phpcs:disable
$testCase = new PrimaryLimit($container);
$testCase->run();
// phpcs:enable
