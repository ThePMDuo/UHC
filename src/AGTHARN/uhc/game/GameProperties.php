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
    public int $game = 0;

    /** @var int */
    public int $phase = PhaseChangeEvent::WAITING;
    /** @var int */
    public int $countdown = GameTimer::TIMER_COUNTDOWN;
    /** @var int */
    public int $grace = GameTimer::TIMER_GRACE;
    /** @var int */
    public int $pvp = GameTimer::TIMER_PVP;
    /** @var int */
    public int $deathmatch = GameTimer::TIMER_DEATHMATCH;
    /** @var int */
    public int $winner = GameTimer::TIMER_WINNER;
    /** @var int */
    public int $reset = GameTimer::TIMER_RESET;

    /** @var int */
    public int $playersJoiningTime = 0;

    /** @var int */
    public int $startingPlayers = 0;
    /** @var int */
    public int $startingTeams = 0;

    /** @var array */
    public array $winnerNames = [];
        
    /** @var bool */
    public bool $shrinking = false;
    /** @var bool */
    public bool $globalMuteEnabled = false;

    /** @var array */
    public array $waterdogIPs = [];

    /** @var int */
    public int $normalSeed;
    /** @var int */
    public int $netherSeed;

    /**
     * 
     * █▀ █▀▀ ▀█▀ ▀█▀ █ █▄░█ █▀▀ █▀
     * ▄█ ██▄ ░█░ ░█░ █ █░▀█ █▄█ ▄█
     * 
     */

    /** @var string */
    public string $uhcServer = 'GAME-1';
    /** @var string */
    public string $node = 'NYC-01';
    /** @var string */
    public string $buildNumber = 'BETA-1';
    /** @var bool */
    public bool $operational = true;

    /** @var string */
    public string $reportWebhook = '';
    /** @var string */
    public string $serverReportsWebhook = '';
    /** @var string */
    public string $serverPowerWebhook = '';

    /** @var string */
    public string $map = 'UHC';
    /** @var string */
    public string $nether = 'nether';
    
    /** @var int */
    public int $spawnPosX = 0;
    /** @var int */
    public int $spawnPosY = 150;
    /** @var int */
    public int $spawnPosZ = 0;

    /** @var int */
    public const MIN_PLAYERS = 2;

    /** @var array */
    public array $allVirions = [];
    /** @var array */
    public array $allPlugins = [];

    /**
     * 
     * 
     * █▀█ █▀▀ █▀ █▀▀ ▀█▀   █▀ ▀█▀ ▄▀█ ▀█▀ █░█ █▀
     * █▀▄ ██▄ ▄█ ██▄ ░█░   ▄█ ░█░ █▀█ ░█░ █▄█ ▄█
     *
     */

    /** @var bool */
    public bool $hasUpdate = false;

    /** @var bool */
    public bool $entitiesReset = false;
    /** @var bool */
    public bool $worldReset = false;
    /** @var bool */
    public bool $timerReset = false;
    /** @var bool */
    public bool $teamReset = false;
    /** @var bool */
    public bool $othersReset = false;

    /**
     * 
     * ▄▀█ █▄░█ ▀█▀ █ ▄▀█ █▀▀ █▄▀  / █▀█ █░░ ▄▀█ █▄█ █▀▀ █▀█ █▀
     * █▀█ █░▀█ ░█░ █ █▀█ █▀░ █░█ /  █▀▀ █▄▄ █▀█ ░█░ ██▄ █▀▄ ▄█
     *
     */

    /** @var array */
    public array $allPlayers = [];
    /** @var array */
    public array $allPlayersForm = [];
    
    /** @var array */
    public array $allCapes = [];

    /**
     * 
     * 
     * █▀█ █▀▀ █▀█ █▀█ █▀█ ▀█▀ █▀
     * █▀▄ ██▄ █▀▀ █▄█ █▀▄ ░█░ ▄█
     *
     */

    /** @var array */
    public array $reportTypes = [
        'Bug',
        'Exploits',
        'Disrespectful',
        'Inappropriate',
        'Griefing/Stealing',
        'Impersonation',
        'Unobtainable Items',
        'Advertising',
        'Spamming'
    ];
}