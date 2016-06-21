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
        $parameterSchema = array(
            array('name', $this->createValidator()->orValidator($this->createValidator(Validate::USERNAME), $this->createValidator(Validate::HOSTADDRESS))->platform('vpn-create'), \Nethgui\Controller\Table\Modify::KEY),
            array('VPNRemoteNetmask', Validate::IPv4_NETMASK_OR_EMPTY, \Nethgui\Controller\Table\Modify::FIELD),
            array('VPNRemoteNetwork',  Validate::IPv4_OR_EMPTY, \Nethgui\Controller\Table\Modify::FIELD),
            array('User', $this->createValidator()->orValidator($this->createValidator(Validate::USERNAME), $this->createValidator(Validate::EMPTYSTRING)), \Nethgui\Controller\Table\Modify::FIELD), // used only in UI
            array('AccountType', $this->createValidator()->memberOf(array('user','vpn')), \Nethgui\Controller\Table\Modify::FIELD) //used only in UI
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

        $users = $this->getPlatform()->getDatabase('accounts')->getAll('user');
        $tmp = array();
        foreach($users as $user => $props) {
            if (!isset($props['VPNClientAccess']) || $props['VPNClientAccess'] == 'no' ) {
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


    private function updateUser($name, $status, $network = '', $netmask = '')
    {
        $this->getPlatform()->getDatabase('accounts')->setProp($name, 
            array('VPNClientAccess' => $status, 'VPNRemoteNetwork' => $network, 'VPNRemoteNetmask' => $netmask)
        );
    }
    
    private function updateVPNAccount($name, $network, $netmask)
    {
        $this->getPlatform()->getDatabase('accounts')->setKey($name, 'vpn',  
            array('VPNRemoteNetwork' => $network, 'VPNRemoteNetmask' => $netmask)
        );
    }

    private function deleteAccount($name)
    {
        $type = $this->getPlatform()->getDatabase('accounts')->getType($name);
        if ($type === 'vpn') {  //delete vpn account
            $this->getPlatform()->getDatabase('accounts')->deleteKey($name, 'vpn');  
        } else {
            $this->updateUser($name, 'no');
        }
    }

    public function process()
    {
        $cn = '';
        if ($this->parameters['AccountType'] === 'user' ) {
            $cn = $this->parameters['User'];
        } else {
            $cn = $this->parameters['name'];
        }

        if ($this->getIdentifier() === 'create' && $this->getRequest()->isMutation()) {
            if ($this->parameters['AccountType'] === 'user' ) {
                $this->updateUser($cn, 'yes', $this->parameters['VPNRemoteNetwork'], $this->parameters['VPNRemoteNetmask']);
            } else {
                $this->updateVPNAccount($cn, $this->parameters['VPNRemoteNetwork'], $this->parameters['VPNRemoteNetmask']);
            }
            $this->generateCert($cn);
        }
        
        if ($this->getIdentifier() === 'update' && $this->getRequest()->isMutation()) {
            $type = $this->getPlatform()->getDatabase('accounts')->getType($cn);
            if ($type === 'user') {
                $this->updateUser($cn, 'yes', $this->parameters['VPNRemoteNetwork'], $this->parameters['VPNRemoteNetmask']);
            } else {
                $this->updateVPNAccount($cn, $this->parameters['VPNRemoteNetwork'], $this->parameters['VPNRemoteNetmask']);
            }
        }
        
        if ($this->getIdentifier() === 'delete' && $this->getRequest()->isMutation()) {
            $this->deleteAccount($cn);
            $this->revokeCert($cn);
        }
        if($this->getRequest()->isMutation()) {
            $this->exitCode = $this->getPlatform()->signalEvent('nethserver-vpn-save')->getExitCode();
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
