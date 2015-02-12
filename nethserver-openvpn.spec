Summary: NethServer OpenVPN configuration
Name: nethserver-openvpn
Version: 1.1.2
Release: 1
License: GPL
URL: %{url_prefix}/%{name} 
Source0: %{name}-%{version}.tar.gz
BuildArch: noarch

Requires: openvpn, bridge-utils
Requires: nethserver-firewall-base, nethserver-vpn

BuildRequires: perl
BuildRequires: nethserver-devtools 

%description
NethServer OpenVPN configuration

%prep
%setup

%build
perl createlinks

%install
rm -rf $RPM_BUILD_ROOT
(cd root; find . -depth -print | cpio -dump $RPM_BUILD_ROOT)
%{genfilelist} $RPM_BUILD_ROOT --dir /var/spool/openvpn 'attr(0700,srvmgr,srvmgr)' --dir /etc/openvpn/ccd 'attr(0740,srvmgr,srvmgr)' > %{name}-%{version}-filelist
echo "%doc COPYING" >> %{name}-%{version}-filelist

%post

%preun

%files -f %{name}-%{version}-filelist
%defattr(-,root,root)

%changelog
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


