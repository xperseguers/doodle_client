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

if (!ini_get('date.timezone') && function_exists('date_default_timezone_set')) {
    date_default_timezone_set('UTC');
}

use Causal\DoodleClient\Domain\Model\Poll;
use Causal\DoodleClient\Domain\Repository\PollRepository;

/**
 * Client for Doodle (https://doodle.com).
 *
 * @package Causal\DoodleClient
 */
class Client
{

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
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->userAgent = sprintf('Mozilla/5.0 (%s %s %s) Doodle PHP Client', php_uname('s'), php_uname('r'), php_uname('m'));
        $this->locale = 'en_GB';
        $this->cookiePath = sys_get_temp_dir();
        $this->token = $this->getTokenFromCookie();
        $this->pollRepository = new PollRepository($this);
    }

    /**
     * Returns the authentication token.
     *
     * @return string|null
     */
    protected function getTokenFromCookie()
    {
        $cookies = $this->getCookies();
        if (!empty($cookies['token']) && $cookies['token']['expiration'] > time()) {
            return $cookies['token']['value'];
        }

        return null;
    }

    /**
     * Returns the available cookies.
     *
     * @return array
     */
    public function getCookies(): array
    {
        $cookies = [];
        $cookieFileName = $this->getCookieFileName();
        if (!file_exists($cookieFileName)) {
            return $cookies;
        }

        $contents = file_get_contents($cookieFileName);
        $lines = explode(LF, $contents);
        foreach ($lines as $line) {
            if (empty($line) || $line{0} === '#') {
                continue;
            }
            $data = explode(TAB, $line);
            $cookie = array_combine(
            /** @see http://www.cookiecentral.com/faq/#3.5 */
                ['domain', 'flag', 'path', 'secure', 'expiration', 'name', 'value'],
                $data
            );

            $cookies[$cookie['name']] = $cookie;
        }

        return $cookies;
    }

    /**
     * Returns the cookie file name.
     *
     * @return string
     */
    protected function getCookieFileName(): string
    {
        return $this->cookiePath . sha1($this->username . chr(0) . $this->password . chr(0) . $this->userAgent);
    }

    /**
     * Returns the user agent.
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * Sets the user agent.
     *
     * @param string $userAgent
     * @return $this
     */
    public function setUserAgent(string $userAgent): Client
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * Returns the locale.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Sets the locale.
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale(string $locale): Client
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Returns the cookie directory.
     *
     * @return string
     */
    public function getCookiePath(): string
    {
        return $this->cookiePath;
    }

    /**
     * Sets the cookie directory.
     *
     * @param string $cookiePath
     * @return $this
     */
    public function setCookiePath(string $cookiePath): Client
    {
        $this->cookiePath = rtrim($cookiePath, '/') . '/';
        return $this;
    }

    /**
     * Connects to Doodle.
     *
     * @return bool Returns true if connection succeeded, otherwise false
     */
    public function connect(): bool
    {
        /** @var array $response */
        $response = $this->doPost('/api/v2.0/users/oauth/token', [
            'email' => $this->username,
            'password' => $this->password
        ]);

        // Define the token we want to use
        $this->storeToken($response['accessToken']);

        $response = $this->doGet('/api/v2.0/users/me/cookie-from-access-token');

        return true;
    }

    /**
     * Performs a POST request on a given URL.
     *
     * @param string $relativeUrl
     * @param array $data
     *
     * @return string|array
     */
    protected function doPost(string $relativeUrl, array $data)
    {
        return $this->doRequest('POST', $relativeUrl, $data);
    }

    /**
     * Performs a DELETE request on a given URL.
     *
     * @param string $relativeUrl
     * @return string|array
     */
    protected function doDelete(string $relativeUrl)
    {
        return $this->doRequest('DELETE', $relativeUrl, []);
    }

    /**
     * Sends a HTTP request to Doodle.
     *
     * @param string $method
     * @param string $relativeUrl
     * @param array $data
     *
     * @return string|array
     */
    protected function doRequest(string $method, string $relativeUrl, array $data)
    {
        $url = 'https://doodle.com' . $relativeUrl;
        $cookieFileName = $this->getCookieFileName();

        $dataQuery = '';

        if (strpos($relativeUrl, 'api') !== false) {
            if (!empty($data)) {
                $dataQuery = json_encode($data);
            }
        } else {
            $dataQuery = http_build_query($data);
            $dataQuery = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '%5B%5D=', $dataQuery);
        }

        $ch = curl_init();

        switch ($method) {
            case 'GET':
                if (!empty($dataQuery)) {
                    $url .= '?' . $dataQuery;
                }
                if (strpos($relativeUrl, 'api') !== false) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Access-Token: ' . $this->token
                    ]);
                }
                break;
            case 'POST':
            case 'DELETE':
                if ($method === 'DELETE') {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                } else {
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataQuery);
                }
                if (strpos($relativeUrl, 'api') !== false) {
                    if ($method === 'POST') {
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($dataQuery)]
                        );
                    }
                }
                break;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFileName);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFileName);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        //$verbose = fopen('php://temp', 'wb+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        $result = curl_exec($ch);

        $info = curl_getinfo($ch);

        if ($result === FALSE) {
            printf("cUrl error (#%d): %s\n", curl_errno($ch), curl_error($ch));
            //$verboseLog = stream_get_contents($verbose);
            //echo "Verbose information:\n", $verboseLog, "\n";
        }
        //rewind($verbose);

        curl_close($ch);

        if (strpos($relativeUrl, 'api') !== false) {
            if ($method === 'DELETE') {
                return $info['http_code'] === 200;
            }
            if ($result) {
                if ($result[0] === 'E') {
                    $result = substr($result, 1);
                }

                return json_decode($result, true);
            }
        }

        return $result;
    }

    /**
     * Stores the authentication token.
     *
     * Business logic is inspired from
     * http://doodle.com/builstatic/<timestamp>/doodle/js/common/loginUtils.js:updateToken()
     *
     * @param string $token
     * @return void
     */
    protected function storeToken(string $token)
    {
        $cookies = $this->getCookies();
        $cookies['token']['domain'] = '.doodle.com';
        $cookies['token']['truefalse'] = 'TRUE';
        $cookies['token']['path'] = '/';
        $cookies['token']['truefalse2'] = 'FALSE';
        $cookies['token']['something'] = '0';
        $cookies['token']['name'] = 'Token';
        $cookies['token']['value'] = $token;
        $cookies['d-betaCode'] = $cookies['DoodleAuthentication'];
        $cookies['d-betaCode']['name'] = 'd-betaCode';
        $cookies['d-betaCode']['value'] = true;
        $cookies['d-forceBeta'] = $cookies['DoodleAuthentication'];
        $cookies['d-forceBeta']['name'] = 'd-forceBeta';
        $cookies['d-forceBeta']['value'] = false;
        $this->persistCookies($cookies);
        $this->token = $token;
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
     * Performs a GET request on a given URL.
     *
     * @param string $relativeUrl
     * @param array $data
     * @return string|array
     */
    protected function doGet($relativeUrl, array $data = [])
    {
        return $this->doRequest('GET', $relativeUrl, $data);
    }

    /**
     * Disconnects from Doodle.
     *
     * @return bool Returns true if disconnect succeeded, otherwise false
     */
    public function disconnect(): bool
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
    public function getUserInfo(): array
    {
        $data = [
            'isMobile' => 'false',
            'includeKalsysInfos' => 'false',
            'token' => $this->token,
        ];
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
        $data = [
            'fullList' => 'true',
            'locale' => $this->locale,
            'token' => $this->token,
        ];
        $response = $this->doGet('/np/users/me/dashboard/myPolls', $data);

        if (strpos($response, '<title>Doodle: Not found') !== false) {
            throw new \Causal\DoodleClient\Exception\UnauthenticatedException('Doodle returned an error while fetching polls. Either you are not authenticated or your token is considered to be outdated.', 1454323881);
        }
        $polls = json_decode($response, true);

        $objects = [];
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
        $data = [
            'fullList' => 'true',
            'locale' => $this->locale,
            'token' => $this->token,
        ];
        $response = $this->doGet('/np/users/me/dashboard/otherPolls', $data);
        $polls = json_decode($response, true);

        $objects = [];
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
     *
     * @return Poll
     * @throws \Exception
     */
    public function createPoll(array $info)
    {
        $type = strtoupper($info['type']) === Poll::TYPE_TEXT ? Poll::TYPE_TEXT : Poll::TYPE_DATE;
        $data = [
            'initiator' => [
                'name' => trim($info['name']),
                'email' => trim($info['email']),
                'timeZone' => 'Europe/Berlin',
                'notify' => true
            ],
            'title' => trim($info['title']),
            'location' => ['name' => trim($info['location'])],
            'description' => trim($info['description']),
            'hidden' => 'false',
            'askAddress' => 'false',
            'askEmail' => 'false',
            'askPhone' => 'false',
            'multiDay' => 'false',
            'type' => $type,
            'locale' => $this->locale,
            'timeZone' => true
        ];

        // Optional parameters
        if (isset($info['hidden'])) {
            $data['hidden'] = (bool)$info['hidden'] ? 'true' : 'false';
        }
        if (isset($info['columnConstraint'])) {
            $data['columnConstraint'] = $info['columnConstraint'];
        }
        // Requires a premium account or will be ignored
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
                        if (preg_match('/^(\d+)-(\d+)$/', $time, $matches)) {
                            $data['options'][] = [
                                'allday' => false,
                                'end' => strtotime($matches[2]) . '000', // 000 is some extra stuff the api needs, because of a Java backend (microseconds)
                                'start' => strtotime($matches[1]) . '000'  // 000 is some extra stuff the api needs, because of a Java backend (microseconds)
                            ];
                        } else {
                            $data['options'][] = $date . $time;
                        }
                    }
                } else {
                    // No time given
                    $data['options'][] = $date;
                }
            }
        }

        $response = $this->doPost('/api/v2.0/polls', $data);

        $ret = $response;

        if (empty($ret['id'])) {
            throw new \Exception($response, 1443718401);
        }

        $poll = new Poll($ret['id']);
        $poll
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
    public function deletePoll(Poll $poll): bool
    {
        if (empty($poll->getAdminKey())) {
            throw new \Exception(sprintf('Admin key not available. Poll %s cannot be deleted.', $poll->getId()), 1443782170);
        }
        return $this->doDelete('/api/v2.0/polls/' . $poll->getId() . '?adminKey=' . $poll->getAdminKey());
    }

    /**
     * Deletes a participant.
     *
     * @param Poll $poll
     * @param int $participantid
     * @return string
     * @throws \Exception
     */
    public function deleteParticipant(Poll $poll, int $participantid)
    {
        if (empty($poll->getAdminKey())) {
            throw new \Exception(sprintf('Admin key not available. Poll %s cannot be deleted.', $poll->getId()), 1443782170);
        }
        return $this->doDelete('/api/v2.0/polls/' . $poll->getId() . '/participants/' . $participantid . '?adminKey=' . $poll->getAdminKey());
    }

    /**
     * @param Poll $poll
     * @return array
     * @deprecated ?
     * @internal ?
     */
    public function _getInfo(Poll $poll): array
    {
        $response = $this->doGet('/api/v2.0/polls/' . $poll->getId() . '?adminKey=&participantKey=', []);
        return $response;
    }

    /**
     * Returns the token.
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

}
