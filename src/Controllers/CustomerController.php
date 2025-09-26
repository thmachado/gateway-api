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

class CustomerController
{
    use Validate;

    public function __construct(
        private CustomerService $customerService,
        private LoggerInterface $log
    ) {
    }

    public function index(): ResponseInterface
    {
        $customers = $this->customerService->getCustomers();
        return new JsonResponse([
            "count" => count($customers),
            "customers" => $customers
        ], 200);
    }

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $code = $request->getAttributes()["code"];
        if (is_string($code) === false || empty($code) || $this->validatePattern($code) === false) {
            $this->log->error("Error", ["exception" => "Invalid customer code", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => 400, "message" => "Invalid customer code"]], 400);
        }

        $customer = $this->customerService->getCustomerByCode($code);
        if ($customer === null) {
            $this->log->error("Error", ["exception" => "Customer not found", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => 404, "message" => "Customer not found"]], 404);
        }

        return new JsonResponse(["customer" => $customer->toArray()], 200);
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $code = $request->getAttributes()["code"];
        if (is_string($code) === false || empty($code) || $this->validatePattern($code) === false) {
            $this->log->error("Error", ["exception" => "Invalid customer code", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => 400, "message" => "Invalid customer code"]], 400);
        }

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

            return new JsonResponse($customer->toArray(), 201, ["Location" => "/api/v1/customers/" . $customer->getCode()]);

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

    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $code = $request->getAttributes()["code"];
        if (is_string($code) === false || empty($code) || $this->validatePattern($code) === false) {
            $this->log->error("Error", ["exception" => "Invalid customer code", "method" => $request->getMethod(), "path" => $request->getUri()->getPath()]);
            return new JsonResponse(["error" => ["code" => 400, "message" => "Invalid customer code"]], 400);
        }

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

}