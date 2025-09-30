<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\ValidationException;
use App\Services\CustomerService;
use App\Traits\Validate;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\LoggerInterface;
use RuntimeException;
use OpenApi\Attributes as OA;

#[OA\Info(title: "Gateway API", version: "0.1")]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
class CustomerController
{
    use Validate;

    public function __construct(
        private CustomerService $customerService,
        private LoggerInterface $log
    ) {}

    #[OA\Get(
        path: "/api/v1/customers",
        operationId: "getCustomers",
        security: [["bearerAuth" => []]],
        tags: ["Customers"],
        responses: [
            new OA\Response(
                response: 200,
                description: "List all customers",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "count", type: "integer"),
                        new OA\Property(
                            property: "customers",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/Customer")
                        )
                    ]
                )
            )
        ]
    )]
    public function index(): ResponseInterface
    {
        $customers = $this->customerService->getCustomers();
        return new JsonResponse([
            "count" => count($customers),
            "customers" => $customers
        ], 200);
    }

    #[OA\Get(
        path: "/api/v1/customers/{code}",
        operationId: "getCustomerByCode",
        security: [["bearerAuth" => []]],
        tags: ["Customers"],
        parameters: [
            new OA\Parameter(name: "code", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Customer found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "customer", ref: "#/components/schemas/Customer")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Invalid customer code"),
            new OA\Response(response: 404, description: "Customer not found")
        ]
    )]
    public function show(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string $code */
        $code = $this->validateCode($request);
        $customer = $this->customerService->getCustomerByCode($code);
        if ($customer === null) {
            $this->log->error("Error", ["exception" => "Customer not found", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => 404, "message" => "Customer not found"]], 404);
        }

        return new JsonResponse(["customer" => $customer->toArray()], 200);
    }

    #[OA\Put(
        path: "/api/v1/customers/{code}",
        operationId: "updateCustomer",
        security: [["bearerAuth" => []]],
        tags: ["Customers"],
        parameters: [
            new OA\Parameter(name: "code", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "document", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Customer updated", content: new OA\JsonContent(ref: "#/components/schemas/Customer")),
            new OA\Response(response: 400, description: "Invalid request"),
            new OA\Response(response: 404, description: "Customer not found"),
            new OA\Response(response: 422, description: "Invalid format (only JSON)")
        ]
    )]
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string $code */
        $code = $this->validateCode($request);
        /**
         * @var array{name?:string, document?: string} $data
         */
        $data = json_decode($request->getBody()->getContents(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log->error("Error", ["exception" => "Invalid format (only json)", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => 422, "message" => "Invalid format (only json)"]], 422);
        }

        if (empty($data)) {
            $this->log->error("Error", ["exception" => "No fields provided", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => 400, "message" => "No fields provided"]], 400);
        }

        $findCustomer = $this->customerService->getCustomerByCode($code);
        if ($findCustomer === null) {
            $this->log->error("Error", ["exception" => "Customer not found", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => 404, "message" => "Customer not found"]], 404);
        }

        try {
            $customer = $this->customerService->updateCustomer($findCustomer, $data);
            if ($customer === null) {
                $this->log->error("Error", ["exception" => "Update is failed", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
                return new JsonResponse(["error" => ["code" => 400, "message" => "Update is failed"]], 400);
            }

            return new JsonResponse(["customer" => $customer->toArray()], 200);
        } catch (Exception $e) {
            $this->log->error("Error", ["exception" => $e->getMessage(), "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => 500, "message" => $e->getMessage()]], 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/customers",
        operationId: "createCustomer",
        security: [["bearerAuth" => []]],
        tags: ["Customers"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/Customer")
        ),
        responses: [
            new OA\Response(response: 201, description: "Customer created", content: new OA\JsonContent(ref: "#/components/schemas/Customer")),
            new OA\Response(response: 400, description: "Invalid request"),
            new OA\Response(response: 422, description: "Invalid format (only JSON)")
        ]
    )]
    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $data = json_decode($request->getBody()->getContents(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log->error("Error", ["exception" => "Invalid format (only json)", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => 422, "message" => "Invalid format (only json)"]], 422);
        }

        if (empty($data)) {
            $this->log->error("Error", ["exception" => "No fields provided", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => 400, "message" => "No fields provided"]], 400);
        }

        /**
         * @var array{external: string, name: string, document: string, emails: array<string>, phones: array<string>} $data
         */

        try {
            $customer = $this->customerService->createCustomer($data);
            if ($customer === null) {
                $this->log->error("Error", ["exception" => "Store is failed", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
                return new JsonResponse(["error" => ["code" => 400, "message" => "Store is failed"]], 400);
            }

            return new JsonResponse(["customer" => $customer->toArray()], 201, ["Location" => "/api/v1/customers/" . $customer->getCode()]);
        } catch (ValidationException $e) {
            $this->log->error("Error", ["exception" => $e->getMessage(), "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => $e->getCode(), "message" => $e->getMessage(), "errors" => $e->getErrors()]], $e->getCode());
        } catch (RuntimeException $e) {
            $this->log->error("Error", ["exception" => $e->getMessage(), "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => $e->getCode(), "message" => $e->getMessage()]], $e->getCode());
        } catch (Exception $e) {
            $this->log->error("Error", ["exception" => "Server error", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => 500, "message" => "Server error"]], 500);
        }
    }

    #[OA\Delete(
        path: "/api/v1/customers/{code}",
        operationId: "deleteCustomer",
        security: [["bearerAuth" => []]],
        tags: ["Customers"],
        parameters: [
            new OA\Parameter(name: "code", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Customer deleted"),
            new OA\Response(response: 400, description: "Invalid customer code"),
            new OA\Response(response: 404, description: "Customer not found")
        ]
    )]
    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string $code */
        $code = $this->validateCode($request);
        $findCustomer = $this->customerService->getCustomerByCode($code);
        if ($findCustomer === null) {
            $this->log->error("Error", ["exception" => "Customer not found", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => 404, "message" => "Customer not found"]], 404);
        }

        if ($this->customerService->deleteCustomer($findCustomer) === false) {
            $this->log->error("Error", ["exception" => "Delete is failed", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => 400, "message" => "Delete is failed"]], 400);
        }

        return new JsonResponse([], 204);
    }

    private function validateCode(ServerRequestInterface $request): string|ResponseInterface
    {
        $code = $request->getAttributes()["code"];
        if (is_string($code) === false || empty($code) || $this->validatePattern($code) === false) {
            $this->log->error("Error", ["exception" => "Invalid customer code", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => 400, "message" => "Invalid customer code"]], 400);
        }

        return $code;
    }
}
