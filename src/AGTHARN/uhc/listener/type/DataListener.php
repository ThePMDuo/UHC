<?php
declare(strict_types=1);

/**
 * ███╗░░░███╗██╗███╗░░██╗███████╗██╗░░░██╗██╗░░██╗░█████╗░
 * ████╗░████║██║████╗░██║██╔════╝██║░░░██║██║░░██║██╔══██╗
 * ██╔████╔██║██║██╔██╗██║█████╗░░██║░░░██║███████║██║░░╚═╝
 * ██║╚██╔╝██║██║██║╚████║██╔══╝░░██║░░░██║██╔══██║██║░░██╗
 * ██║░╚═╝░██║██║██║░╚███║███████╗╚██████╔╝██║░░██║╚█████╔╝
 * ╚═╝░░░░░╚═╝╚═╝╚═╝░░╚══╝╚══════╝░╚═════╝░╚═╝░░╚═╝░╚════╝░
 * 
 * Copyright (C) 2020-2021 AGTHARN
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace AGTHARN\uhc\listener\type;

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
    private Main $plugin;
    
    /** @var GameManager */
    private GameManager $gameManager;
    /** @var SessionManager */
    private SessionManager $sessionManager;
    /** @var GameProperties */
    private GameProperties $gameProperties;
            
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
     * (Code extracted from ChatThin)
     *
     * @param  DataPacketSendEvent $event
     * @return void
     */
    public function handleDataPacketSendEvent(DataPacketSendEvent $event): void
    {
        foreach ($event->getPackets() as $_ => $packet) {
            if ($packet instanceof TextPacket) {
                if ($packet->type === TextPacket::TYPE_TIP || $packet->type === TextPacket::TYPE_POPUP || $packet->type === TextPacket::TYPE_JUKEBOX_POPUP)
                    continue;

                if ($packet->type === TextPacket::TYPE_TRANSLATION) {
                    $packet->message = $this->plugin->getClass('UtilPlayer')->toThin($packet->message);
                } else {
                    $packet->message .= TextFormat::ESCAPE . '　';
                }
            } elseif ($packet instanceof AvailableCommandsPacket) {
                foreach ($packet->commandData as $name => $commandData) {
                    $commandData->description = $this->plugin->getClass('UtilPlayer')->toThin($commandData->description);
                }
            }
        }
    }
        
    /**
     * handleDataPacketReceive
     * 
     * (Code extracted from DoEmote)
     *
     * @param  DataPacketReceiveEvent $event
     * @return void
     */
    public function handleDataPacketReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        $player = $event->getOrigin()->getPlayer();

        if ($packet instanceof EmotePacket) {
            $emoteId = $packet->getEmoteId();
            $this->plugin->getServer()->broadcastPackets($player->getViewers(), [EmotePacket::create($player->getId(), $emoteId, 1 << 0)]);
        }

        if ($packet instanceof LoginPacket) {   
            // still get IP cuz we can still check if trying to ban evade or smth
            $clientData = $this->decodeJWT($packet->clientDataJwt);
            if (isset($clientData['Waterdog_IP'])) {
                $this->gameProperties->waterdogIPs[$player->getName()] = $clientData['Waterdog_IP'];
                $player->kick('uh oh!!');
            }
        }
    }
    
    /**
     * decodeJWT
     * 
     * (Code extracted from PMMP API 3)
     *
     * @param  string $token
     * @return array
     */
    public function decodeJWT(string $token): array
    {
		[$headB64, $payloadB64, $sigB64] = explode('.', $token);

		$rawPayloadJSON = base64_decode(strtr($payloadB64, '-_', '+/'), true);
		if($rawPayloadJSON === false){
			return [];
		}
		$decodedPayload = json_decode($rawPayloadJSON, true);
		if(!is_array($decodedPayload)){
			throw [];
		}
		return $decodedPayload;
	}
}
