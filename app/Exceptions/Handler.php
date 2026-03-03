<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Handle CSRF token mismatch (419) for exam anti-cheat
        if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
            // Check if it's from exam route
            if ($request->is('exam/*') || $request->is('*/anti-cheat')) {
                \Log::warning('CSRF token mismatch on exam route', [
                    'url' => $request->fullUrl(),
                    'user_id' => auth()->id(),
                ]);
                
                // Show anti-cheat violation page
                return response()->view('exam.anti-cheat-violation', [], 419);
            }
            
            // For other routes, redirect to login
            return redirect()->route('login')
                ->with('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
        }

        return parent::render($request, $exception);
    }
}
