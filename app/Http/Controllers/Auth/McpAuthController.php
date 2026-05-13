<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles browser-based MCP server authorization.
 *
 * Flow: MCP server opens browser → user logs in → authorizes → redirected
 * back to localhost with a Sanctum token.
 */
class McpAuthController extends Controller
{
    /**
     * Show the MCP authorization page.
     */
    public function show(Request $request): Response
    {
        $request->validate([
            'port' => 'required|integer|min:1024|max:65535',
            'state' => 'required|string|max:128',
        ]);

        return Inertia::render('auth/McpAuthorize', [
            'port' => (int) $request->input('port'),
            'state' => $request->input('state'),
        ]);
    }

    /**
     * Create a Sanctum token and redirect back to the MCP server's local callback.
     */
    public function store(Request $request): \Illuminate\Http\Response|RedirectResponse
    {
        $request->validate([
            'port' => 'required|integer|min:1024|max:65535',
            'state' => 'required|string|max:128',
        ]);

        $user = $request->user();
        $port = (int) $request->input('port');
        $state = $request->input('state');

        // Revoke any existing MCP tokens to keep it clean
        $user->tokens()->where('name', 'mcp-server')->delete();

        $token = $user->createToken('mcp-server');

        $callbackUrl = "http://127.0.0.1:{$port}/callback?" . http_build_query([
            'token' => $token->plainTextToken,
            'state' => $state,
        ]);

        // Inertia intercepts standard redirects, so for external URLs
        // we return a 409 with the redirect location header, which tells
        // Inertia to do a full page visit to the external URL.
        if ($request->header('X-Inertia')) {
            return Inertia::location($callbackUrl);
        }

        return redirect()->away($callbackUrl);
    }
}
