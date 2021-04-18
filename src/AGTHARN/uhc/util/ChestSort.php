<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\item\Item;
use pocketmine\Player;

use AGTHARN\uhc\Main;

class ChestSort
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

    public function sortChest(array $contents): array
    {
        for ($i = 0; $i < count($contents); $i++) {
			for ($j = 0; $j < $i; $j++) {
				if ($contents[$i]->equals($contents[$j], true, true) && !$contents[$i]->isNull()) {
					$maxStackSize = $contents[$j]->getMaxStackSize();
					$total = $contents[$j]->getCount() + $contents[$i]->getCount();
					if ($total > $maxStackSize) {
						$contents[$i]->setCount($contents[$i]->getCount() - ($maxStackSize - $contents[$j]->getCount()));
						$contents[$j]->setCount($maxStackSize);
					} else {
						$contents[$j]->setCount($contents[$i]->getCount() + $contents[$j]->getCount());
						$contents[$i]->setCount(0);
					}
				}
			}
		}
		return array_values(
			array_filter($contents, function(Item $item): bool {
				return !$item->isNull();
			})
		);
    }
}
