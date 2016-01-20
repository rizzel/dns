<?php $host = $_SERVER['HTTP_HOST']; ?>
<h4>Updating a content field</h4>
<p>
    You can update a content field with the following URLs.
    The <var>CONTENT_FIELD</var> is always optional. If omitted, the
    IP is used which the update request originated from.
    <br/>
    The password for each record can be shown via the "Click" in the
    password column above.
</p>
<p>
    Possible URLs:
<blockquote>http://<?php echo $host; ?>/ip?<var>RECORDID</var>;<var>PASSWORD</var>;<var>CONTENT_FIELD</var></blockquote>
<blockquote>http://<?php echo $host; ?>/ip4?<var>RECORDNAME</var>;<var>PASSWORD</var>;<var>CONTENT_FIELD</var>
</blockquote>
<blockquote>http://<?php echo $host; ?>/ip6?<var>RECORDNAME</var>;<var>PASSWORD</var>;<var>CONTENT_FIELD</var>
</blockquote>
<blockquote>http://<?php echo $host; ?>/inadyn4?<var>PASSWORT</var>;<var>RECORDNAME</var></blockquote>
<blockquote>http://<?php echo $host; ?>/inadyn6?<var>PASSWORT</var>;<var>RECORDNAME</var></blockquote>
<blockquote>http://<?php echo $host; ?>/update.php?recordid=<var>RECORDID</var>&password=<var>PASSWORD</var>&content=<var>CONTENT_FIELD</var></blockquote>
<blockquote>http://<?php echo $host; ?>/update.php?recordname=<var>RECORDNAME</var>&addrtype=ipv4&password=<var>PASSWORD</var>&content=<var>CONTENT_FIELD</var></blockquote>
<blockquote>http://<?php echo $host; ?>/update.php?recordname=<var>RECORDNAME</var>&addrtype=ipv6&password=<var>PASSWORD</var>&content=<var>CONTENT_FIELD</var></blockquote>
</p>
<p>
    The addition of additional URL-Schemata is possible.
    <br/>
    An example update crontab row would be (updated every 5 minutes):
<blockquote>*/5 * * * * nobody wget -qO /dev/null 'http://<?php echo $host; ?>
    /ip4?<var>RECORDNAME</var>;<var>PASSWORD</var>'
</blockquote>
</p>
<p>
    An example Fritz!Box configuration would be:
<blockquote>
    Dynamic DNS-Provider: Custom<br/>
    Update-URL: http://ggdns.de/ip?<var>RECORDID</var>;&lt;pass&gt;;&lt;ipaddr&gt;<br/>
    Domainname: <var>RECORDNAME</var><br/>
    Username: 1<br/>
    Password: <var>PASSWORD</var>
</blockquote>
</p>
<p>
    An example INADYN configuration would be:
<blockquote>
    dyndns_system custom@http_svr_basic_auth<br/>
    ip_server_name <?php echo $host; ?> /myip<br/>
    dyndns_server_name <?php echo $host; ?><br/>
    dyndns_server_url /inadyn4?<var>PASSWORD</var>;<br/>
    alias <var>RECORDNAME</var>
</blockquote>
To run inadyn as a service with Windows the following software is required:
<ul>
    <li><a href="/download/srvany.rar">SRVANY</a> to run inadyn as a service</li>
    <li><a href="/download/configure_inadyn_service_dns.reg">Registry-Entry</a>
        to configure the SRVANY. The paths in the file have to be adapted.
    </li>
</ul>
</p>
<p>
    The DDWRT Frontend has a inadyn section. The following has to be set
    in Setup&rarr;DDNS:
<table border="1">
    <tr>
        <th>Field</th>
        <th>Content</th>
    </tr>
    <tr>
        <td>DDNS Service</td>
        <td>Custom</td>
    </tr>
    <tr>
        <td>DYNDNS Server</td>
        <td><?php echo $host; ?></td>
    </tr>
    <tr>
        <td>User Name</td>
        <td><var>WHATEVER</var></td>
    </tr>
    <tr>
        <td>Password</td>
        <td><var>WHATEVER</var></td>
    </tr>
    <tr>
        <td>Host Name</td>
        <td><var>RECORDNAME</var></td>
    </tr>
    <tr>
        <td>URL</td>
        <td>/inadyn4?<var>PASSWORD</var>;</td>
    </tr>
</table>
</p>
<p>
    In openwrt the following configuration can be used with the package <em>luci-app-ddns</em>:
<table border="1">
    <tr>
        <th>Field</th>
        <th>Content</th>
    </tr>
    <tr>
        <td>Service</td>
        <td>-- custom --</td>
    </tr>
    <tr>
        <td>Custom update-URL</td>
        <td>http://<?php echo $host; ?>/ip4?[DOMAIN];[PASSWORD]</td>
    </tr>
    <tr>
        <td>Hostname</td>
        <td><var>RECORDNAME</var></td>
    </tr>
    <tr>
        <td>Password</td>
        <td><var>PASSWORD</var></td>
    </tr>
</table>
<em>Source of IP address</em> can be set to <a href="/myip" target="_blank">this URL</a>.
</p>
<p>
    A list of other update-clients is
    <a href="http://freedns.afraid.org/scripts/freedns.clients.php" target="_blank">here</a>.
    However, not all clients work with <?php echo $host; ?>.
</p>

<h4>Client IP</h4>
<p>
    The public IP address of the client can be queried via
<blockquote>http://<?php echo $host; ?>/myip</blockquote>
.
</p>

<h4>Creation of Records</h4>
<p>
    Three different types of records can be created for now.
    Every record has to be linked to a domain.
</p>
<p>
    Rules for creating Domain-Names:
<ul>
    <li>A Domain-Name may not start with a "." (e.g. "<span class="nicht">.</span>test.<?php echo $host; ?>")</li>
    <li>A Domain-Name may not end with a "." (e.g. "test.<?php echo $host; ?><span class="nicht">.</span>")</li>
    <li>A subdomain may not start with a "-" (e.g. "<span class="nicht">-</span>test.<?php echo $host; ?>"
        or "a.<span class="nicht">-</span>test.<?php echo $host; ?>")
    </li>
    <li>A subdomain may not end with a "-" (e.g. "test<span class="nicht">-</span>.<?php echo $host; ?>"
        or "a.test<span class="nicht">-</span>.<?php echo $host; ?>")
    </li>
    <li>A domain may only contain the following characters: 0-9, a-z, A-Z, ., - (no commas...).</li>
</ul>
</p>
<p>
    An A record links a domain name to a specific IPv4 address.
    The name-field contains the requested domain name.
    <br/>
    The content field contains the IPv4 address the domain name should point to.
    The default is the IP address this page has been called from.
    <br/>
    The update password is used to update a content field via REST-API.
    This is stored in plain text and can be shown anytime.
    <br/>
    The TTL specifies, how long the domain name is cached.
</p>
<p>
    A AAAA record links a domain name to a specific IPv6 address.
    This record is equivalent to the A record.
</p>
<p>
    A CNAME record is an alias for a domain (like a symlink).
    The name-field contains the alias, the content-field the original domain the alias is linked to.
    This record can not be updated automatically, hence it has no password.
    As this record is not updated automatically its TTL can be set higher than for A or AAAA records.
</p>
