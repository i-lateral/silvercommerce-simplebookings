<?php

namespace ilateral\SimpleBookings\Helpers;

use DateTime;
use SilverStripe\ORM\DB;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use ilateral\SimpleBookings\Interfaces\Bookable;
use ilateral\SimpleBookings\Model\BookableProduct;
use ilateral\SimpleBookings\Model\Booking;
use ilateral\SimpleBookings\Model\ResourceAllocation;
use LogicException;
use SilverCommerce\CatalogueAdmin\Model\CatalogueProduct;

class BookingHelper
{
    use Configurable, Injectable;

    /**
     * Do BookableProducts lock the shopping cart (so
     * they cannot be edited)?
     *
     * @var    boolean
     * @config 
     */
    private static $lock_cart = true;

    /**
     * Do BookableProducts contain a deliverable component
     * (for example tickets to be posted). By default this
     * module assumes no.
     *
     * @var    boolean
     * @config 
     */
    private static $allow_delivery = false;

    /**
     * Start date used for filtering
     *
     * @var string
     */
    protected $start_date;

    /**
     * End date used for filtering
     *
     * @var string
     */
    protected $end_date;

    /**
     * Product to check for bookings
     *
     * @var CatalogueProduct
     */
    protected $product;

    /**
     * Get an array of product classnames that implement bookable
     *
     * @return array
     */
    public static function getBookableProductClasses()
    {
        return ClassInfo::implementorsOf(Bookable::class);
    }

    public function __construct(string $start, string $end, CatalogueProduct $product)
    {
        $this->setStartDate($start);
        $this->setEndDate($end);
        $this->setProduct($product);
    }

    /**
     * Get a list of bookings within the time range
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getBookings()
    {
        $bookings = Booking::get()
            ->filter(
                [
                    'Item.StockID' => $this->getProduct()->StockID,
                    'Status' => $this->getBookingConfirmedStatus()
                ]
            )->where($this->getWhereFilter());

        return $bookings;
    }

    /**
     * Get a list of Resource Allocations within the time range
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getResourceAllocations()
    {
        return ResourceAllocation::get()->where($this->getWhereFilter(false));
    }

    /**
     * Generate filter used to generate a where statement
     *
     * @param string $start_field     Custom name for the start field
     * @param string $end_field       Custom name for the end field
     *
     * @return array
     */
    public function getWhereFilter($start_field = 'Start', $end_field = 'End')
    {
        $db = DB::get_conn();
        $sql_format = "%Y-%m-%d";
        $date_format = 'Y-m-d';
        $start_field = $db->formattedDatetimeClause($start_field, $sql_format);
        $end_field = $db->formattedDatetimeClause($end_field, $sql_format);
        $date_from = $this->getStartDateObject();
        $date_to = $this->getEndDateObject();

        return [
            $start_field . ' <= ?' =>  $date_to->format($date_format),
            $start_field . ' >= ?' =>  $date_from->format($date_format),
            $end_field . ' >= ?' =>  $date_from->format($date_format),
            $end_field . ' <= ?' =>  $date_to->format($date_format)
        ];
    }

    /**
     * Takes two dates formatted as YYYY-MM-DD and creates an inclusive
     * array of the timecodes between the from and to dates.
     * 
     * Thanks to this stack overflow post:
     * http://stackoverflow.com/questions/4312439/php-return-all-dates-between-two-dates-in-an-array
     *
     * @param string $date_from The starting date
     * @param string $date_to   The end date
     * @param int    $interval  Time period (seconds) to use to make the array
     *
     * @return array
     */
    public static function createDateRangeArray($date_from, $date_to, $interval)
    {
        $range = array();
        $time_from = strtotime($date_from);
        $time_to = strtotime($date_to);

        if ($time_to >= $time_from) {
            while ($time_from < $time_to) {
                $range[] = $time_from;
                $time_from += $interval;
            }
        }

        return $range;
    }

    /**
     * Find the total spaces already booked between the defined dates
     * for the selected product.
     * 
     * @return int
     */
    public function getTotalBookedSpaces()
    {
        // Get all products inside these bookings that
        // match our date range and tally the results
        $bookings = $this->getBookings();
        $total_places = 0;

        foreach ($bookings as $booking) {
            $total_places += $booking->PlacesBooked;
        }

        /*// Now get all allocations and update
        $allocations = $this->getResourceAllocations();
        $all_allocated = false;

        foreach ($allocations as $allocation) {
            if ($allocation->AllocateAll) {
                $all_allocated = true;
            }

            $resources = $allocation->Resources()->Filter('ID', $ID);
            
            foreach ($resources as $product) {
                if ($all_allocated || $product->AllocateAll) {
                    $total_places += $product->AvailablePlaces;
                } else {
                    $start_stamp = strtotime($date_from);
                    $end_stamp = strtotime($date_to);
                    $alloc_start_stamp = strtotime($allocation->Start);
                    $alloc_end_stamp = strtotime($allocation->End);

                    if ($alloc_start_stamp >= $start_stamp && $alloc_start_stamp <= $end_stamp 
                        || $alloc_start_stamp <= $start_stamp && $alloc_end_stamp >= $end_stamp 
                        || $alloc_end_stamp >= $start_stamp && $alloc_end_stamp <= $end_stamp
                    ) {
                        if ($product->Increase) {
                            $total_places -= $product->Quantity;
                        } else {
                            $total_places += $product->Quantity;
                        }
                    }
                }
            }
        }*/

        return $total_places;
    }

    /**
     * Get start date as a datetime object
     *
     * @return DateTime
     */ 
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * Get start date as a datetime object
     *
     * @return DateTime
     */ 
    public function getStartDateObject()
    {
        return new DateTime($this->start_date);
    }

    /**
     * Set start date used for filtering
     *
     * @param string $start_date Start date used for filtering
     *
     * @return self
     */ 
    public function setStartDate(string $start_date)
    {
        $this->start_date = $start_date;
        return $this;
    }

    /**
     * Get end date
     *
     * @return string
     */ 
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * Get end date as a datetime object
     *
     * @return DateTime
     */ 
    public function getEndDateObject()
    {
        return new DateTime($this->end_date);
    }

    /**
     * Set end date used for filtering
     *
     * @param string $end_date End date used for filtering
     *
     * @return self
     */ 
    public function setEndDate(string $end_date)
    {
        $this->end_date = $end_date;
        return $this;
    }

    /**
     * Get product to check for bookings
     *
     * @return CatalogueProduct
     */ 
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set product to check for bookings
     *
     * @param CatalogueProduct  $product  Product to check for bookings
     *
     * @return self
     */ 
    public function setProduct(CatalogueProduct $product)
    {
        // If this product isn't an allowed type, flag an exception
        if (!in_array($product->ClassName, self::getBookableProductClasses())) {
            throw new LogicException('Product must implement "Bookable"');
        }

        $this->product = $product;
        return $this;
    }

    /**
     * Get the status to filter bookings by (only confimed bookings should be counted)
     *
     * @return string 
     */
    public function getBookingConfirmedStatus()
    {
        return Booking::config()->confirmed_status;
    }
}
