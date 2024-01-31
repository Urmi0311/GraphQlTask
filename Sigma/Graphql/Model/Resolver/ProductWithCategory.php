<?php
namespace Sigma\Graphql\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface; // Import the StoreManagerInterface
use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;
use Magento\Directory\Model\CurrencyFactory;

/**
 * Resolver for fetching product details with category.
 */
class ProductWithCategory implements ResolverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var CollectionFactory
     */
    protected $categoryCollection;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DateTimeFormatterInterface
     */
    protected $dateTimeFormatter;

    /**
     * @var CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * ProductWithCategory constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     * @param Product $product
     * @param CollectionFactory $categoryCollection
     * @param StoreManagerInterface $storeManager
     * @param DateTimeFormatterInterface $dateTimeFormatter
     * @param CurrencyFactory $currencyFactory
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Product $product,
        CollectionFactory $categoryCollection,
        StoreManagerInterface $storeManager,
        DateTimeFormatterInterface $dateTimeFormatter,
        CurrencyFactory $currencyFactory
    ) {
        $this->productRepository = $productRepository;
        $this->product = $product;
        $this->categoryCollection = $categoryCollection;
        $this->storeManager = $storeManager;
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->currencyFactory = $currencyFactory;
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
        $sku = $args['sku'];

        try {
            $product = $this->productRepository->get($sku);
            $categoryNames = $this->getCategoriesName($product->getId());
            $materials = $product->getAttributeText('materials');
            $relativeImagePath = $product->getData('images');
            $imageUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $relativeImagePath;
            $date = $this->formatDate($product->getData('special_price_data'));
            $price = $this->formatPrice($product->getPrice());

            return [
                'sku' => $sku,
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $price,
                'category' => $categoryNames,
                'material' => $materials,
                'images' => $imageUrl,
                'date' => $date
            ];
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            throw new \Exception(__('Product with SKU %1 does not exist.', $sku));
        } catch (\Exception $e) {
            throw new \Exception(__('Failed to fetch product details: %1', $e->getMessage()));
        }
    }

    /**
     * Format date.
     *
     * @param string $date
     * @return string
     */
    public function formatDate($date)
    {
        $dateTime = new \DateTime($date);
        return $this->dateTimeFormatter->formatObject($dateTime, \IntlDateFormatter::LONG);
    }

    /**
     * Format price with currency.
     *
     * @param float $price
     * @return string
     */
    public function formatPrice($price)
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        $currencySymbol = $this->currencyFactory->create()->load($currencyCode)->getCurrencySymbol();
        return $currencySymbol . number_format((float)$price, 2);
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
