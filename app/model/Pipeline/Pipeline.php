<?php

namespace Pipeline;

use FKS\Logging\ILogger;
use Nette\InvalidStateException;
use RuntimeException;

/**
 * Represents a simple pipeline where each stage has its input and output and they
 * comprise a linear chain.
 * 
 * @todo Implement generic ILogger.
 * 
 * @author Michal Koutný <michal@fykos.cz>
 */
class Pipeline {

    /**
     * @var array of IStage
     */
    private $stages = array();

    /**
     * @var mixed
     */
    private $input;

    /**
     * @var bool
     */
    private $fixedStages = false;

    /**
     * @var ILogger
     */
    private $logger = null;

    public function setLogger(ILogger $logger) {
        $this->logger = $logger;
    }

    public function getLogger() {
        return $this->logger;
    }

    /**
     * Stages can be added only in the build phase (not after setting the data).
     * 
     * @param Stage $stage
     * @throws InvalidStateException
     */
    public function addStage(Stage $stage) {
        if ($this->fixedStages) {
            throw new InvalidStateException('Cannot modify pipeline after loading data.');
        }
        $this->stages[] = $stage;
        $stage->setPipeline($this);
    }

    /**
     * Input to the pipeline.
     * 
     * @param mixed $input
     */
    public function setInput($input) {
        $this->fixedStages = true;
        $this->input = $input;
    }

    /**
     * Starts the pipeline.
     * 
     * @return mixed    output of the last stage
     */
    public function run() {
        $data = $this->input;
        foreach ($this->stages as $stage) {
            $stage->setInput($data);
            $stage->process();
            $data = $stage->getOutput();
        }

        return $data;
    }

    public function log($message, $level = ILogger::INFO) {
        if ($this->logger) {
            $this->logger->log($message, $level);
        }
    }

}

class PipelineException extends RuntimeException {
    
}
