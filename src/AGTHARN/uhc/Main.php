<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\entity\utils\Bossbar;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use AGTHARN\uhc\game\team\TeamManager;
use AGTHARN\uhc\command\SpectatorCommand;
use AGTHARN\uhc\command\ReportCommand;
use AGTHARN\uhc\command\PingCommand;
use AGTHARN\uhc\command\ModCommand;
use AGTHARN\uhc\util\Generators;
use AGTHARN\uhc\util\Database;
use AGTHARN\uhc\util\Discord;
use AGTHARN\uhc\util\Recipes;
use AGTHARN\uhc\util\Spoon;

// DONT DELETE
use AGTHARN\uhc\listener\type\ListenerManager;

use AGTHARN\uhc\game\scenario\ScenarioManager;
use AGTHARN\uhc\game\reset\ResetStatus;
use AGTHARN\uhc\game\border\Border;
use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\util\ChunkLoader;
use AGTHARN\uhc\util\Punishments;
use AGTHARN\uhc\util\UtilPlayer;
use AGTHARN\uhc\util\DeathChest;
use AGTHARN\uhc\util\Directory;
use AGTHARN\uhc\util\ChestSort;
use AGTHARN\uhc\util\Profanity;
use AGTHARN\uhc\util\AntiVPN;
use AGTHARN\uhc\util\Handler;
use AGTHARN\uhc\util\Items;
use AGTHARN\uhc\util\Capes;
use AGTHARN\uhc\util\Forms;
use AGTHARN\uhc\kits\Kits;
// DONT OK

use AGTHARN\uhc\libs\poggit\libasynql\libasynql;

class Main extends PluginBase
{   
    /** @var string */
    public $uhcServer = 'GAME-1';
    /** @var string */
    public $node = 'NYC-01';
    /** @var string */
    public $buildNumber = 'BETA-1';
    /** @var bool */
    public $operational = true;

    /** @var int */
    public $normalSeed;
    /** @var string */
    public $map = 'UHC';
    /** @var int */
    public $netherSeed;
    /** @var string */
    public $nether = 'nether';

    /** @var int */
    public $startingPlayers = 0;
    /** @var int */
    public $startingTeams = 0;

    /** @var array */
    public $entityRegainNote = [];

    /** @var Config */
    public $secrets;
    /** @var string */
    public $reportWebhook = '';
    /** @var string */
    public $serverReportsWebhook = '';
    /** @var string */
    public $serverPowerWebhook = '';

    /** @var int */
    public $spawnPosX = 0;
    /** @var int */
    public $spawnPosY = 125;
    /** @var int */
    public $spawnPosZ = 0;

    /** @var ListenerManager */
    private $listenerManager;
    
    /**
     * onEnable
     *
     * @return void
     */
    public function onEnable(): void
    {   
        $this->runCompatibilityChecks();

        @mkdir($this->getDataFolder() . 'scenarios');
        $this->saveResource('capes/normal_cape.png');
        $this->saveResource('capes/potion_cape.png');
        $this->saveResource('swearwords.yml');
        $this->saveResource('secrets.yml');
        $this->getClass('Spoon')->makeTheCheck();

        $this->getClass('Generators')->prepareWorld();
        //$this->getClass('Generators')->prepareNether();

        $this->getClass('Recipes')->registerGoldenHead();

        $this->secrets = new Config($this->getDataFolder() . 'secrets.yml', Config::YAML);

        $this->reportWebhook = $this->secrets->get('reportWebhook');
        $this->serverReportsWebhook = $this->secrets->get('serverReportsWebhook');
        $this->serverPowerWebhook = $this->secrets->get('serverPowerWebhook');

        $this->listenerManager = new ListenerManager($this);

        $this->getScheduler()->scheduleRepeatingTask($this->getClass('GameManager'), 20);
        $this->getListenerManager()->registerListeners();
        $this->registerCommands();
    
        $this->getClass('Discord')->sendStartReport($this->getServer()->getVersion(), $this->buildNumber, $this->node, $this->uhcServer);
        
        $this->data = $this->getClass('Database')->initDataDatabase();
        $this->data->executeGeneric('uhc.data.init');
    }
    
    /**
     * onDisable
     *
     * @return void
     */
    public function onDisable(): void
    {   
        $this->getClass('Generators')->removeAllWorlds();

        if (isset($this->data)) $this->data->close();
    }
    
    /**
     * runCompatibilityChecks
     *
     * @return void
     */
    public function runCompatibilityChecks()
    {
        if (!extension_loaded('gd')) {
            $this->getServer()->getLogger()->error('GD Lib is disabled! Turning on safe mode!');
            $this->setOperational(false);
        }
        if (!in_array($this->getServer()->getApiVersion(), $this->getDescription()->getCompatibleApis())) {
            $this->getServer()->getLogger()->error('Incompatible version! Turning on safe mode!');
            $this->setOperational(false);
        }
    }
    
    /**
     * registerCommands
     *
     * @return void
     */
    public function registerCommands(): void
    {
        $this->getServer()->getCommandMap()->register('spectate', new SpectatorCommand($this, 'spectate', 'Spectates a player!', [
            'spectator'
        ]));
        $this->getServer()->getCommandMap()->register('report', new ReportCommand($this, 'report', 'Report a player for breaking a rule!', [
            'reports', 
            'reporter', 
            'reporting', 
            'callmod', 
            'modcall', 
            'admincall', 
            'calladmin',
            'saveme',
            'hacker',
            'cheater'
        ]));
        $this->getServer()->getCommandMap()->register('ping', new PingCommand($this, 'ping', 'Provides a report on your ping!', [
            'myping',
            'getping'
        ]));
        $this->getServer()->getCommandMap()->register('moderate', new ModCommand($this, 'moderate', 'Secret stuff!', [
            'mod',
            'punish'
        ]));
    }
    
    /**
     * getClass
     *
     * @param  string $namespace
     * @return mixed
     */
    public function getClass(string $namespace): mixed
    {
        if (
            strpos($namespace, 'SessionManager') !== false ||
            strpos($namespace, 'TeamManager') !== false ||
            strpos($namespace, 'KitsManager') !== false
        ) {
            return new $namespace();
        }
        return new $namespace($this);
    }  

    /**
     * getBossBar
     *
     * @return mixed
     */
    public function getBossBar(string $text, float $float)
    {
        return new Bossbar($text, $float); /** @phpstan-ignore-line */
    }

    /**
     * getListenerManager
     *
     * @return ListenerManager
     */
    public function getListenerManager(): ListenerManager
    {
        return $this->listenerManager ?? new ListenerManager($this);
    }
    
    /**
     * setOperational
     *
     * @param  bool $operational
     * @return void
     */
    public function setOperational(bool $operational): void
    {
        if ($this->getOperational()) {
            $this->operational = $operational;
        }
    }
    
    /**
     * getOperational
     *
     * @return bool
     */
    public function getOperational(): bool
    {
        return $this->operational;
    }
    
    /**
     * getOperationalColoredMessage
     *
     * @return string
     */
    public function getOperationalColoredMessage(): string
    {
        if ($this->getOperational()) {
            return '§aSERVER OPERATIONAL';
        }
        return '§cSERVER UNOPERATIONAL: POSSIBLY RESETTING';
    }

    /**
     * getOperationalMessage
     *
     * @return string
     */
    public function getOperationalMessage(): string
    {
        if ($this->getOperational()) {
            return 'SERVER OPERATIONAL';
        }
        return 'SERVER UNOPERATIONAL: POSSIBLY RESETTING';
    }
    
    /**
     * isPhar
     *
     * @return bool
     */
    public function isPhar(): bool
    {
        return parent::isPhar();
    }
    
    /**
     * getFile
     *
     * @return string
     */
    public function getFile(): string
    {
        return parent::getFile();
    }
}
