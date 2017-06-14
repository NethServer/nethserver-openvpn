<?php
namespace NethServer\Module\OpenVpnTunnels\Servers;

/*
 * Copyright (C) 2012 Nethesis S.r.l.
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

use Nethgui\System\PlatformInterface as Validate;

/**
 * Modify VPN clients (tunnels)
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 */
class Modify extends \Nethgui\Controller\Table\Modify
{
    const TUNNEL_PATH = "/var/lib/nethserver/openvpn-tunnels/";

    public function initialize()
    {
        $ciphers = $this->getParent()->readCiphers();
        $parameterSchema = array(
            array('name', Validate::USERNAME, \Nethgui\Controller\Table\Modify::KEY),
            array('status', Validate::SERVICESTATUS, \Nethgui\Controller\Table\Modify::FIELD),
            array('Port', Validate::PORTNUMBER, \Nethgui\Controller\Table\Modify::FIELD),
            array('Network', $this->getPlatform()->createValidator()->cidrBlock(), \Nethgui\Controller\Table\Modify::FIELD),
            array('LocalNetworks', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD),
            array('RemoteNetworks', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD),
            array('Protocol', $this->getPlatform()->createValidator()->memberOf(array('tcp-server','udp')), \Nethgui\Controller\Table\Modify::FIELD),
            array('Compression', Validate::SERVICESTATUS, \Nethgui\Controller\Table\Modify::FIELD),
            array('PublicAddresses', Validate::NOTEMPTY, \Nethgui\Controller\Table\Modify::FIELD),
            array('Cipher', $this->getPlatform()->createValidator()->memberOf($ciphers), \Nethgui\Controller\Table\Modify::FIELD),
        );
        
        $this->declareParameter('Psk', $this->createValidator()->minLength(8), $this->getPlatform()->getMapAdapter(
                array($this, 'readPskFile'), array($this, 'writePskFile'), array()
            ));

        $this->setSchema($parameterSchema);
        $this->setDefaultValue('status', 'enabled');
        $this->setDefaultValue('Port', rand(1200, 1300));
        $this->setDefaultValue('Compression', 'enabled');
        $this->setDefaultValue('LocalNetworks', $this->readNetworks());
        $this->setDefaultValue('PublicAddresses', '');
        $this->setDefaultValue('Psk', $this->generatePsk());
        $this->setDefaultValue('Cipher', '');

        parent::initialize();
    }

    public function readLocalNetworks($v)
    {
        return implode("\n", explode(",", $v));
    }

    public function writeLocalNetworks($p)
    {
        return array(implode(',', array_filter(preg_split("/[,\s]+/", $p))));
    }

    public function readRemoteNetworks($v)
    {
        return $this->readLocalNetworks($v);
    }

    public function writeRemoteNetworks($p)
    {
        return $this->writeLocalNetworks($p);
    }

    private function maskToCidr($mask){
        $long = ip2long($mask);
        $base = ip2long('255.255.255.255');
        return 32-log(($long ^ $base)+1,2);
    }

    private function readPublicAddresses()
    {
        static $ips;

        if (!isset($ips)) {
            foreach ($this->getPlatform()->getDatabase('networks')->getAll() as $key => $props) {
                if ( ($props['role'] == 'red' || $props['role'] == 'green') && $props['ipaddr']) {
                    $ip = $this->getPlatform()->exec("/usr/bin/timeout -s 4 -k 1 1 /usr/bin/dig -b ".$props['ipaddr']." +short +time=1 myip.opendns.com @resolver1.opendns.com")->getOutput();
                    if ($ip) {
                        $ips[$ip] = '';
                    }
                }
            }
            $ips = array_keys($ips);

            # add FQDN as fallback
            $s = $this->getPlatform()->getDatabase('configuration')->getType('SystemName');
            $d = $this->getPlatform()->getDatabase('configuration')->getType('DomainName');
            $ips[] = "$s.$d";
        }

        return $ips;
    }

    private function readNetworks()
    {
        static $networks;

        if (isset($networks)) {
            return $networks;
        }

        $interfaces = $this->getPlatform()->getDatabase('networks')->getAll();
        foreach ($interfaces as $interface => $props) {
            if(isset($props['role']) && isset($props['ipaddr']) && $props['role'] == 'green') {
                $net = long2ip(ip2long($props['ipaddr']) & ip2long($props['netmask']));
                $cidr = $this->maskToCidr($props['netmask']); 
                $networks[] = "$net/$cidr";
            }
        }
        return $networks;
    }
  
    private function generatePsk()
    {
        $tmp = tempnam(sys_get_temp_dir() , "OPENVPN");
        $this->getPlatform()->exec('/usr/sbin/openvpn --genkey --secret '.$tmp);
        $ret = file_get_contents($tmp);
        unlink($tmp);
        return $ret;
    }

    private function readFile($fileName) {
        if (!file_exists($fileName)) {
            return '';
        }
        $value = $this->getPhpWrapper()->file_get_contents($fileName);

        if ($value === FALSE) {
            $value = '';
        }

        return trim($value);
    }
    
    private function writeFile($fileName, $value) {
        // Prepare the RAW value
        $valueRaw = trim($value) . "\n";
        $retvalRaw = $this->getPhpWrapper()->file_put_contents($fileName, $valueRaw);
        if ($retvalRaw === FALSE) {
            $this->getLog()->error(sprintf('%s: file_put_contents failed to write data to %s', __CLASS__, $fileName));
            return FALSE;
        }
        chmod($fileName, 0640);

        return TRUE;
    }

    public function readPskFile()
    {
        if (!isset($this->parameters['name'])) {
            return '';
        }
        return $this->readFile(self::TUNNEL_PATH . $this->parameters['name'] . '.key');
    }


    public function writePskFile($value)
    {
        return $this->writeFile(self::TUNNEL_PATH . $this->parameters['name'] . '.key', $value);
    }

    private function isUsedPort($port, $exclude)
    {
        $port = trim($port);
        # check between other tunnels
        $db = $this->getPlatform()->getDatabase('vpn');
        foreach ($db->getAll() as $key => $props) {
            if ( $key == $exclude || $props['type'] != "openvpn-tunnel-server" ) {
                continue;
            }
            if ($props['Port'] == $port) {
                return true;
            }
        }
        # check host to net server
        $htn = $this->getPlatform()->getDatabase('configuration')->getKey('openvpn@host-to-net');
        if ($htn['UDPPort'] == $port) {
            return true;
        }

        return false;
    }

    private function isUsedNetwork($network, $exclude)
    {
        $network = trim($network);
        # check between other tunnels
        $db = $this->getPlatform()->getDatabase('vpn');
        foreach ($db->getAll() as $key => $props) {
            if ( $key == $exclude || $props['type'] != "openvpn-tunnel-server" ) {
                continue;
            }
            if ($props['Network'] == $network) {
                return true;
            }
        }
        # check host to net server
        $htn = $this->getPlatform()->getDatabase('configuration')->getKey('openvpn@host-to-net');
        $htn_cidr = $htn['Network']."/".$this->maskToCidr($htn['Netmask']);
        if ($htn_cidr == $network) {
            return true;
        }

        return false;
    }

    private function keyExists($key)
    {
        return $this->getPlatform()->getDatabase('vpn')->getType($key);
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        if ( ! $this->getRequest()->isMutation()) {
            return;
        }
        if ($this->isUsedPort($this->parameters['Port'], $this->parameters['name'])) {
            $report->addValidationErrorMessage($this, 'Port', 'port_in_use');
        }
        if ($this->isUsedNetwork($this->parameters['Network'], $this->parameters['name'])) {
            $report->addValidationErrorMessage($this, 'Network', 'network_in_use');
        }
        $v_cidr = $this->getPlatform()->createValidator()->cidrBlock();
        foreach (array('LocalNetworks', 'RemoteNetworks') as $param) {
            $networks = array_filter(preg_split('/[,\s]+/', $this->parameters[$param]));
            foreach ($networks as $net){
                if( ! $v_cidr->evaluate($net)) {
                    $report->addValidationError($this, $param, $v_cidr); 
                }
            }
        }

        $v_host = $this->getPlatform()->createValidator(Validate::HOSTADDRESS);
        foreach (explode(",",$this->parameters["PublicAddresses"]) as $host) {
            if ( ! $v_host->evaluate($host) ) {
                    $report->addValidationError($this, "PublicAddresses", $v_host); 
            }
        }
        if ($this->getIdentifier() === 'create') {
            if ($this->keyExists($this->parameters['name'])) {
                $report->addValidationErrorMessage($this, 'name', 'key_exists_message');
            }
        }
        parent::validate($report);
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['CipherDatasource'] = array_map(function($fmt) use ($view) {
            if ($fmt) {
                return array($fmt, $fmt);
            } else {
                return array($fmt,$view->translate('Auto_label'));
            }
        }, $this->getParent()->readCiphers());
        $templates = array(
            'create' => 'NethServer\Template\OpenVpnTunnels\Servers\Modify',
            'update' => 'NethServer\Template\OpenVpnTunnels\Servers\Modify',
            'delete' => 'Nethgui\Template\Table\Delete',
        );
        $view['DownloadClient'] = $view->getModuleUrl('../Download');

        $view->setTemplate($templates[$this->getIdentifier()]);
        if($this->getIdentifier() === 'create' && $this->getRequest()->isValidated() && ! $this->getRequest()->isMutation()) {
           $view['PublicAddresses'] = $this->readPublicAddresses();
        }
    }

    protected function onParametersSaved($changedParameters)
    {
        $event = $this->getIdentifier();
        if ($event == "update") {
            $event = "modify";
        }
        $this->getPlatform()->signalEvent(sprintf('openvpn-tunnel-%s &', $event), array($this->parameters['name']));
    }

}
