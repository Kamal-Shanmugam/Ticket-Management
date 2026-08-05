<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Http\Resources\DepartmentResource;

class ApiDepartmentController extends Controller
{
    use HandlesApiResponses;

    /**
     * List all departments.
     */
    public function index()
    {
        $departments = Department::all();
        return $this->successResponse(DepartmentResource::collection($departments), 'Departments list retrieved.');
    }

    /**
     * Retrieve a specific department details.
     */
    public function show($id)
    {
        $department = Department::find($id);

        if (!$department) {
            return $this->errorResponse('Department not found.', [], 404);
        }

        return $this->successResponse(new DepartmentResource($department), 'Department details retrieved.');
    }
}
