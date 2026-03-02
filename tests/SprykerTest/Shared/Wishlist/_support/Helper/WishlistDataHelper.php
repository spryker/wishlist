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
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

class WishlistDataHelper extends Module
{
    use DataCleanupHelperTrait;
    use LocatorHelperTrait;

    public function haveWishlist(array $override): WishlistTransfer
    {
        $wishlistTransfer = (new WishlistBuilder($override))->build();
        $wishlistTransfer = $this->getWishlistFacade()
            ->validateAndCreateWishlist($wishlistTransfer)
            ->getWishlist();

        $this->getDataCleanupHelper()->_addCleanup(function () use ($wishlistTransfer): void {
            $this->cleanupWishlist($wishlistTransfer);
        });

        return $wishlistTransfer;
    }

    public function haveItemInWishlist(array $override): WishlistItemTransfer
    {
        $wishlistItemTransfer = (new WishlistItemBuilder($override))->build();
        $wishlistItemTransfer = $this->getWishlistFacade()->addItem($wishlistItemTransfer);

        $this->getDataCleanupHelper()->_addCleanup(function () use ($wishlistItemTransfer): void {
            $this->cleanupWishlistItem($this->getWishlistFacade()->addItem($wishlistItemTransfer));
        });

        return $wishlistItemTransfer;
    }

    protected function getWishlistFacade(): WishlistFacadeInterface
    {
        return $this->getLocator()->wishlist()->facade();
    }

    protected function cleanupWishlist(WishlistTransfer $wishlistTransfer): void
    {
        $this->getWishlistFacade()->removeWishlist($wishlistTransfer);
    }

    protected function cleanupWishlistItem(WishlistItemTransfer $wishlistItemTransfer): void
    {
        $this->getWishlistFacade()->removeItem($wishlistItemTransfer);
    }
}
