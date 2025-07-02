<?php declare(strict_types=1);

namespace POSBasicExample\Storefront\Controller;

use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class CustomerController extends StorefrontController
{
    private EntityRepository $customerRepository;
    private EntityRepository $customerGroupRepository;
    private EntityRepository $salesChannelRepository;
    private EntityRepository $countryRepository;


    public function __construct(
        EntityRepository $customerRepository,
        EntityRepository $customerGroupRepository,
        EntityRepository $salesChannelRepository,
        EntityRepository $countryRepository

    ) {
        $this->customerRepository = $customerRepository;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->countryRepository = $countryRepository;

    }

    #[Route(path: '/pos/add-customer', name: 'frontend.add.customer', methods: ['GET'])]
    public function index(Context $context): Response
    {
        $customerGroups = $this->customerGroupRepository->search(new Criteria(), $context)->getEntities();
        $groupList = array_map(function (CustomerGroupEntity $group) {
            return [
                'id' => $group->getId(),
                'name' => $group->getTranslation('name') ?? $group->getName(),
            ];
        }, $customerGroups->getElements());

        $salesChannels = $this->salesChannelRepository->search(new Criteria(), $context)->getEntities();
        $salesChannelList = array_map(function (SalesChannelEntity $channel) {
            return [
                'id' => $channel->getId(),
                'name' => $channel->getTranslation('name') ?? $channel->getName(),
            ];
        }, $salesChannels->getElements());

         $countries = $this->countryRepository->search(new Criteria(), $context)->getEntities();
        $countryList = array_map(function ($country) {
            return [
                'id' => $country->getId(), 
                'name' => $country->getTranslation('name') ?? $country->getName(),
            ];
        }, $countries->getElements());

        return $this->renderStorefront('@POSBasicExample/storefront/page/POS/add-customer.html.twig', [
            'customerGroups' => $groupList,
            'salesChannels' => $salesChannelList,
            'countries' => $countryList,

        ]);
    }

   #[Route(path: '/pos/save-customer', name: 'frontend.pos.customer.save', methods: ['POST'])]
    public function saveCustomer(Request $request, SalesChannelContext $context): Response
    {
        $data = $request->request->all();

        $requiredFields = ['first_name', 'last_name', 'email'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $this->addFlash('danger', ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                return $this->redirectToRoute('frontend.add.customer');
            }
        }

        $firstName = $data['first_name'];
        $lastName = $data['last_name'];
        $email = $data['email'];
        $groupId = $data['group_id'] ?? $context->getCurrentCustomerGroup()->getId();
        $salesChannelId = $data['sales_channel_id'] ?? $context->getSalesChannel()->getId();
        $countryId = $data['country_id'] ?? null;

        $addressId = Uuid::randomHex();
        $customerId = Uuid::randomHex();

        $address = [
            'id' => $addressId,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'street' => $data['street'] ?? '',
            'city' => $data['city'] ?? '',
            'countryId' => $countryId,
        ];

        $customerData = [
            'id' => $customerId,
            'customerNumber' => 'POS-' . random_int(10000, 99999),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'groupId' => $groupId,
            'salesChannelId' => $salesChannelId,
            'defaultPaymentMethodId' => $context->getPaymentMethod()->getId(),
            'defaultBillingAddress' => $address,
            'defaultShippingAddress' => $address,
            'addresses' => [$address],
        ];

        try {
            $this->customerRepository->create([$customerData], $context->getContext());
            $this->addFlash('success', 'Customer account created successfully.');
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Error creating customer: ' . $e->getMessage());
        }

        return $this->redirectToRoute('frontend.add.customer');
    }

}
