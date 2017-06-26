<?php

namespace NethServer\Module\OpenVpnTunnels\Servers;
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

class Download extends \Nethgui\Controller\Table\RowAbstractAction
{
     public function initialize()
     {
         $parameterSchema = array(
             array('name', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::KEY),
         );
         $this->setSchema($parameterSchema);
         parent::initialize();
    }

    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        $keyValue = implode('/', $request->getPath());
        $this->getAdapter()->setKeyValue(basename($keyValue, '.txt'));
        parent::bind($request);
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {

        if( ! $this->getRequest()->isValidated()) {
            parent::prepareView($view);
            return;
        }
        if(! $this->getRequest()->hasParameter('download')) {
            $view->getCommandList('/Main')->sendQuery($view->getModuleUrl(sprintf('/OpenVpnTunnels/Servers/Download/%s.txt?OpenVpnTunnels[Servers][Download][download]=1', $this->parameters['name'])));
            return;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $record = $this->getPlatform()->getDatabase('vpn')->getKey($this->parameters['name']);
        $client = array(
                         'name' => substr("clnt".$this->parameters['name'],0,8),
                         'type' => 'tunnel',
                         'AuthMode' => 'psk',
                         'Mode' => 'routed',
                         'status' => 'enabled',
                         'Compression' => $record['Compression'],
                         'RemotePort' => $record['Port'],
                         'RemoteHost' => $record['PublicAddresses'],
                         'Cipher' =>  $record['Cipher'],
                         'Psk' => file_get_contents("/var/lib/nethserver/openvpn-tunnels/".$this->parameters['name'].".key")
                  );
        $file = tempnam("sys_get_temp_dir", "openvpn-tunnel-client");
        file_put_contents($file, json_encode($client)); 
        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="openvpn-tunnel-client-' . $this->parameters['name'] . '.json"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            unlink($file);
            exit;
        }
    }

    public function nextPath()
    {
        return 'read';
    }
}
