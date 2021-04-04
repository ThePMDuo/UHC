<?php
declare(strict_types=1);

namespace AGTHARN\uhc\command;

use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

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
        parent::__construct("spectate", $plugin);
        $this->plugin = $plugin;
        $this->setUsage("/spectate <playerName>");
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
        if ($sender->getGamemode() !== 3) {
            $sender->sendMessage(TextFormat::RED . "You must be eliminated to use this command!");
            return;
        }

        if (!isset($args[0])) {
            throw new InvalidCommandSyntaxException();
        }

        $player = $this->plugin->getServer()->getPlayer(mb_strtolower($args[0]));
        if ($player === null) {
            $sender->sendMessage(TextFormat::RED . "That player is not in the game!");
            return;
        }

        if ($player === $sender) {
            $sender->sendMessage(TextFormat::RED . "You can't spectate yourself!");
            return;
        } else {
            $sender->teleport($player->getPosition());
            $sender->sendMessage(TextFormat::GREEN . "Currently spectating " . $player->getDisplayName());
            return;
        }
    }
}