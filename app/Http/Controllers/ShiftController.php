<?php

namespace App\Http\Controllers;
use App\Actions\Admin\BuildAdminDashboard;
use App\Models\User;
use App\Models\Project;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function create(Request $request)
    {
        return redirect()->route('landing');
    }

    public function index(Request $request)
    {
        return redirect()->route('landing');
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

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Shift logged successfully!']);
        }

        return redirect()->route('landing')->with('message', 'Shift logged successfully!');
    }

    public function update(Request $request, Shift $shift)
    {
        $user = auth()->user();

        if (!$this->canEditShift($user, $shift)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You cannot edit this shift.'], 403);
            }

            return redirect()->route('landing')->with('message', 'You cannot edit this shift.');
        }

        $validatedData = $request->validate([
            'netid' => 'sometimes|required|exists:users,netid',
            'proj_id' => 'sometimes|required|exists:projects,id',
            'date' => 'sometimes|required|date',
            'duration' => 'sometimes|required|integer|min:1',
            'entered' => 'sometimes|boolean',
            'billed' => 'sometimes|boolean',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ], [], [
            'netid' => 'Name',
            'proj_id' => 'Project',
            'date' => 'Date',
            'duration' => 'Duration',
            'entered' => 'Entered in University System',
            'billed' => 'Billed in Cider',
        ]);

        if (isset($validatedData['proj_id']) && !$user->isAdmin()) {
            $projectIds = $this->visibleProjects($user, true);

            if (!in_array((int) $validatedData['proj_id'], $projectIds, true)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'You are not authorized to use this project.'], 403);
                }

                return back()->withErrors(['proj_id' => 'You are not authorized to use this project.']);
            }
        }

        if ($request->has('entered')) {
            $validatedData['entered'] = $request->boolean('entered');
        }

        if ($request->has('billed')) {
            $validatedData['billed'] = $request->boolean('billed');
        }

        $shift->update($validatedData);
        $shift->load(['project', 'user']);

        if ($request->expectsJson()) {
            return response()->json([
                'shift' => app(BuildAdminDashboard::class)->formatShiftRow($shift),
                'csrf_token' => csrf_token(),
            ]);
        }

        return redirect()->route('landing')->with('message', 'Shift updated successfully!');
    }

    public function bulkUpdateEntered(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'shift_ids' => 'required|array|min:1',
            'shift_ids.*' => 'integer',
            'entered' => 'required',
        ]);

        if (!$request->has('entered')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Entered status is required.'], 422);
            }

            return redirect()->route('landing')->with('message', 'Entered status is required.');
        }

        $entered = $request->boolean('entered');
        $shiftIds = array_values(array_unique($validated['shift_ids']));
        $shifts = Shift::whereIn('id', $shiftIds)->get();

        if ($shifts->count() !== count($shiftIds)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'One or more shifts were not found.'], 404);
            }

            return redirect()->route('landing')->with('message', 'One or more shifts were not found.');
        }

        foreach ($shifts as $shift) {
            if (!$user->isAdmin() && ($shift->netid !== $user->netid || $shift->billed)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'You cannot update one or more of these shifts.'], 403);
                }

                abort(403, 'You cannot update one or more of these shifts.');
            }
        }

        Shift::whereIn('id', $shiftIds)->update(['entered' => $entered]);

        if ($request->expectsJson()) {
            return response()->json([
                'shift_ids' => $shiftIds,
                'entered' => $entered,
                'csrf_token' => csrf_token(),
            ]);
        }

        return redirect()->route('landing')->with('message', 'Shift updated successfully!');
    }

    public function updateEntered(Request $request, Shift $shift)
    {
        $request->merge([
            'shift_ids' => [$shift->id],
        ]);

        return $this->bulkUpdateEntered($request);
    }

    public function edit(Shift $shift)
    {
        return redirect()->route('landing');
    }
        
    public function destroy(Shift $shift)
    {
        $user = auth()->user();
        
        if (!$user->isAdmin() && 
            ($shift->netid !== $user->netid || $shift->entered || $shift->billed)) {
            abort(403, 'You cannot delete this shift.');
        }
        
        $shift->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'shift_id' => $shift->id,
                'csrf_token' => csrf_token(),
            ]);
        }

        return redirect()->route('landing')->with('message', 'Shift deleted successfully.');
    }

    public function viewAllShifts(Request $request)
    {
        return redirect()->route('landing', ['view' => 'admin']);
    }

    private function canEditShift(User $user, Shift $shift): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $shift->netid === $user->netid && !$shift->entered && !$shift->billed;
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
}
