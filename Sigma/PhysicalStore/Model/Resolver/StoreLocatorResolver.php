<?php
/**
 * Resolver for retrieving store locations based on latitude, longitude, and radius.
 */

namespace Sigma\PhysicalStore\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Psr\Log\LoggerInterface;
use Ideo\StoreLocator\Model\StoreFactory;
use Ideo\StoreLocator\Model\CategoryFactory;

class StoreLocatorResolver implements ResolverInterface
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
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * StoreLocatorResolver constructor.
     *
     * @param ValueFactory $valueFactory
     * @param LoggerInterface $logger
     * @param StoreFactory $storeFactory
     * @param CategoryFactory $categoryFactory
     */
    public function __construct(
        ValueFactory $valueFactory,
        LoggerInterface $logger,
        StoreFactory $storeFactory,
        CategoryFactory $categoryFactory
    ) {
        $this->valueFactory = $valueFactory;
        $this->logger = $logger;
        $this->storeFactory = $storeFactory;
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * Resolve function for retrieving store locations based on latitude, longitude, and radius.
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
        $latitude = $args['latitude'];
        $longitude = $args['longitude'];
        $radius = $args['radius'];

        $this->logger->info('Latitude: ' . $latitude);
        $this->logger->info('Longitude: ' . $longitude);
        $this->logger->info('Radius: ' . $radius);

        $stores = $this->getStoreLocatorData($latitude, $longitude, $radius);

        if (empty($stores)) {
            return [];
        }

        return $stores;
    }

    /**
     * Get store locations based on latitude, longitude, and radius.
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $radius
     * @return array
     */
    private function getStoreLocatorData($latitude, $longitude, $radius)
    {
        $storeCollection = $this->storeFactory->create()->getCollection();
        $categoryModel = $this->categoryFactory->create();

        $stores = [];
        foreach ($storeCollection as $store) {
            $categoryId = $store->getCategoryId();
            $category = $categoryModel->load($categoryId);
            if (!$category || !$category->isActive()) {
                continue;
            }

            $distance = $this->calculateDistance($latitude, $longitude, $store->getLat(), $store->getLng());

            if ($distance <= $radius) {
                $stores[] = [
                    'storeName' => $store->getName(),
                    'address' => $store->getAddress() . ', ' . $store->getCity() . ', ' . $store->getCountry(),
                    'distance' => $distance
                ];
            }
        }

        return $stores;
    }

    /**
     * Calculate distance between two points on the Earth's surface.
     *
     * @param float $latitude1
     * @param float $longitude1
     * @param float $latitude2
     * @param float $longitude2
     * @return float
     */
    public function calculateDistance($latitude1, $longitude1, $latitude2, $longitude2)
    {
        $latFrom = deg2rad($latitude1);
        $lonFrom = deg2rad($longitude1);
        $latTo = deg2rad($latitude2);
        $lonTo = deg2rad($longitude2);

        $earthRadius = 6371;

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        $distance = $angle * $earthRadius;

        return $distance;
    }
}
