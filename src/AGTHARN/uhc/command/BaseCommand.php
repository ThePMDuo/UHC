<?php
declare(strict_types=1);

namespace AGTHARN\uhc\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class BaseCommand extends Command
{

    /**
     * __construct
     *
     * @param  string $name
     * @return void
     */
    public function __construct(string $name)
    {
        parent::__construct($name);
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