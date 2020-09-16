<?php

namespace ilateral\SimpleBookings\Products;

use Exception;
use ilateral\SimpleBookings\Forms\DateOptionsField;
use LogicException;
use ProductController;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\ORM\ValidationException;
use ilateral\SimpleBookings\Products\EventProduct;
use SilverCommerce\ShoppingCart\Forms\AddToCartForm;
use SilverCommerce\ShoppingCart\ShoppingCartFactory;
use SilverCommerce\OrdersAdmin\Factory\LineItemFactory;

class EventProductController extends ProductController
{
    private static $allowed_actions = [
        'AddToCartForm'
    ];

    /**
     * Customised AddToCartForm that allows selecting the date of the event
     * as a radio set
     *
     * @return AddToCartForm
     */
    public function AddToCartForm()
    {
        $form = AddToCartForm::create(
            $this,
            "AddToCartForm"
        );
        /** @var EventProduct */
        $object = $this->dataRecord;

        $form
            ->setProductClass(EventProduct::class)
            ->setProductID($object->ID);
        
        $fields = $form->Fields();
        $actions = $form->Actions();

        $fields->insertBefore(
            'Quantity',
            DateOptionsField::create(
                'Date',
                _t(__CLASS__ . '.SelectDate', 'Select a date')
            )->setSource($object->getCurrentDates()->map())
            ->setDisabledItems($this->getDisabledDateIDs())
            ->setForm($form)
        );

        $fields->insertBefore(
            'Quantity',
            HeaderField::create(
                'QuantityHeader',
                _t(__CLASS__ . '.HowManyPeople', 'How Many People are Attending?'),
                6
            )
        );

        /** @var \SilverStripe\Forms\FormAction */
        $submit_action = $actions->fieldByName('action_doAddItemToCart');

        if (!empty($submit_action)) {
            $submit_action->setTitle(_t('Bookings.BookNow', 'Book Now'));
        }

        /** @var \SilverStripe\Forms\RequiredFields */
        $validator = $form->getValidator();
        $validator->addRequiredField('Date');

        $this->extend('updateAddToCartForm', $form);

        return $form;
    }

    public function doAddItemToCart($data, $form)
    {
        $classname = $data["ClassName"];
        $id = $data["ID"];
        /** @var EventProduct */
        $object = $classname::get()->byID($id);
        $cart = ShoppingCartFactory::create();
        $customisations = [];
        $date = $object->getCurrentDates()->byID($data['Date']);

        if (!empty($object) && !empty($date)) {
            // Manually create a line item and add to cart, return any exceptions raised as a message
            try {
                $customisations[] = [
                    "Title" => _t("Bookings.Date", 'Date'),
                    "Value" => $date->Title,
                    "BasePrice" => 0
                ];

                // Generate a line item and lock it (so booking details cannot be changed)
                $factory = LineItemFactory::create()
                    ->setProductDeliverableParam('DeliverTicket')
                    ->setProduct($object)
                    ->setQuantity($data['Quantity'])
                    ->setCustomisations($customisations)
                    ->makeItem()
                    ->write();

                // Now get and update the assotiated booking data
                $item = $factory->getItem();

                /** @var \ilateral\SimpleBookings\Model\Booking */
                $booking = $item->Booking();
                $booking->Start = $date->Start;
                $booking->End = $date->End;
                $booking->write();

                // Perform initial check of quantities before adding
                // So we can cleanup anything invalid
                if (!$factory->checkStockLevel()) {
                    $item->delete();
                    throw new ValidationException(
                        _t(
                            "Bookings.NotEnoughSpaces",
                            "Not enough spaces available for '{title}'",
                            ['title' => $factory->getItem()->Title]
                        )
                    );
                }

                $cart->addFromLineItemFactory($factory);
                $cart->save();

                $message = _t(
                    'Bookings.AddedItemToCart',
                    'Added booking "{item}" to your basket',
                    ["item" => $object->Title]
                );

                $form->sessionMessage(
                    $message,
                    ValidationResult::TYPE_GOOD
                );
            } catch (Exception $e) {
                $form->sessionMessage(
                    $e->getMessage()
                );
            }
        } else {
            $form->sessionMessage(
                _t("Bookings.ErrorWithBooking", "Error With Booking")
            );
        }

        return $this->redirectBack();
    }
}
