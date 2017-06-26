===================
OpenVPN roadwarrior
===================

Allows users around the world to connect to the this server
with a Virtual Private Network (VPN).
Connected clients will be able to access local services and networks.

Server
======

Configure the OpenVPN server for roadwarrior clients and net2net tunnels.

When creating a net2net tunnel, you should choose one of the server as master.
The master must have the roadwarrior server enabled.
The slave must be configured using the :guilabel:`Client` tab.

Enable roadwarrior server
    Enable roadwarrior OpenVPN server listening on default UDP port 1194.
    The server will handle multiple client connections.

Authentication mode
    Select the desired authentication method.
    Three methods are available:

    * Username and password: choose it when you want to use a system user
    * Certificate: choose when creating a net2net configuration
    * User, password and certificate: this is the most secure combination.
      The user must be a system user.

Routed mode
    (Preferred mode). Must be used if the VPN must carry only IP traffic.
    VPN clients will have a different IP address then LAN clients.

    The OpenVPN server will reply to remote DHCP requests using the configured network:

    * Network: VPN reserved network. Eg: 10.1.1.0
    * Netmask: VPN network mask. Eg: 255.255.255.0

Bridged mode
    Must be used if the VPN must carry IP and non-IP (eg. NetBios) traffic.
    When this mode is selected, VPN clients will have an IP from the LAN network segment.

    The OpenVPN server will reply to remote DHCP.
    If a DHCP server is already present inside the LAN, make sure to
    choose a free range which will not collide with current DHCP server configuration.

    * Bridge: associated bridge interface
    * IP range start: first IP address of the range
    * IP range stop: last IP address of the range

Route all client traffic through VPN
    All VPN clients will use this server as default gateway.
    Available only in routed mode.

Allow client-to-client network traffic
    All VPN clients will be able to exchange network traffic between themselves.
    Available only in routed mode.

Enable LZO compression
    Enable LZO compression. This options must be enabled both on client and server.

Push all static routes
    If enabled, push to client all static routes configured for the server.

DHCP options
    Extra options for OpenVPN DHCP server.
   
    * Domain: if left blank, the Domain of the server will be used
    * DNS, WINS, NBDD: if left blank, the server configured DNS will be used

Contact this server on public IP / host
    Specify a comma separated list of IP and host names that the OpenVPN
    clients will attempt to contact this server.  If this value is
    changed the client configuration must be downloaded again.

UDP port
    Change server UDP port. Default is 1194.


Roadwarrior accounts
====================

The account tab allows to manage users used for
OpenVPN connections to the local roadwarrior server. Users can be normal
system users or dedicated exclusively to the VPN service (without standard services like email).

Create new
----------

Allow the creation of a new user. For each user, the system
creates a x509 certificate.

VPN only
    The name used for VPN access. It can contain only
    lowercase letters, numbers, hyphens, underscores (_) and
    must begin with a lowercase letter. For example "luisa",
    "Jsmith" and "liu-jo" is a valid user name, while "4Friends"
    "Franco Blacks" and "aldo / mistake" are not.

Reserved IP
    The roadwarrior server act as a DHCP server for the VPN.
    Choose a static IP to assign to this account.

System User
    Enable VPN access for a user already existing in the system.
    The user can be selected from the drop-down list.

Remote network
    Enter this information only when you want to create a nt2net VPN.
    These fields are used by the local server to correctly create
    routes to the remote network.

    * Network Address: the network address of the remote network. Eg: 10.0.0.0 
    * Netmask: Netmask of the remote network. Eg: 255.255.255.0


