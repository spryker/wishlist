<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist\Business\Writer;

use Generated\Shared\Transfer\MessageTransfer;
use Generated\Shared\Transfer\WishlistItemCriteriaTransfer;
use Generated\Shared\Transfer\WishlistItemResponseTransfer;
use Generated\Shared\Transfer\WishlistItemTransfer;
use Spryker\Zed\Wishlist\Persistence\WishlistEntityManagerInterface;
use Spryker\Zed\Wishlist\Persistence\WishlistRepositoryInterface;

class WishlistItemWriter implements WishlistItemWriterInterface
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
     * @param \Spryker\Zed\Wishlist\Persistence\WishlistEntityManagerInterface $wishlistEntityManager
     * @param \Spryker\Zed\Wishlist\Persistence\WishlistRepositoryInterface $wishlistRepository
     */
    public function __construct(
        WishlistEntityManagerInterface $wishlistEntityManager,
        WishlistRepositoryInterface $wishlistRepository
    ) {
        $this->wishlistEntityManager = $wishlistEntityManager;
        $this->wishlistRepository = $wishlistRepository;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistItemTransfer $wishlistItemTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistItemResponseTransfer
     */
    public function updateWishlistItem(WishlistItemTransfer $wishlistItemTransfer): WishlistItemResponseTransfer
    {
        if (!$wishlistItemTransfer->getIdWishlistItem()) {
            return $this->getErrorResponse(static::GLOSSARY_KEY_WISHLIST_ITEM_CANNOT_BE_UPDATED);
        }

        $persistedWishlistItem = $this->wishlistRepository
            ->findWishlistItem($this->createWishlistItemCriteria($wishlistItemTransfer));

        if (!$persistedWishlistItem) {
            return $this->getErrorResponse(static::GLOSSARY_KEY_WISHLIST_ITEM_NOT_FOUND);
        }

        $persistedWishlistItem->fromArray($wishlistItemTransfer->modifiedToArray());
        $wishlistItemTransfer = $this->wishlistEntityManager->updateWishlistItem($persistedWishlistItem);

        return (new WishlistItemResponseTransfer())
            ->setIsSuccess(true)
            ->setWishlistItem($wishlistItemTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistItemTransfer $wishlistItemTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistItemCriteriaTransfer
     */
    protected function createWishlistItemCriteria(WishlistItemTransfer $wishlistItemTransfer): WishlistItemCriteriaTransfer
    {
        return (new WishlistItemCriteriaTransfer())
            ->setIdWishlistItem($wishlistItemTransfer->getIdWishlistItem());
    }

    /**
     * @param string $message
     *
     * @return \Generated\Shared\Transfer\WishlistItemResponseTransfer
     */
    protected function getErrorResponse(string $message): WishlistItemResponseTransfer
    {
        $messageTransfer = (new MessageTransfer())
            ->setValue($message);

        return (new WishlistItemResponseTransfer())
            ->setIsSuccess(false)
            ->addMessage($messageTransfer);
    }
}
