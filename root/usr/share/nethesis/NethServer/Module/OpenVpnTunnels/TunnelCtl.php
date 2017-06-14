<?php
namespace NethServer\Module\OpenVpnTunnels;

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
 * Start/Stop/Restart a OpenVPn tunnel server/client
 *
 */
class TunnelCtl extends \Nethgui\Controller\Table\AbstractAction
{

    public function __construct($identifier = NULL)
    {
        if ($identifier !== 'enable' && $identifier !== 'disable' ) {
            throw new \InvalidArgumentException(sprintf('%s: module identifier must be one of "enable", "disable".', get_class($this)), 1497255939 );
        }
        parent::__construct($identifier);
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        $this->declareParameter('service', Validate::ANYTHING);

        parent::bind($request);
        $service = \Nethgui\array_end($request->getPath());

        if ( ! $service) {
            throw new \Nethgui\Exception\HttpException('Not found', 404, 1497256101);
        }

        $this->parameters['service'] = $service;
    }

    public function process()
    {
        $this->setViewTemplate('NethServer\Template\OpenVpnAccounts\TunnelCtl');
        if ( ! $this->getRequest()->isMutation()) {
            return;
        }
       
        $db = $this->getPlatform()->getDatabase('vpn');
        if ( $this->getIdentifier() == 'enable') {
            $db->setProp($this->parameters['service'], array('status' => 'enabled'));
        } else {
            $db->setProp($this->parameters['service'], array('status' => 'disabled'));
        }
        $this->getPlatform()->signalEvent('openvpn-tunnel-modify', array($this->parameters['service']));
    }

}
