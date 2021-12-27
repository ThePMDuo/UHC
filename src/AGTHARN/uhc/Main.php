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
namespace AGTHARN\uhc;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use AGTHARN\uhc\game\scenario\ScenarioManager;
use AGTHARN\uhc\game\border\Border;
use AGTHARN\uhc\game\team\TeamManager;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\game\GameHandler;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\task\UpdateCheck;
use AGTHARN\uhc\listener\ListenerManager;
use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\util\bossbar\BossBarAPI;
use AGTHARN\uhc\util\form\FormManager;
use AGTHARN\uhc\util\kit\KitManager;
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

class Main extends PluginBase
{   
    /** @var Config */
    public Config $secrets;
    
    /** @var GameManager */
    private GameManager $gamemanager;
    /** @var ScenarioManager */
    private ScenarioManager $scenariomanager;
    /** @var SessionManager */
    private SessionManager $sessionmanager;
    /** @var ListenerManager */
    private ListenerManager $listenermanager;
    /** @var TeamManager */
    private TeamManager $teammanager;
    /** @var GameHandler */
    private GameHandler $gamehandler;
    /** @var GameProperties */
    private GameProperties $gameproperties;

    /** @var Border */
    private Border $border;
    /** @var ChestSort */
    private ChestSort $chestsort;
    /** @var DeathChest */
    private DeathChest $deathchest;
    /** @var KitManager */
    private KitManager $kitmanager;
    /** @var Capes */
    private Capes $capes;
    /** @var Generators */
    private Generators $generators;
    /** @var UtilPlayer */
    private UtilPlayer $utilplayer;
    /** @var FormManager */
    private FormManager $formmanager;
    /** @var Discord */
    private Discord $discord;
    /** @var Recipes */
    private Recipes $recipes;
    /** @var Spoon */
    private Spoon $spoon;
    /** @var Profanity */
    private Profanity $profanity;
    /** @var BossBarAPI */
    private BossBarAPI $bossbarapi;

    /** @var DataConnector */
    public DataConnector $data;
    
    /**
     * onEnable
     * 
     * Plugin startup handler.
     * Everything is in order. Do not mess it up.
     *
     * @return void
     */
    public function onEnable(): void
    {   
        @mkdir($this->getDataFolder() . 'update_folder');
        @mkdir($this->getDataFolder() . 'scenarios');
        @mkdir($this->getDataFolder() . 'virions');
        @mkdir($this->getDataFolder() . 'plugins');
        @mkdir($this->getDataFolder() . 'capes');

        $this->saveResource('settings/setting.json');
        $this->saveResource('swearwords.yml');
        $this->saveResource('secrets.yml');

        $this->gameproperties = new GameProperties();
        $this->directory = new Directory($this);

        $this->gameproperties->allCapes = $this->directory->getDirContents($this->getServer()->getDataPath() . 'plugins/UHC/resources/capes', '/\.png$/');
        foreach ($this->gameproperties->allCapes as $cape) {
            $this->saveResource('capes/' . basename($cape));
        }

        $this->sessionmanager = new SessionManager();
        $this->utilplayer = new UtilPlayer($this);

        $this->generators = new Generators($this);
        $this->generators->prepareWorld();
        $this->generators->prepareNether();
        
        $this->border = new Border($this->getServer()->getWorldManager()->getWorldByName($this->gameproperties->map));
        
        $this->gamemanager = new GameManager($this);
        $this->scenariomanager = new ScenarioManager($this);
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
        $this->database = new Database($this);
        $this->punishments = new Punishments($this);
        $this->updatecheck = new UpdateCheck($this);
        $this->antivpn = new AntiVPN($this);
        $this->bossbarapi = new BossBarAPI();

        $this->do_not_edit = new Config($this->getDataFolder() . 'DO-NOT-EDIT.yml', Config::YAML);
        $this->secrets = new Config($this->getDataFolder() . 'secrets.yml', Config::YAML);

        $this->gameproperties->reportWebhook = $this->secrets->get('reportWebhook');
        $this->gameproperties->serverReportsWebhook = $this->secrets->get('serverReportsWebhook');
        $this->gameproperties->serverPowerWebhook = $this->secrets->get('serverPowerWebhook');
        
        $this->recipes->registerGoldenHead();
        $this->spoon->makeTheCheck();

        $this->getScheduler()->scheduleRepeatingTask($this->updatecheck, 6000);
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
     * Plugin shutdown handler.
     * Everything is in order. Do not mess it up.
     *
     * @return void
     */
    public function onDisable(): void
    {   
        if (isset($this->data)) $this->data->close();
    }

    /**
     * getClass
     * (calling for improvements!)
     * 
     * Simple way to get a class.
     * Based off class namespace.
     *
     * @param  string $class
     * @return mixed
     */
    public function getClass(string $class)
    {   
        return $this->{strtolower($class)};
    }
    
    /**
     * updateCheck
     * 
     * Checks if there is an updated folder,
     * and updates accordingly if so.
     *
     * @param  bool $justResult
     * @return bool
     */
    public function updateCheck($justResult = true): bool
    {   
        $updatesUHCFolder = $this->getServer()->getDataPath() . 'update_folder/UHC';
        $pluginsUHCFolder = $this->getServer()->getDataPath() . 'plugins/UHC';

        if ($this->do_not_edit->get('hasUpdate')) {
            // STEP 1: Check if UHC in update_folder exists
            if (is_dir($updatesUHCFolder)) {
                // STEP 2: Check if it's just results wanted
                if ($justResult = true) 
                    return true;
                // STEP 3: Delete the old UHC folder
                $this->directory->removeDir($pluginsUHCFolder);
                // STEP 4: Move new UHC folder to plugins
                $this->directory->callDirectory($updatesUHCFolder, false, function (string $namespace, string $directory) use ($pluginsUHCFolder, $updatesUHCFolder): void {
                    rename($directory, $pluginsUHCFolder . str_replace($updatesUHCFolder, $directory, ''));
                });
                // STEP 5: Delete the new UHC folder
                $this->directory->removeDir($updatesUHCFolder);
                // STEP 6: Replace all virions and plugins
                $allVirions = $this->directory->getDirContents($this->getServer()->getDataPath() . 'plugins/UHC/resources/virions', '/\.phar$/');
                $allPlugins = $this->directory->getDirContents($this->getServer()->getDataPath() . 'plugins/UHC/resources/plugins', '/\.phar$/');
                
                foreach ($allVirions as $virion) {
                    rename($virion, $this->getServer()->getDataPath() . 'virions/');
                }
                foreach ($allPlugins as $plugin) {
                    rename($plugin, $this->getServer()->getDataPath() . 'plugins/');
                }
                // STEP 7: Reboot the server
                register_shutdown_function(function () {
                    pcntl_exec("./bin/php7/bin/php ./PocketMine-MP.phar --no-wizard --disable-ansi");
                });
                $this->getServer()->shutdown();
                return true;
            }
        }
        return false;
    }
    
    /**
     * registerCommands
     * 
     * Registers all commands necessary.
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
     * Sets plugin's player whitelist state.
     *
     * @param  bool $operational
     * @return void
     */
    public function setOperational(bool $operational = true): void
    {
        if ($this->getOperational()) {
            $this->gameproperties->operational = $operational;
        }
    }
    
    /**
     * getOperational
     * 
     * Returns plugin's player whitelist state.
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
     * Returns colorized plugin's player whitelist state in a message.
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
     * Returns plugin's player whitelist state in a message.
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
     * getFile
     * 
     * Returns the plugin's file.
     *
     * @return string
     */
    public function getFile(): string
    {
        return parent::getFile();
    }
}
