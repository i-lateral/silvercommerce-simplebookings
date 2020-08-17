<?php

namespace ilateral\SimpleBookings\Extensions;

use ilateral\SimpleBookings\Helpers\BookingHelper;
use SilverStripe\ORM\DataExtension;
use ilateral\SimpleBookings\Model\Booking;

class LineItemExtension extends DataExtension
{
    private static $belongs_to = [
        'Booking' => Booking::class
    ];

    /**
     * Is this item bookable (contains some form of bookable product)
     *
     * @return boolean
     */
    public function isBookable()
    {
        $owner = $this->getOwner();
        $product = $owner->FindStockItem();
        $classes = BookingHelper::getBookableProductClasses();

        return in_array($product->ClassName, $classes);
    }

    /**
     * Each line item that is bookable needs a relevent booking
     *
     */
    public function onAfterWrite()
    {
        /** @var \SilverCommerce\OrdersAdmin\Model\LineItem */
        $owner = $this->getOwner();

        // Move settings to booking
        if ($owner->isBookable()) {
            /** @var \ilateral\SimpleBookings\Model\Booking */
            $booking = $owner->Booking();
            $booking->ItemID = $owner->ID;
            $booking->CustomerID = $owner->Parent()->CustomerID;
            $booking->StockID = $owner->StockID;
            $booking->Spaces = $owner->Quantity;
            $booking->write();
        }
    }

    /**
     * If any items linked to pending bookings are deleted,
     * ensure the booking is also deleted
     */
    public function onAfterDelete()
    {
        /** @var \SilverCommerce\OrdersAdmin\Model\LineItem */
        $owner = $this->getOwner();
        /** @var \ilateral\SimpleBookings\Model\Booking */
        $booking = $owner->Booking();

        // Move settings to booking
        if ($booking->exists() && $booking->isPending()) {
            $booking->delete();
        }
    }
}
