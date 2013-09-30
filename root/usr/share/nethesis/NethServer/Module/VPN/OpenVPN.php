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

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $base)
    {
        return \Nethgui\Module\SimpleModuleAttributesProvider::extendModuleAttributes($base, 'Configuration', 30);
    }

    public function initialize()
    {
        parent::initialize();
        $authModes = $this->createValidator()->memberOf(array('password', 'certificate','password-certificate'));
        $modes = $this->createValidator()->memberOf(array('bridged', 'routed'));
        $this->declareParameter('ServerStatus', Validate::SERVICESTATUS, array('configuration', 'openvpn', 'ServerStatus'));
        $this->declareParameter('AuthMode', $authModes, array('configuration', 'openvpn', 'AuthMode'));
        $this->declareParameter('Mode', $modes, array('configuration', 'openvpn', 'Mode'));
        $this->declareParameter('ClientToClient', Validate::SERVICESTATUS, array('configuration', 'openvpn', 'ClientToClient'));
        $this->declareParameter('RouteToVPN', Validate::SERVICESTATUS, array('configuration', 'openvpn', 'RouteToVPN'));
        $this->declareParameter('BridgeStartIP', Validate::IPv4, array('configuration', 'openvpn', 'BridgeStartIP'));
        $this->declareParameter('BridgeEndIP', Validate::IPv4, array('configuration', 'openvpn', 'BridgeEndIP'));
        $this->declareParameter('Netmask', Validate::NETMASK, array('configuration', 'openvpn', 'Netmask'));
        $this->declareParameter('Network', "/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}(0)$/", array('configuration', 'openvpn', 'Network'));
    }

    protected function onParametersSaved($changes)
    {
        $this->getPlatform()->signalEvent('nethserver-openvpn-save@post-process');
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

        $view['ClientToClientDatasource'] = array(
            array('enabled',$view->translate('enabled_label')),
            array('disabled',$view->translate('disabled_label'))
        );
        $view['RouteToVPNDatasource'] = array(
            array('enabled',$view->translate('enabled_label')),
            array('disabled',$view->translate('disabled_label'))
        );
        $view['AuthModeDatasource'] = array(
            array('password',$view->translate('password_mode_label')),
            array('certificate',$view->translate('certificate_mode_label')),
            array('password-certificate',$view->translate('password_certificate_mode_label'))
        );
        $view['ModeDatasource'] = array(
            array('bridged',$view->translate('bridged_label')),
            array('routed',$view->translate('routed_label')),
        );
        $view['priorityDatasource'] = array(array('1',$view->translate('1_label')),array('2',$view->translate('2_label')),array('3',$view->translate('3_label')));

    }


}
