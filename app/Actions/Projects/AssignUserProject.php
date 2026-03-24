<?php

namespace App\Actions\Projects;

use App\Mail\UserAddedToProject;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AssignUserProject
{
    public function __invoke(Project $project, array $selectedNetids): array
    {
        $existingUserNetids = $project->users->pluck('netid')->toArray();
        $newUserNetids = array_values(array_diff($selectedNetids, $existingUserNetids));

        if (empty($newUserNetids)) {
            return [
                'assigned_count' => 0,
                'assigned_netids' => [],
            ];
        }

        $syncData = [];
        foreach ($newUserNetids as $netid) {
            $syncData[$netid] = ['active' => true];
        }

        $project->users()->syncWithoutDetaching($syncData);

        foreach ($newUserNetids as $netid) {
            $user = User::where('netid', $netid)->first();
            if ($user && $user->email) {
                Mail::to($user->email)->send(new UserAddedToProject($user, $project));
            }
        }

        return [
            'assigned_count' => count($newUserNetids),
            'assigned_netids' => $newUserNetids,
        ];
    }
}
