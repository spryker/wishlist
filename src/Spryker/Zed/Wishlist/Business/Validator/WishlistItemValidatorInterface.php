<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist\Business\Validator;

use Generated\Shared\Transfer\WishlistItemTransfer;
use Generated\Shared\Transfer\WishlistPreAddItemCheckResponseTransfer;

interface WishlistItemValidatorInterface
{
    public function validateWishlistItemBeforeCreation(
        WishlistItemTransfer $wishlistItemTransfer
    ): WishlistPreAddItemCheckResponseTransfer;
}
