<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use AGTHARN\uhc\Main;

use AGTHARN\uhc\libs\CortexPE\DiscordWebhookAPI\Message;
use AGTHARN\uhc\libs\CortexPE\DiscordWebhookAPI\Webhook;
use AGTHARN\uhc\libs\CortexPE\DiscordWebhookAPI\Embed;

class Discord
{    
    /** @var Main */
    private $plugin;

    /**
     * __construct
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }
    
    /**
     * sendReport
     *
     * @param  mixed $reporter
     * @param  mixed $reported
     * @param  mixed $reportType
     * @param  mixed $reason
     * @return void
     */
    public function sendReport($reporter, $reported, $reportType, $reason): void
    {
        $webHook = new Webhook($this->plugin->reportWebhook);

        $msg = new Message();
        $msg->setUsername('JAX REPORTS');
        $msg->setAvatarURL('https://www.yanacomoxvalley.com/wp-content/uploads/2016/11/774408_orig.png');

        $embed = new Embed();
        $embed->setTitle('REPORT FROM ' . $reporter . ' (' . $this->plugin->uhcServer . ')');
        $embed->addField('REPORTED:', $reported, true);
        $embed->addField('REPORT TYPE:', $reportType, true);
        $embed->addField('REASON:', $reason, true);
        $embed->setFooter(date('Y-m-d') . 'T' . date('H:i:s') . '.' . date('v') . 'Z');
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
        $webHook = new Webhook($this->plugin->serverReportsWebhook);

        $msg = new Message();
        $msg->setUsername('JAX REPORTS');
        $msg->setAvatarURL('https://www.yanacomoxvalley.com/wp-content/uploads/2016/11/774408_orig.png');

        $embed = new Embed();
        $embed->setTitle('DETECTED POSSIBLE PIRACY (' . $spoonName . ')');
        $embed->addField('SERVER VERSION:', $serverVersion, true);
        $embed->addField('SPOON VERSION:', $spoonVersion, true);
        $embed->addField('IP & PORT:', $ip . ':' . $port, true);
        $embed->setFooter(date('Y-m-d') . 'T' . date('H:i:s') . '.' . date('v') . 'Z');
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
        $webHook = new Webhook($this->plugin->serverPowerWebhook);

        $msg = new Message();
        $msg->setUsername('JAX REPORTS');
        $msg->setAvatarURL('https://www.yanacomoxvalley.com/wp-content/uploads/2016/11/774408_orig.png');

        $embed = new Embed();
        $embed->setTitle('SERVER START-UP (UHC-' . $uhcServer . ')');
        $embed->addField('SERVER VERSION:', $serverVersion, true);
        $embed->addField('UHC BUILD:', $buildNumber, true);
        $embed->addField('NODE:', $node, true);
        $embed->setFooter(date('Y-m-d') . 'T' . date('H:i:s') . '.' . date('v') . 'Z');
        $embed->setColor(0x33FF33);
        $msg->addEmbed($embed);

        $webHook->send($msg);
    }
}
