<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace Tests\BitBag\SyliusWishlistPlugin\Behat\Context\Ui;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Sylius\Behat\NotificationType;
use Sylius\Behat\Service\NotificationCheckerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Tests\BitBag\SyliusWishlistPlugin\Behat\Page\Shop\ProductIndexPageInterface;
use Tests\BitBag\SyliusWishlistPlugin\Behat\Page\Shop\ProductShowPageInterface;
use Tests\BitBag\SyliusWishlistPlugin\Behat\Page\Shop\WishlistPageInterface;
use Tests\BitBag\SyliusWishlistPlugin\Behat\Service\LoginerInterface;
use Tests\BitBag\SyliusWishlistPlugin\Behat\Service\WishlistCreatorInterface;
use Webmozart\Assert\Assert;

final class WishlistContext extends RawMinkContext implements Context
{
    private ProductRepositoryInterface $productRepository;

    private ProductIndexPageInterface $productIndexPage;

    private ProductShowPageInterface $productShowPage;

    private WishlistPageInterface $wishlistPage;

    private NotificationCheckerInterface $notificationChecker;

    private LoginerInterface $loginer;

    private WishlistCreatorInterface $wishlistCreator;

    private Session $session;

    private RouterInterface $router;

    public function __construct(
        ProductRepositoryInterface   $productRepository,
        ProductIndexPageInterface    $productIndexPage,
        ProductShowPageInterface     $productShowPage,
        WishlistPageInterface        $wishlistPage,
        NotificationCheckerInterface $notificationChecker,
        LoginerInterface             $loginer,
        WishlistCreatorInterface     $wishlistCreator,
        Session                      $session,
        RouterInterface              $router
    )
    {
        $this->productRepository = $productRepository;
        $this->productIndexPage = $productIndexPage;
        $this->wishlistPage = $wishlistPage;
        $this->notificationChecker = $notificationChecker;
        $this->loginer = $loginer;
        $this->wishlistCreator = $wishlistCreator;
        $this->productShowPage = $productShowPage;
        $this->session = $session;
        $this->router = $router;
    }

    /**
     * @When I add this product to wishlist
     */
    public function iAddThisProductToWishlist(): void
    {
        $this->productIndexPage->open(['slug' => 'main']);

        /** @var ProductInterface $product */
        $product = $this->productRepository->findOneBy([]);

        $this->productIndexPage->addProductToWishlist($product->getName());
    }

    /**
     * @When I add :productName product to my wishlist
     */
    public function iAddProductToMyWishlist(string $productName): void
    {
        $this->productIndexPage->open(['slug' => 'main']);

        $this->productIndexPage->addProductToWishlist($productName);
    }

    /**
     * @When I add this product variant to wishlist
     */
    public function iAddThisProductVariantToWishlist(): void
    {
        $this->productShowPage->addVariantToWishlist();
    }

    /**
     * @When I log in to my account which already has :product product in the wishlist
     */
    public function iLogInToMyAccountWhichAlreadyHasProductInTheWishlist(ProductInterface $product): void
    {
        $user = $this->loginer->createUser();

        $this->wishlistCreator->createWishlistWithProductAndUser($user, $product);
        $this->loginer->logIn();
    }

    /**
     * @When I log in
     */
    public function iLogIn(): void
    {
        $this->loginer->createUser();

        $this->loginer->logIn();
    }

    /**
     * @When I log in again
     */
    public function iLogInAgain(): void
    {
        $this->loginer->logIn();
    }

    /**
     * @When I log out
     */
    public function iLogOut(): void
    {
        $this->loginer->logOut();
    }

    /**
     * @When I go to the wishlist page
     */
    public function iGoToTheWishlistPage(): void
    {
        $this->wishlistPage->open();
    }

    /**
     * @When I select :quantity quantity of :productName product
     */
    public function iSelectQuantityOfProduct(int $quantity, string $productName): void
    {
        $this->wishlistPage->selectProductQuantity($productName, $quantity);
    }

    /**
     * @When I add my wishlist products to cart
     */
    public function iAddMyWishlistProductsToCart(): void
    {
        $this->wishlistPage->addProductToCart();
    }

    /**
     * @When I add selected products to cart
     */
    public function iAddSelectedProductsToCart(): void
    {
        $this->wishlistPage->addSelectedProductsToCart();
    }

    /**
     * @When I remove this product
     */
    public function iRemoveThisProduct(): void
    {
        $this->wishlistPage->removeProduct($this->productRepository->findOneBy([])->getName());
    }

    /**
     * @When I remove selected products from wishlist
     */
    public function iRemoveSelectedProductsFromWishlist(): void
    {
        $this->wishlistPage->removeSelectedProductsFromWishlist();
    }

    /**
     * @When I export to pdf selected products from wishlist and file is downloaded
     */
    public function iExportToPdfSelectedProductsFromWishlistAndFileIsDownloaded(): void
    {
        $this->wishlistPage->exportToPdfSelectedProductsFromWishlist();

        $cookieName = $this->session->getName();
        $sessionId = $this->session->getId();
        $baseUrl = $this->getMinkParameter('base_url');
        $domain = parse_url($baseUrl)['host'];

        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray(array(
            $cookieName => $sessionId,
        ), $domain);

        $guzzle = new \GuzzleHttp\Client(array(
            'timeout' => 10,
            'cookies' => $cookieJar,
        ));

        $url = $this->router->generate('bitbag_sylius_wishlist_plugin_shop_wishlist_export_to_pdf',[], UrlGeneratorInterface::ABSOLUTE_URL);
        $response = $guzzle->get('https://127.0.0.1:8080/wishlist/export/pdf');

        $driver = $this->getSession()->getDriver();
        $contentType = $response->getHeader('Content-Type')[0];
        if ($contentType !== "text/html; charset=UTF-8") {
            throw new \Behat\Mink\Exception\ExpectationException('The content type of the downloaded file is not correct.', $driver);
        }
    }

    /**
     * @Then I should be on my wishlist page
     */
    public function iShouldBeOnMyWishlistPage(): void
    {
        $this->wishlistPage->verify();
    }

    /**
     * @Then I should be notified that the product has been successfully added to my wishlist
     */
    public function iShouldBeNotifiedThatTheProductHasBeenSuccessfullyAddedToMyWishlist(): void
    {
        $this->notificationChecker->checkNotification('Product has been added to your wishlist.', NotificationType::success());
    }

    /**
     * @Then I should be notified that the product has been removed from my wishlist
     */
    public function iShouldBeNotifiedThatTheProductHasBeenRemovedFromMyWishlist(): void
    {
        $this->notificationChecker->checkNotification('Product has been removed from your wishlist.', NotificationType::success());
    }

    /**
     * @Then I should have one item in my wishlist
     */
    public function iShouldHaveOnItemInMyWishlist(): void
    {
        Assert::eq(1, $this->wishlistPage->getItemsCount());
    }

    /**
     * @Then I should have :count products in my wishlist
     */
    public function iShouldHaveProductsInMyWishlist(int $count): void
    {
        Assert::eq($count, $this->wishlistPage->getItemsCount());
    }

    /**
     * @Then I should have :productName product in my wishlist
     */
    public function iShouldHaveProductInMyWishlist(string $productName): void
    {
        Assert::true($this->wishlistPage->hasProduct($productName));
    }

    /**
     * @Then I should have ":productName" product in my cart
     */
    public function iShouldHaveProductInMyCart(string $productName): void
    {
        Assert::true(
            $this->wishlistPage->hasProductInCart($productName),
            sprintf('Product %s was not found in the cart.', $productName)
        );
    }

    /**
     * @Then I should be notified that :product does not have sufficient stock
     */
    public function iShouldBeNotifiedThatThisProductDoesNotHaveSufficientStock(ProductInterface $product)
    {
        Assert::true($this->wishlistPage->hasProductOutOfStockValidationMessage($product));
    }
}
