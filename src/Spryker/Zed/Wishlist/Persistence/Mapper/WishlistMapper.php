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

class WishlistMapper implements WishlistMapperInterface
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
    ): WishlistCollectionTransfer {
        foreach ($wishlistEntities as $wishlistEntity) {
            $wishlistCollectionTransfer->addWishlist(
                $this->mapWishlistEntityToWishlistTransfer($wishlistEntity, new WishlistTransfer()),
            );
        }

        return $wishlistCollectionTransfer;
    }

    /**
     * @param \Orm\Zed\Wishlist\Persistence\SpyWishlist $wishlistEntity
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function mapWishlistEntityToWishlistTransfer(
        SpyWishlist $wishlistEntity,
        WishlistTransfer $wishlistTransfer
    ): WishlistTransfer {
        $wishlistData = $wishlistEntity->toArray();

        if (array_key_exists(WishlistTransfer::NUMBER_OF_ITEMS, $wishlistData)) {
            $wishlistData[WishlistTransfer::NUMBER_OF_ITEMS] = (int)$wishlistData[WishlistTransfer::NUMBER_OF_ITEMS];
        }

        return $wishlistTransfer->fromArray($wishlistData, false);
    }

    /**
     * @param \Orm\Zed\Wishlist\Persistence\SpyWishlist $wishlistEntity
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function mapWishlistEntityToWishlistTransferIncludingWishlistItems(
        SpyWishlist $wishlistEntity,
        WishlistTransfer $wishlistTransfer
    ): WishlistTransfer {
        $wishlistTransfer = $this->mapWishlistEntityToWishlistTransfer($wishlistEntity, $wishlistTransfer);

        foreach ($wishlistEntity->getSpyWishlistItems() as $wishlistItemEntity) {
            $wishlistItemTransfer = $this->mapWishlistItemEntityToWishlistItemTransfer(
                $wishlistItemEntity,
                new WishlistItemTransfer(),
            );

            $wishlistItemTransfer
                ->setFkCustomer($wishlistTransfer->getFkCustomer())
                ->setWishlistName($wishlistTransfer->getName());

            $wishlistTransfer->addWishlistItem($wishlistItemTransfer);
        }

        return $wishlistTransfer;
    }

    /**
     * @param \Orm\Zed\Wishlist\Persistence\SpyWishlistItem $wishlistItemEntity
     * @param \Generated\Shared\Transfer\WishlistItemTransfer $wishlistItemTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistItemTransfer
     */
    public function mapWishlistItemEntityToWishlistItemTransfer(
        SpyWishlistItem $wishlistItemEntity,
        WishlistItemTransfer $wishlistItemTransfer
    ): WishlistItemTransfer {
        return $wishlistItemTransfer
            ->fromArray($wishlistItemEntity->toArray(), true)
            ->setWishlistName($wishlistItemEntity->getSpyWishlist()->getName());
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistItemTransfer $wishlistItemTransfer
     * @param \Orm\Zed\Wishlist\Persistence\SpyWishlistItem $wishlistItemEntity
     *
     * @return \Orm\Zed\Wishlist\Persistence\SpyWishlistItem
     */
    public function mapWishlistItemTransferToWishlistItemEntity(
        WishlistItemTransfer $wishlistItemTransfer,
        SpyWishlistItem $wishlistItemEntity
    ): SpyWishlistItem {
        $wishlistItemEntity->fromArray($wishlistItemTransfer->toArray());

        return $wishlistItemEntity;
    }
}
