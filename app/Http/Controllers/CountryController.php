<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request) {
        $q = trim($request->get('q', ''));
        $query = Country::query();

        if ($q !== '') {
            $query->where(function($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('code', 'like', "%{$q}%")
                  ->orWhere('currency', 'like', "%{$q}%");
            });
        }

        return response()->json(
            $query->orderBy('name')->get(['id','code','name','phone','currency'])
        );
    }
}
