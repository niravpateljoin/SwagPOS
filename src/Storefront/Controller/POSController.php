<?php declare(strict_types=1);

namespace POSBasicExample\Storefront\Controller;

use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Content\Product\ProductEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Checkout\Order\OrderLineItemEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route(defaults: ['_routeScope' => ['api']])]
class POSController extends StorefrontController
{
    private EntityRepository $customerRepository;
    private EntityRepository $productRepository;
    private EntityRepository $orderRepository;
    private EntityRepository $paymentMethodRepository;
    private CartService $cartService;
    private OrderService $orderService;
    public function __construct(
        EntityRepository $customerRepository,
        EntityRepository $productRepository,
        EntityRepository $orderRepository,
        EntityRepository $paymentMethodRepository,
        CartService $cartService,
        OrderService $orderService
    ) {
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->cartService = $cartService;
        $this->orderService = $orderService;
    }

    #[Route(path: '/pos', name: 'frontend.pos.index', methods: ['GET'], defaults: ['_routeScope' => ['storefront']])]
    public function index(Context $context): Response
    {
        // Get the customer data
        $customers = $this->customerRepository->search(new Criteria(), $context)->getEntities();
        $customerList = [];
        foreach ($customers as $customer) {
            $customerList[] = [
                'id' => $customer->getId(),
                'name' => $customer->getFirstName() . ' ' . $customer->getLastName(),
                'email' => $customer->getEmail(),
            ];
        }

        // Get product data
        $products = $this->productRepository->search(
            (new Criteria())->addAssociation('translations')->addAssociation('cover.media')->addAssociation('tax'),
            $context
        )->getEntities();

        $productList = array_map(function (ProductEntity $product) {
            return [
                'id' => $product->getId(),
                'name' => $product->getTranslation('name') ?? $product->getName(),
                'description' => $product->getTranslation('description') ?? '',
                'price' => $product->getPrice()?->first()?->getGross() ?? 0,
                'imageUrl' => $product->getCover()?->getMedia()?->getUrl() ?? '/default-product.png',
                'taxRate' => $product->getTax()?->getTaxRate() ?? 0,
            ];
        }, $products->getElements());

        // Get payment methods
        $paymentMethods = $this->paymentMethodRepository->search(
            (new Criteria())->addFilter(
                new \Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter('active', true)
            ),
            $context
        )->getEntities();

        $paymentList = [];
        foreach ($paymentMethods as $payment) {
            $paymentList[] = [
                'id' => $payment->getId(),
                'name' => $payment->getTranslation('name') ?? $payment->getName(),
                'description' => $payment->getTranslation('description') ?? '',
            ];
        }

        return $this->renderStorefront('@POSBasicExample/storefront/page/POS/index.html.twig', [
            'customers' => $customerList,
            'products' => $productList,
            'paymentMethods' => $paymentList
        ]);
    }

}