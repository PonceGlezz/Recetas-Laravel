<?php

namespace App\Http\Controllers;

use App\Receta;
use App\CategoriaReceta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;

class RecetaController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth', ['except' => 'show']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //user()->recetas->dd();
        $recetas = auth()->user()->recetas;
        return view('recetas.index')->with('recetas',$recetas);
      
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //obtener categorias sin modelo
       // $categorias = DB::table('categoria_receta')->get()->pluck('nombre', 'id');

        //Con modelo
        $categorias = CategoriaReceta::all(['id', 'nombre']);

        return view('recetas.create')->with('categorias',$categorias);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {   
        

        $data = request()->validate([
            'titulo'=> 'required|min:6',
            'categoria'=>'required',
            'preparacion'=>'required',
            'ingredientes'=>'required',
            //'imagen' => 'required|image'

        ]);

        //Obtener la ruta de la imagen
        $ruta_imagen = $request['imagen']->store('upload-recetas','public');
        
       // Resize de la imagen
       $img = Image::make(public_path("storage/{$ruta_imagen}"))->fit(1000,500);
       $img->save();
       
        //Almacenar en la bd(sin modelo)
        /*DB::table('recetas')->insert([
            'titulo'=> $data['titulo'],
            'preparacion' => $data['preparacion'],
            'ingredientes' => $data['ingredientes'],
            'imagen' => $ruta_imagen,
            'user_id' => Auth::user()->id,
            'categoria_id'=>$data['categoria']

        ]);*/
        //Almacenar en la bd con modelo 
        auth()->user()->recetas()->create([
            'titulo' => $data['titulo'],
            'preparacion' => $data['preparacion'],
            'ingredientes' => $data['ingredientes'],
            'imagen' => $ruta_imagen,
            'categoria_id'=>$data['categoria']
        ]);

        //Rediccionar
        return redirect()->action('RecetaController@index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Receta  $receta
     * @return \Illuminate\Http\Response
     */
    public function show(Receta $receta)
    {
        //definir que template se va a cargar
        return view('recetas.show', compact('receta'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Receta  $receta
     * @return \Illuminate\Http\Response
     */
    public function edit(Receta $receta)
    {
        $categorias = CategoriaReceta::all(['id', 'nombre']);

        return view('recetas.edit', compact('categorias', 'receta'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Receta  $receta
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Receta $receta)
    {
        //Revisar el policy 
        $this->authorize('update',$receta);

       $data = $request->validate([
            'titulo'=> 'required|min:6',
            'preparacion'=>'required',
            'ingredientes'=>'required',
            'categoria'=>'required',
       ]);

       //asignar los valores 
       $receta->titulo = $data['titulo'];
       $receta->preparacion = $data['preparacion'];
       $receta->ingredientes = $data['ingredientes'];
       $receta->categoria_id = $data['categoria'];


       //Si el usuario sube una nueva imagen la
       if(request('imagen')){
        $ruta_imagen = $request['imagen']->store('upload-recetas','public');
        // Resize de la imagen
        $img = Image::make(public_path("storage/{$ruta_imagen}"))->fit(1000,500);
        $img->save();

        //Asignar al objeto
        $receta->imagen = $ruta_imagen;
       }
       $receta->save();

       //Redireccionar 
       return redirect()->action('RecetaController@index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Receta  $receta
     * @return \Illuminate\Http\Response
     */
    public function destroy(Receta $receta)
    {
        //Ejecutar el Policy
        $this->authorize('delete',$receta);

        //Eliminar la receta
        $receta->delete();
        
        return redirect()->action('RecetaController@index');
    }
}
