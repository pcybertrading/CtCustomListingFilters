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
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;


class CustomListingFilterSubscriber implements EventSubscriberInterface
{
    public const CUSTOM_FIELD_FILTER_NUMERIC_MIN = 'acris_filter_numeric';
    public const CUSTOM_FIELD_FILTER_NUMERIC_MAX = 'acris_filter_numeric_max';

    public const CUSTOM_FIELD_FILTER_TYPE = 'acris_filter_type';
    public const CUSTOM_FIELD_FILTER_TYPE_RANGE_MIN_MAX = 'range_min_max';
    public const CUSTOM_FIELD_FILTER_TYPE_RANGE_MIN_MAX_SILDER = 'range_min_max_slider';
    public const CUSTOM_FIELD_FILTER_TYPE_RANGE_SLIDER = 'range_slider';

    public const CUSTOM_FIELD_LOGIC_OPERATOR = 'acris_filter_logic_operator';
    public const LOGIC_OPERATOR_OR = 'or';
    public const LOGIC_OPERATOR_AND = 'and';

    private const CATEGORY_FILTER_AGGREGATION_TREE = 'acrisCategoryFilterAggregationTree';

    /**
     * Upper bound for values taken from the request, so a crafted url cannot blow up the criteria
     * or trigger one database query per submitted value.
     */
    private const MAX_REQUEST_FILTER_VALUES = 50;
    private TreeItem $treeItem;

    public function __construct(
        private readonly EntityRepository         $repository,
        private readonly Connection               $connection,
        private readonly EntityRepository         $optionRepository,
        private readonly EntityRepository         $categoryRepository,
        private readonly SystemConfigService      $systemConfigService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack             $requestStack,
        private readonly EntityRepository         $productRepository
    )
    {
        $this->treeItem = new TreeItem(null, []);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductSearchResultEvent::class => 'onProductListingSearchResultEvent',
            ProductListingCollectFilterEvent::class => 'addFilter'
//            ProductListingResultEvent::class => 'getResult',
//            ProductListingCriteriaEvent::class => 'handleCriteria',
//            ProductSearchCriteriaEvent::class => 'handleCriteria',
        ];
    }

    public function onProductListingSearchResultEvent(ProductListingResultEvent $event): void
    {
//        $filterResult = $this->filterRepository->search((new Criteria())->addSorting(new FieldSorting('position', FieldSorting::ASCENDING))->addFilter(new EqualsFilter('active', true)), $event->getContext());
//        $this->sortFilterResult($filterResult);
//
//        /** @var FilterEntity|null $categoryFilter */
//        $categoryFilter = $filterResult->getEntities()->filterByProperty('identifier', 'categories')->first();
//
        $this->buildCategoryTree($event->getResult(), $event->getSalesChannelContext());
    }

    public function handleCriteria(ProductListingCriteriaEvent $event): void
    {
        $criteria = $event->getCriteria();
        $criteria->addAssociation('categoriesRo');
        $criteria->addAssociation('mainCategories');
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

        $filter = new Filter(
            'categories',
            true,
            [new EntityAggregation('categories', 'product.categoriesRo.id', 'category')],
            $this->buildCategory(['24b3fa3f95063cb61d32ba26b0c7540f'], $event->getSalesChannelContext()->getContext()),
            ['24b3fa3f95063cb61d32ba26b0c7540f']
        );
        $filters->add($filter);

//        $categoryFilter = $this->getCategoryFilter($request);
//        $filters->add($categoryFilter);
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

        $clonedCategoryConnection = clone $categoryCollection;
        $tree = $this->loadTree($context->getSalesChannel()->getNavigationCategoryId(), $clonedCategoryConnection, $context, $categoryFilter);
        // Check if it's a single tree
        if (!empty($tree) && !$this->isSingleTreeStack($tree)) {
            // Add the extension only if it's not a single tree
            $categoryAggregation->addExtension(self::CATEGORY_FILTER_AGGREGATION_TREE, new Tree(null, $tree));
        }
    }

    /**
     * Recursively checks if the given tree structure is a single tree.
     *
     * @param array $elements
     * @return bool
     */
    private function isSingleTreeStack(array $elements): bool
    {
        // If there's more than one root element, it's not a single tree
        if (count($elements) > 1) {
            return false;
        }

        foreach ($elements as $element) {
            // Check if the element has children
            if (!empty($element->getChildren())) {
                // Recursively check the children
                if (!$this->isSingleTreeStack($element->getChildren())) {
                    return false;
                }
            }
        }

        // If we reach here, it's a single tree
        return true;
    }

    /**
     * Copied and modified from Core/Content/Category/Service/NavigationLoader.php
     *
     * @param string|null $parentId
     * @param CategoryCollection $categories
     * @param bool|null $isChildren
     * @return TreeItem[]
     */
    private function buildTree(?string $parentId, CategoryCollection $categories, ?bool $isChildren = false, ?FilterEntity $categoryFilter = null): array
    {
        $children = new CategoryCollection();
        foreach ($categories->getElements() as $category) {
            if ($category->getParentId() !== $parentId) {
                continue;
            }

            $categories->remove($category->getId());

            if ($category->getActive() === true && $category->getVisible() === true && $category->getType() === CategoryDefinition::TYPE_PAGE) $children->add($category);
        }

        $children->sortByPosition();

        $items = [];
        foreach ($children as $child) {
            $item = clone $this->treeItem;
            $item->setCategory($child);

            $item->setChildren(
                $this->buildTree($child->getId(), $categories, true, $categoryFilter)
            );

            $items[$child->getId()] = $item;
        }

        return $items;
    }

    private function loadTree(string $parentId, CategoryCollection $categories, SalesChannelContext $context, ?FilterEntity $categoryFilter = null): array
    {
        if ($categoryFilter && $categoryFilter->getLimitCategoryFilterScope() === FilterDefinition::LIMIT_CATEGORY_FILTER_SCOPE_CURRENT_TREE) {

            $levels = [];
            foreach ($categories as $category) {
                $level = $category->getLevel();
                $levels[$level] = ($levels[$level] ?? 0) + 1;
            }

            $categories = $categories->filter(function (CategoryEntity $category) use ($levels) {
                return ($levels[$category->getLevel()] ?? 0) > 1;
            });

            if ($categories->count() > 0) {
                $minLevel = min(array_map(fn(CategoryEntity $c) => $c->getLevel(), $categories->getElements()));

                foreach ($categories as $category) {
                    if ($category->getLevel() === $minLevel) {
                        $category->setParentId($parentId);
                    }
                }
            }

            $tree = $this->buildTree($parentId, $categories, false, $categoryFilter);
            return [new TreeItem($categories->get($parentId), $tree)];
        }

        if (!$categories->has($parentId)) return [];

        $tree = $this->buildTree($parentId, $categories, false, $categoryFilter);
        return [new TreeItem($categories->get($parentId), $tree)];
    }

    private function sortFilterResult(EntitySearchResult $filterResult): void
    {
        $filterResult->getEntities()->sort(function (FilterEntity $a, FilterEntity $b) {
            return $a->getPosition() <=> $b->getPosition();
        });
    }

    private function validateCategoryIds(array $selectedCategoryIds, Context $context): array
    {
        if ($selectedCategoryIds === []) {
            return [];
        }

        $categoryNames = [];
        foreach ($selectedCategoryIds as $slug) {
            $categoryNames[] = urldecode($slug);
        }

        // Logically, this should never happen, but just in case
        if ($categoryNames === []) {
            return [];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('name', $categoryNames));
        $criteria->setLimit(self::MAX_REQUEST_FILTER_VALUES);

        return $this->categoryRepository->searchIds($criteria, $context)->getIds();
    }

    public function getCategoryFilter(Request $request): Filter
    {
        $ids = $this->getCategoryIds($request);

        $categoryAggregation = new TermsAggregation('categories', 'product.categories.id');
        $categoryParentAggregation = new TermsAggregation('categoriesParents', 'product.categories.parentId');

        $aggregations = [
            $categoryAggregation,
            $categoryParentAggregation
        ];

        if (empty($ids)) {
            return new Filter('categories', false, $aggregations, new AndFilter([]), [], false);
        }

        $grouped = $this->connection->fetchAllAssociative(
            'SELECT LOWER(HEX(parent_id)) as parent_id, LOWER(HEX(id)) as id
             FROM category
             WHERE id IN (:ids)',
            ['ids' => Uuid::fromHexToBytesList($ids)],
            ['ids' => ArrayParameterType::BINARY]
        );

        $grouped = FetchModeHelper::group($grouped, static fn($row): string => (string)$row['id']);

        $filters = [];
        foreach ($grouped as $options) {
            $filters[] = new OrFilter([
                new EqualsAnyFilter('product.categoryIds', $options),
                new EqualsAnyFilter('product.categoryTree', $options),
            ]);
        }

        return new Filter('categories', true, $aggregations, new OrFilter($filters), $ids, false);
    }

    public function getResult(ProductListingResultEvent $event): void
    {
        $context = $event->getSalesChannelContext();
        $result = $event->getResult();
        $request = $event->getRequest();
        $this->addAggregation($request, $result, $context);
    }

    private function addAggregation(Request $request, ProductListingResult $result, SalesChannelContext $context): void
    {
        $ids = $this->collectSubCategoryIds($result);

        if (empty($ids)) {
            return;
        }

        $criteria = new Criteria($ids);
        $criteria->setLimit(500);
        $criteria->addAssociation('translations');
        $criteria->addAssociation('parents');
        $criteria->addAssociation('children');
        $criteria->setTitle('product-listing::category-filter');
        $criteria->addSorting(new FieldSorting('translations.name', FieldSorting::ASCENDING));

        $categories = new CategoryCollection();

        $repositoryIterator = new RepositoryIterator($this->repository, $context->getContext(), $criteria);
        while (($loop = $repositoryIterator->fetch()) !== null) {
            $entities = $loop->getEntities();

            $categories->merge($entities);
        }

        $criteria = new Criteria($categories->getParentIds());

        $parents = $this->repository->search($criteria, $context->getContext())->getEntities();

        foreach ($categories as $category) {
            $parent = $parents->get($category->getParentId());
            if (!$parent) {
                continue;
            }

            $category->setParent($parent);
        }

        $groupedParentCategories = $this->groupByParentCategories($categories);

        $criteria = new Criteria($groupedParentCategories->getParentIds());

        $parents = $this->repository->search($criteria, $context->getContext())->getEntities();

        foreach ($groupedParentCategories as $category) {
            $parent = $parents->get($category->getParentId());
            if (!$parent) {
                continue;
            }

            $category->setParent($parent);
        }

        $groupedCategories = $this->groupByParentCategories($groupedParentCategories, false);
        $groupedCategories->sortByPosition();
        $groupedCategories->sortByName();

        $aggregations = $result->getAggregations();

        // remove id results to prevent wrong usages
        $aggregations->remove('categories');
        $aggregations->remove('categoriesParents');

        $aggregations->add(new EntityResult('category', $groupedCategories));
    }

    private function groupByParentCategories(CategoryCollection $categories, bool $excludeDE = true): CategoryCollection
    {
        $categoriesParents = new CategoryCollection();

        foreach ($categories->getIterator() as $element) {
            $de = (!$excludeDE && $element->getName() === 'DE');

            if (($element->getParentId() === null || $element->getParent() === null) && !($de)) {
                continue;
            }

            if ($de) {
                $category = $element;
            } elseif ($categoriesParents->has($element->getParentId())) {
                $category = $categoriesParents->get($element->getParentId());
            } else {
                $category = CategoryEntity::createFrom($element->getParent());
                $categoriesParents->add($category);

                $category->setChildren(new CategoryCollection());
            }

            if ($de) {
                foreach ($categories->getIterator() as $cat) {
                    if ($cat->getName() === 'DE') {
                        continue;
                    }
                    $category->getChildren()->add($cat);
                }
            } elseif ($category->getChildren() && !($excludeDE && $element->getParent()->getName() === 'DE')) {
                $category->getChildren()->add($element);
            }
        }

        return $categoriesParents;
    }

    private function getCategoryIds(Request $request): array
    {
        $ids = $request->query->get('category', '');
        if ($request->isMethod(Request::METHOD_POST)) {
            $ids = $request->request->get('category', '');
        }

        if (\is_string($ids)) {
            $ids = explode('|', $ids);
        }

        /** @var list<string> $ids */
        $ids = array_filter((array)$ids);

        return $ids;
    }

    private function collectSubCategoryIds(ProductListingResult $result): array
    {
        $aggregations = $result->getAggregations();

        $parentCategories = $aggregations->get('categoriesParents');
        \assert($parentCategories instanceof TermsResult || $parentCategories === null);

        $subCategories = $aggregations->get('categories');
        \assert($subCategories instanceof TermsResult || $subCategories === null);

        $subCategories = $subCategories ? $subCategories->getKeys() : [];
        $parentCategories = $parentCategories ? $parentCategories->getKeys() : [];

        return array_unique(array_filter([...$subCategories, ...$parentCategories]));
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
}
