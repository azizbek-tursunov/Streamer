<?php

namespace App\Http\Requests;

class UpdateCameraRequest extends StoreCameraRequest
{
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        // Don't overwrite password with empty string on update
        if (is_array($data) && array_key_exists('password', $data) && empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
