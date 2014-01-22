<?php
namespace NethServer\Module\Dashboard\VPN;

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
 * Manage VPN accounts.
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 * @since 1.0
 */
class OpenVPNClients extends \Nethgui\Controller\TableController
{

    public $sortId = 30;

    public function initialize()
    {

        $columns = array(
            'Key',
            'real_address',
            'virtual_address',
            'bytes',
            'since',
            'Actions'
        );

        $this
            ->setTableAdapter(new \Nethgui\Adapter\LazyLoaderAdapter(array($this, 'readClients')))
            ->setColumns($columns)
            ->addRowAction(new \NethServer\Module\Dashboard\VPN\OpenVPNClients\Kill())

        ;

        parent::initialize();
    }

    public function readClients()
    {
        $loader = new \ArrayObject();

        $clients = json_decode($this->getPlatform()->exec('/usr/libexec/nethserver/openvpn-status /var/spool/openvpn/host-to-net')->getOutput(), true); 
        if (isset($clients['result']) && $clients['result'] == 'ERROR') {
            return $loader;
        }
        foreach ($clients as $client => $values) {
            $loader[$client] = array(
                'name' => $client, 
                'real_address' => $values['real_address'], 
                'virtual_address' => $values['virtual_address'],
                'bytes' => number_format(($values['sent']+$values['rcvd'])/1024/1024,2) . " MB",
                'since' => $values['since']
            );
        }
        return $loader;
    }

}
