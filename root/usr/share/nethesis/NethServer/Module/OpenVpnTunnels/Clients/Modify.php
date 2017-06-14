<?php
namespace NethServer\Module\OpenVpnTunnels\Clients;

/*
 * Copyright (C) 2012 Nethesis S.r.l.
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
 * Modify VPN clients (tunnels)
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 */
class Modify extends \Nethgui\Controller\Table\Modify
{
    const CRT_PATH = "/var/lib/nethserver/certs/clients/";

    public function initialize()
    {
        $parameterSchema = array(
            array('name', Validate::USERNAME, \Nethgui\Controller\Table\Modify::KEY),
            array('Mode', $this->createValidator()->memberOf(array('routed','bridged')), \Nethgui\Controller\Table\Modify::FIELD),
            array('Password', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD),
            array('RemoteHost', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD),
            array('RemotePort', Validate::PORTNUMBER, \Nethgui\Controller\Table\Modify::FIELD),
            array('User', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD),
            array('Compression', Validate::SERVICESTATUS, \Nethgui\Controller\Table\Modify::FIELD),
            array('status', Validate::SERVICESTATUS, \Nethgui\Controller\Table\Modify::FIELD),
            array('AuthMode', $this->createValidator()->memberOf(array('certificate','psk','password-certificate')), \Nethgui\Controller\Table\Modify::FIELD),
            array('Protocol', $this->getPlatform()->createValidator()->memberOf(array('tcp-client','udp')), \Nethgui\Controller\Table\Modify::FIELD),
            array('Cipher', $this->createValidator()->memberOf($this->getParent()->readCiphers()), \Nethgui\Controller\Table\Modify::FIELD)
        );
        
        $this->declareParameter('Crt', Validate::ANYTHING, $this->getPlatform()->getMapAdapter(
                array($this, 'readCrtFile'), array($this, 'writeCrtFile'), array()
            ));
        $this->declareParameter('Psk', Validate::ANYTHING, $this->getPlatform()->getMapAdapter(
                array($this, 'readPskFile'), array($this, 'writePskFile'), array()
            ));

        $this->setSchema($parameterSchema);
        $this->setDefaultValue('status', 'enabled');
        $this->setDefaultValue('Protocol', 'udp');
        $this->setDefaultValue('Mode', 'routed');
        $this->setDefaultValue('AuthMode', 'certificate');

        parent::initialize();
    }

    public function readRemoteHost($v)
    {
        return implode("\n", explode(",", $v));
    }

    public function writeRemoteHost($p)
    {
        return array(implode(',', array_filter(preg_split("/[,\s]+/", $p))));
    }

    private function readFile($fileName) {
        if (!file_exists($fileName)) {
            return '';
        }

        $value = $this->getPhpWrapper()->file_get_contents($fileName);

        if ($value === FALSE) {
            $value = '';
        }

        return trim($value);
    }
    
    private function writeFile($fileName, $value) {
        // Prepare the RAW value
        $valueRaw = trim($value) . "\n";
        $retvalRaw = $this->getPhpWrapper()->file_put_contents($fileName, $valueRaw);
        if ($retvalRaw === FALSE) {
            $this->getLog()->error(sprintf('%s: file_put_contents failed to write data to %s', __CLASS__, $fileName));
            return FALSE;
        }
        chmod($fileName, 0640);

        return TRUE;
    }

    public function readCrtFile()
    {
        if (!isset($this->parameters['name'])) {
            return '';
        }
        return $this->readFile(self::CRT_PATH . $this->parameters['name'] . '.pem');
    }


    public function writeCrtFile($value)
    {
        return $this->writeFile(self::CRT_PATH . $this->parameters['name'] . '.pem', $value);
    }

    public function readPskFile()
    {
        if (!isset($this->parameters['name'])) {
            return '';
        }
        return $this->readFile(self::CRT_PATH . $this->parameters['name'] . '.key');
    }


    public function writePskFile($value)
    {
        return $this->writeFile(self::CRT_PATH . $this->parameters['name'] . '.key', $value);
    }

    private function keyExists($key)
    {
        return $this->getPlatform()->getDatabase('vpn')->getType($key);
    }

    public function validate(\Nethgui\Controller\ValidationReportInterface $report)
    {
        if ( ! $this->getRequest()->isMutation()) {
            return;
        }
        $hv = $this->createValidator(Validate::HOSTADDRESS);
        $networks = array_filter(preg_split('/[,\s]+/', $this->parameters['RemoteHost']));
        foreach ($networks as $net){
            if( ! $hv->evaluate($net)) {
                $report->addValidationError($this, 'RemoteHost', $hv);
            }
        }
        if ($this->getIdentifier() === 'create') {
            if ($this->keyExists($this->parameters['name'])) {
                $report->addValidationErrorMessage($this, 'name', 'key_exists_message');
            }
        }

        parent::validate($report);
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['CipherDatasource'] = array_map(function($fmt) use ($view) {
            if ($fmt) {
                return array($fmt, $fmt);
            } else {
                return array($fmt,$view->translate('Auto_label'));
            }
        }, $this->getParent()->readCiphers());
        $templates = array(
            'create' => 'NethServer\Template\OpenVpnTunnels\Clients\Modify',
            'update' => 'NethServer\Template\OpenVpnTunnels\Clients\Modify',
            'delete' => 'Nethgui\Template\Table\Delete',
        );
        $view->setTemplate($templates[$this->getIdentifier()]);

        $view['ModeDatasource'] =  array_map(function($fmt) use ($view) {
            return array($fmt, $view->translate($fmt . '_label'));
        }, array('routed', 'bridged'));


    }

    protected function onParametersSaved($changedParameters)
    {
        $event = $this->getIdentifier();
        if ($event == "update") {
            $event = "modify";
        }
        $this->getPlatform()->signalEvent(sprintf('openvpn-tunnel-%s &', $event), array($this->parameters['name']));
    }

}
