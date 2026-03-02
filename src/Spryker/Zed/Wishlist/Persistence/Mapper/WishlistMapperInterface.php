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
use Propel\Runtime\Collection\Collection;

interface WishlistMapperInterface
{
    /**
     * @param \Propel\Runtime\Collection\Collection<\Orm\Zed\Wishlist\Persistence\SpyWishlist> $wishlistEntities
     * @param \Generated\Shared\Transfer\WishlistCollectionTransfer $wishlistCollectionTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistCollectionTransfer
     */
    public function mapWishlistEntitiesToWishlistCollectionTransfer(
        Collection $wishlistEntities,
        WishlistCollectionTransfer $wishlistCollectionTransfer
    ): WishlistCollectionTransfer;

    public function mapWishlistEntityToWishlistTransfer(
        SpyWishlist $wishlistEntity,
        WishlistTransfer $wishlistTransfer
    ): WishlistTransfer;

    public function mapWishlistEntityToWishlistTransferIncludingWishlistItems(
        SpyWishlist $wishlistEntity,
        WishlistTransfer $wishlistTransfer
    ): WishlistTransfer;

    public function mapWishlistItemEntityToWishlistItemTransfer(
        SpyWishlistItem $wishlistItemEntity,
        WishlistItemTransfer $wishlistItemTransfer
    ): WishlistItemTransfer;

    public function mapWishlistItemTransferToWishlistItemEntity(
        WishlistItemTransfer $wishlistItemTransfer,
        SpyWishlistItem $wishlistItemEntity
    ): SpyWishlistItem;
}
