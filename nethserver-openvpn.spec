Summary: NethServer OpenVPN configuration
Name: nethserver-openvpn
Version: 1.5.1
Release: 1%{?dist}
License: GPL
URL: %{url_prefix}/%{name} 
Source0: %{name}-%{version}.tar.gz
BuildArch: noarch

Requires: openvpn, bridge-utils
Requires: nethserver-firewall-base

BuildRequires: perl
BuildRequires: nethserver-devtools 

%description
NethServer OpenVPN configuration

%prep
%setup

%build
%{makedocs}
perl createlinks
mkdir -p root%{perl_vendorlib}
mkdir -p root/etc/systemd/system/openvpn@.service.d/
mv -v lib/perl/NethServer root%{perl_vendorlib}

%install
rm -rf %{buildroot}
(cd root; find . -depth -print | cpio -dump %{buildroot})
%{genfilelist} %{buildroot} \
  --dir /var/spool/openvpn 'attr(0700,srvmgr,srvmgr)' \
  --dir /var/lib/nethserver/certs/clients 'attr(0740,srvmgr,adm)' \
  --dir /var/lib/nethserver/openvpn-tunnels 'attr(0740,srvmgr,adm)' \
  --dir /etc/systemd/system/openvpn@.service.d 'attr(0755,root,root)' \
  --dir /etc/openvpn/ccd 'attr(0740,srvmgr,srvmgr)' > %{name}-%{version}-filelist
echo "%doc COPYING" >> %{name}-%{version}-filelist

%post

%preun

%files -f %{name}-%{version}-filelist
%defattr(-,root,root)
%dir %{_nseventsdir}/%{name}-update

%changelog
* Thu Jun 01 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.5.1-1
- OpenVPN: can't edit existing system users - Bug NethServer/dev#5302

* Wed May 10 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.5.0-1
- Upgrade from NS 6 via backup and restore - NethServer/dev#5234

* Thu Apr 20 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.4.9-1
- OpenVPN 2.4 breaks CRL (Certificate Revocation List) - Bug NethServer/dev#5271

* Fri Mar 31 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.4.8-1
- OpenVPN: road warrior network not added to trusted networks - NethServer/dev#5246

* Tue Mar 14 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.4.7-1
- OpenVPN: no internet access if Route all client traffic through VPN is on - Bug NethServer/dev#5238

* Wed Feb 15 2017 Davide Principi <davide.principi@nethesis.it> - 1.4.6-1
- OpenVPN: remove net2net client mode with user name and password authentication - NethServer/dev#5219

* Tue Feb 14 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.4.5-1
- OpenVPN: clients not restarted after modification - Bug NethServer/dev#5213

* Wed Jan 18 2017 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.4.4-1
- OpenVPN: client not starting at boot - Bug NethServer/dev#5198

* Fri Dec 09 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.4.3-1
- OpenVPN: bridged mode doesn't work - NethServer/dev#5173

* Tue Oct 04 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.4.2-1
- Handle advanced static routes - NethServer/dev#5079

* Tue Sep 27 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.4.1-1
- broken plugin link on nethserver-openvpn - Bug NethServer/dev#5115

* Thu Jul 07 2016 Stefano Fancello <stefano.fancello@nethesis.it> - 1.4.0-1
- First NS7 release

* Fri May 20 2016 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.3.1-1
- Openvpn n2n not working after restore - Bug #3387 [NethServer]

* Fri Nov 20 2015 Davide Principi <davide.principi@nethesis.it> - 1.3.0-1
- Public IP text field for OpenVPN - Enhancement #2635 [NethServer]

* Thu Aug 27 2015 Davide Principi <davide.principi@nethesis.it> - 1.2.4-1
- Firewall rules: support hosts within VPN zones - Enhancement #3233 [NethServer]

* Thu Jul 16 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.2.3-1
- IPsec tunnels (net2net) web interface - Feature #3194 [NethServer]

* Wed Jul 15 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.2.2-1
- Event trusted-networks-modify - Enhancement #3195 [NethServer]
- With multiple GREEN networks configured, missing the route in host-to-net.conf for OpenVPN Client - Enhancement #3189 [NethServer]

* Tue May 19 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.2.1-1
- OpenVPN fixed ip support via standard db prop - Feature #3169 [NethServer]
- OpenVPN: add UDP port to web interface - Enhancement #3164 [NethServer]
- Incorrect OpenVPN pushed DNS - Bug #3158 [NethServer]
- Network access via green lost if OpenVPN has a bad configuration - Bug #3074 [NethServer]

* Wed Mar 11 2015 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.2.0-1
- OpenVPN roadwarrior doesn't work with MultiWan configured - Bug #3061 [NethServer]
- VPN: missing firewall policy - Bug #3052 [NethServer]
- OpenVPN in bridged mode - missing gateway - Bug #3048 [NethServer]
- Adding a route should re-create vpn config files - Feature #3037 [NethServer]
- Template fragment for /etc/openvpn/host-to-net.conf add push for network added in networks db  - Bug #3018 [NethServer]

* Tue Dec 09 2014 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.1.2-1.ns6
- DNS: remove role property from dns db key - Enhancement #2915 [NethServer]

* Tue Nov 04 2014 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.1.1-1.ns6
- Firewall fallback when IPS is not running - Enhancement #2935 [NethServer]

* Wed Aug 20 2014 Davide Principi <davide.principi@nethesis.it> - 1.1.0-1.ns6
- OpenVPN: firewall rules for tun/tap devices - Enhancement #2813 [NethServer]
- IDS/IPS (snort) - Feature #1771 [NethServer]

* Wed Feb 05 2014 Davide Principi <davide.principi@nethesis.it> - 1.0.2-1.ns6
- OpenVPN Downloaded client configuration contains a bad directive - Bug #2624 [NethServer]
- OpenVPN name resolution - Bug #2525 [NethServer]
- Move admin user in LDAP DB - Feature #2492 [NethServer]
- Dashboard:  OpenVPN status widget - Enhancement #2300 [NethServer]

* Thu Oct 24 2013 Giacomo Sanchietti <giacomo.sanchietti@nethesis.it> - 1.0.1-1.ns6
- Avoid event block during bridge creation #1956

* Wed Oct 23 2013 Davide Principi <davide.principi@nethesis.it> - 1.0.0-1.ns6
- VPN: add support for OpenVPN net2net - Feature #1958 [NethServer]
- VPN: support for OpenVPN roadwarrior - Feature #1956 [NethServer]
- VPN - Feature #1763 [NethServer]


