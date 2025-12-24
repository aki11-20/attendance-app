<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class StoreCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'breaks' => ['array'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'date_format:H:i'],
            'comment' => ['required', 'string'],
        ];
    }

    public function messages() {
        return [
            'comment.required' => '備考を記入してください',
            'clock_in.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.start.date_format' => '休憩時間が不適切な値です',
            'breaks.*.end.date_format' => '休憩時間が不適切な値です',
        ];
    }

    public function withValidator(Validator $validator) {
        $validator->after(function (Validator $validator) {
            $errors = $validator->errors();

            if ($errors->has('clock_in') || $errors->has('clock_out')) {
                return;
            }

            $clockInInput = $this->input('clock_in');
            $clockOutInput = $this->input('clock_out');
            $clockIn = $clockInInput ? Carbon::createFromFormat('H:i', $clockInInput) : null;
            $clockOut = $clockOutInput ? Carbon::createFromFormat('H:i', $clockOutInput) : null;

            if ($clockIn && $clockOut && $clockIn->gt($clockOut)) {
                $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            foreach ($this->input('breaks', []) as $index => $row) {
                if ($errors->has("breaks.$index.start") || $errors->has("breaks.$index.end")) {
                    continue;
                }

                $startInput = $row['start'] ?? null;
                $endInput = $row['end'] ?? null;
                $start = $startInput ? Carbon::createFromFormat('H:i', $startInput) : null;
                $end = $endInput ? Carbon::createFromFormat('H:i', $endInput) : null;

                if ($start) {
                    if ($clockIn && $start->lt($clockIn)) {
                        $validator->errors()->add("breaks.$index.start", '休憩時間が不適切な値です');
                    }
                    if ($clockOut && $start->gt($clockOut)) {
                        $validator->errors()->add("breaks.$index.start", '休憩時間が不適切な値です');
                    }
                }

                if ($end && $clockOut && $end->gt($clockOut)) {
                    $validator->errors()->add("breaks.$index.end", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }
}
