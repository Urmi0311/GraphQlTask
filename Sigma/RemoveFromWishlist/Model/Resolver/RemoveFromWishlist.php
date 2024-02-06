<?php

namespace Sigma\RemoveFromWishlist\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Wishlist\Model\WishlistFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\GraphQl\Config\Element\Field;

/**
 * Resolver class for removing items from wishlist
 */

class RemoveFromWishlist implements ResolverInterface
{

    /**
     * @var WishlistFactory
     */

    protected $wishlistFactory;

    /**
     * @var LoggerInterface
     */

    protected $logger;

    /**
     * Constructor
     *
     * @param WishlistFactory $wishlistFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        WishlistFactory $wishlistFactory,
        LoggerInterface $logger
    ) {
        $this->wishlistFactory = $wishlistFactory;
        $this->logger = $logger;
    }
    
    /**
     * Resolve method for removing items from wishlist
     *
     * @param Field $field
     * @param mixed $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $productId = $args['productId'];
        $customerId = $context->getUserId();

        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId, true);

        try {
            $wishlistItem = $this->findWishlistItem($wishlist, $productId);

            if ($wishlistItem) {
                $wishlistItem->delete();
                $this->logger->info('Product removed from wishlist.', ['productId' => $productId]);
            } else {
                $this->logger->error('Product not found in wishlist.', ['productId' => $productId]);
                throw new \Exception('Product not found in wishlist.');
            }

            $wishlistItemsData = $this->getWishlistItemsData($wishlist);

            return [
                'success' => true,
                'message' => "Successfully removed item from wishlist",

            ];
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Find wishlist item by product ID
     *
     * @param mixed $wishlist
     * @param int $productId
     * @return mixed|null
     */

    protected function findWishlistItem($wishlist, $productId)
    {
        $wishlistItems = $wishlist->getItemCollection();
        foreach ($wishlistItems as $wishlistItem) {
            if ($wishlistItem->getProductId() == $productId) {
                return $wishlistItem;
            }
        }
        return null;
    }

    /**
     * Get wishlist items data
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
