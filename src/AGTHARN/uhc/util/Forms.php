<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\Player;

use AGTHARN\uhc\libs\jojoe77777\FormAPI\SimpleForm;

class Forms
{    
    /**
     * sendNewsForm
     *
     * @param  Player $player
     * @return mixed
     */
    public function sendNewsForm(Player $player): mixed
    {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data === null) return;
                
            switch ($data) {
                case 0:
                    // updates
                    break;
                case 1:
                    // exit button
                    break;
            }
        });
            
        $form->setTitle('§l§7< §6MineUHC News §7>');
        $form->setContent('Nothing Yet');
        $form->addButton('§aView Updates');
        $form->addButton('§cExit');
        $form->sendToPlayer($player);
        return $form;
    }
}
