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
 * Manage OpenVpnTunnels server tunnels.
 *
 * @author Giacomo Sanchietti <giacomo.sanchietti@nethesis.it>
 * @since 1.0
 */
class Servers extends \Nethgui\Controller\TableController
{
    public function initialize()
    {

        $columns = array(
            'Key',
            'Port',
            'Topology',
            'Network',
            'LocalNetworks',
            'RemoteNetworks',
            'State',
            'Actions'
        );

        $this
            ->setTableAdapter($this->getPlatform()->getTableAdapter('vpn','openvpn-tunnel-server'))
            ->setColumns($columns)
            ->addTableAction(new \NethServer\Module\OpenVpnTunnels\Servers\Modify('create'))
            ->addTableAction(new \Nethgui\Controller\Table\Help('Help'))
            ->addRowAction(new \NethServer\Module\OpenVpnTunnels\Servers\Download('download'))
            ->addRowAction(new \NethServer\Module\OpenVpnTunnels\Servers\Modify('update'))
            ->addRowAction(new \NethServer\Module\OpenVpnTunnels\Servers\Modify('delete'))
            ->addRowAction(new \NethServer\Module\OpenVpnTunnels\TunnelCtl('enable'))
            ->addRowAction(new \NethServer\Module\OpenVpnTunnels\TunnelCtl('disable'))
        ;

        parent::initialize();
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

    private function readStatus()
    {
        static $status;
        if (!isset($status)) {
            $status = json_decode($this->getPlatform()->exec('sudo /usr/libexec/nethserver/openvpn-tunnels list')->getOutput(), true);
        }
        return $status;
    }

    public function prepareViewForColumnPort(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        # valid protocl values are udp, tcp-server
        return $values['Port'] . " (" . strtoupper(substr($values['Protocol'],0,3)) . ")";
    }

    public function prepareViewForColumnNetwork(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        if (isset($values['Network']) && $values['Network']) {
            return $values['Network'];
        } else {
            return $values['LocalPeer'] . " - ". $values['RemotePeer'];
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
}
