<?php

namespace App\Services;


use App\Http\Requests\LanguageFileTextGetRequest;
use App\Libraries\AppLibrary;
use Exception;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\LanguageRequest;
use App\Http\Requests\PaginateRequest;
use App\Libraries\QueryExceptionLibrary;
use Dipokhalder\Settings\Facades\Settings;


class LanguageService
{

    protected $languageFilter = [
        'name',
        'code',
        'status',
    ];
    /**
     * @throws Exception
     */
    public function list(PaginateRequest $request)
    {
        try {
            $requests    = $request->all();
            $method      = $request->get('paginate', 0) == 1 ? 'paginate' : 'get';
            $methodValue = $request->get('paginate', 0) == 1 ? $request->get('per_page', 10) : '*';
            $orderColumn = $request->get('order_column') ?? 'id';
            $orderType   = $request->get('order_type') ?? 'desc';

            return Language::where(function ($query) use ($requests) {
                foreach ($requests as $key => $request) {
                    if (in_array($key, $this->languageFilter)) {
                        $query->where($key, 'like', '%' . $request . '%');
                    }
                }
            })->orderBy($orderColumn, $orderType)->$method(
                $methodValue
            );
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function store(LanguageRequest $request)
    {
        try {
            if (!file_exists(base_path("resources/js/languages/{$request->code}.json"))) {
                copy(base_path("resources/js/languages/en.json"), base_path("resources/js/languages/{$request->code}.json"));
            }

            if (!file_exists(base_path("lang/{$request->code}"))) {
                mkdir(base_path("lang/{$request->code}"), 0755);
                $files = scandir(base_path("lang/en"));
                if (count($files) > 2) {
                    foreach ($files as $file) {
                        if ($file != '.' && $file != '..') {
                            copy(base_path("lang/en/{$file}"), base_path("lang/{$request->code}/{$file}"));
                        }
                    }
                }
            }

            $language = Language::create($request->validated());
            if ($request->image) {
                $language->addMediaFromRequest('image')->toMediaCollection('language');
            }

            return $language;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function update(LanguageRequest $request, Language $language): Language
    {
        try {
            $language->update($request->validated());
            if ($request->image) {
                $language->clearMediaCollection('language');
                $language->addMediaFromRequest('image')->toMediaCollection('language');
            }
            return $language;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function destroy(Language $language): void
    {
        try {
            if (Settings::group('site')->get("site_default_language") != $language->id) {
                if (!env('DEMO')) {
                    AppLibrary::deleteDir(base_path("lang/{$language->code}"));
                    if (file_exists(base_path("resources/js/languages/{$language->code}.json"))) {
                        unlink(base_path("resources/js/languages/{$language->code}.json"));
                    }
                }
                $language->delete();
            } else {
                throw new Exception("Default language not deletable", 422);
            }
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * @throws Exception
     */
    public function show(Language $language): Language
    {
        try {
            return $language;
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }


    /**
     * @throws Exception
     */
    public function fileList(Language $language)
    {
        try {
            $i = 0;
            $array = [];

            if (file_exists(base_path("resources/js/languages/{$language->code}.json"))) {
                $array[$i] = (object)[
                    'path' => base_path("resources/js/languages/{$language->code}.json"),
                    'name' => "{$language->code}.json"
                ];
                $i++;
            }

            if (file_exists(base_path("lang/{$language->code}"))) {
                $files = scandir(base_path("lang/{$language->code}"));
                if (count($files) > 2) {
                    foreach ($files as $file) {
                        if ($file != '.' && $file != '..') {
                            $array[$i] = (object)[
                                'path' => base_path("lang/{$language->code}/{$file}"),
                                'name' => $file
                            ];
                            $i++;
                        }
                    }
                }
            }
            return collect($array);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }


    /**
     * Safely read language file contents (JSON or PHP lang arrays). Never include/executes paths.
     *
     * @throws Exception
     */
    public function fileText(LanguageFileTextGetRequest $request)
    {
        $path = $this->assertSafeLanguagePath((string) $request->path);
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'json') {
            $raw = file_get_contents($path);
            return json_decode($raw, true) ?? [];
        }

        if ($ext === 'php') {
            // Load as data only — no include of attacker-controlled paths outside allowlist.
            $data = include $path;
            return is_array($data) ? $data : [];
        }

        throw new Exception('Unsupported language file type.', 422);
    }

    /**
     * @throws Exception
     */
    public function fileTextStore(Request $request): void
    {
        try {
            $path        = $this->assertSafeLanguagePath((string) $request->x_language_file_path);
            $fileContent = file_get_contents($path);
            if ($fileContent === false) {
                throw new Exception('Unable to read language file.', 422);
            }

            foreach ($request->all() as $key => $value) {
                if ($key != 'x_language_file_path' && $key != 'x_language_file_name') {
                    $key = str_replace('_', ' ', $key);
                    if (strpos($fileContent, "'" . $key . "'") !== false) {
                        $fileContent = str_replace("'" . $key . "'", "\"{$value}\"", $fileContent);
                    } elseif (strpos($fileContent, "\"{$key}\"") !== false) {
                        $fileContent = str_replace("\"{$key}\"", "\"{$value}\"", $fileContent);
                    }
                }
            }

            file_put_contents($path, $fileContent);
        } catch (Exception $exception) {
            Log::info($exception->getMessage());
            throw new Exception(QueryExceptionLibrary::message($exception), 422);
        }
    }

    /**
     * Only allow language files under lang/{code}/ or resources/js/languages/.
     *
     * @throws Exception
     */
    protected function assertSafeLanguagePath(string $path): string
    {
        $real = realpath($path);
        if ($real === false || !is_file($real)) {
            throw new Exception('Language file not found.', 422);
        }

        $allowedRoots = array_filter([
            realpath(base_path('lang')),
            realpath(base_path('resources/js/languages')),
        ]);

        $ok = false;
        foreach ($allowedRoots as $root) {
            if ($root && str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
                $ok = true;
                break;
            }
        }

        if (!$ok) {
            throw new Exception('Invalid language file path.', 422);
        }

        $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
        if (!in_array($ext, ['php', 'json'], true)) {
            throw new Exception('Unsupported language file type.', 422);
        }

        return $real;
    }
}
