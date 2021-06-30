<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\utils\TextFormat;
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
        $player->setFood($player->getMaxFood() ?? 10);
        $player->setHealth($player->getMaxHealth() ?? 10);
        $player->removeAllEffects();
        $player->setXpLevel(0);
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        $player->getOffHandInventory()->clearAll(); /** @phpstan-ignore-line */

        if ($fullReset) {
            $player->setGamemode(Player::SURVIVAL);
            $player->teleport(new Position($this->plugin->spawnPosX, $this->plugin->spawnPosY, $this->plugin->spawnPosZ, $this->plugin->getServer()->getLevelByName($this->plugin->map)));
        }
    }

    /**
     * playerJoinReset
     *
     * @param  Player $player
     * @return void
     */
    public function playerJoinReset(Player $player): void
    {   
        $player->setXpLevel(0);
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        $player->getOffHandInventory()->clearAll(); /** @phpstan-ignore-line */
        $player->setGamemode(Player::SURVIVAL);
        $player->teleport(new Position($this->plugin->spawnPosX, $this->plugin->spawnPosY, $this->plugin->spawnPosZ, $this->plugin->getServer()->getLevelByName($this->plugin->map)));
    
        $this->plugin->getClass('Items')->giveItems($player);
    }
    
    /**
     * toThin
     *
     * @param  string $str
     * @return string
     */
    public function toThin(string $str): string
    {
        return preg_replace('/%*(([a-z0-9_]+\.)+[a-z0-9_]+)/i', '%$1', $str) . TextFormat::ESCAPE . '　';
    }
}
