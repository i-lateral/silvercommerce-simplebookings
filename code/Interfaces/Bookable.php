<?php

namespace ilateral\SimpleBookings\Interfaces;

/**
 * Anything that you want to be bookable needs to implement this interface
 */
interface Bookable
{
    /**
     * Get the available spaces within a date range
     *
     * @param string $start 
     * @param string $end
     *
     * @return int
     */
    public function getAvailableSpaces(string $start, string $end);

    /**
     * Get the number of spaces booked within a date range
     *
     * @param string $start 
     * @param string $end
     *
     * @return int
     */
    public function getBookedSpaces(string $start, string $end);
}