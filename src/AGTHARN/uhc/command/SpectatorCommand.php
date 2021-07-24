<?php
declare(strict_types=1);

namespace AGTHARN\uhc\command;

use pocketmine\command\CommandSender;
use pocketmine\Player;

use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\Main;

use CortexPE\Commando\BaseCommand;

class SpectatorCommand extends BaseCommand
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
        if ($sender->getGamemode() !== Player::SPECTATOR) {
            $sender->sendMessage(GameProperties::PREFIX_COSMIC . '§cYou must be eliminated to use this command!');
            return;
        }
        if (!isset($args[0])) {
            $sender->sendMessage(GameProperties::PREFIX_COSMIC . '§cPlease specify a player to spectate!');
            return;
        }

        $player = $this->plugin->getServer()->getPlayer($args[0]) ?? null;
        if ($player === $sender) {
            $sender->sendMessage(GameProperties::PREFIX_COSMIC . '§cYou cant spectate yourself!');
            return;
        }
        if ($player === null) {
            $sender->sendMessage(GameProperties::PREFIX_COSMIC . '§cThat player is not in the server!');
            return;
        }
        if (!$this->plugin->getClass('SessionManager')->getSession($player)->isPlaying()) {
            $sender->sendMessage(GameProperties::PREFIX_COSMIC . '§cThat player is not in the game!');
            return;
        }
        $sender->teleport($player->getPosition());
        $sender->sendMessage(GameProperties::PREFIX_COSMIC . '§aTeleported you to: ' . $player->getName());
    }
}