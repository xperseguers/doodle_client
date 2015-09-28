<?php
namespace Causal\DoodleClient;

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

define('LF', "\n");
define('TAB', "\t");

// Check for the json extension, the Doodle PHP Client won't function
// without it.
if (!function_exists('json_decode')) {
    throw new Exception('Doodle PHP Client requires the JSON PHP extension', 1443411993);
}

if (!function_exists('curl_init')) {
    throw new Exception('Doodle PHP Client requires the cURL PHP extension', 1443412007);
}

if (!function_exists('http_build_query')) {
    throw new Exception('Doodle PHP Client requires http_build_query()', 1443412105);
}

if (! ini_get('date.timezone') && function_exists('date_default_timezone_set')) {
    date_default_timezone_set('UTC');
}

use Causal\DoodleClient\Domain\Model\Poll;

/**
 * Client for Doodle (http://doodle.com).
 *
 * @package Causal\DoodleClient
 */
class Client {

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $userAgent;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $cookiePath;

    /**
     * @var string
     */
    protected $token;

    /**
     * Client constructor.
     *
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->userAgent = sprintf('Mozilla/5.0 (%s %s %s) Doodle PHP Client', php_uname('s'), php_uname('r'), php_uname('m'));
        $this->locale = 'en_GB';
        $this->cookiePath = sys_get_temp_dir();
        $this->token = $this->getToken();
    }

    /**
     * Returns the user agent.
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * Sets the user agent.
     *
     * @param string $userAgent
     * @return $this
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * Returns the locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Sets the locale.
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Returns the cookie directory.
     *
     * @return string
     */
    public function getCookiePath()
    {
        return $this->cookiePath;
    }

    /**
     * Sets the cookie directory.
     *
     * @param string $cookiePath
     * @return $this
     */
    public function setCookiePath($cookiePath)
    {
        $this->cookiePath = $cookiePath;
        return $this;
    }

    /**
     * Connects to Doodle.
     *
     * @return bool Returns true if connection succeeded, otherwise false
     */
    public function connect()
    {
        $this->token = $this->getToken();
        if ($this->token !== null)
        {
            // Already properly authenticated
            return;
        }

        // 1 - Get first login page
        // This page will set some cookies and we will use them for posting in form data
        $this->doGet('http://doodle.com');

        // 2 - Post login data
        $data = array(
            'eMailAddress' => $this->username,
            'password' => $this->password,
            'locale' => 'fr_CH',
            'timeZone' => 'Europe/Zurich',
        );
        $response = $this->doPost('https://doodle.com/np/mydoodle/logister', $data);

        // 3 - Define the token we want to use
        $this->generateToken();

        return true;
    }

    /**
     * Disconnects from Dodle.
     *
     * @return bool Returns true if disconnect succeeded, otherwise false
     */
    public function disconnect()
    {
        $cookieFileName = $this->getCookieFileName();
        if (file_exists($cookieFileName)) {
            return unlink($cookieFileName);
        }

        return false;
    }

    /**
     * Returns user information.
     *
     * @return array
     */
    public function getUserInfo()
    {
        $data = array(
            'isMobile' => 'false',
            'includeKalsysInfos' => 'false',
            'token' => $this->token,
        );
        $response = $this->doGet('https://doodle.com/np/users/me', $data);
        $userInfo = json_decode($response, true);

        return $userInfo;
    }

    /**
     * Returns personal polls.
     *
     * @return Poll[]
     */
    public function getPersonalPolls()
    {
        $data = array(
            'fullList' => 'true',
            'locale' => $this->locale,
            'token' => $this->token,
        );
        $response = $this->doGet('https://doodle.com/np/users/me/dashboard/myPolls', $data);
        $polls = json_decode($response, true);

        $objects = array();
        if (!empty($polls['myPolls']['myPolls'])) {
            foreach ($polls['myPolls']['myPolls'] as $poll) {
                $objects[] = Poll::create($poll);
            }
        }

        return $objects;
    }

    /**
     * Returns other polls.
     *
     * @return Poll[]
     */
    public function getOtherPolls()
    {
        $data = array(
            'fullList' => 'true',
            'locale' => $this->locale,
            'token' => $this->token,
        );
        $response = $this->doGet('https://doodle.com/np/users/me/dashboard/otherPolls', $data);
        $polls = json_decode($response, true);

        $objects = array();
        if (!empty($polls['otherPolls']['otherPolls'])) {
            foreach ($polls['otherPolls']['otherPolls'] as $poll) {
                $objects[] = Poll::create($poll);
            }
        }

        return $objects;
    }

    /**
     * Performs a GET request on a given URL.
     *
     * @param string $url
     * @param array $data
     * @return string
     */
    protected function doGet($url, array $data = array())
    {
        $cookieFileName = $this->getCookieFileName();

        if (!empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFileName);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFileName);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Performs a POST request on a given URL.
     *
     * @param string $url
     * @param array $data
     * @return string
     */
    protected function doPost($url, array $data)
    {
        $postFields = http_build_query($data);
        $cookieFileName = $this->getCookieFileName();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFileName);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFileName);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Returns the cookie file name.
     *
     * @return string
     */
    protected function getCookieFileName()
    {
        return $this->cookiePath . sha1($this->username . chr(0) . $this->password . chr(0) . $this->userAgent);
    }

    /**
     * Returns the available cookies.
     *
     * @return array
     */
    protected function getCookies()
    {
        $cookies = array();
        $cookieFileName = $this->getCookieFileName();
        if (!file_exists($cookieFileName)) {
            return $cookies;
        }

        $contents = file_get_contents($cookieFileName);
        $lines = explode(LF, $contents);
        foreach ($lines as $line) {
            if (empty($line) || $line{0} === '#') continue;
            $data = explode(TAB, $line);
            $cookie = array_combine(
            /** @see http://www.cookiecentral.com/faq/#3.5 */
                array('domain', 'flag', 'path', 'secure', 'expiration', 'name', 'value'),
                $data
            );

            $cookies[$cookie['name']] = $cookie;
        }

        return $cookies;
    }

    /**
     * Persists cookies.
     *
     * @param array $cookies
     * @return void
     */
    protected function persistCookies(array $cookies)
    {
        $cookieFileName = $this->getCookieFileName();
        $contents = <<<EOT
# Netscape HTTP Cookie File
# http://curl.haxx.se/docs/http-cookies.html
# This file was generated by libcurl! Edit at your own risk.

EOT;

        foreach ($cookies as $cookie) {
            $contents .= implode(TAB, $cookie) . LF;
        }

        file_put_contents($cookieFileName, $contents);
    }

    /**
     * Returns the authentication token.
     *
     * @return string
     */
    protected function getToken()
    {
        $cookies = $this->getCookies();
        if (!empty($cookies['token']) && $cookies['token']['expiration'] > time()) {
            return $cookies['token']['value'];
        }

        return null;
    }

    /**
     * Generates an authentication token.
     *
     * Business logic is inspired from
     * http://doodle.com/builstatic/<timestamp>/doodle/js/common/loginUtils.js:updateToken()
     *
     * @return void
     */
    protected function generateToken()
    {
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $length = strlen($charset);
        $randomString = '';
        for ($i = 0; $i < 30; $i++) {
            $position = rand(0, $length - 1);
            $randomString .= $charset{$position};
        }

        $cookies = $this->getCookies();

        // Lifetime must be the same as DoodleAuthentication and DoodleIdentification cookies
        // because we set those and this one only on login
        $cookies['token'] = $cookies['DoodleAuthentication'];
        $cookies['token']['name'] = 'token';
        $cookies['token']['value'] = $randomString;

        $this->persistCookies($cookies);
        $this->token = $randomString;
    }

}
