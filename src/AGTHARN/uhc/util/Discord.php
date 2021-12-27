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
namespace AGTHARN\uhc\util;

use AGTHARN\uhc\game\GameProperties;
use AGTHARN\uhc\Main;

use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Webhook;
use CortexPE\DiscordWebhookAPI\Embed;

class Discord
{    
    /** @var Main */
    private Main $plugin;
    
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

        $this->gameProperties = $plugin->getClass('GameProperties');
    }
    
    /**
     * sendReport
     *
     * @param  string $reporter
     * @param  string $reported
     * @param  string $reportType
     * @param  string $reason
     * @return void
     */
    public function sendReport(string $reporter, string $reported, string $reportType, string $reason): void
    {
        $webHook = new Webhook($this->gameProperties->reportWebhook);

        $msg = new Message();
        $msg->setUsername('JAX REPORTS');
        $msg->setAvatarURL('https://www.yanacomoxvalley.com/wp-content/uploads/2016/11/774408_orig.png');

        $embed = new Embed();
        $embed->setTitle('REPORT FROM ' . $reporter . ' (' . $this->gameProperties->uhcServer . ')');
        $embed->addField('REPORTED:', $reported, true);
        $embed->addField('REPORT TYPE:', $reportType, true);
        $embed->addField('REASON:', $reason, true);
        $embed->setFooter(date("F j, Y, g:i a"));
        $embed->setColor(0xFF3333);
        $msg->addEmbed($embed);

        $webHook->send($msg);
    }
    
    /**
     * sendSpoonReport
     *
     * @param  string $serverVersion
     * @param  string $spoonVersion
     * @param  string $spoonName
     * @param  string $ip
     * @param  int $port
     * @return void
     */
    public function sendSpoonReport(string $serverVersion, string $spoonVersion, string $spoonName, string $ip, int $port): void
    {
        $webHook = new Webhook($this->gameProperties->serverReportsWebhook);

        $msg = new Message();
        $msg->setUsername('JAX REPORTS');
        $msg->setAvatarURL('https://www.yanacomoxvalley.com/wp-content/uploads/2016/11/774408_orig.png');

        $embed = new Embed();
        $embed->setTitle('DETECTED POSSIBLE PIRACY (' . $spoonName . ')');
        $embed->addField('SERVER VERSION:', $serverVersion, true);
        $embed->addField('SPOON VERSION (if any):', $spoonVersion, true);
        $embed->addField('IP & PORT:', $ip . ':' . $port, true);
        $embed->setFooter(date("F j, Y, g:i a"));
        $embed->setColor(0xFF3333);
        $msg->addEmbed($embed);

        $webHook->send($msg);
    }
    
    /**
     * sendStartReport
     *
     * @param  string $serverVersion
     * @param  string $buildNumber
     * @param  string $node
     * @param  string $uhcServer
     * @return void
     */
    public function sendStartReport(string $serverVersion, string $buildNumber, string $node, string $uhcServer): void
    {
        $webHook = new Webhook($this->gameProperties->serverPowerWebhook);

        $msg = new Message();
        $msg->setUsername('JAX REPORTS');
        $msg->setAvatarURL('https://www.yanacomoxvalley.com/wp-content/uploads/2016/11/774408_orig.png');

        $embed = new Embed();
        $embed->setTitle('SERVER START-UP (UHC-' . $uhcServer . ')');
        $embed->addField('SERVER VERSION:', $serverVersion, true);
        $embed->addField('UHC BUILD:', $buildNumber, true);
        $embed->addField('NODE:', $node, true);
        $embed->setFooter(date("F j, Y, g:i a"));
        $embed->setColor(0x33FF33);
        $msg->addEmbed($embed);

        $webHook->send($msg);
    }
}
