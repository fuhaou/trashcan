<?php
/**
 * Created by Kieu Trung.
 * User: trung.kieu@epsilo.io
 * Date: 31/12/2020
 * Time: 16:20.
 */

namespace App\Http\Middleware;

use App\Traits\CommonTrait;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Str;
use App\Repositories\Sql\UserRepository;

class LoggingMiddleware
{
    use CommonTrait;

    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     * (Web server FastCGI only).
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     * @return void
     */
    public function terminate($request, $response)
    {
        $fullUrl = $request->fullUrl();

        // exclude health-check api
        if (Str::endsWith($fullUrl, 'api/health-check')) {
            return;
        }
        try {
            $duration = microtime(true) - LARAVEL_START;
            $clientIp = FacadesRequest::ip();
            $method = $request->getMethod();
            // POST with json body will be in both input() and getContent(), need to remove 'password' field there
            $param = $request->input();
            $requestContent = $request->getContent();
            $user = null;
            if (Str::endsWith($fullUrl, 'oauth/token')) {
                if (!is_null($param)) {
                    if (array_key_exists('password', $param)) {
                        unset($param['password']);
                    }
                    if (array_key_exists('client_secret', $param)) {
                        unset($param['client_secret']);
                    }
                }

                $requestContent = json_decode($requestContent, true);
                if (!is_null($requestContent)) {
                    if (array_key_exists('password', $requestContent)) {
                        unset($requestContent['password']);
                    }
                    if (array_key_exists('client_secret', $requestContent)) {
                        unset($requestContent['client_secret']);
                    }
                }

                // record last login
                if (!is_null($param)) {
                    if (array_key_exists('username', $param) && array_key_exists('grant_type', $param)) {
                        if ($param['grant_type'] == 'password') {
                            (new UserRepository())->updateLastLogin(
                                $param['username'],
                                $request->header('X-Client-User-Agent', ''),
                                $request->header('X-Client-Ip', null),
                            );
                        }
                    }
                }
            } elseif (Str::endsWith($fullUrl, 'shop/link-shop')
                || Str::endsWith($fullUrl, 'update-credential-shop')
                || Str::endsWith($fullUrl, 'shop/add-marketing-credential')
                || Str::endsWith($fullUrl, 'register-account')) {
                if (!is_null($param)) {
                    if (array_key_exists('password', $param)) {
                        unset($param['password']);
                    }
                }

                $requestContent = json_decode($requestContent, true);
                if (!is_null($requestContent)) {
                    if (array_key_exists('password', $requestContent)) {
                        unset($requestContent['password']);
                    }
                }
            } else {
                $user = auth('api')->user();
                if (!is_null($user)) {
                    $user = $user->getAuthIdentifier();
                }
            }
            $timestamp = time();
            $dateTime = date('Y-m-d H:i:s', $timestamp);

            $logInfo = [
                'request' => [
                    'method' => $method,
                    'url' => $fullUrl,
                    'param' => $param,
                    'header' => getallheaders(), // array of string
                    'internal_caller_ip' => $clientIp,
                    'client_ip' => $request->header('X-Client-Ip', ''),
                    'server_ip' => get_public_ip(),
                    'content' => $requestContent,
                ],
                'loggedInUser' => $user, // null or user id
                'response' => $response->getContent(),
                'date' => $dateTime,
                'time' => $timestamp,
                'duration' => $duration,
            ];
            if ($response->getStatusCode() == 200) {
                $this->logInfo('Log from http request', $logInfo, 1, 'Epsilo_log_http_request');
            } else {
                $this->logInfo('error from http request', $logInfo, 1, 'END_USER_ERROR');
            }
        } catch (Exception $e) {
            $this->logError('error while writing log', [
                'request' => [
                    'method' => $request->getMethod(),
                    'url' => $request->fullUrl(),
                    'param' => $param,
                    'header' => getallheaders(), // array of string
                    'internal_caller_ip' => $clientIp,
                    'client_ip' => $request->header('X-Client-Ip', ''),
                    'server_ip' => get_public_ip(),
                ],
                'response' => $response->getContent(),
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 1, 'END_USER_ERROR');
        }
    }
}
