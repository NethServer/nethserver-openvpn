#
# Copyright (C) 2015 Nethesis S.r.l.
# http://www.nethesis.it - nethserver@nethesis.it
#
# This script is part of NethServer.
#
# NethServer is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License,
# or any later version.
#
# NethServer is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with NethServer.  If not, see COPYING.
#

use strict;

package NethServer::Firewall::OpenVPN;
use NethServer::Firewall qw(register_callback);
use esmith::DB::db;
use esmith::util;

register_callback(\&openvpn_networks);
register_callback(\&openvpn_tunnels);

#
# Search inside roadwarrior networks
#
sub openvpn_networks
{
    my $value = shift;

    return '' if(!$value);;

    my $config_db = esmith::DB::db->open_ro('configuration');
    if( ! $config_db ) {
        return '';
    }

    my $net = $config_db->get_prop('openvpn@host-to-net', 'Network') || return '';
    my $msk = $config_db->get_prop('openvpn@host-to-net', 'Netmask') || return '';
    my $cidr = esmith::util::computeLocalNetworkShortSpec($net, $msk);

    if (Net::IPv4Addr::ipv4_in_network($cidr, $value)) {
        return 'ovpn';
    }

    return '';
}

#
# Search inside tunnel networks
#
sub openvpn_tunnels
{
    my $value = shift;

    my $vpn_db = esmith::DB::db->open_ro('vpn');
    foreach ($vpn_db->get_all_by_prop('type' => 'vpn')) {
        my $net = $_->prop('VPNRemoteNetwork') || next;
        my $msk = $_->prop('VPNRemoteNetmask') || next;
        my $cidr = esmith::util::computeLocalNetworkShortSpec($net, $msk);
        if (Net::IPv4Addr::ipv4_in_network($cidr, $value)) {
            return 'ovpn';
        }
    }

    foreach ($vpn_db->get_all_by_prop('type' => 'vpn-user')) {
        my $net = $_->prop('VPNRemoteNetwork') || next;
        my $msk = $_->prop('VPNRemoteNetmask') || next;
        my $cidr = esmith::util::computeLocalNetworkShortSpec($net, $msk);
        if (Net::IPv4Addr::ipv4_in_network($cidr, $value)) {
            return 'ovpn';
        }
    }

    foreach my $type (qw(tunnel openvpn-tunnel-server)) {
        foreach my $t ($vpn_db->get_all_by_prop('type' => $type)) {
            my $nets = $t->prop('RemoteNetworks') || next;
            foreach (split(/,/,$nets)) {
                if (Net::IPv4Addr::ipv4_in_network($_, $value)) {
                    return 'ovpn';
                }
            }
        }
    }

    return '';
}
