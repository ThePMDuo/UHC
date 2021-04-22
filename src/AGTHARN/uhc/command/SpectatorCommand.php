<?php
declare(strict_types=1);

namespace AGTHARN\uhc\command;

use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\Player;

use AGTHARN\uhc\Main;

class SpectatorCommand extends BaseCommand
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
        parent::__construct('spectate');
        $this->plugin = $plugin;
        $this->setUsage('/spectate <playerName>');
    }
    
    /**
     * onExecute
     *
     * @param  Player $sender
     * @param  array $args
     * @return void
     */
    public function onExecute(Player $sender, array $args) : void
    {
        if ($sender->getGamemode() !== Player::SPECTATOR) {
            $sender->sendMessage('§cYou must be eliminated to use this command!');
            return;
        }

        if (!isset($args[0])) {
            throw new InvalidCommandSyntaxException();
        }

        $player = $this->plugin->getServer()->getPlayer(mb_strtolower($args[0]));
        if ($player === null) {
            $sender->sendMessage('§cThat player is not in the game!');
            return;
        }

        if ($player === $sender) {
            $sender->sendMessage('§cYou cant spectate yourself!');
            return;
        } else {
            $sender->teleport($player->getPosition());
            $sender->sendMessage('§aCurrently spectating ' . $player->getDisplayName());
            return;
        }
    }
}