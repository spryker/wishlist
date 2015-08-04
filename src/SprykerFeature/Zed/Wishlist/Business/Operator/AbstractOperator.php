<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace SprykerFeature\Zed\Wishlist\Business\Operator;

use Generated\Shared\Wishlist\WishlistChangeInterface;
use Generated\Shared\Wishlist\WishlistInterface;
use SprykerFeature\Zed\Wishlist\Business\Storage\StorageInterface;
use Bundles\Wishlist\src\SprykerFeature\Zed\Wishlist\Dependency\PostSavePluginInterface;
use Bundles\Wishlist\src\SprykerFeature\Zed\Wishlist\Dependency\PreSavePluginInterface;

abstract class AbstractOperator
{
    /**
     * @var PreSavePluginInterface[]
     */
    protected $preSavePlugins = [];

    /**
     * @var PostSavePluginInterface[]
     */
    protected $postSavePlugins = [];

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var WishlistChangeInterface
     */
    private $wishlistChange;

    /**
     * @param StorageInterface        $storage
     * @param WishlistChangeInterface $wishlistChange
     */
    public function __construct(StorageInterface $storage, WishlistChangeInterface $wishlistChange)
    {
        $this->storage = $storage;
        $this->wishlistChange = $wishlistChange;
    }

    /**
     * @return WishlistInterface
     */
    public function executeOperation()
    {
        $this->preSave($this->wishlistChange);
        $wishlist = $this->applyOperation($this->wishlistChange);
        $this->postSave($wishlist);

        return $wishlist;
    }

    /**
     * @param WishlistChangeInterface $wishlistChange
     */
    protected function preSave(WishlistChangeInterface $wishlistChange)
    {
        foreach ($this->preSavePlugins as $plugin) {
            $plugin->trigger($wishlistChange);
        }
    }

    /**
     * @param WishlistInterface $wishlist
     */
    protected function postSave(WishlistInterface $wishlist)
    {
        foreach ($this->postSavePlugins as $plugin) {
            $plugin->trigger($wishlist);
        }
    }

    /**
     * @param WishlistChangeInterface $wishlistItem
     *
     */
    abstract protected function applyOperation(WishlistChangeInterface $wishlistItem);


}
