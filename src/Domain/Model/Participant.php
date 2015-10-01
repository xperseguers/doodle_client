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

use Causal\DoodleClient\Domain\Model\Preference;

/**
 * Class Participant.
 *
 * @package Causal\DoodleClient\Domain\Model
 */
class Participant
{

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $avatar;

    /**
     * @var Preference[]
     */
    protected $preferences;

    /**
     * Participant constructor.
     *
     * @param string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
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
     * Returns the name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name.
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = trim($name);
        return $this;
    }

    /**
     * Returns the avatar.
     *
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Sets the avatar.
     *
     * @param string $avatar
     * @return $this
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
        return $this;
    }

    /**
     * Returns the preferences.
     *
     * @return Preference[]
     */
    public function getPreferences()
    {
        return $this->preferences;
    }

    /**
     * Sets the preferences.
     *
     * @param Preference[] $preferences
     * @return $this
     */
    public function setPreferences(array $preferences)
    {
        $this->preferences = $preferences;
        return $this;
    }

}