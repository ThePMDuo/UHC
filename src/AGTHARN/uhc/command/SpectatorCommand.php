<?php
declare(strict_types=1);

namespace AGTHARN\uhc\command;

use pocketmine\command\CommandSender;
use pocketmine\Player;

use AGTHARN\uhc\Main;

use AGTHARN\uhc\libs\CortexPE\Commando\BaseCommand;

class SpectatorCommand extends BaseCommand
{

    /**
     * plugin
     *
     * @var Main
     */
    private $plugin;
    
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
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage('COSMIC »» You can only use this command in-game!');
            return;
        }

        if ($sender->getGamemode() !== Player::SPECTATOR) {
            $sender->sendMessage('§6COSMIC §7»» §cYou must be eliminated to use this command!');
            return;
        }

        if (!isset($args[0])) {
            $sender->sendMessage('§6COSMIC §7»» §cPlease specify a player to spectate!');
            return;
        }

        $player = $this->plugin->getServer()->getPlayer(mb_strtolower($args[0])) ?? null;
        if ($player === null) {
            $sender->sendMessage('§6COSMIC §7»» §cThat player is not in the game!');
            return;
        }

        if ($player === $sender) {
            $sender->sendMessage('§6COSMIC §7»» §cYou cant spectate yourself!');
            return;
        }
        $sender->teleport($player->getPosition());
        $sender->sendMessage('§6COSMIC §7»» §aTeleported you to: ' . $player->getName());
    }
}