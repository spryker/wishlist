<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Wishlist\Dependency\Client;

class WishlistToProductBridge implements WishlistToProductInterface
{
    /**
     * @var \Spryker\Client\Product\ProductClientInterface
     */
    protected $productClient;

    /**
     * @param \Spryker\Client\Product\ProductClientInterface $productClient
     */
    public function __construct($productClient)
    {
        $this->productClient = $productClient;
    }

    /**
     * @param array $idProductConcreteCollection
     *
     * @return array<\Generated\Shared\Transfer\StorageProductTransfer>
     */
    public function getProductConcreteCollection(array $idProductConcreteCollection)
    {
        return $this->productClient->getProductConcreteCollection($idProductConcreteCollection);
    }
}
