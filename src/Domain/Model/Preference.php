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
 * Class Preference.
 *
 * @package Causal\DoodleClient\Domain\Model
 */
class Preference
{

    /**
     * @var Option
     */
    protected $option;

    /**
     * @var string
     */
    protected $value;

    /**
     * Preference constructor.
     *
     * @param Option $option
     * @param string $value
     */
    public function __construct(Option $option, $value)
    {
        $this->option = $option;
        $this->value = $value;
    }

    /**
     * Returns the option.
     *
     * @return Option
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * Returns the value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }

}