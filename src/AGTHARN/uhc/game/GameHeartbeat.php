<?php

declare(strict_types=1);

namespace AGTHARN\uhc\game;

use uhc\libs\JackMD\ScoreFactory\ScoreFactory;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\level\sound\ClickSound;
use pocketmine\level\sound\BlazeShootSound;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat as TF;
use uhc\event\PhaseChangeEvent;
use uhc\game\type\GameTimer;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use uhc\Loader;

class GameHeartbeat extends Task
{
	
    /** @var int */
    private $phase = PhaseChangeEvent::WAITING;

    /** @var int */
    private $game = 0;

    /** @var int */
    private $countdown = GameTimer::TIMER_COUNTDOWN;
    /** @var float|int */
    private $grace = GameTimer::TIMER_GRACE;
    /** @var float|int */
    private $pvp = GameTimer::TIMER_PVP;
    /** @var float|int */
    private $normal = GameTimer::TIMER_NORMAL;
	/** @var int */
    private $winner = GameTimer::TIMER_WINNER;
	/** @var int */
    private $reset = GameTimer::TIMER_RESET;
	
    /** @var Border */
    private $border;
    /** @var Loader */
    private $plugin;

    /** @var int */
    private $playerTimer = 1;
	
	private $shrinking = false;

	/** @var string */
	private $maplevel = "UHC";

    public function __construct(Loader $plugin)
    {
        $this->plugin = $plugin;
        //$this->level = $this->plugin->getServer()->getLevelByName($maplevel);
        $this->border = new Border($plugin->getServer()->getDefaultLevel());
    }

    public function getPhase(): int
    {
        return $this->phase;
    }

    public function setPhase(int $phase): void
    {
        $this->phase = $phase;
    }
    
    public function setGameTimer($time)
    {
        $this->game = $time;
    }
	
	public function setCountdownTimer($time)
    {
        $this->countdown = $time;
    }
	
	public function setGraceTimer($time)
    {
        $this->grace = $time;
    }
	
	public function setPVPTimer($time)
    {
        $this->pvp = $time;
    }
	
	public function setNormalTimer($time)
    {
        $this->normal = $time;
    }
	
	public function setWinnerTimer($time)
    {
        $this->winner = $time;
    }
	
	public function setResetTimer($time)
    {
        $this->reset = $time;
    }

    public function hasStarted(): bool
    {
        return $this->getPhase() >= PhaseChangeEvent::GRACE;
    }
	
	public function setShrinking(bool $shrinking)
	{
		$this->shrinking = $shrinking;
	}

	public function setMap(string $maplevel)
	{
		$this->maplevel = $maplevel;
	}

	public function getMap(): string
    {
        return $this->maplevel;
    }

    public function onRun(int $currentTick): void
    {
		$server = $this->plugin->getServer();
        $this->handlePlayers();
		
        switch ($this->getPhase()) {
			case PhaseChangeEvent::WAITING:
                $this->handleWaiting();
                break;
            case PhaseChangeEvent::COUNTDOWN:
                $this->handleCountdown();
                break;
            case PhaseChangeEvent::GRACE:
                $this->handleGrace();
                break;
            case PhaseChangeEvent::PVP:
                $this->handlePvP();
                break;
            case PhaseChangeEvent::NORMAL:
                $this->handleNormal();
                break;
			case PhaseChangeEvent::WINNER:
                $this->handleWinner();
                break;
			case PhaseChangeEvent::RESET:
                $this->handleReset();
                break;
        }
        if ($this->hasStarted(true) && $this->phase !== PhaseChangeEvent::WINNER) $this->game++;
		
		$server->getLevelByName($this->maplevel)->setTime(1000);
		
		foreach ($server->getOnlinePlayers() as $player) {
			$playerx = $player->getX();
			$playery = $player->getY();
			$playerz = $player->getZ();
			
			if (!$player->hasEffect(16)) {
				$player->addEffect(new EffectInstance(Effect::getEffect(16), 1000000, 1, false));
			}
			
			if ($playerx >= $this->border->getSize() || -$playerx >= $this->border->getSize() || $playery >= $this->border->getSize() || $playerz >= $this->border->getSize() || -$playerz >= $this->border->getSize()) {
				if ($this->phase === PhaseChangeEvent::WAITING || $this->phase === PhaseChangeEvent::COUNTDOWN) {
					$x = 265;
					$y = 70;
					$z = 265;
					$level = $server->getLevelByName($this->maplevel);
					
					$player->teleport(new Position($x, $y, $z, $level));
				} else {
				$player->addEffect(new EffectInstance(Effect::getEffect(19), 60, 1, false));
				if ($player->getHealth() <= 2) {
					$player->addEffect(new EffectInstance(Effect::getEffect(7), 100, 1, false));
				}
				}
			}
			if ($playerx >= $this->border->getSize() - 20 || -$playerx >= $this->border->getSize() - 20 || $playery >= $this->border->getSize() - 20 || $playerz >= $this->border->getSize() - 20 || -$playerz >= $this->border->getSize() - 20) {
				$player->sendPopup(TF::RED . "BORDER IS CLOSE!");
			}
			
			if($player->getLevel()->getName() == $server->getLevelByName($this->maplevel)){
				$x = $player->getX();
				$y = $player->getY();
				$z = $player->getZ();
				$level = $server->getLevelByName($this->maplevel);
				$player->teleport(new Position($x, $y, $z, $level));
			}
			
			if($player->getGamemode() === 3) {
				$inventory = $player->getInventory();
				//if ($player->getInventory()->getItemInHand()->getId() === 355 && $player->getInventory()->getItemInHand()->hasEnchantment(17)) {
						//$player->sendPopup("§aReturn To Hub");
						//return;
				//}
				$item = Item::get(Item::BED, 0, 1)->setCustomName("§aReturn To Hub");
				$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
				$player->getInventory()->setItem(8, $item);
				
				$item2 = Item::get(35, 14, 1)->setCustomName("§cReport");
				$item2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
				$player->getInventory()->setItem(0, $item2);
			}
		}
		
		if (count($this->plugin->getGamePlayers()) <= 1) {
			if ($this->phase === PhaseChangeEvent::WAITING) return;
			if ($this->phase === PhaseChangeEvent::COUNTDOWN) return;
			if ($this->phase === PhaseChangeEvent::WINNER) return;
			if ($this->phase === PhaseChangeEvent::RESET) return;
			
			$this->border->setSize(500);
			$this->setPhase(PhaseChangeEvent::WINNER);
		}
		
		if ($this->shrinking == true) {
			if ($this->pvp >= 801 and $this->pvp <= 900 and $this->getPhase() === PhaseChangeEvent::PVP) {
				$this->border->setSize($this->border->getSize() - 1);
			}
			if ($this->pvp >= 501 and $this->pvp <= 600 and $this->getPhase() === PhaseChangeEvent::PVP) {
				$this->border->setSize($this->border->getSize() - 1);
			}
			if ($this->pvp >= 201 and $this->pvp <= 300 and $this->getPhase() === PhaseChangeEvent::PVP) {
				$this->border->setSize($this->border->getSize() - 1);
			}
			if ($this->normal >= 1101 and $this->getPhase() === PhaseChangeEvent::NORMAL) {
				$this->border->setSize($this->border->getSize() - 1);
			}
			if ($this->normal >= 651 and $this->normal <= 700 and $this->getPhase() === PhaseChangeEvent::NORMAL) {
				$this->border->setSize($this->border->getSize() - 1);
			}
			if ($this->normal >= 361 and $this->normal <= 400 and $this->getPhase() === PhaseChangeEvent::NORMAL) {
				$this->border->setSize($this->border->getSize() - 1);
			}
			if ($this->normal >= 291 and $this->normal <= 300 and $this->getPhase() === PhaseChangeEvent::NORMAL) {
				$this->border->setSize($this->border->getSize() - 1);
			}
		}
		$this->plugin->getConfig()->set("border-size", $this->border->getSize());
		$this->plugin->getConfig()->save();
		
		$server->getLevelByName($this->maplevel)->setAutoSave(false);
		$server->getLevelByName("nether")->setAutoSave(false);
		
		//for query in gameshub
		if ($this->phase !== PhaseChangeEvent::WAITING) {
			//$server->setConfigString("gamemode", "3");
		}
    }

    private function handlePlayers(): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
            if ($p->isSurvival()) {
                $this->plugin->addToGame($p);
            } else {
                $this->plugin->removeFromGame($p);
            }
            $this->handleScoreboard($p);
        }

        foreach ($this->plugin->getGamePlayers() as $player) {
            switch ($this->getPhase()) {
                case PhaseChangeEvent::COUNTDOWN:
                    $player->setFood($player->getMaxFood());
                    $player->setHealth($player->getMaxHealth());
                    if ($this->countdown === 29) {
                        $this->randomizeCoordinates(-250, 250, 180, 200, -250, 250);
                        //$player->setWhitelisted(true);
                        $player->removeAllEffects();
                        $player->getInventory()->clearAll();
                        $player->getArmorInventory()->clearAll();
                        $player->getCursorInventory()->clearAll();
                        $player->setImmobile(true);
                    } elseif ($this->countdown === 0) {
                        $player->setImmobile(false);
                    }
                    break;
                case PhaseChangeEvent::GRACE:
                    if ($this->grace === 601) {
                        $player->setHealth($player->getMaxHealth());
                    }
                    break;
            }
        }
    }
	
	private function handleWaiting(): void
    {
		$server = $this->plugin->getServer();
		$this->border->setSize(500);
		$this->hasStarted(false);
		
		//$server->setConfigString("gamemode", "0");
		$server->getNetwork()->setName("NOT STARTED");
		
		$playerstartcount = 2 - count($server->getOnlinePlayers());
		
		if(count($server->getOnlinePlayers()) >= 2){
				$this->setPhase(PhaseChangeEvent::COUNTDOWN);
		}
		
		foreach ($this->plugin->getGamePlayers() as $player) {
			//$server->setConfigBool("white-list", false);
			$player->setFood($player->getMaxFood());
            $player->setHealth($player->getMaxHealth());
			$player->setImmobile(false);
			//$player->removeAllEffects();
			//$player->getInventory()->clearAll();
			//$player->getArmorInventory()->clearAll();
			//$player->getCursorInventory()->clearAll();
			$this->handleScoreboard($player);
			}
			foreach ($server->getOnlinePlayers() as $p) {
				$inventory = $p->getInventory();
				
				$this->plugin->removeFromGame($p);
				$p->setGamemode(Player::SURVIVAL);
				if(count($server->getOnlinePlayers()) <= "2"){
					if ($p->getInventory()->getItemInHand()->getId() === 355 && $p->getInventory()->getItemInHand()->hasEnchantment(17) && $this->getPhase() === PhaseChangeEvent::WAITING) {
						$p->sendPopup("§aReturn To Hub");
						return;
					}elseif ($p->getInventory()->getItemInHand()->getId() === 35 && $p->getInventory()->getItemInHand()->hasEnchantment(17) && $this->getPhase() === PhaseChangeEvent::WAITING) {
						$p->sendPopup("§cReport");
						return;
					}
					$p->sendPopup(TF::RED . $playerstartcount . " more players required...");
				}
				$item = Item::get(Item::BED, 0, 1)->setCustomName("§aReturn To Hub");
				$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
				$p->getInventory()->setItem(8, $item);
				
				$item2 = Item::get(35, 14, 1)->setCustomName("§aReport");
				$item2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
				$p->getInventory()->setItem(0, $item2);
			}
	}

    private function handleCountdown(): void
    {
        $server = $this->plugin->getServer();
        switch ($this->countdown) {
			case 60:
			//for uhcreset so it can happen for second time
			$this->setResetTimer(3);
			//double check
			$this->setGameTimer(0);
                foreach ($server->getOnlinePlayers() as $player) {
                	//players will see effect refresh
			$player->removeAllEffects();
			$player->getInventory()->clearAll();
			$player->getArmorInventory()->clearAll();
			$player->getCursorInventory()->clearAll();
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Game starting in " . TF::AQUA . "60 seconds!");
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "All players will be teleported in " . TF::AQUA . "30 seconds!");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
			case 45:
                foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "All players will be teleported in " . TF::AQUA . "15 seconds!");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 30:
				$this->border->setSize(500);
				$server->getNetwork()->setName("STARTED");
				foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The game will begin in " . TF::AQUA . "30 seconds.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 10:
			foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The game will begin in " . TF::AQUA . "10 seconds.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 3:
			foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The game will begin in " . TF::AQUA . "3 seconds.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 2:
			foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The game will begin in " . TF::AQUA . "2 seconds.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 1:
			foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The game will begin in " . TF::AQUA . "1 second.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 0:
                foreach ($this->plugin->getServer()->getDefaultLevel()->getEntities() as $entity) {
                    if (!$entity instanceof Player) {
                        $entity->flagForDespawn();
                    }
                }

                foreach ($this->plugin->getGamePlayers() as $playerSession) {
                    $ev = new PhaseChangeEvent($playerSession, PhaseChangeEvent::COUNTDOWN, PhaseChangeEvent::GRACE);
                    $ev->call();
                }
				foreach ($server->getOnlinePlayers() as $player) {
				$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . TF::BOLD . "The match has begun!");
				$player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
				//NOTE PLS SET TO GRACE CUZ OF TESTING
                $this->setPhase(PhaseChangeEvent::GRACE);
                break;
        }
        $this->countdown--;
    }

    private function handleGrace(): void
    {
        $server = $this->plugin->getServer();
        switch ($this->grace) {
            case 1190:
			foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Final heal in " . TF::AQUA . "10 minutes.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 601:
			foreach ($server->getOnlinePlayers() as $player) {
				$player->setHealth($player->getMaxHealth());
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Final heal has " . TF::AQUA . "occurred!");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 600:
			foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PVP will enable in 10 minutes.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 300:
			foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PVP will enable in 5 minutes.");
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border will start shrinking to " . TF::AQUA . "400" . TF::WHITE . " in " . TF::AQUA . "10 minutes.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 60:
			foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PVP will enable in 1 minute.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 30:
			foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PVP will enable in 30 seconds.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 10:
			foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PVP will enable in 10 seconds.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 3:
			foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PvP will be enabled in 3 seconds.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 2:
			foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PvP will be enabled in 2 seconds.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 1:
			foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PvP will be enabled in 1 second.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 0:
                foreach ($this->plugin->getGamePlayers() as $playerSession) {
                    $ev = new PhaseChangeEvent($playerSession, PhaseChangeEvent::GRACE, PhaseChangeEvent::PVP);
                    $ev->call();
                }
				foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "PvP has been enabled!");
					$player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                $this->setPhase(PhaseChangeEvent::PVP);
                break;
        }
        $this->grace--;
    }

    private function handlePvP(): void
    {
        $server = $this->plugin->getServer();
		$this->setShrinking(true);
        switch ($this->pvp) {
            case 1199:
			foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border will start shrinking to " . TF::AQUA . "400" . TF::WHITE . " in " . TF::AQUA . "5 minutes");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 900:
				foreach ($server->getOnlinePlayers() as $player) {
					//$this->border->setSize(400);
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border is now shrinking to " . TF::AQUA . "400.\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Shrinking to " . TF::AQUA . "300" . TF::WHITE . " in " . TF::AQUA . "5 minutes.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
			case 600:
				foreach ($server->getOnlinePlayers() as $player) {
					//$this->border->setSize(300);
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border is now shrinking to " . TF::AQUA . "300.\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Shrinking to " . TF::AQUA . "200" . TF::WHITE . " in " . TF::AQUA . "5 minutes.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
			case 300:
				foreach ($server->getOnlinePlayers() as $player) {
					//$this->border->setSize(200);
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border is now shrinking to " . TF::AQUA . "200.\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Shrinking to " . TF::AQUA . "100" . TF::WHITE . " in " . TF::AQUA . "5 minutes.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "Deathmatch starts in " . TF::AQUA . "10 minutes" . ".\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "All players would be teleported before the Deathmatch starts.");
				}
                break;
            case 0:
                foreach ($this->plugin->getGamePlayers() as $playerSession) {
                    $ev = new PhaseChangeEvent($playerSession, PhaseChangeEvent::PVP, PhaseChangeEvent::NORMAL);
                    $ev->call();
                }
				foreach ($server->getOnlinePlayers() as $player) {
					//$this->border->setSize(100);
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border is now shrinking to " . TF::AQUA . "100.");
					$player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "Deathmatch starts in " . TF::AQUA . "5 minutes" . ".\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "All players would be teleported before the Deathmatch starts.");
				}
                $this->setPhase(PhaseChangeEvent::NORMAL);
                break;
        }
        $this->pvp--;
    }

    public function handleNormal(): void
    {
        $server = $this->plugin->getServer();
        switch ($this->normal) {
			case 960:
				foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "Deathmatch starts in " . TF::AQUA . "1 minute" . ".\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "All players would be teleported in 30 seconds.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
			case 930:
				foreach ($server->getOnlinePlayers() as $player) {
					$this->randomizeCoordinates(-99, 99, 180, 200, -99, 99);
					$player->setImmobile(true);
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "Deathmatch starts in 30 seconds" . ".\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "All players have been teleported.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 900:
				foreach ($server->getOnlinePlayers() as $player) {
					$player->setImmobile(false);
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Deathmatch has started, " . TF::AQUA . "GOOD LUCK!\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Border is shrinking to " . TF::AQUA . "100" . TF::WHITE . " in " . TF::AQUA . "5 minutes.");
					$player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
            case 700:
				foreach ($server->getOnlinePlayers() as $player) {
					//$this->border->setSize(50);
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border is now shrinking to " . TF::AQUA . "50.\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Shrinking to " . TF::AQUA . "75" . TF::WHITE . " in " . TF::AQUA . "5 minutes.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
			case 400:
				foreach ($server->getOnlinePlayers() as $player) {
					//$this->border->setSize(10);
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border is now shrinking to " . TF::AQUA . "10.\n" . TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Shrinking to " . TF::AQUA . "50" . TF::WHITE . " in " . TF::AQUA . "5 minutes.");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
			case 300:
				foreach ($server->getOnlinePlayers() as $player) {
					//$this->border->setSize(1);
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "The border is now shrinking to " . TF::AQUA . "1.");
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "THE GAME IS ENDING IN 5 MINS!!!");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
                break;
			case 0:
				foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "GAME OVER!");
					$player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
				}
				$this->setPhase(PhaseChangeEvent::WINNER);
                break;
        }
        $this->normal--;
    }
	
	private function handleWinner(): void
    {
		$server = $this->plugin->getServer();
		//$this->hasStarted(false);
		
		switch ($this->winner) {
			case 60:
                foreach ($server->getOnlinePlayers() as $player) {
					$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::GREEN . "Congratulations to the winner!");
					$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
					$player->setFood($player->getMaxFood());
					$player->setHealth($player->getMaxHealth());
					$player->removeAllEffects();
					$player->getInventory()->clearAll();
					$player->getArmorInventory()->clearAll();
					$player->getCursorInventory()->clearAll();
					$player->setImmobile(false);
					$this->handleScoreboard($player);
					}
					foreach ($server->getOnlinePlayers() as $p) {
						$this->plugin->removeFromGame($p);
						$p->teleport($server->getLevelByName($this->maplevel)->getSafeSpawn());
						$p->setGamemode(Player::SURVIVAL);
						}
					$this->setShrinking(false);
				break;
			case 45:
			foreach ($server->getOnlinePlayers() as $player) {
				$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "All players would be sent back to the games hub in " . TF::AQUA . "40 seconds as map resets!");
				$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
			}
                break;
			case 30:
			foreach ($server->getOnlinePlayers() as $player) {
				$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "All players would be sent back to the games hub in " . TF::AQUA . "25 seconds as map resets!");
				$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
			}
                break;
			case 10:
			foreach ($server->getOnlinePlayers() as $player) {
				$player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "All players will be sent back to the games hub in " . TF::AQUA . "5 seconds!");
				$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
			}
                break;
			case 7:
			foreach ($server->getOnlinePlayers() as $player) {
                $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . "Thanks for playing on " . TF::AQUA . "MineWarrior UHC!");
				$player->getLevel()->addSound(new ClickSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
			}
                break;
			case 5:
			foreach ($server->getOnlinePlayers() as $player) {
                $player->sendMessage(TF::GREEN . "JAX " . TF::GRAY . "»» " . TF::RESET . TF::RED . "ALL PLAYERS TELEPORTING!");
				$player->getLevel()->addSound(new BlazeShootSound(new Vector3($player->getX(), $player->getY(), $player->getZ())));
			}
			//so nobody interrupts reset
			$server->setConfigBool("white-list", true);
                break;
			case 4:
                foreach ($server->getOnlinePlayers() as $p) {
					$this->plugin->getServer()->dispatchCommand($p, "transfer hub");
				}
                break;
            case 1:
                foreach ($server->getOnlinePlayers() as $p) {
					$p->kick();
				}
                break;
			case 0:
			$this->setPhase(PhaseChangeEvent::RESET);
				break;
		}
		$this->winner--;
	}
	
	private function handleReset(): void
    {
		$server = $this->plugin->getServer();
		//$this->hasStarted(false);
		
		switch ($this->reset) {
			case 3:
			
			$server->getLogger()->info("Starting reset");
			
			foreach ($server->getLevels() as $level) {
				foreach ($level->getEntities() as $entity) {
					//if ($entity->getSaveId() === "Slapper") return;
					if (!$entity instanceof Player) $entity->close(); 
				}
			}
			
			$server->unloadLevel($server->getLevelByName($this->maplevel));
			$server->loadLevel($this->maplevel);
			
			$server->getLogger()->info("Reset completed");
				break;
			case 2:
			$this->setGameTimer(0);
			$this->setCountdownTimer(60);
			$this->setGraceTimer(60 * 20);
			$this->setPVPTimer(60 * 20);
			$this->setNormalTimer(60 * 20);
			$this->setWinnerTimer(60);
			//$this->setResetTimer(30); //moved to countdown
			
			$server->getLogger()->info("Timers have been reset");
			break;
			case 1:
			$chance = mt_rand(1,1);

			if ($chance === 1) $this->setMap("UHC");
			
			$server->getLogger()->info("Map has been randomized to " . $this->maplevel);
			break;
			case 0:
			$this->setPhase(PhaseChangeEvent::WAITING);
            $server->getLogger()->info("Changed to waiting phase");
            //so nobody interrupts reset
			$server->setConfigBool("white-list", false);
                break;
		}
		$this->reset--;
	}
	
    private function handleScoreboard(Player $p): void
    {
        ScoreFactory::setScore($p, "§7»» §f§eMINEWARRIOR UHC-1 §7««");
        if ($this->hasStarted(true)) {
            ScoreFactory::setScoreLine($p, 1, "§7§l[-------------------]");
            ScoreFactory::setScoreLine($p, 2, " §fGame Time: §a" . gmdate("H:i:s", $this->game));
			if ($this->phase === PhaseChangeEvent::GRACE) {
				if ($this->grace >= 601) {
					ScoreFactory::setScoreLine($p, 3, " §fFinal Heal In: §a" . gmdate("i:s", $this->grace - 601));
				}
			}elseif ($this->phase === PhaseChangeEvent::PVP) {
				if ($this->shrinking == true and $this->border->getSize() >= "499") {
					if ($this->pvp - 900 >= 61) {
					ScoreFactory::setScoreLine($p, 3, " §fBorder Shrinks(400): §a" . gmdate("i:s", $this->pvp - 900));
					}elseif ($this->pvp - 900 <= 60) {
					ScoreFactory::setScoreLine($p, 3, " §fBorder Shrinks(400): §c" . gmdate("i:s", $this->pvp - 900));
					}
				}elseif ($this->shrinking == true and $this->border->getSize() >= "399") {
					if ($this->pvp - 600 >= 61) {
					ScoreFactory::setScoreLine($p, 3, " §fBorder Shrinks(300): §a" . gmdate("i:s", $this->pvp - 600));
					}elseif ($this->pvp - 600 <= 60) {
						ScoreFactory::setScoreLine($p, 3, " §fBorder Shrinks(300): §c" . gmdate("i:s", $this->pvp - 600));
						}
				}elseif ($this->shrinking == true and $this->border->getSize() >= "299") {
					if ($this->pvp - 300 >= 61) {
					ScoreFactory::setScoreLine($p, 3, " §fBorder Shrinks(200): §a" . gmdate("i:s", $this->pvp - 300));
					}elseif ($this->pvp - 300 <= 60) {
						ScoreFactory::setScoreLine($p, 3, " §fBorder Shrinks(200): §c" . gmdate("i:s", $this->pvp - 300));
						}
				}elseif ($this->shrinking == true and $this->border->getSize() >= "199") {
					if ($this->pvp - 0 >= 61) {
					ScoreFactory::setScoreLine($p, 3, " §fBorder Shrinks(100): §a" . gmdate("i:s", $this->pvp - 0));
					}elseif ($this->pvp - 0 <= 60) {
						ScoreFactory::setScoreLine($p, 3, " §fBorder Shrinks(100): §c" . gmdate("i:s", $this->pvp - 0));
						}
				}elseif ($this->shrinking == true and $this->border->getSize() >= "99") {
					if ($this->normal - 700 >= 61) {
					ScoreFactory::setScoreLine($p, 3, " §fBorder Shrinks(50): §a" . gmdate("i:s", $this->normal - 700));
					}elseif ($this->normal - 700 <= 60) {
						ScoreFactory::setScoreLine($p, 3, " §fBorder Shrinks(50): §c" . gmdate("i:s", $this->normal - 700));
						}
				}elseif ($this->shrinking == true and $this->border->getSize() >= "49") {
					if ($this->normal - 400 >= 61) {
					ScoreFactory::setScoreLine($p, 3, " §fBorder Shrinks(10): §a" . gmdate("i:s", $this->normal - 400));
					}elseif ($this->normal - 400 <= 60) {
						ScoreFactory::setScoreLine($p, 3, " §fBorder Shrinks(10): §c" . gmdate("i:s", $this->normal - 400));
						}
				}elseif ($this->shrinking == true and $this->border->getSize() >= "9") {
					if ($this->normal - 300 >= 61) {
					ScoreFactory::setScoreLine($p, 3, " §fBorder Shrinks(1): §a" . gmdate("i:s", $this->normal - 300));
					}elseif ($this->normal - 300 <= 60) {
						ScoreFactory::setScoreLine($p, 3, " §fBorder Shrinks(1): §c" . gmdate("i:s", $this->normal - 300));
						}
				}
			}
			if ($this->phase === PhaseChangeEvent::GRACE) {
				if ($this->grace <= 300) {
				ScoreFactory::setScoreLine($p, 4, " §fPVP Enables In: §c" . gmdate("i:s", $this->grace));
				} else {
					ScoreFactory::setScoreLine($p, 4, " §fPVP Enables In: §a" . gmdate("i:s", $this->grace));
				}
			}elseif ($this->phase === PhaseChangeEvent::NORMAL) {
				if ($this->normal >= 900) {
				ScoreFactory::setScoreLine($p, 4, " §fDeathmatch In: §c" . gmdate("i:s", $this->normal - 900));
				}
			}
			//put the deathmatch time for normal too 5 mins i think
			ScoreFactory::setScoreLine($p, 5, " ");
            ScoreFactory::setScoreLine($p, 6, " §fPlayers: §a" . count($this->plugin->getGamePlayers()) . "§f§7/50");
			ScoreFactory::setScoreLine($p, 7, "  ");
			ScoreFactory::setScoreLine($p, 8, $this->plugin->hasSession($p) !== true ? " §fKills: §a0" : " §fKills: §a" . $this->plugin->getSession($p)->getEliminations());
			ScoreFactory::setScoreLine($p, 9, " §fTPS: §a" . $this->plugin->getServer()->getTicksPerSecond());
			ScoreFactory::setScoreLine($p, 10, "   ");
            ScoreFactory::setScoreLine($p, 11, " §fBorder: §a± " . $this->border->getSize());
            ScoreFactory::setScoreLine($p, 12, " §fCenter: §a0, 0");
			ScoreFactory::setScoreLine($p, 13, "    ");
			ScoreFactory::setScoreLine($p, 14, "§7§l[-------------------] ");
			ScoreFactory::setScoreLine($p, 15, " §eplay.minewarrior.xyz");
        } else {
            ScoreFactory::setScoreLine($p, 1, "§7§l[-------------------]");
            ScoreFactory::setScoreLine($p, 2, " §fPlayers §f");
			ScoreFactory::setScoreLine($p, 3, " §a" . count($this->plugin->getGamePlayers()) . "§f§7/50");
			ScoreFactory::setScoreLine($p, 4, " ");
            ScoreFactory::setScoreLine($p, 5, $this->getPhase() === PhaseChangeEvent::WAITING ? "§7 Waiting for more players..." : "§7 Starting in:§f $this->countdown");
			ScoreFactory::setScoreLine($p, 6, "  ");
			ScoreFactory::setScoreLine($p, 7, "§7§l[-------------------] ");
			ScoreFactory::setScoreLine($p, 8, " §eplay.minewarrior.xyz");
        }
    }

    public function randomizeCoordinates($x1, $x2, $y1, $y2, $z1, $z2): void
    {
        $server = $this->plugin->getServer();
		foreach ($this->plugin->getGamePlayers() as $player) {
			$x = mt_rand($x1, $x2);
			$y = mt_rand($y1, $y2);
			$z = mt_rand($z1, $z2);
			$level = $server->getLevelByName($this->maplevel);
			
			//$player->teleport(new Vector3($x, $y, $z));
			$player->teleport(new Position($x, $y, $z, $level));
		}

            $this->playerTimer += 5;
			$this->playerTimer;
    }
}