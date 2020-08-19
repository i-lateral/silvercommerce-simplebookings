<?php

namespace ilateral\SimpleBookings\Search;

use DateTime;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Core\Injector\Injector;
use ilateral\SimpleBookings\Model\Booking;
use SilverStripe\ORM\Search\SearchContext;
use ilateral\SimpleBookings\Helpers\BookingHelper;
use ilateral\SimpleBookings\Products\EventProduct;

class BookingSearchContext extends SearchContext
{
    /**
     * Get the default intial date for this search
     *
     * @return string
     */
    protected function getDefaultDateOne()
    {
        return (new DateTime())->format('Y-m-d');
    }

    /**
     * Get the default second date for this search
     *
     * @return string
     */
    protected function getDefaultDateTwo()
    {
        return (new DateTime())->modify('+30 days')->format('Y-m-d');
    }

    /**
     * Get the default status for a booking to be filtered by
     *
     * @return string
     */
    protected function getDefaultStatus()
    {
        return Config::inst()->get(Booking::class, 'confirmed_status');
    }

    /**
     * Is a record currently being edited? Used to disable default filters
     * so GridFields don't return an error
     *
     * @return boolean
     */
    protected function isEditingRecord()
    {
        /**
         * If we are querying a record directly, remove the default search filters
         * @var HTTPRequest
         */
        $request = Injector::inst()->get(HTTPRequest::class);
        $url = $request->getURL();
        $parts = explode('/', $url);

        return in_array('item', $parts);
    }

    /**
     * Overwrite default search fields and update with date ranges and
     * a dropdown for status
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function getSearchFields()
    {
        $fields = parent::getSearchFields();
        $obj = singleton($this->modelClass);
        $searchParams = $this->getSearchParams();

        $fields->unshift(
            HeaderField::create(
                'CustomerInfo',
                _t('Bookings.Customer Info', 'Customer Info'),
                4
            )
        );

        // Add date range fields
        $fields->insertBefore(
            'Status',
            HeaderField::create(
                'DateRangeInfo',
                _t('Bookings.BookingsBetween', 'Bookings between these dates'),
                4
            )
        );

        $fields->insertBefore(
            'Status',
            $date_one = DateField::create('DateOne')
                ->setTitle(null)
        );

        if (!isset($searchParams['DateOne'])) {
            $date_one->setValue($this->getDefaultDateOne());
        } else {
            $date_one->setValue($searchParams['DateOne']);
        }

        $fields->insertBefore(
            'Status',
            $date_two = DateField::create('DateTwo')
                ->setTitle(null)
                ->setDescription(_t('Bookings.DateRangeDefault', 'defaults to next 30 days'))
        );

        if (!isset($searchParams['DateTwo'])) {
            $date_two->setValue($this->getDefaultDateTwo());
        } else {
            $date_two->setValue($searchParams['DateTwo']);
        }

        // Update status field to be a dropdown
        $status = $fields->fieldByName('Status');

        if (!empty($status)) {
            $status_field = DropdownField::create(
                'Status',
                $obj->fieldLabel('Status')
            )->setSource($obj->config()->statuses)
            ->setDescription(_t('Bookings.StatusDefault', 'defaults to "confirmed"'));

            if (!isset($searchParams['Status'])) {
                $status_field->setValue($this->getDefaultStatus());
            }
            $fields->replaceField(
                'Status',
                $status_field
            );
        }

        return $fields;
    }

    /**
     * Add additional search filter to list for date range
     *
     * @param array $searchParams
     * @param array|bool|string $sort
     * @param array|bool|string $limit
     * @return DataList
     * @throws Exception
     */
    public function getQuery($searchParams, $sort = false, $limit = false, $existingQuery = null)
    {
        $editing = $this->isEditingRecord();

        if (!$editing && !$this->isStatusFilterSet($searchParams)) {
            $searchParams['Status'] = $this->getDefaultStatus();
        }

        /**
         * @todo This is a bit hacky, ideally need to find a way to access
         * the where filter without passing a product
         */
        $obj = singleton(EventProduct::class);
        $query = parent::getQuery($searchParams, $sort, $limit);

        // If date range selected, apply filter
        if (!$editing && !$this->isDateFilterSet($searchParams)) {
            $obj = singleton(EventProduct::class);

            $helper = BookingHelper::create(
                $this->getDefaultDateOne(),
                $this->getDefaultDateTwo(),
                $obj
            );
            $query = $query->where($helper->getWhereFilter());
        } elseif (isset($searchParams['DateOne']) && isset($searchParams['DateTwo'])) {
            $helper = BookingHelper::create($searchParams['DateOne'], $searchParams['DateTwo'], $obj);
            $query = $query->where($helper->getWhereFilter());
        }

        return $query;
    }

    /**
     * Is the current view filtering by status?
     *
     * @return bool
     */
    public function isStatusFilterSet($searchParams = [])
    {
        if (count($searchParams) == 0) {
            $searchParams = $this->getSearchParams();
        }

        if (count($searchParams) == 0) {
            return false;
        }

        if (isset($searchParams['Status'])) {
            return true;
        }

        return false;
    }

    /**
     * Is the current view filtering by status?
     *
     * @return bool
     */
    public function isDateFilterSet($searchParams = [])
    {
        if (count($searchParams) == 0) {
            $searchParams = $this->getSearchParams();
        }

        if (count($searchParams) == 0) {
            return false;
        }

        if (isset($searchParams['DateOne']) && isset($searchParams['DateTwo'])) {
            return true;
        }

        return false;
    }
}
