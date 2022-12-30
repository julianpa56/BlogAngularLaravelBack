<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        
        //COMPROBAR SI EL USUARIO ESTA IDENTIFICADO
        $token= $request->header('Authorization');
        $jwtAuth= new \JwtAuth();
        $checkToken= $jwtAuth->checkToken($token); // Si envio true adicional devuelve los datos del decode
        
        if($checkToken){
            return $next($request);
        }
        else{
            $data=array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no esta registrado'
            );
            return response()->json($data,$data['code']);
        }
                
    }
}
