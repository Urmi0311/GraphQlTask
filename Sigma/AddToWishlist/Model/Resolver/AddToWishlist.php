<?php

namespace Sigma\AddToWishlist\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Customer\Model\Session;

class AddToWishlist implements ResolverInterface
{
    /**
     * @var WishlistFactory
     */
    protected $wishlistFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @param WishlistFactory $wishlistFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Session $customerSession
     */
    public function __construct(
        WishlistFactory $wishlistFactory,
        ProductRepositoryInterface $productRepository,
        Session $customerSession
    ) {
        $this->wishlistFactory = $wishlistFactory;
        $this->productRepository = $productRepository;
        $this->customerSession = $customerSession;
    }

    /**
     * Resolve method to add a product to the wishlist.
     *
     * @param Field $field
     * @param mixed $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws \Exception
     */

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $productId = $args['productId'];
        $customerId = $this->customerSession->getCustomerId();

        if (!$customerId) {
            throw new \Exception("Customer not authenticated.");
        }

        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId, true);
        if (!$wishlist) {
            throw new \Exception("Wishlist not found.");
        }

        $product = $this->productRepository->getById($productId);

        try {
            $wishlist->addNewItem($product);
            $wishlist->save();
            $wishlistItemsData = $this->getWishlistItemsData($wishlist);
            return [
                'success' => true,
                'message' => "Successfully added to wishlist",
                'wishlists' => [
                    'item' => $wishlistItemsData
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get wishlist items data.
     *
     * @param mixed $wishlist
     * @return array
     */

    protected function getWishlistItemsData($wishlist)
    {
        $wishlistItems = $wishlist->getItemCollection();
        $wishlistItemsData = [];
        foreach ($wishlistItems as $wishlistItem) {
            $wishlistItemsData[] = [
                'name' => $wishlistItem->getProduct()->getName(),
                'price' => $wishlistItem->getProduct()->getPrice()
            ];
        }
        return $wishlistItemsData;
    }
}
