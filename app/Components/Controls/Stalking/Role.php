<?php

namespace FKSDB\Components\Controls\Stalking;

use Authorization\Grant;
use FKSDB\ORM\DbNames;

/**
 * Class Role
 * @package FKSDB\Components\Controls\Stalking
 */
class Role extends StalkingComponent {

    public function render() {
        $this->beforeRender();
        $template = $this->template;
        $login = $this->modelPerson->getLogin();
        $roles = [];
        if ($login) {
            foreach ($login->related(DbNames::TAB_GRANT, 'login_id') as $grant) {
                $roles[] = new Grant($grant->contest_id, $grant->ref(DbNames::TAB_ROLE, 'role_id')->name);
            }
        }
        $this->template->roles = $roles;
        $template->setFile(__DIR__ . '/Role.latte');
        $template->render();
    }
}
