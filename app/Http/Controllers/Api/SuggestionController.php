<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Suggestion;

class SuggestionController extends Controller
{
    //
    public function index()
    {
        return response()->json(Suggestion::all());
    }

    // GET /suggestions/{id}
    public function show($id)
    {
        $suggestion = Suggestion::find($id);

        if (!$suggestion) {
            return response()->json(['message' => 'Suggestion not found'], 404);
        }

        return response()->json($suggestion);
    }

    // POST /suggestions
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contentId' => 'required|string',
            'imgUrl' => 'required|string',
            'link' => 'nullable|string',
            'is_ads' => 'boolean'
        ]);

        $suggestion = Suggestion::create($validated);

        return response()->json($suggestion, 201);
    }

    // PUT /suggestions/{id}
    public function update(Request $request, $id)
    {
        $suggestion = Suggestion::find($id);

        if (!$suggestion) {
            return response()->json(['message' => 'Suggestion not found'], 404);
        }

        $validated = $request->validate([
            'contentId' => 'string',
            'imgUrl' => 'string',
            'link' => 'nullable|string',
            'is_ads' => 'boolean'
        ]);

        $suggestion->update($validated);

        return response()->json($suggestion);
    }

    // DELETE /suggestions/{id}
    public function destroy($id)
    {
        $suggestion = Suggestion::find($id);

        if (!$suggestion) {
            return response()->json(['message' => 'Suggestion not found'], 404);
        }

        $suggestion->delete();

        return response()->json(['message' => 'Suggestion deleted']);
    }
}
