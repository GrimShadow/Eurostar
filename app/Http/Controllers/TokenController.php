<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function create(Request $request)
    {
        $validated = $request->validate([
            'token_name' => 'required|string|max:255',
        ]);

        $token = $request->user()->createToken($validated['token_name']);
        $plainTextToken = $token->plainTextToken;
        
        // Remove any prefix number and pipe from the token
        $cleanToken = preg_replace('/^\d+\|/', '', $plainTextToken);

        return back()->with('token', $cleanToken);
    }

    public function destroy(Request $request, $tokenId)
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();
        return back()->with('status', 'Token deleted successfully');
    }
}