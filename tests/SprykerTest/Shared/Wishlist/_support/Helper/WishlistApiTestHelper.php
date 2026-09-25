<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerTest\Shared\Wishlist\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use Generated\Shared\DataBuilder\WishlistBuilder;
use Generated\Shared\DataBuilder\WishlistItemBuilder;
use Generated\Shared\Transfer\WishlistCollectionTransfer;
use Generated\Shared\Transfer\WishlistItemResponseTransfer;
use Generated\Shared\Transfer\WishlistItemTransfer;
use Generated\Shared\Transfer\WishlistResponseTransfer;
use Generated\Shared\Transfer\WishlistTransfer;
use PHPUnit\Framework\Assert;
use Ramsey\Uuid\Uuid;
use RuntimeException;

/**
 * Boot-free wishlist test-support for the API test lanes: builds the transfers a stubbed
 * `WishlistClient` returns, and asserts an API resource against the wishlists it built.
 *
 * Unlike {@see WishlistDataHelper} nothing here touches the database or the locator, so it runs
 * host-lane. Tests therefore carry no wishlist fixtures of their own — build through this helper
 * and let it assert, so no test repeats a uuid, a name or an item count.
 *
 * Enable it through the actor in a suite's codeception.yml:
 * ```yaml
 * modules:
 *     enabled:
 *         - \SprykerTest\Shared\Wishlist\Helper\WishlistApiTestHelper
 * ```
 */
class WishlistApiTestHelper extends Module
{
    /**
     * Raw validation messages {@see \Spryker\Zed\Wishlist\Business\Model\Writer} returns on
     * `WishlistResponseTransfer::getErrors()` — contract strings, not `ERROR_IDENTIFIER_*`
     * constants. Glue maps already-exists and wrong-format both to a 400. Kept here so the raw
     * literals live in exactly one place across the API test lanes.
     */
    protected const string ERROR_MESSAGE_NAME_ALREADY_EXISTS = 'wishlist.validation.error.name.already_exists';

    protected const string ERROR_MESSAGE_NAME_WRONG_FORMAT = 'wishlist.validation.error.name.wrong_format';

    protected const string RESOURCE_FIELD_UUID = 'uuid';

    protected const string RESOURCE_FIELD_NAME = 'name';

    protected const string RESOURCE_FIELD_NUMBER_OF_ITEMS = 'numberOfItems';

    protected const string RESOURCE_FIELD_SKU = 'sku';

    /**
     * A generated wishlist always holds at least one item (the databuilder seeds `numberOfItems`
     * with `numberBetween(1, 10)`), so {@see haveEmptyWishlistTransfer()} stays the only way a test
     * gets an empty one.
     */
    protected const int NUMBER_OF_ITEMS_EMPTY = 0;

    /**
     * Wishlist names the API suites pin. A test that needs a name it does not assert on should use
     * `WISHLIST_NAME_ANY`; the rest are named for the case they belong to.
     */
    public const string WISHLIST_NAME_EXISTING = 'MyExistingList';

    public const string WISHLIST_NAME_EXISTING_LIST = 'ExistingList';

    public const string WISHLIST_NAME_ITEMS_LIST = 'ItemsList';

    public const string WISHLIST_NAME_LIST_WITH_ITEMS = 'ListWithItems';

    public const string WISHLIST_NAME_FIRST = 'FirstList';

    public const string WISHLIST_NAME_SECOND = 'SecondList';

    public const string WISHLIST_NAME_CREATED = 'New List';

    public const string WISHLIST_NAME_RENAMED = 'Renamed';

    public const string WISHLIST_NAME_TAKEN = 'TakenName';

    public const string WISHLIST_NAME_OTHER = 'OtherName';

    public const string WISHLIST_NAME_ANY = 'Whatever';

    /**
     * Rejected by the name format validator.
     */
    public const string WISHLIST_NAME_INVALID = 'Bad@Name!';

    public const string WISHLIST_NAME_BLANK = '';

    public const int WISHLIST_NAME_MAX_LENGTH = 255;

    public const int WISHLIST_COLLECTION_COUNT = 2;

    public const string ITEM_SKU = 'SKU-1';

    public const string ITEM_SKU_BLANK = '';

    public const int ITEM_QUANTITY_AVAILABLE = 5;

    public const int ITEM_QUANTITY_UPDATED = 3;

    /**
     * Ids no fixture ever creates, for the 404 cases. The item one is a sku rather than a uuid
     * because wishlist items are addressed by sku.
     */
    public const string UNKNOWN_WISHLIST_UUID = '00000000-0000-0000-0000-000000000000';

    public const string UNKNOWN_ITEM_UUID = 'UNKNOWN-ITEM-SKU';

    /**
     * Raw request bodies for the parsing branches of a processor.
     */
    public const string BODY_EMPTY_JSON_OBJECT = '{}';

    public const string BODY_NON_JSON = 'not-json';

    /**
     * JSON:API attribute and product-configuration keys the responses are asserted against — the
     * response contract, so they name what is under assertion rather than fixture data.
     */
    public const string ATTRIBUTE_QUANTITY = 'quantity';

    public const string ATTRIBUTE_PRODUCT_CONFIGURATION_INSTANCE = 'productConfigurationInstance';

    public const string CONFIG_KEY_CONFIGURATION = 'configuration';

    public const string CONFIG_KEY_CONFIGURATOR_KEY = 'configuratorKey';

    public const string CONFIG_KEY_DISPLAY_DATA = 'displayData';

    public const string CONFIG_KEY_IS_COMPLETE = 'isComplete';

    public const string CONFIG_KEY_AVAILABLE_QUANTITY = 'availableQuantity';

    public const string CONFIGURATOR_KEY = 'CONCRETE_PRODUCT_TYPE';

    /**
     * The wishlists built in the running test method, in build order, so the `assert*` methods can
     * check a resource against what this helper created.
     *
     * @var array<int, \Generated\Shared\Transfer\WishlistTransfer>
     */
    protected array $wishlistTransfers = [];

    /**
     * The wishlist items built in the running test method, in build order, so the `assert*` methods
     * can check a resource against what this helper created.
     *
     * @var array<int, \Generated\Shared\Transfer\WishlistItemTransfer>
     */
    protected array $wishlistItemTransfers = [];

    public function _before(TestInterface $test): void
    {
        $this->wishlistTransfers = [];
        $this->wishlistItemTransfers = [];
    }

    /**
     * DataBuilder-backed wishlist transfer; seed only the fields a test pins, the rest (including
     * `uuid` and `numberOfItems`) are randomised by the databuilder. The built transfer is
     * remembered for {@see assertResourceMatchesWishlist()}.
     *
     * @param array<string, mixed> $seed
     */
    public function haveWishlistTransfer(array $seed = []): WishlistTransfer
    {
        // uuid is a WishlistsRestApi-owned property, so it cannot carry a Wishlist databuilder rule;
        // seed a default here for the API lanes that address a wishlist by its uuid.
        $seed += [WishlistTransfer::UUID => Uuid::uuid4()->toString()];

        $wishlistTransfer = (new WishlistBuilder($seed))->build();
        $this->wishlistTransfers[] = $wishlistTransfer;

        return $wishlistTransfer;
    }

    /**
     * A wishlist the customer created but never added an item to.
     */
    public function haveEmptyWishlistTransfer(): WishlistTransfer
    {
        return $this->haveWishlistTransfer([WishlistTransfer::NUMBER_OF_ITEMS => static::NUMBER_OF_ITEMS_EMPTY]);
    }

    /**
     * A wishlist that already holds one item, so a mapping seam runs the wishlist-items
     * relationship loop that an empty wishlist skips.
     */
    public function haveWishlistTransferWithItem(): WishlistTransfer
    {
        $wishlistTransfer = $this->haveWishlistTransfer();
        $wishlistTransfer->addWishlistItem($this->haveWishlistItemTransfer());

        return $wishlistTransfer;
    }

    /**
     * The collection a stubbed `getWishlistCollection()` returns. Defaults to every wishlist built
     * so far, so a collection test builds its wishlists and asserts without repeating them.
     */
    public function haveWishlistCollectionTransfer(WishlistTransfer ...$wishlistTransfers): WishlistCollectionTransfer
    {
        $wishlistCollectionTransfer = new WishlistCollectionTransfer();

        foreach ($wishlistTransfers ?: $this->wishlistTransfers as $wishlistTransfer) {
            $wishlistCollectionTransfer->addWishlist($wishlistTransfer);
        }

        return $wishlistCollectionTransfer;
    }

    /**
     * The response a stubbed client returns for a wishlist that was created, updated or deleted.
     * Defaults to a freshly built wishlist.
     */
    public function haveSuccessfulWishlistResponseTransfer(?WishlistTransfer $wishlistTransfer = null): WishlistResponseTransfer
    {
        return (new WishlistResponseTransfer())
            ->setIsSuccess(true)
            ->setWishlist($wishlistTransfer ?? $this->haveWishlistTransfer());
    }

    /**
     * The response a stubbed client returns when the wishlist operation failed. Pass the module's
     * `ERROR_IDENTIFIER_*` when the consumer maps identifiers to a status; omit it for consumers
     * that only read `getIsSuccess()`.
     */
    public function haveFailedWishlistResponseTransfer(?string $errorIdentifier = null): WishlistResponseTransfer
    {
        return (new WishlistResponseTransfer())
            ->setIsSuccess(false)
            ->setErrorIdentifier($errorIdentifier);
    }

    /**
     * A failed response carrying a raw validation message on `getErrors()` — the shape the Zed
     * writer returns for name validation. Pass one of the module's raw error strings; the
     * dedicated builders below cover the known ones.
     */
    public function haveWishlistResponseWithRawError(string $rawError): WishlistResponseTransfer
    {
        return (new WishlistResponseTransfer())
            ->setIsSuccess(false)
            ->setErrors([$rawError]);
    }

    /**
     * The response the Zed writer returns when the customer already owns a wishlist with the
     * requested name — a raw validation message rather than an error identifier.
     */
    public function haveDuplicateNameWishlistResponseTransfer(): WishlistResponseTransfer
    {
        return $this->haveWishlistResponseWithRawError(static::ERROR_MESSAGE_NAME_ALREADY_EXISTS);
    }

    /**
     * The response the Zed writer returns when the requested wishlist name is malformed — a raw
     * validation message rather than an error identifier.
     */
    public function haveWrongFormatNameWishlistResponseTransfer(): WishlistResponseTransfer
    {
        return $this->haveWishlistResponseWithRawError(static::ERROR_MESSAGE_NAME_WRONG_FORMAT);
    }

    /**
     * The defensive response where the client reports success but carries no wishlist — the
     * processor must raise an unknown-error rather than map a null wishlist.
     */
    public function haveSuccessNoWishlistResponseTransfer(): WishlistResponseTransfer
    {
        return (new WishlistResponseTransfer())->setIsSuccess(true);
    }

    /**
     * DataBuilder-backed wishlist-item transfer; seed only the fields a test pins, the rest are
     * randomised. The built item is remembered for {@see assertResourceMatchesWishlistItem()}.
     *
     * @param array<string, mixed> $seed
     */
    public function haveWishlistItemTransfer(array $seed = []): WishlistItemTransfer
    {
        $wishlistItemTransfer = (new WishlistItemBuilder($seed))->build();
        $this->wishlistItemTransfers[] = $wishlistItemTransfer;

        return $wishlistItemTransfer;
    }

    /**
     * The response a stubbed `WishlistsRestApiClient` returns for an item that was added or updated.
     * Defaults to a freshly built item.
     */
    public function haveSuccessfulWishlistItemResponseTransfer(?WishlistItemTransfer $wishlistItemTransfer = null): WishlistItemResponseTransfer
    {
        return (new WishlistItemResponseTransfer())
            ->setIsSuccess(true)
            ->setWishlistItem($wishlistItemTransfer ?? $this->haveWishlistItemTransfer());
    }

    /**
     * The defensive response where the client reports success but carries no item — the processor
     * must raise an error rather than map a null item.
     */
    public function haveSuccessNoWishlistItemResponseTransfer(): WishlistItemResponseTransfer
    {
        return (new WishlistItemResponseTransfer())->setIsSuccess(true);
    }

    /**
     * The response a stubbed client returns when an item operation failed. Pass the module's
     * `ERROR_IDENTIFIER_*` when the consumer maps identifiers to a status; omit it for consumers
     * that only read `getIsSuccess()`.
     */
    public function haveFailedWishlistItemResponseTransfer(?string $errorIdentifier = null): WishlistItemResponseTransfer
    {
        return (new WishlistItemResponseTransfer())
            ->setIsSuccess(false)
            ->setErrorIdentifier($errorIdentifier);
    }

    /**
     * Asserts the API resource carries the fields of the wishlist this helper built. Defaults to
     * the last built wishlist; pass `$expectedWishlistTransfer` when a test builds several.
     *
     * Reads the resource's public fields rather than typing the parameter, because the resource
     * class is generated per project (`Generated\Api\…`) and this module must not depend on it.
     */
    public function assertResourceMatchesWishlist(object $apiResource, ?WishlistTransfer $expectedWishlistTransfer = null): void
    {
        $expectedWishlistTransfer ??= $this->getLastWishlistTransfer();
        $resourceFields = get_object_vars($apiResource);

        Assert::assertSame(
            $expectedWishlistTransfer->getUuid(),
            $resourceFields[static::RESOURCE_FIELD_UUID] ?? null,
        );
        Assert::assertSame(
            $expectedWishlistTransfer->getName(),
            $resourceFields[static::RESOURCE_FIELD_NAME] ?? null,
        );
        Assert::assertSame(
            $expectedWishlistTransfer->getNumberOfItems(),
            $resourceFields[static::RESOURCE_FIELD_NUMBER_OF_ITEMS] ?? null,
        );
    }

    /**
     * Asserts the provided API resources are, in order, every wishlist this helper built.
     *
     * @param array<int, object> $apiResources
     */
    public function assertResourceCollectionMatchesWishlists(array $apiResources): void
    {
        Assert::assertCount(count($this->wishlistTransfers), $apiResources);

        foreach (array_values($this->wishlistTransfers) as $index => $wishlistTransfer) {
            $this->assertResourceMatchesWishlist($apiResources[$index], $wishlistTransfer);
        }
    }

    /**
     * Asserts the API resource carries the sku of the wishlist item this helper built. Defaults to
     * the last built item; pass `$expectedWishlistItemTransfer` when a test builds several.
     *
     * Reads the resource's public fields rather than typing the parameter, because the resource
     * class is generated per project (`Generated\Api\…`) and this module must not depend on it.
     */
    public function assertResourceMatchesWishlistItem(object $apiResource, ?WishlistItemTransfer $expectedWishlistItemTransfer = null): void
    {
        $expectedWishlistItemTransfer ??= $this->getLastWishlistItemTransfer();
        $resourceFields = get_object_vars($apiResource);

        Assert::assertSame(
            $expectedWishlistItemTransfer->getSku(),
            $resourceFields[static::RESOURCE_FIELD_SKU] ?? null,
        );
        Assert::assertSame(
            $this->getExpectedWishlistItemIdentifier($expectedWishlistItemTransfer),
            $resourceFields[static::RESOURCE_FIELD_UUID] ?? null,
        );
    }

    /**
     * The identifier the resource answers with. A plain item is addressed by its sku; an item bound
     * to a product offer is addressed by `<sku>_<productOfferReference>`, which the item-attribute
     * mapper plugin chain composes. An item carrying a product configuration is addressed by
     * `<sku>_<configurationHash>` instead, and the hash is minted by a service the fixture cannot
     * reproduce — such an item has to name its own expectation.
     *
     * @throws \RuntimeException
     */
    protected function getExpectedWishlistItemIdentifier(WishlistItemTransfer $wishlistItemTransfer): string
    {
        if ($wishlistItemTransfer->getProductConfigurationInstance() !== null) {
            throw new RuntimeException(sprintf(
                'The identifier of a wishlist item with a product configuration carries a hash this '
                . 'helper cannot mint. Assert `%s` in the test instead of calling %s().',
                static::RESOURCE_FIELD_UUID,
                __FUNCTION__,
            ));
        }

        $productOfferReference = $wishlistItemTransfer->getProductOfferReference();

        if ($productOfferReference === null || $productOfferReference === '') {
            return (string)$wishlistItemTransfer->getSku();
        }

        return sprintf('%s_%s', $wishlistItemTransfer->getSku(), $productOfferReference);
    }

    protected function getLastWishlistTransfer(): WishlistTransfer
    {
        $wishlistTransfer = end($this->wishlistTransfers);

        if ($wishlistTransfer === false) {
            Assert::fail(sprintf(
                'No wishlist was built yet — call %s::haveWishlistTransfer() before asserting.',
                static::class,
            ));
        }

        return $wishlistTransfer;
    }

    protected function getLastWishlistItemTransfer(): WishlistItemTransfer
    {
        $wishlistItemTransfer = end($this->wishlistItemTransfers);

        if ($wishlistItemTransfer === false) {
            Assert::fail(sprintf(
                'No wishlist item was built yet — call %s::haveWishlistItemTransfer() before asserting.',
                static::class,
            ));
        }

        return $wishlistItemTransfer;
    }
}
