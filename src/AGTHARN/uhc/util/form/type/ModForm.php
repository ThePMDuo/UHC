<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util\form\type;

use pocketmine\Player;

use AGTHARN\uhc\util\form\FormManager;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\Main;

use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;

class ModForm
{    
    /** @var Main */
    private $plugin;
    /** @var GameProperties */
    private $gameProperties;

    /**
     * __construct
     *
     * @param  Main $plugin
     * @param  GameProperties $gameProperties
     * @return void
     */
    public function __construct(Main $plugin, GameProperties $gameProperties)
    {
        $this->plugin = $plugin;

        $this->gameProperties = $gameProperties;
    }

    /**
     * sendSelectionModForm
     *
     * @param  Player $player
     * @return void
     */
    public function sendSelectionModForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data !== null) {
                switch ($data) {
                    case 0:
                        // online player
                        $this->sendOnlineModForm($player);
                        break;
                    case 1:
                        // offline player
                        $this->sendOfflineModForm($player);
                        break;
                    case 2:
                        // exit button
                        break;
                }
            }
        }); 

        $form->setTitle('§l§7< §6MineUHC Capes §7>');
        $form->setContent('Is the player online or offline?');
        $form->addButton('ONLINE');
        $form->addButton('OFFLINE');
        $form->addButton('§l§cExit');
        $form->sendToPlayer($player);
    }

    /**
     * sendOnlineModForm
     *
     * @param  Player $player
     * @return void
     */
    public function sendOnlineModForm(Player $player): void
    {   
        $form = new CustomForm(function (Player $player, $data) {
            if ($data !== null) {
                $t1 = $data[0];
                $selectedName = $this->gameProperties->allPlayersForm[$player->getName()][$t1];

                $player->sendMessage(GameProperties::PREFIX_FABIO . '§aProcessing request. Please wait.');
                if (empty($player) || empty($selectedName) || empty($data[1]) || empty($data[2])) {
                    $this->plugin->getClass('FormManager')->getForm($sender, FormManager::ERROR_FORM)->sendErrorForm($player, 'Empty Variable');
                } else {
                    $this->sendConfirmation1ModForm($player, $selectedName, $data[1], $data[2]);
                }
            }
        });

        $players = [];
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $onlinePlayer) {
            $players[] = $onlinePlayer->getName();
        }
        $this->gameProperties->allPlayersForm[$player->getName()] = $players;
        
        $form->setTitle('§l§7< §6MineUHC Moderation §7>');
        $form->addDropdown('Player:', $this->gameProperties->allPlayersForm[$player->getName()]);
        $form->addInput('Reason:', 'Reason for the selection', '-');
        $form->addInput('Duration:', 'm/h/d/w/M/y (eg. 1d12h)');
        $form->sendToPlayer($player);
    }

    /**
     * sendOfflineModForm
     *
     * @param  Player $player
     * @return void
     */
    public function sendOfflineModForm(Player $player): void
    {   
        $form = new CustomForm(function (Player $player, $data) {
            if ($data !== null) {
                $player->sendMessage(GameProperties::PREFIX_FABIO . '§aProcessing request. Please wait.');
                if (empty($player) || empty($data[0]) || empty($data[1]) || empty($data[1])) {
                    $this->plugin->getClass('FormManager')->getForm($sender, FormManager::ERROR_FORM)->sendErrorForm($player, 'Empty Variable');
                } else {
                    $this->sendConfirmation1ModForm($player, $data[0], $data[1], $data[2]);
                }
            }
        });
        
        $form->setTitle('§l§7< §6MineUHC Moderation §7>');
        $form->addInput('Player:', 'Player name');
        $form->addInput('Reason:', 'Reason for the selection', '-');
        $form->addInput('Duration:', 'm/h/d/w/M/y (eg. 1d12h)');
        $form->sendToPlayer($player);
    }
    
    /**
     * sendConfirmation1ModForm
     *
     * @param  Player $player
     * @param  string $selectedName
     * @param  string $reason
     * @param  string $duration
     * @return void
     */
    public function sendConfirmation1ModForm(Player $player, string $selectedName, string $reason, string $duration): void
    {
        $form = new SimpleForm(function (Player $player, $data) use ($selectedName, $reason, $duration) {
            if ($data !== null) {
                switch ($data) {
                    case 0:
                        $this->sendSelectedModForm($player, $selectedName, $reason, $duration);
                        break;
                    case 1:
                        // exit button
                        break;
                }
            }
        }); 

        $form->setTitle('§l§7< §6MineUHC Capes §7>');
        $form->setContent("PLEASE CONFIRM THE REQUEST:\n\nYour Name: " . $player->getName() . "\nSelected Name: " . $selectedName . "\nReason: " . $reason . "\nDuration: " . $duration);
        $form->addButton('CONFIRM');
        $form->addButton('§l§cExit');
        $form->sendToPlayer($player);
    }

    /**
     * sendSelectedModForm
     *
     * @param  Player $player
     * @param  string $selectedName
     * @param  string $reason
     * @param  string $duration
     * @return void
     */
    public function sendSelectedModForm(Player $player, string $selectedName, string $reason, string $duration): void
    {
        $form = new SimpleForm(function (Player $player, $data) use ($selectedName, $reason, $duration) {
            $type = 'ERROR';

            if ($data !== null) {
                switch ($data) {
                    case 0:
                        // create ban
                        $type = 'CREATE_BAN';
                        break;
                    case 1:
                        // create kick
                        $type = 'CREATE_KICK';
                        break;
                    case 2:
                        // create mute
                        $type = 'CREATE_MUTE';
                        break;
                    case 3:
                        // create warn
                        $type = 'CREATE_WARN';
                        break;
                    case 4:
                        // check warns
                        $type = 'CHECK_WARNS';
                        break;
                    case 5:
                        // check history
                        $type = 'CHECK_HISTORY';
                        break;
                    case 6:
                        // exit button
                        break;
                }
            }
            $this->sendConfirmation2ModForm($player, $selectedName, $reason, $duration, $type);
        }); 

        $form->setTitle('§l§7< §6MineUHC Capes §7>');
        $form->setContent("CURRENT REQUEST:\n\nYour Name: " . $player->getName() . "\nSelected Name: " . $selectedName . "\nReason: " . $reason . "\nDuration: " . $duration . "\n\nIf a request has been made wrongly, please contact an admin to help you undo an action.");
        $form->addButton('Create Ban');
        $form->addButton('Create Kick');
        $form->addButton('Create Mute');
        $form->addButton('Create Warn');
        $form->addButton('Check Warns');
        $form->addButton('Check History');
        $form->addButton('§l§cEnd Request');
        $form->sendToPlayer($player);
    }

    /**
     * sendConfirmation2ModForm
     *
     * @param  Player $player
     * @param  string $selectedName
     * @param  string $reason
     * @param  string $duration
     * @param  string $type
     * @return void
     */
    public function sendConfirmation2ModForm(Player $player, string $selectedName, string $reason, string $duration, string $type): void
    {
        $form = new SimpleForm(function (Player $player, $data) use ($selectedName, $reason, $duration, $type) {
            if ($data !== null) {
                switch ($data) {
                    case 0:
                        // create punishment
                        $this->plugin->getClass('Punishments')->createPunishment($player, $selectedName, $reason, $duration, $type);
                        break;
                    case 1:
                        // exit button
                        break;
                }
            }
        }); 

        $form->setTitle('§l§7< §6MineUHC Capes §7>');
        $form->setContent("PLEASE CONFIRM THE PUNISHMENT:\n\nYour Name: " . $player->getName() . "\nSelected Name: " . $selectedName . "\nReason: " . $reason . "\nDuration: " . $duration . "\nType: " . $type);
        $form->addButton('CONFIRM');
        $form->addButton('§l§cReturn');
        $form->sendToPlayer($player);
    }
}
