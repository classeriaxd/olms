<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MaterialsSubject extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    protected $controller;
    protected $routeName;

    public function __construct(array $attributes = array())
    {

        $routeArray = app('request')->route()->getAction();
        $controllerAction = class_basename($routeArray['controller']);
        list($this -> controller, $action) = explode('@', $controllerAction);
        $this -> routeName = Route::currentRouteName();
    }


    public function index()
    {
        //

        if(auth::check() == true){
            $user_permission = db::table('user_links as a')
                ->join('user_permission as b', 'a.id', '=' , 'b.link_id')
                ->where('b.user_id', auth::user()->id)
                ->where('b.status' , '=' , 'On')
                ->Where('a.slug_name', 'LIKE' , '%'.$this -> controller.'%')
                ->Where('link_id', '!=', 0)
                ->get();

            if($user_permission -> contains('slug_name', $this -> routeName)){
                return view('Materials_Subject.list')
                    ->with('user_perm', $user_permission);
            }else{
                return redirect()->route('Dashboard');
            }
        }else{
            return redirect()->route('user_login_page');
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //

        extract($request->all());

        $data_inserted = [
            'subject_name' => $subject_name,
            'created_at' => Carbon::now()
        ];
        $data_updated = [
            'subject_name' => $subject_name,
            'updated_at' => Carbon::now()
        ];

        if($id != ''){

            db::table('materials_subject')
                ->where('id', $id)
                ->update($data_updated);

            return response()->json(['status' => 'success' , 'message' => "Materials Subject Data is successfully updated"]);

        }else{

            db::table('materials_subject')
                ->insert($data_inserted);

            return response()->json(['status' => 'success' , 'message' => "Materials Subject Data is successfully inserted"]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        $data = db::table('materials_subject')
            ->where('id', $id)
            ->get();

        return response()->json($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function MaterialsSubjectDatatables(){

        $data = DB::table('materials_subject')
            ->select('id as id','subject_name')
            ->where('status', 1);

        $user_permission = db::table('user_links as a')
            ->join('user_permission as b', 'a.id', '=' , 'b.link_id')
            ->where('b.user_id', auth::user()->id)
            ->where('b.status' , '=' , 'On')
            ->where('a.slug_name', 'LIKE' , '%'.$this->controller.'%')
            ->where('b.link_id' , '!=' , 0)
            ->get();

        if($user_permission -> contains('slug_name', 'MaterialsSubject.show') && $user_permission -> contains('slug_name', 'MaterialsSubjectDelete')) {
            return DataTables::query($data)
                ->addColumn('action', function ($row) {
                    $btn = '<td></d></tr><div class="btn-group-vertical">
                                <a type="button" class="btn btn-info data-edit" id="data-edit" data-id=' . $row->id . ' ><span class="fa fa-edit">&nbsp;&nbsp;</span>Edit</a>
                                <a type="button" class="btn btn-warning data-delete" id="data-delete" data-id=' . $row->id . ' ><span class="fa fa-trash">&nbsp;&nbsp;</span>Delete</a>
                            </div></td>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->toJson();
        }elseif($user_permission -> contains('slug_name', 'MaterialsSubject.show')) {
            return DataTables::query($data)
                ->addColumn('action', function ($row) {
                    $btn = '<td></d></tr><div class="btn-group-vertical">
                                <a type="button" class="btn btn-info data-edit" id="data-edit" data-id=' . $row->id . ' ><span class="fa fa-edit">&nbsp;&nbsp;</span>Edit</a>
                            </div></td>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->toJson();
        }elseif($user_permission -> contains('slug_name', 'MaterialsSubjectDelete')) {
            return DataTables::query($data)
                ->addColumn('action', function ($row) {
                    $btn = '<td></d></tr><div class="btn-group-vertical">
                                <a type="button" class="btn btn-warning data-delete" id="data-delete" data-id=' . $row->id . ' ><span class="fa fa-trash">&nbsp;&nbsp;</span>Delete</a>
                            </div></td>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->toJson();
        }
        else{
            return DataTables::query($data)
                ->addColumn('action', function ($row) {
                    $btn = '';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->toJson();
        }


    }

    public function MaterialsSubjectDelete(Request $request){
        DB::table('materials_subject')
            ->where('id', $request->id)
            ->update([
                'status' => 0,
                'deleted_at' => Carbon::now()
            ]);

        return response()->json([
            'status' => 'success'
        ]);
    }
}
