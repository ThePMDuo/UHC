<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\Player;

use AGTHARN\uhc\Main;

class DeathChest
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
     * spawnChest
     *
     * @param  Player $player
     * @return void
     */
    public function spawnChest(Player $player): void
    {
        $pos = $player->getPosition();
        $level = $player->getLevel();
        $chest = Block::get(Block::CHEST);
        $x = ((int)$pos->getX());
        $y = ((int)$pos->getY());
        $z = ((int)$pos->getZ());
        $nbt = Chest::createNBT(new Vector3($x,$y,$z));
        $tile = Tile::createTile(Tile::CHEST, $level, $nbt);

        $level->setBlock(new Vector3($x, $y, $z), $chest);

        if ($tile instanceof Chest) {
            $tile->getInventory()->setContents($player->getInventory()->getContents()); 
        }
    }
}
