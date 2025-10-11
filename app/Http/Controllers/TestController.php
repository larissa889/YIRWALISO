<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\App;

class TestController extends Controller
{
    public function testMiddleware()
    {
        $middleware = App::make(AdminMiddleware::class);
        return response()->json([
            'message' => 'Le middleware a été correctement résolu.',
            'middleware_class' => get_class($middleware)
        ]);
    }
}
