<?php

/* 
 * ____    ____ .______   .__   __. .______   .______        ______   .___________. _______   ______ .___________.
 * \   \  /   / |   _  \  |  \ |  | |   _  \  |   _  \      /  __  \  |           ||   ____| /      ||           |
 *  \   \/   /  |  |_)  | |   \|  | |  |_)  | |  |_)  |    |  |  |  | `---|  |----`|  |__   |  ,----'`---|  |----`
 *   \      /   |   ___/  |  . `  | |   ___/  |      /     |  |  |  |     |  |     |   __|  |  |         |  |     
 *    \    /    |  |      |  |\   | |  |      |  |\  \----.|  `--'  |     |  |     |  |____ |  `----.    |  |     
 *     \__/     | _|      |__| \__| | _|      | _| `._____| \______/      |__|     |_______| \______|    |__|     
 *                                                                                                             
 * VPNProtect, is an advanced AntiVPN plugin for PMMP.
 * Copyright (C) 2021 AGTHARN
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AGTHARN\uhc\util;

class AntiVPN
{          
    /**
     * checkAll
     *
     * @param  string $ip
     * @param  array $configs
     * @return array
     */
    public function checkAll(string $ip, array $configs = null): array
    {   
        // This code is originated from VPNProtect.
        if ($configs === null) $configs = $this->getDefaults();
        $APIs = [
            'api1' => 'https://check.getipintel.net/check.php?ip=' . $ip . '&format=json&contact=idonthavetook@outlook.de',
            'api2' => 'https://proxycheck.io/v2/' . $ip . '?key=' . $configs['check2.key'],
            'api3' => 'https://api.iptrooper.net/check/' . $ip,
            'api4' => 'http://api.vpnblocker.net/v2/json/' . $ip . $configs['check4.key'],
            'api5' => 'https://api.ip2proxy.com/?ip=' . $ip . '&format=json&key=' . $configs['check5.key'],
            'api6' => 'https://vpnapi.io/api/' . $ip,
            'api7' => 'https://ipqualityscore.com/api/json/ip/' . $configs['check7.key'] . '/' . $ip . '?strictness=' . $configs['check7.strictness'] . '&allow_public_access_points=true&fast=' . $configs['check7.fast'] . '&lighter_penalties=' . $configs['check7.lighter_penalties'] . '&mobile=' . $configs['check7.mobile'],
            'api8' => 'http://v2.api.iphub.info/ip/' . $ip,
            'api9' => 'https://www.iphunter.info:8082/v1/ip/' . $ip,
            'api10' => 'https://ipinfo.io/' . $ip . '/json?token=' . $configs['check10.key']
        ];
        $apiHeaders = [
            'api8_header' => ['X-Key: ' . $configs['check8.key']],
            'api9_header' => ['X-Key: ' . $configs['check9.key']]
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_AUTOREFERER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 4
        ]);

        foreach ($APIs as $key => $value) {
            $dataLabel = str_replace('api', 'data', $key);

            curl_setopt($curl, CURLOPT_URL, $value);
            if (in_array($key . '_header', $apiHeaders)) {
                curl_setopt_array($curl, [
                    CURLOPT_HTTPHEADER => $apiHeaders[$key . '_header'],
                    CURLOPT_HEADER => true
                ]);
            } else {
                curl_setopt($curl, CURLOPT_HEADER, false);
            }
            ${$dataLabel} = curl_exec($curl);
            ${$dataLabel} = is_bool(${$dataLabel}) ? 'error' : json_decode(${$dataLabel}, true);
        }

        $check1 = isset($data1['result']) ? $data1['result'] : 'error';                                    /* @phpstan-ignore-line */
        $check2 = isset($data2[$ip]['proxy']) ? $data2[$ip]['proxy'] : 'error';                            /* @phpstan-ignore-line */
        $check3 = isset($data3) ? $data3 : 'error';                                                        /* @phpstan-ignore-line */
        $check4 = isset($data4['host-ip']) ? $data4['host-ip'] : 'error';                                  /* @phpstan-ignore-line */
        $check5 = isset($data5['isProxy']) ? $data5['isProxy'] : 'error';                                  /* @phpstan-ignore-line */
        $check6_vpn = isset($data6['security']['vpn']) ? $data6['security']['vpn'] : 'error';              /* @phpstan-ignore-line */
        $check6_proxy = isset($data6['security']['proxy']) ? $data6['security']['proxy'] : 'error';        /* @phpstan-ignore-line */
        $check6_tor = isset($data6['security']['tor']) ? $data6['security']['tor'] : 'error';              /* @phpstan-ignore-line */
        $check7_vpn = isset($data7['vpn']) ? $data7['vpn'] : 'error';                                      /* @phpstan-ignore-line */
        $check7_proxy = isset($data7['proxy']) ? $data7['proxy'] : 'error';                                /* @phpstan-ignore-line */
        $check7_tor = isset($data7['tor']) ? $data7['tor'] : 'error';                                      /* @phpstan-ignore-line */
        $check8 = isset($data8['block']) ? $data8['block'] : 'error';                                      /* @phpstan-ignore-line */
        $check9 = isset($data9['data']['block']) ? $data9['data']['block'] : 'error';                      /* @phpstan-ignore-line */
        $check10_vpn = isset($data10['privacy']['vpn']) ? $data10['privacy']['vpn'] : 'error';             /* @phpstan-ignore-line */
        $check10_proxy = isset($data10['privacy']['proxy']) ? $data10['privacy']['proxy'] : 'error';       /* @phpstan-ignore-line */
        $check10_tor = isset($data10['privacy']['tor']) ? $data10['privacy']['tor'] : 'error';             /* @phpstan-ignore-line */
        $check10_hosting = isset($data10['privacy']['hosting']) ? $data10['privacy']['hosting'] : 'error'; /* @phpstan-ignore-line */

        curl_close($curl);
        return [
            'check1' => $check1 === 'error' ? 'error' : ($check1 > 0.98 ? true : false),
            'check2' => $check2 === 'error' ? 'error' : ($check2 === 'yes' ? true : false),
            'check3' => $check3 === 'error' ? 'error' : ($check3 === 1 ? true : false),                    /* @phpstan-ignore-line */
            'check4' => $check4 === 'error' ? 'error' : ($check4 === true ? true : false),
            'check5' => $check5 === 'error' ? 'error' : ($check5 === 'YES' ? true : false),
            'check6.vpn' => $check6_vpn === 'error' ? 'error' : ($check6_vpn === true ? true : false),
            'check6.proxy' => $check6_proxy === 'error' ? 'error' : ($check6_proxy === true ? true : false),
            'check6.tor' => $check6_tor === 'error' ? 'error' : ($check6_tor === true ? true : false),
            'check7.vpn' => $check7_vpn === 'error' ? 'error' : ($check7_vpn === true ? true : false),
            'check7.proxy' => $check7_proxy === 'error' ? 'error' : ($check7_proxy === true ? true : false),
            'check7.tor' => $check7_tor === 'error' ? 'error' : ($check7_tor === true ? true : false),
            'check8' => $check8 === 'error' ? 'error' : ($check8 === 1 ? true : false),
            'check9' => $check9 === 'error' ? 'error' : ($check9 === 1 ? true : false),
            'check10.vpn' => $check10_vpn === 'error' ? 'error' : ($check10_vpn === true ? true : false),
            'check10.proxy' => $check10_proxy === 'error' ? 'error' : ($check10_proxy === true ? true : false),
            'check10.tor' => $check10_tor === 'error' ? 'error' : ($check10_tor === true ? true : false),
            'check10.hosting' => $check10_hosting === 'error' ? 'error' : ($check10_hosting === true ? true : false)
        ];
    }
    
    /**
     * getDefaults
     *
     * @return array
     */
    public function getDefaults(): array
    {
        return [
            'check2.key' => '',
            'check4.key' => '',
            'check5.key' => 'demo',
            'check7.key' => 'demo',
            'check7.mobile' => true,
            'check7.fast' => false,
            'check7.strictness' => 0,
            'check7.lighter_penalties' => true,
            'check8.key' => '',
            'check9.key' => '',
            'check10.key' => ''
        ];
    }
}