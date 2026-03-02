<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist\Persistence;

use Generated\Shared\Transfer\WishlistItemTransfer;

interface WishlistEntityManagerInterface
{
    public function addItem(WishlistItemTransfer $wishlistItemTransfer): WishlistItemTransfer;

    public function deleteItem(WishlistItemTransfer $wishlistItemTransfer): WishlistItemTransfer;

    public function updateWishlistItem(WishlistItemTransfer $wishlistItemTransfer): WishlistItemTransfer;
}
