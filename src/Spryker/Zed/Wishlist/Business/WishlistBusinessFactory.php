<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\Wishlist\Business\Model\Reader;
use Spryker\Zed\Wishlist\Business\Model\Writer;
use Spryker\Zed\Wishlist\Business\Transfer\WishlistTransferMapper;
use Spryker\Zed\Wishlist\WishlistDependencyProvider;

/**
 * @method \Spryker\Zed\Wishlist\Persistence\WishlistQueryContainer getQueryContainer()
 * @method \Spryker\Zed\Wishlist\WishlistConfig getConfig()
 */
class WishlistBusinessFactory extends AbstractBusinessFactory
{

    /**
     * @return \Spryker\Zed\Wishlist\Business\Model\ReaderInterface
     */
    public function createReader()
    {
        return new Reader(
            $this->getQueryContainer(),
            $this->createTransferMapper(),
            $this->getLocaleFacade()
        );
    }

    /**
     * @return \Spryker\Zed\Wishlist\Business\Model\WriterInterface
     */
    public function createWriter()
    {
        return new Writer(
            $this->getQueryContainer(),
            $this->createReader()
        );
    }

    /**
     * @return \Spryker\Zed\Wishlist\Business\Transfer\WishlistTransferMapperInterface
     */
    protected function createTransferMapper()
    {
        return new WishlistTransferMapper();
    }

    /**
     * @return \Spryker\Zed\Wishlist\Dependency\Facade\WishlistToLocaleInterface
     */
    protected function getLocaleFacade()
    {
        return $this->getProvidedDependency(WishlistDependencyProvider::FACADE_LOCALE);
    }

}
