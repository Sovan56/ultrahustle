<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateServiceOrderRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'milestones'               => ['required','array','min:1','max:20'],
            'milestones.*.title'       => ['required','string','max:160'],
            'milestones.*.description' => ['nullable','string','max:4000'],
            'milestones.*.price'       => ['required','numeric','min:0'],
            'milestones.*.start_date'  => ['nullable','date'],
            'milestones.*.end_date'    => ['nullable','date','after_or_equal:milestones.*.start_date'],
        ];
    }

    /**
     * Cross-row validation:
     * - Each milestone must have start < end (strict)
     * - Row[n].start must be > Row[n-1].end (strictly increasing timeline)
     */
    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $rows = $this->input('milestones', []);
            if (!is_array($rows)) return;

            $prevEnd = null;
            foreach ($rows as $i => $row) {
                $s = !empty($row['start_date']) ? Carbon::parse($row['start_date']) : null;
                $e = !empty($row['end_date'])   ? Carbon::parse($row['end_date'])   : null;

                // If both provided, enforce strict start < end
                if ($s && $e && !$s->lt($e)) {
                    $v->errors()->add("milestones.$i.start_date", 'Start date must be strictly earlier than end date.');
                    $v->errors()->add("milestones.$i.end_date", 'End date must be strictly later than start date.');
                }

                // Cross-row: current start must be strictly > previous end
                if ($prevEnd && $s && !$s->gt($prevEnd)) {
                    $v->errors()->add("milestones.$i.start_date", 'Start date must be later than the previous milestone’s end date.');
                }

                if ($e) $prevEnd = $e;
            }
        });
    }
}
