<?php
declare(strict_types=1);

namespace AGTHARN\uhc\command;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;

use AGTHARN\uhc\Loader;

class BaseCommand extends PluginCommand{

    /**
     * __construct
     *
     * @param  string $name
     * @param  Loader $plugin
     * @return void
     */
    public function __construct(string $name, Loader $plugin)
    {
        parent::__construct($name, $plugin);
    }
    
    /**
     * execute
     *
     * @param  CommandSender $sender
     * @param  string $commandLabel
     * @param  array $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if(!$sender instanceof Player){
            $sender->sendMessage("You must be a player to use this command!");
            return;
        }
        $this->onExecute($sender, $args);
    }
    
    /**
     * onExecute
     *
     * @param  Player $sender
     * @param  array $args
     * @return void
     */
    public function onExecute(Player $sender, array $args): void
    {

    }
}