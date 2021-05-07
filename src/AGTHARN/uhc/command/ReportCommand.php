<?php
declare(strict_types=1);

namespace AGTHARN\uhc\command;

use pocketmine\command\CommandSender;
use pocketmine\Player;

use AGTHARN\uhc\Main;

use AGTHARN\uhc\libs\CortexPE\Commando\BaseCommand;

class ReportCommand extends BaseCommand
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
            $sender->sendMessage('JAX »» You can only use this command in-game!');
            return;
        }
        $this->plugin->getForms()->sendReportForm($sender);
    }
}