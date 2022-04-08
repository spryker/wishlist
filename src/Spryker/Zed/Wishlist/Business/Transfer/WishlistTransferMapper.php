<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist\Business\Transfer;

use ArrayObject;
use Generated\Shared\Transfer\WishlistItemMetaTransfer;
use Generated\Shared\Transfer\WishlistItemTransfer;
use Generated\Shared\Transfer\WishlistTransfer;
use Orm\Zed\Product\Persistence\SpyProduct;
use Orm\Zed\Wishlist\Persistence\SpyWishlist;
use Orm\Zed\Wishlist\Persistence\SpyWishlistItem;
use Propel\Runtime\Collection\ObjectCollection;

class WishlistTransferMapper implements WishlistTransferMapperInterface
{
    /**
     * @var array<\Spryker\Zed\Wishlist\Dependency\Plugin\ItemExpanderPluginInterface>
     */
    protected $itemExpanderPlugins;

    /**
     * @param array<\Spryker\Zed\Wishlist\Dependency\Plugin\ItemExpanderPluginInterface> $itemExpanderPlugins
     */
    public function __construct(array $itemExpanderPlugins)
    {
        $this->itemExpanderPlugins = $itemExpanderPlugins;
    }

    /**
     * @param \Orm\Zed\Wishlist\Persistence\SpyWishlist $wishlistEntity
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function convertWishlist(SpyWishlist $wishlistEntity)
    {
        $wishlistTransfer = (new WishlistTransfer())
            ->fromArray($wishlistEntity->toArray(), true)
            ->setNumberOfItems($wishlistEntity->getSpyWishlistItems()->count());

        return $wishlistTransfer;
    }

    /**
     * @param \Propel\Runtime\Collection\ObjectCollection<\Orm\Zed\Wishlist\Persistence\SpyWishlist> $wishlistEntityCollection
     *
     * @return array<\Generated\Shared\Transfer\WishlistTransfer>
     */
    public function convertWishlistCollection(ObjectCollection $wishlistEntityCollection)
    {
        $transferCollection = [];
        foreach ($wishlistEntityCollection as $wishlistEntity) {
            $transferCollection[] = $this->convertWishlist($wishlistEntity);
        }

        return $transferCollection;
    }

    /**
     * @param \Orm\Zed\Wishlist\Persistence\SpyWishlistItem $wishlistItemEntity
     *
     * @return \Generated\Shared\Transfer\WishlistItemTransfer
     */
    public function convertWishlistItem(SpyWishlistItem $wishlistItemEntity)
    {
        $wishlistItemTransfer = (new WishlistItemTransfer())
            ->fromArray($wishlistItemEntity->toArray(), true);

        foreach ($this->itemExpanderPlugins as $plugin) {
            $wishlistItemTransfer = $plugin->expandItem($wishlistItemTransfer);
        }

        return $wishlistItemTransfer;
    }

    /**
     * @param \Propel\Runtime\Collection\ObjectCollection<\Orm\Zed\Wishlist\Persistence\SpyWishlistItem> $wishlistItemEntityCollection
     *
     * @return array<\Generated\Shared\Transfer\WishlistItemTransfer>
     */
    public function convertWishlistItemCollection(ObjectCollection $wishlistItemEntityCollection)
    {
        $transferCollection = [];
        foreach ($wishlistItemEntityCollection as $wishlistEntity) {
            $transferCollection[] = $this->convertWishlistItem($wishlistEntity);
        }

        return $transferCollection;
    }

    /**
     * @param \Orm\Zed\Product\Persistence\SpyProduct $productEntity
     * @param \Generated\Shared\Transfer\WishlistItemMetaTransfer $wishlistItemMetaTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistItemMetaTransfer
     */
    public function mapProductEntityToWishlistItemMetaTransfer(
        SpyProduct $productEntity,
        WishlistItemMetaTransfer $wishlistItemMetaTransfer
    ): WishlistItemMetaTransfer {
        return $wishlistItemMetaTransfer->fromArray($productEntity->toArray(), true);
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\WishlistItemTransfer> $wishlistItemTransfers
     * @param \ArrayObject<int, \Generated\Shared\Transfer\WishlistItemMetaTransfer> $wishlistItemMetaTransfers
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\WishlistItemMetaTransfer>
     */
    public function mapWishlistItemTransfersToWishlistItemMetaTransfers(
        ArrayObject $wishlistItemTransfers,
        ArrayObject $wishlistItemMetaTransfers
    ): ArrayObject {
        foreach ($wishlistItemMetaTransfers as $wishlistItemMetaTransfer) {
            foreach ($wishlistItemTransfers as $wishlistItemTransfer) {
                if ($wishlistItemTransfer->getIdWishlistItem() !== $wishlistItemMetaTransfer->getIdWishlistItem()) {
                    continue;
                }

                $wishlistItemMetaTransfer->fromArray($wishlistItemTransfer->toArray(), true);
            }
        }

        return $wishlistItemMetaTransfers;
    }
}
