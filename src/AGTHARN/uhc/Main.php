<?php
declare(strict_types=1);

namespace AGTHARN\uhc;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use AGTHARN\uhc\game\scenario\ScenarioManager;
use AGTHARN\uhc\game\border\Border;
use AGTHARN\uhc\game\team\TeamManager;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\game\GameHandler;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\listener\ListenerManager;
use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\kit\KitManager;
use AGTHARN\uhc\util\chunkgen\ChunkLoader;
use AGTHARN\uhc\util\bossbar\BossBarAPI;
use AGTHARN\uhc\util\form\FormManager;
use AGTHARN\uhc\util\virion\DEVirion;
use AGTHARN\uhc\util\Punishments;
use AGTHARN\uhc\util\UtilPlayer;
use AGTHARN\uhc\util\Generators;
use AGTHARN\uhc\util\Directory;
use AGTHARN\uhc\util\DeathChest;
use AGTHARN\uhc\util\ChestSort;
use AGTHARN\uhc\util\Profanity;
use AGTHARN\uhc\util\Database;
use AGTHARN\uhc\util\AntiVPN;
use AGTHARN\uhc\util\Discord;
use AGTHARN\uhc\util\Recipes;
use AGTHARN\uhc\util\Spoon;
use AGTHARN\uhc\util\Capes;

use AGTHARN\uhc\command\SpectatorCommand;
use AGTHARN\uhc\command\ReportCommand;
use AGTHARN\uhc\command\PingCommand;
use AGTHARN\uhc\command\ModCommand;

use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

class Main extends PluginBase
{   
    /** @var Config */
    public $secrets;
    
    /** @var GameManager */
    private $gamemanager;
    /** @var ScenarioManager */
    private $scenariomanager;
    /** @var SessionManager */
    private $sessionmanager;
    /** @var ListenerManager */
    private $listenermanager;
    /** @var TeamManager */
    private $teammanager;
    /** @var GameHandler */
    private $gamehandler;
    /** @var GameProperties */
    private $gameproperties;

    /** @var Border */
    private $border;
    /** @var ChestSort */
    private $chestsort;
    /** @var DeathChest */
    private $deathchest;
    /** @var KitManager */
    private $kitmanager;
    /** @var Capes */
    private $capes;
    /** @var Generators */
    private $generators;
    /** @var UtilPlayer */
    private $utilplayer;
    /** @var FormManager */
    private $formmanager;
    /** @var Discord */
    private $discord;
    /** @var Recipes */
    private $recipes;
    /** @var Spoon */
    private $spoon;
    /** @var Profanity */
    private $profanity;
    /** @var ChunkLoader */
    private $chunkloader;
    /** @var BossBarAPI */
    private $bossbarapi;
    /** @var DEVirion */
    private $devirion;

    /** @var DataConnector */
    public $data;
    
    /**
     * onLoad
     *
     * @return void
     */
    public function onLoad(): void
    {
		$this->devirion = new DEVirion($this);
        $this->devirion->virionLoad();
	}
    
    /**
     * onEnable
     *
     * @return void
     */
    public function onEnable(): void
    {   
        $this->devirion->virionEnable();
        
        // everything in order dont mess it up
        @mkdir($this->getDataFolder() . 'scenarios');
        @mkdir($this->getDataFolder() . 'capes');

        $this->saveResource("settings/setting.json");
        $this->saveResource('swearwords.yml');
        $this->saveResource('secrets.yml');

        $this->gameproperties = new GameProperties();
        $this->directory = new Directory($this);

        $this->gameproperties->allCapes = $this->directory->getDirContents($this->getServer()->getDataPath() . 'plugins/UHC/resources/capes', '/\.png$/');
        foreach ($this->gameproperties->allCapes as $cape) {
            $this->saveResource('capes/' . basename($cape));
        }

        $this->utilplayer = new UtilPlayer($this);

        $this->generators = new Generators($this);
        $this->generators->prepareWorld();
        $this->generators->prepareNether();
        
        $this->border = new Border($this->getServer()->getLevelByName($this->gameproperties->map));
        
        $this->gamemanager = new GameManager($this);
        $this->scenariomanager = new ScenarioManager($this);
        $this->sessionmanager = new SessionManager();
        $this->listenermanager = new ListenerManager($this);
        $this->teammanager = new TeamManager();
        $this->gamehandler = new GameHandler($this);

        $this->chestsort = new ChestSort($this);
        $this->deathchest = new DeathChest($this);
        $this->kitmanager = new KitManager();
        $this->capes = new Capes($this);
        $this->formmanager = new FormManager($this);
        $this->discord = new Discord($this);
        $this->recipes = new Recipes($this);
        $this->spoon = new Spoon($this);
        $this->profanity = new Profanity($this);
        $this->chunkloader = new ChunkLoader($this);
        $this->database = new Database($this);
        $this->punishments = new Punishments($this);
        $this->antivpn = new AntiVPN($this);
        $this->bossbarapi = new BossBarAPI();

        $this->secrets = new Config($this->getDataFolder() . 'secrets.yml', Config::YAML);

        $this->gameproperties->reportWebhook = $this->secrets->get('reportWebhook');
        $this->gameproperties->serverReportsWebhook = $this->secrets->get('serverReportsWebhook');
        $this->gameproperties->serverPowerWebhook = $this->secrets->get('serverPowerWebhook');
        ////////////////////////////////////////
        
        $this->recipes->registerGoldenHead();
        $this->spoon->makeTheCheck();

        $this->getScheduler()->scheduleRepeatingTask($this->gamemanager, 20);
        $this->listenermanager->registerListeners();
        $this->registerCommands();
    
        $this->discord->sendStartReport($this->getServer()->getVersion(), $this->gameproperties->buildNumber, $this->gameproperties->node, $this->gameproperties->uhcServer);

        $this->data = $this->database->initDataDatabase();
        $this->data->executeGeneric('uhc.data.init');
    }
    
    /**
     * onDisable
     *
     * @return void
     */
    public function onDisable(): void
    {   
        if (isset($this->data)) $this->data->close();
    }

    /**
     * getClass
     *
     * @param  string $class
     * @return mixed
     */
    public function getClass(string $class)
    {   
        // no camelcase sorry pmmp :")
        // should improve how this works btw
        return $this->{strtolower($class)};
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
     * setOperational
     *
     * @param  bool $operational
     * @return void
     */
    public function setOperational(bool $operational): void
    {
        if ($this->getOperational()) {
            $this->gameproperties->operational = $operational;
        }
    }
    
    /**
     * getOperational
     *
     * @return bool
     */
    public function getOperational(): bool
    {
        return $this->gameproperties->operational;
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
