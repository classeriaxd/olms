<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Yajra\DataTables\Facades\DataTables;

class DTRController extends Controller
{
    //
    public function __construct(array $attributes = array())
    {
        /* if controller is not compatible with slug name */
        $routeArray = app('request')->route()->getAction();
        $controllerAction = class_basename($routeArray['controller']);
        list($this -> controller, $action) = explode('Controller@', $controllerAction);

        $this -> routeName = Route::currentRouteName();

    }

    public function view(){
        if(auth::check() == true){
            $user_permission = db::table('user_links as a')
                ->join('user_permission as b', 'a.id', '=' , 'b.link_id')
                ->where('b.user_id', auth::user()->id)
                ->where('b.status' , '=' , 'On')
                ->Where('a.slug_name', 'LIKE' , '%'.$this->controller.'%')
                ->Where('link_id', '!=', 0)
                ->get();

            if($user_permission -> contains('slug_name', $this -> routeName)){
                return view('DTR.view');
            }else{
                return redirect()->route('Dashboard');
            }
        }else{
            return redirect()->route('user_login_page');
        }
    }

    public function view_datatables(){
        $dtr = db::table('timein as a')
            ->select('username', db::raw("DATE_FORMAT(timein, '%M %d,%Y %h:%i %p') as timein"), db::raw("DATE_FORMAT(timeout, '%M %d,%Y %h:%i %p') as timeout"), 'a.id as time_id','c.image_url as image_url')
            ->join('users as b', 'a.users_id', '=', 'b.id')
            ->join('user_details as c', 'a.users_id', '=' , 'c.user_id')
            ->orderBy('time_id', 'desc');

        return Datatables::query($dtr)
            ->addColumn('images', function ($row) {
                if(is_null($row->image_url)){
                    $url= asset('img/upload_picture.png');
                    $images = '<img src="'.$url.'" border="0" style="border-radius: 50%;height:70px;width:70px" class="img-rounded" align="center" />';
                }else{
                    $url= asset('images/'.$row->image_url);
                    $images = '<img src="'.$url.'" border="0" style="border-radius: 50%;height:70px;width:70px" class="img-rounded" align="center" />';
                }
                return $images;
            })
            ->rawColumns(['images'])
            ->toJson();
    }

    public function main(){
        return view('DTR.main');
    }

    public function dtr_login(Request $request){
        extract($request->all());

        $users = db::table('users')
            ->where('username', $username)
            ->get();

        foreach($users as $data){
            $id = $data->id;
        }

        $user_details = db::table('user_details')
            ->where('user_id', $id)
            ->get();

        foreach($user_details as $data_details){
            $fullname = $data_details -> lastname . ',' . $data_details -> firstname .' '. $data_details ->middlename;
        }

        $existing_dtr = db::table('timein')
            ->where('users_id', $id)
            ->where('status', '!=', 0)
            ->get();

        if(count($existing_dtr) > 0){
            DB::table('timein')
                ->where('users_id', $id)
                ->update([
                        'status' => 0,
                        'timeout' => carbon::now()
                    ]);

            $message = $fullname . "is successfully Time-out";
        }else{
            db::table('timein')
            ->insert([
                'users_id' => $id,
                'timein' => carbon::now(),
                'status'  => 1
            ]);
            $message = $fullname . " is successfully Time-in";
        }

        return response()->json(['status' => 'success' , 'message' => $message]);
    } // End of function

    public function mainpage(Request $request){
        extract($request->all());

        $userdata = base64_decode($username);

        $users = db::table('users as a')
            ->join('user_details as b', 'a.id', '=', 'b.user_id')
            ->where('a.username', $userdata)
            ->get();

        return view('DTR.dtr')
            ->with('user_data', $users);
    }

    public function search_book(){
        $data = DB::table('materials')
            ->select('*',  DB::raw('(CASE WHEN type = 1 THEN "Borrow" WHEN type = 2 THEN "Room Use" END) AS type'), DB::raw('(CASE WHEN is_available = 0 THEN "NOT AVAILABLE" WHEN is_available = 1 THEN "AVAILABLE" WHEN is_available = 2 THEN "RESERVED" END) AS is_available'))
            ->where('status', 1);

        return DataTables::query($data)
            ->toJson();
    }

    public function force_logout(Request $request){
        extract($request->all());

        DB::table('timein')
                ->where('id', $id)
                ->update([
                        'status' => 0,
                        'timeout' => carbon::now()
                    ]);

        return response()->json(['status' => 'success']);
    }


}
