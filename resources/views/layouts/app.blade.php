<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Sidebar styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100%;
            background-color: #1A202C;
            padding-top: 20px;
        }
        .sidebar a {
            color: white;
            padding: 15px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }
        .sidebar a:hover {
            background-color: #2D3748;
        }
        .content {
            margin-left: 250px;
            padding: 20px;
        }
        .footer {
            font-size: 0.875rem;
            color: #6B7280;
            padding: 10px 0;
            text-align: center;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2 class="text-2xl text-white text-center mb-10">Dashboard</h2>
            <a href="#companyData">Company Data</a>
            <a href="#orderTracking">Order Tracking</a>
            <a href="#contactDetails">Contact Details</a>
        </div>

        <!-- Content -->
        <div class="content">
            @yield('content')
        </div>
    </div>
</body>
</html>
