<?php
namespace Causal\DoodleClient\Domain\Repository;

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

use Causal\DoodleClient\Client;
use Causal\DoodleClient\Domain\Model\Poll;

/**
 * Class PollRepository
 *
 * @package Causal\DoodleClient\Domain\Repository
 */
class PollRepository
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * PollRepository constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Creates a new Poll object.
     *
     * @param array $data
     * @return Poll
     */
    public function create(array $data)
    {
        $poll = new Poll($data['id'], $this);
        $poll
            ->setType($data['type'])
            ->setTitle($data['title'])
            ->setState($data['state'])
            ->setMultiDay((bool)$data['multiDay'])
            ->setByInvitation((bool)$data['byInvitation'])
            ->setInviteesCount((int)$data['inviteesCount'])
            ->setParticipantsCount((int)$data['participantsCount'])
            ->setAskAddress((bool)$data['askAddress'])
            ->setAskEmail((bool)$data['askEmail'])
            ->setAskPhone((bool)$data['askPhone'])
            ->setAmINotified((bool)$data['amINotified'])
            ->setLastWriteAccess(new \DateTime($data['lastWriteAccess']));

        // Possible exception
        try {
            $lastActivity = new \DateTime($data['lastActivity']);
        } catch (\Exception $e) {
            $lastActivity = new \DateTime();
        }
        $poll->setLastActivity($lastActivity);

        // Optional, possibly missing, attributes
        if (!empty($data['adminKey'])) {
            $poll->setAdminKey($data['adminKey']);
        }
        if (!empty($data['rowConstraint'])) {
            $poll->setRowConstraint((bool)$data['rowConstraint']);
        }

        return $poll;
    }

    /**
     * Injects the description of a given poll.
     *
     * @param Poll $poll
     * @return void
     */
    public function injectDescription(Poll $poll)
    {
        $info = $poll->_getInfo();
        if ($info === null) {
            $info = $this->client->getInfo($poll);
            $poll->_setInfo($info);
        }

        $description = $this->decodeHtml($info['descriptionHTML']);
        $poll->setDescription($description);
    }

    /**
     * Decodes HTML entities.
     *
     * @param string $html
     * @return string
     */
    protected function decodeHtml($html)
    {
        $text = html_entity_decode($html);
        $text = preg_replace('#<br\s*/?>#', LF, $text);
        return $text;
    }

}
