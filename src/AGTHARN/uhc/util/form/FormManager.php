<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util\form;

use pocketmine\Player;

use AGTHARN\uhc\util\form\type\NewsForm;
use AGTHARN\uhc\util\form\type\CapeForm;
use AGTHARN\uhc\util\form\type\ReportForm;
use AGTHARN\uhc\util\form\type\ModForm;
use AGTHARN\uhc\util\form\type\ErrorForm;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\Main;

class FormManager
{    
    /** @var Main */
    private $plugin;

    /** @var GameProperties */
    private $gameProperties;

    /** @var int */
    public const ERROR_FORM = -1;
    /** @var int */
    public const NEWS_FORM = 1;
    /** @var int */
    public const CAPE_FORM = 2;
    /** @var int */
    public const REPORT_FORM = 3;
    /** @var int */
    public const MOD_FORM = 4;

    /**
     * __construct
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;

        $this->gameProperties = $plugin->getClass('GameProperties');
    }
    
    /**
     * getForm
     *
     * @param  Player $player
     * @param  int $type
     * @return mixed
     */
    public function getForm(Player $player, int $type)
    {
        switch ($type) {
            case self::ERROR_FORM:
                return new ErrorForm();
            case self::NEWS_FORM:
                return new NewsForm();
            case self::CAPE_FORM:
                return new CapeForm($this->plugin);
            case self::REPORT_FORM:
                return new ReportForm($this->plugin, $this->gameProperties);
            case self::MOD_FORM:
                return new ModForm($this->plugin, $this->gameProperties);
        }
        $errorForm = new ErrorForm();
        $errorForm->sendErrorForm($player, 'FORM NOT FOUND! TYPE: ' . $type);
    }
}
