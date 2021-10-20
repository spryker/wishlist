<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist\Business\Model;

use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\WishlistItemCriteriaTransfer;
use Generated\Shared\Transfer\WishlistItemTransfer;
use Generated\Shared\Transfer\WishlistResponseTransfer;
use Generated\Shared\Transfer\WishlistTransfer;
use Orm\Zed\Wishlist\Persistence\SpyWishlist;
use Orm\Zed\Wishlist\Persistence\SpyWishlistQuery;
use Spryker\Zed\PropelOrm\Business\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\PropelOrm\Business\Transaction\DatabaseTransactionHandlerTrait;
use Spryker\Zed\Wishlist\Business\Exception\WishlistExistsException;
use Spryker\Zed\Wishlist\Dependency\Facade\WishlistToProductInterface;
use Spryker\Zed\Wishlist\Persistence\WishlistEntityManagerInterface;
use Spryker\Zed\Wishlist\Persistence\WishlistQueryContainerInterface;

class Writer implements WriterInterface
{
    use DatabaseTransactionHandlerTrait;

    /**
     * @var string
     */
    public const DEFAULT_NAME = 'default';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_NAME_ALREADY_EXISTS = 'wishlist.validation.error.name.already_exists';

    /**
     * @var string
     */
    protected const ERROR_MESSAGE_NAME_HAS_INCORRECT_FORMAT = 'wishlist.validation.error.name.wrong_format';

    /**
     * @var string
     */
    protected const WISH_LIST_NAME_VALIDATION_REGEX = '/^[ A-Za-z0-9_-]+$/';

    /**
     * @var \Spryker\Zed\Wishlist\Persistence\WishlistQueryContainerInterface
     */
    protected $queryContainer;

    /**
     * @var \Spryker\Zed\Wishlist\Business\Model\ReaderInterface
     */
    protected $reader;

    /**
     * @var \Spryker\Zed\Wishlist\Persistence\WishlistEntityManagerInterface
     */
    protected $wishlistEntityManager;

    /**
     * @var \Spryker\Zed\Wishlist\Dependency\Facade\WishlistToProductInterface|null
     */
    protected $productFacade;

    /**
     * @var array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\AddItemPreCheckPluginInterface>
     */
    protected $addItemPreCheckPlugins;

    /**
     * @var array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistPreAddItemPluginInterface>
     */
    protected $wishlistPreAddItemPlugins;

    /**
     * @param \Spryker\Zed\Wishlist\Persistence\WishlistQueryContainerInterface $queryContainer
     * @param \Spryker\Zed\Wishlist\Business\Model\ReaderInterface $reader
     * @param \Spryker\Zed\Wishlist\Persistence\WishlistEntityManagerInterface $wishlistEntityManager
     * @param \Spryker\Zed\Wishlist\Dependency\Facade\WishlistToProductInterface|null $productFacade
     * @param array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\AddItemPreCheckPluginInterface> $addItemPreCheckPlugins
     * @param array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistPreAddItemPluginInterface> $wishlistPreAddItemPlugins
     */
    public function __construct(
        WishlistQueryContainerInterface $queryContainer,
        ReaderInterface $reader,
        WishlistEntityManagerInterface $wishlistEntityManager,
        ?WishlistToProductInterface $productFacade = null,
        array $addItemPreCheckPlugins = [],
        array $wishlistPreAddItemPlugins = []
    ) {
        $this->queryContainer = $queryContainer;
        $this->reader = $reader;
        $this->wishlistEntityManager = $wishlistEntityManager;
        $this->productFacade = $productFacade;
        $this->addItemPreCheckPlugins = $addItemPreCheckPlugins;
        $this->wishlistPreAddItemPlugins = $wishlistPreAddItemPlugins;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function createWishlist(WishlistTransfer $wishlistTransfer)
    {
        $this->assertWishlistUniqueName($wishlistTransfer);

        return $this->handleDatabaseTransaction(function () use ($wishlistTransfer) {
            return $this->executeCreateWishlistTransaction($wishlistTransfer);
        });
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function executeCreateWishlistTransaction(WishlistTransfer $wishlistTransfer)
    {
        $wishlistEntity = new SpyWishlist();
        $wishlistEntity->fromArray($wishlistTransfer->toArray());
        $wishlistEntity->save();

        $wishlistTransfer->fromArray($wishlistEntity->toArray(), true);

        return $wishlistTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistResponseTransfer
     */
    public function validateAndCreateWishlist(WishlistTransfer $wishlistTransfer)
    {
        $wishlistResponseTransfer = new WishlistResponseTransfer();

        if (!$this->checkWishlistUniqueName($wishlistTransfer)) {
            return $wishlistResponseTransfer
                ->setIsSuccess(false)
                ->addError(static::ERROR_MESSAGE_NAME_ALREADY_EXISTS);
        }

        if (!$this->isWishListNameValid($wishlistTransfer)) {
            return $wishlistResponseTransfer
                ->setIsSuccess(false)
                ->addError(static::ERROR_MESSAGE_NAME_HAS_INCORRECT_FORMAT);
        }

        return $wishlistResponseTransfer
            ->setWishlist($this->createWishlist($wishlistTransfer))
            ->setIsSuccess(true);
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function updateWishlist(WishlistTransfer $wishlistTransfer)
    {
        $wishlistTransfer->requireIdWishlist();

        return $this->handleDatabaseTransaction(function () use ($wishlistTransfer) {
            return $this->executeUpdateWishlistTransaction($wishlistTransfer);
        });
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function executeUpdateWishlistTransaction(WishlistTransfer $wishlistTransfer)
    {
        $wishListEntity = $this->reader->getWishlistEntityById($wishlistTransfer->getIdWishlist());
        $this->assertWishlistUniqueNameWhenUpdating($wishlistTransfer);

        $wishListEntity->fromArray($wishlistTransfer->toArray());
        $wishListEntity->save();

        return $wishlistTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistResponseTransfer
     */
    public function validateAndUpdateWishlist(WishlistTransfer $wishlistTransfer)
    {
        $wishlistResponseTransfer = new WishlistResponseTransfer();

        if (!$this->checkWishlistUniqueNameWhenUpdating($wishlistTransfer)) {
            return $wishlistResponseTransfer
                ->setIsSuccess(false)
                ->addError(static::ERROR_MESSAGE_NAME_ALREADY_EXISTS);
        }

        if (!$this->isWishListNameValid($wishlistTransfer)) {
            return $wishlistResponseTransfer
                ->setIsSuccess(false)
                ->addError(static::ERROR_MESSAGE_NAME_HAS_INCORRECT_FORMAT);
        }

        return $wishlistResponseTransfer
            ->setWishlist($this->updateWishlist($wishlistTransfer))
            ->setIsSuccess(true);
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function removeWishlist(WishlistTransfer $wishlistTransfer)
    {
        $wishlistTransfer->requireIdWishlist();

        return $this->handleDatabaseTransaction(function () use ($wishlistTransfer) {
            return $this->executeRemoveWishlistTransaction($wishlistTransfer);
        });
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function executeRemoveWishlistTransaction(WishlistTransfer $wishlistTransfer)
    {
        $wishListEntity = $this->reader->getWishlistEntityById($wishlistTransfer->getIdWishlist());

        $this->emptyWishlist($wishlistTransfer);
        $wishListEntity->delete();

        return $wishlistTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function removeWishlistByName(WishlistTransfer $wishlistTransfer)
    {
        $wishlistTransfer
            ->requireName()
            ->requireFkCustomer();

        return $this->handleDatabaseTransaction(function () use ($wishlistTransfer) {
            return $this->executeRemoveWishlistByNameTransaction($wishlistTransfer);
        });
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function executeRemoveWishlistByNameTransaction(WishlistTransfer $wishlistTransfer)
    {
        $wishListEntity = $this->reader->getWishlistEntityByCustomerIdAndWishlistName(
            $wishlistTransfer->getFkCustomer(),
            $wishlistTransfer->getName()
        );
        $wishlistTransfer->fromArray($wishListEntity->toArray(), true);

        $this->emptyWishlist($wishlistTransfer);
        $wishListEntity->delete();

        return $wishlistTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     * @param array<\Generated\Shared\Transfer\WishlistItemTransfer> $wishlistItemCollection
     *
     * @return void
     */
    public function addItemCollection(WishlistTransfer $wishlistTransfer, array $wishlistItemCollection)
    {
        $wishlistTransfer->requireFkCustomer();
        $wishlistTransfer->requireName();

        foreach ($wishlistItemCollection as $itemTransfer) {
            $itemTransfer->setWishlistName($wishlistTransfer->getName());
            $itemTransfer->setFkCustomer($wishlistTransfer->getFkCustomer());
            $this->addItem($itemTransfer);
        }
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return void
     */
    public function emptyWishlist(WishlistTransfer $wishlistTransfer)
    {
        $wishlistTransfer->requireIdWishlist();

        $this->queryContainer->queryWishlistItem()
            ->filterByFkWishlist($wishlistTransfer->getIdWishlist())
            ->delete();
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistItemTransfer $wishlistItemTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistItemTransfer
     */
    public function addItem(WishlistItemTransfer $wishlistItemTransfer)
    {
        $this->assertWishlistItemUpdateRequest($wishlistItemTransfer);

        $productConcreteTransfer = (new ProductConcreteTransfer())->setSku($wishlistItemTransfer->getSku());

        if (
            $this->productFacade
            && (!$this->productFacade->hasProductConcrete($wishlistItemTransfer->getSku())
                || !$this->productFacade->isProductConcreteActive($productConcreteTransfer))
        ) {
            return $wishlistItemTransfer;
        }

        if (!$this->preAddItemCheck($wishlistItemTransfer)) {
            return $wishlistItemTransfer;
        }

        $idWishlist = $this->getDefaultWishlistIdByName(
            $wishlistItemTransfer->getWishlistName(),
            $wishlistItemTransfer->getFkCustomer()
        );

        $wishlistItemTransfer->setFkWishlist($idWishlist);

        $wishlistItemTransfer = $this->executeWishlistPreAddItemPlugins($wishlistItemTransfer);
        $createdWishlistItemTransfer = $this->wishlistEntityManager->addItem($wishlistItemTransfer);

        $wishlistItemTransfer->setIdWishlistItem($createdWishlistItemTransfer->getIdWishlistItemOrFail());

        // This part for expanding created wishlist item.
        $wishlistItemCriteriaTransfer = (new WishlistItemCriteriaTransfer())
            ->fromArray($wishlistItemTransfer->toArray(), true);

        $newWishlistItemTransfer = $this->reader->findWishlistItem($wishlistItemCriteriaTransfer);

        if (!$newWishlistItemTransfer) {
            return new WishlistItemTransfer();
        }

        $newWishlistItemTransfer->setWishlistName($wishlistItemTransfer->getWishlistName())
            ->setFkCustomer($wishlistItemTransfer->getFkCustomer());

        return $newWishlistItemTransfer;
    }

    /**
     * @param string $name
     * @param int $fkCustomer
     *
     * @return int
     */
    protected function getDefaultWishlistIdByName($name, $fkCustomer)
    {
        $name = trim($name);
        if ($name === '') {
            $name = self::DEFAULT_NAME;
        }

        $wishlistEntity = $this->queryContainer
            ->queryWishlist()
            ->filterByFkCustomer($fkCustomer)
            ->filterByName($name)
            ->findOneOrCreate();

        if ($wishlistEntity->isNew()) {
            $wishlistEntity->save();
        }

        return $wishlistEntity->getIdWishlist();
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistItemTransfer $wishlistItemTransfer
     *
     * @return void
     */
    protected function assertWishlistItem(WishlistItemTransfer $wishlistItemTransfer)
    {
        $wishlistItemTransfer->requireFkWishlist();
        $wishlistItemTransfer->requireSku();
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistItemTransfer $wishlistItemTransfer
     *
     * @return void
     */
    protected function assertWishlistItemUpdateRequest(WishlistItemTransfer $wishlistItemTransfer)
    {
        $wishlistItemTransfer->requireFkCustomer();
        $wishlistItemTransfer->requireSku();
        $wishlistItemTransfer->requireWishlistName();
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return void
     */
    protected function assertWishlistUniqueName(WishlistTransfer $wishlistTransfer)
    {
        $wishlistTransfer->requireName();
        $wishlistTransfer->requireFkCustomer();

        $query = $this->queryContainer->queryWishlist()
            ->filterByName($wishlistTransfer->getName())
            ->filterByFkCustomer($wishlistTransfer->getFkCustomer());

        $this->assertWishlistIsUnique($query, $wishlistTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return void
     */
    protected function assertWishlistUniqueNameWhenUpdating(WishlistTransfer $wishlistTransfer)
    {
        $wishlistTransfer->requireName();
        $wishlistTransfer->requireIdWishlist();

        $query = $this->queryContainer->queryWishlist()
            ->filterByName($wishlistTransfer->getName())
            ->filterByFkCustomer($wishlistTransfer->getFkCustomer())
            ->filterByIdWishlist($wishlistTransfer->getIdWishlist(), Criteria::NOT_EQUAL);

        $this->assertWishlistIsUnique($query, $wishlistTransfer);
    }

    /**
     * @phpstan-param \Orm\Zed\Wishlist\Persistence\SpyWishlistQuery<mixed> $query
     *
     * @param \Orm\Zed\Wishlist\Persistence\SpyWishlistQuery $query
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @throws \Spryker\Zed\Wishlist\Business\Exception\WishlistExistsException
     *
     * @return void
     */
    protected function assertWishlistIsUnique(SpyWishlistQuery $query, WishlistTransfer $wishlistTransfer)
    {
        $exists = $query->count();

        if ($exists) {
            throw new WishlistExistsException(sprintf(
                'Wishlist with name "%s" for customer "%s" already exists',
                $wishlistTransfer->getName(),
                $wishlistTransfer->getFkCustomer()
            ));
        }
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return bool
     */
    protected function checkWishlistUniqueName(WishlistTransfer $wishlistTransfer)
    {
        $wishlistTransfer->requireName();
        $wishlistTransfer->requireFkCustomer();

        $query = $this->queryContainer->queryWishlist()
            ->filterByName($wishlistTransfer->getName())
            ->filterByFkCustomer($wishlistTransfer->getFkCustomer());

        return $query->count() === 0;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return bool
     */
    protected function checkWishlistUniqueNameWhenUpdating(WishlistTransfer $wishlistTransfer)
    {
        $wishlistTransfer->requireName();
        $wishlistTransfer->requireIdWishlist();

        $query = $this->queryContainer->queryWishlist()
            ->filterByName($wishlistTransfer->getName())
            ->filterByFkCustomer($wishlistTransfer->getFkCustomer())
            ->filterByIdWishlist($wishlistTransfer->getIdWishlist(), Criteria::NOT_EQUAL);

        return $query->count() === 0;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return bool
     */
    protected function isWishListNameValid(WishlistTransfer $wishlistTransfer): bool
    {
        $wishlistTransfer->requireName();

        return (bool)preg_match(static::WISH_LIST_NAME_VALIDATION_REGEX, $wishlistTransfer->getName());
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistItemTransfer $wishlistItemTransfer
     *
     * @return bool
     */
    protected function preAddItemCheck(WishlistItemTransfer $wishlistItemTransfer): bool
    {
        foreach ($this->addItemPreCheckPlugins as $preAddItemCheckPlugin) {
            $shoppingListPreAddItemCheckResponseTransfer = $preAddItemCheckPlugin->check($wishlistItemTransfer);
            if (!$shoppingListPreAddItemCheckResponseTransfer->getIsSuccess()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistItemTransfer $wishlistItemTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistItemTransfer
     */
    protected function executeWishlistPreAddItemPlugins(WishlistItemTransfer $wishlistItemTransfer): WishlistItemTransfer
    {
        foreach ($this->wishlistPreAddItemPlugins as $wishlistPreAddItemPlugin) {
            $wishlistItemTransfer = $wishlistPreAddItemPlugin->preAddItem($wishlistItemTransfer);
        }

        return $wishlistItemTransfer;
    }
}
