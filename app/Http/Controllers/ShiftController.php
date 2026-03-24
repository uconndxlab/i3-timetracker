<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Project;
use App\Models\Shift;
use Illuminate\Http\Request;

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
                $hasAccess = $this->canAccessProject($user, $selectedProjectId);
                    
                if (!$hasAccess) {
                    $selectedProject = null; 
                }
            }
        }
        $projects = $this->availableProjects($user);

        return view('shifts.create', compact('projects', 'selectedProject', 'date'));
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Shift::query();
        $query->where('netid', $user->netid);
        
        $sortField = $request->input('sort');
        $direction = $request->input('direction', 'asc');

        $this->shiftSort($query, $sortField, $direction, false, true);
        
        $shifts = $query->with(['user', 'project'])->get();
        if ($sortField === 'user.name') {
            $shifts = $this->sortByUser($shifts, $direction)->values();
        }

        $this->shiftButtons($shifts, $user, false);
        
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
            $projectIds = $this->visibleProjects($user, true);
                
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
        
        $projects = $this->availableProjects($user, false);
        
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
        
        $this->shiftSort($query, $sortField, $direction, true, false);
        
        $shifts = $query->with(['user', 'project'])->paginate(30)->appends($request->except('page'));

        $this->shiftButtons($shifts, $user, true);
        
        return view('shifts.manage', compact('shifts', 'enteredFilter', 'billedFilter', 'search'));
    }

    private function availableProjects(User $user, bool $orderByName = true)
    {
        if ($user->isAdmin()) {
            $query = Project::where('active', true);
            return $orderByName ? $query->orderBy('name')->get() : $query->get();
        }

        $projectIds = $this->visibleProjects($user, false);
        $query = Project::whereIn('id', $projectIds)->where('active', true);

        return $orderByName ? $query->orderBy('name')->get() : $query->get();
    }

    private function visibleProjects(User $user, bool $activeOnly): array
    {
        $query = Project::join('project_user', 'projects.id', '=', 'project_user.project_id')
            ->where('project_user.user_netid', $user->netid);

        if ($activeOnly) {
            $query->where('projects.active', true);
        }

        return $query->pluck('projects.id')->toArray();
    }

    private function canAccessProject(User $user, int $projectId): bool
    {
        return Project::join('project_user', 'projects.id', '=', 'project_user.project_id')
            ->where('project_user.user_netid', $user->netid)
            ->where('projects.id', $projectId)
            ->exists();
    }

    private function shiftSort($query, ?string $sortField, string $direction, bool $userAlias, bool $lastNull): void
    {
        if ($sortField === 'project.name') {
            $query->join('projects', 'shifts.proj_id', '=', 'projects.id')
                ->select('shifts.*')
                ->orderBy('projects.name', $direction);
            return;
        }

        if ($sortField === 'user.name') {
            if ($userAlias) {
                $query->leftJoin('users', 'shifts.netid', '=', 'users.netid')
                    ->select('shifts.*', 'users.name as user_name')
                    ->orderBy('user_name', $direction);
                return;
            }

            if ($lastNull) {
                return;
            }

            $query->leftJoin('users', 'shifts.netid', '=', 'users.netid')
                ->select('shifts.*')
                ->orderBy('users.name', $direction);
            return;
        }

        if ($sortField === 'shift_date') {
            $query->orderBy('date', $direction);
            return;
        }

        if ($sortField === 'duration') {
            $query->orderBy('duration', $direction);
            return;
        }

        if ($sortField) {
            $query->orderBy($sortField, $direction);
            return;
        }

        $query->orderBy('date', 'desc');
    }

    private function sortByUser($shifts, string $direction)
    {
        return $shifts->sort(function ($a, $b) use ($direction) {
            $aName = $a->user?->name;
            $bName = $b->user?->name;

            if ($aName === null && $bName === null) {
                return 0;
            }

            if ($aName === null) {
                return 1;
            }

            if ($bName === null) {
                return -1;
            }

            $comparison = strcmp(strtolower($aName), strtolower($bName));
            return $direction === 'desc' ? -$comparison : $comparison;
        });
    }

    private function shiftButtons($shifts, User $user, bool $nullDate)
    {
        foreach ($shifts as $shift) {
            if ($nullDate) {
                $shift->shift_date = $shift->date ? $shift->date->format('M d, Y') : '-';
            } else {
                $shift->shift_date = $shift->date->format('M d, Y');
            }

            $shift->can_edit = $user->isAdmin() ||
                ($shift->netid === $user->netid && !$shift->entered && !$shift->billed);
        }
    }
}
