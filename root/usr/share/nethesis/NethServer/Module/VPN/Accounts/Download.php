<?php
namespace NethServer\Module\VPN\Accounts;

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
class Download extends \Nethgui\Controller\Table\RowAbstractAction
{
    public function bind(\Nethgui\Controller\RequestInterface $request)
    {
        parent::bind($request);
    }

    public function initialize()
    {
        $parameterSchema = array(
            array('name', Validate::USERNAME, \Nethgui\Controller\Table\Modify::KEY),
        );

        $this->setSchema($parameterSchema);
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);

        $name = \Nethgui\array_head($this->getRequest()->getPath()); 
        $view['ovpn'] = $view->getModuleUrl('/VPN/Accounts/download/ovpn/' . $name);
        $view['pem'] = $view->getModuleUrl('/VPN/Accounts/download/pem/' .  \Nethgui\array_head($this->getRequest()->getPath()));
        $view['ca'] = $view->getModuleUrl('/VPN/Accounts/download/ca/' .  \Nethgui\array_head($this->getRequest()->getPath()));
        $view['pkcs12'] = $view->getModuleUrl('/VPN/Accounts/download/pkcs12/' .  \Nethgui\array_head($this->getRequest()->getPath()));
        if ($this->getRequest()->isValidated()) {
            $command = "";
            $file = "";
            $path = $this->getRequest()->getPath();
            $name = array_pop($path);
            $type = array_pop($path);
            $mime = 'text/plain; charset=UTF-8';

            switch($type) {
                case 'ovpn':
                    $command = $this->prepareCommand("/usr/libexec/nethserver/openvpn-local-client", array($name));
                    $mime = 'application/x-openvpn-profile';
                    $file = "$name.ovpn";
                    break;
                case 'ca':
                    $command = $this->prepareCommand("/bin/cat", array("/etc/pki/tls/certs/NSRV.crt"));
                    $mime = 'application/x-pem-file';
                    $file = "ca.crt";
                    break;
                case 'pem':
                    $command = $this->prepareCommand("/bin/cat", array("/var/lib/nethserver/certs/$name.key","/var/lib/nethserver/certs/$name.crt","/etc/pki/tls/certs/NSRV.crt"));
                    $file = "$name.pem";
                    $mime = 'application/x-pem-file';
                    break;
                case 'pkcs12':
                    $command = $this->prepareCommand("/bin/cat", array("/var/lib/nethserver/certs/$name.p12"));
                    $file = "$name.p12";
                    $mime = 'application/x-pkcs12';
                    break;
            }
            $view->getCommandList('/Main')->setDecoratorTemplate(function (\Nethgui\View\ViewInterface $view) use ($command, $file, $mime) {
                    // Discard any output buffer:
                    while (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                    header(sprintf('Content-type: %s', $mime));
                    header('Content-Disposition: attachment; filename="' . $file . '"');
                    passthru($command);
                    exit(0);
                });
       } 
    }

    private function prepareCommand($cmd, $args = array())
    {
        return escapeshellcmd($cmd) . ' ' . join(' ', array_map('escapeshellarg', $args));
    }

}
