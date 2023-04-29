<?php

namespace Flatpack\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function suggestions(Request $request, $entity)
    {
        $value = $request->query('value', '');

        if (strlen($value) < 2) {
            return response()->json([
                'entity' => $entity,
                'data' => [],
            ]);
        }

        $column = $request->query('search', 'name');
        $model = $request->flatpack['model'];
        $primaryKey = (new $model())->getKeyName();

        $results = $model::select([
                $primaryKey . ' AS value',
                $column,
            ])
            ->where($column, 'LIKE', "%{$value}%")
            ->orderBy($column)
            ->take(10)
            ->get();

        return response()->json([
            'entity' => $entity,
            'data' => $results,
        ]);
    }
}
