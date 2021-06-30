<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\Player;

use AGTHARN\uhc\Main;

use AGTHARN\uhc\libs\poggit\libasynql\DataConnector;
use AGTHARN\uhc\libs\poggit\libasynql\libasynql;

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
     * giveCape
     *
     * @param  Player $player
     * @return void
     */
    public function giveCape(Player $player): void
    {   
        $this->plugin->getClass('DataConnector')->executeSelect('uhc.data.loadplayer', ['xuid' => $player->getXuid() . $this->plugin->secrets->get('secret-xuid-numbers')], function(array $rows): void
        {
            foreach ($rows as [
                'xuid' => $xuid,
                'playername' => $playername,
                'cape' => $cape
            ]) {
                $player = $this->plugin->getServer()->getPlayerExact($playername);
                if ($xuid === $player->getXuid() . $this->plugin->secrets->get('secret-xuid-numbers')) {
                    $this->plugin->getClass('Capes')->giveCape($player, $cape);
                }
            }
        });
    }
    
    /**
     * registerPlayer
     *
     * @param  Player $player
     * @return void
     */
    public function registerPlayer(Player $player): void
    {
        $this->plugin->getClass('DataConnector')->executeInsert('uhc.data.register', [
            'xuid' => $player->getXuid() . $this->plugin->secrets->get('secret-xuid-numbers'),
            'playername' => $player->getName(),
            'cape' => 'normal_cape'
        ]);
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
        $this->plugin->getClass('DataConnector')->executeChange('uhc.data.update', [
            'xuid' => $player->getXuid() . $this->plugin->secrets->get('secret-xuid-numbers'),
            'playername' => $player->getName(),
            'cape' => $cape
        ]);
    }
}