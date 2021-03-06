<?php

namespace CLADevs\VanillaX\blocks\block;

use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\BlockToolType;
use pocketmine\block\Opaque;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;

class BellBlock extends Opaque{

    private int $facing = 0;

    public function __construct(){
        parent::__construct(new BlockIdentifier(BlockLegacyIds::BELL, 0, ItemIds::BELL), "Bell", new BlockBreakInfo(5, BlockToolType::PICKAXE, 0, 5));
    }

    public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, ?Player $player = null): bool{
        if($player !== null){
            $faces = [
                1 => 0,
                3 => 2,
                2 => 2,
                4 => 1,
                5 => 1
            ];
            $this->facing = $faces[$player->getHorizontalFacing()];
        }
        return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player); // TODO: Change the autogenerated stub
    }

    protected function writeStateToMeta(): int{
        return $this->facing;
    }
}