<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util\form\type;

use pocketmine\Player;

use jojoe77777\FormAPI\SimpleForm;

class ErrorForm
{        
    /**
     * sendErrorForm
     *
     * @param  Player $player
     * @param  string $reason
     * @return void
     */
    public function sendErrorForm(Player $player, string $reason): void
    {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data !== null) {
                switch ($data) {
                    case 0:
                        // exit button
                        break;
                }
            }
        }); 
        
        $form->setTitle('§l§7< §aFATAL ERROR! §7>');
        $form->setContent("You have encountered an error! Please send this to a staff member!\n\n" . $reason);
        $form->addButton('§l§cExit');
        $form->sendToPlayer($player);
    }
}
