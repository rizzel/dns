<?php

global $page;
$user = $page->user->getCurrentUser();
$loggedIn = $page->user->isLoggedIn();

IF ($loggedIn): ?>
    <div id="logout">
        <span>Hallo <span id="usertext"><?php echo $user->username ?></span> (<span
                id="userlevel"><?php echo $user->level; ?></span>)</span>
        <a href="index.php">Start</a>
        <a href="user.php">Einstellungen</a>
        <?php IF ($user->level == 'admin'): ?>
            <a href="admin.php">Admin</a>
        <?php ENDIF ?>
        <input type="button" id="logout_submit" value="Logout"/>
    </div>
<?php ELSE: ?>
    <div id="login">
        <div>
            <label for="login_name">Login:</label>
            <input type="text" id="login_name" placeholder="Name / Email"/>
            <input type="password" id="login_password" placeholder="Passwort"/>
            <input type="button" id="login_submit" value="Login"/>
        </div>
        <div>
            <a href="/vergessen.php" id="vergessen">Passwort Vergessen</a>
        </div>
    </div>
<?php ENDIF ?>
