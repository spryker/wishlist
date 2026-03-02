<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist\Business\Updater;

use Generated\Shared\Transfer\MessageTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\WishlistItemCriteriaTransfer;
use Generated\Shared\Transfer\WishlistItemResponseTransfer;
use Generated\Shared\Transfer\WishlistItemTransfer;
use Spryker\Zed\Wishlist\Dependency\Facade\WishlistToProductInterface;
use Spryker\Zed\Wishlist\Persistence\WishlistEntityManagerInterface;
use Spryker\Zed\Wishlist\Persistence\WishlistRepositoryInterface;

class WishlistItemUpdater implements WishlistItemUpdaterInterface
{
    /**
     * @var string
     */
    protected const GLOSSARY_KEY_WISHLIST_ITEM_NOT_FOUND = 'wishlist.validation.error.wishlist_item_not_found';

    /**
     * @var string
     */
    protected const GLOSSARY_KEY_WISHLIST_ITEM_CANNOT_BE_UPDATED = 'wishlist.validation.error.wishlist_item_cannot_be_updated';

    /**
     * @var \Spryker\Zed\Wishlist\Persistence\WishlistEntityManagerInterface
     */
    protected $wishlistEntityManager;

    /**
     * @var \Spryker\Zed\Wishlist\Persistence\WishlistRepositoryInterface
     */
    protected $wishlistRepository;

    /**
     * @var \Spryker\Zed\Wishlist\Dependency\Facade\WishlistToProductInterface
     */
    protected $productFacade;

    /**
     * @var array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\UpdateItemPreCheckPluginInterface>
     */
    protected $updateItemPreCheckPlugins;

    /**
     * @var array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistPreUpdateItemPluginInterface>
     */
    protected $wishlistPreUpdateItemPlugins;

    /**
     * @param \Spryker\Zed\Wishlist\Persistence\WishlistEntityManagerInterface $wishlistEntityManager
     * @param \Spryker\Zed\Wishlist\Persistence\WishlistRepositoryInterface $wishlistRepository
     * @param \Spryker\Zed\Wishlist\Dependency\Facade\WishlistToProductInterface $productFacade
     * @param array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\UpdateItemPreCheckPluginInterface> $updateItemPreCheckPlugins
     * @param array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistPreUpdateItemPluginInterface> $wishlistPreUpdateItemPlugins
     */
    public function __construct(
        WishlistEntityManagerInterface $wishlistEntityManager,
        WishlistRepositoryInterface $wishlistRepository,
        WishlistToProductInterface $productFacade,
        array $updateItemPreCheckPlugins,
        array $wishlistPreUpdateItemPlugins
    ) {
        $this->wishlistEntityManager = $wishlistEntityManager;
        $this->wishlistRepository = $wishlistRepository;
        $this->productFacade = $productFacade;
        $this->updateItemPreCheckPlugins = $updateItemPreCheckPlugins;
        $this->wishlistPreUpdateItemPlugins = $wishlistPreUpdateItemPlugins;
    }

    public function updateWishlistItem(WishlistItemTransfer $wishlistItemTransfer): WishlistItemResponseTransfer
    {
        if (!$wishlistItemTransfer->getIdWishlistItem() || !$this->isProductConcreteActive($wishlistItemTransfer)) {
            return $this->getErrorResponse(static::GLOSSARY_KEY_WISHLIST_ITEM_CANNOT_BE_UPDATED);
        }

        if (!$this->executeUpdateItemPreCheckPlugins($wishlistItemTransfer)) {
            return $this->getErrorResponse(static::GLOSSARY_KEY_WISHLIST_ITEM_CANNOT_BE_UPDATED);
        }

        $persistedWishlistItem = $this->wishlistRepository
            ->findWishlistItem($this->createWishlistItemCriteria($wishlistItemTransfer));

        if (!$persistedWishlistItem) {
            return $this->getErrorResponse(static::GLOSSARY_KEY_WISHLIST_ITEM_NOT_FOUND);
        }

        $persistedWishlistItem->fromArray($wishlistItemTransfer->modifiedToArray());

        $persistedWishlistItem = $this->executeWishlistPreUpdateItemPlugins($persistedWishlistItem);
        $wishlistItemTransfer = $this->wishlistEntityManager->updateWishlistItem($persistedWishlistItem);

        return (new WishlistItemResponseTransfer())
            ->setIsSuccess(true)
            ->setWishlistItem($wishlistItemTransfer);
    }

    protected function isProductConcreteActive(WishlistItemTransfer $wishlistItemTransfer): bool
    {
        $productConcreteTransfer = (new ProductConcreteTransfer())
            ->setSku($wishlistItemTransfer->getSkuOrFail());

        if (
            !$this->productFacade->hasProductConcrete($wishlistItemTransfer->getSkuOrFail())
            || !$this->productFacade->isProductConcreteActive($productConcreteTransfer)
        ) {
            return false;
        }

        return true;
    }

    protected function executeWishlistPreUpdateItemPlugins(WishlistItemTransfer $wishlistItemTransfer): WishlistItemTransfer
    {
        foreach ($this->wishlistPreUpdateItemPlugins as $wishlistPreUpdateItemPlugin) {
            $wishlistItemTransfer = $wishlistPreUpdateItemPlugin->preUpdateItem($wishlistItemTransfer);
        }

        return $wishlistItemTransfer;
    }

    protected function executeUpdateItemPreCheckPlugins(WishlistItemTransfer $wishlistItemTransfer): bool
    {
        foreach ($this->updateItemPreCheckPlugins as $updateItemPreCheckPlugin) {
            $wishlistPreUpdateItemCheckResponseTransfer = $updateItemPreCheckPlugin->check($wishlistItemTransfer);

            if (!$wishlistPreUpdateItemCheckResponseTransfer->getIsSuccess()) {
                return false;
            }
        }

        return true;
    }

    protected function createWishlistItemCriteria(WishlistItemTransfer $wishlistItemTransfer): WishlistItemCriteriaTransfer
    {
        return (new WishlistItemCriteriaTransfer())
            ->setIdWishlistItem($wishlistItemTransfer->getIdWishlistItem());
    }

    protected function getErrorResponse(string $message): WishlistItemResponseTransfer
    {
        $messageTransfer = (new MessageTransfer())
            ->setValue($message);

        return (new WishlistItemResponseTransfer())
            ->setIsSuccess(false)
            ->addMessage($messageTransfer);
    }
}
