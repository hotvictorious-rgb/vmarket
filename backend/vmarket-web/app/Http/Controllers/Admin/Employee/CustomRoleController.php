<?php

namespace App\Http\Controllers\Admin\Employee;

use App\Contracts\Repositories\AdminRepositoryInterface;
use App\Contracts\Repositories\AdminRoleRepositoryInterface;
use App\Enums\ExportFileNames\Admin\Employee;
use App\Enums\GlobalConstant;
use App\Exports\EmployeeRoleListExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\CustomRoleRequest;
use App\Traits\PaginatorTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Maatwebsite\Excel\Facades\Excel;

class CustomRoleController extends BaseController
{
    use PaginatorTrait;

    public function __construct(
        private readonly AdminRepositoryInterface     $adminRepo,
        private readonly AdminRoleRepositoryInterface $adminRoleRepo,
    )
    {
    }

    /**
     * @param Request|null $request
     * @param string|null $type
     * @return View Index function is the starting point of a controller
     * Index function is the starting point of a controller
     */
    public function index(Request|null $request, ?string $type = null): View|RedirectResponse
    {
        return redirect()->route('admin.employee.list')->with('info', translate('All system roles are predetermined and immutable. Select from predefined roles when assigning staff.'));
    }

    public function add(CustomRoleRequest $request): RedirectResponse
    {
        ToastMagic::error(translate('Custom role creation is disabled. All system roles are predetermined.'));
        return redirect()->route('admin.employee.list');
    }

    public function getUpdateView(string $id): View|RedirectResponse
    {
        ToastMagic::info(translate('System roles are predetermined and immutable.'));
        return redirect()->route('admin.employee.list');
    }

    public function update(CustomRoleRequest $request, $id): RedirectResponse
    {
        ToastMagic::error(translate('System roles are predetermined and cannot be modified.'));
        return redirect()->route('admin.employee.list');
    }

    public function updateStatus(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => translate('System roles are permanent and cannot be deactivated.')
        ], 403);
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $roles = $this->adminRoleRepo->getEmployeeRoleList(
            orderBy: ['id' => 'desc'],
            searchValue: $request['searchValue'],
            filters: ['admin_role_id' => $request['role']],
            dataLimit: 'all'
        );
        return Excel::download(new EmployeeRoleListExport([
            'roles' => $roles,
            'searchValue' => $request['searchValue'],
        ]), Employee::EMPLOYEE_ROLE_LIST);
    }

    public function delete(Request $request): JsonResponse
    {
        return response()->json([
            'success' => 0,
            'message' => translate('System roles are permanent and cannot be deleted.')
        ], 403);
    }
}
