<?php
namespace Modules\SkyOps\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FleetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'airline' => 'nullable|string|max:10',
            'icao'    => 'nullable|string|max:10',
            'subtype' => 'nullable|string|max:50',
            'reg'     => 'nullable|string|max:20',
            'min'     => 'nullable|integer|min:0',
            'order'   => 'nullable|string|max:30',
        ];
    }

    public function defaults(): array
    {
        return [
            'airline' => '',
            'icao'    => '',
            'subtype' => '',
            'reg'     => '',
            'min'     => 0,
            'order'   => 'time_desc',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        if (is_array($data)) {
            $data = array_merge($this->defaults(), array_filter($data, fn($v) => $v !== null && $v !== ''));
        }
        return $data;
    }
}
