<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Wishlist\Dependency\Client;

use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface WishlistToCartInterface
{
    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param array<mixed> $params
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function addItem(ItemTransfer $itemTransfer, array $params = []);

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     * @param array<mixed> $params
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function addItems(array $itemTransfers, array $params = []);

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     * @param array<mixed> $params
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function addValidItems(CartChangeTransfer $cartChangeTransfer, array $params = []): QuoteTransfer;

    /**
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function getQuote();
}
