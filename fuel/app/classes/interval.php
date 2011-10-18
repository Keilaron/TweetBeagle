<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of interval
 *
 * @author bushra
 */
class Interval
{
	/* * * constants * * */
	const ROUNDING_POLICY_NONE = 'none';
	const ROUNDING_POLICY_UP   = 'up';
	const ROUNDING_POLICY_DOWN = 'down';
	
	protected static $UNITS_SECONDS_EQUIV = array(
			'hours'  => 3600,
			'days'   => 86400,
			'weeks'  => 604800,
	);
	
	/**
	 * @var int The timestamp of the interval's start date 
	 */
	protected $start;
	/**
	 * @var int The timestamp of the interval's end date
	 */
	protected $end;
	/**
	 * @var string The default date unit to be used when calculating offsets
	 */
	protected $defaultUnit = 'days';
	/**
	 * @var string The default rounding policy of date units. See the rounding
	 * policy constants. This property can also take one of PHP's rounding
	 * modes.
	 * @see http://ca2.php.net/manual/en/function.round.php 
	 */
	protected $roundingPolicy = self::ROUNDING_POLICY_UP;
	
	/**
	 * 
	 */
	public function __construct($start, $end = 'now')
	{
		$this->start = is_string($start) ? strtotime($start) : $start;
		$this->end = is_string($end) ? strtotime($end) : $end;
		
		if (!is_numeric($this->start) || !is_numeric($this->end))
			throw new InvalidArgumentException ('Start and End dates must be convertible to timestamps');
		
		if ($this->start > $this->end)
			throw new InvalidArgumentException ('The Start date must be before the End date');
	}
	
	
	public function getStart() 	{	return $this->start; }
	public function setStart($start) { $this->start = $start;	}

	public function getEnd() { return $this->end; }
	public function setEnd($end) { $this->end = $end;	}

	public function getDefaultUnit() { return $this->defaultUnit;	}
	public function setDefaultUnit($defaultUnit) { $this->defaultUnit = $defaultUnit; }
	
	public function getRoundingPolicy()  { return $this->roundingPolicy; }
	public function setRoundingPolicy($roundingPolicy) { $this->roundingPolicy = $roundingPolicy; }

 	
	/**
	 * Calculates various date units that fall within the interval.
	 * @param int $secs An optional number of seconds to be used instead of the 
	 *        interval span.
	 * @return array The array keys are date units maped to their calculated values
	 */
	public function getDateUnits($secs = null)
	{
		$seconds = empty($secs) ? $this->end - $this->start : $secs;
		
		$units = array(
			'hours'  => $seconds / 3600,
			'days'   => $seconds / 24 / 3600,
			'weeks'  => $seconds / 7 / 24 / 3600,
		);
		
		$this->roundUnits($units);
		
		return $units;
	}
	
	/**
	 * The cardinal value of $date calculated as an offset from the begining of
	 * the interval.
	 * @param mixed $date A date represented as string or timestamp (integer)
	 * @param string $unit One of the date units returned by Interval::getDateUnits
	 * @return int The position of $date in the interval
	 */
	public function getDateOffset($date, $unit = null)
	{
		$unit = empty($unit) ? $this->defaultUnit : $unit;
		
		if (is_string($date))
			$date = strtotime($date);
		
		if (!is_numeric($date))
			throw new InvalidArgumentException ('The supplied date must be either a string or an integer');
		
		if ($date > $this->end || $date < $this->start)
			throw new InvalidArgumentException ('The supplied date does not fall within the interval');
		
		$diffUnits = $this->getDateUnits($date - $this->start);
		
		return $diffUnits[$unit];
	}
	
	/**
	 * 
	 * @param int $increment Iteration interval length (multiple) to increment by. Usually (1)
	 * @param callback $callback A PHP Callable that accepts a single parameter: the timestamp of 
	 *        the current iteration as an integer.
	 */
	public function iterate($increment = 1, $callback)
	{
		if (empty($this->defaultUnit)) 
			throw new InvalidArgumentException ('The default unit must be set in order to determine iterations');
		
		$unitIncrementSecs = self::$UNITS_SECONDS_EQUIV[$this->defaultUnit];
		$i = 0;
		
		do
		{
			call_user_func($callback, $i + $this->start);
			$i += $increment * $unitIncrementSecs;
		} while(($i + $this->start) <= $this->end);
		
	}
	
	/**
	 *
	 * @param array $units The date units array returned by Interval::getDateUnits
	 */
	protected function roundUnits(&$units)
	{
		foreach ($units as $unit => &$value)
		{
			switch ($this->roundingPolicy)
			{
				case self::ROUNDING_POLICY_NONE:
					break;
				case self::ROUNDING_POLICY_DOWN:
					$units[$unit] = floor($value);
					break;
				case self::ROUNDING_POLICY_UP:
					$units[$unit] = ceil($value);
					break;
				default:
					$units[$unit] = round($value, 0, $this->roundingPolicy);
					break;
			}
		}
	}
	
	/**
	 * Calculates the number of date units since a specific date until now.
	 * @param mixed $since A date represented as string or timestamp (integer)
	 * @return array see Interval::getDateUnits
	 * @throws
	 * @see Interval::getDateUnits
	 * 
	 
	public function getDateUnitsSince($since)
	{
		$milliseconds = 0;
		
		if (is_string($since))
			$milliseconds = time() - strtotime($since);
		else if (is_numeric($since))
			$milliseconds = time() - $since;
		else
			throw new InvalidArgumentException ('$since must be either a string or an integer');
		
		return self::getDateUnits($milliseconds);
	}
	*/
	
}

?>
