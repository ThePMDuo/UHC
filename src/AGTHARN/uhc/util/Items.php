<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\nbt\tag\StringTag;
use pocketmine\item\Item;
use pocketmine\Player;

use AGTHARN\uhc\Main;

class Items
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
     * giveItems
     *
     * @param  Player $player
     * @return void
     */
    public function giveItems(Player $player): void
    {
        $hub = Item::get(Item::COMPASS)->setCustomName("§aReturn To Hub");;
        $hub->setNamedTagEntry(new StringTag("Hub"));
        $report = Item::get(Item::BED)->setCustomName("§cReport");
        $report->setNamedTagEntry(new StringTag("Report"));

        $player->getInventory()->setItem(0 , $hub);
        $player->getInventory()->setItem(8 , $report);
    }
}
