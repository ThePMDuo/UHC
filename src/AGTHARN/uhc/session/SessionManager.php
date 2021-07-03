<?php
declare(strict_types=1);

namespace AGTHARN\uhc\session;

use AGTHARN\uhc\session\type\PlayerSession;
use pocketmine\Player;

class SessionManager
{
    /** @var PlayerSession[] */
    private array $activeSessions = [];
    
    /**
     * createSession
     *
     * @param  Player $player
     * @return PlayerSession
     */
    public function createSession(Player $player): PlayerSession
    {
        if (!$this->hasSession($player)) {
            $this->activeSessions[$player->getUniqueId()->toString()] = new PlayerSession($player);
        } else {
            $this->getSession($player)->update($player);
        }
        return $this->activeSessions[$player->getUniqueId()->toString()];
    }
    
    /**
     * removeSession
     *
     * @param  Player $player
     * @return void
     */
    public function removeSession(Player $player): void
    {
        if ($this->hasSession($player)) {
            unset($this->activeSessions[$player->getUniqueId()->toString()]);
        }
    }
    
    /**
     * hasSession
     *
     * @param  Player $player
     * @return bool
     */
    public function hasSession(Player $player): bool
    {
        return isset($this->activeSessions[$player->getUniqueId()->toString()]);
    }
    
    /**
     * getSessions
     *
     * @return array
     */
    public function getSessions(): array
    {
        return $this->activeSessions;
    }
    
    /**
     * getSession
     *
     * @param  Player $player
     * @return PlayerSession
     */
    public function getSession(Player $player): ?PlayerSession
    {
        return $this->hasSession($player) ? $this->activeSessions[$player->getUniqueId()->toString()] : null;
    }
    
    /**
     * getPlaying
     *
     * @return array
     */
    public function getPlaying(): array
    {
        $playing = [];
        foreach ($this->getSessions() as $session) {
            if ($session->isPlaying() && $session->getPlayer()->isOnline()) {
                $playing[] = $session;
            }
        }
        return $playing;
    }
}