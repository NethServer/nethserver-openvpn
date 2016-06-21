<?php
namespace NethServer\Module\OpenVPNStatus\OpenVPNClients;

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
 * Download VPN client configuration
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 * @since 1.0
 */
class Kill extends \Nethgui\Controller\Table\RowAbstractAction
{
    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
    }

    public function process() { 
        $name = \Nethgui\array_head($this->getRequest()->getPath());
        $this->getPlatform()->exec('/usr/libexec/nethserver/openvpn-kill /var/spool/openvpn/host-to-net '.$name);
    }
    public function initialize()
    {
        $parameterSchema = array(
            array('name', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::KEY),
        );

        $this->setSchema($parameterSchema);

    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
    }

}
