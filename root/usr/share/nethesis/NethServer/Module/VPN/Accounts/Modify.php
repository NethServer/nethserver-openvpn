<?php
namespace NethServer\Module\VPN\Accounts;

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
use Nethgui\Controller\Table\Modify as Table;

/**
 * Modify OpenVPN net2net tunnel
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 */
class Modify extends \Nethgui\Controller\Table\Modify
{
    public function initialize()
    {
        $nv = $this->createValidator()->orValidator(
                  $this->createValidator(Validate::USERNAME), 
                  $this->createValidator(Validate::HOSTADDRESS)
        );
        $ipv = $this->createValidator()->orValidator($this->createValidator(Validate::IPv4), $this->createValidator(Validate::EMPTYSTRING));

        $parameterSchema = array(
            array('name', $nv, \Nethgui\Controller\Table\Modify::KEY),
            array('VPNRemoteNetmask', Validate::IPv4_NETMASK_OR_EMPTY, \Nethgui\Controller\Table\Modify::FIELD),
            array('VPNRemoteNetwork',  Validate::IPv4_OR_EMPTY, \Nethgui\Controller\Table\Modify::FIELD),
            array('OpenVpnIp',  $ipv, \Nethgui\Controller\Table\Modify::FIELD),
            array('User', VALIDATE::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD), // used only in UI
            array('AccountType', $this->createValidator()->memberOf(array('vpn-user','vpn')), \Nethgui\Controller\Table\Modify::FIELD) //used only in UI
        );

        $this->setSchema($parameterSchema);

        parent::initialize();
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $templates = array(
            'create' => 'NethServer\Template\VPN\Accounts\Modify',
            'update' => 'NethServer\Template\VPN\Accounts\Modify',
            'delete' => 'Nethgui\Template\Table\Delete',
        );
        $view->setTemplate($templates[$this->getIdentifier()]);

        $tmp = array();
        foreach($this->getParent()->getUsers() as $user => $props) {
            $vpn_user = $this->getPlatform()->getDatabase('vpn')->getKey($user);
            if (!$vpn_user) {
                $tmp[] = array($user,$user);
            }
        }

        $view['UserDatasource'] = $tmp;

        $view['AccountType'] = 'vpn';
    }

    private function execCrtCmd($cmd) 
    {
        $process = $this->getPlatform()->exec($cmd);
        if ($process->getExitCode() != 0) {
                $this->getLog()->error(sprintf("%s: $cmd failed", __CLASS__));
        }
    }

    private function revokeCert($cn)
    {
        $this->execCrtCmd("/usr/bin/sudo /usr/libexec/nethserver/pki-vpn-revoke -d $cn");
    }

    private function generateCert($cn)
    {
        $this->execCrtCmd("/usr/bin/sudo /usr/libexec/nethserver/pki-vpn-gencert $cn");
    }



    public function process()
    {
        if ($this->parameters['User']) {
            $cn = $this->parameters['User'];
        } else {
            $cn = $this->parameters['name'];
        }

        if ($this->getIdentifier() === 'create' && $this->getRequest()->isMutation()) {
            $props = array('VPNRemoteNetwork' => $this->parameters['VPNRemoteNetwork'], 'VPNRemoteNetmask' => $this->parameters['VPNRemoteNetmask'], 'OpenVpnIp' => $this->parameters['OpenVpnIp']);
            $this->getPlatform()->getDatabase('vpn')->setKey($cn, $this->parameters['AccountType'], $props);
            $this->generateCert($cn);
        }
        
        if ($this->getIdentifier() === 'update' && $this->getRequest()->isMutation()) {
            $this->getPlatform()->getDatabase('vpn')->setProp($cn, array('VPNRemoteNetwork' => $this->parameters['VPNRemoteNetwork'], 'VPNRemoteNetmask' => $this->parameters['VPNRemoteNetmask'], 'OpenVpnIp' => $this->parameters['OpenVpnIp']));
        }
        
        if ($this->getIdentifier() === 'delete' && $this->getRequest()->isMutation()) {
            $this->getPlatform()->getDatabase('vpn')->deleteKey($cn);
            $this->revokeCert($cn);
        }
        if($this->getRequest()->isMutation()) {
            $this->exitCode = $this->getPlatform()->signalEvent('nethserver-openvpn-save')->getExitCode();
        }
    }

    public function nextPath()
    {
        // Workaround for LazyLoaderAdapter to reload table contents after mutation request
        if($this->getRequest()->isMutation()) {
            return '/VPN/Accounts/read';
        }
        return parent::nextPath();
    }

}
