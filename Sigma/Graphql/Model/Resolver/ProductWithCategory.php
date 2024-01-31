<?php
namespace Sigma\Graphql\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

class ProductWithCategory implements ResolverInterface
{
    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var Product */
    protected $product;

    /** @var CollectionFactory */
    protected $categoryCollection;

    /**
     * Constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     * @param Product $product
     * @param CollectionFactory $categoryCollection
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Product $product,
        CollectionFactory $categoryCollection
    ) {
        $this->productRepository = $productRepository;
        $this->product = $product;
        $this->categoryCollection = $categoryCollection;
    }

    /**
     * Resolve product details with categories.
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
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $sku = $args['sku'];

        try {
            $product = $this->productRepository->get($sku);
            $categoryNames = $this->getCategoriesName($product->getId());
            return [
                'sku' => $sku,
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'category' => $categoryNames
            ];
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            throw new \Exception(__('Product with SKU %1 does not exist.', $sku));
        } catch (\Exception $e) {
            throw new \Exception(__('Failed to fetch product details: %1', $e->getMessage()));
        }
    }

    /**
     * Get category names for a product.
     *
     * @param int $productId
     * @return array
     */

    public function getCategoriesName($productId)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("hello");
        $product = $this->product->load($productId);
        $categoryIds = $product->getCategoryIds();
        $categories = $this->categoryCollection->create()->addAttributeToSelect('*')->addAttributeToFilter('entity_id', $categoryIds);
        $categoryNames = [];
        foreach ($categories as $category) {
            $categoryNames[] = [

                'id' => $category->getId(),
                'name' => $category->getName()
            ];
        }
        return $categoryNames;
    }
}
