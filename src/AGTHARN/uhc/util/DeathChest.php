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
        $x = ((int)$pos->getX());
        $y = ((int)$pos->getY());
        $z = ((int)$pos->getZ());
        $chest = Block::get(Block::CHEST);

        $level->setBlock(new Vector3($x, $y, $z), $chest);
        $level->setblock(new Vector3($x + 1, $y, $z), $chest);

        $chest1 = $level->getBlock(new Vector3($x, $y, $z));
        $chest2 = $level->getBlock(new Vector3($x + 1, $y, $z));

        $nbt = Chest::createNBT(new Vector3($x, $y, $z));
        $tile = Tile::createTile(Tile::CHEST, $level, $nbt);
        $nbt2 = Chest::createNBT(new Vector3($x + 1, $y, $z));
        $tile2 = Tile::createTile(Tile::CHEST, $level, $nbt2);

        $chest1->pairwith($chest2);
        $chest2->pairwith($chest1);

        if ($tile instanceof Chest) {
            $tile->getInventory()->setContents($player->getInventory()->getContents()); 
            $tile->getInventory()->addItem($this->plugin->getUtilItems()->getHead($player));
        }
    }
}
