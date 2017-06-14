<?php

namespace NethServer\Module\OpenVpnTunnels\Clients;

/*
 * Copyright (C) 2017 Nethesis S.r.l.
 * http://www.nethesis.it - nethserver@nethesis.it
 *
 * This script is part of NethServer.
 *
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License,
 * or any later version.
 *
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see COPYING.
 */

 use Nethgui\System\PlatformInterface as Validate;

class Upload extends \Nethgui\Controller\Table\AbstractAction
{
 public function initialize()
    {
        parent::initialize();
        $this->declareParameter('id', FALSE);
        $this->declareParameter('Description', $this->createValidator()->maxLength(32));
    }
    
    public function process()
    {
        if ( ! $this->getRequest()->isMutation()) {
            return;
        }
        $client = json_decode(file_get_contents($_FILES['arc']['tmp_name']));
        $this->getPlatform()->signalEvent("openvpn-tunnel-upload", array($_FILES['arc']['tmp_name']));
        $this->getParent()->getAdapter()->flush();
    }

    public function nextPath()
    {
        return $this->getRequest()->isMutation() ? 'read' : $this->getIdentifier();
    }
}
