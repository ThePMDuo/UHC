<?php
declare(strict_types=1);

namespace AGTHARN\uhc\game\timer;

final class GameTimer
{
    /** @var int */
    public const TIMER_COUNTDOWN = 60;
    /** @var int */
    public const TIMER_GRACE = 60 * 20;
    /** @var int */
    public const TIMER_PVP = 60 * 20;
    /** @var int */
    public const TIMER_DEATHMATCH = 60 * 20;
    /** @var int */
    public const TIMER_WINNER = 60;
    /** @var int */
    public const TIMER_RESET = 60;
}