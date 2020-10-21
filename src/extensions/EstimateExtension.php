<?php

namespace ilateral\SimpleBookings\Extensions;

use ilateral\SimpleBookings\Model\Booking;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverStripe\ORM\DataExtension;

class EstimateExtension extends DataExtension
{
    /**
     * Get a list of bookings from the current estimate (based on the attached products)
     */
    public function getBookings()
    {
        return Booking::get()->filter(
            'ItemID',
            $this->getOwner()->Items()->column('ID')
        );
    }

    /**
     * Perform booking functions when the invoice is saved
     */
    public function onBeforeWrite()
    {
        /**
         * @var \SilverCommerce\OrdersAdmin\Model\Estimate
         */
        $owner = $this->getOwner();
        $bookings = $owner->getBookings();

        foreach ($bookings as $booking) {
            $write = false;

            // If the current booking is not assigned to this customer, update it
            if ($owner->Customer()->exists() && $owner->CustomerID != $booking->CustomerID) {
                $booking->CustomerID = $owner->CustomerID;
                $write = true;
            }

            // If this invoice has a booking and is paid, mark all linked bookings as confirmed
            if (is_a($owner, Invoice::class) && $owner->isChanged('Status') && $owner->isPaid()) {
                $booking->markConfirmed();
                $write = true;
            }

            if ($write) {
                $booking->write();
            }
        }
    }
}
