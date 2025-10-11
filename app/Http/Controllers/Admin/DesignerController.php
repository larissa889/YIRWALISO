<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Designer;
use App\Models\User;
use Illuminate\Http\Request;

class DesignerController extends Controller
{
    public function index()
    {
        $designers = Designer::with('user')->paginate(15);
        return view('admin.designers.index', compact('designers'));
    }

    public function create()
    {
        $users = User::where('role', 'designer')->whereDoesntHave('designer')->get();
        return view('admin.designers.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id|unique:designers,user_id',
            'specialty' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'working_days' => 'required|array',
            'working_days.*' => 'integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $data = $request->except('photo');

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('designers', 'public');
        }

        Designer::create($data);

        return redirect()->route('admin.designers.index')
            ->with('success', 'Coiffeur créé avec succès.');
    }

    public function show(Designer $designer)
    {
        return view('admin.designers.show', compact('designer'));
    }

    public function edit(Designer $designer)
    {
        return view('admin.designers.edit', compact('designer'));
    }

    public function update(Request $request, Designer $designer)
    {
        $request->validate([
            'specialty' => 'required|string|max:255',
            'bio' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'working_days' => 'required|array',
            'working_days.*' => 'integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $data = $request->except('photo');

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('designers', 'public');
        }

        $designer->update($data);

        return redirect()->route('admin.designers.index')
            ->with('success', 'Coiffeur mis à jour avec succès.');
    }

    public function destroy(Designer $designer)
    {
        $designer->delete();

        return redirect()->route('admin.designers.index')
            ->with('success', 'Coiffeur supprimé avec succès.');
    }
}
