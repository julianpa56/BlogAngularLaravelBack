<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class JwtAuth{
    
    public $key;
    
    public function __construct() {
        $this->key = 'clave_de_encriptacion_JWT_2022';
    }
    
    public function signup($email,$password,$getToken=null){
        
    $signup=false;
    //BUSCAR SI EXISTE EL USUARIO CON SUS CREDENCIALES
    
    $user= User::where([
        'email' => $email,
        'password' => $password
    ])->first();
        
    //COMPROBAR SI SON CORRECTAS
    
    if (is_object($user)){
        $signup= true;
    }
        

    //GENERAR EL TOKEN CON LOS DATOS DEL USUARIO IDENTIFICADO
    
    if ($signup){
        $token= array(
            'sub' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'surname' => $user->surname,
            'description' => $user->description,
            'image' => $user->image,
            'iat' => time(),
            'exp' => time() + (24*60*60)
        );
        
        $jwt = JWT::encode($token,$this->key,'HS256');          
        $decoded = JWT::decode($jwt, new Key($this->key,'HS256'));
        
        if (is_null($getToken)){
            $data= $jwt;
        }
        else {
            $data= $decoded;
        }
    }
    else{
        $data= array(
            'status' => 'error',
            'message' => 'Login incorrecto'
        );
    }
   
    
    //DEVOLVER LOS DATOS DECODIFICADOS O EL TOKEN EN FUNCION DE UN PARAMETRO
    
        return $data;
    }
    
    public function checkToken($jwt,$getIdentity=false){
        $auth=false;      
        try{
            $jwt= \str_replace('"','', $jwt);
            $decoded= JWT::decode($jwt, new Key($this->key,'HS256'));
        }
        catch(\UnexpectedValueException $e){
            $auth=false;
        }
        catch(\DomainException $e){
            $auth=false;
        }
        if (!empty($decoded)&& is_object($decoded) && isset($decoded->sub)){
            $auth=true;
        }
        else{
            $auth=false;
        }
        
        if($getIdentity){
            return $decoded;
        }
        return $auth;
    }
        
    
}

