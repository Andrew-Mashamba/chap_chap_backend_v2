<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ApiLogger
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();
        $startTime = microtime(true);

        // Store request ID for use in response logging
        $request->attributes->set('request_id', $requestId);

        // Log incoming request
        $this->logRequest($request, $requestId);

        try {
            $response = $next($request);

            // Log successful response
            $this->logResponse($request, $response, $requestId, $startTime);

            return $response;
        } catch (\Throwable $e) {
            // Log error
            $this->logError($request, $e, $requestId, $startTime);

            throw $e;
        }
    }

    /**
     * Log incoming request details.
     */
    private function logRequest(Request $request, string $requestId): void
    {
        $logData = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
        ];

        // Add request body (sanitized)
        $body = $request->except($this->getSensitiveFields());
        if (!empty($body)) {
            $logData['body'] = $this->truncateData($body);
        }

        // Add query parameters
        $query = $request->query();
        if (!empty($query)) {
            $logData['query'] = $query;
        }

        Log::channel('api')->info('📥 API Request', $logData);
    }

    /**
     * Log response details.
     */
    private function logResponse(Request $request, Response $response, string $requestId, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $statusCode = $response->getStatusCode();

        $logData = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $statusCode,
            'duration_ms' => $duration,
            'user_id' => $request->user()?->id,
        ];

        // Add response body for non-2xx responses or if small enough
        $content = $response->getContent();
        if ($statusCode >= 400 || strlen($content) < 2000) {
            $decoded = json_decode($content, true);
            if ($decoded !== null) {
                $logData['response'] = $this->truncateData($decoded);
            }
        }

        $emoji = $this->getStatusEmoji($statusCode);
        $level = $statusCode >= 400 ? 'warning' : 'info';

        Log::channel('api')->{$level}("{$emoji} API Response", $logData);
    }

    /**
     * Log error details.
     */
    private function logError(Request $request, \Throwable $e, string $requestId, float $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $logData = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'duration_ms' => $duration,
            'user_id' => $request->user()?->id,
            'error' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        // Add stack trace for non-validation errors
        if (!$e instanceof \Illuminate\Validation\ValidationException) {
            $logData['trace'] = collect(explode("\n", $e->getTraceAsString()))
                ->take(10)
                ->implode("\n");
        } else {
            $logData['validation_errors'] = $e->errors();
        }

        Log::channel('api')->error('❌ API Error', $logData);
    }

    /**
     * Get fields that should not be logged.
     */
    private function getSensitiveFields(): array
    {
        return [
            'password',
            'password_confirmation',
            'pin',
            'pin_confirmation',
            'token',
            'access_token',
            'refresh_token',
            'secret',
            'api_key',
            'credit_card',
            'card_number',
            'cvv',
            'ssn',
        ];
    }

    /**
     * Truncate data to prevent huge log entries.
     */
    private function truncateData(array $data, int $maxDepth = 3, int $currentDepth = 0): array
    {
        if ($currentDepth >= $maxDepth) {
            return ['...' => 'truncated'];
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->truncateData($value, $maxDepth, $currentDepth + 1);
            } elseif (is_string($value) && strlen($value) > 500) {
                $result[$key] = substr($value, 0, 500) . '... [truncated]';
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Get emoji based on status code.
     */
    private function getStatusEmoji(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 500 => '🔥',
            $statusCode >= 400 => '⚠️',
            $statusCode >= 300 => '↪️',
            $statusCode >= 200 => '✅',
            default => '📤',
        };
    }
}
