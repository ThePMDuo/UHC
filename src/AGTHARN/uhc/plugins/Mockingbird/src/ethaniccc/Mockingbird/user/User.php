<?php

namespace ethaniccc\Mockingbird\user;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\processing\EventProcessor;
use ethaniccc\Mockingbird\processing\InboundPacketProcessor;
use ethaniccc\Mockingbird\processing\OutboundProcessor;
use ethaniccc\Mockingbird\processing\TestProcessor;
use ethaniccc\Mockingbird\processing\TickProcessor;
use ethaniccc\Mockingbird\user\data\ClickData;
use ethaniccc\Mockingbird\user\data\HitData;
use ethaniccc\Mockingbird\user\data\MoveData;
use ethaniccc\Mockingbird\user\data\TickData;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\MouseRecorder;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class User{

    /** @var Player - The player associated with this User class. */
    public $player;
    /** @var InboundPacketProcessor - The processor that will handle all incoming packets */
    public $inboundProcessor;
    /** @var OutboundProcessor - The processor that will handle all packets sent by the server. */
    public $outboundProcessor;
    /** @var TickProcessor - The processor that will run every tick for particular data. */
    public $tickProcessor;
    /** @var TestProcessor - A development processor for testing things that should not be used in a release. */
    public $testProcessor;
    /** @var MouseRecorder|null - A debug feature to record mouse movements and clicks, meant for determining aiming patterns and such. */
    public $mouseRecorder;
    /** @var Detection[] - The detections available that will run. */
    public $detections = [];
    /** @var array - The key is the detection name, and the value is the violations (float). - Make this a class? */
    public $violations = [];
    /** @var string[] - The key is the detection name and the value is a mini-debug log string. */
    public $debugCache = [];
    /** @var bool - The boolean value for if the user is logged into the server. */
    public $loggedIn = false;
    /** @var bool */
    public $isDesktop = false;
    /** @var bool - Boolean value for if the user is on Windows 10 */
    public $win10 = false;

    /** @var bool - The boolean value for if the user has alerts enabled. */
    public $alerts = false;
    /** @var int - The cooldown for alerts in seconds. */
    public $alertCooldown;
    /** @var string|null - The detection that the user should get debug information from. */
    public $debugChannel = null;
    /** @var bool - The boolean value for if the user is being packet logged. */
    public $isPacketLogged = false;
    /** @var DataPacket[] - The packet log of the user. */
    public $packetLog = [];
    /**
     * @var int - The client ticks that have passed since the specified "thing". For
     * instance, if the client sends 10 PlayerAuthInputPackets since their teleport,
     * $timeSinceTeleport would be 10.
     */
    public $timeSinceTeleport = 0;
    public $timeSinceJoin = 0;
    public $timeSinceMotion = 0;
    public $timeSinceDamage = 0;
    public $timeSinceAttack = 0;
    public $timeSinceStoppedFlight = 0;
    public $timeSinceStoppedGlide = 0;

    /** @var Block[] - An array of vector3's which represent the position the User has recently placed. */
    public $placedBlocks = [];
    /** @var Block[] - An array of ghost blocks the client has client-side. */
    public $ghostBlocks = [];

    /** @var int|float - The time the last NetworkStackLatencyPacket has been sent. */
    public $lastSentNetworkLatencyTime = 0;
    /** @var int|float - The time it took for the client to respond with a NetworkStackLatencyPacket. */
    public $transactionLatency = 0;
    /** @var bool - Boolean value for if the user responded with a NetworkStackLatencyPacket. */
    public $responded = false;
    /** @var bool - Boolean value for if the user has (probablly) received and loaded chunks. */
    public $hasReceivedChunks = false;

    /** @var Vector3 - Just a Vector3 with it's x, y, and z values at 0 - don't mind me! */
    public $zeroVector;
    /** @var NetworkStackLatencyPacket - Packet responsible for measuring user latency. */
    public $latencyPacket;
    /** @var NetworkStackLatencyPacket - Packet responsible for chunk receiving. */
    public $chunkResponsePacket;

    /**
     * NOTE: I use these values (isSneaking, isSprinting, etc.) because Pocketmine's values will be off by at least one tick, since it has not
     * handled the packets to set these values yet.
     */

    /** @var bool - The boolean value for if the user is sneaking or not. */
    public $isSneaking = false;
    /** @var bool - The boolean value for if the user is sprinting or not. */
    public $isSprinting = false;
    /** @var bool - The boolean value for if the user is gliding or not */
    public $isGliding = false;

    /** @var MoveData - The class that stores the movement data of the user, the MoveProcessor will handle data to be put in here. */
    public $moveData;
    /** @var ClickData - The class that stores the click data of the user, the ClickProcessor will handle data to be put in here. */
    public $clickData;
    /** @var HitData - The class that stores the hit data of the user, the HitProcessor will handle data to be put in here. */
    public $hitData;
    /** @var TickData - The class that stores data updated every server tick. This data includes entity location history. */
    public $tickData;

    public function __construct(Player $player){
        $this->player = $player;
        $this->alertCooldown = ($cooldown = Mockingbird::getInstance()->getConfig()->get('default_alert_delay')) === false ? 2 : $cooldown;
        $this->moveData = new MoveData();
        $this->clickData = new ClickData();
        $this->hitData = new HitData();
        $this->hitData->lastTick = Server::getInstance()->getTick();
        $this->tickData = new TickData();
        $this->moveData->lastOnGroundLocation = $player->asLocation();
        $zeroVector = new Vector3(0, 0, 0);
        $this->moveData->AABB = AABB::fromPosition($zeroVector);
        $this->zeroVector = $zeroVector;
        $this->moveData->moveDelta = $zeroVector;
        $this->moveData->lastMoveDelta = $zeroVector;
        $this->moveData->location = $player->asLocation();
        $this->moveData->lastLocation = $this->moveData->location;
        $this->moveData->lastMotion = $zeroVector;
        $this->moveData->directionVector = $zeroVector;
        $this->inboundProcessor = new InboundPacketProcessor(); $this->outboundProcessor = new OutboundProcessor();
        $this->tickProcessor = new TickProcessor(); $this->testProcessor = new TestProcessor();
        foreach(Mockingbird::getInstance()->availableChecks as $check){
            $this->detections[$check->name] = clone $check;
        }
        $this->latencyPacket = new NetworkStackLatencyPacket();
        $this->latencyPacket->needResponse = true;
        $this->latencyPacket->timestamp = mt_rand(10, 1000000) * 1000;
        $this->chunkResponsePacket = new NetworkStackLatencyPacket();
        $this->chunkResponsePacket->needResponse = true;
        // to ensure that the two timestamps are NOT the same in any case (the chance of it happening is low, but still possible)
        $this->chunkResponsePacket->timestamp = $this->latencyPacket->timestamp + mt_rand(-10000, 10000) * 1000;
    }

    public function sendMessage(string $message) : void{
        if(!$this->loggedIn) return;
        $this->player->sendMessage(TextFormat::BOLD . TextFormat::DARK_GRAY . '[' . TextFormat::RED . 'DEBUG' . TextFormat::DARK_GRAY . ']' . TextFormat::RESET . ' ' . $message);
    }

}