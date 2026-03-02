<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist\Persistence;

use Generated\Shared\Transfer\WishlistCollectionTransfer;
use Generated\Shared\Transfer\WishlistFilterTransfer;
use Generated\Shared\Transfer\WishlistItemCriteriaTransfer;
use Generated\Shared\Transfer\WishlistItemTransfer;
use Generated\Shared\Transfer\WishlistTransfer;

interface WishlistRepositoryInterface
{
    public function getByCustomerReference(string $customerReference): WishlistCollectionTransfer;

    public function findWishlistByFilter(WishlistFilterTransfer $wishlistFilterTransfer): ?WishlistTransfer;

    public function findWishlistItem(WishlistItemCriteriaTransfer $wishlistItemCriteriaTransfer): ?WishlistItemTransfer;
}
