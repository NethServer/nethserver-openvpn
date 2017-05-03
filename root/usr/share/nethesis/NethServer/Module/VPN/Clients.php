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
 * Manage VPN clients (tunnels).
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 * @since 1.0
 */
class Clients extends \Nethgui\Controller\TableController
{
    public function initialize()
    {

        $columns = array(
            'Key',
            'RemoteHost',
            'Actions'
        );

        $this
            ->setTableAdapter($this->getPlatform()->getTableAdapter('vpn','tunnel'))
            ->setColumns($columns)
            ->addTableAction(new \NethServer\Module\VPN\Clients\Modify('create'))
            ->addTableAction(new \Nethgui\Controller\Table\Help('Help'))
            ->addRowAction(new \NethServer\Module\VPN\Clients\Modify('update'))
            ->addRowAction(new \NethServer\Module\VPN\Clients\Modify('delete'))

        ;

        parent::initialize();
    }

}
