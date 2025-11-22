<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Permite acesso à página de login e rotas de autenticação do Filament
        if ($request->routeIs('filament.admin.auth.*') || 
            $request->is('admin/login') || 
            $request->is('admin/logout')) {
            return $next($request);
        }

        // Permite requisições do Livewire (usado pelo Filament)
        if ($request->is('livewire/*') || 
            $request->hasHeader('X-Livewire') ||
            $request->hasHeader('X-Inertia')) {
            return $next($request);
        }

        // Permite assets estáticos (CSS, JS, fonts, imagens, etc.)
        $path = $request->path();
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $allowedExtensions = ['css', 'js', 'woff', 'woff2', 'ttf', 'eot', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'webp'];
        
        if (in_array($extension, $allowedExtensions) || 
            str_contains($path, '/assets/') ||
            str_contains($path, '/css/') ||
            str_contains($path, '/js/') ||
            str_contains($path, '/fonts/') ||
            str_contains($path, '/build/')) {
            return $next($request);
        }

        // Se não está autenticado, deixa o Filament lidar com o redirecionamento
        if (!auth()->check()) {
            return $next($request);
        }

        // Verifica se é admin apenas para rotas autenticadas
        $user = auth()->user();
        if (!$user) {
            return $next($request);
        }

        // Verifica se o usuário é admin
        // Usa fresh() apenas se necessário para evitar problemas de cache
        $isAdmin = $user->is_admin ?? false;
        
        if (!$isAdmin) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => 'Acesso negado. Apenas administradores podem acessar este painel.'], 403);
            }
            abort(403, 'Acesso negado. Apenas administradores podem acessar este painel.');
        }

        return $next($request);
    }
}

