<?php

namespace App\Http\Requests;

use App\Models\ProductVideo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductVideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'video_provider' => ['required', 'numeric', 'max:24'],
            'link'           => ['required_unless:video_provider,20', 'nullable', 'url', 'max:5000',
                Rule::unique("product_videos", "link")->where('product_id', $this->route('product.id'))->ignore($this->route('productVideo.id'))
            ],
            'file'           => [
                Rule::requiredIf(fn () => $this->requiresUploadedVideoFile()),
                'nullable',
                'file',
                'mimetypes:video/mp4,video/x-m4v,video/quicktime,video/webm,video/x-msvideo,video/x-flv',
                'max:20480',
            ],
            'thumbnail'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_thumbnail' => ['nullable', 'boolean'],
        ];
    }

    private function requiresUploadedVideoFile(): bool
    {
        if ((int) $this->input('video_provider') !== 20) {
            return false;
        }

        $productVideo = $this->route('productVideo');
        if ($productVideo instanceof ProductVideo && $productVideo->getFirstMedia('product_video')) {
            return false;
        }

        return true;
    }
}
