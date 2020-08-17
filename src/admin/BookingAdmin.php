<?php

namespace ilateral\SimpleBookings\Admin;

use DateTime;
use SilverStripe\Admin\ModelAdmin;
use ilateral\SimpleBookings\Model\Booking;
use ilateral\SimpleBookings\Model\ResourceAllocation;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\GridFieldAddOns\GridFieldRecordHighlighter;
use ilateral\SimpleBookings\Forms\GridField\BookingDetailForm;
use ilateral\SimpleBookings\Forms\GridField\BookingDetailForm_ItemRequest;
use SilverStripe\Dev\CsvBulkLoader;

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
                        'message' => _t("SimpleBookings.OverBookedAlert", 'This is overbooked'),
                    ]
                ]
            ]
        ];
    }

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $fields = $form->Fields();

        /** @var \SilverStripe\Forms\GridField\GridField */
        $gridField = $form->Fields()->fieldByName($this->sanitiseClassName($this->modelClass));
        $config = $gridField->getConfig();

        if ($this->modelClass == Booking::class) {
            /** @var GridFieldDetailForm */
            $detail_form = $config
                ->getComponentByType(GridFieldDetailForm::class);
            
            $detail_form
                ->setItemRequestClass(BookingDetailForm_ItemRequest::class);
            
            $config->addComponent(new GridFieldRecordHighlighter($this->getBookingAlerts()));
        }

        $this->extend('updateEditForm', $form);

        return $form;
    }

    public function getList()
    {
        $list = parent::getList();

        $filter = [];
        
        // Perform complex filtering
        if ($this->modelClass == Booking::class) {
            $query = $this->getRequest()->getVar("q");
            // If a start date and end date are set, filter all dates
            /*if (is_array($query)) {
                $start_object = new Date();
                $end_object = new Date();
                $start_date = null;
                $end_date = null;

                if (array_key_exists("StartDate", $query) && $query["StartDate"]) {
                    $start_object->setValue($query["StartDate"]);
                    $start_date = new DateTime($start_object->getValue() . " " . $this->config()->default_start_time);
                }

                if (array_key_exists("EndDate", $query) && $query["EndDate"]) {
                    $end_object->setValue($query["EndDate"]);
                    $end_date = new DateTime($end_object->getValue() . " " . $this->config()->default_end_time);
                } elseif ($start_date) {
                    $end_date = new DateTime($start_object->getValue() . " " . $this->config()->default_end_time);
                }

                // If both dates are the same, we can assume that it is a one day booking
                if ($start_date && $end_date) {
                    $list = $list
                        ->exclude("End:LessThan", $start_date->format("Y-m-d H:i:s"))
                        ->exclude("Start:GreaterThan", $end_date->format("Y-m-d H:i:s"));
                }
            }*/
        }
        
        $this->extend('updateList', $list);

        return $list;
    }
}
