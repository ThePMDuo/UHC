<?php
declare(strict_types=1);

namespace AGTHARN\uhc\command;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;

use AGTHARN\uhc\util\form\FormManager;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\Main;

use CortexPE\Commando\BaseCommand;

class ModCommand extends BaseCommand
{
    /** @var Main */
    protected Main $plugin;
    
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
        // nothing
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
        
        if ($sender->hasPermission('uhc.mod.command')) {
            $this->plugin->getClass('FormManager')->getForm($sender, FormManager::MOD_FORM)->sendSelectionModForm($sender);
        }
    }
}