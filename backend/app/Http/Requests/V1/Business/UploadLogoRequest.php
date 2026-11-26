<?php

namespace App\Http\Requests\V1\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PNG or JPEG, at most 1 MB. The real file content is checked, not just
 * the file name.
 */
class UploadLogoRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'logo' => ['required', 'file', 'mimetypes:image/png,image/jpeg', 'max:1024', Rule::dimensions()->maxWidth(2000)->maxHeight(2000)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.mimetypes' => 'Upload a PNG or JPEG image.',
            'logo.max' => 'The logo must be 1 MB or smaller.',
            'logo.dimensions' => 'The logo can be at most 2000 by 2000 pixels.',
        ];
    }
}
