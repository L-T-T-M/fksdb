<?php

namespace Tasks\Legacy;

use Pipeline\Pipeline;
use Pipeline\Stage;
use ServicePerson;
use ServiceTaskContribution;
use SimpleXMLElement;
use Tasks\SeriesData;

/**
 * @note Assumes TasksFromXML has been run previously.
 * 
 * @author Michal Koutný <michal@fykos.cz>
 */
class ContributionsFromXML2 extends Stage {

    /**
     * @var SeriesData
     */
    private $data;

    /**
     * @var array   contribution type => xml element 
     */
    private static $contributionFromXML = [
        'author' => 'authors/author',
        'solution' => 'solution-authors/solution-author',
    ];

    /**
     * @var ServiceTaskContribution
     */
    private $taskContributionService;

    /**
     * @var ServicePerson
     */
    private $servicePerson;

    public function __construct(ServiceTaskContribution $taskContributionService, ServicePerson $servicePerson) {
        $this->taskContributionService = $taskContributionService;
        $this->servicePerson = $servicePerson;
    }

    public function setInput($data) {
        $this->data = $data;
    }

    public function process() {
        $xml = $this->data->getData();
        foreach ($xml->problems[0]->problem as $task) {
            $this->processTask($task);
        }
    }

    public function getOutput() {
        return $this->data;
    }

    private function processTask(SimpleXMLElement $XMLTask) {
        $tasks = $this->data->getTasks();
        $tasknr = (int) (string) $XMLTask->number;

        $task = $tasks[$tasknr];
        $this->taskContributionService->getConnection()->beginTransaction();

        foreach (self::$contributionFromXML as $type => $XMLElement) {
            list($parent, $child) = explode('/', $XMLElement);
            $parentEl = $XMLTask->{$parent}[0];
            // parse contributors            
            $contributors = array();
            if (!$parentEl || !isset($parentEl->{$child})) {
                continue;
            }
            foreach ($parentEl->{$child} as $element) {
                $signature = (string) $element;
                $signature = trim($signature);
                if (!$signature) {
                    continue;
                }


                $person = $this->servicePerson->findByTeXSignature($signature);
                if (!$person) {
                    $this->log(sprintf(_("Neznámý TeX identifikátor '%s'."), $signature));
                    continue;
                }

                $org = $person->getOrgs($this->data->getContest()->contest_id)->fetch();

                if (!$org) {
                    $this->log(sprintf(_("Osoba '%s' není org."), (string) $person), Pipeline::LOG_WARNING);
                }
                $contributors[] = $person;
            }

            // delete old contributions
            foreach ($task->getContributions($type) as $contribution) {
                $this->taskContributionService->dispose($contribution);
            }

            // store new contributions
            foreach ($contributors as $contributor) {
                $contribution = $this->taskContributionService->createNew(array(
                    'person_id' => $contributor->person_id,
                    'task_id' => $task->task_id,
                    'type' => $type,
                ));

                $this->taskContributionService->save($contribution);
            }
        }

        $this->taskContributionService->getConnection()->commit();
    }

}
