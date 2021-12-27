<?php

namespace CLADevs\VanillaX\blocks\block\basalt;

use CLADevs\VanillaX\blocks\utils\BlockVanilla;
use CLADevs\VanillaX\items\ItemIdentifiers;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockToolType;
use pocketmine\block\Opaque;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;

class BasaltBlock extends Opaque{

    private int $facing = 0;

    public function __construct(){
        parent::__construct(new BlockIdentifier(BlockVanilla::BASALT, 0, ItemIdentifiers::BASALT), "Basalt", new BlockBreakInfo(1.25, BlockToolType::PICKAXE, 0, 4.2));
    }

    public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null): bool{
        $faces = [
            1 => 0,
            3 => 2,
            2 => 2,
            4 => 1,
            5 => 1
        ];
        $this->facing = $faces[$face] ?? $face;
        return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
    }

    protected function writeStateToMeta(): int{
        return $this->facing;
    }
}