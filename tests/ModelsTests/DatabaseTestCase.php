<?php

namespace FKSDB\Tests\ModelsTests;

use FKSDB\Models\Authentication\PasswordAuthenticator;
use FKSDB\Models\ORM\DbNames;
use Nette\Database\Context;
use Nette\Database\IRow;
use Nette\DI\Container;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;

abstract class DatabaseTestCase extends TestCase {

    private Container $container;
    protected Context $context;
    private int $instanceNo;

    /**
     * DatabaseTestCase constructor.
     * @param Container $container
     */
    public function __construct(Container $container) {
        $this->container = $container;

        $this->context = $container->getByType(Context::class);
        $max = $container->parameters['tester']['dbInstances'];
        $this->instanceNo = (getmypid() % $max) + 1;
        $this->context->query('USE fksdb_test' . $this->instanceNo);
    }

    protected function getContainer(): Container {
        return $this->container;
    }

    protected function setUp(): void {
        Environment::lock(LOCK_DB . $this->instanceNo, TEMP_DIR);
        $this->context->query("INSERT INTO address (address_id, target, city, region_id) VALUES(1, 'nikde', 'nicov', 3)");
        $this->context->query("INSERT INTO school (school_id, name, name_abbrev, address_id) VALUES(1, 'Skola', 'SK', 1)");
        $this->context->query("INSERT INTO contest_year (contest_id, year, ac_year) VALUES(1, 1, 2000)");
        $this->context->query("INSERT INTO contest_year (contest_id, year, ac_year) VALUES(2, 1, 2000)");
    }

    protected function tearDown(): void {
        $this->truncateTables([
            DbNames::TAB_ORG,
            DbNames::TAB_GLOBAL_SESSION,
            DbNames::TAB_LOGIN,
            DbNames::TAB_PERSON_HISTORY,
            DbNames::TAB_CONTEST_YEAR,
            DbNames::TAB_SCHOOL,
            DbNames::TAB_ADDRESS,
            DbNames::TAB_PERSON,
        ]);
    }

    protected function createPerson(string $name, string $surname, array $info = [], ?array $loginData = null): int {
        $this->context->query("INSERT INTO person (other_name, family_name,gender) VALUES(?, ?,'M')", $name, $surname);
        $personId = $this->context->getInsertId();

        if ($info) {
            $info['person_id'] = $personId;
            $this->insert(DbNames::TAB_PERSON_INFO, $info);
        }

        if (!is_null($loginData)) {
            $data = [
                'login_id' => $personId,
                'person_id' => $personId,
                'active' => 1,
            ];
            $loginData = array_merge($data, $loginData);

            $this->insert(DbNames::TAB_LOGIN, $loginData);

            if (isset($loginData['hash'])) {
                $pseudoLogin = (object)$loginData;
                $hash = PasswordAuthenticator::calculateHash($loginData['hash'], $pseudoLogin);
                $this->context->query('UPDATE login SET `hash` = ? WHERE person_id = ?', $hash, $personId);
            }
        }

        return $personId;
    }

    protected function assertPersonInfo(int $personId): IRow {
        $personInfo = $this->context->fetch('SELECT * FROM person_info WHERE person_id = ?', $personId);
        Assert::notEqual(null, $personInfo);
        return $personInfo;
    }

    protected function createPersonHistory(int $personId, int $acYear, ?int $school = null, ?int $studyYear = null, ?string $class = null): int {
        $this->context->query('INSERT INTO person_history (person_id, ac_year, school_id, class, study_year) VALUES(?, ?, ?, ?, ?)', $personId, $acYear, $school, $class, $studyYear);
        return $this->context->getInsertId();
    }

    protected function insert(string $table, array $data): int {
        $this->context->query("INSERT INTO `$table`", $data);
        return $this->context->getInsertId();
    }

    protected function truncateTables(array $tables): void {
        foreach ($tables as $table) {
            $this->context->query("DELETE FROM `$table`");
        }
    }
}
