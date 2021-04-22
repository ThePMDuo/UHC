<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\level\Position;
use pocketmine\Player;

use AGTHARN\uhc\Main;

class UtilPlayer
{   
    /** @var Main */
    private $plugin;

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
     * resetPlayer
     *
     * @param  Player $player
     * @return void
     */
    public function resetPlayer(Player $player, bool $fullReset = false): void
    {   
        $player->setFood($player->getMaxFood());
        $player->setHealth($player->getMaxHealth());
        $player->removeAllEffects();
        $player->setXpLevel(0);
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        $player->getOffHandInventory()->clearAll(); /** @phpstan-ignore-line */
        $player->setGamemode(Player::SURVIVAL);

        if ($fullReset) {
            $player->teleport(new Position($this->plugin->spawnPosX, $this->plugin->spawnPosY, $this->plugin->spawnPosZ, $this->plugin->getServer()->getLevelByName($this->plugin->map)));
        }
    }
}
