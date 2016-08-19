# SOMA FM reverse proxy server
This is a reverse proxy server for the San Francisco based non-profit internet radio station SOMA FM.

## Reverse Proxy
From [Wikipedia - Reverse proxy](https://en.wikipedia.org/wiki/Reverse_proxy)
>In computer networks, a reverse proxy is a type of proxy server that retrieves resources on behalf of a client from one or more
servers. These resources are then returned to the client as if they originated from the proxy server itself.[1] While a forward proxy
acts as an intermediary for its associated clients to contact any server, a reverse proxy acts as an intermediary for its associated
servers to be contacted by any client.

>Quite often, popular web servers utilize reverse-proxying functionality, acting as shields for application frameworks with weaker
HTTP capabilities.

![Wikipedia - reverse proxy](https://upload.wikimedia.org/wikipedia/commons/6/67/Reverse_proxy_h2g2bob.svg)

### Apache `mod_proxy`
Apache includes a [`mod_proxy`](https://httpd.apache.org/docs/current/mod/mod_proxy.html) module that provides the basic forward
and reverse proxy features. The main directives required depend on whether the proxy is forward or reverse:

#### Reverse Proxy Directives

* [`ProxyPass`](https://httpd.apache.org/docs/trunk/mod/mod_proxy.html#proxypass) - Maps remote serverse into the local server URL
space.
* [`ProxyPassReverse`](https://httpd.apache.org/docs/trunk/mod/mod_proxy.html#proxypassreverse) - Adjusts the URL in HTTP response
headers sent from a reverse proxied server.

#### Configuration

Configure the reverse proxy in the Apache `httpd.conf` file. This file may called different names, _eg_: `apache.conf` or you may
include it from another file _eg_:`sites.conf` by with `Include "sites.conf"`. On some server shares, like
[alwaysdata](https://www.alwaysdata.com/en/), there may be an [admin page](https://admin.alwaysdata.com/) where you may be able to
add a custom configuration to your Apache sites.

For alwaysdata from the admin page select [**Site**](https://admin.alwaysdata.com/site/) and click the gear icon under **Edit** and
change the site to **Apache custom**. Then in the **Virtual host directives** enter your proxy directives.

```apache
ProxyPass /somafm/indiepop http://ice1.somafm.com/indiepop-128-mp3
ProxyPassReverse /somafm/indiepop http://ice1.somafm.com/indiepop-128-mp3

ProxyPass /somafm/poptron http://ice1.somafm.com/poptron-128-mp3
ProxyPassReverse /somafm/poptron http://ice1.somafm.com/poptron-128-mp3

DocumentRoot /home/breaking-bytes/www
DirectoryIndex index.html
<Directory />
    AllowOverride All
</Directory>
```
