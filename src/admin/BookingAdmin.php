<?php

namespace ilateral\SimpleBookings\Admin;

use DateTime;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Dev\CsvBulkLoader;
use SilverStripe\Core\Config\Config;
use ilateral\SimpleBookings\Model\Booking;
use ilateral\SimpleBookings\Helpers\BookingHelper;
use ilateral\SimpleBookings\Products\EventProduct;
use ilateral\SimpleBookings\Model\ResourceAllocation;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\GridFieldAddOns\GridFieldRecordHighlighter;
use ilateral\SimpleBookings\Forms\GridField\BookingDetailForm_ItemRequest;

class BookingAdmin extends ModelAdmin
{
    private static $url_segment = 'bookings';

    private static $menu_title = 'Bookings';

    private static $menu_priority = 4;

    /**
     * The default start time used for filtering
     *
     * @var string
     */
    private static $default_start_time = "00:00";

    /**
     * The default end time used for filtering
     *
     * @var string
     */
    private static $default_end_time = "23:59";

    private static $managed_models = [
        Booking::class,
        ResourceAllocation::class
    ];

    private static $model_importers = [
        Booking::class => CsvBulkLoader::class,
    ];

    /**
     * Return list of booking alerts to show
     */
    public function getBookingAlerts()
    {
        return [
            'SpacesRemaining' => [
                'comparator' => 'less',
                'patterns' => [
                    '0' => [
                        'status' => 'alert',
                        'message' => _t("Bookings.OverBooked", 'Over Booked'),
                    ]
                ]
            ]
        ];
    }

    /**
     * Update the default edit form
     *
     * @return \SilverStripe\Forms\Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $config = null;
        $grid_field = $form
            ->Fields()
            ->dataFieldByName($this->sanitiseClassName($this->modelClass));

        if (!empty($grid_field)) {
            $config = $grid_field->getConfig();
        }

        if ($this->modelClass == Booking::class && !empty($config)) {
            /** @var GridFieldDetailForm */
            $detail_form = $config
                ->getComponentByType(GridFieldDetailForm::class);
            
            $detail_form
                ->setItemRequestClass(BookingDetailForm_ItemRequest::class);
            
            $config->addComponent(new GridFieldRecordHighlighter($this->getBookingAlerts()));
        }

        $this->extend('updateBookingAdminEditForm', $form);

        return $form;
    }

    /**
     * By default, bookings should  only display confirmed (unless we filter them) and
     * only the next 30 days
     *
     * @return \SilverStripe\ORM\DataList
     */
    public function getList()
    {
        // Bookings get handled via Search Context results
        if ($this->modelClass == Booking::class) {
            /** @var Booking */
            $context = singleton(Booking::class)->getDefaultSearchContext();
            return $context->getResults([]);
        }

        $list = parent::getList();

        $this->extend('updateBookingAdminList', $list);

        return $list;
    }
}
