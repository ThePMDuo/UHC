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
    private $playerArray;
    /** @var array */
    private $reportsArray;
    /** @var array */
    private $players;

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
        $form->setContent("Welcome to MineUHC! This is a solo project and is currently in development. I hope you enjoy your time here!\n\nIf you encounter any issues or any rule-breakers, feel free to report it with §a/report§r.");
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

                $this->plugin->getDiscord()->sendReport($reporter, $reported, $reportType, $reason);
                $player->sendMessage('§aThanks for the report! You can ask any staff for a follow-up if needed!');
            }
        });

        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player){
            $this->players[] = $player->getName();
        }
        $this->playerArray[$player->getName()] = $this->players;
        $this->reportsArray[$player->getName()] = ['Bug', 'Exploits', 'Disrespectful', 'Inappropriate', 'Griefing/Stealing', 'Impersonation', 'Unobtainable Items', 'Advertising', 'Spamming'];

        $form->setTitle('§l§7< §6MineUHC Reporting §7>');
        $form->addDropdown("Select Player:", $this->playerArray[$player->getName()]);
        $form->addDropdown('Report Type:', $this->reportsArray[$player->getName()]);
        $form->addInput("Reason:", "Reason for the report");
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
                        $this->plugin->getCapes()->createNormalCape($player);
                        $this->plugin->getDatabase()->changeCape($player, 'normal_cape');
                        break;
                    case 1:
                        // potion cape
                        $this->plugin->getCapes()->createPotionCape($player);
                        $this->plugin->getDatabase()->changeCape($player, 'potion_cape');
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
}
