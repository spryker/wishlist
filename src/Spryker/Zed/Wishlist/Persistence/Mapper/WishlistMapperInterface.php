<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist\Persistence\Mapper;

use Generated\Shared\Transfer\WishlistCollectionTransfer;
use Generated\Shared\Transfer\WishlistItemTransfer;
use Generated\Shared\Transfer\WishlistTransfer;
use Orm\Zed\Wishlist\Persistence\SpyWishlist;
use Orm\Zed\Wishlist\Persistence\SpyWishlistItem;
use Propel\Runtime\Collection\ObjectCollection;

interface WishlistMapperInterface
{
    /**
     * @param \Propel\Runtime\Collection\ObjectCollection|\Orm\Zed\Wishlist\Persistence\SpyWishlist[] $wishlistEntities
     * @param \Generated\Shared\Transfer\WishlistCollectionTransfer $wishlistCollectionTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistCollectionTransfer
     */
    public function mapWishlistEntitiesToWishlistCollectionTransfer(
        ObjectCollection $wishlistEntities,
        WishlistCollectionTransfer $wishlistCollectionTransfer
    ): WishlistCollectionTransfer;

    /**
     * @param \Orm\Zed\Wishlist\Persistence\SpyWishlist $wishlistEntity
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function mapWishlistEntityToWishlistTransfer(
        SpyWishlist $wishlistEntity,
        WishlistTransfer $wishlistTransfer
    ): WishlistTransfer;

    /**
     * @param \Orm\Zed\Wishlist\Persistence\SpyWishlistItem $wishlistItemEntity
     * @param \Generated\Shared\Transfer\WishlistItemTransfer $wishlistItemTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistItemTransfer
     */
    public function mapWishlistItemEntityToWishlistItemTransfer(
        SpyWishlistItem $wishlistItemEntity,
        WishlistItemTransfer $wishlistItemTransfer
    ): WishlistItemTransfer;

    /**
     * @param \Generated\Shared\Transfer\WishlistItemTransfer $wishlistItemTransfer
     * @param \Orm\Zed\Wishlist\Persistence\SpyWishlistItem $wishlistItemEntity
     *
     * @return \Orm\Zed\Wishlist\Persistence\SpyWishlistItem
     */
    public function mapWishlistItemTransferToWishlistItemEntity(
        WishlistItemTransfer $wishlistItemTransfer,
        SpyWishlistItem $wishlistItemEntity
    ): SpyWishlistItem;
}
