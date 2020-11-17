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
 * Download VPN tunnel configuration
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 */
class Download extends \Nethgui\Controller\Table\RowAbstractAction
{

    public function initialize()
    {
        $parameterSchema = array(
            array('name', $this->createValidator(Validate::USERNAME)->maxLength(13), \Nethgui\Controller\Table\Modify::KEY),
        );

        $this->setSchema($parameterSchema);
    }

    private function downloadJson($name)
    {
        $record = $this->getPlatform()->getDatabase('vpn')->getKey($name);
        $client = array(
                         'name' => substr("c$name",0,13),
                         'type' => 'tunnel',
                         'Mode' => 'routed',
                         'status' => 'enabled',
                         'Compression' => $record['Compression'],
                         'RemotePort' => $record['Port'],
                         'RemoteHost' => $record['PublicAddresses'],
                         'Digest' =>  $record['Digest'],
                         'Cipher' =>  $record['Cipher'],
                         'Topology' => $record['Topology'],
                         'RemoteNetworks' => $record['LocalNetworks']
                  );
        if ($record['Topology'] == 'p2p') {
             $client['Psk'] = file_get_contents("/var/lib/nethserver/openvpn-tunnels/$name.key");
             $client['LocalPeer'] = $record['RemotePeer'];
             $client['RemotePeer'] = $record['LocalPeer'];
             $client['AuthMode'] = 'psk';
        } else {
             $client['AuthMode'] = 'certificate';
             $pem = tempnam(sys_get_temp_dir(), "tunnel-pem");
             $this->getPlatform()->exec("/usr/bin/sudo /usr/libexec/nethserver/openvpn-tunnel-pem $name $pem");
             $client['Crt'] = file_get_contents($pem);
             unlink($pem);
        }
        $file = tempnam(sys_get_temp_dir(), "openvpn-tunnel-client");
        file_put_contents($file, json_encode($client)); 
        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="openvpn-tunnel-client-' . $name . '.json"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            unlink($file);
            exit;
        }

    }

    private function downloadPem($name)
    {
        $pem = tempnam(sys_get_temp_dir(), "tunnel-pem");
        $this->getPlatform()->exec("/usr/bin/sudo /usr/libexec/nethserver/openvpn-tunnel-pem $name $pem");
        header('Content-Description: File Transfer');
        header('Content-Type: application/x-pem-file');
        header("Content-Disposition: attachment; filename=$name.pem");
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($pem));
        readfile($pem);
        unlink($pem);
        exit;
    }

    private function downloadPsk($name)
    {
        $pem = tempnam(sys_get_temp_dir(), "tunnel-pem");
        $psk = "/var/lib/nethserver/openvpn-tunnels/$name.key";
        if (!file_exists($psk)) {
            exit;
        }
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=$name.psk");
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($psk));
        readfile($psk);
        exit;
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

        $name = \Nethgui\array_head($this->getRequest()->getPath()); 
        $view['json'] = $view->getModuleUrl('/OpenVpnTunnels/Servers/download/json/' .  \Nethgui\array_head($this->getRequest()->getPath()));
        $view['pem'] = $view->getModuleUrl('/OpenVpnTunnels/Servers/download/pem/' .  \Nethgui\array_head($this->getRequest()->getPath()));
        $view['psk'] = $view->getModuleUrl('/OpenVpnTunnels/Servers/download/psk/' .  \Nethgui\array_head($this->getRequest()->getPath()));
        if ($this->getRequest()->isValidated()) {
            $command = "";
            $file = "";
            $path = $this->getRequest()->getPath();
            $name = array_pop($path);
            $type = array_pop($path);
            while (ob_get_level() > 0) {
                ob_end_clean();
            }


            switch($type) {
                case 'json':
                    $this->downloadJson($name);
                case 'pem':
                    $this->downloadPem($name);
                case 'psk':
                    $this->downloadPsk($name);
            }
       } 
    }

}
