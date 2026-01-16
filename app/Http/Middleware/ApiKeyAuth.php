<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKeyValue = $request->header('X-Api-Key');

        if (!$apiKeyValue) {
            return response()->json([
                'error' => 'API key is required',
                'message' => 'Please provide X-Api-Key header'
            ], 401);
        }

        $apiKey = ApiKey::where('key', $apiKeyValue)
            ->where('is_active', true)
            ->with('project')
            ->first();

        if (!$apiKey) {
            return response()->json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is invalid or inactive'
            ], 401);
        }

        if (!$apiKey->project->isActive()) {
            return response()->json([
                'error' => 'Project inactive',
                'message' => 'The project associated with this API key is inactive'
            ], 403);
        }

        // Update last used timestamp asynchronously
        $apiKey->markAsUsed();

        // Attach project to request
        $request->merge(['project' => $apiKey->project]);
        $request->attributes->set('project', $apiKey->project);

        return $next($request);
    }
}
