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

namespace Causal\DoodleClient\Domain\Model;

use Causal\DoodleClient\Domain\Model\Option;
use Causal\DoodleClient\Domain\Model\Participant;
use Causal\DoodleClient\Domain\Repository\PollRepository;

/**
 * Class Poll.
 *
 * @package Causal\DoodleClient\Domain\Model
 */
class Poll
{

    const TYPE_TEXT = 'TEXT';
    const TYPE_DATE = 'DATE';

    const STATE_OPEN = 'OPEN';
    const STATE_CLOSED = 'CLOSED';

    const MAX_LENGTH_TITLE = 64;
    const MAX_LENGTH_DESCRIPTION = 512;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string
     */
    protected $adminKey;

    /**
     * @var bool
     */
    protected $multiDay;

    /**
     * @var bool
     */
    protected $rowConstraint;

    /**
     * @var bool
     */
    protected $byInvitation;

    /**
     * @var int
     */
    protected $inviteesCount;

    /**
     * @var int
     */
    protected $participantsCount;

    /**
     * @var bool
     */
    protected $askAddress;

    /**
     * @var bool
     */
    protected $askEmail;

    /**
     * @var bool
     */
    protected $askPhone;

    /**
     * @var bool
     */
    protected $amINotified;

    /**
     * @var \DateTime
     */
    protected $lastWriteAccess;

    /**
     * @var \DateTime
     */
    protected $lastActivity;

    /**
     * @var array
     * @internal
     */
    protected $_info = null;

    /**
     * @var PollRepository
     * @internal
     */
    protected $_repository = null;

    /**
     * @var string
     */
    protected $description = null;

    /**
     * @var Option[]
     */
    protected $options = null;

    /**
     * @var Participant[]
     */
    protected $participants = null;

    /**
     * @var Location
     */
    protected $location = false;

    /**
     * Poll constructor.
     *
     * @param string $id
     * @param PollRepository $repository
     */
    public function __construct($id = '', PollRepository $repository = null)
    {
        $this->id = $id;
        $this->_repository = $repository;
    }

    /**
     * Returns the ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the ID.
     *
     * @param string $id
     * @return $this
     * @internal
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Returns the type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the type.
     *
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Returns the title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title.
     *
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $title = trim($title);
        $this->title = substr($title, 0, min(static::MAX_LENGTH_TITLE, strlen($title)));
        return $this;
    }

    /**
     * Returns the state.
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Sets the state.
     *
     * @param string $state
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Returns the admin key.
     *
     * @return string
     */
    public function getAdminKey()
    {
        return $this->adminKey;
    }

    /**
     * Sets the admin key.
     *
     * @param string $adminKey
     * @return $this
     */
    public function setAdminKey($adminKey)
    {
        $this->adminKey = $adminKey;
        return $this;
    }

    /**
     * Returns true if this is a multi-day poll.
     *
     * @return bool
     */
    public function getMultiDay()
    {
        return $this->multiDay;
    }

    /**
     * Sets whether this is a multi-day poll.
     *
     * @param bool $multiDay
     * @return $this
     */
    public function setMultiDay($multiDay)
    {
        $this->multiDay = (bool)$multiDay;
        return $this;
    }

    /**
     * Returns whether poll has row constraint.
     *
     * @return bool
     */
    public function getRowConstraint()
    {
        return $this->rowConstraint;
    }

    /**
     * Sets whether poll has row constraint.
     *
     * @param bool $rowConstraint
     * @return $this
     */
    public function setRowConstraint($rowConstraint)
    {
        $this->rowConstraint = (bool)$rowConstraint;
        return $this;
    }

    /**
     * Returns true if this is a poll by invitation.
     *
     * @return bool
     */
    public function getByInvitation()
    {
        return $this->byInvitation;
    }

    /**
     * Sets whether this is a poll by invitation.
     *
     * @param bool $byInvitation
     * @return $this
     */
    public function setByInvitation($byInvitation)
    {
        $this->byInvitation = (bool)$byInvitation;
        return $this;
    }

    /**
     * Returns the number of invitees.
     *
     * @return int
     */
    public function getInviteesCount()
    {
        return $this->inviteesCount;
    }

    /**
     * Sets the number of invitees.
     *
     * @param int $inviteesCount
     * @return $this
     */
    public function setInviteesCount($inviteesCount)
    {
        $this->inviteesCount = (int)$inviteesCount;
        return $this;
    }

    /**
     * Returns the number of participants.
     *
     * @return int
     */
    public function getParticipantsCount()
    {
        return $this->participantsCount;
    }

    /**
     * Sets the number of participants.
     *
     * @param int $participantsCount
     * @return $this
     */
    public function setParticipantsCount($participantsCount)
    {
        $this->participantsCount = (int)$participantsCount;
        return $this;
    }

    /**
     * Returns wether the address should be asked for.
     *
     * @return bool
     */
    public function getAskAddress()
    {
        return $this->askAddress;
    }

    /**
     * Sets wether the address should be asked for.
     *
     * @param bool $askAddress
     * @return $this
     */
    public function setAskAddress($askAddress)
    {
        $this->askAddress = (bool)$askAddress;
        return $this;
    }

    /**
     * Returns wether the email address should be asked for.
     *
     * @return bool
     */
    public function getAskEmail()
    {
        return $this->askEmail;
    }

    /**
     * Sets wether the email address should be asked for.
     *
     * @param bool $askEmail
     * @return $this
     */
    public function setAskEmail($askEmail)
    {
        $this->askEmail = (bool)$askEmail;
        return $this;
    }

    /**
     * Returns wether the phone should be asked for.
     *
     * @return bool
     */
    public function getAskPhone()
    {
        return $this->askPhone;
    }

    /**
     * Sets wether the phone should be asked for.
     *
     * @param bool $askPhone
     * @return $this
     */
    public function setAskPhone($askPhone)
    {
        $this->askPhone = (bool)$askPhone;
        return $this;
    }

    /**
     * Returns true if the poll owner is being notified of poll activity.
     *
     * @return bool
     */
    public function getAmINotified()
    {
        return $this->amINotified;
    }

    /**
     * Returns true if the poll owner is being notified of poll activity.
     *
     * @param bool $amINotified
     * @return $this
     */
    public function setAmINotified($amINotified)
    {
        $this->amINotified = (bool)$amINotified;
        return $this;
    }

    /**
     * Returns the last write access.
     *
     * @return \DateTime
     */
    public function getLastWriteAccess()
    {
        return $this->lastWriteAccess;
    }

    /**
     * Sets the last write access.
     *
     * @param \DateTime $lastWriteAccess
     * @return $this
     */
    public function setLastWriteAccess(\DateTime $lastWriteAccess)
    {
        $this->lastWriteAccess = $lastWriteAccess;
        return $this;
    }

    /**
     * Returns the last activity.
     *
     * @return \DateTime
     */
    public function getActivity()
    {
        return $this->lastActivity;
    }

    /**
     * Sets the last activity.
     *
     * @param \DateTime $lastActivity
     * @return $this
     */
    public function setLastActivity(\DateTime $lastActivity)
    {
        $this->lastActivity = $lastActivity;
        return $this;
    }

    /**
     * Returns the public URL.
     *
     * @return string
     */
    public function getPublicUrl()
    {
        if (!empty($this->_info['prettyUrl'])) {
            $publicUrl = $this->_info['prettyUrl'];
        } else {
            $publicUrl = 'http://doodle.com/poll/' . $this->getId();
        }
        return $publicUrl;
    }

    /**
     * Returns the description.
     *
     * @return string
     */
    public function getDescription()
    {
        if ($this->description === null && $this->_repository !== null)
        {
            $this->_repository->injectDescription($this);
        }
        return $this->description ?: '';
    }

    /**
     * Sets the description.
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $description = trim($description);
        $this->description = substr($description, 0, min(static::MAX_LENGTH_DESCRIPTION, strlen($description)));
        return $this;
    }

    /**
     * Returns the options.
     *
     * @return Option[]
     */
    public function getOptions()
    {
        if ($this->options === null && $this->_repository !== null) {
            $this->_repository->injectOptions($this);
        }
        return $this->options ?: array();
    }

    /**
     * Sets the options.
     *
     * @param Option[] $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Returns the participants.
     *
     * @return Participant[]
     */
    public function getParticipants()
    {
        if ($this->participants === null && $this->_repository !== null) {
            $this->_repository->injectParticipants($this);
        }
        return $this->participants ?: array();
    }

    /**
     * Sets the participants.
     *
     * @param Participant[] $participants
     * @return $this
     */
    public function setParticipants(array $participants)
    {
        $this->participants = $participants;
        return $this;
    }

    /**
     * Returns the location.
     *
     * @return Location
     */
    public function getLocation()
    {
        if ($this->location === false && $this->_repository !== null) {
            $this->_repository->injectLocation($this);
        }
        return $this->location ?: null;
    }

    /**
     * Sets the location.
     *
     * @param Location $location
     * @return $this
     */
    public function setLocation(Location $location = null)
    {
        $this->location = $location;
        return $this;
    }

    /**
     * Returns the export Excel URL.
     *
     * @return string
     */
    public function getExportExcelUrl()
    {
        if ($this->_info === null && $this->_repository !== null) {
            $this->_repository->injectInfo($this);
        }
        return !empty($this->_info['exportExcelUrl']) ? 'https://doodle.com' . $this->_info['exportExcelUrl'] : '';
    }

    /**
     * Returns the export PDF URL.
     *
     * @return string
     */
    public function getExportPdfUrl()
    {
        if ($this->_info === null && $this->_repository !== null) {
            $this->_repository->injectInfo($this);
        }
        return !empty($this->_info['exportPdfUrl']) ? 'https://doodle.com' . $this->_info['exportPdfUrl'] : '';
    }

    /**
     * Returns the export Print URL.
     *
     * @return string
     */
    public function getExportPrintUrl()
    {
        if ($this->_info === null && $this->_repository !== null) {
            $this->_repository->injectInfo($this);
        }
        return !empty($this->_info['exportPrintUrl']) ? 'https://doodle.com' . $this->_info['exportPrintUrl'] : '';
    }

    /**
     * @return array|null
     * @internal
     */
    public function _getInfo()
    {
        return $this->_info;
    }

    /**
     * @param array $info
     * @return $this
     * @internal
     */
    public function _setInfo(array $info)
    {
        $this->_info = $info;
        return $this;
    }

}
