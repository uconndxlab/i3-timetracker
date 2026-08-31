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

    public function updateProduct(Request $request, User $user)
    {
        $validated = $request->validate([
            'product_id' => 'nullable|string',
        ]);

        $user->update([
            'honeycrisp_product_id' => $validated['product_id'] ?: null,
        ]);

        return response()->json(['success' => true]);
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
}
