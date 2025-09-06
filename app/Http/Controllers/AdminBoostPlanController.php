<?php

namespace App\Http\Controllers;

use App\Models\BoostPlan;
use Illuminate\Http\Request;

class AdminBoostPlanController extends Controller
{
    public function page()
    {
        return view('admin.boost_plans'); // new blade below
    }

    public function list()
    {
        $rows = BoostPlan::orderByDesc('updated_at')->get([
            'id','name','days','price_usd','is_active','description','updated_at'
        ]);

        return response()->json($rows->map(function(BoostPlan $p){
            return [
                'id'          => $p->id,
                'name'        => $p->name,
                'days'        => $p->days,
                'price_usd'   => number_format((float)$p->price_usd, 2),
                'is_active'   => (bool)$p->is_active,
                'description' => $p->description,
                'updated_at'  => optional($p->updated_at)->format('Y-m-d H:i'),
            ];
        }));
    }

    public function save(Request $req)
    {
        $data = $req->validate([
            'id'          => ['nullable','integer','exists:boost_plans,id'],
            'name'        => ['required','string','max:120'],
            'days'        => ['required','integer','min:1','max:3650'],
            'price_usd'   => ['required','numeric','min:0'],
            'description' => ['nullable','string'],
            'is_active'   => ['nullable','boolean'],
        ]);

        $plan = $data['id'] ? BoostPlan::findOrFail($data['id']) : new BoostPlan();
        $plan->fill([
            'name'        => $data['name'],
            'days'        => $data['days'],
            'price_usd'   => $data['price_usd'],
            'description' => $data['description'] ?? null,
            'is_active'   => (bool)($data['is_active'] ?? false),
        ])->save();

        return response()->json(['message' => 'Saved', 'id' => $plan->id]);
    }

    public function delete($id)
    {
        BoostPlan::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
