<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\inventory\ShapedRecipe;
use pocketmine\nbt\tag\StringTag;
use pocketmine\item\Item;

use AGTHARN\uhc\Main;

class Recipes
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
     * registerGoldenHead
     *
     * @return void
     */
    public function registerGoldenHead(): void
    {   
        $playerHead = Item::get(Item::MOB_HEAD, 3, 1);
        $playerHead->setNamedTagEntry(new StringTag('player_head_1'));
        $this->plugin->getServer()->getCraftingManager()->registerRecipe(new ShapedRecipe(
        [
            'GGG',
            'GHG',
            'GG'
        ], [
            "G" => Item::get(Item::GOLD_INGOT, 0, 1),
            "H" => $playerHead
        ], [
            $this->plugin->getUtilItems()->getGoldenHead()
        ]));
    }
}
