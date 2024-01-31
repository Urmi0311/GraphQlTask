<?php
namespace Sigma\Graphql\Model\Resolver;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class UpdateProductStock implements ResolverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        \Magento\InventoryApi\Api\StockRepositoryInterface $productStockRepository
    ) {
        $this->productRepository = $productRepository;
        $this->productStockRepository = $productStockRepository;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/urmi.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $sku = $args['sku'];
        $logger->info($sku);
        $quantity = $args['quantity'];
        $logger->info($quantity);

        try {
            $product = $this->productRepository->get($sku);
            $product->setStockData(['qty' => $quantity]);
            $this->productRepository->save($product);
            $updatedProduct = $this->productRepository->get($sku);

            return [
                'sku' => $sku
            ];
        } catch (\Exception $e) {
            throw new \Exception(__('Failed to update stock quantity: %1', $e->getMessage()));
        }
    }
}
