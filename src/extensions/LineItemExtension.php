<?php

namespace ilateral\SimpleBookings\Extensions;

use Exception;
use SilverStripe\ORM\DataExtension;
use ilateral\SimpleBookings\Model\Booking;
use ilateral\SimpleBookings\Helpers\BookingHelper;

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

        // If product data is not available, it should not be bookable
        if (empty($product)) {
            return false;
        }

        $classes = BookingHelper::getBookableProductClasses();

        return in_array($product->ClassName, $classes);
    }

    /**
     * Tap into checking of stock levels (so we can flag overbooking)
     *
     * @return null 
     */
    public function updateCheckStockLevel(&$return, $qty)
    {
        /** @var \SilverCommerce\OrdersAdmin\Model\LineItem */
        $owner = $this->getOwner();

        // If this is not bookable, skip
        if (!$owner->isBookable()) {
            return;
        }

        /** @var \SilverCommerce\CatalogueAdmin\Model\CatalogueProduct  */
        $product = $owner->FindStockItem();
        /** @var Booking */
        $booking = $owner->Booking();

        // If we cannot book, return false
        if (!$product->canBookSpaces($qty, $booking->Start, $booking->End)) {
            $return = -1;
        }
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
