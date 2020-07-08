<?php

namespace Maintenance;

use FKSDB\Config\GlobalParameters;
use Nette\SmartObject;
use Tracy\Debugger;

/**
 * Due to author's laziness there's no class doc (or it's self explaining).
 *
 * @author Michal Koutný <michal@fykos.cz>
 */
class Updater {
    use SmartObject;

    /** @var GlobalParameters */
    private $globalParameters;

    /**
     * Updater constructor.
     * @param GlobalParameters $globalParameters
     */
    public function __construct(GlobalParameters $globalParameters) {
        $this->globalParameters = $globalParameters;
    }

    /**
     * @param string $requestedBranch
     * @return void
     */
    public function installBranch($requestedBranch) {
        $deployment = $this->globalParameters['updater']['deployment'];
        foreach ($deployment as $path => $branch) {
            if ($branch != $requestedBranch) {
                continue;
            }
            $this->install($path, $branch);
        }
    }

    /**
     * @param mixed $path
     * @param mixed $branch
     */
    private function install($path, $branch) {
        $user = $this->globalParameters['updater']['installUser'];
        $script = $this->globalParameters['updater']['installScript'];
        $cmd = "sudo -u {$user} {$script} $path $branch >/dev/null 2>/dev/null &";
        Debugger::log("Running: $cmd");
        shell_exec($cmd);
    }

}
