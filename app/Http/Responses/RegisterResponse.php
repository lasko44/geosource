<?php

namespace App\Http\Responses;

use App\Services\ExperimentService;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the redirect after user registration, including experiment conversion tracking.
 */
class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false], 201);
        }

        // Record experiment conversion
        $visitorId = $request->cookie('ab_visitor');
        if ($visitorId) {
            app(ExperimentService::class)->recordConversion($visitorId, $request->user());
        }

        return redirect()->intended(config('fortify.home'));
    }
}
