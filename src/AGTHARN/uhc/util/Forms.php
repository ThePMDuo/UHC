<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\Player;

use AGTHARN\uhc\Main;

use AGTHARN\uhc\libs\jojoe77777\FormAPI\SimpleForm;
use AGTHARN\uhc\libs\jojoe77777\FormAPI\CustomForm;

class Forms
{    
    /** @var Main */
    private $plugin;

    /** @var array */
    private $playerArray = [];
    /** @var array */
    private $reportsArray = [];

    /**
     * __construct
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * sendNewsForm
     *
     * @param  Player $player
     * @return void
     */
    public function sendNewsForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data !== null) {
                switch ($data) {
                    case 0:
                        // updates
                        break;
                    case 1:
                        // exit button
                        break;
                }
            }
        }); 

        $form->setTitle('§l§7< §6MineUHC News §7>');
        $form->setContent("Welcome to MineUHC! This is a solo project and is currently in development. I hope you enjoy your time here!\n\nIf you encounter any issues or any rule-breakers, feel free to report it with §a/report§r.\n\n§rServer Bots:\n§aJAX §7- §rGame Manager§7; §rIn charge of running the UHC Game and controlling the events.\n§6COSMIC §7- §rAlerts Manager§7; §rIn charge of commands and small alerts.\n§cSteveAC §7- §rCheats Manager§7; §rIn charge of catching cheaters.\n");
        $form->addButton('§l§aView Updates');
        $form->addButton('§l§cExit');
        $form->sendToPlayer($player);
    }
    
    /**
     * sendReportForm
     *
     * @param  Player $player
     * @return void
     */
    public function sendReportForm(Player $player): void
    {
        $form = new CustomForm(function (Player $player, $data) {
            if ($data !== null) {
                $t1 = $data[0];
                $reported = $this->playerArray[$player->getName()][$t1];
                $t2 = $data[1];
                $reportType = $this->reportsArray[$player->getName()][$t2];

                $reporter = $player->getName();
                $reason = $data[2];

                $this->plugin->getClass('Discord')->sendReport($reporter, $reported, $reportType, $reason);
                $player->sendMessage('§aThanks for the report! You can ask any staff for a follow-up if needed!');
            }
        });
        
        $players = [];
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $onlinePlayer) {
            $players[] = $onlinePlayer->getName();
        }
        $this->playerArray[$player->getName()] = $players;
        $this->reportsArray[$player->getName()] = ['Bug', 'Exploits', 'Disrespectful', 'Inappropriate', 'Griefing/Stealing', 'Impersonation', 'Unobtainable Items', 'Advertising', 'Spamming'];

        $form->setTitle('§l§7< §6MineUHC Reporting §7>');
        $form->addDropdown('Select Player:', $this->playerArray[$player->getName()]);
        $form->addDropdown('Report Type:', $this->reportsArray[$player->getName()]);
        $form->addInput('Reason:', 'Reason for the report');
        $form->sendToPlayer($player);
    }

    /**
     * sendCapesForm
     *
     * @param  Player $player
     * @return void
     */
    public function sendCapesForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data !== null) {
                switch ($data) {
                    case 0:
                        // golden apple cape
                        $this->plugin->getClass('Capes')->createNormalCape($player);
                        $this->plugin->getClass('Database')->changeCape($player, 'normal_cape');
                        break;
                    case 1:
                        // potion cape
                        $this->plugin->getClass('Capes')->createPotionCape($player);
                        $this->plugin->getClass('Database')->changeCape($player, 'potion_cape');
                        break;
                    case 2:
                        // exit button
                        break;
                }
            }
        }); 

        $form->setTitle('§l§7< §6MineUHC Capes §7>');
        $form->setContent("Choose a cape of your own choice! All capes are unlocked for free!\n\nCapes are cosmetic and do not include gameplay features.");
        $form->addButton('§l§aGolden Apple Cape');
        $form->addButton('§l§aPotion Cape');
        $form->addButton('§l§cExit');
        $form->sendToPlayer($player);
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
                        $this->senOnlineModForm($player);
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
     * senOnlineModForm
     *
     * @param  Player $player
     * @return void
     */
    public function senOnlineModForm(Player $player): void
    {   
        $form = new CustomForm(function (Player $player, $data) {
            if ($data !== null) {
                $t1 = $data[0];
                $selectedName = $this->playerArray[$player->getName()][$t1];

                $player->sendMessage('§aProcessing request. Please wait.');
                if (empty($player) || empty($selectedName) || empty($data[1]) || empty($data[2])) {
                    $player->sendMessage('§cFailed to process request. Error: Empty Variable.');
                } else {
                    $this->sendConfirmation1ModForm($player, $selectedName, $data[1], $data[2]);
                }
            }
        });
        
        $players = [];
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $onlinePlayer) {
            $players[] = $onlinePlayer->getName();
        }
        $this->playerArray[$player->getName()] = $players;
        
        $form->setTitle('§l§7< §6MineUHC Moderation §7>');
        $form->addDropdown('Player:', $this->playerArray[$player->getName()]);
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

                $player->sendMessage('§aProcessing request. Please wait.');
                if (empty($player) || empty($data[0]) || empty($data[1]) || empty($data[1])) {
                    $player->sendMessage('§cFailed to process request. Error: Empty Variable.');
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
