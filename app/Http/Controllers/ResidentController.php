<?php

namespace App\Http\Controllers;

use Hash;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\User;
use App\Resident;
use App\Barangay;

class ResidentController extends Controller {
    public function index(Request $request) {
        $resident_list = [];
        if($request->user()->role == 'Administrator') {
            $resident_list = Resident::all();
        } else if($request->user()->role == 'Barangay') {
            $barangay = $request->user()->barangay;
            $resident_list = Resident::where('barangay_id', $barangay->id)->get();
        }
        $data = [];
        foreach($resident_list as $resident) {
            $data[] = [
                'id' => $resident->id, 'username' => $resident->user->username,
                'fullname' => $resident->fullname, 'barangay' => $resident->barangay->name, 'phone_number' => $resident->phone_number,
            ];
        }
        return response(['residents' => $data]);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            
            'fullname' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'latitude' => 'required|string|max:255',
            'longitude' => 'required|string|max:255',
        ]);

        if ($validator->fails()){
            return response(['errors'=>$validator->errors()->all()], 422);
        }

        $request['password'] = Hash::make($request['password']);
        $user = User::create(array_merge($request->toArray(), ['verified' => false]));
        $barangay = Barangay::where('name', $request->barangay)->first();
        $resident = Resident::create(array_merge($request->toArray(), ['user_id' => $user->id, 'barangay_id' => $barangay->id]));

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;
        return response(['token' => $token, 'user' => $user, 'resident' => $resident], 201);
    }

    public function show(Resident $resident) {
        $resident->user; $resident->barangay;
        return response(['resident' => $resident], 200);
    }

    public function update(Request $request, Resident $resident) {
        $request['password'] = Hash::make($request['password']);
        $resident->update($request->toArray());
        $resident->user->update($request->toArray());
        return response(['resident' => $resident], 200);
    }

    public function destroy(Resident $resident) {
        $resident->user->delete();
        $resident->delete();
        return response(null, 204);
    }

    public function login_resident(Request $request) {
        
        // $response = array();
        // $response["status"] = 2;
        // $response["message"] = file_get_contents('php://input');
        // return $response;

        $arry = array();
        $arry = file_get_contents('php://input');
        $arry = json_decode($arry);
        $password = User::where('username', $arry->username)->value('password');
        if (password_verify($arry->password, $password))
        {
            $response = array();
            $response["status"] = 0;
            $response["message"] = "Login successful";
            return $response;
        }
    }

    public function register_resident(Request $request) {
        
        $response = array();
        $response["status"] = 2;
        $response["message"] = file_get_contents('php://input');
        // return $response;
        $arry = array();
        $arry = json_decode(file_get_contents('php://input'));
        $request['username']=$arry['username'];
        $request['password']=$arry['password'];
        $request['barangay']=$arry['brgy_id'];
        $request['fullname']=$arry['name'];
        $request['role']='user';
        $request['phone_number']='09759458361';
        $request['fullname']=$arry['name'];
        $this->store($request);
    }
}
