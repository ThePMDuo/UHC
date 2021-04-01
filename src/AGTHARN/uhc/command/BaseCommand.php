<?php
declare(strict_types=1);

namespace AGTHARN\uhc\command;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;

use AGTHARN\uhc\Loader;

class BaseCommand extends PluginCommand{

    public function __construct(string $name, Loader $plugin)
    {
        parent::__construct($name, $plugin);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player){
            $sender->sendMessage("You must be a player to use this command!");
            return;
        }
        $this->onExecute($sender, $args);
    }

    public function onExecute(Player $sender, array $args): void
    {

    }
}