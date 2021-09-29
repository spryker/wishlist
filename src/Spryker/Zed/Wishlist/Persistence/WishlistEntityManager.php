<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist\Persistence;

use Generated\Shared\Transfer\WishlistItemTransfer;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \Spryker\Zed\Wishlist\Persistence\WishlistPersistenceFactory getFactory()
 */
class WishlistEntityManager extends AbstractEntityManager implements WishlistEntityManagerInterface
{
    /**
     * @param \Generated\Shared\Transfer\WishlistItemTransfer $wishlistItemTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistItemTransfer
     */
    public function updateWishlistItem(WishlistItemTransfer $wishlistItemTransfer): WishlistItemTransfer
    {
        $wishlistItemEntity = $this->getFactory()
            ->createWishlistItemQuery()
            ->filterByIdWishlistItem($wishlistItemTransfer->getIdWishlistItemOrFail())
            ->findOne();

        $wishlistItemEntity = $this->getFactory()
            ->createWishlistMapper()
            ->mapWishlistItemTransferToWishlistItemEntity($wishlistItemTransfer, $wishlistItemEntity);

        $wishlistItemEntity->save();

        return $wishlistItemTransfer;
    }
}
