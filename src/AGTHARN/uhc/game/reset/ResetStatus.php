<?php
declare(strict_types=1);

namespace AGTHARN\uhc\game\reset;

class ResetStatus
{   
    /** @var bool */
    public $entitiesReset = false;
    /** @var bool */
    public $worldReset = false;
    /** @var bool */
    public $timerReset = false;
    /** @var bool */
    public $teamReset = false;
    /** @var bool */
    public $chunkReset = false;
}
