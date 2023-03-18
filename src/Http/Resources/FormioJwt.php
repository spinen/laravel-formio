<?php

namespace Spinen\Formio\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FormioJwt extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'expires_at' => $this->expires_at->toIso8601String(),
            'jwt' => $this->jwt,
        ];
    }
}
