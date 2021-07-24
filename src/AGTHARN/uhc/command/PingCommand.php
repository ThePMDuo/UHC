<?php
declare(strict_types=1);

namespace AGTHARN\uhc\command;

use pocketmine\command\CommandSender;
use pocketmine\Player;

use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\Main;

use CortexPE\Commando\BaseCommand;

class PingCommand extends BaseCommand
{

    /**
     * plugin
     *
     * @var Main
     */
    protected $plugin;
    
    /**
     * __construct
     *
     * @param  Main $plugin
     * @param  string $name
     * @param  string $description
     * @param  array $aliases
     * @return void
     */
    public function __construct(Main $plugin, string $name, string $description, $aliases = [])
    {
        $this->plugin = $plugin;

        parent::__construct($plugin, $name, $description, $aliases);
    }

    /**
     * prepare
     *
     * @return void
     */
    public function prepare(): void
    {
    }
    
    /**
     * onRun
     *
     * @param  CommandSender $sender
     * @param  string $aliasUsed
     * @param  array $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(GameProperties::PREFIX_COSMIC . 'You can only use this command in-game!');
            return;
        }
        
        $ping = $sender->getPing();
        if ($ping <= 70) {
			$sender->sendMessage(GameProperties::PREFIX_COSMIC . '§rPing: §a' . $ping . 'ms');
		} elseif ($ping <= 150) {
			$sender->sendMessage(GameProperties::PREFIX_COSMIC . '§rPing: §e' . $ping . 'ms');
		} elseif ($ping <= 250) {
			$sender->sendMessage(GameProperties::PREFIX_COSMIC . '§rPing: §6' . $ping . 'ms');
		} else {
			$sender->sendMessage(GameProperties::PREFIX_COSMIC . '§rPing: §c' . $ping . 'ms');
		}
    }
}