<div id="admin">
    <h3><?php echo pgettext("TemplateHeading", "Administration"); ?></h3>
    <h4><?php echo pgettext("TemplateHeading", "Users"); ?></h4>
    <input type="button" id="user_add_button" value="<?php echo pgettext("Button", "Add User"); ?>"/>

    <div id="user_add" class="hider">
        <label for="user_add_username"><?php echo pgettext("AddUser", "Name"); ?>:</label>
        <input type="text" id="user_add_username"/>
        <br/>
        <label for="user_add_password1"><?php echo pgettext("AddUser", "Password"); ?>:</label>
        <input type="password" id="user_add_password1"/>
        <input type="password" id="user_add_password2"/>
		<span id="user_add_default">
			<?php echo pgettext("AddUserDefaultPassword", "Default"); ?>: "<span id="user_add_password_default"></span>"
		</span>
        <span id="user_add_nomatch"
              style="display: none"><?php echo pgettext("AddUserPasswordNoMatch", "passwords differ"); ?></span>
        <br/>
        <label for="user_add_email"><?php echo pgettext("AddUser", "Email"); ?>:</label>
        <input type="text" id="user_add_email"/>
        <br/>
        <label for="user_add_level"><?php echo pgettext("AddUser", "Level"); ?>:</label>
        <select id="user_add_level">
            <option value="nobody"><?php echo pgettext("UserLevel", "nobody"); ?></option>
            <option value="user"><?php echo pgettext("UserLevel", "user"); ?></option>
            <option value="admin"><?php echo pgettext("UserLevel", "admin"); ?></option>
        </select>
        <br/>
        <input type="button" id="user_add_submit" value="<?php echo pgettext("UserAddConfirm", "Add"); ?>"/>
    </div>
    <table id="users" border="1">
        <tr>
            <th><?php echo pgettext("UserListTableHeader", "User"); ?></th>
            <th><?php echo pgettext("UserListTableHeader", "Level"); ?></th>
            <th><?php echo pgettext("UserListTableHeader", "Email"); ?></th>
            <th><?php echo pgettext("UserListTableHeader", "Records"); ?></th>
            <th><?php echo pgettext("UserListTableHeader", "Operation"); ?></th>
        </tr>
    </table>
    <input type="button" id="userListReload" value="<?php echo pgettext("UserList", "Reload list"); ?>"/>

    <div id="userListLevelPopup" class="popup" style="display: none">
        <label for="userListLevel"><?php echo pgettext("AddUser", "Level"); ?>:</label>
        <select id="userListLevel">
            <option><?php echo pgettext("UserLevel", "nobody"); ?></option>
            <option><?php echo pgettext("UserLevel", "user"); ?></option>
            <option><?php echo pgettext("UserLevel", "admin"); ?></option>
        </select>
        <br/>
        <input type="button" id="userListLevelSubmit" value="<?php echo _("OK"); ?>"/>
        <input type="button" class="popupAbort" value="<?php echo _("Abort"); ?>"/>
    </div>

    <h4><?php echo pgettext("TemplateHeading", "Domains"); ?></h4>
    <input type="button" id="domain_add_button" value="<?php echo pgettext("AddDomain", "Add Domain"); ?>"/>

    <div id="domain_add" class="hider">
        <label for="domain_add_name"><?php echo pgettext("AddDomain", "Name"); ?>:</label>
        <input type="text" id="domain_add_name"/>
        <br/>
        <label for="domain_add_type"><?php echo pgettext("AddDomain", "Type"); ?>:</label>
        <select id="domain_add_type">
            <option>NATIVE</option>
        </select>
        <br/>
        <label for="domain_add_soa"><?php echo _("Soa line containing '%MasterDNS% %Email%'"); ?>:</label>
        <input type="text" id="domain_add_soa" size="64"/>
        <br/>
        <input type="button" id="domain_add_submit" value="<?php echo pgettext("AddDomain", "Add"); ?>"/>
    </div>
    <table id="domains" border="1">
        <tr>
            <th><?php echo pgettext("DomainListTableHeader", "ID"); ?></th>
            <th><?php echo pgettext("DomainListTableHeader", "Name"); ?></th>
            <th><?php echo pgettext("DomainListTableHeader", "Type"); ?></th>
            <th><?php echo pgettext("DomainListTableHeader", "Last Check"); ?></th>
            <th><?php echo pgettext("DomainListTableHeader", "Special Records"); ?></th>
            <th><?php echo pgettext("DomainListTableHeader", "Operation"); ?></th>
        </tr>
    </table>
    <input type="button" id="domainListReload" value="<?php echo pgettext("DomainList", "Reload List"); ?>"/>

    <div id="domainsListNamePopup" class="popup" style="display: none">
        <label for="domainsListName"><?php echo pgettext("DomainModify", "Name"); ?>:</label>
        <input type="text" id="domainsListName"/>
        <br/>
        <input type="button" id="domainsListNameSubmit" value="<?php echo _("OK"); ?>"/>
        <input type="button" class="popupAbort" value="<?php echo _("Abort"); ?>"/>
    </div>
    <div id="domainsListRecordPopup" class="popup" style="display: none">
        <label for="domainsListRecordName"><?php echo pgettext("RecordAdd", "Name"); ?>:</label>
        <input type="text" id="domainsListRecordName"/>
        <br/>
        <label for="domainsListRecordType"><?php echo pgettext("RecordAdd", "Type"); ?>:</label>
        <select id="domainsListRecordType">
            <option>A</option>
            <option>AAAA</option>
            <option value="CERT">CERT (pdns2.9.21)</option>
            <option>CNAME</option>
            <option value="DNSKEY">DNSKEY (pdns2.9.21)</option>
            <option value="DS">DS (pdns 2.9.21)</option>
            <option>HINFO</option>
            <option value="KEY">KEY (pdns 2.9.21)</option>
            <option>LOC</option>
            <option>MX</option>
            <option>NAPTR</option>
            <option>NS</option>
            <option value="NSEC">NSEC (pdns 2.9.21)</option>
            <option>PTR</option>
            <option>RP</option>
            <option value="RRSIG">RRSIG (pdns 2.9.21)</option>
            <option>SOA</option>
            <option>SPF</option>
            <option>SSHFP</option>
            <option>SRV</option>
            <option>TXT</option>
        </select>
        <span><?php echo pgettext("ReferenceToDocumenationPrefix", "See"); ?> <a
                href="http://doc.powerdns.com/html/types.html"
                target="_blank"><?php echo pgettext("ReferenceToDocumentationLink", "here"); ?></a>
            <?php echo pgettext("ReferenceToDocumentationSuffix", "."); ?>
        </span>
        <br/>
        <label for="domainsListRecordContent"><?php echo pgettext("RecordAdd", "Content"); ?>:</label>
        <input type="text" id="domainsListRecordContent" size="32"/>
        <br/>
        <label for="domainsListRecordTTL"><?php echo pgettext("RecordAdd", "TTL"); ?>:</label>
        <input type="number" min="5" id="domainsListRecordTTL"/>
        <br/>
        <input type="button" id="domainsListRecordSubmit" value="<?php echo _("OK"); ?>"/>
        <input type="button" class="popupAbort" value="<?php echo _("Abort"); ?>"/>
    </div>
</div>
