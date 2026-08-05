<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;

class ApiCustomerController extends Controller
{
    use HandlesApiResponses;

    /**
     * Retrieve all customers (Only accessible to Employees).
     */
    public function index()
    {
        $customers = Customer::paginate(15);
        return $this->successResponse(
            CustomerResource::collection($customers)->response()->getData(true),
            'Customers retrieved successfully.'
        );
    }

    /**
     * Retrieve a specific customer.
     */
    public function show($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return $this->errorResponse('Customer not found.', [], 404);
        }

        return $this->successResponse(new CustomerResource($customer), 'Customer details retrieved.');
    }
}
