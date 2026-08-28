<?php

namespace App\Http\Controllers;

use App\Contracts\ControllerInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as MainController;

abstract class BaseController extends MainController implements ControllerInterface
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Default implementation for ControllerInterface contract across all ecosystem controllers.
     */
    public function index(?Request $request, ?string $type = null)
    {
        return null;
    }
}
