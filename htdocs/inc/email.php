<?php

/**
 * Class Email
 *
 * Class for sending emails and managing tokens.
 */
class Email
{
    /**
     * @var Page The base page instance
     */
    private $page;

    /**
     * @var bool Whether this host has PEAR mail support (detect on instantiation).
     */
    private $hasPearMail = FALSE;

    function __construct($page)
    {
        $this->page = $page;
        $this->cleanUpTokens();

        $e = ini_get('error_reporting');
        ini_set('error_reporting', 0);
        if (include("Mail.php"))
            $this->hasPearMail = TRUE;
        ini_set('error_reporting', $e);
    }

    /**
     * Cleanup old tokens.
     */
    public function cleanUpTokens()
    {
        $this->page->db->query("DELETE FROM dns_users_update WHERE DATEDIFF(NOW(), requesttime) > 2");
    }

    /**
     * Send an email to the current user.
     *
     * @param string $subject The subject of the email.
     * @param string $body The body of the email.
     * @return bool Whether successful.
     */
    public function sendToCurrent($subject, $body)
    {
        return $this->sendTo($this->page->currentUser->getEmail(), $subject, $body);
    }

    /**
     * Send an email.
     *
     * @param string $to The recipient of the email.
     * @param string $subject The subject of the email.
     * @param string $body The body of the email.
     * @return bool Whether successful.
     */
    public function sendTo($to, $subject, $body)
    {
        if (strlen($to) == 0 || strpos($to, '@') === FALSE)
            return FALSE;

        $from = isset($this->page->settings->mailFrom) ?
            $this->page->settings->mailFrom : sprintf('dns@%s', $_SERVER['HTTP_HOST']);
        $subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        if (
            $this->hasPearMail &&
            isset($this->page->settings->usePearMail) &&
            isset($this->page->settings->pearConfig) &&
            isset($this->page->settings->pearBackend)
        ) {
            $e = ini_get('error_reporting');
            ini_set('error_reporting', 0);

            $mail = &Mail::factory($this->page->settings->pearBackend, $this->page->settings->pearConfig);

            $mail->send(
                $to,
                array(
                    'From' => $from,
                    'To' => $to,
                    'Subject' => $subject,
                    'Content-type' => 'text/plain; charset=utf-8',
                    'Content-Transfer-Encoding' => '8bit'
                ),
                $body
            );

            ini_set('error_reporting', $e);
        } else {
            mail($to, $subject, $body, implode("\r\n", array(
                'From' => $from,
                'Content-type' => 'text/plain; charset=utf-8',
                'Content-Transfer-Encoding' => '8bit'
            )));
        }

        return TRUE;
    }

    /**
     * Create an database update request with email confirmation.
     *
     * @param string $subject The subject of the email.
     * @param string $text The text of the email.
     * @param string $key The key to update.
     * @param string $value The value to update the key to.
     * @param string|null $username Username or null for current user.
     * @return bool Whether successful.
     */
    public function createUpdate($subject, $text, $key, $value, $username = null)
    {
        $withURL = TRUE;
        if ($username != null) {
            $withURL = FALSE;
            $user = $this->page->users->getUserByName($username);
        }
        if ($user->getUserName() == 'anonymous')
            return FALSE;

        $token = '';
        $possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        for ($i = 0; $i < 32; $i++)
            $token .= $possible[mt_rand(1, strlen($possible)) - 1];

        $url = sprintf("%s://%s/u?u=%s&t=%s",
            isset($_SERVER['HTTPS']) ? 'https' : 'http',
            $_SERVER['SERVER_NAME'],
            $user->getUserName(),
            $token
        );

        $this->sendTo(
            $user->getEmail(),
            $subject,
            $text . "\n" .
            ($withURL ? "\nURL: $url\nODER" : "") .
            "\nToken: $token" .
            ($withURL ? "\n\nDas Token kann direkt auf der Einstellungen-Seite eingegeben werden." : "")
        );

        $this->page->db->query("
            INSERT INTO dns_users_update VALUES (?, NOW(), ?, ?, ?)
        ",
            $user->getUserName(),
            $token,
            $key,
            $value
        );
        return TRUE;
    }

    /**
     * Verify an update token.
     *
     * @param string $user The user the update belongs to.
     * @param string $token The token to verify.
     * @return bool|array The key and value to update.
     */
    public function verifyUpdate($user, $token)
    {
        $get = $this->page->db->query("
            SELECT * FROM dns_users_update
            WHERE username = ? AND token = ?
        ",
            $user, $token
        );

        if ($get && $row = $get->fetch()) {
            $this->page->db->query("
                DELETE FROM dns_users_update
                WHERE username = ? AND token = ?
            ",
                $user, $token
            );

            switch ($row['key']) {
                case 'password':
                    if (!$this->page->user->confirmPasswordUpdate($user, $row['value']))
                        return FALSE;
                    break;
                case 'email':
                    if (!$this->page->user->confirmEmailUpdate($user, $row['value']))
                        return FALSE;
                    break;
            }
            return $row;
        }
        return FALSE;
    }
}

