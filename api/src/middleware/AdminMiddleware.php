<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class AdminMiddleware
{
    public static function handle(Request $request): ?Response
    {
        return RoleMiddleware::handle($request, 'admin');
    }
}
