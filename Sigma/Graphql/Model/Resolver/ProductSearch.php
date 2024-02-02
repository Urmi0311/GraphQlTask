<?php

namespace Sigma\Graphql\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class ProductSearch implements ResolverInterface
{
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option
     */
    private $eavOption;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavAttribute;

    public function __construct(CollectionFactory $productCollectionFactory, \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option $eavOption, \Magento\Eav\Model\Config $eavAttribute)
    {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->eavOption = $eavOption;
        $this->eavAttribute = $eavAttribute;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/urmi.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $query = $args['query'] ?? '';
        $filters = $args['filters'] ?? [];
        $brands = $filters['brands'] ?? '';
        $logger->info($brands);
        $minPrice = $filters['price']['min'] ?? null;
        $maxPrice = $filters['price']['max'] ?? null;
        $sortDirection = $args['sort']['direction'] ?? null; // Get sort direction

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');

        // Apply name filter
        if (!empty($query)) {
            $collection->addAttributeToFilter('name', ['like' => '%' . $query . '%']);
        }

        if ($brands !== null) {
            $collection->addAttributeToFilter('brands', ['eq' => $brands]);
        }

        if ($minPrice !== null) {
            $collection->addFieldToFilter('price', ['gteq' => $minPrice]);
        }

        if ($maxPrice !== null) {
            $collection->addFieldToFilter('price', ['lteq' => $maxPrice]);
        }

        if ($sortDirection === 'DESC') {
            $collection->setOrder('price', 'DESC');
        } else {
            $collection->setOrder('price', 'ASC');
        }

        $products = [];
        foreach ($collection as $product) {
            $products[] = [
                'name' => $product->getName(),
                'price' => $product->getPrice(),
            ];
        }

        return $products;
    }
}
