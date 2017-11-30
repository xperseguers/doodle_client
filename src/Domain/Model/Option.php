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
 * Class Option.
 *
 * @package Causal\DoodleClient\Domain\Model
 */
class Option
{

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $text = '';

    /**
     * @var \DateTime
     */
    protected $dateStart;

    /**
     * @var \DateTime
     */
    protected $dateEnd;

    /**
     * Option constructor.
     *
     * @param int $dateId
     * @param \DateTime $start
     * @param \DateTime $end
     */
    public function __construct(int $dateId, \DateTime $start, \DateTime $end)
    {
        $this->id = $dateId;
        $this->dateStart = $start;
        $this->dateEnd = $end;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Returns the text.
     *
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Returns the date.
     *
     * @return \DateTime
     */
    public function getDateStart()
    {
        return $this->dateStart;
    }

    /**
     * Returns the date.
     *
     * @return \DateTime
     */
    public function getDateEnd(): \DatTime
    {
        return $this->dateEnd;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $out = $this->text;

        if ($this->dateStart !== null) {
            $out = strftime('%a %d.%m.%Y %R', $this->dateStart->getTimestamp());
            if ($this->dateEnd !== null && $this->dateEnd->getTimestamp() > 0) {
                $out .= ' - ' . strftime('%a %d.%m.%Y %R', $this->dateEnd->getTimestamp());
            }
        }

        return $out;
    }

}
