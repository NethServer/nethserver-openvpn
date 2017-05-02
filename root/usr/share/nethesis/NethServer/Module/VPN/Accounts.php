<?php
namespace NethServer\Module\VPN;

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

use Nethgui\System\PlatformInterface as Validate;

/**
 * Manage VPN accounts.
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 * @since 1.0
 */
class Accounts extends \Nethgui\Controller\TableController
{
    private $certindex = "/var/lib/nethserver/certs/certindex";
    private $users = array();

    public function getUsers()
    {
        if(!$this->users) {
            $provider = new \NethServer\Tool\UserProvider($this->getPlatform());
            $this->users = $provider->getUsers();
        }
        return $this->users;
    }


    public function initialize()
    {

        $columns = array(
            'Key',
            'Expiration',
            'OpenVpnIp',
            'RemoteNetwork',
            'Actions'
        );

        $this
            ->setTableAdapter(new \Nethgui\Adapter\LazyLoaderAdapter(array($this, 'readCertIndexAccounts')))
            ->setColumns($columns)
            ->addTableAction(new \NethServer\Module\VPN\Accounts\Modify('create'))
            ->addTableAction(new \Nethgui\Controller\Table\Help('Help'))
            ->addRowAction(new \NethServer\Module\VPN\Accounts\Modify('update'))
            ->addRowAction(new \NethServer\Module\VPN\Accounts\Modify('delete'))
            ->addRowAction(new \NethServer\Module\VPN\Accounts\Download('download'))

        ;

        parent::initialize();
    }

    private function formatDate($date)
    {
        if (!trim($date)) {
            return "-";
        }
        return "20{$date[0]}{$date[1]}-{$date[2]}{$date[3]}-{$date[4]}{$date[5]}";
    }

    private function parseCN($str)
    {
        if (!trim($str)) {
            return "-";
        }
        $tmp = explode("/",$str);
        $tmp = explode("=",$tmp[6]);
        return $tmp[1];
    }

    public function readCertIndexAccounts()
    {
        $loader = new \ArrayObject();
        $domain = $this->getPlatform()->getDatabase('configuration')->getType('DomainName');

        // get all users with VPNClientAccess enabled
        $users = $this->getUsers();
        foreach($users as $user => $props) {
            $vpn_access = $this->getPlatform()->getDatabase('vpn')->getProp($user, 'VPNClientAccess');
            if ($vpn_access && $vpn_access == 'yes' ) {
                $loader[$user] = array(
                    'name' => $user,
                    'VPNRemoteNetwork' => $props['VPNRemoteNetwork'],
                    'VPNRemoteNetmask' => $props['VPNRemoteNetmask'],
                    'OpenVpnIp' => $props['OpenVpnIp'],
                );
            }
        }

        // get all vpn accounts
        $users = $this->getPlatform()->getDatabase('vpn')->getAll('vpn');
        foreach($users as $user => $props) {
            $loader[$user] = array(
                'name' => $user,
                'VPNRemoteNetwork' => $props['VPNRemoteNetwork'],
                'VPNRemoteNetmask' => $props['VPNRemoteNetmask'],
                'OpenVpnIp' => $props['OpenVpnIp'],
            );
        }

        // read certificate expiration for certificate associated with each account
        $lines = $this->getPhpWrapper()->file($this->certindex);
        if ($lines !== FALSE) {
            foreach ($lines as $line) {
                list($status, $exp_date, $rev_date, $index, $name, $cn) = explode("\t", trim($line, "\n"));
                $cn = $this->parseCN($cn);
                if ( !isset($loader[$cn]) ) {
                    # check certificates from NS 6
                    #   user = goofy@nethserver.org
                    #   cn = goofy
                    if ( !isset($loader[$cn."@$domain"]) ) {
                        continue;
                    } else { # map to full user name
                        $cn = $cn."@$domain";
                    }
                }
                $loader[$cn]['Expiration'] = $this->formatDate($exp_date);
                $loader[$cn]['Status'] = $status;
            }
        } else {
            $this->getLog()->error("Can't access certificate index file: ".$this->certindex);
        }

        return $loader;
    }

    public function prepareViewForColumnRemoteNetwork(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        if (isset($values['VPNRemoteNetwork']) && $values['VPNRemoteNetwork']) {
            return $values['VPNRemoteNetwork'].'/'.$values['VPNRemoteNetmask'];
        }
        return '';
    }
}
