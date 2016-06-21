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

    public function initialize()
    {

        $columns = array(
            'Key',
            'Expiration',
            'VPNRemoteNetwork',
            'VPNRemoteNetmask',
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

        // get all users with VPNClientAccess enabled
        $users = $this->getPlatform()->getDatabase('accounts')->getAll('user');
        foreach($users as $user => $props) {
            if (isset($props['VPNClientAccess']) && $props['VPNClientAccess'] == 'yes' ) {
                $loader[$user] = array(
                    'name' => $user,
                    'VPNRemoteNetwork' => $props['VPNRemoteNetwork'],
                    'VPNRemoteNetmask' => $props['VPNRemoteNetmask'],
                );
            }
        }

        // get all vpn accounts
        $users = $this->getPlatform()->getDatabase('accounts')->getAll('vpn');
        foreach($users as $user => $props) {
            $loader[$user] = array(
                'name' => $user,
                'VPNRemoteNetwork' => $props['VPNRemoteNetwork'],
                'VPNRemoteNetmask' => $props['VPNRemoteNetmask']
            );
        }

        // read certificate expiration for certificate associated with each account
        $lines = $this->getPhpWrapper()->file($this->certindex);
        if ($lines !== FALSE) {
            foreach ($lines as $line) {
                list($status, $exp_date, $rev_date, $index, $name, $cn) = explode("\t", trim($line, "\n"));
                $cn = $this->parseCN($cn);
                if (!isset($loader[$cn])) {
                    continue;
                }
                $loader[$cn]['Expiration'] = $this->formatDate($exp_date);
                $loader[$cn]['Status'] = $status;
            }
        } else {
            $this->getLog()->error("Can't access certificate index file: ".$this->certindex);
        }

        return $loader;
    }

}
