<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\Player;

use AGTHARN\uhc\Main;

use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

class Database
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
     * initDataDatabase
     *
     * @return DataConnector
     */
    public function initDataDatabase(): DataConnector
    {
        return libasynql::create($this->plugin, $this->plugin->secrets->get('database'), [
            'mysql' => 'database_stmts/mysql.sql'
        ]);
    }

    /**
     * registerPlayer
     *
     * @param  Player $player
     * @return void
     */
    public function registerPlayer(Player $player): void
    {
        $this->plugin->data->executeInsert('uhc.data.register', [
            'uuid' => $player->getUniqueId()->toString(),
            'playername' => $player->getName(),
            'cape' => 'normal_cape'
        ]);
    }
    
    /**
     * giveCape
     *
     * @param  Player $player
     * @return void
     */
    public function giveCape(Player $player): void
    {   
        $this->plugin->data->executeSelect('uhc.data.loadplayer', ['uuid' => $player->getUniqueId()->toString()], function(array $rows): void
        {
            foreach ($rows as [
                'uuid' => $uuid,
                'playername' => $playername,
                'cape' => $cape
            ]) {
                $player = $this->plugin->getServer()->getPlayerExact($playername);
                if ($uuid === $player->getUniqueId()->toString()) {
                    $this->plugin->getClass('Capes')->giveCape($player, $cape);
                }
            }
        });
    }
        
    /**
     * changeCape
     *
     * @param  Player $player
     * @param  string $cape
     * @return void
     */
    public function changeCape(Player $player, string $cape): void
    {
        $this->plugin->data->executeChange('uhc.data.update', [
            'uuid' => $player->getUniqueId()->toString(),
            'playername' => $player->getName(),
            'cape' => $cape
        ]);
    }

    /**
     * checkNameTally
     *
     * @param  Player $player
     * @return void
     */
    public function checkNameTally(Player $player): void
    {   
        $this->plugin->data->executeSelect('uhc.data.loadplayer', ['uuid' => $player->getUniqueId()->toString()], function(array $rows): void
        {
            foreach ($rows as [
                'uuid' => $uuid,
                'playername' => $playername,
                'cape' => $cape
            ]) {
                $player = $this->plugin->getServer()->getPlayerExact($playername);
                $playerUUID = $this->plugin->getServer()->getPlayerByUUID($uuid);
                if ($playerUUID === null || $uuid !== $player->getUniqueId()->toString() || $playername !== $player->getName()) {
                    if ($player->isOnline()) $player->kick("UUID does not match (possible impersonation attempt)", false);
                }
            }
        });
    }
}