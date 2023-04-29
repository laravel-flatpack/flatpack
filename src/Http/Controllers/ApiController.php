<?php

namespace Flatpack\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ApiController extends Controller
{
    public function suggestions(Request $request, $entity)
    {
        $search = $request->query('search', '');
        $display = $request->query('display', 'name');
        $model = new $request->flatpack['model']();

        $tableName = $model->getTable();
        $primaryKey = $model->getKeyName();

        $results = $model::select([
                "{$primaryKey} AS value",
                "{$display} AS display",
            ])
            ->where($display, 'LIKE', "%{$search}%")
            ->when(
                $request->exists('selected'),
                fn ($query) => $query->whereIn('id', $request->input('selected', [])),
                fn ($query) => $query->limit(10)
            )
            ->when(
                Schema::hasColumn($tableName, 'deleted_at'),
                fn ($query) => $query->whereNull('deleted_at')
            )
            ->orderBy('display')
            ->take(10)
            ->get();

        return response()->json($results);
    }
}
