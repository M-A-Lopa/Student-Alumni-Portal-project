<?php
include('DBconnect.php');
session_start();

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
                  $params["path"], $params["domain"],
                  $params["secure"], $params["httponly"]
        );
    }
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$admin_check = "SELECT * FROM `admin` WHERE `Admin ID`='$user_id'";
$result = mysqli_query($conn, $admin_check);
if (mysqli_num_rows($result) == 0) {
    header("Location: home.php");
    exit;
}
$admin_profile = mysqli_fetch_assoc($result);

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'create_event') {
            $title = mysqli_real_escape_string($conn, $_POST['title']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            $event_date = mysqli_real_escape_string($conn, $_POST['event_date']);

            if (!empty($title) && !empty($description) && !empty($event_date)) {
                // Create event
                $post_id = uniqid('post_');
                $post_type = 'event';

                $sql_post = "INSERT INTO post (`Post ID`, `Title`, `Content`, `Date of Post`, `Type`, `Ur ID`) VALUES ('$post_id', '$title', '$description', NOW(), '$post_type', '$user_id')";
                if (mysqli_query($conn, $sql_post)) {
                    //insert event
                    $sql_event = "INSERT INTO event (`Event ID`, `Title`, `Description`, `Date`, `Approved By`) VALUES ('$post_id', '$title', '$description', '$event_date', '$user_id')";
                    if (mysqli_query($conn, $sql_event)) {
                        $message = "Event and corresponding post created successfully.";
                        $message_type = "success";
                    } else {
                        mysqli_query($conn, "DELETE FROM post WHERE `Post ID`='$post_id'");
                        $message = "Error creating event: " . mysqli_error($conn);
                        $message_type = "error";
                    }
                } else {
                    $message = "Error creating post for event: " . mysqli_error($conn);
                    $message_type = "error";
                }
            } else {
                $message = "Please fill all required fields.";
                $message_type = "error";
            }
            // update event
        } elseif ($action === 'update_event' && isset($_POST['event_id'])) {
            $event_id = mysqli_real_escape_string($conn, $_POST['event_id']);
            $title = mysqli_real_escape_string($conn, $_POST['title']);
            $description = mysqli_real_escape_string($conn, $_POST['description']);
            $event_date = mysqli_real_escape_string($conn, $_POST['event_date']);

            if (!empty($title) && !empty($description) && !empty($event_date)) {
                $sql_event_update = "UPDATE event SET `Title`='$title', `Description`='$description', `Date`='$event_date' WHERE `Event ID`='$event_id'";
                $sql_post_update = "UPDATE post SET `Title`='$title', `Content`='$description' WHERE `Post ID`='$event_id'";

                if (mysqli_query($conn, $sql_event_update) && mysqli_query($conn, $sql_post_update)) {
                    $message = "Event updated successfully.";
                    $message_type = "success";
                } else {
                    $message = "Error updating event: " . mysqli_error($conn);
                    $message_type = "error";
                }
            } else {
                $message = "Please fill all required fields.";
                $message_type = "error";
            }
        } elseif ($action === 'delete_event' && isset($_POST['event_id'])) {
            $event_id = mysqli_real_escape_string($conn, $_POST['event_id']);
            mysqli_query($conn, "DELETE FROM ticket WHERE `E ID`='$event_id'");
            
            // Remove event
            if (mysqli_query($conn, "DELETE FROM event WHERE `Event ID`='$event_id'") &&
                mysqli_query($conn, "DELETE FROM post WHERE `Post ID`='$event_id'")) {
                $message = "Event and associated post deleted successfully.";
                $message_type = "success";
            } else {
                $message = "Error deleting event: " . mysqli_error($conn);
                $message_type = "error";
            }
        }
    }
}
// search 
$search = $_GET['search'] ?? '';
$filter = $_GET['date_filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page -1) * $per_page;

$where = [];
if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(e.Title LIKE '%$s%' OR e.Description LIKE '%$s%' OR u.`First Name` LIKE '%$s%' OR u.`Last Name` LIKE '%$s%')";
}

//time
$today = date('Y-m-d');
if ($filter && $filter !== 'all') {
    if ($filter === 'upcoming') $where[] = "e.Date >= '$today'";
    else if ($filter === 'past') $where[] = "e.Date < '$today'";
    else if ($filter === 'today') $where[] = "e.Date = '$today'";
    else if ($filter === 'this_week') {
        $start = date('Y-m-d', strtotime('monday this week'));
        $end = date('Y-m-d', strtotime('sunday this week'));
        $where[] = "e.Date BETWEEN '$start' AND '$end'";
    } else if ($filter === 'this_month') {
        $start = date('Y-m-01');
        $end = date('Y-m-t');
        $where[] = "e.Date BETWEEN '$start' AND '$end'";
    }
}

$w = count($where) ? 'WHERE '.implode(' AND ', $where) : '';

//sort
$order = match($sort) {
    'oldest' => 'ORDER BY e.Date ASC',
    'title' => 'ORDER BY e.Title ASC',
    'date_created' => 'ORDER BY e.`Event ID` DESC',
    default => 'ORDER BY e.Date DESC',
};

$count_sql = "SELECT COUNT(*) AS total FROM event e JOIN user u ON e.`Approved By`=u.`User ID` $w";
$count_res = mysqli_query($conn, $count_sql);
$total = $count_res ? intval(mysqli_fetch_assoc($count_res)['total']) : 0;

$total_pages = ceil($total / $per_page);

$fetch_sql = "SELECT e.*, u.`First Name`, u.`Last Name` FROM event e JOIN user u ON e.`Approved By`=u.`User ID` $w $order LIMIT $offset, $per_page";
$fetch_res = mysqli_query($conn, $fetch_sql);

function formatDateInfo($date) {
    $d = new DateTime($date);
    $now = new DateTime();
    if ($d->format('Y-m-d') === $now->format('Y-m-d')) return ['label'=>'Today','class'=>'date-today'];
    if ($d > $now) return ['label'=>$d->format('M j, Y'),'class'=>'date-upcoming'];
    return ['label'=>$d->format('M j, Y'),'class'=>'date-past'];
}

function ticketCount($id, $conn) {
    $r = mysqli_query($conn,"SELECT COUNT(*) as cnt FROM ticket WHERE `E ID`='".mysqli_real_escape_string($conn,$id)."'");
    if ($r) return intval(mysqli_fetch_assoc($r)['cnt']);
    return 0;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Manage Events</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        color: #333;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        opacity: 0;
        animation: fadeIn 0.6s ease-out forwards;
    }

    @keyframes fadeIn {
        to { opacity: 1; }
    }

    .header {
        background: white;
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-left h1 {
        font-size: 2rem;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 8px;
    }

    .header-left h1 i {
        color: #667eea;
        margin-right: 12px;
    }

    .header-left p {
        color: #718096;
        font-size: 1rem;
    }

    .header-right {
        display: flex;
        gap: 12px;
    }

    /* Statistics Cards */
    .stats-section {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .stat-number.total { color: #4299e1; }
    .stat-number.pending { color: #ed8936; }
    .stat-number.approved { color: #48bb78; }
    .stat-number.rejected { color: #f56565; }

    .stat-label {
        color: #718096;
        font-size: 0.875rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .content-section {
        background: white;
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .section-header {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f7fafc;
    }

    .section-header h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #2d3748;
    }

    .section-header i {
        color: #667eea;
        margin-right: 10px;
        font-size: 1.1rem;
    }

    .event-form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        align-items: end;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-label {
        font-weight: 500;
        color: #4a5568;
        margin-bottom: 8px;
        font-size: 0.875rem;
    }

    .form-input {
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        background: #fafafa;
    }

    .form-input:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    textarea.form-input {
        resize: vertical;
        min-height: 80px;
    }

    .filters-section {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 25px;
    }

    .filters-section label {
        display: flex;
        flex-direction: column;
        gap: 5px;
        font-size: 0.875rem;
        font-weight: 500;
        color: #4a5568;
    }

    .filters-section input,
    .filters-section select {
        padding: 10px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.875rem;
        background: white;
        min-width: 140px;
    }

    .events-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .events-table th {
        background: #f8fafc;
        padding: 15px 12px;
        text-align: left;
        font-weight: 600;
        color: #4a5568;
        border-bottom: 2px solid #e2e8f0;
        font-size: 0.875rem;
    }

    .events-table td {
        padding: 15px 12px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: top;
    }

    .events-table tr:hover {
        background: #f8fafc;
    }

    .event-title {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 4px;
    }

    .event-description {
        color: #718096;
        font-size: 0.875rem;
        line-height: 1.4;
    }

    .date-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .date-today {
        background: #fed7d7;
        color: #c53030;
    }

    .date-upcoming {
        background: #c6f6d5;
        color: #2f855a;
    }

    .date-past {
        background: #e2e8f0;
        color: #718096;
    }

    .ticket-count {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #667eea;
        font-weight: 500;
        font-size: 0.875rem;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        text-align: center;
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #5a67d8;
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: #718096;
        color: white;
    }

    .btn-secondary:hover {
        background: #4a5568;
    }

    .btn-success {
        background: #48bb78;
        color: white;
    }

    .btn-success:hover {
        background: #38a169;
    }

    .btn-warning {
        background: #ed8936;
        color: white;
    }

    .btn-warning:hover {
        background: #dd6b20;
    }

    .btn-danger {
        background: #f56565;
        color: white;
    }

    .btn-danger:hover {
        background: #e53e3e;
    }

    .btn-sm {
        padding: 8px 12px;
        font-size: 0.75rem;
    }

    .alert {
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
    }

    .alert-success {
        background: #c6f6d5;
        color: #2f855a;
        border-left: 4px solid #48bb78;
    }

    .alert-error {
        background: #fed7d7;
        color: #c53030;
        border-left: 4px solid #f56565;
    }

    .no-events {
        text-align: center;
        padding: 60px 20px;
        color: #718096;
    }

    .no-events i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .no-events h3 {
        font-size: 1.5rem;
        margin-bottom: 10px;
        color: #4a5568;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 30px;
        flex-wrap: wrap;
    }

    .pagination a,
    .pagination span {
        padding: 10px 15px;
        border-radius: 6px;
        text-decoration: none;
        color: #4a5568;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .pagination a:hover {
        background: #667eea;
        color: white;
    }

    .pagination .current {
        background: #667eea;
        color: white;
    }

    .pagination .disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    @media (max-width: 768px) {
        .header {
            flex-direction: column;
            gap: 20px;
            text-align: center;
        }
        
        .event-form {
            grid-template-columns: 1fr;
        }
        
        .filters-section {
            flex-direction: column;
            align-items: stretch;
        }
        
        .events-table {
            font-size: 0.75rem;
        }
        
        .events-table th,
        .events-table td {
            padding: 8px;
        }
    }

    @media (max-width: 480px) {
        .container {
            padding: 10px;
        }
        
        .content-section {
            padding: 20px;
        }
        
        .stats-section {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>
<body>
<div class="container fade-in">
  <div class="header">
    <div class="header-left">
      <h1><i class="fas fa-calendar-alt"></i> Manage Events</h1>
      <p>Create and manage community events</p>
    </div>
    <div class="header-right">
      <a href="home.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
      <a href="?logout=1" class="btn btn-danger" onclick="return confirm('Logout?')">Logout</a>
    </div>
  </div>

  <div class="stats-section">
    <div class="stat-card">
        <div class="stat-number total"><?= $total ?></div>
        <div class="stat-label">Total Events</div>
    </div>
    <div class="stat-card">
        <div class="stat-number pending">
            <?php 
            $pending_count = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM event WHERE Date >= CURDATE()");
            echo $pending_count ? mysqli_fetch_assoc($pending_count)['cnt'] : 0;
            ?>
        </div>
        <div class="stat-label">Upcoming</div>
    </div>
    <div class="stat-card">
        <div class="stat-number approved">
            <?php 
            $today_count = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM event WHERE Date = CURDATE()");
            echo $today_count ? mysqli_fetch_assoc($today_count)['cnt'] : 0;
            ?>
        </div>
        <div class="stat-label">Today</div>
    </div>
    <div class="stat-card">
        <div class="stat-number rejected">
            <?php 
            $past_count = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM event WHERE Date < CURDATE()");
            echo $past_count ? mysqli_fetch_assoc($past_count)['cnt'] : 0;
            ?>
        </div>
        <div class="stat-label">Past Events</div>
    </div>
  </div>

  <div class="content">
    <?php if ($message): ?>
      <div class="alert <?= $message_type==='success'?'alert-success':'alert-error' ?>">
        <i class="fas <?= $message_type==='success'?'fa-check-circle':'fa-exclamation-triangle' ?>"></i>
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <div class="content-section">
      <div class="section-header">
        <h3><i class="fas fa-plus"></i> Create New Event</h3>
      </div>
      <form method="POST" action="" class="event-form">
        <input type="hidden" name="action" value="create_event" />
        <div class="form-group">
          <label class="form-label">Event Title *</label>
          <input type="text" name="title" class="form-input" required placeholder="Enter event title" />
        </div>
        <div class="form-group">
          <label class="form-label">Event Date *</label>
          <input type="date" name="event_date" class="form-input" required min="<?= date('Y-m-d') ?>" />
        </div>
        <div class="form-group full-width">
          <label class="form-label">Description *</label>
          <textarea name="description" class="form-input" required placeholder="Enter event description"></textarea>
        </div>
        <div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create Event</button>
        </div>
      </form>
    </div>

    <div class="content-section">
      <div class="filters-section">
        <form method="GET" style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap; width: 100%;">
          <label>Search: <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" /></label>
          <label>Date Filter:
            <select name="date_filter">
              <option value="all" <?= $filter=='all'?'selected':'' ?>>All</option>
              <option value="upcoming" <?= $filter=='upcoming'?'selected':'' ?>>Upcoming</option>
              <option value="today" <?= $filter=='today'?'selected':'' ?>>Today</option>
              <option value="this_week" <?= $filter=='this_week'?'selected':'' ?>>This Week</option>
              <option value="this_month" <?= $filter=='this_month'?'selected':'' ?>>This Month</option>
              <option value="past" <?= $filter=='past'?'selected':'' ?>>Past</option>
            </select>
          </label>
          <label>Sort:
            <select name="sort">
              <option value="newest" <?= $sort=='newest'?'selected':'' ?>>Newest</option>
              <option value="oldest" <?= $sort=='oldest'?'selected':'' ?>>Oldest</option>
              <option value="title" <?= $sort=='title'?'selected':'' ?>>Title</option>
              <option value="date_created" <?= $sort=='date_created'?'selected':'' ?>>Recently Created</option>
            </select>
          </label>
          <button type="submit" class="btn btn-primary">Filter</button>
        </form>
      </div>
    </div>

    <div class="content-section">
      <div class="section-header">
        <h3><i class="fas fa-list"></i> Events (<?= $total ?> total)</h3>
      </div>
      
      <?php if ($fetch_res && mysqli_num_rows($fetch_res) > 0): ?>
        <form method="POST" id="bulkForm">
          <table class="events-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Created By</th>
                <th>Tickets</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($fetch_res)):
                  $dateInfo = formatDateInfo($row['Date']);
                  $tickets = ticketCount($row['Event ID'], $conn);
              ?>
              <tr>
                <td><input type="checkbox" name="selected_events[]" value="<?= htmlspecialchars($row['Event ID']) ?>" /></td>
                <td>
                  <div class="event-title"><?= htmlspecialchars($row['Title']) ?></div>
                  <div class="event-description">
                    <?= htmlspecialchars(mb_strimwidth($row['Description'], 0, 100, "...")) ?>
                  </div>
                </td>
                <td><span class="date-badge <?= $dateInfo['class'] ?>"><?= $dateInfo['label'] ?></span></td>
                <td><?= htmlspecialchars($row['First Name'] . ' ' . $row['Last Name']) ?></td>
                <td><span class="ticket-count"><i class="fas fa-ticket-alt"></i> <?= $tickets ?></span></td>
                <td>
                  <a href="?edit=<?= urlencode($row['Event ID']) ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this event?')">
                    <input type="hidden" name="action" value="delete_event" />
                    <input type="hidden" name="event_id" value="<?= htmlspecialchars($row['Event ID']) ?>" />
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                  </form>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        
        <div class="pagination">
          <?php if ($total_pages > 1):
            if ($page > 1): ?>
              <a href="?page=1&search=<?= urlencode($search) ?>&date_filter=<?= $filter ?>&sort=<?= $sort ?>">&laquo; First</a>
              <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&date_filter=<?= $filter ?>&sort=<?= $sort ?>">&lsaquo; Prev</a>
            <?php else: ?>
              <span class="disabled">&laquo; First</span>
              <span class="disabled">&lsaquo; Prev</span>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);

            if ($start > 1) {
                echo '<a href="?page=1&search=' . urlencode($search) . '&date_filter=' . $filter . '&sort=' . $sort . '">1</a>';
                if ($start > 2) echo '<span>...</span>';
            }

            for ($i = $start; $i <= $end; ++$i) {
                if ($i == $page) {
                    echo '<span class="current">' . $i . '</span>';
                } else {
                    echo '<a href="?page=' . $i . '&search=' . urlencode($search) . '&date_filter=' . $filter . '&sort=' . $sort . '">' . $i . '</a>';
                }
            }

            if ($end < $total_pages) {
                if ($end < $total_pages - 1) echo '<span>...</span>';
                echo '<a href="?page=' . $total_pages . '&search=' . urlencode($search) . '&date_filter=' . $filter . '&sort=' . $sort . '">' . $total_pages . '</a>';
            }

            if ($page < $total_pages): ?>
              <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&date_filter=<?= $filter ?>&sort=<?= $sort ?>">Next &rsaquo;</a>
            <?php else: ?>
              <span class="disabled">Next &rsaquo;</span>
            <?php endif;
          endif; ?>
        </div>
      <?php else: ?>
        <div class="no-events">
          <i class="fas fa-calendar-times"></i>
          <h3>No Events Found</h3>
          <p>Try adjusting filters or create new events.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function confirmDelete() { return confirm('Are you sure you want to delete this event?'); }
</script>
</body>
</html>