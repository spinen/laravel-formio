<?php

namespace Spinen\Formio\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spinen\Formio\Client as Formio;
use Spinen\Formio\Http\Resources\FormioJwt;

/**
 * Class FormioController
 */
class FormioController extends Controller
{
    /**
     * The JWT for Formio for the logged in user
     *
     * @throws Exception
     */
    public function jwt(Request $request, Formio $formio): FormioJwt
    {
        return FormioJwt::make($formio->sso($request->user())->token);
    }
}
