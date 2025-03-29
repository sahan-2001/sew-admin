<?php
// filepath: /C:/Users/User/Desktop/Sahan_Personal Files/Academics/project/sew-admin/resources/views/exports/activities.blade.php
<!DOCTYPE html>
<html>
<head>
    <title>Activity Logs</title>
</head>
<body>
    <h1>Activity Logs</h1>
    <table>
        <thead>
            <tr>
                <th>Log Name</th>
                <th>Description</th>
                <th>Caused By</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($activities as $activity)
                <tr>
                    <td>{{ $activity->log_name }}</td>
                    <td>{{ $activity->description }}</td>
                    <td>{{ $activity->causer->name ?? 'N/A' }}</td>
                    <td>{{ $activity->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>