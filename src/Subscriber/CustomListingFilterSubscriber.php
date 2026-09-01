<?php

namespace CtCustomListingFilters\Subscriber;

use Acris\CustomerProductGroup\Custom\Events\CustomerProductGroupResultEvent;
use Acris\Filter\Custom\FilterDefinition;
use Acris\Filter\Custom\FilterEntity;
use Acris\ProductFinder\Custom\Events\ProductFinderResultEvent;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Category\Tree\Tree;
use Shopware\Core\Content\Category\Tree\TreeItem;
use Shopware\Core\Content\Product\Events\ProductListingCollectFilterEvent;
use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductListingResultEvent;
use Shopware\Core\Content\Product\Events\ProductSearchResultEvent;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\PartialEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\EntityResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CustomListingFilterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityRepository $categoryRepository
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCollectFilterEvent::class => 'addFilter'
        ];
    }

    public function addFilter(ProductListingCollectFilterEvent $event): void
    {
        $filters = $event->getFilters();
        $request = $event->getRequest();

        $ids = $this->getDeliveryTimeIds($request);

        $deliveryTimeFilter = new Filter(
            'deliveryTime',
            !empty($ids),
            [new EntityAggregation('deliveryTime', 'product.deliveryTimeId', 'delivery_time')],
            new EqualsAnyFilter('product.deliveryTimeId', $ids),
            $ids
        );

        $filters->add($deliveryTimeFilter);

        $ids = $this->getCategoryIds($request);

        $filter = new Filter(
            'categories',
            !empty($ids),
            [new EntityAggregation('categories', 'product.categoriesRo.id', 'category')],
            $this->buildCategory($ids, $event->getSalesChannelContext()->getContext()),
            $ids
        );
        $filters->add($filter);
    }


    /**
     * Keeps only valid uuids, so an invalid id from the url cannot reach the DAL,
     * where it would throw an InvalidUuidException and break the whole listing page.
     *
     * @param string[] $ids
     * @return string[]
     */
    private function filterValidUuids(array $ids): array
    {
        $valid = [];
        foreach ($ids as $id) {
            if (is_string($id) && Uuid::isValid($id)) {
                $valid[] = $id;
            }
        }

        return array_values(array_unique($valid));
    }

    /**
     * Accepts only numeric values under a valid uuid key, limited in amount.
     *
     * @param array<string|int, mixed> $values
     * @return array<string, float>
     */

    private function buildCategory(array $selectedCategoryIds, Context $context): \Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\Filter
    {
        $requestedCategoryIds = $selectedCategoryIds;
        $selectedCategoryIds = $this->filterValidUuids($selectedCategoryIds);

        // ids were requested but none of them is a valid uuid, so nothing can match
        if ($selectedCategoryIds === [] && $requestedCategoryIds !== []) {
            return new EqualsAnyFilter('product.id', [Uuid::randomHex()]);
        }

        if ($selectedCategoryIds === []) {
            return new EqualsFilter('product.active', true);
        }

        $directCategoryIds = [];

        $criteria = new Criteria($selectedCategoryIds);
        $criteria->addFields(['id']);

        $categories = $this->categoryRepository->search($criteria, $context)->getEntities();
        foreach ($categories as $category) {
            if (!$category instanceof PartialEntity) {
                continue;
            }

            $directCategoryIds[] = $category->getId();
        }

        $queries = [];
        if ($directCategoryIds !== []) {
            $queries[] = new EqualsAnyFilter('product.categoriesRo.id', array_values(array_unique($directCategoryIds)));
        }

        // If no queries are generated, return a filter that excludes all products
        if ($queries === []) {
            return new EqualsAnyFilter('product.id', [Uuid::randomHex()]);
        }

        return new MultiFilter(MultiFilter::CONNECTION_OR, $queries);
    }


    private function buildCategoryTree(ProductListingResult $productListingResult, SalesChannelContext $context, ?FilterEntity $categoryFilter = null): void
    {
        $categoryAggregation = $productListingResult->getAggregations()->get('categories');

        if (!$categoryAggregation instanceof EntityResult || $categoryAggregation->getEntities() === null || $categoryAggregation->getEntities()->count() === 0) {
            return;
        }

        $categoryCollection = $categoryAggregation->getEntities();
        if (!$categoryCollection instanceof CategoryCollection) {
            return;
        }

        if ($categoryFilter && $categoryFilter->getLimitCategoryFilterScope() === FilterDefinition::LIMIT_CATEGORY_FILTER_SCOPE_CURRENT_TREE) {
            $request = $this->requestStack->getCurrentRequest();
            $navigationId = $request?->attributes->get('navigationId');

            if ($navigationId) {
                $criteria = new Criteria([$navigationId]);
                $criteria->addFields(['id', 'path']);

                /** @var CategoryEntity|null $currentCategory */
                $currentCategory = $this->categoryRepository->search($criteria, $context->getContext())->first();

                if ($currentCategory) {
                    $categoryCollection = $categoryCollection->filter(function (CategoryEntity $category) use ($currentCategory) {

                        error_log(print_r(array(date('h:i:s'), 'world'), true)."\n", 3, '/var/www/html' . '/error.log');

                        return $category->getId() === $currentCategory->getId() ||
                            str_contains((string)$category->get('path'), (string)$currentCategory->getId()) ||
                            str_contains((string)$currentCategory->get('path'), (string)$category->getId());
                    });

                    $levels = [];
                    foreach ($categoryCollection as $category) {
                        $level = $category->getLevel();
                        $levels[$level] = ($levels[$level] ?? 0) + 1;
                    }

                    $categoryCollection = $categoryCollection->filter(function (CategoryEntity $category) use ($levels) {
                        return ($levels[$category->getLevel()] ?? 0) > 1;
                    });

                    $categoryAggregation->assign(['entities' => $categoryCollection]);
                }
            }
        }
    }

    private function getDeliveryTimeIds(Request $request): array
    {
        $ids = $request->query->get('deliveryTime', '');
        if ($request->isMethod(Request::METHOD_POST)) {
            $ids = $request->request->get('deliveryTime', '');
        }

        if (\is_string($ids)) {
            $ids = explode('|', $ids);
        }

        /** @var list<string> $ids */
        $ids = array_filter((array)$ids);

        return $ids;
    }

    private function getCategoryIds(Request $request): array
    {
        $ids = $request->query->get('categories', '');
        if ($request->isMethod(Request::METHOD_POST)) {
            $ids = $request->request->get('categories', '');
        }

        if (\is_string($ids)) {
            $ids = explode('|', $ids);
        }

        /** @var list<string> $ids */
        $ids = array_filter((array)$ids);

        return $ids;
    }

}
