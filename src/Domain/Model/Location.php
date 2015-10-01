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

/**
 * Class Location.
 *
 * @package Causal\DoodleClient\Domain\Model
 */
class Location
{

    /**
     * @var Option
     */
    protected $name;

    /**
     * @var string
     */
    protected $address;

    /**
     * @var string
     */
    protected $country;

    /**
     * Preference constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
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
     * Returns the address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Sets the address.
     *
     * @param string $address
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = trim($address);
        return $this;
    }

    /**
     * Returns the country.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Sets the country.
     *
     * @param string $country
     * @return $this
     */
    public function setCountry($country)
    {
        $this->country = trim($country);
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

}