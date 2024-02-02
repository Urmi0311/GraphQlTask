<?php

namespace Sigma\CustomerOrder\Model\Resolver;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\CurrencyFactory;

/**
 * Resolver for retrieving customer orders via GraphQL
 */
class CustomerOrder implements ResolverInterface
{
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * CustomerOrder constructor.
     * @param Session $customerSession
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param TimezoneInterface $timezone
     * @param StoreManagerInterface $storeManager
     * @param CurrencyFactory $currencyFactory
     */
    public function __construct(
        Session $customerSession,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        TimezoneInterface $timezone,
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory
    ) {
        $this->customerSession = $customerSession;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->timezone = $timezone;
        $this->storeManager = $storeManager;
        $this->currencyFactory = $currencyFactory;
    }

    /**
     * Resolves customer orders
     *
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws GraphQlAuthorizationException
     * @throws LocalizedException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->customerSession->isLoggedIn()) {
            throw new GraphQlAuthorizationException(
                __('The current customer is not authorized.')
            );
        }

        $customerId = $this->customerSession->getCustomerId();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id', $customerId)
            ->create();

        $orders = [];
        $customerOrders = $this->orderRepository->getList($searchCriteria)->getItems();
        foreach ($customerOrders as $order) {

            $totalAmount = $this->formatPriceWithCurrencySymbol($order->getGrandTotal());
            $shippingAmount = $this->formatPriceWithCurrencySymbol($this->getShippingInformation($order));

            $orders[] = [
                'order_number' => $order->getIncrementId(),
                'date' => $this->formatDate($order->getCreatedAt()),
                'products' => $this->getOrderProducts($order),
                'total_amount' => $totalAmount,
                'shipping_amount' => $shippingAmount
            ];
        }

        return $orders;
    }

    /**
     * Retrieves products of an order
     *
     * @param $order
     * @return array
     */
    private function getOrderProducts($order)
    {
        $products = [];

        foreach ($order->getAllVisibleItems() as $item) {

            $price = $this->formatPriceWithCurrencySymbol($item->getPrice()); // Pass the price as argument
            $taxAmount = $this->formatCurrency($item->getTaxAmount());
            $discountAmount = $this->formatCurrency($item->getDiscountAmount());
            $total = $this->formatCurrency($item->getRowTotal());

            $products[] = [
                'name' => $item->getName(),
                'quantity' => $item->getQtyOrdered(),
                'price' =>  $price,
                'tax_amount' => $taxAmount,
                'tax_percent' => $item->getTaxPercent(),
                'discount_amount' => $discountAmount,
                'total' => $total
            ];
        }
        return $products;
    }

    /**
     * Formats date
     *
     * @param $date
     * @return string
     */
    private function formatDate($date)
    {
        return $this->timezone->formatDateTime($date, \IntlDateFormatter::MEDIUM, true);
    }

    /**
     * Retrieves shipping information of an order
     *
     * @param $order
     * @return float
     */
    private function getShippingInformation($order)
    {
        $shippingAmount = (float) $order->getShippingAmount();
        return $shippingAmount;
    }

    /**
     * Formats price with currency symbol
     *
     * @param $price
     * @return string
     */
    private function formatPriceWithCurrencySymbol($price)
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        $currencySymbol = $this->currencyFactory->create()->load($currencyCode)->getCurrencySymbol();
        return $currencySymbol . number_format((float)$price, 2);
    }

    /**
     * Formats currency
     *
     * @param $amount
     * @return string
     */
    public function formatCurrency($amount)
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        $currencySymbol = $this->currencyFactory->create()->load($currencyCode)->getCurrencySymbol();
        return $currencySymbol . number_format((float)$amount, 2);
    }
}
