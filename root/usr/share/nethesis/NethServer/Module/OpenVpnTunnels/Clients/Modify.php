<?php
namespace NethServer\Module\OpenVpnTunnels\Clients;

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
    const CRT_PATH = "/var/lib/nethserver/certs/clients/";

    private $topologies = array('subnet','p2p');

    public function initialize()
    {
        $parameterSchema = array(
            array('name', $this->createValidator(Validate::USERNAME)->maxLength(13), \Nethgui\Controller\Table\Modify::KEY),
            array('Mode', $this->createValidator()->memberOf(array('routed','bridged')), \Nethgui\Controller\Table\Modify::FIELD),
            array('Password', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD),
            array('RemoteHost', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD),
            array('RemotePort', Validate::PORTNUMBER, \Nethgui\Controller\Table\Modify::FIELD),
            array('User', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD),
            array('Compression', Validate::SERVICESTATUS, \Nethgui\Controller\Table\Modify::FIELD),
            array('status', Validate::SERVICESTATUS, \Nethgui\Controller\Table\Modify::FIELD),
            array('AuthMode', $this->createValidator()->memberOf(array('certificate','psk','password-certificate')), \Nethgui\Controller\Table\Modify::FIELD),
            array('Protocol', $this->getPlatform()->createValidator()->memberOf(array('tcp-client','udp')), \Nethgui\Controller\Table\Modify::FIELD),
            array('Cipher', $this->createValidator()->memberOf($this->getParent()->readCiphers()), \Nethgui\Controller\Table\Modify::FIELD),
            array('WanPriorities', FALSE, \Nethgui\Controller\Table\Modify::FIELD),
            array('Topology', $this->getPlatform()->createValidator()->memberOf($this->topologies), \Nethgui\Controller\Table\Modify::FIELD),
            array('LocalPeer', Validate::IPv4, \Nethgui\Controller\Table\Modify::FIELD),
            array('RemotePeer', Validate::IPv4, \Nethgui\Controller\Table\Modify::FIELD),
            array('RemoteNetworks', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD),
        );

        $this->declareParameter('Crt', Validate::ANYTHING, $this->getPlatform()->getMapAdapter(
                array($this, 'readCrtFile'), array($this, 'writeCrtFile'), array()
            ));
        $this->declareParameter('Psk', Validate::ANYTHING, $this->getPlatform()->getMapAdapter(
                array($this, 'readPskFile'), array($this, 'writePskFile'), array()
            ));

        $this->setSchema($parameterSchema);
        $this->setDefaultValue('status', 'enabled');
        $this->setDefaultValue('Protocol', 'udp');
        $this->setDefaultValue('Mode', 'routed');
        $this->setDefaultValue('AuthMode', 'certificate');
        $this->setDefaultValue('Topology', 'subnet');

        parent::initialize();
    }
 
    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);

        # Force PSK authentication for topology p2p
        if($this->getRequest()->isMutation()) {
            if ($this->parameters['Topology'] == 'p2p') {
                 $this->parameters['AuthMode'] = "psk";
            }
        }
    }

    public function readRemoteNetworks($v)
    {
        return implode("\n", explode(",", $v));
    }

    public function writeRemoteNetworks($p)
    {
        return array(implode(',', array_filter(preg_split("/[,\s]+/", $p))));
    }

    public function readRemoteHost($v)
    {
        return implode("\n", explode(",", $v));
    }

    public function writeRemoteHost($p)
    {
        return array(implode(',', array_filter(preg_split("/[,\s]+/", $p))));
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

    public function readCrtFile()
    {
        if (!isset($this->parameters['name'])) {
            return '';
        }
        return $this->readFile(self::CRT_PATH . $this->parameters['name'] . '.pem');
    }


    public function writeCrtFile($value)
    {
        return $this->writeFile(self::CRT_PATH . $this->parameters['name'] . '.pem', $value);
    }

    public function readPskFile()
    {
        if (!isset($this->parameters['name'])) {
            return '';
        }
        return $this->readFile(self::CRT_PATH . $this->parameters['name'] . '.key');
    }


    public function writePskFile($value)
    {
        return $this->writeFile(self::CRT_PATH . $this->parameters['name'] . '.key', $value);
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
        $hv = $this->createValidator(Validate::HOSTADDRESS);
        $networks = array_filter(preg_split('/[,\s]+/', $this->parameters['RemoteHost']));
        foreach ($networks as $net){
            if( ! $hv->evaluate($net)) {
                $report->addValidationError($this, 'RemoteHost', $hv);
            }
        }
        if ($this->getIdentifier() === 'create') {
            if ($this->keyExists($this->parameters['name'])) {
                $report->addValidationErrorMessage($this, 'name', 'key_exists_message');
            }
        }

        if($this->getRequest()->getParameter('WanPriorityStatus') === 'enabled') {
            $wanInterfaces = array_keys($this->getWanInterfaces());
            $wanPriorities = $this->parseWanPrioritiesFromRequest();
            $adiff = array_merge(array_diff($wanInterfaces, $wanPriorities), array_diff($wanPriorities, $wanInterfaces));
            if($adiff !== array()) {
                $report->addValidationErrorMessage($this, 'WanPriorityStatus', 'missing_wanpriority_rules', array(implode(', ', $adiff)));
            }
        }

        parent::validate($report);
    }

    public function process()
    {
        if($this->getRequest()->isMutation()) {
            $this->parameters['WanPriorities'] = implode(',', $this->parseWanPrioritiesFromRequest());
        }
        parent::process();
    }

    public function parseWanPrioritiesFromRequest()
    {
        if( ! $this->getRequest()->hasParameter('WanPriority')) {
            return array();
        }
        $wanPriority = $this->getRequest()->getParameter('WanPriority');
        $value = array();
        foreach($wanPriority as $item) {
            $value[] = $item['Interface'];
        }
        return $value;
    }

    public function getWanInterfaces()
    {
         static $ret;
         if(is_array($ret)) {
             return $ret;
         }
         $ret = array();
         $providers = $this->getPlatform()->getDatabase('networks')->getAll('provider');
         // sort providers by weight:
         uasort($providers, function($r1, $r2) {
             $a = intval($r1['weight']);
             $b = intval($r2['weight']);
             if($a === $b) {
                 return 0;
             }
             return $a < $b ? -1 : 1;
         });
         foreach ($providers as $name => $provider) {
             $ret[$provider['interface']] = sprintf('%s (%s)', $name, $provider['interface']);
         }
         return $ret;
    }

    public function getWanPriorityTable()
    {
        $id = 0;
        $table = array();
        $interfaces = $this->getWanInterfaces();
        
        // concatenate the keys from WanPriorities prop and providers default sort order from NetworksDB,
        // then remove duplicates:
        $keyList = array_unique(array_filter(array_merge(explode(',', $this->parameters['WanPriorities']), array_keys($interfaces))));
        foreach($keyList as $key) {
            if( ! isset($interfaces[$key])) {
                continue;
            }
            $table[] = array('id' => $id++, 'Interface' => $key);
        }
        return $table;
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['WanInterfaces'] = $this->getWanInterfaces();
        if($this->getRequest()->isValidated()) {
            $view['WanPriority'] = $this->getWanPriorityTable();
            $view['WanPriorityStatus'] = $this->parameters['WanPriorities'] ? 'enabled' : 'disabled';
        }
        $view['CipherDatasource'] = array_map(function($fmt) use ($view) {
            if ($fmt) {
                return array($fmt, $fmt);
            } else {
                return array($fmt,$view->translate('Auto_label'));
            }
        }, $this->getParent()->readCiphers());
        $templates = array(
            'create' => 'NethServer\Template\OpenVpnTunnels\Clients\Modify',
            'update' => 'NethServer\Template\OpenVpnTunnels\Clients\Modify',
            'delete' => 'Nethgui\Template\Table\Delete',
        );
        $view->setTemplate($templates[$this->getIdentifier()]);

        $view['ModeDatasource'] =  array_map(function($fmt) use ($view) {
            return array($fmt, $view->translate($fmt . '_label'));
        }, array('routed', 'bridged'));

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
