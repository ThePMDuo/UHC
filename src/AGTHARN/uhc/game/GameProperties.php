<?php

declare(strict_types=1);

namespace AGTHARN\uhc\game;

use AGTHARN\uhc\game\timer\GameTimer;
use AGTHARN\uhc\event\phase\PhaseChangeEvent;

class GameProperties
{   
    /**
     * 
     * █▀█ █▀█ █▀▀ █▀▀ █ ▀▄▀ █▀▀ █▀
     * █▀▀ █▀▄ ██▄ █▀░ █ █░█ ██▄ ▄█
     *
     */

    /** @var string */
    public const PREFIX_JAX = '§aJAX §7»» ';
    /** @var string */
    public const PREFIX_COSMIC = '§6COSMIC §7»» ';
    /** @var string */
    public const PREFIX_STEVEAC = '§cSteveAC §7»» ';
    /** @var string */
    public const PREFIX_FABIO = '§gFABIO §7»» ';

    /**
     * 
     * █▀▀ ▄▀█ █▀▄▀█ █▀▀
     * █▄█ █▀█ █░▀░█ ██▄
     * 
     */

    /** @var int */
    public $game = 0;

    /** @var int */
    public $phase = PhaseChangeEvent::WAITING;
    /** @var int */
    public $countdown = GameTimer::TIMER_COUNTDOWN;
    /** @var int */
    public $grace = GameTimer::TIMER_GRACE;
    /** @var int */
    public $pvp = GameTimer::TIMER_PVP;
    /** @var int */
    public $deathmatch = GameTimer::TIMER_DEATHMATCH;
    /** @var int */
    public $winner = GameTimer::TIMER_WINNER;
    /** @var int */
    public $reset = GameTimer::TIMER_RESET;

    /** @var int */
    public $playersJoiningTime = 0;

    /** @var int */
    public $startingPlayers = 0;
    /** @var int */
    public $startingTeams = 0;

    /** @var array */
    public $winnerNames = [];
        
    /** @var bool */
    public $shrinking = false;
    /** @var bool */
    public $globalMuteEnabled = false;

    /** @var array */
    public $waterdogIPs = [];

    /** @var int */
    public $normalSeed;
    /** @var int */
    public $netherSeed;

    /**
     * 
     * █▀ █▀▀ ▀█▀ ▀█▀ █ █▄░█ █▀▀ █▀
     * ▄█ ██▄ ░█░ ░█░ █ █░▀█ █▄█ ▄█
     * 
     */

    /** @var string */
    public $uhcServer = 'GAME-1';
    /** @var string */
    public $node = 'NYC-01';
    /** @var string */
    public $buildNumber = 'BETA-1';
    /** @var bool */
    public $operational = true;

    /** @var string */
    public $reportWebhook = '';
    /** @var string */
    public $serverReportsWebhook = '';
    /** @var string */
    public $serverPowerWebhook = '';

    /** @var string */
    public $map = 'UHC';
    /** @var string */
    public $nether = 'nether';
    
    /** @var int */
    public $spawnPosX = 0;
    /** @var int */
    public $spawnPosY = 150;
    /** @var int */
    public $spawnPosZ = 0;

    /** @var int */
    public const MIN_PLAYERS = 2;

    /**
     * 
     * 
     * █▀█ █▀▀ █▀ █▀▀ ▀█▀   █▀ ▀█▀ ▄▀█ ▀█▀ █░█ █▀
     * █▀▄ ██▄ ▄█ ██▄ ░█░   ▄█ ░█░ █▀█ ░█░ █▄█ ▄█
     *
     */

    /** @var bool */
    public $entitiesReset = false;
    /** @var bool */
    public $worldReset = false;
    /** @var bool */
    public $timerReset = false;
    /** @var bool */
    public $teamReset = false;
    /** @var bool */
    public $othersReset = false;

    /**
     * 
     * ▄▀█ █▄░█ ▀█▀ █ ▄▀█ █▀▀ █▄▀  / █▀█ █░░ ▄▀█ █▄█ █▀▀ █▀█ █▀
     * █▀█ █░▀█ ░█░ █ █▀█ █▀░ █░█ /  █▀▀ █▄▄ █▀█ ░█░ ██▄ █▀▄ ▄█
     *
     */

    /** @var array */
    public $allPlayers = [];
    /** @var array */
    public $allPlayersForm = [];
    
    /** @var array */
    public $allCapes = [];

    /**
     * 
     * 
     * █▀█ █▀▀ █▀█ █▀█ █▀█ ▀█▀ █▀
     * █▀▄ ██▄ █▀▀ █▄█ █▀▄ ░█░ ▄█
     *
     */

    /** @var array */
    public $reportTypes = ['Bug', 'Exploits', 'Disrespectful', 'Inappropriate', 'Griefing/Stealing', 'Impersonation', 'Unobtainable Items', 'Advertising', 'Spamming'];
}