<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;

use AGTHARN\uhc\Main;

class Punishments
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
        $this->plugin = $plugin;
    }

    public function createPunishment(Player $player, string $selectedName, string $reason, string $duration, string $type): void
    {
        switch ($type) {
            case 'CREATE_BAN':
                if ($this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'ban "' . $selectedName . '" ' . $duration . ' ' . $reason)) {
                    $player->sendMessage('§aBan request successfully completed!');
                    return;
                }
                $player->sendMessage('§cBan request unsuccessful!');
                break;
            case 'CREATE_KICK':
                if ($this->plugin->getServer()->getPlayerByName($selectedName)->kick($reason, false)) {
                    $player->sendMessage('§aKick request successfully completed!');
                    return;
                }
                $player->sendMessage('§cKick request unsuccessful!');
                break;
            case 'CREATE_MUTE':
                if ($this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'mute "' . $selectedName . '" ' . $duration . ' ' . $reason)) {
                    $player->sendMessage('§aBan request successfully completed!');
                    return;
                }
                $player->sendMessage('§cBan request unsuccessful!');
                break;
            case 'CREATE_WARN':
                if ($this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'warn "' . $selectedName . '" ' . $duration . ' ' . $reason)) {
                    $player->sendMessage('§aWarn request successfully completed!');
                    return;
                }
                $player->sendMessage('§cWarn request unsuccessful!');
                break;
            case 'CHECK_WARNS':
                 // to do
                break;
            case 'CHECK_HISTORY':
                 // to do
                break;
        }
    }
}
