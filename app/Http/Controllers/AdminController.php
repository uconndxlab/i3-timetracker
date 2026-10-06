<?php

namespace App\Http\Controllers;

use App\Actions\Projects\AssignUserProject;
use App\Models\Project;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $logoutUrl = 'https://login.microsoftonline.com/'
            .config('services.entra.tenant_id')
            .'/oauth2/v2.0/logout?'
            .http_build_query([
                'post_logout_redirect_uri' => url('/'),
            ]);

        return redirect()->away($logoutUrl);
    }

    public function markProjectRemainingBilled(Project $project)
    {
        $unbilledShifts = $project->shifts()->where('billed', false)->get();
        $shiftCount = $unbilledShifts->count();

        if ($shiftCount > 0) {
            $project->shifts()->where('billed', false)->update(['billed' => true]);

            return redirect()->back()->with('success', "{$shiftCount} shift(s) marked as billed successfully.");
        }

        return redirect()->back()->with('info', 'No unbilled shifts to mark.');
    }

    public function batchUpdateShifts(Request $request, Project $project)
    {
        $updates = $request->input('updates', []);

        if (empty($updates)) {
            return response()->json(['success' => false, 'message' => 'No updates provided']);
        }

        $updatedCount = 0;

        foreach ($updates as $shiftId => $changes) {
            $shift = Shift::where('id', $shiftId)
                ->whereHas('project', function ($query) use ($project) {
                    $query->where('id', $project->id);
                })
                ->first();

            if ($shift) {
                $updateData = [];

                if (isset($changes['billed'])) {
                    $updateData['billed'] = (bool) $changes['billed'];
                }

                if (isset($changes['entered'])) {
                    $updateData['entered'] = (bool) $changes['entered'];
                }

                if (! empty($updateData)) {
                    $shift->update($updateData);
                    $updatedCount++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$updatedCount} shift(s) updated successfully",
            'updated_count' => $updatedCount,
        ]);
    }

    public function assignUsers(Request $request, Project $project)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,netid',
        ]);
        $result = app(AssignUserProject::class)($project, $validated['user_ids']);

        if ($result['assigned_count'] > 0) {
            return redirect()->route('landing', ['view' => 'admin'])
                ->with('success', $result['assigned_count'].' user(s) successfully assigned to project.');
        }

        return redirect()->route('landing', ['view' => 'admin'])
            ->with('info', 'All selected users were already assigned to this project.');
    }

    public function removeUser(Project $project, $netid)
    {
        $project->users()->detach($netid);

        return redirect()->route('landing', ['view' => 'admin'])
            ->with('success', 'User successfully removed from project.');
    }

    public function toggleAdmin(User $user)
    {
        if ($user->netid === auth()->user()->netid) {
            return redirect()->back()->with('error', 'You cannot change your own admin status.');
        }

        $user->is_admin = ! $user->is_admin;
        $user->save();

        $status = $user->is_admin ? 'granted' : 'revoked';

        return redirect()->route('landing', ['view' => 'admin'])
            ->with('message', "Admin privileges {$status} for {$user->name}.");
    }

    public function approveUser(Request $request)
    {
        $validated = $request->validate([
            'netid' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $netid = strtolower(trim($validated['netid']));
        $email = $validated['email'] ?? ($netid.'@uconn.edu');
        $existing = User::where('netid', $netid)->first();

        if ($existing && $existing->active) {
            $message = "User with NetID {$netid} is already approved.";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['netid' => [$message]],
                ], 422);
            }

            return redirect()->route('landing', ['view' => 'admin'])->with('error', $message);
        }

        if (
            User::where('email', $email)
                ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
                ->exists()
        ) {
            $message = 'That email is already in use.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['email' => [$message]],
                ], 422);
            }

            return redirect()->route('landing', ['view' => 'admin'])->with('error', $message);
        }

        if ($existing) {
            $existing->update([
                'name' => $validated['name'],
                'email' => $email,
                'active' => true,
            ]);
            $user = $existing;
            $message = "Re-approved access for {$netid}.";
        } else {
            $user = User::create([
                'netid' => $netid,
                'name' => $validated['name'],
                'email' => $email,
                'active' => true,
            ]);
            $message = "Approved access for {$netid}.";
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'user' => [
                    'netid' => $user->netid,
                    'name' => $user->name,
                ],
                'redirect_url' => route('landing', ['view' => 'admin']),
                'csrf_token' => csrf_token(),
            ]);
        }

        return redirect()->route('landing', ['view' => 'admin'])
            ->with('success', $message);
    }
}
