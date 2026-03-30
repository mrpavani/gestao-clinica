<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['patient_id'] = 1;
$_POST['professional_id'] = 1;
$_POST['therapy_id'] = 1;
$_POST['start_time'] = '2026-03-30T08:00';
$_POST['duration'] = 60;
$_POST['notes'] = '';
$_POST['recurrence_mode'] = 'recurrent';
$_POST['repeat_end_date'] = '2026-04-30';

// Bypass auth locally by setting session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['branch_id'] = 1;

require_once 'ajax/save_appointment.php';
