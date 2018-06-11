<?php
namespace NethServer\Module\OpenVpnTunnels;

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
 * Manage OpenVpnTunnels clients (tunnels).
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 * @since 1.0
 */
class Clients extends \Nethgui\Controller\TableController
{
    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $attributes)
    {
        return new \NethServer\Tool\CustomModuleAttributesProvider($attributes, array('languageCatalog' => array('NethServer_Module_OpenVpnTunnels', 'NethServer_Module_VPN_Clients')));
    }

    public function initialize()
    {

        $columns = array(
            'Key',
            'RemotePort',
            'Topology',
            'RemoteHost',
            'RemoteNetworks',
            'State',
            'Actions'
        );

        $this
            ->setTableAdapter($this->getPlatform()->getTableAdapter('vpn','tunnel'))
            ->setColumns($columns)
            ->addTableAction(new \NethServer\Module\OpenVpnTunnels\Clients\Modify('create'))
            ->addTableAction(new \NethServer\Module\OpenVpnTunnels\Clients\Upload())
            ->addTableAction(new \Nethgui\Controller\Table\Help('Help'))
            ->addRowAction(new \NethServer\Module\OpenVpnTunnels\Clients\Modify('update'))
            ->addRowAction(new \NethServer\Module\OpenVpnTunnels\Clients\Modify('delete'))
            ->addRowAction(new \NethServer\Module\OpenVpnTunnels\TunnelCtl('enable'))
            ->addRowAction(new \NethServer\Module\OpenVpnTunnels\TunnelCtl('disable'))

        ;

        parent::initialize();
    }

    private function readStatus()
    {
        static $status;
        if (!isset($status)) {
            $status = json_decode($this->getPlatform()->exec('sudo /usr/libexec/nethserver/openvpn-tunnels list')->getOutput(), true);
        }
        return $status;
    }


    public function prepareViewForColumnRemoteNetworks(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        if (isset($values['RemoteNetworks']) && $values['RemoteNetworks']) {
            return $values['RemoteNetworks'];
        }
        $status = $this->readStatus();
        if (!isset($status[$key])) {
           return '-';
        }
        return $status[$key]['remote'];
    }

    public function prepareViewForColumnState(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        if (!isset($values['status']) || ($values['status'] == "disabled")) {
            $rowMetadata['rowCssClass'] = trim($rowMetadata['rowCssClass'] . ' user-locked');
        }
        $status = $this->readStatus();
        if (!isset($status[$key])) {
            return '-';
        }
        if (($status[$key]['running'])) {
           return '<i class="fa fa-check-circle" style="color: green; font-size: 150%"></i>';
        } else {
           return '<i class="fa fa-warning" style="color: red; font-size: 150%"></i>';
        }
    }

    public function prepareViewForColumnActions(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $cellView = $action->prepareViewForColumnActions($view, $key, $values, $rowMetadata);

        if (!isset($values['status']) || ($values['status'] == "disabled")) {
            unset($cellView['disable']);
        } else {
            unset($cellView['enable']);
        }

        return $cellView;
    }

    public function readDigests()
    {
        static $digests;

        if (isset($digests)) {
            return $digests;
        }

        $digests = array('');
        $out = $this->getPlatform()->exec('/usr/sbin/openvpn --show-digests')->getOutputArray();
        foreach ($out as $line) {
            if (strpos($line, 'bit') !== false) {
                $tmp = preg_split("/\s+/", $line);
                $digests[] = $tmp[0];
            }
        }
        return $digests;
    }

    public function readCiphers()
    {
        static $ciphers;

        if (isset($ciphers)) {
            return $ciphers;
        }

        $ciphers = array('');
        $out = $this->getPlatform()->exec('/usr/sbin/openvpn --show-ciphers')->getOutputArray();
        foreach ($out as $line) {
            if (strpos($line, '(') !== false) {
                $tmp = preg_split("/\s+/", $line);
                $ciphers[] = $tmp[0];
            }
        }
        return $ciphers;
    }
}
