<?php

namespace FKSDB\Results\Models;

use FKSDB\ORM\Models\ModelContest;
use FKSDB\ORM\Services\ServiceTask;
use FKSDB\ORM\Tables\TypedTableSelection;
use FKSDB\Results\EvaluationStrategies\EvaluationStrategy;
use FKSDB\Results\ModelCategory;
use Nette\Database\Connection;
use Nette\Database\Row;
use Nette\InvalidStateException;

/**
 * General results sheet with contestants and their ranks.
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
abstract class AbstractResultsModel {

    const COL_DEF_LABEL = 'label';
    const COL_DEF_LIMIT = 'limit';
    const DATA_NAME = 'name';
    const DATA_SCHOOL = 'school';
    const DATA_RANK_FROM = 'from';
    const DATA_RANK_TO = 'to';

    const LABEL_SUM = 'sum';
    const ALIAS_SUM = 'sum';
    const LABEL_PERCETAGE = 'percent';
    const ALIAS_PERCENTAGE = 'percent';
    const LABEL_TOTAL_PERCENTAGE = 'total-percent';
    const ALIAS_TOTAL_PERCENTAGE = 'total-percent';

    /* for use in School Results */
    const LABEL_UNWEIGHTED_SUM = 'unweighted-sum';
    const ALIAS_UNWEIGHTED_SUM = 'unweighted-sum';
    const LABEL_CONTESTANTS_COUNT = 'contestants-count';
    const ALIAS_CONTESTANTS_COUNT = 'contestants-count';

    const COL_ALIAS = 'alias';
    const DATA_PREFIX = 'd';

    protected int $year;

    protected ModelContest $contest;

    protected ServiceTask $serviceTask;

    protected Connection $connection;

    protected EvaluationStrategy $evaluationStrategy;

    /**
     * FKSDB\Results\Models\AbstractResultsModel constructor.
     * @param ModelContest $contest
     * @param ServiceTask $serviceTask
     * @param Connection $connection
     * @param int $year
     * @param EvaluationStrategy $evaluationStrategy
     */
    public function __construct(ModelContest $contest, ServiceTask $serviceTask, Connection $connection, int $year, EvaluationStrategy $evaluationStrategy) {
        $this->contest = $contest;
        $this->serviceTask = $serviceTask;
        $this->connection = $connection;
        $this->year = $year;
        $this->evaluationStrategy = $evaluationStrategy;
    }

    /**
     * @param ModelCategory $category
     * @return Row[]
     */
    public function getData(ModelCategory $category): array {
        $sql = $this->composeQuery($category);

        $stmt = $this->connection->query($sql);
        $result = $stmt->fetchAll();

        // reverse iteration to get ranking ranges
        $nextSum = false; //because last sum can be null
        for ($i = count($result) - 1; $i >= 0; --$i) {
            if ($result[$i][self::ALIAS_SUM] !== $nextSum) {
                $result[$i][self::DATA_RANK_TO] = $i + 1;
            } else {
                $result[$i][self::DATA_RANK_TO] = $result[$i + 1][self::DATA_RANK_TO];
            }
            $nextSum = $result[$i][self::ALIAS_SUM];
        }

        return $result;
    }

    /**
     * Unused?
     * @return array
     */
    public function getMetaColumns() {
        return [
            self::DATA_NAME,
            self::DATA_SCHOOL,
            self::DATA_RANK_FROM,
            self::DATA_RANK_TO,
        ];
    }

    abstract protected function composeQuery(ModelCategory $category): string;

    /**
     * @note Work only with numeric types.
     * @param mixed $conditions
     * @return string
     */
    protected function conditionsToWhere($conditions): string {
        $where = [];
        foreach ($conditions as $col => $value) {
            if (is_array($value)) {
                $set = [];
                $hasNull = false;
                foreach ($value as $subValue) {
                    if ($subValue === null) {
                        $hasNull = true;
                    } else {
                        $set[] = $subValue;
                    }
                }
                $inClause = "$col IN (" . implode(',', $set) . ")";
                if ($hasNull) {
                    $where[] = "$inClause OR $col IS NULL";
                } else {
                    $where[] = $inClause;
                }
            } elseif ($value === null) {
                $where[] = "$col IS NULL";
            } else {
                $where[] = "$col = $value";
            }
        }
        return "(" . implode(') and (', $where) . ")";
    }

    protected function getTasks(int $series): TypedTableSelection {
        return $this->serviceTask->getTable()
            ->select('task_id, label, points,series')
            ->where([
                'contest_id' => $this->contest->contest_id,
                'year' => $this->year,
                'series' => $series,
            ])
            ->order('tasknr');
    }

    /**
     * @return ModelCategory[]
     */
    abstract public function getCategories(): array;

    /**
     * Single series number or array of them.
     * @param int[]|int $series
     * TODO int[] OR int
     */
    abstract public function setSeries($series);

    /**
     * @return int[]|int (see setSeries)
     */
    abstract public function getSeries();

    /**
     * @param ModelCategory $category
     * @return array
     * @throws InvalidStateException
     */
    abstract public function getDataColumns(ModelCategory $category): array;
}
