<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminFaqController extends Controller
{
    // Admin page (blade)
    public function page()
    {
        return view('admin.faq.index');
    }

    // DataTables-friendly JSON list
    public function list(Request $request)
    {
        $onlyActive = $request->boolean('active_only', false);

        $q = Faq::query();
        if ($onlyActive) {
            $q->active();
        }

        // Simple ordering for admin view
        $rows = $q->orderBy('sort_order')->orderByDesc('updated_at')->get();

        $data = $rows->map(function (Faq $f) {
            $author = e($f->author_name);
            $meta   = trim(implode(', ', array_filter([$f->author_role, $f->author_location])));
            $authorFull = $meta ? "{$author} <small class=\"text-muted\">({$meta})</small>" : $author;

            $quote = e($f->quote);
            $quoteShort = mb_strlen($quote) > 160 ? mb_substr($quote, 0, 160).'…' : $quote;

            return [
                'id'         => $f->id,
                'quote'      => $quoteShort,
                'author'     => $authorFull,
                'is_active'  => $f->is_active ? 'Yes' : 'No',
                'sort_order' => $f->sort_order,
                'updated_at' => $f->updated_at?->format('Y-m-d H:i') ?? '-',
                'actions'    => '', // rendered by Blade script
            ];
        });

        return response()->json($data);
    }

    // Single show (for edit modal)
    public function show(Faq $faq)
    {
        return response()->json([
            'id'              => $faq->id,
            'quote'           => $faq->quote,
            'author_name'     => $faq->author_name,
            'author_role'     => $faq->author_role,
            'author_location' => $faq->author_location,
            'is_active'       => (bool) $faq->is_active,
            'sort_order'      => (int) $faq->sort_order,
            'updated_at'      => optional($faq->updated_at)->toDateTimeString(),
        ]);
    }

    // Create/Update
    public function save(Request $request)
    {
        $id = $request->input('id');

        $v = Validator::make($request->all(), [
            'quote'           => ['required', 'string', 'min:10'],
            'author_name'     => ['required', 'string', 'max:120'],
            'author_role'     => ['nullable', 'string', 'max:120'],
            'author_location' => ['nullable', 'string', 'max:120'],
            'is_active'       => ['nullable', 'in:0,1,true,false'],
            'sort_order'      => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ], [
            'quote.required'       => 'Please enter the quote text.',
            'author_name.required' => 'Please enter the author name.',
        ]);

        if ($v->fails()) {
            return response()->json(['ok' => false, 'message' => $v->errors()->first()], 422);
        }

        $data = $v->validated();
        $isActive = filter_var($data['is_active'] ?? 1, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        if ($id) {
            $faq = Faq::findOrFail($id);
        } else {
            $faq = new Faq();
            // default sort to max+1 if not provided
            if (!isset($data['sort_order'])) {
                $max = (int) Faq::max('sort_order');
                $data['sort_order'] = $max + 1;
            }
        }

        $faq->fill([
            'quote'           => $data['quote'],
            'author_name'     => $data['author_name'],
            'author_role'     => $data['author_role'] ?? null,
            'author_location' => $data['author_location'] ?? null,
            'is_active'       => $isActive,
            'sort_order'      => (int) ($data['sort_order'] ?? ($faq->sort_order ?? 0)),
        ])->save();

        return response()->json(['ok' => true, 'id' => $faq->id, 'message' => 'Saved']);
    }

    // Bulk sort (optional)
    public function bulkSort(Request $request)
    {
        $items = $request->input('items', []); // array of ['id'=>..., 'sort_order'=>...]
        if (!is_array($items) || empty($items)) {
            return response()->json(['ok' => false, 'message' => 'No items provided.'], 422);
        }

        foreach ($items as $it) {
            $id  = isset($it['id']) ? (int) $it['id'] : null;
            $ord = isset($it['sort_order']) ? (int) $it['sort_order'] : null;
            if (!$id || $ord === null) continue;
            Faq::where('id', $id)->update(['sort_order' => $ord]);
        }

        return response()->json(['ok' => true, 'message' => 'Order updated']);
    }

    // Delete
    public function delete(Faq $faq)
    {
        $faq->delete();
        return response()->json(['ok' => true, 'message' => 'Deleted']);
    }
}
