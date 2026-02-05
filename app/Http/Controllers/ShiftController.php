<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Project;
use App\Models\Shift;
use Illuminate\Http\Request;
use  Illuminate\Pagination\LengthAwarePaginator;

class ShiftController extends Controller
{
    public function create(Request $request)
    {
        $user = auth()->user();
        $selectedProjectId = $request->input('proj_id');
        $selectedProject = null;
        
        $est = now()->setTimezone('America/New_York');
        $date = $est->format('Y-m-d');

        if ($selectedProjectId) {
            $selectedProject = Project::find($selectedProjectId);

            if ($selectedProject && !$user->isAdmin()) {
                $hasAccess = Project::join('project_user', 'projects.id', '=', 'project_user.project_id')
                    ->where('project_user.user_netid', $user->netid)
                    ->where('projects.id', $selectedProjectId)
                    ->exists();
                    
                if (!$hasAccess) {
                    $selectedProject = null; 
                }
            }
        }
        if ($user->isAdmin()) {
            $projects = Project::where('active', true)->orderBy('name')->get();
        } else {
            $projectIds = Project::join('project_user', 'projects.id', '=', 'project_user.project_id')
                ->where('project_user.user_netid', $user->netid)
                ->pluck('projects.id');
            $projects = Project::whereIn('id', $projectIds)
                ->where('active', true)
                ->orderBy('name')
                ->get();
        }

        return view('shifts.create', compact('projects', 'selectedProject', 'date'));
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Shift::query();
        $sortField = $request->input('sort');
        $direction = $request->input('direction', 'asc');

        if ($sortField === 'project.name') {
            $query->join('projects', 'shifts.proj_id', '=', 'projects.id')
                ->select('shifts.*')
                ->orderBy('projects.name', $direction);
        } 
        else if ($sortField === 'user.name') {
            $query->leftJoin('users', 'shifts.netid', '=', 'users.netid')
                ->select('shifts.*')
                ->orderByRaw('CASE WHEN users.name IS NULL THEN 1 ELSE 0 END, users.name ' . $direction);
        }
        else if ($sortField === 'shift_date') {
            $query->orderBy('date', $direction);
        }
        else if ($sortField === 'duration') {
            $query->orderBy('duration', $direction);
        }
        else if ($sortField) {
            $query->orderBy($sortField, $direction);
        } 
        else {
            $query->orderBy('date', 'desc');
        }
        
        $shifts = $query->with(['user', 'project'])->get();
        foreach ($shifts as $shift) {
            $shift->shift_date = $shift->date->format('M d, Y');
            // $shift->duration = $shift->duration ? number_format($shift->duration / 60, 2) . ' hrs' : '-';
            $shift->can_edit = $user->isAdmin() || ($shift->netid === $user->netid && !$shift->entered && !$shift->billed);
        }
        
        return view('shifts.index', compact('shifts'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $validatedData = $request->validate([
            'netid' => 'required|exists:users,netid',
            'proj_id' => 'required|exists:projects,id',
            'date' => 'required|date',
            'duration' => 'required|integer|min:1',
            'entered' => 'required|boolean',
            'billed' => 'nullable|boolean',
            'start_time' => 'nullable|date_format:H:i', // while in transition
            'end_time' => 'nullable|date_format:H:i', // while in transition
        ], [], [
            'netid' => 'Name',
            'proj_id' => 'Project',
            'date' => 'Date',
            'duration' => 'Duration',
            'entered' => 'Entered in University System',
        ]);

        if (!$user->isAdmin()) {
            $projectIds = Project::join('project_user', 'projects.id', '=', 'project_user.project_id')
                ->where('project_user.user_netid', $user->netid)
                ->where('projects.active', true)
                ->pluck('projects.id')
                ->toArray();
                
            if (!in_array($validatedData['proj_id'], $projectIds)) {
                return back()->withErrors(['proj_id' => 'You are not authorized to log shifts for this project.']);
            }
        }

        if (!isset($validatedData['billed'])) {
            $validatedData['billed'] = false;
        }

        $user = User::where('netid', $validatedData['netid'])->first();
        $project = Project::find($validatedData['proj_id']);

        $project->users()->syncWithoutDetaching([$user->netid]);

        Shift::create($validatedData);
        return redirect()->route('shifts.index')->with('message', 'Shift logged successfully!');
    }

    public function update(Request $request, Shift $shift)
    {
        $validatedData = $request->validate([
            'netid' => 'sometimes|required|exists:users,netid',
            'proj_id' => 'sometimes|required|exists:projects,id',
            'date' => 'sometimes|required|date',
            'duration' => 'sometimes|required|integer|min:1',
            'entered' => 'sometimes|required|boolean',
            'billed' => 'sometimes|required|boolean',
            'start_time' => 'nullable|date_format:H:i', // while in transition
            'end_time' => 'nullable|date_format:H:i', // while in transition
        ], [], [
            'netid' => 'Name',
            'proj_id' => 'Project',
            'date' => 'Date',
            'duration' => 'Duration',
            'entered' => 'Entered in University System',
            'billed' => 'Billed in Cider',
        ]);

        if (!$request->has('entered')) {
            $validatedData['entered'] = false;
        }
        
        if (!$request->has('billed')) {
            $validatedData['billed'] = false;
        }

        $shift->update($validatedData);
        return redirect()->route('shifts.index')->with('message', 'Shift updated successfully!');
    }

    public function edit(Shift $shift)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && 
            ($shift->netid !== $user->netid || $shift->entered || $shift->billed)) {
            return redirect()->route('shifts.index')->with('message', 'You cannot edit this shift.');
        }
        
        if ($user->isAdmin()) {
            $projects = Project::where('active', true)->get();
        } else {
            $projectIds = Project::join('project_user', 'projects.id', '=', 'project_user.project_id')
                ->where('project_user.user_netid', $user->netid)
                ->pluck('projects.id');
            $projects = Project::whereIn('id', $projectIds)
                ->where('active', true)
                ->get();
        }
        
        return view('shifts.edit', compact('shift', 'projects'));
    }
        
    public function destroy(Shift $shift)
    {
        $user = auth()->user();
        
        if (!$user->isAdmin() && 
            ($shift->netid !== $user->netid || $shift->entered || $shift->billed)) {
            abort(403, 'You cannot delete this shift.');
        }
        
        $shift->delete();
        
        return redirect()->route('shifts.index')->with('message', 'Shift deleted successfully.');
    }

    public function viewAllShifts(Request $request)
    {
        $user = auth()->user();
        $sortField = $request->input('sort');
        $direction = $request->input('direction', 'asc');
        $enteredFilter = $request->input('entered_filter');
        $billedFilter = $request->input('billed_filter');
        $search = $request->input('search'); // Get search parameter
        
        $query = Shift::query();
        if ($search) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }
        
        if ($enteredFilter !== null && $enteredFilter !== '') {
            $query->where('entered', $enteredFilter == '1');
        }
        
        if ($billedFilter !== null && $billedFilter !== '') {
            $query->where('billed', $billedFilter == '1');
        }
        
        if ($sortField === 'project.name') {
            $query->join('projects', 'shifts.proj_id', '=', 'projects.id')
                ->select('shifts.*')
                ->orderBy('projects.name', $direction);
        }

        else if ($sortField === 'user.name') {
            $query->leftJoin('users', 'shifts.netid', '=', 'users.netid')
                ->select('shifts.*', 'users.name as user_name')
                ->orderBy('user_name', $direction);
        }

        else if ($sortField === 'shift_date') {
            $query->orderBy('date', $direction);
        }

        else if ($sortField === 'duration') {
            $query->orderBy('duration', $direction);
        }

        else if ($sortField === 'entered' || $sortField === 'billed') {
            $query->orderBy($sortField, $direction);
        }

        else if ($sortField) {
            $query->orderBy($sortField, $direction);
        }
        
        else {
            $query->orderBy('date', 'desc');
        }
        
        $shifts = $query->with(['user', 'project'])->paginate(30)->appends($request->except('page'));
        
        foreach ($shifts as $shift) {
            $shift->shift_date = $shift->date ? $shift->date->format('M d, Y') : '-';
            $shift->can_edit = $user->isAdmin() || 
                ($shift->netid === $user->netid && !$shift->entered && !$shift->billed);
        }
        
        return view('shifts.manage', compact('shifts', 'enteredFilter', 'billedFilter', 'search'));
    }
}
