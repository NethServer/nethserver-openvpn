<?php
namespace NethServer\Module\VPN;

/*
 * Copyright (C) 2011 Nethesis S.r.l.
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
 * Mange OpenVPN configuration
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 */
class OpenVPN extends \Nethgui\Controller\AbstractController
{
    private $bridges = array();

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($base, 'Configuration', 30);
    }

    private function readBridges()
    {
        $db = $this->getPlatform()->getDatabase('networks');
        $devices = $db->getAll();

        $ret = array();

        foreach ($devices as $dev=>$val) {
            if (preg_match('/bridge/',$val['type'])) {
                if (isset($val['role'])) {
                    $ret[$dev] = $val['role'];
                }
            }
        } 

        return $ret;
    }

    private function orEmptyValidator($v)
    {
        if ($v instanceof \Nethgui\System\ValidatorInterface) {
            return $this->createValidator()->orValidator($v, $this->createValidator(\Nethgui\System\PlatformInterface::EMPTYSTRING));
        }
        return $this->createValidator()->orValidator($this->createValidator($v), $this->createValidator(\Nethgui\System\PlatformInterface::EMPTYSTRING));
    }

    public function initialize()
    {
        parent::initialize();
        if (!$this->bridges) {
            $this->bridges = $this->readBridges();
        }
        $authModes = $this->createValidator()->memberOf(array('password', 'certificate','password-certificate'));
        $modes = $this->createValidator()->memberOf(array('bridged', 'routed'));
        $bridges = $this->createValidator()->memberOf(array_keys($this->bridges));
        $this->declareParameter('ServerStatus', Validate::SERVICESTATUS, array('configuration', 'openvpn@host-to-net', 'status'));
        $this->declareParameter('AuthMode', $authModes, array('configuration', 'openvpn@host-to-net', 'AuthMode'));
        $this->declareParameter('Mode', $modes, array('configuration', 'openvpn@host-to-net', 'Mode'));
        $this->declareParameter('Bridge', $bridges, array('configuration', 'openvpn@host-to-net', 'BridgeName'));
        $this->declareParameter('ClientToClient', Validate::SERVICESTATUS, array('configuration', 'openvpn@host-to-net', 'ClientToClient'));
        $this->declareParameter('RouteToVPN', Validate::SERVICESTATUS, array('configuration', 'openvpn@host-to-net', 'RouteToVPN'));
        $this->declareParameter('PushExtraRoutes', Validate::SERVICESTATUS, array('configuration', 'openvpn@host-to-net', 'PushExtraRoutes'));
        $this->declareParameter('BridgeStartIP', Validate::IPv4, array('configuration', 'openvpn@host-to-net', 'BridgeStartIP'));
        $this->declareParameter('BridgeEndIP', Validate::IPv4, array('configuration', 'openvpn@host-to-net', 'BridgeEndIP'));
        $this->declareParameter('PushDomain', $this->orEmptyValidator($this->createValidator()->hostname(1)), array('configuration', 'openvpn@host-to-net', 'PushDomain'));
        $this->declareParameter('PushDns', Validate::IPv4_OR_EMPTY, array('configuration', 'openvpn@host-to-net', 'PushDns'));
        $this->declareParameter('PushWins', Validate::IPv4_OR_EMPTY, array('configuration', 'openvpn@host-to-net', 'PushWins'));
        $this->declareParameter('PushNbdd', Validate::IPv4_OR_EMPTY, array('configuration', 'openvpn@host-to-net', 'PushNbdd'));
        $this->declareParameter('Netmask', Validate::NETMASK, array('configuration', 'openvpn@host-to-net', 'Netmask'));
        $this->declareParameter('Network', Validate::IPv4, array('configuration', 'openvpn@host-to-net', 'Network'));
        $this->declareParameter('Compression', Validate::SERVICESTATUS, array('configuration', 'openvpn@host-to-net', 'Compression'));
        $this->declareParameter('port', Validate::PORTNUMBER, array('configuration', 'openvpn@host-to-net', 'UDPPort'));
        $this->declareParameter('Remote', Validate::ANYTHING, array('configuration', 'openvpn@host-to-net', 'Remote'));

    }

    protected function onParametersSaved($changes)
    {
        // execute event in background to avoid errors on bridge creation
        $this->getPlatform()->signalEvent('nethserver-openvpn-save &');
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        if (!$this->bridges) {
            $this->bridges = $this->readBridges();
        }

        $view['AuthModeDatasource'] = array(
            array('password',$view->translate('password_mode_label')),
            array('certificate',$view->translate('certificate_mode_label')),
            array('password-certificate',$view->translate('password_certificate_mode_label'))
        );
        $view['ModeDatasource'] = array(
            array('bridged',$view->translate('bridged_label')),
            array('routed',$view->translate('routed_label')),
        );
        $bridges = array();
        foreach ($this->bridges as $dev => $role) {
            $bridges[] = array($dev, "$dev ($role)");
        }
        $view['BridgeDatasource'] = $bridges; 

        $view['priorityDatasource'] = array(array('1',$view->translate('1_label')),array('2',$view->translate('2_label')),array('3',$view->translate('3_label')));
 
        $s = $this->getPlatform()->getDatabase('configuration')->getType('SystemName');
        $d = $this->getPlatform()->getDatabase('configuration')->getType('DomainName');
        $view['RemoteDefault'] = "$s.$d";
        $view['DomainPlaceholder'] = $d;
    }

    private function maskToCidr($mask){
        $long = ip2long($mask);
        $base = ip2long('255.255.255.255');
        return 32-log(($long ^ $base)+1,2);
    }

    private function ipInRange( $ip, $range ) {
        if ( strpos( $range, '/' ) == false ) {
                $range .= '/32';
        }
        // $range is in IP/CIDR format eg 127.0.0.1/24
        list( $range, $netmask ) = explode( '/', $range, 2 );
        $range_decimal = ip2long( $range );
        $ip_decimal = ip2long( $ip );
        $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
        $netmask_decimal = ~ $wildcard_decimal;
        return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
     }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        parent::validate($report);

        if (!$this->getRequest()->isMutation() || $this->parameters['Mode'] == 'bridged' || $this->parameters['ServerStatus'] == 'disabled' || $report->hasValidationErrors()) {
            return;
        }

        // check the "network" parameter is consistent with its "Mask" (only 0-bits in tail)
        $net = long2ip(ip2long($this->parameters['Network']) & ip2long($this->parameters['Netmask']));
        if ($net != $this->parameters['Network']) {
            $report->addValidationErrorMessage($this, 'Network', 'invalid_network', array($this->parameters['Network']));
        }

        // For OpenVPN network must be 255.255.255.248 (/29) or lower
        $cidr_net = $this->maskToCidr($this->parameters['Netmask']);
        if ($cidr_net > 29) {
            $report->addValidationErrorMessage($this, 'Netmask', 'netmask_lower_than_29', array($this->parameters['Netmask']));
        }

        // check the network is not already used
        $interfaces = $this->getPlatform()->getDatabase('networks')->getAll();
        foreach ($interfaces as $interface => $props) {
            if(isset($props['role']) && isset($props['ipaddr']) ) {
                $cidr = $this->parameters['Network']."/".$this->maskToCidr($this->parameters['Netmask']);
                if ($this->ipInRange($props['ipaddr'], $cidr)) {
                    $report->addValidationErrorMessage($this, 'Network', 'used_network', array($this->parameters['network']));
                }
            }
        }

        if (isset($this->parameters['Remote']) && $this->parameters['Remote']) {
            $v = $this->createValidator(Validate::HOSTADDRESS);
            foreach(explode(',',$this->parameters['Remote']) as $r) {
                if ( ! $v->evaluate($r) ) {
                    $report->addValidationError($this, 'Remote', $v);
                }
            }
        }
    }

}
