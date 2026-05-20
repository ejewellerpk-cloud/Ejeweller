<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Services\WhatsappService;
use App\Http\Requests\WhatsappRequest;
use App\Http\Resources\WhatsappResource;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class WhatsappController extends AdminController implements HasMiddleware
{
    private WhatsappService $whatsappService;

    public function __construct(WhatsappService $whatsappService)
    {
        parent::__construct();
        $this->whatsappService = $whatsappService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings', only: ['index']),
            new Middleware('permission:settings', only: ['update']),
        ];
    }

    public function index()
    {
        try {
            return new WhatsappResource($this->whatsappService->list());
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(WhatsappRequest $request): \Illuminate\Http\Response | WhatsappResource | \Illuminate\Contracts\Foundation\Application | \Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            return new WhatsappResource($this->whatsappService->update($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
