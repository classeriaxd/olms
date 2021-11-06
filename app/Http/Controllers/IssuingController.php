<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Yajra\DataTables\Facades\DataTables;

class IssuingController extends Controller
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
        /* if controller is not compatible with slug name */
        $routeArray = app('request')->route()->getAction();
        $controllerAction = class_basename($routeArray['controller']);
        list($this -> controller, $action) = explode('Controller@', $controllerAction);

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
                ->Where('a.slug_name', 'LIKE' , '%'.$this->controller.'%')
                ->Where('link_id', '!=', 0)
                ->get();

            $materials = db::table('materials')
                ->where('status', 1)
                ->where('type', 1)
                ->where('is_available', 1)
                ->get();

            $materials_copy = db::table('materials_copies')
                ->get();

            $borrower = db::table('users as a')
                ->join('user_details as b', 'a.id', '=', 'b.user_id')
                ->where('status', 1)
                ->where('role_id','!=', 1)
                ->get();

            if($user_permission -> contains('slug_name', $this -> routeName)){
                return view('Issuing.list')
                    ->with('user_perm', $user_permission)
                    ->with('materials', $materials)
                    ->with('copies', $materials_copy)
                    ->with('borrower', $borrower);
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

        $penalty_settings = db::table('penalty_settings')
            ->get();

        foreach($penalty_settings as $penalty_settings){

        }

        $data_updated = [
            'materials_id' => $materials,
            'users_id' => $borrower,
            'type' => 1,
            'updated_at' => Carbon::now()
        ];

        if($id != ''){

            db::table('borrowings')
                ->where('id', $id)
                ->update($data_updated);

            db::table('materials_copies')
                ->where('borrows_id', $id)
                ->update([
                    'materials_id' => $materials,
                ]);

            return response()->json(['status' => 'success' , 'message' => "Issuing Data is successfully updated"]);

        }else{

            $data_inserted = [
                'materials_id' => $materials,
                'users_id' => $borrower,
                'date_borrowed' => Carbon::now()->toDateString(),
                'date_returned' => Carbon::now()->addDay(3)->toDateString(),
                'type' => 1,
                'created_at' => Carbon::now()
            ];

            $borrowing_id = db::table('borrowings')
                ->insertGetId($data_inserted);

            db::table('materials_copies')
                ->insert([
                    'materials_id' => $materials,
                    'borrows_id' => $borrowing_id
                ]);

            db::table('materials')
                ->where('materials_id', $materials)
                ->update([
                    'is_available' => 0
                ]);


            return response()->json(['status' => 'success' , 'message' => "Issuing Data is successfully inserted"]);
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
        $data = db::table('borrowings')
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

    public function Datatables(){

        $data = DB::table('borrowings as a')
            ->select('a.id as id','c.accnum as accnum','a.date_borrowed as date_borrowed','a.date_returned as date_returned', DB::raw("CONCAT(b.lastname,',',b.firstname) as fullname"))
            ->join('user_details as b', 'a.users_id', '=' , 'b.user_id')
            ->join('materials as c', 'a.materials_id', '=', 'c.materials_id')
            ->where('a.type' , 1)
            ->where('a.status', 1);


        $user_permission = db::table('user_links as a')
            ->join('user_permission as b', 'a.id', '=' , 'b.link_id')
            ->where('b.user_id', auth::user()->id)
            ->where('b.status' , '=' , 'On')
            ->where('a.slug_name', 'LIKE' , '%'.$this->controller.'%')
            ->where('b.link_id' , '!=' , 0)
            ->get();

        if($user_permission -> contains('slug_name', 'Issuing.show') && $user_permission -> contains('slug_name', 'IssuingDelete')) {
            return DataTables::query($data)
                ->filterColumn('fullname', function($query, $keyword) {
                    $sql = "CONCAT(b.lastname,',',b.firstname)  like ?";
                    $query->whereRaw($sql, ["%{$keyword}%"]);
                })
                ->addColumn('action', function ($row) {
                    $btn = '<td></d></tr><div class="btn-group-horizontally">
                                <a type="button" title="EDIT" class="btn btn-info data-edit" id="data-edit" data-id=' . $row->id . ' ><span class="fa fa-edit"></span></a>
                                <a type="button" title="RETURN" class="btn btn-warning data-delete" id="data-delete" data-id=' . $row->id . ' ><span class="fa fa-backward">&nbsp;&nbsp;</span>RETURN</a>
                            </div></td>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->toJson();
        }elseif($user_permission -> contains('slug_name', 'Issuing.show')) {
            return DataTables::query($data)
                ->filterColumn('fullname', function($query, $keyword) {
                    $sql = "CONCAT(b.lastname,',',b.firstname)  like ?";
                    $query->whereRaw($sql, ["%{$keyword}%"]);
                })
                ->addColumn('action', function ($row) {
                    $btn = '<td></d></tr><div class="btn-group-vertical">
                                <a type="button" class="btn btn-info data-edit" id="data-edit" data-id=' . $row->id . ' ><span class="fa fa-edit">&nbsp;&nbsp;</span>Edit</a>
                            </div></td>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->toJson();
        }elseif($user_permission -> contains('slug_name', 'IssuingDelete')) {
            return DataTables::query($data)
                ->filterColumn('fullname', function($query, $keyword) {
                    $sql = "CONCAT(b.lastname,',',b.firstname)  like ?";
                    $query->whereRaw($sql, ["%{$keyword}%"]);
                })
                ->addColumn('action', function ($row) {
                    $btn = '<td></d></tr><div class="btn-group-vertical">
                                <a type="button" class="btn btn-warning data-delete" id="data-delete" data-id=' . $row->id . ' ><span class="fa fa-backward">&nbsp;&nbsp;</span>Return</a>
                            </div></td>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->toJson();
        }
        else{
            return DataTables::query($data)
                ->filterColumn('fullname', function($query, $keyword) {
                    $sql = "CONCAT(b.lastname,',',b.firstname)  like ?";
                    $query->whereRaw($sql, ["%{$keyword}%"]);
                })
                ->addColumn('action', function ($row) {
                    $btn = '';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->toJson();
        }
    }

    public function Deletion(Request $request){
        DB::table('borrowings')
            ->where('id', $request->id)
            ->update([
                'status' => 0,
                'deleted_at' => Carbon::now()
            ]);

        DB::table('penalty')
            ->where('borrowings_id', $request->id)
            ->update([
                'status' => 0,
                'deleted_at' => Carbon::now()
            ]);

        $borrowers_data = db::table('borrowings')
            ->where('id', $request->id)
            ->get();

        db::table('materials_copies')
            ->where('borrows_id', $request->id)
            ->update([
                'status' => 0,
            ]);

        foreach($borrowers_data as $data){
            $materials_id = $data -> materials_id;
        }

        db::table('materials')
            ->where('materials_id', $materials_id)
            ->update([
                'is_available' => 1
            ]);

        return response()->json([
            'status' => 'success'
        ]);
    }

    public function book_extension(Request $request){
        extract($request->all());

        if($type == "extension"){
            $data = [
                'borrowings_id' => $borrowings_id,
                'users_id' => $user_id
            ];

            db::table('book_extension')
                ->insert($data);

            return response()->json([
                'status' => 'success'
            ]);
        }

        if($type == "accept"){

            $data = db::table('book_extension')
                ->where('id', $extension_id)
                ->get();

            foreach($data as $ext_data){
                $borrow_id = $ext_data -> borrowings_id;
            }

            db::table('book_extension')
            ->where('id', $extension_id)
            ->update(['status' => 1]);

            $penalty = db::table('penalty_settings')
            ->get();

            foreach($penalty as $settings){
                $days = $settings -> penalty_days;
            }

            $borrows = db::table('borrowings')
                ->where('id', $borrow_id)
                ->get();

            foreach($borrows as $borrow_data){
                $returned_date = $borrow_data -> date_returned;
            };

            $date  = Carbon::createFromFormat('Y-m-d', $returned_date);
            $date = $date->addDays($days);


            db::table('borrowings')
                ->where('id', $borrow_id)
                ->update([
                    'date_returned' => $date->toDateString()
                ]);

            return response()->json([
                'status' => 'success'
            ]);
        }

        if($type == "denied"){
            db::table('book_extension')
            ->where('id', $extension_id)
            ->update(['status' => 2]);

            return response()->json([
                'status' => 'success'
            ]);
        }


    }
}
