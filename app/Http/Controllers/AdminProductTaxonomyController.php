<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use App\Models\ProductSubcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminProductTaxonomyController extends Controller
{
    /** Page (Otika) */
    public function page()
    {
        return view('admin.product_taxonomy');
    }

    /* ========================
     * TYPES
     * ======================== */

    /** List (JSON) */
    public function typesList()
    {
        return ProductType::query()
            ->orderByDesc('updated_at')
            ->orderBy('id', 'desc')
            ->get();
    }

    /** Create/Update (JSON) */
    public function typesSave(Request $r)
    {
        $id   = (int) $r->input('id', 0);
        $name = trim((string) $r->input('name'));
        // Backend safety: auto slug if empty or force to a safe slugified version
        $slugIn = trim((string) $r->input('slug'));
        $slug = $slugIn !== '' ? $this->slugify($slugIn) : $this->slugify($name);
        $isActive = (int) $r->boolean('is_active');

        $rules = [
            'name' => ['required', 'max:255', Rule::unique('product_types','name')->ignore($id)],
            'slug' => ['required', 'max:255', Rule::unique('product_types','slug')->ignore($id)],
            'is_active' => ['nullable', 'in:0,1']
        ];
        // Validate against finalized $slug
        $r->merge(['slug' => $slug]);
        $r->validate($rules);

        $payload = [
            'name'      => $name,
            'slug'      => $slug,
            'is_active' => $isActive ? 1 : 0
        ];

        if ($id) {
            $type = ProductType::findOrFail($id);
            $type->update($payload);
        } else {
            $type = ProductType::create($payload);
        }

        return response()->json(['ok'=>true, 'id'=>$type->id]);
    }

    /** Delete (JSON) – cascades subcategories by FK */
    public function typesDelete($id)
    {
        $type = ProductType::findOrFail($id);
        $type->delete();

        return response()->json(['ok'=>true]);
    }

    /* ========================
     * SUBCATEGORIES
     * ======================== */

    /** List (JSON) */
    public function subcategoriesList(Request $r)
    {
        $typeId = (int) $r->query('type_id', 0);
        $q = ProductSubcategory::with('type')
            ->when($typeId > 0, fn($qq)=>$qq->where('product_type_id', $typeId))
            ->orderByDesc('updated_at')->orderBy('id','desc');

        return $q->get();
    }

    /** Create/Update (JSON) */
    public function subcategoriesSave(Request $r)
    {
        $id   = (int) $r->input('id', 0);
        $name = trim((string)$r->input('name'));
        $slugIn = trim((string)$r->input('slug'));
        $typeId = (int)$r->input('product_type_id');
        $icon   = trim((string)$r->input('icon_class'));
        $isActive = (int) $r->boolean('is_active');

        // backend slugify
        $slug = $slugIn !== '' ? $this->slugify($slugIn) : $this->slugify($name);

        // Unique slug within a type
        $rules = [
            'product_type_id' => ['required','integer','exists:product_types,id'],
            'name'            => ['required','max:255'],
            'slug'            => [
                'required','max:255',
                Rule::unique('product_subcategories')
                    ->where(fn($q)=>$q->where('product_type_id', $typeId))
                    ->ignore($id)
            ],
            'icon_class'      => ['nullable','max:120'],
            'is_active'       => ['nullable','in:0,1']
        ];
        $r->merge(['slug' => $slug]);
        $r->validate($rules);

        $payload = [
            'product_type_id' => $typeId,
            'name'            => $name,
            'slug'            => $slug,
            'icon_class'      => $icon ?: null,
            'is_active'       => $isActive ? 1 : 0,
        ];

        if ($id) {
            $sub = ProductSubcategory::findOrFail($id);
            $sub->update($payload);
        } else {
            $sub = ProductSubcategory::create($payload);
        }

        return response()->json(['ok'=>true, 'id'=>$sub->id]);
    }

    /** Delete (JSON) */
    public function subcategoriesDelete($id)
    {
        $sub = ProductSubcategory::findOrFail($id);
        $sub->delete();

        return response()->json(['ok'=>true]);
    }

    /* ========================
     * Helpers
     * ======================== */
    private function slugify(string $text): string
    {
        $s = Str::of($text)->lower()
            ->ascii()                 // strip accents
            ->replaceMatches('/[^a-z0-9]+/','-')
            ->trim('-')
            ->substr(0, 255)
            ->value();

        return $s ?: 'n-a';
    }
}
