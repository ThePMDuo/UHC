<?php
declare(strict_types=1);

namespace AGTHARN\uhc\listener\type;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\block\Block;
use pocketmine\item\Item;

use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\Main;

class BlockListener implements Listener
{
    /** @var Main */
    private $plugin;
    
    /** @var GameManager */
    private $gameManager;
    /** @var SessionManager */
    private $sessionManager;
    /** @var GameProperties */
    private $gameProperties;
            
    /**
     * __construct
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;

        $this->gameManager = $plugin->getClass('GameManager');
        $this->sessionManager = $plugin->getClass('SessionManager');
        $this->gameProperties = $plugin->getClass('GameProperties');
    }
    
    /**
     * handleBreak
     *
     * @param  BlockBreakEvent $event
     * @return void
     */
    public function handleBreak(BlockBreakEvent $event): void
    {
        if (!$this->gameManager->hasStarted()) {
            $event->setCancelled();
            return;
        }

        switch ($event->getBlock()->getId()) { // drops
            case Block::LEAVES:
            case Block::LEAVES2:
                $rand = mt_rand(0, 100);
                if ($event->getItem()->equals(Item::get(Item::SHEARS, 0, 1), false, false)) {
                    if ($rand <= 6) {
                        $event->setDrops([Item::get(Item::APPLE, 0, 1)]);
                    }
                } else {
                    if ($rand <= 3) {
                        $event->setDrops([Item::get(Item::APPLE, 0, 1)]);
                    }
                }
                break;
            case Block::LOG:
            case Block::LOG2:
                $drops[] = Item::get(Item::PLANKS, 0, 4);
                $event->setDrops($drops);
                break;
            case Block::IRON_ORE:
                $drops[] = Item::get(Item::IRON_INGOT, 0, mt_rand(1, 2));
                $event->setDrops($drops);
                break;
            case Block::GOLD_ORE:
                $drops[] = Item::get(Item::GOLD_INGOT, 0, mt_rand(2, 4));
                $event->setDrops($drops);
                break;
            case Block::DIAMOND_ORE:
                $drops[] = Item::get(Item::DIAMOND, 0, mt_rand(1, 2));
                $event->setDrops($drops);
                break;
        }

        if ($this->plugin->getClass('UtilPlayer')->playerTreeChop($event->getPlayer(), $event->getBlock(), $event->getItem())) {
            $event->setCancelled();
        }
    }
    
    /**
     * handlePlace
     *
     * @param  BlockPlaceEvent $event
     * @return void
     */
    public function handlePlace(BlockPlaceEvent $event): void
    {   
        if (!$this->gameManager->hasStarted()) {
            $event->setCancelled();
        }
    }
}
