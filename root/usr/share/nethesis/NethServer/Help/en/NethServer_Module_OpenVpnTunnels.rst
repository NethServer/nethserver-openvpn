===============
OpenVPN tunnels
===============

OpenVPN tunnels allow connecting two remote networks (net2net).


Tunnel servers
==============

A tunnel server is and instance of OpenVPN listening for
incoming client connections.
After the configuration of a tunnel server, the administrator can use
the "Download client configuration" button to download
a pre-compiled configuration.
The downloaded configuration can be later uploaded to the client
firewall.

Tunnel name
    Unique name to identify the VPN.

Public IPs and/or public FQDN
    List of public IPs or host names.
    The generated client configuration will use this value
    as address for the server.

Port
    Port of the VPN server.

VPN network
    Network address used for VPN clients.
    The network should not be already used by any local
    network interface or route. 

Pre-shared key
    Random key to be used as shared secret, but be 2048 bit long.

Local networks
    List of local networks which will be available for remote hosts
    behind the client firewall.

Remote networks
    List of remote networks behind the client firewall
    which will be available to local hosts.

Protocol
    Protocol used by the tunnel, UDP is the recommended one.
    Must be the same in both client and server.

Enable LZO compression
    LZO compression must be the same in both client and server.

Cipher
    As default, OpenVPN will try to negotiate the best cipher.
    Change it only if the client has known limitations.

Download client configuration
    Download a text file which contains the client configuration.


Tunnel clients
==============

The VPN client allows you to connect the server to another OpenVPN server
in order to create a net2net VPN.  

Tunnel name
    Unique name to identify the VPN.

Remote hosts
     Lit of host names or IP addresses of OpenVPN remote server.

Remote port
     UDP port of remote server. Usually the port is 1194.

Authentication
    Choose the authentication configured in the server.

    * Certificate: paste the content of the certificate inside the text area.
      The text must contain both client and CA (Certification Authority) certificates.
    * User, password and certificate: insert user name password and
      past the content of  both client and CA (Certification Authority) certificates
    * Pre-shared key: encryption key shared between client and server (unsafe)

Enable LZO compression
    LZO compression must be the same in both client and server.

Mode
    Choose the same mode configured in the server.

    * Routed: VPN hosts will be in a separated network
    * Bridged: VPN host will be in the same LAN of the remote server

Cipher
    As default, OpenVPN will try to negotiate the best cipher.
    Change it only if the server has known limitations.
