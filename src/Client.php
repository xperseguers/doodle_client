<?php
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

namespace Causal\DoodleClient;

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
use Causal\DoodleClient\Domain\Repository\PollRepository;

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
     * @var PollRepository
     */
    protected $pollRepository;

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
        $this->pollRepository = new PollRepository($this);
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
        $this->cookiePath = rtrim($cookiePath, '/') . '/';
        return $this;
    }

    /**
     * Connects to Doodle.
     *
     * @return bool Returns true if connection succeeded, otherwise false
     */
    public function connect()
    {
        // 1 - Get first login page
        // This page will set some cookies and we will use them for posting in form data
        $this->doGet('/');

        $this->token = $this->getToken();
        if ($this->token !== null)
        {
            // Already properly authenticated
            return;
        }

        // 2 - Post login data
        $data = array(
            'eMailAddress' => $this->username,
            'password' => $this->password,
            'locale' => $this->locale,
            'timeZone' => date_default_timezone_get(),
        );
        $response = $this->doPost('/np/mydoodle/logister', $data);

        // 3 - Define the token we want to use
        $this->generateToken();

        return true;
    }

    /**
     * Disconnects from Doodle.
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
        $response = $this->doGet('/np/users/me', $data);
        $userInfo = json_decode($response, true);

        return $userInfo;
    }

    /**
     * Returns personal polls.
     *
     * @return Poll[]
     * @throws \Causal\DoodleClient\Exception\UnauthenticatedException
     */
    public function getPersonalPolls()
    {
        $data = array(
            'fullList' => 'true',
            'locale' => $this->locale,
            'token' => $this->token,
        );
        $response = $this->doGet('/np/users/me/dashboard/myPolls', $data);
        if (strpos($response, '<title>Doodle: Not found') !== false) {
            throw new \Causal\DoodleClient\Exception\UnauthenticatedException('Doodle returned an error while fetching polls. Either you are not authenticated or your token is considered to be outdated.', 1454323881);
        }
        $polls = json_decode($response, true);

        $objects = array();
        if (!empty($polls['myPolls']['myPolls'])) {
            foreach ($polls['myPolls']['myPolls'] as $poll) {
                $objects[] = $this->pollRepository->create($poll);
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
        $response = $this->doGet('/np/users/me/dashboard/otherPolls', $data);
        $polls = json_decode($response, true);

        $objects = array();
        if (!empty($polls['otherPolls']['otherPolls'])) {
            foreach ($polls['otherPolls']['otherPolls'] as $poll) {
                $objects[] = $this->pollRepository->create($poll);
            }
        }

        return $objects;
    }

    /**
     * Creates a poll.
     *
     * Dates need to be provided as
     *
     * <code>
     * $info['dates'] = [
     *     'yyyymmdd' => [
     *         'time1',
     *         'time2',
     *         ...
     *     ],
     *     ...
     * ];
     * </code>
     *
     * Examples:
     *
     * <code>
     * // No time
     * $info['dates'] = [
     *     '20150929' => [],
     *     '20150930' => [],
     *     '20151001' => [],
     * ];
     *
     * // Time
     * $info['dates'] = [
     *     '20150929' => ['0830', '1430'],
     * ];
     * </code>
     *
     * @param array $info
     * @return Poll
     * @throws \Exception
     */
    public function createPoll(array $info)
    {
        $type = strtoupper($info['type']) === Poll::TYPE_TEXT ? Poll::TYPE_TEXT : Poll::TYPE_DATE;
        $data = array(
            'title' => trim($info['title']),
            'locName' => trim($info['location']),
            'description' => trim($info['description']),
            'initiatorAlias' => trim($info['name']),
            'initiatorEmail' => trim($info['email']),
            'hidden' => 'false',
            'ifNeedBe' => 'false',
            'askAddress' => 'false',
            'askEmail' => 'false',
            'askPhone' => 'false',
            'multiDay' => 'false',
            'byInvitation' => 'false',
            'withTzSupport' => 'false',
            //'columnConstraint' => 0,    // maximum number of participants per option
            'optionsMode' => strtolower($type),
            'currentYear' => date('Y'),
            'currentMonth' => date('n'),
            'type' => $type,
            'createdOnCalendarView' => 'false',
            'shownCalendars' => '',
            'country' => 'CH',
            'locale' => $this->locale,
            'token' => $this->token,
        );

        // Optional parameters
        if (isset($info['ifNeedBe'])) {
            $data['ifNeedBe'] = (bool)$info['ifNeedBe'] ? 'true' : 'false';
        }
        if (isset($info['hidden'])) {
            $data['hidden'] = (bool)$info['hidden'] ? 'true' : 'false';
        }
        // Require a premium account or will be ignored
        if (isset($info['askAddress'])) {
            $data['askAddress'] = (bool)$info['askAddress'] ? 'true' : 'false';
        }
        if (isset($info['askEmail'])) {
            $data['askEmail'] = (bool)$info['askEmail'] ? 'true' : 'false';
        }
        if (isset($info['askPhone'])) {
            $data['askPhone'] = (bool)$info['askPhone'] ? 'true' : 'false';
        }

        if ($type === Poll::TYPE_TEXT) {
            foreach ($info['options'] as $option) {
                $data['options'][] = trim($option);
            }
        } else {
            foreach ($info['dates'] as $date => $times) {
                if (!empty($times)) {
                    foreach ($times as $time) {
                        $data['options'][] = $date . $time;
                    }
                } else {
                    // No time given
                    $data['options'][] = $date;
                }
            }
        }

        $response = $this->doPost('/np/new-polls/', $data);
        $ret = json_decode($response, true);

        if (empty($ret['id'])) {
            throw new \Exception($response, 1443718401);
        }

        $poll = new Poll($ret['id']);
        $poll
            ->setByInvitation($ret['byInvitation'])
            ->setState($ret['state'])
            ->setAdminKey($ret['adminKey'])
            ->setTitle($ret['title']);
        return $poll;
    }

    /**
     * Deletes a poll.
     *
     * @param Poll $poll
     * @return bool
     * @throws \Exception
     */
    public function deletePoll(Poll $poll)
    {
        if (empty($poll->getAdminKey())) {
            throw new \Exception(sprintf('Admin key not available. Poll %s cannot be deleted.', $poll->getId()), 1443782170);
        }

        $data = array(
            'adminKey' => $poll->getAdminKey(),
            'token' => $this->token,
        );
        $response = $this->doPost('/np/new-polls/' . $poll->getId() . '/delete', $data);
    }

    /**
     * Returns information about a given poll.
     *
     * @param Poll $poll
     * @return array
     * @internal
     */
    public function _getInfo(Poll $poll)
    {
        $data = array(
            'adminKey' => '',
            'locale' => $this->locale,
            'token' => $this->token,
        );
        $response = $this->doGet('/poll/' . $poll->getId(), $data);

        $info = array();
        if (($pos = strpos($response, '$.extend(true, doodleJS.data, {"poll"')) !== FALSE) {
            $json = substr($response, $pos + 30);
            $json = trim(substr($json, 0, strpos($json, 'doodleJS.data.poll.keywordsJson')));
            // Remove the end of the javascript code
            $json = rtrim($json, ');');
            $info = json_decode($json, true);
        }

        $info = !empty($info['poll']) ? $info['poll'] : array();
        return $info;
    }

    /**
     * Performs a GET request on a given URL.
     *
     * @param string $relativeUrl
     * @param array $data
     * @return string
     */
    protected function doGet($relativeUrl, array $data = array())
    {
        return $this->doRequest('GET', $relativeUrl, $data);
    }

    /**
     * Performs a POST request on a given URL.
     *
     * @param string $relativeUrl
     * @param array $data
     * @return string
     */
    protected function doPost($relativeUrl, array $data)
    {
        return $this->doRequest('POST', $relativeUrl, $data);
    }

    /**
     * Sends a HTTP request to Doodle.
     *
     * @param string $method
     * @param string $relativeUrl
     * @param array $data
     * @return string
     */
    protected function doRequest($method, $relativeUrl, array $data)
    {
        $scheme = strlen($relativeUrl) > 4 && substr($relativeUrl, 0, 4) === '/np/' ? 'https' : 'http';
        $url = $scheme . '://doodle.com' . $relativeUrl;
        $cookieFileName = $this->getCookieFileName();
        $dataQuery = http_build_query($data);
        $dataQuery = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '%5B%5D=', $dataQuery);
        $ch = curl_init();

        switch ($method) {
            case 'GET':
                if (!empty($dataQuery)) {
                    $url .= '?' . $dataQuery;
                }
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $dataQuery);
                break;
        }

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
