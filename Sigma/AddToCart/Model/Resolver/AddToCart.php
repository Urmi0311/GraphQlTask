<?php

/**
 * This file contains the AddToCart GraphQL resolver.
 */

namespace Sigma\AddToCart\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Resolver for adding products to the cart via GraphQL mutation.
 */
class AddToCart implements ResolverInterface
{
    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var CartItemInterfaceFactory
     */
    protected $cartItemFactory;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * AddToCart constructor.
     *
     * @param CartRepositoryInterface $cartRepository
     * @param CartItemInterfaceFactory $cartItemFactory
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        CartItemInterfaceFactory $cartItemFactory,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        ProductRepositoryInterface $productRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->cartItemFactory = $cartItemFactory;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->productRepository = $productRepository;
    }

    /**
     * Resolve GraphQL mutation to add product to cart.
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
        $cartId = $args['input']['cart_id'];
        $sku = $args['input']['sku'];
        $quantity = $args['input']['quantity'];

        try {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            $cartId = $quoteIdMask->getQuoteId();
            $cart = $this->cartRepository->get($cartId);

            $product = $this->productRepository->get($sku);

            $cartItem = $this->cartItemFactory->create();
            $cartItem->setProduct($product);
            $cartItem->setQty($quantity);
            $cartItem->setQuoteId($cart->getId());
            $cartItem->setStoreId($cart->getStoreId());
            $cartItem->setOptions([]);

            $cart->addItem($cartItem);

            $this->cartRepository->save($cart);

            $cartItems = [];
            foreach ($cart->getAllVisibleItems() as $item) {
                $cartItems[] = [
                    'id' => $item->getId(),
                    'product' => [
                        'name' => $item->getName(),
                        'sku' => $item->getSku()
                    ],
                    'quantity' => $item->getQty(),
                ];
            }

            return ['cart' => ['items' => $cartItems]];
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
