<?php

namespace App\Http\Controllers;

use App\Models\PetStyle;
use App\Models\PetUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PetController extends Controller
{

    public function getBackground(Request $request, $lang, $styleID)
    {
        $style = PetStyle::find($styleID);
        if ($style) {
            $background = $style->background;
            if(Storage::disk()->exists("/pet/".$background))
                return Storage::response("/pet/".$background);
            return Storage::response("/pet/"."default.jpeg");
        }else{
            return Storage::response("/pet/"."default.jpeg");
        }
    }

    public function getMyListPets(Request $request) {
        $data['data']=$request->user->pets()->get();
        $data['error']=array();
        $data['code']=0;
        return response()->json($data);
    }

    public function getDetailsPet(Request $request, $lang, $petId) {
        $petDetails = PetUser::with(['user','details'])->where('id','=', $petId)->get()->first();
        if($petDetails!==null){
            $data['data']=$petDetails;
            $data['error']=array();
            $data['code']=0;
        }else{
            $data['data']=[];
            $data['error']=array(__('pet.error.notFound'));
            $data['code']=-1;
        }
        return response()->json($data);
    }
}
