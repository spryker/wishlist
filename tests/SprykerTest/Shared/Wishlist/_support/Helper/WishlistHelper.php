<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Shared\Wishlist\Helper;

use Codeception\Module;
use Generated\Shared\DataBuilder\WishlistBuilder;
use Generated\Shared\DataBuilder\WishlistItemBuilder;
use Generated\Shared\Transfer\WishlistItemTransfer;
use Generated\Shared\Transfer\WishlistTransfer;
use Spryker\Zed\Wishlist\Business\WishlistFacadeInterface;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

class WishlistHelper extends Module
{
    use LocatorHelperTrait;

    public function haveWishlist(array $override): WishlistTransfer
    {
        $wishlistTransfer = (new WishlistBuilder($override))->build();

        return $this->getWishlistFacade()->validateAndCreateWishlist($wishlistTransfer)->getWishlist();
    }

    public function haveItemInWishlist(array $override): WishlistItemTransfer
    {
        $wishlistItemTransfer = (new WishlistItemBuilder($override))
            ->build();

        return $this->getWishlistFacade()->addItem($wishlistItemTransfer);
    }

    public function getWishlistFacade(): WishlistFacadeInterface
    {
        return $this->getLocatorHelper()->getLocator()->wishlist()->facade();
    }
}
