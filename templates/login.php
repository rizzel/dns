<?php

global $page;
$user = $page->user->getCurrentUser();
$loggedIn = $page->user->isLoggedIn();

if ($loggedIn) {
?>
<div id="logout">
	<span>Hallo <span id="usertext"><?php echo $user->username ?></span> (<span id="userlevel"><?php echo $user->level; ?></span>)</span>
	<a href="index.php">Start</a>
    <a href="user.php">Einstellungen</a>
	<?php if ($user->level == 'admin') { ?>
	<a href="admin.php">Admin</a>
	<?php } ?>
    <input type="button" id="logout_submit" value="Logout" />
</div>
<?php
}
else
{
?>
<div id="login">
    <label for="login_name">Login:</label>
    <input type="text" id="login_name" />
    <input type="password" id="login_password" />
    <input type="button" id="login_submit" value="Login" />
</div>
<?php
}
?>
