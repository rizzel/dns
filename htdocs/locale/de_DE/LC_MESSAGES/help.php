<?php $host = $_SERVER['HTTP_HOST']; ?>
<h4>Aktualisierung eines Content-Feldes</h4>
<p>
    Ein Content-Feld kann über eine Reihe von URLs aktualisiert werden.
    Das Content-Feld ist dabei immer optional - wird es weggelassen, wird die IP
    mit welcher die Seite aufgerufen wird als Content-Feld gesetzt.
    <br/>
    Das Passwort zu jedem Record wird im Klartext gespeichert und kann
    via Klick auf "Klick" in der Passwort-Spalte angezeigt werden.
</p>
<p>
    Möglichkeiten:
<blockquote>http://<?php echo $host; ?>/ip?<var>RECORDID</var>;<var>PASSWORT</var>;<var>CONTENT_FELD</var></blockquote>
<blockquote>http://<?php echo $host; ?>/ip4?<var>RECORDNAME</var>;<var>PASSWORT</var>;<var>CONTENT_FELD</var>
</blockquote>
<blockquote>http://<?php echo $host; ?>/ip6?<var>RECORDNAME</var>;<var>PASSWORT</var>;<var>CONTENT_FELD</var>
</blockquote>
<blockquote>http://<?php echo $host; ?>/inadyn4?<var>PASSWORT</var>;<var>RECORDNAME</var></blockquote>
<blockquote>http://<?php echo $host; ?>/inadyn6?<var>PASSWORT</var>;<var>RECORDNAME</var></blockquote>
</p>
<p>
    Werden andere URL-Schemata benötigt, so können diese noch hinzugefügt werden.
    <br/>
    Eine Beispiel Crontab-Zeile für ein Update alle 5 Minuten sieht so aus:
<blockquote>*/5 * * * * nobody wget -qO /dev/null 'http://<?php echo $host; ?>
    /ip4?<var>RECORDNAME</var>;<var>PASSWORT</var>'
</blockquote>
</p>
<p>
    Eine Beispiel Fritz!Box-Konfiguration siehts so aus:
<blockquote>
    Dynamic DNS-Anbieter: Benutzerdefiniert<br/>
    Update-URL: http://ggdns.de/ip?<var>RECORDID</var>;&lt;pass&gt;;&lt;ipaddr&gt;<br/>
    Domainname: <var>RECORDNAME</var><br/>
    Benutzername: 1<br/>
    Kennwort: <var>PASSWORT</var>
</blockquote>
</p>
<p>
    Eine Beispiel INADYN-Konfiguration sieht so aus:
<blockquote>
    dyndns_system custom@http_svr_basic_auth<br/>
    ip_server_name <?php echo $host; ?> /myip<br/>
    dyndns_server_name <?php echo $host; ?><br/>
    dyndns_server_url /inadyn4?<var>PASSWORT</var>;<br/>
    alias <var>RECORDNAME</var>
</blockquote>
Um inadyn unter Windows nutzen zu können wird noch folgendes benötigt:
<ul>
    <li><a href="/download/srvany.rar">SRVANY</a> um inadyn als Service laufen zu lassen</li>
    <li><a href="/download/configure_inadyn_service_dns.reg">Registry-Eintrag</a> zur Konfiguration des Dienstes. In
        dieser Datein müssen die Pfade noch angepasst werden.
    </li>
</ul>
</p>
<p>
    Im DD-WRT Frontend muss man Folgendes unter Setup&rarr;DDNS eintragen um
    obige inadyn-Konfiguration zu erreichen:
<table border="1">
    <tr>
        <th>Feld</th>
        <th>Inhalt</th>
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
        <td><var>EGAL</var></td>
    </tr>
    <tr>
        <td>Password</td>
        <td><var>EGAL</var></td>
    </tr>
    <tr>
        <td>Host Name</td>
        <td><var>RECORDNAME</var></td>
    </tr>
    <tr>
        <td>URL</td>
        <td>/inadyn4?<var>PASSWORT</var>;</td>
    </tr>
</table>
</p>
<p>
    Für openwrt kann folgende Konfiguration mit dem Paket <em>luci-app-ddns</em> verwendet werden:
<table border="1">
    <tr>
        <th>Feld</th>
        <th>Inhalt</th>
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
        <td><var>PASSWORT</var></td>
    </tr>
</table>
Für <em>Source of IP address</em> kann <a href="/myip" target="_blank">diese URL</a> verwendet werden.
</p>
<p>
    Eine Liste von Update-Clients ist
    <a href="http://freedns.afraid.org/scripts/freedns.clients.php" target="_blank">hier</a>
    zu finden. Es funktionieren jedoch nicht alle mit <?php echo $host; ?>.
</p>

<h4>Client IP</h4>
<p>
    Die IP des Clients aus Sicht des Webservers <?php echo $host; ?> kann via
<blockquote>http://<?php echo $host; ?>/myip</blockquote>
abgefragt werden.
</p>

<h4>Erzeugung von Records</h4>
<p>
    Es gibt 3 verschiedene Records die gesetzt werden können.
    Jedes dieser Records muss einer Domain zugeordnet werden.
</p>
<p>
    Regeln für Domain-Namen:
<ul>
    <li>Ein Domain-Name darf nicht mit einem "." beginnen (z.B. "<span class="nicht">.</span>test.<?php echo $host; ?>")
    </li>
    <li>Ein Domain-Name darf nicht mit einem "." aufhören (z.B. "test.<?php echo $host; ?><span class="nicht">.</span>")
    </li>
    <li>Ein Domain-Name darf am Anfang einer Subdomain kein "-" haben (z.B. "<span
            class="nicht">-</span>test.<?php echo $host; ?>"
        oder "a.<span class="nicht">-</span>test.<?php echo $host; ?>")
    </li>
    <li>Ein Domain-Name darf am Ende einer Subdomain kein "-" haben (z.B. "test<span
            class="nicht">-</span>.<?php echo $host; ?>"
        oder "a.test<span class="nicht">-</span>.<?php echo $host; ?>")
    </li>
    <li>Ein Domain-Name darf nur aus folgenden Zeichen bestehen: 0-9, a-z, A-Z, ., - (keine Kommas...).</li>
</ul>
</p>
<p>
    Ein A Record gibt einem Domain-Namen eine IPv4-Adresse. Im Name-Feld wird der gewünschte
    Domain-Name eingegeben. Wird die Domain (z.B. <?php echo $host; ?>) weggelassen, wird diese automatisch
    angehängt.
    <br/>
    Das Content-Feld enthält die IPv4-Adresse auf welche die Domain auflösen soll.
    Per Default steht die IP-Adresse über welche die Homepage aufgerufen wird.
    <br/>
    Das Update-Passwort wird zur Aktualisierung des Content-Feldes via Script genutzt.
    Dies wird im Klartext gespeichert und kann jederzeit in der Tabelle angezeigt werden.
    <br/>
    Die TTL gibt an, wie lange eine Domain im Cache liegt bis der DNS wieder angefragt
    werden muss.
</p>
<p>
    Ein AAAA Record weist einem Domain-Namen eine IPv6-Adresse zu.
    Dieser Record ist analog zum A Record aufgebaut.
</p>
<p>
    Ein CNAME Record ist ein Alias für eine Domain.
    Im Name-Feld wird der Alias angegeben, im Content-Feld die Original-Domain auf welche verwiesen wird.
    Dieser Record kann nicht automatisch aktualisiert werden - deswegen auch kein Passwort.
    Dies hat keinen technischen Grund - es gibt nur keinen mir bekannten Anwendungsfall.
    Da dieser Record nicht automatisch aktualisiert wird kann die TTL recht hoch sein.
</p>
