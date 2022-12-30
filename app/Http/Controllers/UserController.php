<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;

class UserController extends Controller
{
    public function pruebas(Request $request){
        $users= User::all();
        return json_decode($users,true);
    }
    
    public function register(Request $request){
        
        //Recoger los datos de la request
        $json = $request->input('json',null);
        $params = json_decode($json); // OBJETO 
        $params_array = json_decode($json,true); // ARREGLO
        
        if(!empty($params) && !empty($params_array)){
                // Limpiar datos

            $params_array = array_map('trim', $params_array);

            //Validar datos

            $validate= \Validator::make($params_array,[
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users',
                'password'  => 'required'
            ]);

            if ($validate->fails()){
                
                // LOS DATOS NO PASARON LA VALIDACION
                
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El usuario no se ha creado',
                    'errors' => $validate->errors()
                    );

            }
            else{
                
                // LOS DATOS PASARON LA VALIDACION
                
                //Cifrar contraseña

                $pwd= hash('sha256',$params->password);

                //Comprobar si el usuario existe ya
                // (en la validacion del campo dentro de los parametros con 'unique:[tabla de la db]'

                //Crear usuario

                $user = new User();
                $user->name= $params_array['name'];
                $user->surname= $params_array['surname'];
                $user->email= $params_array['email'];
                $user->password= $pwd;
                $user->role= 'ROLE_USER';
                $user->image= array_key_exists('image',$params_array)?$params_array['image']:null;
                $user->description= array_key_exists('description',$params_array)?$params_array['description']:null;

                $user->save();
                
                                
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El usuario se ha creado',
                    'user' => $user
                    );

            }
        }
        else {
            // ERROR EN LOS DATOS
            
            $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Los datos enviados no son correctos',
                    );
        }
        

        
        return response()->json($data,$data['code']);
    }
    
    public function login(Request $request){
        
        $jwtAuth= new \JwtAuth();
        
        //RECIBIR DATOS DEL POST
        $json= $request->input('json',null);
        $params = json_decode($json);
        $params_array= json_decode($json,true);
        
        //VALIDAR ESOS DATOS
        
        $validate= \Validator::make($params_array,[
                'email'     => 'required|email',
                'password'  => 'required'
            ]);

            if ($validate->fails()){                
                // LOS DATOS NO PASARON LA VALIDACION
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El usuario no se ha podido identificar',
                    'errors' => $validate->errors()
                    );

            }
            else{
                //CIFRAR CONTRASEÑA
                $pwd = hash('sha256',$params->password);
                //DEVOLVER TOKEN O DATOS
                $resp=null;
                if(!empty($params->gettoken)){
                    //DEVUELVE DATOS
                    $resp=$jwtAuth->signup($params->email,$pwd,true);
                }
                else{
                    //DEVUELVE TOKEN
                    $resp=$jwtAuth->signup($params->email,$pwd);
                }
            }
        
        return response()->json($resp,200);
     
    }
        
        public function update(Request $request){
            
            //COMPROBAR SI EL USUARIO ESTA IDENTIFICADO
            $token= $request->header('Authorization');
            $jwtAuth= new \JwtAuth();
            $checkToken= $jwtAuth->checkToken($token); // Si envio true adicional devuelve los datos del decode
            
            //RECOGER LOS DATOS POR POST
            $json= $request->input('json',null);
            $params_array= json_decode($json,true);
            
            if($checkToken && !empty($params_array)){
                //ACTUALIZAMOS EL USUARIO            
                
                //SACAR USUARIO IDENTIFICADO
                
                $user= $jwtAuth->checkToken($token,true);
                
                //VALIDAR DATOS
                
                $validate= \Validator::make($params_array,[
                    'name'      => 'required|alpha',
                    'surname'   => 'required|alpha',
                    'email'     => 'required|email|unique:users'.$user->sub
                ]);
                
                //QUITAR LOS CAMPOS QUE NO QUIERO ACTUALIZAR
                
                unset($params_array['id']);
                unset($params_array['role']);
                unset($params_array['password']);
                unset($params_array['created_at']);
                unset($params_array['remember_token']);
                
                //ACTUALIZAR USUARIO EN DB
                
                $user_update= User::where('id',$user->sub)->update($params_array);
                
                //DEVOLVER ARRAY CON RESULTADO
                $data=array(
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'El usuario se ha actualizado',
                    'user' => $user,
                    'changes' => $params_array
                );
            }
            else{
                //ERROR EN EL CHECKOUT
                $data=array(
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'El usuario no esta identificado'
                );
            }
            
            return response()->json($data,$data['code']);
        }
        
        public function upload(Request $request){
            
            //RECOGER LOS DATOS
            
            $image=$request->file('file0');
            
            //VALIDAR IMAGEN
            $validate= \Validator::make($request->all(),[
                'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
            ]);
                        
            //GUARDAR IMAGEN
            
            if(!$image || $validate->fails()){ //SI IMAGE ES FALSE 
                $data=array(
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Error al subir imagen',
                    'errors' => $validate->errors()
                );
            }
            else
            {
                $image_name= time().$image->getClientOriginalName();
                \Storage::disk('users')->put($image_name, \File::get($image));
                
                $data= array(
                    'image'   => $image_name,
                    'code'    => 200,
                    'status'  => 'success'
                );
            }

            
            //DEVOLVER RESULTADO         
            
            return response()->json($data, $data['code']);
        }
        
        public function getImage($filename){
            
            $isset = \Storage::disk('users')->exists($filename);
            if($isset){
                $file= \Storage::disk('users')->get($filename);
                return new Response($file,200);
            }
            else
            {
               $data= array(
                    'code'    => 404,
                    'status'  => 'error',
                    'message' => 'La imagen no existe'
                );
                return response()->json($data,$data['code']);
            }

        }
        
        public function detail($id){
            $user= User::find($id);
            
            if (is_object($user)){
                $data= array(
                    'code'    => 200,
                    'status'  => 'success',
                    'user' => $user
                );
            }
            else{
                $data= array(
                    'code'    => 404,
                    'status'  => 'error',
                    'message' => 'Usuario no encontrado'
                );
            }
            
            
            return response()->json($data,$data['code']);
        }
}
