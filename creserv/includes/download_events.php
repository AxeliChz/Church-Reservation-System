<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    die("Unauthorized access");
}

$db = new Database();
$conn = $db->connect();


$events = $conn->query("SELECT e.*, u.username, u.email 
                        FROM event e 
                        LEFT JOIN users u ON e.user_id = u.id 
                        ORDER BY e.event_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$format = $_GET['format'] ?? 'csv';

if ($format === 'csv') {
  
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="Church_Events_' . date('Y-m-d') . '.csv"');
    header('Cache-Control: max-age=0');
    
   
    $output = fopen('php://output', 'w');
    
    
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
 
    fputcsv($output, [
        'ID',
        'Event Name',
        'Event Date',
        'Start Time',
        'Description',
        'User Name',
        'User Email',
        'Created At'
    ]);
    
   
    foreach ($events as $event) {
        fputcsv($output, [
            $event['id'],
            $event['event_name'],
            date('Y-m-d', strtotime($event['event_date'])),
            date('H:i', strtotime($event['start_time'])),
            $event['description'] ?: 'N/A',
            $event['username'] ?: 'N/A',
            $event['email'] ?: 'N/A',
            $event['created_at'] ?? 'N/A'
        ]);
    }
    
    fclose($output);
    exit;
}
?>
