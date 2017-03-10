<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Ownership\FrontendCustomerUserAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField; // required by DatesAwareTrait
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * @ORM\Table(name="oro_checkout")
 * @ORM\Entity(repositoryClass="Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository")
 * @ORM\HasLifecycleCallbacks()
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-shopping-cart"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id",
 *              "frontend_owner_type"="FRONTEND_USER",
 *              "frontend_owner_field_name"="customerUser",
 *              "frontend_owner_column_name"="customer_user_id",
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce",
 *              "category"="checkout"
 *          }
 *      }
 * )
 */
class Checkout implements
    CheckoutInterface,
    OrganizationAwareInterface,
    CustomerOwnerAwareInterface,
    DatesAwareInterface,
    ShippingAwareInterface,
    LineItemsNotPricedAwareInterface,
    PaymentMethodAwareInterface
{
    use DatesAwareTrait;
    use UserAwareTrait;
    use FrontendCustomerUserAwareTrait;
    use CheckoutAddressesTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="po_number", type="string", length=255, nullable=true)
     */
    protected $poNumber;

    /**
     * @var Website
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $website;

    /**
     * @var string
     *
     * @ORM\Column(name="shipping_method", type="string", nullable=true)
     */
    protected $shippingMethod;

    /**
     * @var string
     *
     * @ORM\Column(name="shipping_method_type", type="string", nullable=true)
     */
    protected $shippingMethodType;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_method", type="string", nullable=true)
     */
    protected $paymentMethod;

    /**
     * @var float
     *
     * @ORM\Column(name="shipping_estimate_amount", type="money", nullable=true)
     */
    protected $shippingEstimateAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="shipping_estimate_currency", type="string", nullable=true, length=3)
     */
    protected $shippingEstimateCurrency;

    /**
     * @var Price
     */
    protected $shippingCost;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ship_until", type="date", nullable=true)
     */
    protected $shipUntil;

    /**
     * @var string
     *
     * @ORM\Column(name="customer_notes", type="text", nullable=true)
     */
    protected $customerNotes;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    protected $currency;

    /**
     * @var CheckoutSource
     *
     * @ORM\OneToOne(targetEntity="Oro\Bundle\CheckoutBundle\Entity\CheckoutSource", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="source_id", referencedColumnName="id", nullable=false)
     */
    protected $source;

    /**
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean", options={"default"=false})
     */
    protected $deleted = false;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCustomerNotes()
    {
        return $this->customerNotes;
    }

    /**
     * @param string $customerNotes
     * @return Checkout
     */
    public function setCustomerNotes($customerNotes)
    {
        $this->customerNotes = $customerNotes;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     * @return Checkout
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * @return string
     */
    public function getPoNumber()
    {
        return $this->poNumber;
    }

    /**
     * @param string $poNumber
     * @return Checkout
     */
    public function setPoNumber($poNumber)
    {
        $this->poNumber = $poNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getShippingMethod()
    {
        return $this->shippingMethod;
    }

    /**
     * @param mixed $shippingMethod
     * @return Checkout
     */
    public function setShippingMethod($shippingMethod)
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    /**
     * @param string $shippingMethodType
     * @return $this
     */
    public function setShippingMethodType($shippingMethodType)
    {
        $this->shippingMethodType = $shippingMethodType;

        return $this;
    }

    /**
     * @return string
     */
    public function getShippingMethodType()
    {
        return $this->shippingMethodType;
    }

    /**
     * @return \DateTime
     */
    public function getShipUntil()
    {
        return $this->shipUntil;
    }

    /**
     * @param \DateTime $shipUntil
     * @return Checkout
     */
    public function setShipUntil(\DateTime $shipUntil = null)
    {
        $this->shipUntil = $shipUntil;

        return $this;
    }

    /**
     * @return Website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param Website $website
     * @return Checkout
     */
    public function setWebsite(Website $website = null)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return CheckoutSourceEntityInterface|null
     */
    public function getSourceEntity()
    {
        if ($this->source) {
            return $this->source->getEntity();
        }

        return null;
    }

    /**
     * @return CheckoutSource
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param CheckoutSource $source
     * @return Checkout
     */
    public function setSource(CheckoutSource $source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get shipping estimate
     *
     * @return Price|null
     */
    public function getShippingCost()
    {
        return $this->shippingCost;
    }

    /**
     * Set shipping estimate
     *
     * @param Price $shippingCost
     * @return $this
     */
    public function setShippingCost(Price $shippingCost = null)
    {
        $this->shippingCost = $shippingCost;

        $this->updateShippingEstimate();

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return Checkout
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @param bool $deleted
     *
     * @return $this
     */
    public function setDeleted($deleted)
    {
        $this->deleted = (bool)$deleted;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @ORM\PostLoad
     */
    public function postLoad()
    {
        $this->shippingCost = Price::create($this->shippingEstimateAmount, $this->shippingEstimateCurrency);
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateShippingEstimate()
    {
        $this->shippingEstimateAmount = $this->shippingCost ? $this->shippingCost->getValue() : null;
        $this->shippingEstimateCurrency = $this->shippingCost ? $this->shippingCost->getCurrency() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        /** @var LineItemsNotPricedAwareInterface|LineItemsAwareInterface $sourceEntity */
        $sourceEntity = $this->getSourceEntity();
        return $sourceEntity && ($sourceEntity instanceof LineItemsNotPricedAwareInterface
            || $sourceEntity instanceof LineItemsAwareInterface) ? $sourceEntity->getLineItems() : [];
    }
}
