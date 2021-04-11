<?php

/*
 * InstantTree
 * A plugin by thebigsmileXD
 * Creates Trees instantly on placing saplings
 * Remember: there are no Acacia/Dark Oak trees yet
 */
namespace thebigsmileXD\InstantTree;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\generator\object\Tree;
use pocketmine\utils\Random;
use pocketmine\item\Item;
use pocketmine\block\Block;

class Main extends PluginBase implements Listener
{
	/** @var array */
	public $levels = [];
	
	/**
	 * onEnable
	 *
	 * @return void
	 */
	public function onEnable(): void
	{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveDefaultConfig();
		$this->levels = $this->getConfig()->get("worlds");
	}
	
	/**
	 * spawnTree
	 *
	 * @param  PlayerInteractEvent $event
	 * @return void
	 */
	public function spawnTree(PlayerInteractEvent $event): void
	{
		if ($event->getItem()->getId() === Item::SAPLING) {
			$pos = $event->getBlock()->getSide($event->getFace());
			$level = $event->getBlock()->getLevel();

			switch ($pos->getSide(0)->getId()) {
				case Block::DIRT:
				case Block::GRASS:
				case Block::PODZOL:
				case Block::FARMLAND:
					Tree::growTree($level, (int)$pos->x, (int)$pos->y, (int)$pos->z, new Random(mt_rand()), $event->getItem()->getDamage());
					if ($event->getPlayer()->isSurvival()) {
						$event->getPlayer()->getInventory()->removeItem($event->getItem());
					}
					$event->setCancelled();
					break;
				default:
					$event->setCancelled();
					break;
			}
		}
	}
}