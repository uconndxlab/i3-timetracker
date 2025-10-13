<x-mail::message>
# Added to Timetracker Project

<p>You have been added to a new project: {{ $project->name }}. You can now log time for this project and view project details.</p>

<x-mail::button :url="route('landing')">
Visit Timetracker
</x-mail::button>

<div class="footer">
    <p>This is an automated message from the i3 Time Tracker system.</p>
    <p>If you have questions, please contact the project administrator.</p>
</div>


</x-mail::message>