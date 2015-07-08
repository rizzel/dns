<?php

global $page;
$user = $page->currentUser;

IF ($user->isLoggedIn()): ?>
    <div id="logout">
        <span><?php echo pgettext("LoggedInGreeting", "Hello"); ?> <span id="usertext"><?php echo $user->getUserName(); ?></span> (<span
                id="userlevel"><?php echo $user->getLevel(); ?></span>)</span>
        <a href="index.php"><?php echo pgettext("Menu", "Start"); ?></a>
        <a href="user.php"><?php echo pgettext("Menu", "Settings"); ?></a>
        <?php IF ($user->getLevel() == 'admin'): ?>
            <a href="admin.php"><?php echo pgettext("Menu", "Admin"); ?></a>
        <?php ENDIF ?>
        <input type="button" id="logout_submit" value="<?php echo pgettext("LoginField", "Logout"); ?>"/>
    </div>
<?php ELSE: ?>
    <div id="login">
        <div>
            <label for="login_name"><?php echo pgettext("LoginHeading", "Login"); ?>:</label>
            <input type="text" id="login_name" placeholder="<?php echo pgettext("LoginField", "Name / Email"); ?>"/>
            <input type="password" id="login_password" placeholder="<?php echo pgettext("LoginField", "Password"); ?>"/>
            <input type="button" id="login_submit" value="<?php echo pgettext("LoginField", "Login"); ?>"/>
        </div>
        <div>
            <a href="/vergessen.php" id="vergessen"><?php echo pgettext("Menu", "Forgot password"); ?></a>
        </div>
    </div>
<?php ENDIF ?>
