<?php

namespace ilateral\SimpleBookings\Interfaces;

/**
 * Anything that you want to be bookable needs to implement this interface
 */
interface Bookable
{
    /**
     * Get the total number of spaces allowed within a date range
     *
     * @param string $start
     * @param string $end
     *
     * @return int
     */
    public function getPossibleSpaces(string $start, string $end);

    /**
     * Get the number of spaces booked within a date range
     *
     * @param string $start
     * @param string $end
     *
     * @return int
     */
    public function getBookedSpaces(string $start, string $end);

    /**
     * Get the number of spaces remaining in this date range
     *
     * @param string $start
     * @param string $end
     *
     * @return int
     */
    public function getRemainingSpaces(string $start, string $end);

    /**
     * Can the provided number of spaces be booked within this date range
     *
     * @param int    $quantity
     * @param string $start
     * @param string $end
     *
     * @return bool
     */
    public function canBookSpaces(int $quantity, string $start, string $end);

    /**
     * Overwrite default stock checking so that this module can perform custom
     * availability check
     *
     * @return int
     */
    public function getStockLevel();
}
