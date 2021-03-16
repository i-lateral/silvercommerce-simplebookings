<?php

namespace ilateral\SimpleBookings\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Security\Security;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Security\Permission;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverShop\HasOneField\HasOneButtonField;
use SilverStripe\Security\PermissionProvider;
use SilverCommerce\ContactAdmin\Model\Contact;
use SilverCommerce\OrdersAdmin\Model\LineItem;
use ilateral\SimpleBookings\Admin\BookingAdmin;
use SilverCommerce\OrdersAdmin\Admin\OrderAdmin;
use ilateral\SimpleBookings\Helpers\BookingHelper;
use ilateral\SimpleBookings\Products\EventProduct;
use SilverCommerce\OrdersAdmin\Factory\LineItemFactory;
use ilateral\SimpleBookings\Search\BookingSearchContext;
use Sheadawson\DependentDropdown\Forms\DependentDropdownField;
use SilverCommerce\CatalogueAdmin\Model\CatalogueProduct;
use SilverCommerce\OrdersAdmin\Factory\OrderFactory;
use SilverCommerce\OrdersAdmin\Model\Estimate;

/**
 * A single booking that is linked to an invoice. Each lineitem on the Invoice constitutes a resource on this booking
 *
 * @param string StockID
 * @param string Status
 * @param string Start
 * @param string End
 * @param int    Spaces
 * @param string SpecialInstructions
 *
 * @method LineItem  Item
 * @method Contact   Customer
 * @method EventDate EventDate
 */
class Booking extends DataObject implements PermissionProvider
{
    private static $table_name = "SimpleBookings_Booking";

    private static $statuses = [
        'pending'   => 'Pending',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled'
    ];

    private static $pending_status = 'pending';

    private static $confirmed_status = 'confirmed';

    private static $cancelled_status = 'cancelled';

    private static $db = [
        'StockID'       => 'Varchar',
        'Status'        => 'Varchar',
        'Start'         => 'Datetime',
        'End'           => 'Datetime',
        'Spaces'        => 'Int',
        'SpecialInstructions' => 'Text'
    ];

    private static $has_one = [
        'Customer' => Contact::class,
        'Item' => LineItem::class,
        'EventDate' => EventDate::class
    ];

    private static $casting = [
        'SpacesRemaining'=> 'Int',
        'Overbooked'     => 'Boolean',
        'TotalCost'      => 'Currency'
    ];

    private static $field_labels = [
        'StockID'           => 'Product',
        'Spaces'            => 'Spaces to Book',
        'Start'             => 'Start Date & Time',
        'End'               => 'End Date & Time',
        'Customer.FirstName' => 'First Name',
        'Customer.Surname'   => 'Surname',
        'Customer.Email'     => 'Email',
        'Invoice.FullRef'   => 'Invoice Ref'
    ];

    private static $summary_fields = [
        'ID',
        'Start',
        'End',
        'Title',
        'Spaces',
        'Customer.FirstName',
        'Customer.Surname',
        'Customer.Email',
        'Invoice.FullRef',
        'SpecialInstructions',
        'Status'
    ];

    /**
     * Default search fields, additional date fields added via search context
     *
     * @var array
     * @config
     */
    private static $searchable_fields = [
        'Customer.FirstName',
        'Customer.Surname',
        'Customer.Email',
        'Status'
    ];

    private static $defaults = [
        "Status"      => 'pending'
    ];

    private static $cascade_deletes = [
        'Item'
    ];

    private static $default_sort = 'Start DESC';

    /**
     * Use the base title for the original bookable product
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getBaseProduct()->Title;
    }

    /**
     * Mark this booking pending
     *
     * @return self
     */
    public function markPending()
    {
        $status = $this->config()->pending_status;
        $this->Status = $status;
        return $this;
    }

    /**
     * Is this booking pending?
     *
     * @return self
     */
    public function isPending()
    {
        return $this->Status == $this->config()->pending_status;
    }

    /**
     * Mark this booking as confirmed
     *
     * @return self
     */
    public function markConfirmed()
    {
        $status = $this->config()->confirmed_status;
        $this->Status = $status;
        return $this;
    }

    /**
     * Is this booking confirmed?
     *
     * @return self
     */
    public function isConfirmed()
    {
        return $this->Status == $this->config()->confirmed_status;
    }

    /**
     * Mark this booking as cancelled
     *
     * @return self
     */
    public function markCancelled()
    {
        $status = $this->config()->cancelled_status;
        $this->Status = $status;
        return $this;
    }

    /**
     * Is this booking confirmed?
     *
     * @return self
     */
    public function isCancelled()
    {
        return $this->Status == $this->config()->cancelled_status;
    }

    /**
     * Get the base invoice from the underlying line item
     *
     * @return Invoice
     */
    public function getInvoice()
    {
        return $this->Item()->Parent();
    }

    /**
     * Get the product this booking was made against
     * (also accepts optional stock ID)
     *
     * 
     *
     * @return CatalogueProduct
     */
    public function getBaseProduct($stock_id = null)
    {
        if (empty($stock_id)) {
            $stock_id = $this->StockID;
        }
        $product = BookingHelper::getBookableProducts()
            ->filter('StockID', $stock_id)
            ->first();

        if (empty($product)) {
            $product = CatalogueProduct::create();
            $product->ID = -1;
        }

        return $product;
    }

    /**
     * Get the number of spaces available for this booking
     *
     * @return int
     */
    public function getSpacesRemaining()
    {
        $product = $this->getBaseProduct();

        if ($product->exists() && !empty($this->Start) && !empty($this->End)) {
            $helper = BookingHelper::create($this->Start, $this->End, $product);
            return $helper->getRemainingSpaces();
        }

        return 0;
    }

    /**
     * Link to view this item in the CMS
     *
     * @return string
     */
    public function CMSViewLink()
    {
        return Controller::join_links(
            "admin",
            BookingAdmin::config()->url_segment,
            "Booking",
            "EditForm",
            "field",
            "Booking",
            "item",
            $this->ID,
            "view"
        );
    }

    /**
     * Link to view this item's order in the CMS
     *
     * @return string
     */
    public function CMSInvoiceLink()
    {
        $invoice = $this->getInvoice();
        if ($invoice->exists()) {
            return Controller::join_links(
                "admin",
                OrderAdmin::config()->url_segment,
                $this->sanitiseClassName($invoice->ClassName),
                "EditForm",
                "field",
                $this->sanitiseClassName($invoice->ClassName),
                "item",
                $invoice->ID,
                "edit"
            );
        }

        return "";
    }

    /**
     * Get the total cost of this booking, based on all products added
     * and the total number of days
     *
     * @return float
     */
    public function getTotalCost()
    {
        return $this->getInvoice()->getTotal();
    }

    /**
     * Link to edit this item in the CMS
     *
     * @return string
     */
    public function CMSEditLink()
    {
        return Controller::join_links(
            "admin",
            BookingAdmin::config()->url_segment,
            "Booking",
            "EditForm",
            "field",
            "Booking",
            "item",
            $this->ID,
            "edit"
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $self = $this;
        $this->beforeUpdateCMSFields(
            function ($fields) use ($self) {
                $products = BookingHelper::getBookableProducts();
                $self = $this;

                // Add data source for event dates (if the product is an event)
                $event_dates = function($value) use ($self) {
                    /** @var EventProduct */
                    $product = $self->getBaseProduct($value);

                    if (!is_a($product, EventProduct::class)) {
                        return [];
                    }

                    return $product->Dates()->map();
                };

                // Swap out status and stock ID for dropdowns
                $fields->addFieldsToTab(
                    'Root.Main',
                    [
                        $stock_field = DropdownField::create('StockID', $this->fieldLabel('StockID'))
                            ->setSource($products->map('StockID', 'Title')),
                        DependentDropdownField::create('EventDateID', $this->fieldLabel('EventDate'), $event_dates)
                            ->setDepends($stock_field)
                            ->setEmptyString(_t(__CLASS__ . '.SelectDate', 'Select the date of the event (if relevent)')),
                        DropdownField::create('Status', $this->fieldLabel('Status'))
                            ->setSource($self->config()->statuses),
                    ],
                    'Start'
                );

                // Add calculated booking info
                $fields->addFieldToTab(
                    'Root.Main',
                    ReadonlyField::create('SpacesRemaining', $this->fieldLabel('SpacesRemaining'))
                        ->setValue($this->SpacesRemaining),
                    'Spaces'
                );

                // Switch custom relation to a hasone field
                $fields->replaceField(
                    'CustomerID',
                    HasOneButtonField::create($this, 'Customer')
                ); 

                // Hide Item Field (not needed)
                $fields->replaceField(
                    'ItemID',
                    HiddenField::create('ItemID')
                );

                // Add descriptions to start/end fields
                $message = _t(__CLASS__ . '.StartEndDesctiption', 'Do not set if selecting an "Event Date"');

                $fields
                    ->dataFieldByName('Start')
                    ->setDescription($message);

                $fields
                    ->dataFieldByName('End')
                    ->setDescription($message);
            }
        );
        
        return parent::getCMSFields();
    }

    /**
     * Load custom search context to allow for more complex date based filtering
     * 
     * @return BookingSearchContext
     */
    public function getDefaultSearchContext()
    {
        return BookingSearchContext::create(
            static::class,
            $this->scaffoldSearchFields(),
            $this->defaultSearchFilters()
        );
    }

    /**
     * Sanitise a model class' name for inclusion in a link
     *
     * @param string $class
     * @return string
     */
    protected function sanitiseClassName($class)
    {
        return str_replace('\\', '-', $class);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function providePermissions()
    {
        return [
            "BOOKING_VIEW_BOOKINGS" => [
                'name' => 'View any booking',
                'help' => 'Allow user to view any booking',
                'category' => 'Bookings',
                'sort' => 99
            ],
            "BOOKING_CREATE_BOOKINGS" => [
                'name' => 'Create a booking',
                'help' => 'Allow user to create a booking',
                'category' => 'Bookings',
                'sort' => 98
            ],
            "BOOKING_EDIT_BOOKINGS" => [
                'name' => 'Edit any booking',
                'help' => 'Allow user to edit any booking',
                'category' => 'Bookings',
                'sort' => 97
            ],
            "BOOKING_DELETE_BOOKINGS" => [
                'name' => 'Delete any booking',
                'help' => 'Allow user to delete any booking',
                'category' => 'Bookings',
                'sort' => 96
            ]
        ];
    }

    /**
     * Return a member object, based on eith the passed param or
     * getting the currently logged in Member.
     *
     * @param Member $member Either a Member object or an Int
     *
     * @return Member | Null
     */
    protected function getMember($member = null)
    {
        if ($member && $member instanceof Member) {
            return $member;
        } elseif (is_numeric($member)) {
            return Member::get()->byID($member);
        } else {
            return Security::getCurrentUser();
        }
    }

    /**
     * Only users with VIEW admin rights can view
     *
     * @return Boolean
     */
    public function canView($member = null)
    {
        $extended = $this->extend('canView', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        $member = $this->getMember($member);

        if ($member && Permission::checkMember($member->ID, ["ADMIN", "BOOKING_VIEW_BOOKINGS"])) {
            return true;
        }

        return false;
    }

    /**
     * Only users with create admin rights can create
     *
     * @return Boolean
     */
    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extend('canCreate', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        $member = $this->getMember($member);

        if ($member && Permission::checkMember($member->ID, ["ADMIN", "BOOKING_CREATE_BOOKINGS"])) {
            return true;
        }

        return false;
    }

    /**
     * Only users with EDIT admin rights can view an order
     *
     * @return Boolean
     */
    public function canEdit($member = null)
    {
        $extended = $this->extend('canEdit', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        $member = $this->getMember($member);

        if ($member && Permission::checkMember($member->ID, ["ADMIN", "BOOKING_EDIT_BOOKINGS"])) {
            return true;
        }

        return false;
    }

    /**
     * Only users with Delete Permissions can delete Bookings
     *
     * @return Boolean
     */
    public function canDelete($member = null)
    {
        $extended = $this->extend('canEdit', $member);
        if ($extended && $extended !== null) {
            return $extended;
        }

        $member = $this->getMember($member);

        if ($member && Permission::checkMember($member->ID, ["ADMIN", "BOOKING_DELETE_BOOKINGS"])) {
            return true;
        }

        return false;
    }

    /**
     * Ensure any special instructions are transfered to a linked booking
     *
     * @return null
     */
    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $invoice = $this->getInvoice();

        // If special instructions are on the estimate/invoic, but not on the booking, transfer them
        if (!empty($invoice->SpecialInstructions) && $this->SpecialInstructions != $invoice->SpecialInstructions) {
            $this->SpecialInstructions = $invoice->SpecialInstructions;
        }

        // If we have selected an event date, push the start and end to the booking
        $event = $this->EventDate();
        if ($event->exists()) {
            $this->Start = $event->Start;
            $this->End = $event->End; 
        }
    }

    /**
     * Each line item that is bookable needs a relevent booking
     *
     */
    protected function onAfterWrite()
    {
        parent::onAfterWrite();

        $line_item = $this->Item();
        $item_factory = LineItemFactory::create();

        // Create and attach a line item
        if (!$line_item->exists()) {
            $item_factory
                ->setProduct($this->getBaseProduct())
                ->setQuantity($this->Spaces)
                ->makeItem()
                ->write();
            $line_item = $item_factory->getItem();
            $this->ItemID = $line_item->ID;
            $this->write();
        } else {
            $item_factory->setItem($line_item);
        }

        // ensure details are copied to line item (if needed)
        if ($line_item->exists() && ($line_item->StockID != $this->StockID || $line_item->Quantity != $this->Spaces)) {
            $line_item->StockID = $this->StockID;
            $line_item->Quantity = $this->Spaces;
            $line_item->write();
        }

        // If an estimate does does not exist (linked to the item), create
        if ($line_item->exists() && !$line_item->Parent()->exists()) {
            OrderFactory::create()
                ->setCustomer($this->Customer())
                ->addFromLineItemFactory($item_factory)
                ->write();
        }

        // Finally, if there is a customer set on the booking, but not on the Estimate/Invoice
        // set it now.
        $invoice = $line_item->Parent();

        // If special instructions are on the estimate/invoic, but not on the booking, transfer them
        if ($invoice->exists() && $this->Customer()->exists() && !$invoice->Customer()->exists()) {
            $invoice->CustomerID = $this->CustomerID;
            $invoice->write();
        }

        // If we have marked a booking as confirmeed, convert the attached estimate to an invoice
        if ($this->isChanged('Status') && $this->isConfirmed()) {
            $invoice = $this->getInvoice();
            if (is_a($invoice, Estimate::class)) {
                $invoice->convertToInvoice();
            }
        }
    }
}
