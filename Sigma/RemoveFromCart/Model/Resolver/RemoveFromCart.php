<?php
/**
 * This file contains the RemoveFromCart GraphQL resolver.
 */

namespace Sigma\RemoveFromCart\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\QuoteIdMaskFactory;

/**
 * Resolver for removing products from the cart via GraphQL mutation.
 */
class RemoveFromCart implements ResolverInterface
{
    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * RemoveFromCart constructor.
     *
     * @param CartRepositoryInterface $cartRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->cartRepository = $cartRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * Remove product from the cart.
     *
     * @param Field $field
     * @param array|null $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws \Exception
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        // Extract input parameters
        $cartId = $args['input']['cart_id'];
        $itemId = $args['input']['item_id'];

        // Load the cart and remove the item
        $cart = $this->loadCart($cartId);
        $this->removeItemFromCart($cart, $itemId);

        return ['success' => true];
    }

    /**
     * Load cart by cart ID.
     *
     * @param string $cartId
     * @return \Magento\Quote\Model\Quote
     * @throws \Exception
     */
    protected function loadCart($cartId)
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $cartId = $quoteIdMask->getQuoteId();
        return $this->cartRepository->get($cartId);
    }

    /**
     * Remove item from cart.
     *
     * @param \Magento\Quote\Model\Quote $cart
     * @param int $itemId
     * @throws \Exception
     */
    protected function removeItemFromCart($cart, $itemId)
    {
        $item = $this->findCartItemById($cart, $itemId);
        if (!$item) {
            throw new \Exception('Item not found in the cart.');
        }

        $cart->removeItem($item->getId());
        $this->cartRepository->save($cart);
    }

    /**
     * Find a cart item by ID.
     *
     * @param \Magento\Quote\Model\Quote $cart
     * @param int $itemId
     * @return \Magento\Quote\Model\Quote\Item|null
     */
    protected function findCartItemById($cart, $itemId)
    {
        foreach ($cart->getAllVisibleItems() as $item) {
            if ($item->getId() == $itemId) {
                return $item;
            }
        }
        return null;
    }
}
