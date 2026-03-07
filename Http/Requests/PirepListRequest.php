<?php
namespace Modules\SkyOps\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PirepListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from'    => 'nullable|date_format:Y-m-d',
            'to'      => 'nullable|date_format:Y-m-d',
            'q'       => 'nullable|string|max:100',
            'source'  => 'nullable|string|max:50',
            'network' => 'nullable|string|max:20',
            'sort'    => 'nullable|in:datumzeit,flight,dep,arr,pilot,airline,reg,review,block,air,landing,fuel,dist,source,network,phase',
            'dir'     => 'nullable|in:asc,desc',
        ];
    }

    public function defaults(): array
    {
        return [
            'from'    => now()->subDays(30)->format('Y-m-d'),
            'to'      => now()->format('Y-m-d'),
            'sort'    => 'datumzeit',
            'dir'     => 'desc',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        if (is_array($data)) {
            $data = array_merge($this->defaults(), array_filter($data, fn($v) => $v !== null));
        }
        return $data;
    }

    /**
     * Map short sort keys to actual DB column names.
     */
    protected static array $sortMap = [
        'datumzeit' => 'datumzeit',
        'flight'    => 'flight_no_full',
        'dep'       => 'dep_icao',
        'arr'       => 'arr_icao',
        'pilot'     => 'pilot_name',
        'airline'   => 'airline_icao',
        'reg'       => 'registration',
        'review'    => 'pirep_state',
        'block'     => 'block_mins',
        'air'       => 'air_mins',
        'landing'   => 'landing_fpm',
        'fuel'      => 'fuel_used',
        'dist'      => 'dist_nm',
        'source'    => 'source_name',
        'network'   => 'network',
        'phase'     => 'pirep_phase',
    ];

    public function sortColumn(): string
    {
        $sort = $this->validated()['sort'] ?? 'datumzeit';
        return self::$sortMap[$sort] ?? 'datumzeit';
    }
}
