<?php
namespace NethServer\Module\OpenVPNStatus;

/*
 * Copyright (C) 2013 Nethesis S.r.l.
 *
 * This script is part of NethServer.
 *
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Retrieve VPN configuration and status
 *
 * @author Giacomo Sanchietti
 */
class OpenVPNServer extends \Nethgui\Controller\AbstractController
{

    public $sortId = 10;
 
    private $vpn = array();
    
    private function readVPNs() {
         $vpn = array();
         $openvpn = $this->getPlatform()->getDatabase('configuration')->getKey('openvpn'); 
         $vpn['server']['status'] = $openvpn['ServerStatus'];
         $vpn['server']['port'] = $openvpn['UDPPort'];
         $vpn['server']['auth'] = $openvpn['AuthMode'];
         $vpn['server']['mode'] = $openvpn['Mode'];
         if ($openvpn['Mode'] == 'routed') {
             $vpn['server']['range'] = $openvpn['Network'].' / '.$openvpn['Netmask'];
         } else {
             $vpn['server']['range'] = $openvpn['BridgeStartIP'].' - '.$openvpn['BridgeEndIP'];
         }
         return $vpn;
    } 
 
    public function process()
    {
        $this->vpn = $this->readVPNs();
    }
 
    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        if (!$this->vpn) {
            $this->vpn = $this->readVPNs();
        }
        
        $view['server'] = $this->vpn['server'];
    }
}
