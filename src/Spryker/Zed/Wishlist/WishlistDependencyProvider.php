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
    public const FACADE_PRODUCT = 'FACADE_PRODUCT';
    public const QUERY_CONTAINER_PRODUCT = 'QUERY_CONTAINER_PRODUCT';
    public const PLUGINS_ITEM_EXPANDER = 'PLUGINS_ITEM_EXPANDER';
    public const PLUGINS_ADD_ITEM_PRE_CHECK = 'PLUGINS_ADD_ITEM_PRE_CHECK';

    /**
     * @var string
     */
    public const PLUGINS_UPDATE_ITEM_PRE_CHECK = 'PLUGINS_UPDATE_ITEM_PRE_CHECK';

    /**
     * @var string
     */
    public const PLUGINS_WISHLIST_PRE_UPDATE_ITEM = 'PLUGINS_WISHLIST_PRE_UPDATE_ITEM';

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    public function provideBusinessLayerDependencies(Container $container)
    {
        $container[static::FACADE_PRODUCT] = function (Container $container) {
            return new FacadeWishlistToProductBridge($container->getLocator()->product()->facade());
        };

        $container[static::QUERY_CONTAINER_PRODUCT] = function (Container $container) {
            return new QueryContainerWishlistToProductBridge($container->getLocator()->product()->queryContainer());
        };

        $container[static::PLUGINS_ITEM_EXPANDER] = function (Container $container) {
            return $this->getItemExpanderPlugins();
        };

        $container = $this->addAddItemPreCheckPlugins($container);
        $container = $this->addUpdateItemPreCheckPlugins($container);
        $container = $this->addWishlistPreUpdateItemPlugins($container);

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addAddItemPreCheckPlugins(Container $container): Container
    {
        $container[static::PLUGINS_ADD_ITEM_PRE_CHECK] = function () {
            return $this->getAddItemPreCheckPlugins();
        };

        return $container;
    }

    /**
     * @return \Spryker\Zed\Wishlist\Dependency\Plugin\ItemExpanderPluginInterface[]
     */
    protected function getItemExpanderPlugins()
    {
        return [];
    }

    /**
     * @return \Spryker\Zed\WishlistExtension\Dependency\Plugin\AddItemPreCheckPluginInterface[]
     */
    protected function getAddItemPreCheckPlugins(): array
    {
        return [];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addUpdateItemPreCheckPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_UPDATE_ITEM_PRE_CHECK, function () {
            return $this->getUpdateItemPreCheckPlugins();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addWishlistPreUpdateItemPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_WISHLIST_PRE_UPDATE_ITEM, function () {
            return $this->getWishlistPreUpdateItemPlugins();
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistPreUpdateItemPluginInterface>
     */
    protected function getWishlistPreUpdateItemPlugins(): array
    {
        return [];
    }

    /**
     * @return array<\Spryker\Zed\WishlistExtension\Dependency\Plugin\UpdateItemPreCheckPluginInterface>
     */
    protected function getUpdateItemPreCheckPlugins(): array
    {
        return [];
    }
}
