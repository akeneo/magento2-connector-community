<?php

declare(strict_types=1);

namespace Akeneo\Connector\Test\Unit\Job;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Executor\JobExecutor;
use Akeneo\Connector\Helper\Config;
use Akeneo\Connector\Helper\Import\Product as ProductImportHelper;
use Akeneo\Connector\Helper\Store as StoreHelper;
use Akeneo\Connector\Job\Product;
use Magento\Catalog\Model\Product as BaseProductModel;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store as Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProductTest extends TestCase
{
    public function testSetUrlRewriteKeepsBaseRewriteForProductLinkedToInactiveCategory(): void
    {
        $entityId = 480;
        $inactiveCategoryId = 957;
        $storeId = 1;

        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('joinInner')->willReturnSelf();
        $select->method('reset')->willReturnSelf();
        $select->method('columns')->willReturnSelf();

        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('tableColumnExists')->willReturn(true);
        $connection->method('fetchAll')->willReturnOnConsecutiveCalls(
            [['entity_id' => $entityId, 'url_key' => 'test', 'store_id' => $storeId, 'visibility' => 4]],
            [],
            [['product_id' => $entityId, 'category_id' => $inactiveCategoryId]],
            []
        );
        $connection->expects($this->once())
            ->method('insertOnDuplicate')
            ->with(
                $this->equalTo('url_rewrite'),
                $this->callback(
                    function (array $data) use ($entityId) {
                        return ($data['target_path'] ?? null) === 'catalog/product/view/id/' . $entityId;
                    }
                )
            );

        $job = $this->createStub(JobInterface::class);
        $job->method('getCode')->willReturn('product');

        $jobExecutor = $this->createStub(JobExecutor::class);
        $jobExecutor->method('getCurrentJob')->willReturn($job);

        $entitiesHelper = $this->createStub(ProductImportHelper::class);
        $entitiesHelper->method('getConnection')->willReturn($connection);
        $entitiesHelper->method('getTableName')->willReturn('tmp_product');
        $entitiesHelper->method('getTable')->willReturnArgument(0);
        $entitiesHelper->method('verifyProductUrl')->willReturnArgument(0);

        $configHelper = $this->createStub(Config::class);
        $configHelper->method('isUrlGenerationEnabled')->willReturn(true);
        $configHelper->method('isCategoryUsedInProductUrl')->willReturn(true);

        $storeHelper = $this->createStub(StoreHelper::class);
        $storeHelper->method('getStores')->willReturnCallback(
            function (array $keys) use ($storeId) {
                return $keys === ['lang'] ? ['en_US' => [['store_id' => $storeId]]] : [];
            }
        );

        $category = $this->createStub(CategoryCollection::class);
        $category->method('addAttributeToSelect')->willReturnSelf();
        $category->method('setStore')->willReturnSelf();
        $category->method('addFieldToFilter')->willReturnSelf();
        $category->method('getItems')->willReturn([]);

        $categoryCollectionFactory = $this->createStub(CollectionFactory::class);
        $categoryCollectionFactory->method('create')->willReturn($category);

        $store = $this->createStub(Store::class);
        $store->method('getRootCategoryId')->willReturn(2);

        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $product = $this->createStub(BaseProductModel::class);
        $product->method('getEntityId')->willReturn($entityId);
        $product->method('getStoreId')->willReturn($storeId);

        $productUrlPathGenerator = $this->createStub(ProductUrlPathGenerator::class);
        $productUrlPathGenerator->method('getUrlPath')->willReturn('test');
        $productUrlPathGenerator->method('getUrlPathWithSuffix')->willReturn('test.html');

        $objectManager = new ObjectManager($this);
        /** @var Product $productJob */
        $productJob = $objectManager->getObject(
            Product::class,
            [
                'configHelper' => $configHelper,
                'entitiesHelper' => $entitiesHelper,
                'storeHelper' => $storeHelper,
                'jobExecutor' => $jobExecutor,
                'categoryCollectionFactory' => $categoryCollectionFactory,
                'storeManager' => $storeManager,
                'product' => $product,
                'productUrlPathGenerator' => $productUrlPathGenerator,
            ]
        );

        $productJob->setUrlRewrite();
    }
}
