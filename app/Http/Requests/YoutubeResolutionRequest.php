<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class YoutubeResolutionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => 'required|url',
            'resolution' => 'required|in:256x144,426x240,640x360,1280x720,1920x1080',
        ];
    }
}
