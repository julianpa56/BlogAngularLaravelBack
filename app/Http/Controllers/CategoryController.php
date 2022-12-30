<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Category;

class CategoryController extends Controller
{
    /*
    public function pruebas(Request $request){
        return "Accion de pruebas de CATEGORY-CONTROLLER";
    }
    */
    
    public function __construct() {
        
        // PARA APLICAR MIDDLEWARE EN METODOS CON RUTAS AUTOMATICAS
        
        $this->middleware('api.auth',['except' => ['index','show']]);
    }
    
    public function index(){
        $categories= Category::all();
        
        return response()->json([
            'code'      => 200,
            'status'    => 'success',
            'categories'=> $categories
        ]);
    }
    
    public function show($id){
        $category= Category::find($id);
        
        if (is_object($category)){
            $data= array(
                'code'      => 200,
                'status'    => 'success',
                'category'  => $category
            );
        }
        else {
            $data= array(
                'code'      => 404,
                'status'    => 'error',
                'message'   => 'Categoria no encontrada'
            );
        }
              
        return response()->json($data,$data['code']);
    }
    
    public function store(Request $request){
        // RECOGER LOS DATOS
        
        $json= $request->input('json',null);
        $params_array = json_decode($json,true);
        

        if(!empty($params_array)){
            // VALIDAR DATOS
        
            $validate = \Validator::make($params_array,[
                'name'  => 'required|unique:categories'
            ]);
        
            // GUARDAR CATEGORIA EN CASO QUE SEA CORRECTO
            if($validate->fails()){
                $data= array(
                    'code'      => 404,
                    'status'    => 'error',
                    'message'   => 'No se guardo la categoria'
                );   
            }
            else {
                $category= new Category();
                $category->name = $params_array['name'];
                $category->save();
                $data= array(
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Categoria guardada con exito',
                    'category'  => $category
                );   
            }
        }
        else{
            $data= array(
                    'code'      => 404,
                    'status'    => 'error',
                    'message'   => 'No se enviaron categorias'
                ); 
        }
        
        
        // DEVOLVER RESPUESTA
        
        return response()->json($data,$data['code']);
    }
    
    public function update($id,Request $request){
        //RECOGER LOS DATOS
        
        $json= $request->input('json',null);
        $params_array= json_decode($json,true);
        
        if(!empty($params_array)){
            //VALIDAR DATOS
            
            $validate= \Validator::make($params_array,[
                'name'  => 'required'
            ]);
            
            //QUITAR LO QUE NO QUIERO ACTUALIZAR
            
            unset($params_array['id']);
            unset($params_array['created_at']);
            
            //ACTUALIZAR EL REGISTRO
            
            $category= Category::where('id',$id)->update($params_array);
            
            $data= array(
                    'code'      => 200,
                    'status'    => 'success',
                    'message'   => 'Categoria actualizada con exito',
                    'category'  => $params_array
                ); 
            
        }
        
        else{
            $data= array(
                    'code'      => 404,
                    'status'    => 'error',
                    'message'   => 'No se enviaron categorias'
                ); 
        }

        
        //DEVOLVER RESPUESTA
        return response()->json($data,$data['code']);
               
    }
}
