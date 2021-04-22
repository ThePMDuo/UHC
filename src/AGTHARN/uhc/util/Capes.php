<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\entity\Skin;
use pocketmine\Player;

use AGTHARN\uhc\Main;

class Capes
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
     * createNormalCape
     *
     * @param  Player $player
     * @return void
     */
    public function createNormalCape(Player $player): void
    {
        $img = imagecreatefrompng($this->plugin->getDataFolder() . 'normal_cape.png');
        $rgba = '';

        for ($y = 0; $y < imagesy($img); $y++) {
            for ($x = 0; $x < imagesx($img); $x++) {
                $argb = imagecolorat($img, $x, $y);
                $rgba .= chr(($argb >> 16) & 0xff) . chr(($argb >> 8) & 0xff) . chr($argb & 0xff) . chr(((~((int)($argb >> 24))) << 1) & 0xff);
            }
        }
        $this->setCape($player, $rgba);
    }
    
    /**
     * setCape
     *
     * @param  Player $player
     * @param  string $cape
     * @return void
     */
    public function setCape(Player $player, string $cape): void
    {
        $oldSkin = $player->getSkin();
        $newSkin = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $cape, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
        
        $player->setSkin($newSkin);
        $player->sendSkin();
    }
}
