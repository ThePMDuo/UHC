<?php
declare(strict_types=1);

namespace AGTHARN\uhc\listener\type;

use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\ServerSettingsRequestPacket;
use pocketmine\network\mcpe\protocol\ServerSettingsResponsePacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;

use AGTHARN\uhc\session\SessionManager;
use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\game\GameManager;
use AGTHARN\uhc\Main;

class DataListener implements Listener
{
    /** @var Main */
    private $plugin;
    
    /** @var GameManager */
    private $gameManager;
    /** @var SessionManager */
    private $sessionManager;
    /** @var GameProperties */
    private $gameProperties;
            
    /**
     * __construct
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;

        $this->gameManager = $plugin->getClass('GameManager');
        $this->sessionManager = $plugin->getClass('SessionManager');
        $this->gameProperties = $plugin->getClass('GameProperties');
    }
    
    /**
     * handleDataPacketSendEvent
     *
     * @param  DataPacketSendEvent $event
     * @return void
     */
    public function handleDataPacketSendEvent(DataPacketSendEvent $event): void
    {
        $pk = $event->getPacket();

        if ($pk instanceof TextPacket) {
            switch ($pk->type) {
                case TextPacket::TYPE_TIP:
                case TextPacket::TYPE_POPUP:
                case TextPacket::TYPE_JUKEBOX_POPUP:
                    return;
                case TextPacket::TYPE_TRANSLATION;
                    $pk->message = $this->plugin->getClass('UtilPlayer')->toThin($pk->message);
                    break;
                default:
                    $pk->message .= TextFormat::ESCAPE . '　';
                    break;
            }
            return;
        }
        if ($pk instanceof AvailableCommandsPacket) {
            foreach ($pk->commandData as $name => $commandData) {
                $commandData->commandDescription = $this->plugin->getClass('UtilPlayer')->toThin($commandData->commandDescription);
            }
        }
    }
        
    /**
     * handleDataPacketReceive
     *
     * @param  DataPacketReceiveEvent $event
     * @return void
     */
    public function handleDataPacketReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        $player = $event->getPlayer();

        if ($packet instanceof EmotePacket) {
            $emoteId = $packet->getEmoteId();
            $this->plugin->getServer()->broadcastPacket($player->getViewers(), EmotePacket::create($player->getId(), $emoteId, 1 << 0));
        }

        if ($packet instanceof LoginPacket) {   
            // still get IP cuz we can still check if trying to ban evade or smth
            if (isset($packet->clientData['Waterdog_IP'])) {
                $this->waterdogIPs[$packet->username] = $packet->clientData['Waterdog_IP'];
                $player->kick('uh oh!!');
            }
        }

        // currently NOT WORKING (help ok)
        if ($packet instanceof ServerSettingsRequestPacket) {
            $packet = new ServerSettingsResponsePacket;
            $packet->formData = file_get_contents($this->plugin->getDataFolder() . 'settings/setting.json');
            $packet->formId = 8694;

            $player->dataPacket($packet);
        } elseif ($packet instanceof ModalFormResponsePacket) {
            $formId = $packet->formId;
            if ($formId !== 8694) {
                return;
            }
            $formData = (array) json_decode($packet->formData, true);
            $msg = $formData[1];
            $button = $formData[2];
            if ($button) {
                $player->sendMessage($msg);
            }
        }
    }
}
