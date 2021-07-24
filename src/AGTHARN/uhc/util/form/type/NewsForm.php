<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util\form\type;

use pocketmine\Player;

use jojoe77777\FormAPI\SimpleForm;

class NewsForm
{    
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
        $form->setContent("Welcome to MineUHC! This is a solo project and is currently in development. I hope you enjoy your time here!\n\nIf you encounter any issues or any rule-breakers, feel free to report it with §a/report§r.\n\n§rServer Bots:\n§aJAX §7- §rGame Manager§7; §rIn charge of running the UHC Game and controlling the events.\n§6COSMIC §7- §rAlerts Manager§7; §rIn charge of commands and small alerts.\n§cSteveAC §7- §rCheats Manager§7; §rIn charge of catching cheaters.\n§gFABIO §7- §rModeration Manager§7; §rIn charge of assisting guardians.\n");
        $form->addButton('§l§aView Updates');
        $form->addButton('§l§cExit');
        $form->sendToPlayer($player);
    }
}
