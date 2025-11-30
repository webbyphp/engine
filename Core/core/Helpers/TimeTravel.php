<?php


/**
 * This file is part of WebbyPHP Framework.
 *
 * (c) Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * TimeTravel
 * 
 * Travel through time
 *
 * A helper class to work with some
 * DateTime | DatePeriod | DateInterval functionalities
 * Enhanced for production use with better error handling and validation
 * 
 * @author Developer Kwame | <Kwame Oteng Appiah-Nti>
 * @version 3.0.0
 */

namespace Base\Helpers;

use DateTime;
use DatePeriod;
use DateInterval;
use DateTimeZone;
use Exception;
use InvalidArgumentException;

class TimeTravel
{
    private $defaultSeconds = (60 * 60);
    private $defaultMinutes = "30 minutes";
    public $defaultFormat = 'Y-m-d H:i:s';
    private $startTime = null;
    private $endTime = null;
    private $startDatetime = null;
    private $endDatetime = null;
    protected $datetime;
    public $periods = [];
    private $timezone = null;
    private $autoFormat = false;
    private $outputFormat = 'Y-m-d H:i:s';

    // Constants for formatting
    private const DAY_WITH_HOUR = '%a Days and %h hours';
    private const ONLY_DAYS = '%a Days';
    private const ONLY_YEARS = '%a Years';
    private const ONLY_HOURS = '%h hours and %i minutes';
    private const FULL_FORMAT = '%y years, %m months, %a days, %h hours, %i minutes';

    // Validation patterns
    private const VALID_INTERVALS = [
        'second',
        'seconds',
        'minute',
        'minutes',
        'hour',
        'hours',
        'day',
        'days',
        'week',
        'weeks',
        'month',
        'months',
        'year',
        'years'
    ];

    /**
     * Constructor
     * @param string|null $timezone Optional timezone
     * @param bool $autoFormat Enable auto-formatting
     * @param string $outputFormat Default output format when auto-formatting
     */
    public function __construct($timezone = null, $autoFormat = false, $outputFormat = 'Y-m-d H:i:s')
    {
        if ($timezone) {
            try {
                $this->timezone = new DateTimeZone($timezone);
            } catch (Exception $e) {
                // Log error but continue with system default
                error_log("TimeTravel: Invalid timezone '{$timezone}', using system default");
                $this->timezone = null;
            }
        }

        $this->autoFormat = $autoFormat;
        $this->outputFormat = $outputFormat;
    }

    /**
     * Create a DateInterval from string representation
     * Enhanced with better validation and error handling
     * 
     * @param string $interval
     * @return DateInterval
     * @throws InvalidArgumentException
     */
    public static function interval($interval = '1 hour'): DateInterval
    {
        if (empty($interval) || !is_string($interval)) {
            throw new InvalidArgumentException('Interval must be a non-empty string');
        }

        $interval = strtolower(trim($interval));
        $parts = explode(' ', $interval);

        if (count($parts) < 2) {
            throw new InvalidArgumentException('Invalid interval format. Expected format: "number unit" (e.g., "1 hour")');
        }

        $number = (int) $parts[0];
        $unit = $parts[1];

        // Validate number
        if ($number <= 0) {
            throw new InvalidArgumentException('Interval number must be positive');
        }

        // Validate unit
        if (!in_array($unit, self::VALID_INTERVALS)) {
            throw new InvalidArgumentException("Invalid interval unit: {$unit}");
        }

        // Build interval string
        $time_interval = '';

        if (in_array($unit, ['hour', 'hours'])) {
            $time_interval = "PT{$number}H";
        } elseif (in_array($unit, ['minute', 'minutes'])) {
            $time_interval = "PT{$number}M";
        } elseif (in_array($unit, ['second', 'seconds'])) {
            $time_interval = "PT{$number}S";
        } elseif (in_array($unit, ['year', 'years'])) {
            $time_interval = "P{$number}Y";
        } elseif (in_array($unit, ['month', 'months'])) {
            $time_interval = "P{$number}M";
        } elseif (in_array($unit, ['week', 'weeks'])) {
            $time_interval = "P{$number}W";
        } elseif (in_array($unit, ['day', 'days'])) {
            $time_interval = "P{$number}D";
        }

        try {
            return new DateInterval($time_interval);
        } catch (Exception $e) {
            throw new InvalidArgumentException("Failed to create DateInterval: " . $e->getMessage());
        }
    }

    /**
     * Enhanced timestamp handling with better validation
     * 
     * @param string $dayOfWeek
     * @return int|false
     */
    private function useTimestamp(string $dayOfWeek)
    {
        $dayOfWeek = strtolower(trim($dayOfWeek));

        try {
            if (strpos($dayOfWeek, 'next') !== false || strpos($dayOfWeek, 'last') !== false) {
                $timestamp = strtotime($dayOfWeek);
                if ($timestamp === false) {
                    throw new InvalidArgumentException("Invalid date string: {$dayOfWeek}");
                }
                return $timestamp;
            }
            return time();
        } catch (Exception $e) {
            error_log("TimeTravel: Error parsing date '{$dayOfWeek}': " . $e->getMessage());
            return time();
        }
    }

    /**
     * Create a DateTime object with timezone support
     * 
     * @param string $date
     * @return DateTime
     * @throws InvalidArgumentException
     */
    private function createDateTime($date = ''): DateTime
    {
        if (empty($date)) {
            $date = date($this->defaultFormat);
        }

        try {
            $datetime = new DateTime($date, $this->timezone);
            return $datetime;
        } catch (Exception $e) {
            throw new InvalidArgumentException("Invalid date format: {$date}. Error: " . $e->getMessage());
        }
    }

    /**
     * Travel to a specific date with optional interval
     * Enhanced with better validation and auto-formatting support
     * 
     * @param string $date
     * @param string $interval
     * @return TimeTravel|string
     * @throws InvalidArgumentException
     */
    public function to($date = '', $interval = '')
    {
        try {
            $datetime = $this->createDateTime($date);

            if (!empty($interval)) {
                $intervalObj = self::interval($interval);
                $datetime->add($intervalObj);
            }

            $this->datetime = $datetime;

            // Return formatted string if auto-formatting is enabled
            if ($this->autoFormat) {
                return $this->datetime->format($this->outputFormat);
            }

            return $this;
        } catch (Exception $e) {
            throw new InvalidArgumentException("Error in to() method: " . $e->getMessage());
        }
    }

    /**
     * Travel forward for a specific interval
     * 
     * @param string $interval
     * @return TimeTravel|string
     * @throws InvalidArgumentException
     */
    public function for($interval = '1 minute')
    {
        try {
            $datetime = $this->createDateTime();
            $intervalObj = self::interval($interval);
            $this->datetime = $datetime->add($intervalObj);

            // Return formatted string if auto-formatting is enabled
            if ($this->autoFormat) {
                return $this->datetime->format($this->outputFormat);
            }

            return $this;
        } catch (Exception $e) {
            throw new InvalidArgumentException("Error in for() method: " . $e->getMessage());
        }
    }

    /**
     * Set date range for operations
     * Enhanced with validation
     * 
     * @param string $startDatetime
     * @param string $endDatetime
     * @return TimeTravel
     * @throws InvalidArgumentException
     */
    public function between($startDatetime, $endDatetime): TimeTravel
    {
        try {
            $start = $this->createDateTime($startDatetime);
            $end = $this->createDateTime($endDatetime);

            if ($start >= $end) {
                throw new InvalidArgumentException("Start date must be before end date");
            }

            $this->startDatetime = $start;
            $this->endDatetime = $end;

            return $this;
        } catch (Exception $e) {
            throw new InvalidArgumentException("Error in between() method: " . $e->getMessage());
        }
    }

    /**
     * Generate periods with specific interval
     * Enhanced with validation
     * 
     * @param string $interval
     * @return TimeTravel
     * @throws InvalidArgumentException
     */
    public function every($interval = '1 minute'): TimeTravel
    {
        try {
            if ($this->startDatetime && $this->endDatetime) {
                $intervalObj = self::interval($interval);

                // Add DATEPERIOD_EXCLUDE_START_DATE flag to exclude start date
                $ranges = new DatePeriod(
                    $this->startDatetime,
                    $intervalObj,
                    $this->endDatetime,
                    DatePeriod::EXCLUDE_START_DATE
                );

                $periods = [];
                foreach ($ranges as $range) {
                    $periods[] = clone $range; // Clone to avoid reference issues
                }

                $this->periods = $periods;
                return $this;
            }

            // Fallback behavior
            $datetime = $this->createDateTime();
            $intervalObj = self::interval($interval);
            $this->datetime = $datetime->add($intervalObj);

            return $this;
        } catch (Exception $e) {
            throw new InvalidArgumentException("Error in every() method: " . $e->getMessage());
        }
    }

    /**
     * Travel back in time
     * Enhanced with better parsing and auto-formatting support
     * 
     * @param string $interval
     * @param string $time
     * @return TimeTravel|string
     * @throws InvalidArgumentException
     */
    public function back($interval = '0 day', $time = '')
    {
        try {
            $date = $this->createDateTime($time);

            // Handle special date strings like 'next monday', 'last friday'
            if ($timestamp = $this->useTimestamp($interval)) {
                $date->setTimestamp($timestamp);
                $this->datetime = $date;

                // Return formatted string if auto-formatting is enabled
                if ($this->autoFormat) {
                    return $this->datetime->format($this->outputFormat);
                }

                return $this;
            }

            $intervalObj = self::interval($interval);
            $this->datetime = $date->sub($intervalObj);

            // Return formatted string if auto-formatting is enabled
            if ($this->autoFormat) {
                return $this->datetime->format($this->outputFormat);
            }

            return $this;
        } catch (Exception $e) {
            throw new InvalidArgumentException("Error in back() method: " . $e->getMessage());
        }
    }

    /**
     * Convert datetime objects to timestamps
     * Enhanced with validation
     * 
     * @return TimeTravel
     * @throws InvalidArgumentException
     */
    public function strToTime(): TimeTravel
    {
        if (!$this->startDatetime || !$this->endDatetime) {
            throw new InvalidArgumentException("Start and end datetime must be set before calling strToTime()");
        }

        try {
            $this->startTime = $this->startDatetime->getTimestamp();
            $this->endTime = $this->endDatetime->getTimestamp();

            return $this;
        } catch (Exception $e) {
            throw new InvalidArgumentException("Error converting to timestamp: " . $e->getMessage());
        }
    }

    /**
     * Get difference in days and hours format
     * 
     * @return string
     */
    public function dayWithHour(): string
    {
        $this->validateDateRange();
        $difference = $this->endDatetime->diff($this->startDatetime);
        return $difference->format(self::DAY_WITH_HOUR);
    }

    /**
     * Get difference in days only
     * 
     * @return string
     */
    public function dayDifference(): string
    {
        $this->validateDateRange();
        $difference = $this->endDatetime->diff($this->startDatetime);
        return $difference->format(self::ONLY_DAYS);
    }

    /**
     * Get difference in hours
     * Enhanced with better calculation
     * 
     * @param bool $hrs Short format flag
     * @return string
     */
    public function hourDifference($hrs = false): string
    {
        $this->validateDateRange();

        try {
            $this->strToTime();
            $difference = abs($this->endTime - $this->startTime);
            $hours = $difference / $this->defaultSeconds;

            return $hrs ? number_format($hours, 2) . ' hrs' : number_format($hours, 2) . ' hours';
        } catch (Exception $e) {
            throw new InvalidArgumentException("Error calculating hour difference: " . $e->getMessage());
        }
    }

    /**
     * Get duration in various formats
     * Enhanced with more options
     * 
     * @param string $format
     * @return string
     */
    public function duration($format = 'hours'): string
    {
        switch (strtolower($format)) {
            case 'hours':
                return $this->hourDifference();
            case 'days':
                return $this->dayDifference();
            case 'day_hour':
                return $this->dayWithHour();
            case 'full':
                $this->validateDateRange();
                $difference = $this->endDatetime->diff($this->startDatetime);
                return $difference->format(self::FULL_FORMAT);
            case 'minutes':
                $this->validateDateRange();
                $this->strToTime();
                $difference = abs($this->endTime - $this->startTime);
                $minutes = $difference / 60;
                return number_format($minutes, 2) . ' minutes';
            case 'seconds':
                $this->validateDateRange();
                $this->strToTime();
                $difference = abs($this->endTime - $this->startTime);
                return $difference . ' seconds';
            default:
                return $this->hourDifference();
        }
    }

    /**
     * Format current datetime
     * 
     * @param string $format
     * @return string
     * @throws InvalidArgumentException
     */
    public function format($format = 'Y-m-d H:i:s'): string
    {
        if (!$this->datetime) {
            throw new InvalidArgumentException("No datetime set. Use to(), for(), or back() first.");
        }

        return $this->datetime->format($format);
    }

    /**
     * Alias for format method
     * 
     * @param string $format
     * @return string
     */
    public function showTime($format = 'Y-m-d H:i:s'): string
    {
        return $this->format($format);
    }

    /**
     * Format periods array
     * Enhanced with validation
     * 
     * @param string $format
     * @return array|false
     */
    public function formatTo($format = 'Y-m-d H:i:s')
    {
        if (empty($this->periods)) {
            return false;
        }

        $periods = [];
        foreach ($this->periods as $period) {
            if ($period instanceof DateTime) {
                $periods[] = $period->format($format);
            }
        }

        return $periods;
    }

    /**
     * Alias for formatTo method
     * 
     * @param string $format
     * @return array|false
     */
    public function showTimes($format = 'Y-m-d H:i:s')
    {
        return $this->formatTo($format);
    }

    /**
     * Get week of month for a given date
     * 
     * @param string|int|null $date Date string, timestamp, or null for current datetime
     * @return int
     * @throws InvalidArgumentException
     */
    public function weekOfMonth($date = null): int
    {
        try {
            if ($date === null && $this->datetime) {
                $timestamp = $this->datetime->getTimestamp();
            } elseif (is_numeric($date)) {
                $timestamp = (int) $date;
            } elseif (is_string($date)) {
                $dateObj = $this->createDateTime($date);
                $timestamp = $dateObj->getTimestamp();
            } else {
                $timestamp = time();
            }

            // Get the first day of the month
            $firstOfMonth = strtotime(date("Y-m-01", $timestamp));

            if ($firstOfMonth === false) {
                throw new InvalidArgumentException("Invalid date for week of month calculation");
            }

            // Apply formula: current week of year - first week of month + 1
            return $this->weekOfYear($timestamp) - $this->weekOfYear($firstOfMonth) + 1;
        } catch (Exception $e) {
            throw new InvalidArgumentException("Error calculating week of month: " . $e->getMessage());
        }
    }

    /**
     * Get week of year for a given date
     * Enhanced with better edge case handling
     * 
     * @param string|int|null $date Date string, timestamp, or null for current datetime
     * @return int
     * @throws InvalidArgumentException
     */
    public function weekOfYear($date = null): int
    {
        try {
            if ($date === null && $this->datetime) {
                $timestamp = $this->datetime->getTimestamp();
            } elseif (is_numeric($date)) {
                $timestamp = (int) $date;
            } elseif (is_string($date)) {
                $dateObj = $this->createDateTime($date);
                $timestamp = $dateObj->getTimestamp();
            } else {
                $timestamp = time();
            }

            $weekOfYear = (int) date("W", $timestamp);
            $month = (int) date('n', $timestamp);

            if ($month === 1 && $weekOfYear > 51) {
                // It's the last week of the previous year
                return 0;
            } elseif ($month === 12 && $weekOfYear === 1) {
                // It's the first week of the next year
                return 53;
            } else {
                // It's a "normal" week
                return $weekOfYear;
            }
        } catch (Exception $e) {
            throw new InvalidArgumentException("Error calculating week of year: " . $e->getMessage());
        }
    }

    /**
     * Validate that date range is set
     * 
     * @throws InvalidArgumentException
     */
    private function validateDateRange(): void
    {
        if (!$this->startDatetime || !$this->endDatetime) {
            throw new InvalidArgumentException("Date range not set. Use between() method first.");
        }
    }

    /**
     * Reset the instance for reuse
     * 
     * @return TimeTravel
     */
    public function reset(): TimeTravel
    {
        $this->startTime = null;
        $this->endTime = null;
        $this->startDatetime = null;
        $this->endDatetime = null;
        $this->datetime = null;
        $this->periods = [];

        return $this;
    }

    /**
     * Get current timezone
     * 
     * @return DateTimeZone|null
     */
    public function getTimezone(): ?DateTimeZone
    {
        return $this->timezone;
    }

    /**
     * Set timezone
     * 
     * @param string $timezone
     * @return TimeTravel
     * @throws InvalidArgumentException
     */
    public function setTimezone(string $timezone): TimeTravel
    {
        try {
            $this->timezone = new DateTimeZone($timezone);

            // Update existing datetime objects if they exist
            if ($this->datetime) {
                $this->datetime->setTimezone($this->timezone);
            }
            if ($this->startDatetime) {
                $this->startDatetime->setTimezone($this->timezone);
            }
            if ($this->endDatetime) {
                $this->endDatetime->setTimezone($this->timezone);
            }

            return $this;
        } catch (Exception $e) {
            throw new InvalidArgumentException("Invalid timezone: {$timezone}");
        }
    }

    /**
     * Check if current datetime is in the future
     * 
     * @return bool
     */
    public function isFuture(): bool
    {
        if (!$this->datetime) {
            return false;
        }

        $now = new DateTime('now', $this->timezone);
        return $this->datetime > $now;
    }

    /**
     * Check if current datetime is in the past
     * 
     * @return bool
     */
    public function isPast(): bool
    {
        if (!$this->datetime) {
            return false;
        }

        $now = new DateTime('now', $this->timezone);
        return $this->datetime < $now;
    }

    /**
     * Add business days (excludes weekends)
     * Useful for payment scheduling
     * 
     * @param int $days
     * @param string $startDate
     * @return TimeTravel|string
     * @throws InvalidArgumentException
     */
    public function addBusinessDays(int $days, string $startDate = '')
    {
        if ($days < 0) {
            throw new InvalidArgumentException("Business days must be positive");
        }

        try {
            $datetime = $this->createDateTime($startDate);
            $addedDays = 0;

            while ($addedDays < $days) {
                $datetime->add(new DateInterval('P1D'));

                // Skip weekends (Saturday = 6, Sunday = 0)
                $dayOfWeek = (int) $datetime->format('w');
                if ($dayOfWeek !== 0 && $dayOfWeek !== 6) {
                    $addedDays++;
                }
            }

            $this->datetime = $datetime;

            // Return formatted string if auto-formatting is enabled
            if ($this->autoFormat) {
                return $this->datetime->format($this->outputFormat);
            }

            return $this;
        } catch (Exception $e) {
            throw new InvalidArgumentException("Error adding business days: " . $e->getMessage());
        }
    }

    /**
     * Enable auto-formatting for fluent syntax
     * 
     * @param string $format Output format to use
     * @return TimeTravel
     */
    public function autoFormat(string $format = 'Y-m-d H:i:s'): TimeTravel
    {
        $this->autoFormat = true;
        $this->outputFormat = $format;
        return $this;
    }

    /**
     * Disable auto-formatting
     * 
     * @return TimeTravel
     */
    public function disableAutoFormat(): TimeTravel
    {
        $this->autoFormat = false;
        return $this;
    }

    /**
     * Set the output format for auto-formatting
     * 
     * @param string $format
     * @return TimeTravel
     */
    public function setOutputFormat(string $format): TimeTravel
    {
        $this->outputFormat = $format;
        return $this;
    }

    /**
     * Get the current output format
     * 
     * @return string
     */
    public function getOutputFormat(): string
    {
        return $this->outputFormat;
    }

    /**
     * Check if auto-formatting is enabled
     * 
     * @return bool
     */
    public function isAutoFormatEnabled(): bool
    {
        return $this->autoFormat;
    }

    /**
     * Magic method to handle string conversion
     * Allows the object to be used directly as a string
     * 
     * @return string
     */
    public function __toString(): string
    {
        if ($this->datetime) {
            return $this->datetime->format($this->outputFormat);
        }

        return '';
    }
}
