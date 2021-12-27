<?php
declare(strict_types=1);

/**
 * ███╗░░░███╗██╗███╗░░██╗███████╗██╗░░░██╗██╗░░██╗░█████╗░
 * ████╗░████║██║████╗░██║██╔════╝██║░░░██║██║░░██║██╔══██╗
 * ██╔████╔██║██║██╔██╗██║█████╗░░██║░░░██║███████║██║░░╚═╝
 * ██║╚██╔╝██║██║██║╚████║██╔══╝░░██║░░░██║██╔══██║██║░░██╗
 * ██║░╚═╝░██║██║██║░╚███║███████╗╚██████╔╝██║░░██║╚█████╔╝
 * ╚═╝░░░░░╚═╝╚═╝╚═╝░░╚══╝╚══════╝░╚═════╝░╚═╝░░╚═╝░╚════╝░
 * 
 * Copyright (C) 2020-2021 AGTHARN
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace AGTHARN\uhc\util\form;

use pocketmine\player\Player;

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
    private Main $plugin;

    /** @var GameProperties */
    private GameProperties $gameProperties;

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
