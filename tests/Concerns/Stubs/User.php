<?php

namespace Spinen\Formio\Concerns\Stubs;

use Spinen\Formio\Concerns\HasForms;

class User
{
    use HasForms;

    public $attributes = [];
    public $fillable = [];
    public $hidden = [];
}
