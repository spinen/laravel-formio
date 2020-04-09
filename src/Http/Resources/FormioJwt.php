<?php

namespace Spinen\Formio\Http\Resources;

//use Illuminate\Http\Resources\Json\JsonResource;

// TODO: When dropping support of Laravel 5.5, remove this if/else
if (class_exists('Illuminate\Http\Resources\Json\JsonResource')) {
    class JsonResource extends \Illuminate\Http\Resources\Json\JsonResource
    {

    }
} else {
    // NOTE: Only here to support Laravel 5.5
    class JsonResource extends \Illuminate\Http\Resources\Json\Resource
    {

    }
}

class FormioJwt extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'expires_at' => $this->expires_at->toIso8601String(),
            'jwt'        => $this->jwt,
        ];
    }
}
