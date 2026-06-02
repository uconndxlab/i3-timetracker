<?php

namespace App\Http\Controllers;

use App\Actions\Shifts\BuildWeeklyChart;
use App\Models\Project;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'proj_id' => 'required|exists:projects,id',
            'date' => 'required|date',
            'duration' => $this->durationRules(),
            'entered' => 'required|boolean',
            'billed' => 'nullable|boolean',
        ];

        if ($user->isAdmin()) {
            $rules['netid'] = 'required|exists:users,netid';
        }

        $validatedData = $request->validate($rules, [], [
            'netid' => 'Name',
            'proj_id' => 'Project',
            'date' => 'Date',
            'duration' => 'Duration',
            'entered' => 'Entered in University System',
        ]);

        $validatedData['netid'] = $user->isAdmin()
            ? $validatedData['netid']
            : $user->netid;

        if (! $user->isAdmin()) {
            $projectIds = $this->visibleProjects($user, true);

            if (! in_array((int) $validatedData['proj_id'], $projectIds, true)) {
                return back()->withErrors(['proj_id' => 'You are not authorized to log shifts for this project.']);
            }

            $validatedData['billed'] = false;
        } elseif (! isset($validatedData['billed'])) {
            $validatedData['billed'] = false;
        }

        $shiftUser = User::where('netid', $validatedData['netid'])->firstOrFail();
        $project = Project::findOrFail($validatedData['proj_id']);

        $project->users()->syncWithoutDetaching([$shiftUser->netid]);

        Shift::create($validatedData);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Shift logged successfully!']);
        }

        return redirect()->route('landing')->with('message', 'Shift logged successfully!');
    }

    public function update(Request $request, Shift $shift)
    {
        $user = auth()->user();

        if (! $this->canEditShift($user, $shift)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You cannot edit this shift.'], 403);
            }

            return redirect()->route('landing')->with('message', 'You cannot edit this shift.');
        }

        $validatedData = $request->validate([
            'netid' => 'sometimes|required|exists:users,netid',
            'proj_id' => 'sometimes|required|exists:projects,id',
            'date' => 'sometimes|required|date',
            'duration' => $this->durationRules(required: false),
            'entered' => 'sometimes|boolean',
            'billed' => 'sometimes|boolean',
        ], [], [
            'netid' => 'Name',
            'proj_id' => 'Project',
            'date' => 'Date',
            'duration' => 'Duration',
            'entered' => 'Entered in University System',
            'billed' => 'Billed in Cider',
        ]);

        if (! $user->isAdmin()) {
            unset($validatedData['netid'], $validatedData['billed']);
        }

        if (isset($validatedData['proj_id']) && ! $user->isAdmin()) {
            $projectIds = $this->visibleProjects($user, true);

            if (! in_array((int) $validatedData['proj_id'], $projectIds, true)) {
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
            $chartBuilder = app(BuildWeeklyChart::class);
            $shiftDate = $shift->date->format('Y-m-d');

            return response()->json([
                'shift' => $shift->toAdminRow(),
                'day' => $chartBuilder->buildDay($shift->netid, $shiftDate, $user->isAdmin()),
                'week' => $chartBuilder->buildWeekSummary($shift->netid, $shiftDate),
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

        if (! $request->has('entered')) {
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
            if (! $user->isAdmin() && ($shift->netid !== $user->netid || $shift->billed)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'You cannot update one or more of these shifts.'], 403);
                }

                abort(403, 'You cannot update one or more of these shifts.');
            }
        }

        Shift::whereIn('id', $shiftIds)->update(['entered' => $entered]);

        if ($request->expectsJson()) {
            $chartBuilder = app(BuildWeeklyChart::class);
            $isAdmin = $user->isAdmin();
            $updatedShifts = Shift::query()
                ->whereIn('id', $shiftIds)
                ->get(['id', 'netid', 'date']);

            $days = $updatedShifts
                ->map(fn (Shift $shift) => [
                    'netid' => $shift->netid,
                    'date' => $shift->date->format('Y-m-d'),
                ])
                ->unique(fn (array $row) => $row['netid'].'|'.$row['date'])
                ->map(fn (array $row) => $chartBuilder->buildDay($row['netid'], $row['date'], $isAdmin))
                ->values()
                ->all();

            $weeks = $updatedShifts
                ->map(fn (Shift $shift) => [
                    'netid' => $shift->netid,
                    'date' => $shift->date->format('Y-m-d'),
                ])
                ->unique(fn (array $row) => $row['netid'].'|'.$row['date'])
                ->map(fn (array $row) => $chartBuilder->buildWeekSummary($row['netid'], $row['date']))
                ->unique('start_date')
                ->values()
                ->all();

            return response()->json([
                'shift_ids' => $shiftIds,
                'entered' => $entered,
                'days' => $days,
                'weeks' => $weeks,
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

    public function updateBilled(Request $request, Shift $shift)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only admins can update billed status.');
        }

        $billed = $request->boolean('billed', true);
        $shift->update(['billed' => $billed]);
        $shift->load(['project', 'user']);

        if ($request->expectsJson()) {
            return response()->json([
                'shift' => $shift->toAdminRow(),
                'csrf_token' => csrf_token(),
            ]);
        }

        return redirect()->back()->with('message', 'Shift updated successfully.');
    }

    public function destroy(Shift $shift)
    {
        $user = auth()->user();

        if (! $user->isAdmin() &&
            ($shift->netid !== $user->netid || $shift->entered || $shift->billed)) {
            abort(403, 'You cannot delete this shift.');
        }

        $shiftId = $shift->id;
        $shift->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'shift_id' => $shiftId,
                'csrf_token' => csrf_token(),
            ]);
        }

        return redirect()->route('landing')->with('message', 'Shift deleted successfully.');
    }

    /**
     * @return list<\Closure|string>
     */
    private function durationRules(bool $required = true): array
    {
        $rules = $required
            ? ['required', 'integer', 'min:15']
            : ['sometimes', 'required', 'integer', 'min:15'];

        $rules[] = function (string $attribute, mixed $value, \Closure $fail): void {
            if ((int) $value % 15 !== 0) {
                $fail('Duration must be in 15-minute increments.');
            }
        };

        return $rules;
    }

    private function canEditShift(User $user, Shift $shift): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $shift->netid === $user->netid && ! $shift->entered && ! $shift->billed;
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
