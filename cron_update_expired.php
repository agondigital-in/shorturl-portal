<?php
/**
 * Cron job script to automatically update expired campaigns
 * This script should be run daily to check for expired campaigns
 * and update their status to inactive
 */

require_once 'config.php';

// Update campaigns where end date is before today and status is still active
$sql = "UPDATE campaigns SET status = 'inactive' WHERE end_date < CURDATE() AND status = 'active'";
if ($conn->query($sql) === TRUE) {
    echo "Expired campaigns updated successfully. Rows affected: " . $conn->affected_rows . "\n";
} else {
    echo "Error updating expired campaigns: " . $conn->error . "\n";
}

$conn->close();
?>