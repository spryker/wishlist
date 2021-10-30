<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist\Business\Model;

use ArrayObject;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\WishlistCollectionTransfer;
use Generated\Shared\Transfer\WishlistFilterTransfer;
use Generated\Shared\Transfer\WishlistItemCriteriaTransfer;
use Generated\Shared\Transfer\WishlistItemMetaTransfer;
use Generated\Shared\Transfer\WishlistItemResponseTransfer;
use Generated\Shared\Transfer\WishlistItemTransfer;
use Generated\Shared\Transfer\WishlistOverviewMetaTransfer;
use Generated\Shared\Transfer\WishlistOverviewRequestTransfer;
use Generated\Shared\Transfer\WishlistOverviewResponseTransfer;
use Generated\Shared\Transfer\WishlistPaginationTransfer;
use Generated\Shared\Transfer\WishlistResponseTransfer;
use Generated\Shared\Transfer\WishlistTransfer;
use Propel\Runtime\Util\PropelModelPager;
use Spryker\Zed\Wishlist\Business\Exception\MissingWishlistException;
use Spryker\Zed\Wishlist\Business\Transfer\WishlistTransferMapperInterface;
use Spryker\Zed\Wishlist\Dependency\QueryContainer\WishlistToProductInterface;
use Spryker\Zed\Wishlist\Persistence\WishlistQueryContainerInterface;
use Spryker\Zed\Wishlist\Persistence\WishlistRepositoryInterface;

class Reader implements ReaderInterface
{
    /**
     * @var string
     */
    protected const ERROR_MESSAGE_WISHLIST_NOT_FOUND = 'wishlist.not.found';

    /**
     * @var \Spryker\Zed\Wishlist\Persistence\WishlistQueryContainerInterface
     */
    protected $queryContainer;

    /**
     * @var \Spryker\Zed\Wishlist\Dependency\QueryContainer\WishlistToProductInterface
     */
    protected $productQueryContainer;

    /**
     * @var \Spryker\Zed\Wishlist\Business\Transfer\WishlistTransferMapperInterface
     */
    protected $transferMapper;

    /**
     * @var \Spryker\Zed\Wishlist\Persistence\WishlistRepositoryInterface
     */
    protected $wishlistRepository;

    /**
     * @var array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistReloadItemsPluginInterface>
     */
    protected $wishlistReloadItemsPlugins;

    /**
     * @var array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistItemsValidatorPluginInterface>
     */
    protected $wishlistItemsValidatorPlugins;

    /**
     * @var array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistItemExpanderPluginInterface>
     */
    protected $wishlistItemExpanderPlugins;

    /**
     * @param \Spryker\Zed\Wishlist\Persistence\WishlistQueryContainerInterface $queryContainer
     * @param \Spryker\Zed\Wishlist\Dependency\QueryContainer\WishlistToProductInterface $productQueryContainer
     * @param \Spryker\Zed\Wishlist\Business\Transfer\WishlistTransferMapperInterface $transferMapper
     * @param \Spryker\Zed\Wishlist\Persistence\WishlistRepositoryInterface $wishlistRepository
     * @param array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistReloadItemsPluginInterface> $wishlistReloadItemsPlugins
     * @param array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistItemsValidatorPluginInterface> $wishlistItemsValidatorPlugins
     * @param array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistItemExpanderPluginInterface> $wishlistItemExpanderPlugins
     */
    public function __construct(
        WishlistQueryContainerInterface $queryContainer,
        WishlistToProductInterface $productQueryContainer,
        WishlistTransferMapperInterface $transferMapper,
        WishlistRepositoryInterface $wishlistRepository,
        array $wishlistReloadItemsPlugins = [],
        array $wishlistItemsValidatorPlugins = [],
        array $wishlistItemExpanderPlugins = []
    ) {
        $this->queryContainer = $queryContainer;
        $this->productQueryContainer = $productQueryContainer;
        $this->transferMapper = $transferMapper;
        $this->wishlistRepository = $wishlistRepository;
        $this->wishlistReloadItemsPlugins = $wishlistReloadItemsPlugins;
        $this->wishlistItemsValidatorPlugins = $wishlistItemsValidatorPlugins;
        $this->wishlistItemExpanderPlugins = $wishlistItemExpanderPlugins;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function getWishlistByName(WishlistTransfer $wishlistTransfer)
    {
        $wishlistTransfer->requireFkCustomer();
        $wishlistTransfer->requireName();

        $wishlistEntity = $this->getWishlistEntityByCustomerIdAndWishlistName(
            $wishlistTransfer->getFkCustomer(),
            $wishlistTransfer->getName(),
        );

        return $this->transferMapper->convertWishlist($wishlistEntity);
    }

    /**
     * @api
     *
     * @param \Generated\Shared\Transfer\WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistOverviewResponseTransfer
     */
    public function getWishlistOverview(WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer)
    {
        $this->assertWishlistOverviewRequest($wishlistOverviewRequestTransfer);

        $wishlistPaginationTransfer = $this->buildWishlistPaginationTransfer($wishlistOverviewRequestTransfer);
        $wishlistOverviewResponseTransfer = $this->buildWishlistOverviewResponseTransfer(
            $wishlistOverviewRequestTransfer->getWishlist(),
            $wishlistPaginationTransfer,
        );

        $wishlistEntity = $this->findWishlistEntity($wishlistOverviewRequestTransfer->getWishlist());
        if (!$wishlistEntity) {
            return $wishlistOverviewResponseTransfer;
        }

        $wishlistTransfer = $this->transferMapper->convertWishlist($wishlistEntity);
        $wishlistTransfer->fromArray($wishlistOverviewRequestTransfer->getWishlist()->modifiedToArray());
        $wishlistOverviewRequestTransfer->setWishlist($wishlistTransfer);

        $itemPaginationModel = $this->getWishlistOverviewPaginationModel($wishlistOverviewRequestTransfer);
        $wishlistPaginationTransfer = $this->updatePaginationTransfer($wishlistPaginationTransfer, $itemPaginationModel);
        /** @var \Propel\Runtime\Collection\ObjectCollection|\Orm\Zed\Wishlist\Persistence\SpyWishlistItem[] $wishlistItemCollection */
        $wishlistItemCollection = $itemPaginationModel->getResults();
        $wishlistItems = $this->transferMapper->convertWishlistItemCollection($wishlistItemCollection);

        $wishlistItems = $this->expandProductId($wishlistItems);
        $wishlistTransfer->setWishlistItems(new ArrayObject($wishlistItems));

        $wishlistTransfer = $this->reloadWishlistItems($wishlistTransfer);
        $this->validateWishlistItems($wishlistTransfer, $wishlistOverviewResponseTransfer);

        $wishlistOverviewMetaTransfer = $this->createWishlistOverviewMeta($wishlistOverviewRequestTransfer);

        $wishlistOverviewMetaTransfer->setWishlistItemMetaCollection(
            $this->transferMapper->mapWishlistItemTransfersToWishlistItemMetaTransfers(
                $wishlistTransfer->getWishlistItems(),
                $wishlistOverviewMetaTransfer->getWishlistItemMetaCollection(),
            ),
        );

        $wishlistOverviewResponseTransfer
            ->setWishlist($wishlistTransfer)
            ->setPagination($wishlistPaginationTransfer)
            ->setItems($wishlistTransfer->getWishlistItems())
            ->setMeta($wishlistOverviewMetaTransfer);

        return $wishlistOverviewResponseTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistItemCriteriaTransfer $wishlistItemCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistItemTransfer|null
     */
    public function findWishlistItem(WishlistItemCriteriaTransfer $wishlistItemCriteriaTransfer): ?WishlistItemTransfer
    {
        $wishlistItemTransfer = $this->wishlistRepository->findWishlistItem($wishlistItemCriteriaTransfer);

        if (!$wishlistItemTransfer) {
            return null;
        }

        $wishlistItemTransfer = $this->executeWishlistItemExpanderPlugins($wishlistItemTransfer);

        return $wishlistItemTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistItemCriteriaTransfer $wishlistItemCriteriaTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistItemResponseTransfer
     */
    public function getWishlistItem(WishlistItemCriteriaTransfer $wishlistItemCriteriaTransfer): WishlistItemResponseTransfer
    {
        $wishlistItemResponseTransfer = new WishlistItemResponseTransfer();
        $wishlistItemTransfer = $this->findWishlistItem($wishlistItemCriteriaTransfer);

        if (!$wishlistItemTransfer) {
            return $wishlistItemResponseTransfer->setIsSuccess(false);
        }

        return $wishlistItemResponseTransfer
            ->setIsSuccess(true)
            ->setWishlistItem($wishlistItemTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer
     *
     * @return void
     */
    protected function assertWishlistOverviewRequest(WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer)
    {
        $wishlistOverviewRequestTransfer->requireWishlist();

        $wishlistTransfer = $wishlistOverviewRequestTransfer->getWishlist();
        $wishlistTransfer->requireFkCustomer();
        $wishlistTransfer->requireName();
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistPaginationTransfer
     */
    protected function buildWishlistPaginationTransfer(WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer)
    {
        return (new WishlistPaginationTransfer())
            ->setPage($wishlistOverviewRequestTransfer->getPage())
            ->setItemsPerPage($wishlistOverviewRequestTransfer->getItemsPerPage());
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     * @param \Generated\Shared\Transfer\WishlistPaginationTransfer $wishlistPaginationTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistOverviewResponseTransfer
     */
    protected function buildWishlistOverviewResponseTransfer(WishlistTransfer $wishlistTransfer, WishlistPaginationTransfer $wishlistPaginationTransfer)
    {
        return (new WishlistOverviewResponseTransfer())
            ->setWishlist($wishlistTransfer)
            ->setPagination($wishlistPaginationTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Orm\Zed\Wishlist\Persistence\SpyWishlist|null
     */
    protected function findWishlistEntity(WishlistTransfer $wishlistTransfer)
    {
        return $this->queryContainer
            ->queryWishlistByCustomerId($wishlistTransfer->getFkCustomer())
            ->filterByName($wishlistTransfer->getName())
            ->findOne();
    }

    /**
     * @phpstan-param \Propel\Runtime\Util\PropelModelPager<mixed> $itemPaginationModel
     *
     * @param \Generated\Shared\Transfer\WishlistPaginationTransfer $paginationTransfer
     * @param \Propel\Runtime\Util\PropelModelPager $itemPaginationModel
     *
     * @return \Generated\Shared\Transfer\WishlistPaginationTransfer
     */
    protected function updatePaginationTransfer(WishlistPaginationTransfer $paginationTransfer, PropelModelPager $itemPaginationModel)
    {
        $pagesTotal = ceil($itemPaginationModel->getNbResults() / $itemPaginationModel->getMaxPerPage());
        $paginationTransfer->setPagesTotal((int)$pagesTotal);
        $paginationTransfer->setItemsTotal($itemPaginationModel->getNbResults());
        $paginationTransfer->setItemsPerPage($itemPaginationModel->getMaxPerPage());

        if ($paginationTransfer->getPage() <= 0) {
            $paginationTransfer->setPage(1);
        }

        if ($paginationTransfer->getPage() > $pagesTotal) {
            $paginationTransfer->setPage((int)$pagesTotal);
        }

        return $paginationTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer
     *
     * @return \Propel\Runtime\Util\PropelModelPager|\Orm\Zed\Wishlist\Persistence\SpyWishlistItem[]
     */
    protected function getWishlistOverviewPaginationModel(WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer)
    {
        $wishlistOverviewRequestTransfer->requireWishlist();
        $wishlistOverviewRequestTransfer->getWishlist()->requireIdWishlist();

        $page = $wishlistOverviewRequestTransfer
            ->requirePage()
            ->getPage();

        $maxPerPage = $wishlistOverviewRequestTransfer
            ->requireItemsPerPage()
            ->getItemsPerPage();

        $itemsQuery = $this->queryContainer->queryItemsByWishlistId(
            $wishlistOverviewRequestTransfer->getWishlist()->getIdWishlist(),
        );

        return $itemsQuery->paginate($page, $maxPerPage);
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistOverviewMetaTransfer
     */
    protected function createWishlistOverviewMeta(WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer)
    {
        $wishlistOverviewMetaTransfer = new WishlistOverviewMetaTransfer();
        $idCustomer = $wishlistOverviewRequestTransfer->getWishlist()->getFkCustomer();
        $idWishlist = $wishlistOverviewRequestTransfer->getWishlist()->getIdWishlist();

        $wishlistOverviewMetaTransfer
            ->setWishlistCollection($this->getCollectionByIdCustomer($idCustomer))
            ->setWishlistItemMetaCollection($this->createWishlistItemMetaCollection($idWishlist));

        return $wishlistOverviewMetaTransfer;
    }

    /**
     * @param int $idWishlist
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\WishlistItemMetaTransfer>
     */
    protected function createWishlistItemMetaCollection($idWishlist)
    {
        $wishlistItemEntities = $this->queryContainer
            ->queryItemsByWishlistId($idWishlist)
            ->find();

        $wishlistItemMetaTransfers = new ArrayObject();
        foreach ($wishlistItemEntities as $wishlistItemEntity) {
            $productEntity = $wishlistItemEntity->getSpyProduct();
            $wishlistItemMetaTransfer = new WishlistItemMetaTransfer();
            $wishlistItemMetaTransfer->fromArray($wishlistItemEntity->toArray(), true);
            $wishlistItemMetaTransfer = $this->transferMapper
                ->mapProductEntityToWishlistItemMetaTransfer($productEntity, $wishlistItemMetaTransfer);
            $wishlistItemMetaTransfers->append($wishlistItemMetaTransfer);
        }

        return $wishlistItemMetaTransfers;
    }

    /**
     * @param array<\Generated\Shared\Transfer\WishlistItemTransfer> $wishlistItemCollection
     *
     * @return array<\Generated\Shared\Transfer\WishlistItemTransfer>
     */
    protected function expandProductId(array $wishlistItemCollection)
    {
        $productCollection = $this->getProductCollection($wishlistItemCollection);

        foreach ($productCollection as $productEntity) {
            foreach ($wishlistItemCollection as $itemTransfer) {
                if (mb_strtolower($itemTransfer->getSku()) === mb_strtolower($productEntity->getSku())) {
                    $itemTransfer->setIdProduct($productEntity->getIdProduct());
                }
            }
        }

        return $wishlistItemCollection;
    }

    /**
     * @param array<\Generated\Shared\Transfer\WishlistItemTransfer> $itemCollection
     *
     * @return \Propel\Runtime\Collection\ObjectCollection|\Orm\Zed\Product\Persistence\SpyProduct[]
     */
    protected function getProductCollection(array $itemCollection)
    {
        $skuCollection = $this->getSkuCollection($itemCollection);

        return $this->productQueryContainer
            ->queryProduct()
            ->filterBySku_In($skuCollection)
            ->find();
    }

    /**
     * @phpstan-return array<int, string>
     *
     * @param array<\Generated\Shared\Transfer\WishlistItemTransfer> $itemCollection
     *
     * @return array
     */
    protected function getSkuCollection(array $itemCollection)
    {
        $skuCollection = [];
        foreach ($itemCollection as $itemTransfer) {
            $skuCollection[] = $itemTransfer->getSku();
        }

        return $skuCollection;
    }

    /**
     * @param int $idWishlist
     *
     * @return int
     */
    protected function getTotalItemCount($idWishlist)
    {
        return $this->queryContainer->queryWishlistItem()
            ->filterByFkWishlist($idWishlist)
            ->count();
    }

    /**
     * @param int $idWishlist
     *
     * @throws \Spryker\Zed\Wishlist\Business\Exception\MissingWishlistException
     *
     * @return \Orm\Zed\Wishlist\Persistence\SpyWishlist
     */
    public function getWishlistEntityById($idWishlist)
    {
        $wishlistEntity = $this->queryContainer->queryWishlist()
            ->filterByIdWishlist($idWishlist)
            ->findOne();

        if (!$wishlistEntity) {
            throw new MissingWishlistException(sprintf(
                'Wishlist with id %s not found',
                $idWishlist,
            ));
        }

        return $wishlistEntity;
    }

    /**
     * @param int $idCustomer
     * @param string $name
     *
     * @throws \Spryker\Zed\Wishlist\Business\Exception\MissingWishlistException
     *
     * @return \Orm\Zed\Wishlist\Persistence\SpyWishlist
     */
    public function getWishlistEntityByCustomerIdAndWishlistName($idCustomer, $name)
    {
        $wishlistEntity = $this->queryContainer
            ->queryWishlistByCustomerId($idCustomer)
            ->filterByName($name)
            ->findOne();

        if (!$wishlistEntity) {
            throw new MissingWishlistException(sprintf(
                'Wishlist: %s for customer with id: %s not found',
                $name,
                $idCustomer,
            ));
        }

        return $wishlistEntity;
    }

    /**
     * @param \Generated\Shared\Transfer\CustomerTransfer $customerTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistCollectionTransfer
     */
    public function getCustomerWishlistCollection(CustomerTransfer $customerTransfer)
    {
        if ($customerTransfer->getCustomerReference()) {
            return $this->getCollectionByCustomerReference($customerTransfer->getCustomerReference());
        }

        $idCustomer = $customerTransfer
            ->requireIdCustomer()
            ->getIdCustomer();

        return $this->getCollectionByIdCustomer($idCustomer);
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistFilterTransfer $wishlistFilterTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistResponseTransfer
     */
    public function getWishlistByFilter(WishlistFilterTransfer $wishlistFilterTransfer): WishlistResponseTransfer
    {
        $wishlistFilterTransfer->requireIdCustomer();

        $wishlistTransfer = $this->wishlistRepository->findWishlistByFilter($wishlistFilterTransfer);

        if (!$wishlistTransfer) {
            return (new WishlistResponseTransfer())
                ->setIsSuccess(false)
                ->addError(static::ERROR_MESSAGE_WISHLIST_NOT_FOUND);
        }

        $wishlistItemTransfers = [];

        foreach ($wishlistTransfer->getWishlistItems() as $wishlistItemTransfer) {
            $wishlistItemTransfers[] = $this->executeWishlistItemExpanderPlugins($wishlistItemTransfer);
        }

        $wishlistTransfer->setWishlistItems(new ArrayObject($wishlistItemTransfers));

        return (new WishlistResponseTransfer())
            ->setWishlist($wishlistTransfer)
            ->setIsSuccess(true);
    }

    /**
     * @param int $idCustomer
     * @param string $name
     *
     * @return bool
     */
    protected function hasCustomerWishlist($idCustomer, $name)
    {
        return $this->queryContainer
            ->queryWishlistByCustomerId($idCustomer)
            ->filterByName($name)
            ->count() > 0;
    }

    /**
     * @param int $idCustomer
     *
     * @return \Generated\Shared\Transfer\WishlistCollectionTransfer
     */
    protected function getCollectionByIdCustomer($idCustomer)
    {
        $wishlistCollection = new WishlistCollectionTransfer();
        $wishlistEntities = $this->queryContainer
            ->queryWishlistByCustomerId($idCustomer)
            ->find();

        if (!$wishlistEntities->count()) {
            return $wishlistCollection;
        }

        foreach ($wishlistEntities as $wishlistEntity) {
            $wishlistCollection->addWishlist($this->transferMapper->convertWishlist($wishlistEntity));
        }

        return $wishlistCollection;
    }

    /**
     * @param string $customerReference
     *
     * @return \Generated\Shared\Transfer\WishlistCollectionTransfer
     */
    protected function getCollectionByCustomerReference(string $customerReference): WishlistCollectionTransfer
    {
        return $this->wishlistRepository->getByCustomerReference($customerReference);
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    protected function reloadWishlistItems(WishlistTransfer $wishlistTransfer): WishlistTransfer
    {
        foreach ($this->wishlistReloadItemsPlugins as $wishlistReloadItemsPlugin) {
            if ($wishlistReloadItemsPlugin->isApplicable($wishlistTransfer)) {
                $wishlistTransfer = $wishlistReloadItemsPlugin->reloadItems($wishlistTransfer);
            }
        }

        return $wishlistTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     * @param \Generated\Shared\Transfer\WishlistOverviewResponseTransfer $wishlistOverviewResponseTransfer
     *
     * @return void
     */
    protected function validateWishlistItems(
        WishlistTransfer $wishlistTransfer,
        WishlistOverviewResponseTransfer $wishlistOverviewResponseTransfer
    ): void {
        foreach ($this->wishlistItemsValidatorPlugins as $wishlistItemsValidatorPlugin) {
            if (!$wishlistItemsValidatorPlugin->isApplicable($wishlistTransfer)) {
                continue;
            }

            $validationResponseTransfer = $wishlistItemsValidatorPlugin->validateItems($wishlistTransfer);

            if ($validationResponseTransfer->getIsSuccess()) {
                continue;
            }

            foreach ($validationResponseTransfer->getErrorMessages() as $messageTransfer) {
                $wishlistOverviewResponseTransfer->addError($messageTransfer);
            }
        }
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistItemTransfer $wishlistItemTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistItemTransfer
     */
    protected function executeWishlistItemExpanderPlugins(WishlistItemTransfer $wishlistItemTransfer): WishlistItemTransfer
    {
        foreach ($this->wishlistItemExpanderPlugins as $wishlistItemExpanderPlugin) {
            $wishlistItemTransfer = $wishlistItemExpanderPlugin->expand($wishlistItemTransfer);
        }

        return $wishlistItemTransfer;
    }
}
