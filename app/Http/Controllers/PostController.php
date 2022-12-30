<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Post;
use App\Helpers\JwtAuth;

class PostController extends Controller
{
    /*
    public function pruebas(Request $request){
        return "Accion de pruebas de POST-CONTROLLER";
    }
     *
     */
    
    public function __construct() {
        
        // PARA APLICAR MIDDLEWARE EN METODOS CON RUTAS AUTOMATICAS
        
        $this->middleware('api.auth',['except' => [
            'index',
            'show',
            'getImage',
            'getPostsByUser',
            'getPostsByCategory']]);
    }
    
    public function index(){
        $posts= Post::all()->load('category');
        
        return response()->json([
            'code'      => 200,
            'status'    => 'success',
            'posts'     => $posts
        ]);
    }
    
    public function show($id){
        $post= Post::find($id)->load('category')
                             ->load('user');
        
        if(is_object($post)){
            $data= array(
                'code'      => 200,
                'status'    => 'success',
                'post'  => $post
            );
        }
        else{
            $data= array(
                'code'      => 404,
                'status'    => 'error',
                'message'  => 'Post no encontrado'
            );
        }
        
        return response()->json($data,$data['code']);
    }
    
    public function store(Request $request){
        
        //RECUPERAR DATOS
        $json=$request->input('json',null);
        $params= json_decode($json);
        $params_array= json_decode($json,true);
        
        if(!empty($params_array)){
            //CONSEGUIR USUARIO IDENTIFICADO
            $user= $this->getIdentity($request);
            
            //VALIDAR DATOS
            
            $validate= \Validator::make($params_array,[
                'title'         => 'required',
                'content'   => 'required',
                'category_id'   => 'required'
            ]);
            
            if($validate->fails()){
                $data= array(
                    'code'      => 404,
                    'status'    => 'error',
                    'message'  => 'Faltan datos'
                );
            }
            else {
                //GUARDAR POST
                $post= new Post();
                $post->user_id=$user->sub;
                $post->category_id= $params->category_id;
                $post->title= $params->title;
                $post->content= $params->content;
                $post->image= $params->image;
                $post->save();
                
                $data= array(
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Post creado con exito',
                    'post'      => $post
                );
            }        
            
        }
        else{
            $data= array(
                'code'      => 404,
                'status'    => 'error',
                'message'  => 'Datos enviados incorrectamente'
            );
        }
        
        //DEVOLVER RESPUESTA
        return response()->json($data,$data['code']);
    }
    
    public function update($id, Request $request){
        //RECOGER DATOS
        $json=$request->input('json',null);
        $params= json_decode($json);
        $params_array= json_decode($json,true); 

        
        if(!empty($params_array)){
            //VALIDAR DATOS
        
            $validate=\Validator::make($params_array,[
                'title'         => 'required',
                'content'       => 'required',
                'category_id'   => 'required'
            ]);

            if($validate->fails()){
                $data= array(
                    'code'      => 404,
                    'status'    => 'error',
                    'message'   => 'Error en la validacion de datos',
                    'errors'    => $validate->errors()
                );
            }
            else{
                //ELIMINAR LO QUE NO QUEREMOS ACTUALIZAR
                unset($params_array['id']);
                unset($params_array['user_id']);
                unset($params_array['created_at']);
                unset($params_array['user']);        
                
                        
                //CONSEGUIR USUARIO IDENTIFICADO
                $user= $this->getIdentity($request);

                //BUSCAR REGISTRO
                $post=Post::where('id',$id)
                    ->where('user_id',$user->sub)
                    ->first();                       

                if(!empty($post) && is_object($post)){
                    
                    //ACTUALIZAR EL REGISTRO
                    $post->update($params_array); 
                    $data= array(
                        'code'      => 200,
                        'status'    => 'success',
                        'message'   => 'Post actualizado correctamente',
                        'post'      => $post,
                        'changes'      =>  $params_array
                    );
                }
                else{
                    $data= array(
                        'code'      => 404,
                        'status'    => 'error',
                        'message'   => 'El post no existe'
                    );
                }
            }
        }
        else{
            $data= array(
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'Datos enviados incorrectamente'
            );
        }
        
        
        //DEVOLVER RESPUESTA
        return response()->json($data,$data['code']);
    }
    
    public function destroy($id,Request $request){
        
        //CONSEGUIR USUARIO IDENTIFICADO
        $user= $this->getIdentity($request);
        
        
        //CONSEGUIR EL POST
        $post=Post::where('id',$id)
                    ->where('user_id',$user->sub)
                    ->first();
        
        
        if(!empty($post)){
            //BORRARLO
            $post->delete();        

            //DEVOLVER RESPUESTA

            $data= array(
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Post eliminado correctamente',
                    'post'      => $post
                );
        }
        else {
            $data= array(
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'El post no existe'
            );
        }
        
        return response()->json($data,$data['code']);
    }
    
    private function getIdentity(Request $request){
        $jwtAuth= new JwtAuth();
        $token= $request->header('Authorization',null);
        $user= $jwtAuth->checkToken($token,true);
        
        return $user;
    }
    
    public function upload(Request $request){
        //RECOGER IMAGEN DE LA PETICION
        $image= $request->file('file0');
        
        //VALIDAR IMAGEN
        $validate= \Validator::make($request->all(),[
            'file0'     => 'required|image|mimes:jpg,jpeg,gif,png'
        ]);
        
        //GUARDAR IMAGEN EN DISCO
        
        if(!$image || $validate->fails()){
            $data= array(
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'Error al subir la imagen'
            );
        }
        else{
            $image_name= time().$image->getClientOriginalName();
            \Storage::disk('images')->put($image_name,\File::get($image));
            $data= array(
                'code'      => 200,
                'status'    => 'success',
                'image'     => $image_name
            );
        }
        
        //DEVOLVER RESPUESTA
        return response()->json($data,$data['code']);
    }
    
    public function getImage($filename){
        //COMPROBAR SI EXISTE
        $isset= \Storage::disk('images')->exists($filename);
        
        if($isset){
            //CONSEGUIR IMAGEN
            $file= \Storage::disk('images')->get($filename);
            //DEVOLVER IMAGEN
            return new Response($file,200);
        }
        else {
            //MOSTRAR ERROR
            $data= array(
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'La imagen no existe'
            );
            return response()->json($data,$data['code']);
        }         
    }
    
    public function getPostsByCategory($id){
        $posts= Post::where('category_id',$id)->get();
        
        if(count($posts)>0){
            $data= array(
                'code'      => 200,
                'status'    => 'success',
                'posts'     => $posts
                );
        }
        else {
            $data= array(
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'No existen posts de esa categoria'
            );
        }
        
        return response()->json($data,$data['code']);
    }
    
    public function getPostsByUser($id){
        $posts= Post::where('user_id',$id)->get();
        
        if(count($posts)>0){
            $data= array(
                'code'      => 200,
                'status'    => 'success',
                'posts'     => $posts
                );
        }
        else {
            $data= array(
                'code'      => 200,
                'status'    => 'success',
                'message'   => 'No existen posts de ese usuario'
            );
        }
        
        return response()->json($data,$data['code']);
    }
}
