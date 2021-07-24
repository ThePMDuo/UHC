<?php
declare(strict_types=1);

namespace AGTHARN\uhc\listener\type;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;

use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\Main;

class InventoryListener implements Listener
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
     * handleInventoryTransaction
     *
     * @param  InventoryTransactionEvent $event
     * @return void
     */
    public function handleInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        foreach ($transaction->getActions() as $action) {
            $item = $action->getSourceItem();

            if ($item->getNamedTagEntry('Report') || $item->getNamedTagEntry('Capes') || $item->getNamedTagEntry('Hub')) {
                $event->setCancelled();
            }
            if ($item->getId() === Item::ELYTRA) {
                $event->setCancelled();
            }
        }
    }
}
