<?php
include('DBconnect.php');

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

//user details
$user_query = "SELECT `First Name`, `Last Name` FROM user WHERE `User ID` = '$user_id'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

//role
function getUserRole($user_id, $conn) {
    $student_check = mysqli_query($conn, "SELECT 1 FROM `student profile` WHERE `Student ID` = '$user_id'");
    if (mysqli_num_rows($student_check) > 0) return 'student';
    
    $alumni_check = mysqli_query($conn, "SELECT 1 FROM `alumni profile` WHERE `Alumni ID` = '$user_id'");
    if (mysqli_num_rows($alumni_check) > 0) return 'alumni';
    
    $admin_check = mysqli_query($conn, "SELECT 1 FROM `admin` WHERE `Admin ID` = '$user_id'");
    if (mysqli_num_rows($admin_check) > 0) return 'admin';
    
    return 'unknown';
}

$user_role = getUserRole($user_id, $conn);

$message = '';
$message_type = '';
$show_ticket = false;
$ticket_data = null;
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'all'; 
$current_ticket = isset($_GET['ticket']) ? (int)$_GET['ticket'] : 0;

//download
if (isset($_GET['download']) && isset($_GET['event_id'])) {
    $event_id = mysqli_real_escape_string($conn, $_GET['event_id']);
    $download_type = $_GET['download']; 
    
    //tickets --> download
    $download_query = "SELECT t.*, e.`Title`, e.`Description`, e.`Date` as event_date 
                      FROM ticket t 
                      JOIN event e ON t.`E ID` = e.`Event ID` 
                      WHERE t.`E ID` = '$event_id' AND t.`U ID` = '$user_id'
                      ORDER BY t.`Serial No`";
    $download_result = mysqli_query($conn, $download_query);
    
    if ($download_result && mysqli_num_rows($download_result) > 0) {
        $tickets = mysqli_fetch_all($download_result, MYSQLI_ASSOC);
        
        if ($download_type === 'all') {
            // all tickets --> file
            $filename = 'event-tickets-' . preg_replace('/[^a-zA-Z0-9]/', '-', $tickets[0]['Title']) . '.txt';
            $content = generateAllTicketsContent($tickets, $user_data);
        } else {
            //specific ticket
            $ticket_index = (int)$download_type;
            if (isset($tickets[$ticket_index])) {
                $ticket = $tickets[$ticket_index];
                $filename = 'ticket-' . ($ticket_index + 1) . '-' . preg_replace('/[^a-zA-Z0-9]/', '-', $ticket['Title']) . '.txt';
                $content = generateSingleTicketContent($ticket, $user_data, $ticket_index + 1, count($tickets));
            }
        }
        
        if (isset($content)) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($content));
            echo $content;
            exit();
        }
    }
}

//content --> all tickets
function generateAllTicketsContent($tickets, $user_data) {
    $content = "STUDENT ALUMNI PORTAL - EVENT TICKETS\n";
    $content .= str_repeat('=', 50) . "\n\n";
    $content .= "Event: " . $tickets[0]['Title'] . "\n";
    $content .= "Event Date: " . date('M j, Y', strtotime($tickets[0]['event_date'])) . "\n";
    $content .= "Ticket Holder: " . $user_data['First Name'] . ' ' . $user_data['Last Name'] . "\n";
    $content .= "Total Tickets: " . count($tickets) . "\n";
    $content .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tickets as $index => $ticket) {
        $content .= "TICKET #" . ($index + 1) . "\n";
        $content .= str_repeat('-', 30) . "\n";
        $content .= "Serial Number: " . $ticket['Serial No'] . "\n";
        $content .= "Status: Confirmed\n";
        $content .= "Ticket " . ($index + 1) . " of " . count($tickets) . "\n\n";
    }
    
    $content .= "Please present these tickets at the event venue.\n";
    $content .= "Keep this confirmation for your records.\n";
    
    return $content;
}

//content --> single ticket
function generateSingleTicketContent($ticket, $user_data, $ticket_number, $total_tickets) {
    $content = "STUDENT ALUMNI PORTAL - EVENT TICKET\n";
    $content .= str_repeat('=', 50) . "\n\n";
    $content .= "Event: " . $ticket['Title'] . "\n";
    $content .= "Event Date: " . date('M j, Y', strtotime($ticket['event_date'])) . "\n";
    $content .= "Ticket Holder: " . $user_data['First Name'] . ' ' . $user_data['Last Name'] . "\n";
    $content .= "Ticket Number: " . $ticket_number . " of " . $total_tickets . "\n";
    $content .= "Serial Number: " . $ticket['Serial No'] . "\n";
    $content .= "Status: Confirmed\n";
    $content .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $content .= str_repeat('-', 50) . "\n";
    $content .= "Please present this ticket at the event venue.\n";
    $content .= "Keep this confirmation for your records.\n";
    
    return $content;
}

// booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_ticket'])) {
    $event_id = mysqli_real_escape_string($conn, $_POST['event_id']);
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity < 1 || $quantity > 3) {
        $message = "You can book between 1 and 3 tickets only.";
        $message_type = "error";
    } else {
        $event_check = "SELECT * FROM event WHERE `Event ID` = '$event_id'";
        $event_result = mysqli_query($conn, $event_check);
        
        if (!$event_result) {
            $message = "Database error occurred. Please try again.";
            $message_type = "error";
        } elseif (mysqli_num_rows($event_result) > 0) {
            $event_data = mysqli_fetch_assoc($event_result);
            
            $existing_tickets = "SELECT COUNT(*) as ticket_count FROM ticket WHERE `E ID` = '$event_id' AND `U ID` = '$user_id'";
            $existing_result = mysqli_query($conn, $existing_tickets);
            
            if (!$existing_result) {
                $message = "Database error occurred. Please try again.";
                $message_type = "error";
            } else {
                $existing_count = mysqli_fetch_assoc($existing_result)['ticket_count'];
                
                if ($existing_count + $quantity > 3) {
                    $message = "You cannot book more than 3 tickets per event. You already have $existing_count ticket(s).";
                    $message_type = "error";
                } else {
                    $booking_success = true;
                    $ticket_serials = [];
                    
                    mysqli_autocommit($conn, false);
                    
                    for ($i = 0; $i < $quantity; $i++) {
                        $serial_no = 'TKT_' . $event_id . '_' . $user_id . '_' . time() . '_' . ($i + 1);
                        $insert_ticket = "INSERT INTO ticket (`E ID`, `Serial No`, `Date of Event`, `U ID`) 
                                         VALUES ('$event_id', '$serial_no', '{$event_data['Date']}', '$user_id')";
                        
                        if (mysqli_query($conn, $insert_ticket)) {
                            $ticket_serials[] = $serial_no;
                        } else {
                            $booking_success = false;
                            break;
                        }
                    }
                    
                    if ($booking_success) {
                        mysqli_commit($conn);
                        mysqli_autocommit($conn, true);
                        
                        $message = "Successfully booked $quantity ticket(s)! Your ticket(s) are ready to view/download.";
                        $message_type = "success";
                        $show_ticket = true;
                        $ticket_data = [
                            'event' => $event_data,
                            'user' => $user_data,
                            'serials' => $ticket_serials,
                            'quantity' => $quantity,
                            'booking_date' => date('Y-m-d H:i:s')
                        ];
                    } else {
                        mysqli_rollback($conn);
                        mysqli_autocommit($conn, true);
                        
                        $message = "Error booking tickets. Please try again.";
                        $message_type = "error";
                    }
                }
            }
        } else {
            $message = "Selected event does not exist.";
            $message_type = "error";
        }
    }
}

//ticket viewing
if (isset($_GET['view_ticket']) && isset($_GET['event_id'])) {
    $event_id = mysqli_real_escape_string($conn, $_GET['event_id']);
    
    $tickets_query = "SELECT t.*, e.`Title`, e.`Description`, e.`Date` as event_date 
                     FROM ticket t 
                     JOIN event e ON t.`E ID` = e.`Event ID` 
                     WHERE t.`E ID` = '$event_id' AND t.`U ID` = '$user_id'";
    $tickets_result = mysqli_query($conn, $tickets_query);
    
    if ($tickets_result && mysqli_num_rows($tickets_result) > 0) {
        $show_ticket = true;
        $tickets = mysqli_fetch_all($tickets_result, MYSQLI_ASSOC);
        $ticket_data = [
            'event' => [
                'Event ID' => $event_id,
                'Title' => $tickets[0]['Title'],
                'Description' => $tickets[0]['Description'],
                'Date' => $tickets[0]['event_date']
            ],
            'user' => $user_data,
            'serials' => array_column($tickets, 'Serial No'),
            'quantity' => count($tickets),
            'booking_date' => $tickets[0]['Date of Event']
        ];
    }
}

//available events
$events_query = "SELECT * FROM event WHERE `Date` >= CURDATE() ORDER BY `Date` ASC";
$events_result = mysqli_query($conn, $events_query);

//booked tickets
$user_tickets_query = "SELECT t.*, e.`Title`, e.`Date` as event_date 
                      FROM ticket t 
                      JOIN event e ON t.`E ID` = e.`Event ID` 
                      WHERE t.`U ID` = '$user_id' 
                      ORDER BY e.`Date` DESC";
$user_tickets_result = mysqli_query($conn, $user_tickets_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Tickets - Student Alumni Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --primary-glow: rgba(99, 102, 241, 0.2);
            --secondary: #f8fafc;
            --accent: #1e1b4b;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --surface: #ffffff;
            --surface-elevated: #f9fafb;
            --border: #e2e8f0;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --gradient-primary: linear-gradient(135deg, #6366f1 0%, #7c3aed 50%, #6d28d9 100%);
            --shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gradient-primary);
            min-height: 100vh;
            color: var(--text-primary);
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-left h1 {
            font-size: 2.2rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .header-left p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            font-weight: 500;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }

        .btn-secondary {
            background: var(--surface-elevated);
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(99, 102, 241, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .alert {
            padding: 16px 20px;
            border-radius: var(--radius);
            margin-bottom: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tickets-container {
            margin-bottom: 30px;
        }

        .ticket-display {
            background: var(--surface);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border: 2px solid var(--primary);
        }

        .ticket-header {
            background: var(--gradient-primary);
            color: white;
            padding: 24px 30px;
            text-align: center;
            position: relative;
        }

        .ticket-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
        }

        .ticket-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .ticket-subtitle {
            opacity: 0.9;
            font-weight: 500;
        }

        .ticket-body {
            padding: 30px;
        }

        .ticket-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .info-item {
            text-align: center;
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .ticket-serials {
            background: var(--surface-elevated);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 24px;
        }

        .serial-item {
            background: white;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            border: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .serial-item:last-child {
            margin-bottom: 0;
        }

        .ticket-navigation {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin: 20px 0;
            padding: 20px;
            background: var(--surface-elevated);
            border-radius: var(--radius);
        }

        .ticket-counter {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            padding: 8px 16px;
            background: white;
            border-radius: 8px;
            border: 2px solid var(--primary);
        }

        .view-controls {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .single-view .ticket-display {
            display: none;
        }

        .single-view .ticket-display.active {
            display: block;
        }

        .booking-section {
            background: var(--surface-elevated);
            border-radius: var(--radius);
            padding: 30px;
            border: 1px solid var(--border);
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            font-size: 16px;
            font-family: inherit;
            transition: var(--transition);
            background: var(--surface);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }

        .event-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 24px;
            border: 1px solid var(--border);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .event-date {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--primary);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .event-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
            margin-right: 80px;
        }

        .event-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .my-tickets-list {
            background: var(--surface-elevated);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .ticket-item {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }

        .ticket-item:hover {
            background: var(--surface);
        }

        .ticket-item:last-child {
            border-bottom: none;
        }

        .ticket-item-info h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .ticket-item-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .no-content {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .no-content i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .no-content h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        @media print {
            body * {
                visibility: hidden;
            }
            
            .tickets-container,
            .tickets-container * {
                visibility: visible;
            }
            
            .tickets-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background: white !important;
            }
            
            .ticket-display {
                page-break-after: always;
                background: white !important;
                border: 2px solid #000 !important;
                margin-bottom: 0;
            }
            
            .ticket-display:last-child {
                page-break-after: auto;
            }
            
            .view-controls,
            .ticket-navigation,
            .no-print {
                display: none !important;
            }
            
            .single-view .ticket-display {
                display: block !important;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
                padding: 25px 20px;
            }

            .content {
                padding: 25px 20px;
            }

            .ticket-info {
                grid-template-columns: 1fr;
            }

            .view-controls {
                flex-direction: column;
            }

            .ticket-navigation {
                flex-direction: column;
                gap: 15px;
            }

            .events-grid {
                grid-template-columns: 1fr;
            }

            .ticket-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1><i class="fas fa-ticket-alt"></i> Event Tickets</h1>
                <p>Book tickets for upcoming events</p>
            </div>
            <div class="header-right">
                <a href="home.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="content">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($show_ticket && $ticket_data && count($ticket_data['serials']) > 0): ?>
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-ticket-alt"></i>
                        Your Tickets (<?php echo $ticket_data['quantity']; ?> tickets)
                    </h2>

                    <?php if ($ticket_data['quantity'] > 1): ?>
                        <div class="view-controls no-print">
                            <?php if ($view_mode === 'single'): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'all'])); ?>" class="btn btn-warning">
                                    <i class="fas fa-th"></i> View All Tickets
                                </a>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'single', 'ticket' => 0])); ?>" class="btn btn-warning">
                                    <i class="fas fa-eye"></i> View Individual Tickets
                                </a>
                            <?php endif; ?>
                            
                            <button onclick="window.print()" class="btn btn-primary">
                                <i class="fas fa-print"></i> Print All Tickets
                            </button>
                            
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['download' => 'all', 'event_id' => $ticket_data['event']['Event ID']])); ?>" class="btn btn-success">
                                <i class="fas fa-download"></i> Download All Tickets
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($view_mode === 'single' && $ticket_data['quantity'] > 1): ?>
                        <div class="ticket-navigation no-print">
                            <?php 
                            $prev_ticket = max(0, $current_ticket - 1);
                            $next_ticket = min($ticket_data['quantity'] - 1, $current_ticket + 1);
                            $base_params = array_merge($_GET, ['view' => 'single']);
                            ?>
                            
                            <?php if ($current_ticket > 0): ?>
                                <a href="?<?php echo http_build_query(array_merge($base_params, ['ticket' => $prev_ticket])); ?>" class="btn btn-secondary">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                            <?php else: ?>
                                <span class="btn btn-secondary" style="opacity: 0.5;">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </span>
                            <?php endif; ?>
                            
                            <div class="ticket-counter">
                                <?php echo ($current_ticket + 1); ?> of <?php echo $ticket_data['quantity']; ?>
                            </div>
                            
                            <?php if ($current_ticket < $ticket_data['quantity'] - 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($base_params, ['ticket' => $next_ticket])); ?>" class="btn btn-secondary">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="btn btn-secondary" style="opacity: 0.5;">
                                    Next <i class="fas fa-chevron-right"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="tickets-container <?php echo $view_mode === 'single' ? 'single-view' : ''; ?>">
                        <?php foreach ($ticket_data['serials'] as $index => $serial): ?>
                            <div class="ticket-display <?php echo ($view_mode === 'single' && $index === $current_ticket) ? 'active' : ''; ?>">
                                <div class="ticket-header">
                                    <div class="ticket-title"><?php echo htmlspecialchars($ticket_data['event']['Title']); ?></div>
                                    <div class="ticket-subtitle">
                                        <?php if ($ticket_data['quantity'] > 1): ?>
                                            Ticket #<?php echo $index + 1; ?>
                                        <?php else: ?>
                                            Event Ticket
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="ticket-body">
                                    <div class="ticket-info">
                                        <div class="info-item">
                                            <div class="info-label">Event Date</div>
                                            <div class="info-value"><?php echo date('M j, Y', strtotime($ticket_data['event']['Date'])); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-label">Ticket Holder</div>
                                            <div class="info-value"><?php echo htmlspecialchars($ticket_data['user']['First Name'] . ' ' . $ticket_data['user']['Last Name']); ?></div>
                                        </div>
                                        <?php if ($ticket_data['quantity'] > 1): ?>
                                            <div class="info-item">
                                                <div class="info-label">Ticket Number</div>
                                                <div class="info-value"><?php echo $index + 1; ?> of <?php echo $ticket_data['quantity']; ?></div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="info-item">
                                            <div class="info-label">Status</div>
                                            <div class="info-value">Confirmed</div>
                                        </div>
                                    </div>

                                    <div class="ticket-serials">
                                        <h4 style="margin-bottom: 12px; font-size: 1rem; color: var(--text-secondary);">
                                            <i class="fas fa-barcode"></i> Ticket Serial Number
                                        </h4>
                                        <div class="serial-item">
                                            <span><?php echo htmlspecialchars($serial); ?></span>
                                            <i class="fas fa-qrcode" style="color: var(--primary);"></i>
                                        </div>
                                    </div>

                                    <?php if ($ticket_data['quantity'] > 1): ?>
                                        <div class="individual-ticket-actions no-print" style="text-align: center; margin-top: 20px;">
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['download' => $index, 'event_id' => $ticket_data['event']['Event ID']])); ?>" class="btn btn-success btn-sm">
                                                <i class="fas fa-download"></i> Download This Ticket
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="view-controls no-print">
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="fas fa-print"></i> Print Tickets
                        </button>
                        
                        <?php if ($ticket_data['quantity'] === 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['download' => '0', 'event_id' => $ticket_data['event']['Event ID']])); ?>" class="btn btn-success">
                                <i class="fas fa-download"></i> Download Ticket
                            </a>
                        <?php else: ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['download' => 'all', 'event_id' => $ticket_data['event']['Event ID']])); ?>" class="btn btn-success">
                                <i class="fas fa-download"></i> Download All Tickets
                            </a>
                        <?php endif; ?>
                        
                        <a href="ticket.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Booking
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$show_ticket): ?>
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-calendar-plus"></i>
                        Book Event Tickets
                    </h2>
                    
                    <?php if ($events_result && mysqli_num_rows($events_result) > 0): ?>
                        <div class="events-grid">
                            <?php while ($event = mysqli_fetch_assoc($events_result)): ?>
                                <div class="event-card">
                                    <div class="event-date"><?php echo date('M j', strtotime($event['Date'])); ?></div>
                                    <h3 class="event-title"><?php echo htmlspecialchars($event['Title']); ?></h3>
                                    <p class="event-description"><?php echo htmlspecialchars(substr($event['Description'], 0, 120)) . (strlen($event['Description']) > 120 ? '...' : ''); ?></p>
                                    
                                    <div class="booking-section">
                                        <form method="POST" action="">
                                            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event['Event ID']); ?>">
                                            
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fas fa-hashtag"></i> Quantity (Max 3)
                                                </label>
                                                <select name="quantity" class="form-control" required>
                                                    <option value="1">1 Ticket</option>
                                                    <option value="2">2 Tickets</option>
                                                    <option value="3">3 Tickets</option>
                                                </select>
                                            </div>
                                            
                                            <button type="submit" name="book_ticket" class="btn btn-primary" style="width: 100%;">
                                                <i class="fas fa-ticket-alt"></i> Book Tickets
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-content">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Upcoming Events</h3>
                            <p>There are no upcoming events available for booking at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-history"></i>
                    My Tickets
                </h2>
                
                <?php if ($user_tickets_result && mysqli_num_rows($user_tickets_result) > 0): ?>
                    <div class="my-tickets-list">
                        <?php
                        // Group tickets
                        $grouped_tickets = array();
                        mysqli_data_seek($user_tickets_result, 0); 
                        while ($ticket = mysqli_fetch_assoc($user_tickets_result)) {
                            $event_id = $ticket['E ID'];
                            if (!isset($grouped_tickets[$event_id])) {
                                $grouped_tickets[$event_id] = array();
                            }
                            $grouped_tickets[$event_id][] = $ticket;
                        }

                        foreach ($grouped_tickets as $event_id => $tickets) {
                            $first_ticket = $tickets[0];
                        ?>
                            <div class="ticket-item">
                                <div class="ticket-item-info">
                                    <h4><?php echo htmlspecialchars($first_ticket['Title']); ?></h4>
                                    <p><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($first_ticket['event_date'])); ?> | <?php echo count($tickets); ?> ticket<?php echo count($tickets) > 1 ? 's' : ''; ?></p>
                                </div>
                                <div class="ticket-item-actions">
                                    <a href="?view_ticket=1&event_id=<?php echo $event_id; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View Tickets
                                    </a>
                                </div>
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <div class="no-content">
                        <i class="fas fa-ticket-alt"></i>
                        <h3>No Tickets Yet</h3>
                        <p>You haven't booked any tickets yet. Check out the upcoming events above!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Confirm
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                if (form.querySelector('button[name="book_ticket"]')) {
                    form.addEventListener('submit', function(e) {
                        const eventTitle = this.closest('.event-card').querySelector('.event-title').textContent;
                        const quantity = this.querySelector('select[name="quantity"]').value;
                        
                        if (!confirm(`Confirm booking ${quantity} ticket(s) for "${eventTitle}"?`)) {
                            e.preventDefault();
                        }
                    });
                }
            });

            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.3s, transform 0.3s';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }, 8000);
            });
        });

        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>