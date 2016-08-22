# SOMA FM reverse proxy server

This is a reverse proxy server for the San Francisco based non-profit internet radio station SOMA FM.

## Reverse Proxy

From [Wikipedia - Reverse proxy](https://en.wikipedia.org/wiki/Reverse_proxy):

>In computer networks, a reverse proxy is a type of proxy server that retrieves resources on behalf of a client from one or more
servers. These resources are then returned to the client as if they originated from the proxy server itself.<sup>[1][1]</sup> While a forward proxy
acts as an intermediary for its associated clients to contact any server, a reverse proxy acts as an intermediary for its associated
servers to be contacted by any client.

>Quite often, popular web servers utilize reverse-proxying functionality, acting as shields for application frameworks with weaker
HTTP capabilities.

[1]: http://httpd.apache.org/docs/current/mod/mod_proxy.html#forwardreverse

![Wikipedia - reverse proxy](https://upload.wikimedia.org/wikipedia/commons/6/67/Reverse_proxy_h2g2bob.svg)

### Apache `mod_proxy`

Apache includes a [`mod_proxy`](https://httpd.apache.org/docs/current/mod/mod_proxy.html) module that provides the basic forward
and reverse proxy features. The main directives required depend on whether the proxy is forward or reverse:

#### Reverse Proxy Directives

* [`ProxyPass`](https://httpd.apache.org/docs/trunk/mod/mod_proxy.html#proxypass) - Maps remote servers into the local server URL
space.
* [`ProxyPassReverse`](https://httpd.apache.org/docs/trunk/mod/mod_proxy.html#proxypassreverse) - Adjusts the URL in HTTP response
headers sent from a reverse proxied server.

#### Configuration

Configure the reverse proxy in the Apache `httpd.conf` file. This file may called different names, _eg_: `apache.conf` or you may
include it from another file _eg_:`sites.conf` with `Include "sites.conf"`. On some server shares, like
[alwaysdata](https://www.alwaysdata.com/en/), there may be an [admin page](https://admin.alwaysdata.com/) where you may be able to
add a custom configuration to your Apache sites.

For alwaysdata from the admin page select [**Site**](https://admin.alwaysdata.com/site/) and click the gear icon under **Edit** and
change the site to **Apache custom**. Then in the **Virtual host directives** enter your proxy directives.

```apache
ProxyPass /somafm/indiepop http://ice1.somafm.com/indiepop-128-mp3
ProxyPassReverse /somafm/indiepop http://ice1.somafm.com/indiepop-128-mp3

ProxyPass /somafm/poptron http://ice1.somafm.com/poptron-128-mp3
ProxyPassReverse /somafm/poptron http://ice1.somafm.com/poptron-128-mp3

ProxyPass /songhistory/indiepop http://somafm.com/indiepop/songhistory.html
ProxyPassReverse /songhistory/indiepop http://somafm.com/indiepop/songhistory.html

ProxyPass /songhistory/poptron http://somafm.com/poptron/songhistory.html
ProxyPassReverse /songhistory/poptron http://somafm.com/poptron/songhistory.html

DocumentRoot /home/breaking-bytes/somafm/www
DirectoryIndex index.html
<Directory />
    AllowOverride All
</Directory>
```

Not included here are the `<VirtualHost *></VirtualHost>` and `ServerName` directives, which were set by alwaysdata.

These directives:

* set the root URL `/` to the `/home/breaking-bytes/somafm/www` directory on the server,
* will serve any document found called `index.html`,
* allow `.htaccess` in any folder to [override all](https://httpd.apache.org/docs/current/mod/core.html#allowoverride),
* reverse and pass the SOMA FM Indie Pop icecast as `/somafm/indiepop`,
* PopTron icecast as `/somafm/poptron`,
* reverse and pass the Indie Pop song history as `/songhistory/indiepop` and
* PopTron song history as `/songhistory/poptron`.

Therefore, anyone pointing their browser to
[breaking-bytes.alwaysdata.net/somafm/indiepop](http://breaking-bytes.alwaysdata.net/somafm/indiepop) can listen to the stream
as if they were on the SOMA FM website listening to Indie Pop Rocks!

#### Forward Proxy

From [Wikipedia - Proxy server](https://en.wikipedia.org/wiki/Proxy_server):

>A forward proxy is an Internet-facing proxy used to retrieve from a wide range of sources (in most cases anywhere on the Internet).

From [Apache - `mod_proxy`](https://httpd.apache.org/docs/current/mod/mod_proxy.html):

>An ordinary forward proxy is an intermediate server that sits between the client and the origin server. In order to get content from
the origin server, the client sends a request to the proxy naming the origin server as the target. The proxy then requests the content
from the origin server and returns it to the client. The client must be specially configured to use the forward proxy to access other
sites.

## Static Site

To make the website look good I use [Bootstrap](http://getbootstrap.com/) to create a
[navbar](http://getbootstrap.com/components/#navbar) with buttons to each station and a
[jumbotron](http://getbootstrap.com/components/#jumbotron) to say something about the website. I used cdn to get the Bootstrap
and JQuery resources. The markup for this is in [`index.html`](./www/index.html).

## Song History Application

### Reverse Proxy HTML URL Mapping

The reverse proxy retrieves the song history, but the links on the page are all wrong and the scripts on the pages don't run. The reverse proxy has mapped all of the links to this server. The Apache module
[`mod_proxy_html`](https://httpd.apache.org/docs/current/mod/mod_proxy_html.html) can fix these URLs by setting
[`ProxyHTMLEnable On`](https://httpd.apache.org/docs/current/mod/mod_proxy_html.html#proxyhtmlenable) and entering maps of links with
[`ProxyHTMLURLMap`](https://httpd.apache.org/docs/current/mod/mod_proxy_html.html#proxyhtmlurlmap). If available Apache
[`mod_xml2enc`](https://httpd.apache.org/docs/current/mod/mod_xml2enc.html) provides enhanced support for markup. But this only works
if you can load those modules. Unfortunately, they were not available until Apache-2.4, so if you are using Apache-2.2, you'll have
to find another solution.

### FastCGI Application & WSGI Server

I also can't use JavaScript to scrape the song history from SOMA FM, because cross-domain is normally blocked by most browsers.
Even if I could find a work around for cross-domain requests, the song history may still be blocked for remote users. So I need
a server side solution like a Python application that can get the song history from SOMA FM and dynamically re-render it from
this server. On my server I can use [FastCGI](https://en.wikipedia.org/wiki/FastCGI) to execute a Python script. The Python script
uses the Python [flup package](https://www.saddi.com/software/flup/) to start an
[WSGI Server](https://en.wikipedia.org/wiki/Web_Server_Gateway_Interface).

#### Installations

I don't have root on the server share, so I use [cURL](https://curl.haxx.se/) to download the tarballs from the
[Cheese Shop](https://pypi.python.org/pypi), extract them using [`tar -xf <archive>`](https://www.gnu.org/software/tar/)
and install them using the
[alternate `--user` install scheme](https://docs.python.org/2/install/#alternate-installation-the-user-scheme). The
[`pip`](https://pip.pypa.io/en/stable/) version on my server share is too old, so I use
[distutils](https://docs.python.org/2.7/library/distutils.html), _ie_: `~$ python setup.py install --user` in the extracted
package distribution folder. This installs the packages into `~/.local/lib/python2.6/site-packages` which is automatically
included on my `PYTHONPATH`.

* [flup](https://pypi.python.org/pypi/flup/1.0.2)
* [Requests](https://pypi.python.org/pypi/requests)
* [BeautifulSoup4](https://pypi.python.org/pypi/beautifulsoup4)

#### FCGI Configuration

Apache [`mod_fcgid`](http://httpd.apache.org/mod_fcgid/mod/mod_fcgid.html) has the directives that control how to setup FastCGI
on Apache HTTPD. The following settings were precofigured in the global directives section of the custom Apache configuration on
the alwaysdata admin site page.

```apache
# mod_fcgid
LoadModule fcgid_module /usr/lib/apache2/modules/mod_fcgid.so
SocketPath run/fcgidsock

FcgidMaxProcesses           20
FcgidMinProcessesPerClass   1
FcgidMaxProcessesPerClass   20
FcgidMaxRequestsPerProcess  3000
FcgidBusyTimeout            900
FcgidIOTimeout              900
FcgidOutputBufferSize       131072
FcgidMaxRequestInMem        524288
FcgidMaxRequestLen          1073741824

FcgidInitialEnv             LANG en_US.UTF-8
FcgidInitialEnv             LC_ALL en_US.UTF-8
FcgidPassHeader             Authorization
```

These directives load the module, set the socket path, number of processes and other parameters. Since FCGI communicates with your script through a socket, you must provide a location where the pipe or socket semiphore will be stored.

There is also a deprecated `mod_fastcgi` which is different from Apache Software Foundation (ASF) [`mod_fcgid`](https://httpd.apache.org/mod_fcgid/).

There are several steps to get the ASF `mod_fcgid` to work. [Graham Dumpleton describes some of them in his blog.](http://blog.dscpl.com.au/2011/09/why-is-wsgi-deployment-under-fastcgi-so.html). Apache also has a tutorial on [serving dynamic content](http://httpd.apache.org/docs/current/howto/cgi.html). Here are some common issues:

1. The `fcgi` script and the directory it's in must be executable.
2. Use [`AddHandler`](https://httpd.apache.org/docs/current/mod/mod_mime.html#addhandler) to map the scripts to extensions.
3. Use [`Options ExecCGI`](https://httpd.apache.org/docs/2.4/mod/core.html#Options) to allow scripts to execute.
4. Use [`ScriptAlias`](https://httpd.apache.org/docs/current/mod/mod_alias.html#scriptalias) or [`Rewrite`](http://httpd.apache.org/docs/current/mod/mod_rewrite.html) to alias or rewrite a URL to a script file and to pass arguments.



