.. --initial-header-level=2

OpenVPN
=======

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

UDP port
    Change server UDP port. Default is 1194.
