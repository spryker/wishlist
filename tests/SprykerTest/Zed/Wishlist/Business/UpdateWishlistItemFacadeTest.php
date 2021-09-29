<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Wishlist\Business;

use Codeception\TestCase\Test;
use Generated\Shared\Transfer\WishlistItemTransfer;
use Generated\Shared\Transfer\WishlistTransfer;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Wishlist
 * @group Business
 * @group Facade
 * @group UpdateWishlistItemFacadeTest
 * Add your own group annotations below this line
 */
class UpdateWishlistItemFacadeTest extends Test
{
    /**
     * @var string
     */
    protected const FAKE_PRODUCT_CONFIGURATION = 'FAKE_PRODUCT_CONFIGURATION';

    /**
     * @var int
     */
    protected const FAKE_ID_WISHLIST_ITEN = 88888;

    /**
     * @see \Spryker\Zed\Wishlist\Business\Writer\WishlistItemWriter::GLOSSARY_KEY_WISHLIST_ITEM_NOT_FOUND
     *
     * @var string
     */
    protected const GLOSSARY_KEY_WISHLIST_ITEM_NOT_FOUND = 'wishlist.validation.error.wishlist_item_not_found';

    /**
     * @see \Spryker\Zed\Wishlist\Business\Writer\WishlistItemWriter::GLOSSARY_KEY_WISHLIST_ITEM_CANNOT_BE_UPDATED
     *
     * @var string
     */
    protected const GLOSSARY_KEY_WISHLIST_ITEM_CANNOT_BE_UPDATED = 'wishlist.validation.error.wishlist_item_cannot_be_updated';

    /**
     * @var \SprykerTest\Zed\Wishlist\WishlistBusinessTester
     */
    protected $tester;

    /**
     * @var \Generated\Shared\Transfer\CustomerTransfer
     */
    protected $customerTransfer;

    /**
     * @var \Generated\Shared\Transfer\WishlistTransfer
     */
    protected $wishlistTransfer;

    /**
     * @var \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    protected $productConcreteTransfer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->customerTransfer = $this->tester->haveCustomer();
        $this->productConcreteTransfer = $this->tester->haveProduct();

        $this->wishlistTransfer = $this->tester->haveWishlist([
            WishlistTransfer::FK_CUSTOMER => $this->customerTransfer->getIdCustomer(),
        ]);
    }

    /**
     * @return void
     */
    public function testUpdateWishlistItemEnsureThatWishlistItemPropertyCanBeUpdated(): void
    {
        // Arrange
        $wishlistItemTransfer = $this->createDefaultWishlistItem();

        $wishlistItemTransfer = (new WishlistItemTransfer())
            ->setIdWishlistItem($wishlistItemTransfer->getIdWishlistItem())
            ->setProductConfigurationInstanceData(static::FAKE_PRODUCT_CONFIGURATION);

        // Act
        $wishlistItemResponseTransfer = $this->tester
            ->getFacade()
            ->updateWishlistItem($wishlistItemTransfer);

        // Assert
        $this->assertTrue($wishlistItemResponseTransfer->getIsSuccess());
        $this->assertSame(
            static::FAKE_PRODUCT_CONFIGURATION,
            $this->tester->getWishlistItemFromPersistence($wishlistItemTransfer->getIdWishlistItem())->getProductConfigurationInstanceData(),
            'Wishlist item property was not updated in database storage.'
        );
    }

    /**
     * @return void
     */
    public function testUpdateWishlistItemByUuidEnsureThatWishlistItemPropertyWasUpdated(): void
    {
        // Arrange
        $wishlistItemTransfer = $this->createDefaultWishlistItem();

        $wishlistItemTransfer = (new WishlistItemTransfer())
            ->setIdWishlistItem($wishlistItemTransfer->getIdWishlistItem())
            ->setProductConfigurationInstanceData(static::FAKE_PRODUCT_CONFIGURATION);

        // Act
        $wishlistItemResponseTransfer = $this->tester
            ->getFacade()
            ->updateWishlistItem($wishlistItemTransfer);

        // Assert
        $this->assertSame(
            static::FAKE_PRODUCT_CONFIGURATION,
            $wishlistItemResponseTransfer->getWishlistItemOrFail()->getProductConfigurationInstanceData(),
            'Wishlist item property was not updated in result transfer.'
        );
    }

    /**
     * @return void
     */
    public function testUpdateWishlistItemWithFakeWishlistItemId(): void
    {
        // Arrange
        $wishlistItemTransfer = (new WishlistItemTransfer())
            ->setIdWishlistItem(static::FAKE_ID_WISHLIST_ITEN);

        // Act
        $wishlistItemResponseTransfer = $this->tester
            ->getFacade()
            ->updateWishlistItem($wishlistItemTransfer);

        // Assert
        $this->assertFalse($wishlistItemResponseTransfer->getIsSuccess());
        $this->assertSame(
            static::GLOSSARY_KEY_WISHLIST_ITEM_NOT_FOUND,
            $wishlistItemResponseTransfer->getMessages()->offsetGet(0)->getValue()
        );
    }

    /**
     * @return void
     */
    public function testUpdateWishlistItemWithoutExpectedProperties(): void
    {
        // Arrange
        $wishlistItemTransfer = (new WishlistItemTransfer())
            ->setIdWishlistItem(null);

        // Act
        $wishlistItemResponseTransfer = $this->tester
            ->getFacade()
            ->updateWishlistItem($wishlistItemTransfer);

        // Assert
        $this->assertFalse($wishlistItemResponseTransfer->getIsSuccess());
        $this->assertSame(
            static::GLOSSARY_KEY_WISHLIST_ITEM_CANNOT_BE_UPDATED,
            $wishlistItemResponseTransfer->getMessages()->offsetGet(0)->getValue()
        );
    }

    /**
     * @return \Generated\Shared\Transfer\WishlistItemTransfer
     */
    protected function createDefaultWishlistItem(): WishlistItemTransfer
    {
        return $this->tester->haveItemInWishlist([
            WishlistItemTransfer::FK_WISHLIST => $this->wishlistTransfer->getIdWishlist(),
            WishlistItemTransfer::SKU => $this->productConcreteTransfer->getSku(),
            WishlistItemTransfer::FK_CUSTOMER => $this->customerTransfer->getIdCustomer(),
            WishlistItemTransfer::PRODUCT_CONFIGURATION_INSTANCE_DATA => '',
        ]);
    }
}
