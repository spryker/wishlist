<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\Wishlist\Dependency\Facade\WishlistToProductBridge as FacadeWishlistToProductBridge;
use Spryker\Zed\Wishlist\Dependency\QueryContainer\WishlistToProductBridge as QueryContainerWishlistToProductBridge;

/**
 * @method \Spryker\Zed\Wishlist\WishlistConfig getConfig()
 */
class WishlistDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const FACADE_PRODUCT = 'FACADE_PRODUCT';

    /**
     * @var string
     */
    public const QUERY_CONTAINER_PRODUCT = 'QUERY_CONTAINER_PRODUCT';

    /**
     * @var string
     */
    public const PLUGINS_ITEM_EXPANDER = 'PLUGINS_ITEM_EXPANDER';

    /**
     * @var string
     */
    public const PLUGINS_ADD_ITEM_PRE_CHECK = 'PLUGINS_ADD_ITEM_PRE_CHECK';

    /**
     * @var string
     */
    public const PLUGINS_WISHLIST_RELOAD_ITEMS = 'PLUGINS_RELOAD_ITEMS';

    /**
     * @var string
     */
    public const PLUGINS_WISHLIST_ITEMS_VALIDATOR = 'PLUGINS_WISHLIST_ITEMS_VALIDATOR';

    /**
     * @var string
     */
    public const PLUGINS_WISHLIST_PRE_ADD_ITEM = 'PLUGINS_WISHLIST_PRE_ADD_ITEM';

    /**
     * @var string
     */
    public const PLUGINS_WISHLIST_ITEM_EXPANDER = 'PLUGINS_WISHLIST_ITEM_EXPANDER';

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    public function provideBusinessLayerDependencies(Container $container)
    {
        $container = $this->addProductFacade($container);
        $container = $this->addProductQueryContainer($container);
        $container = $this->addItemExpanderPlugins($container);
        $container = $this->addAddItemPreCheckPlugins($container);
        $container = $this->addWishlistReloadItemPlugins($container);
        $container = $this->addWishlistItemsValidatorPlugins($container);
        $container = $this->addWishlistPreAddItemPlugins($container);
        $container = $this->addWishlistItemExpanderPlugins($container);

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addProductFacade(Container $container): Container
    {
        $container->set(static::FACADE_PRODUCT, function (Container $container) {
            return new FacadeWishlistToProductBridge($container->getLocator()->product()->facade());
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addProductQueryContainer(Container $container): Container
    {
        $container->set(static::QUERY_CONTAINER_PRODUCT, function (Container $container) {
            return new QueryContainerWishlistToProductBridge($container->getLocator()->product()->queryContainer());
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addItemExpanderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_ITEM_EXPANDER, function () {
            return $this->getItemExpanderPlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addAddItemPreCheckPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_ADD_ITEM_PRE_CHECK, function () {
            return $this->getAddItemPreCheckPlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addWishlistReloadItemPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_WISHLIST_RELOAD_ITEMS, function () {
            return $this->getWishlistReloadItemsPlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addWishlistItemsValidatorPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_WISHLIST_ITEMS_VALIDATOR, function () {
            return $this->getWishlistItemsValidatorPlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addWishlistPreAddItemPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_WISHLIST_PRE_ADD_ITEM, function () {
            return $this->getWishlistPreAddItemPlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addWishlistItemExpanderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_WISHLIST_ITEM_EXPANDER, function () {
            return $this->getWishlistItemExpanderPlugins();
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Zed\Wishlist\Dependency\Plugin\ItemExpanderPluginInterface>
     */
    protected function getItemExpanderPlugins()
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\AddItemPreCheckPluginInterface>
     */
    protected function getAddItemPreCheckPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistReloadItemsPluginInterface>
     */
    protected function getWishlistReloadItemsPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistItemsValidatorPluginInterface>
     */
    protected function getWishlistItemsValidatorPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistPreAddItemPluginInterface>
     */
    protected function getWishlistPreAddItemPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistItemExpanderPluginInterface>
     */
    protected function getWishlistItemExpanderPlugins(): array
    {
        return [];
    }
}
