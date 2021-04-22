<?php

declare(strict_types = 1);

namespace AGTHARN\uhc\libs\jojoe77777\FormAPI;

use pocketmine\form\Form as IForm;
use pocketmine\Player;

abstract class Form implements IForm
{

    /** @var array */
    protected $data = [];
    /** @var callable|null */
    private $callable;

    /**
     * __construct
     * 
     * @param callable|null $callable
     * @return void
     */
    public function __construct(?callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * sendToPlayer
     * 
     * @deprecated
     * @see Player::sendForm()
     *
     * @param Player $player
     * @return void
     */
    public function sendToPlayer(Player $player): void
    {
        $player->sendForm($this);
    }
    
    /**
     * getCallable
     *
     * @return callable|null
     */
    public function getCallable(): ?callable 
    {
        return $this->callable;
    }
    
    /**
     * setCallable
     *
     * @param  callable|null $callable
     * @return void
     */
    public function setCallable(?callable $callable): void
    {
        $this->callable = $callable;
    }
    
    /**
     * handleResponse
     *
     * @param  Player $player
     * @param  mixed $data
     * @return void
     */
    public function handleResponse(Player $player, $data): void
    {
        $this->processData($data);
        $callable = $this->getCallable();
        if($callable !== null) {
            $callable($player, $data);
        }
    }
    
    /**
     * processData
     *
     * @param  mixed $data
     * @return void
     */
    public function processData(&$data): void
    {
        // whats this for idk
    }
    
    /**
     * jsonSerialize
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->data;
    }
}
