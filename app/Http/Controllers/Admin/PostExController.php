<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PostExApiException;
use App\Http\Requests\PostExRequest;
use App\Http\Resources\PostExResource;
use App\Services\PostEx\PostExApiClient;
use App\Services\PostEx\PostExSettingsService;
use Exception;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PostExController extends AdminController implements HasMiddleware
{
    public function __construct(
        protected PostExSettingsService $postExSettingsService,
        protected PostExApiClient $postExApiClient
    ) {
        parent::__construct();
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:settings', only: ['index', 'update', 'testConnection']),
        ];
    }

    public function index(): PostExResource|\Illuminate\Http\Response
    {
        try {
            return new PostExResource($this->postExSettingsService->list());
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function update(PostExRequest $request): PostExResource|\Illuminate\Http\Response
    {
        try {
            return new PostExResource($this->postExSettingsService->update($request));
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function testConnection(): \Illuminate\Http\Response|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        try {
            $response = $this->postExApiClient->testConnection();

            return response([
                'status'  => true,
                'message' => $response['statusMessage'] ?? 'Connected to PostEx successfully.',
                'data'    => $response['dist'] ?? [],
            ]);
        } catch (PostExApiException $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        } catch (Exception $exception) {
            return response(['status' => false, 'message' => $exception->getMessage()], 422);
        }
    }
}
