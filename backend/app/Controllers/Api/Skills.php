<?php

namespace App\Controllers\Api;

use Atom\Plugins\SkillManager;

class Skills extends BaseApiController
{
    private static ?SkillManager $manager = null;

    private function getManager(): SkillManager
    {
        if (self::$manager === null) {
            self::$manager = new SkillManager();
        }
        return self::$manager;
    }

    public function list()
    {
        $manager = $this->getManager();
        $skills = $manager->getSkills();
        $data = array_map(fn($s) => $s->toArray(), $skills);
        return $this->respondSuccess($data);
    }

    public function enable($name = null)
    {
        if (empty($name)) {
            return $this->respondError('Skill name required');
        }

        $manager = $this->getManager();
        $success = $manager->enableSkill($name);

        if ($success) {
            return $this->respondSuccess(null, "Skill '{$name}' enabled successfully");
        }
        return $this->respondError("Skill '{$name}' not found");
    }

    public function disable($name = null)
    {
        if (empty($name)) {
            return $this->respondError('Skill name required');
        }

        $manager = $this->getManager();
        $success = $manager->disableSkill($name);

        if ($success) {
            return $this->respondSuccess(null, "Skill '{$name}' disabled successfully");
        }
        return $this->respondError("Skill '{$name}' not found");
    }

    public function history($name = null)
    {
        $manager = $this->getManager();
        $history = $manager->getExecutionHistory($name);
        return $this->respondSuccess($history);
    }
}
