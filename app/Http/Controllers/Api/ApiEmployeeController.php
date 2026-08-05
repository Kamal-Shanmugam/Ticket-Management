<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Http\Resources\EmployeeResource;
use Illuminate\Http\Request;

class ApiEmployeeController extends Controller
{
    use HandlesApiResponses;

    /**
     * Retrieve all employees, with optional department filtering.
     */
    public function index(Request $request)
    {
        $query = Employee::with(['role', 'department']);

        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('is_available')) {
            $query->where('is_available', filter_var($request->is_available, FILTER_VALIDATE_BOOLEAN));
        }

        $employees = $query->paginate(15);

        return $this->successResponse(
            EmployeeResource::collection($employees)->response()->getData(true),
            'Employees list retrieved.'
        );
    }

    /**
     * Retrieve a specific employee details.
     */
    public function show($id)
    {
        $employee = Employee::with(['role', 'department'])->find($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found.', [], 404);
        }

        return $this->successResponse(new EmployeeResource($employee), 'Employee details retrieved.');
    }

    /**
     * Update employee profile/availability (e.g. self-toggling or admin updating).
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return $this->errorResponse('Employee not found.', [], 404);
        }

        // Validate availability toggle or department reassignment
        $request->validate([
            'is_available' => ['nullable', 'boolean'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        if ($request->has('is_available')) {
            $employee->is_available = $request->is_available;
        }

        if ($request->has('department_id')) {
            $employee->department_id = $request->department_id;
        }

        $employee->save();

        return $this->successResponse(new EmployeeResource($employee), 'Employee updated successfully.');
    }
}
