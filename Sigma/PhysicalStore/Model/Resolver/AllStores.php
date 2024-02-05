<?php
/**
 * Resolver for retrieving all store locations.
 */

namespace Sigma\PhysicalStore\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Psr\Log\LoggerInterface;
use Ideo\StoreLocator\Model\StoreFactory;
use Ideo\StoreLocator\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;

class AllStores implements ResolverInterface
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
     * AllStores constructor.
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
     * Resolve function for retrieving all store locations.
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
        $enabled = isset($args['enabled']) ? $args['enabled'] : null;
        $stores = $this->getStoreLocatorData($enabled);

        if (empty($stores)) {
            return [];
        }

        return $stores;
    }

    /**
     * Get store locator data based on enabled/disabled status.
     *
     * @param bool|null $enabled
     * @return array
     */
    private function getStoreLocatorData($enabled = null)
    {
        $storeCollection = $this->storeCollectionFactory->create();
        $stores = [];

        foreach ($storeCollection as $store) {
            if ($enabled === null || $store->isActive() == $enabled) {
                $status = $store->isActive() ? 'Enabled' : 'Disabled';

                $stores[] = [
                    'storeName' => $store->getName(),
                    'address' => $store->getAddress() . ', ' . $store->getCity() . ', ' . $store->getCountry(),
                    'status' => $status
                ];
            }
        }

        return $stores;
    }
}
