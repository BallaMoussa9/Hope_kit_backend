<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** Captures authenticated API write requests as an additional audit layer. */
class AuditApiMutations
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        try {
            if ($request->user() && (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true) || ($request->isMethod('GET') && preg_match('#^api/(dashboard/(orders|kits|deliveries|documents|payments)|admin/(users|roles|audit-logs))#', $request->path())))
                && $response->getStatusCode() < 400
                && ! $request->is('api/login', 'api/logout', 'api/mobile/login', 'api/mobile/logout*', 'api/ivr/webhook/*', 'api/admin/audit-logs', 'api/dashboard/orders/*/audit-logs')) {
                $routeParams = $request->route()?->parameters() ?? [];
                $subject = collect($routeParams)->first(fn ($v) => is_object($v) && isset($v->id));
                $params = collect($routeParams)->map(function ($v) {
                    return is_object($v) && isset($v->id) ? ['id' => $v->id, 'type' => class_basename($v)] : (is_scalar($v) ? $v : null);
                })->all();
                $payload = $request->isMethod('GET') ? $request->query() : $request->except(['password','password_confirmation','current_password','token','access_token','authorization','secret','client_secret','api_key','otp','code']);
                array_walk_recursive($payload, function (&$value, $key) {
                    if (preg_match('/password|token|secret|authorization|api.?key|otp|phone|email|first_name|last_name|address|birth|pregnan|medical|beneficiar.*name/i', (string) $key)) $value = '[REDACTED]';
                    if (preg_match('/^(notes|description|clinical_notes)$/i', (string) $key)) $value = '[OMITTED]';
                    if (is_string($value) && strlen($value) > 2000) $value = substr($value, 0, 2000).'…';
                });
                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'actor_name' => $request->user()->name,
                    'actor_email' => $request->user()->email,
                    'action' => 'api.'.($request->isMethod('GET') ? 'view' : Str::lower($request->method())).'.'.(optional($request->route())->getName() ?: trim($request->path(), '/')),
                    'auditable_type' => $subject ? $subject::class : null,
                    'auditable_id' => $subject->id ?? null,
                    'new_values' => ['route_parameters' => $params, 'input' => $payload, 'http_status' => $response->getStatusCode(), 'request_id' => $request->headers->get('X-Request-ID')],
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                ]);
            }
        } catch (\Throwable $e) { report($e); }
        return $response;
    }
}
