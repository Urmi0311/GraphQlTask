<?php

namespace Sigma\PhysicalStore\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Psr\Log\LoggerInterface;
use Ideo\StoreLocator\Model\StoreFactory;
use Ideo\StoreLocator\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;

class AllCategories implements ResolverInterface
{
    /**
     * @var ValueFactory
     */
    private $valueFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreFactory
     */
    private $storeFactory;

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * AllCategories constructor.
     *
     * @param ValueFactory $valueFactory
     * @param LoggerInterface $logger
     * @param StoreFactory $storeFactory
     * @param StoreCollectionFactory $storeCollectionFactory
     */
    public function __construct(
        ValueFactory $valueFactory,
        LoggerInterface $logger,
        StoreFactory $storeFactory,
        StoreCollectionFactory $storeCollectionFactory
    ) {
        $this->valueFactory = $valueFactory;
        $this->logger = $logger;
        $this->storeFactory = $storeFactory;
        $this->storeCollectionFactory = $storeCollectionFactory;
    }

    /**
     * Resolve function for getting store locations by category.
     *
     * @param \Magento\Framework\GraphQl\Config\Element\Field $field
     * @param mixed $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     */
    public function resolve(
        \Magento\Framework\GraphQl\Config\Element\Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $categoryId = $args['categoryId'];

        $stores = $this->getStoreLocationsByCategory($categoryId);

        if (empty($stores)) {
            return [];
        }

        return $stores;
    }

    /**
     * Get store locations by category.
     *
     * @param int $categoryId
     * @return array
     */
    private function getStoreLocationsByCategory($categoryId)
    {
        $storeCollection = $this->storeCollectionFactory->create();
        $stores = [];

        foreach ($storeCollection as $store) {
            if ($store->getCategoryId() == $categoryId) {
                $stores[] = [
                    'storeName' => $store->getName(),
                    'address' => $store->getAddress() . ', ' . $store->getCity() . ', ' . $store->getCountry()
                ];
            }
        }

        return $stores;
    }
}
