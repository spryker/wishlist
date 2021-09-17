<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\Wishlist\Business\Deleter\WishlistDeleter;
use Spryker\Zed\Wishlist\Business\Deleter\WishlistDeleterInterface;
use Spryker\Zed\Wishlist\Business\Model\Reader;
use Spryker\Zed\Wishlist\Business\Model\Writer;
use Spryker\Zed\Wishlist\Business\Transfer\WishlistTransferMapper;
use Spryker\Zed\Wishlist\WishlistDependencyProvider;

/**
 * @method \Spryker\Zed\Wishlist\Persistence\WishlistQueryContainerInterface getQueryContainer()
 * @method \Spryker\Zed\Wishlist\WishlistConfig getConfig()
 * @method \Spryker\Zed\Wishlist\Persistence\WishlistRepositoryInterface getRepository()
 * @method \Spryker\Zed\Wishlist\Persistence\WishlistEntityManagerInterface getEntityManager()
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
            $this->getProductQueryContainer(),
            $this->createTransferMapper(),
            $this->getRepository(),
            $this->getWishlistReloadItemsPlugins(),
            $this->getWishlistItemValidatorPlugins(),
            $this->getWishlistItemExpanderPlugins()
        );
    }

    /**
     * @return \Spryker\Zed\Wishlist\Business\Model\WriterInterface
     */
    public function createWriter()
    {
        return new Writer(
            $this->getQueryContainer(),
            $this->createReader(),
            $this->getEntityManager(),
            $this->getProductFacade(),
            $this->getAddItemPreCheckPlugins(),
            $this->getWishlistPreAddItemPlugins()
        );
    }

    /**
     * @return \Spryker\Zed\Wishlist\Business\Transfer\WishlistTransferMapperInterface
     */
    protected function createTransferMapper()
    {
        return new WishlistTransferMapper(
            $this->getItemExpanderPlugins()
        );
    }

    /**
     * @return \Spryker\Zed\Wishlist\Business\Deleter\WishlistDeleterInterface
     */
    public function createDeleter(): WishlistDeleterInterface
    {
        return new WishlistDeleter(
            $this->getEntityManager()
        );
    }

    /**
     * @return \Spryker\Zed\Wishlist\Dependency\QueryContainer\WishlistToProductBridge
     */
    protected function getProductQueryContainer()
    {
        return $this->getProvidedDependency(WishlistDependencyProvider::QUERY_CONTAINER_PRODUCT);
    }

    /**
     * @return array<\Spryker\Zed\Wishlist\Dependency\Plugin\ItemExpanderPluginInterface>
     */
    protected function getItemExpanderPlugins()
    {
        return $this->getProvidedDependency(WishlistDependencyProvider::PLUGINS_ITEM_EXPANDER);
    }

    /**
     * @return \Spryker\Zed\Wishlist\Dependency\Facade\WishlistToProductInterface
     */
    protected function getProductFacade()
    {
        return $this->getProvidedDependency(WishlistDependencyProvider::FACADE_PRODUCT);
    }

    /**
     * @return array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\AddItemPreCheckPluginInterface>
     */
    public function getAddItemPreCheckPlugins(): array
    {
        return $this->getProvidedDependency(WishlistDependencyProvider::PLUGINS_ADD_ITEM_PRE_CHECK);
    }

    /**
     * @return array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistReloadItemsPluginInterface>
     */
    public function getWishlistReloadItemsPlugins(): array
    {
        return $this->getProvidedDependency(WishlistDependencyProvider::PLUGINS_WISHLIST_RELOAD_ITEMS);
    }

    /**
     * @return array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistItemsValidatorPluginInterface>
     */
    public function getWishlistItemValidatorPlugins()
    {
        return $this->getProvidedDependency(WishlistDependencyProvider::PLUGINS_WISHLIST_ITEMS_VALIDATOR);
    }

    /**
     * @return array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistPreAddItemPluginInterface>
     */
    public function getWishlistPreAddItemPlugins()
    {
        return $this->getProvidedDependency(WishlistDependencyProvider::PLUGINS_WISHLIST_PRE_ADD_ITEM);
    }

    /**
     * @return array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistItemExpanderPluginInterface>
     */
    public function getWishlistItemExpanderPlugins(): array
    {
        return $this->getProvidedDependency(WishlistDependencyProvider::PLUGINS_WISHLIST_ITEM_EXPANDER);
    }
}
