<?php
namespace NethServer\Module\VPN\Clients;

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
            array('RemoteHost', Validate::HOSTADDRESS, \Nethgui\Controller\Table\Modify::FIELD),
            array('RemotePort', Validate::PORTNUMBER, \Nethgui\Controller\Table\Modify::FIELD),
            array('User', Validate::ANYTHING, \Nethgui\Controller\Table\Modify::FIELD),
            array('Compression', Validate::SERVICESTATUS, \Nethgui\Controller\Table\Modify::FIELD),
            array('AuthMode', $this->createValidator()->memberOf(array('certificate','psk','password-certificate')), \Nethgui\Controller\Table\Modify::FIELD)
        );
        
        $this->declareParameter('Crt', Validate::ANYTHING, $this->getPlatform()->getMapAdapter(
                array($this, 'readCrtFile'), array($this, 'writeCrtFile'), array()
            ));
        $this->declareParameter('Psk', Validate::ANYTHING, $this->getPlatform()->getMapAdapter(
                array($this, 'readPskFile'), array($this, 'writePskFile'), array()
            ));

        $this->setSchema($parameterSchema);
        $this->setDefaultValue('Mode', 'routed');
        $this->setDefaultValue('AuthMode', 'certificate');

        parent::initialize();
    }

    private function readFile($fileName) {
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


    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $templates = array(
            'create' => 'NethServer\Template\VPN\Clients\Modify',
            'update' => 'NethServer\Template\VPN\Clients\Modify',
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
        $this->getPlatform()->signalEvent(sprintf('nethserver-vpn-%s &', $event), array($this->parameters['name']));
    }

}
