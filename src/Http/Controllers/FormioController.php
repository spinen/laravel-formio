<?php

namespace Spinen\Formio\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spinen\Formio\Client as Formio;
use Spinen\Formio\Http\Resources\FormioJwt;

/**
 * Class FormioController
 *
 * @package Spinen\Formio\Http\Controllers
 */
class FormioController extends Controller
{
    /**
     * The JWT for Formio for the logged in user
     *
     * @param Request $request
     * @param Formio $formio
     *
     * @return FormioJwt
     * @throws Exception
     */
    public function jwt(Request $request, Formio $formio)
    {
        return FormioJwt::make($formio->sso($request->user())->token);
    }
}
