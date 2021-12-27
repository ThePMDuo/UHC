<?php
declare(strict_types=1);

/**
 * ███╗░░░███╗██╗███╗░░██╗███████╗██╗░░░██╗██╗░░██╗░█████╗░
 * ████╗░████║██║████╗░██║██╔════╝██║░░░██║██║░░██║██╔══██╗
 * ██╔████╔██║██║██╔██╗██║█████╗░░██║░░░██║███████║██║░░╚═╝
 * ██║╚██╔╝██║██║██║╚████║██╔══╝░░██║░░░██║██╔══██║██║░░██╗
 * ██║░╚═╝░██║██║██║░╚███║███████╗╚██████╔╝██║░░██║╚█████╔╝
 * ╚═╝░░░░░╚═╝╚═╝╚═╝░░╚══╝╚══════╝░╚═════╝░╚═╝░░╚═╝░╚════╝░
 * 
 * Copyright (C) 2020-2021 AGTHARN
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace AGTHARN\uhc\util;

use pocketmine\item\Item;

use AGTHARN\uhc\Main;

class ChestSort
{
    /** @var Main */
    private Main $plugin;

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
     * sortChest
     *
     * @param  array $contents
     * @return array
     */
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
