<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: join.html');
    exit;
}

// Simple hidden-field spam check.
if (!empty($_POST['website'])) {
    header('Location: join.html?status=success');
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$grade = trim($_POST['grade'] ?? '');
$interest = trim($_POST['interest'] ?? '');
$message = trim($_POST['message'] ?? '');

$allowedGrades = ['9', '10', '11', '12'];
$allowedInterests = [
    '',
    'Arduino / Electronics',
    'Coding',
    'Engineering Projects',
    'Volunteering',
    'Not sure yet'
];

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($grade, $allowedGrades, true) || !in_array($interest, $allowedInterests, true)) {
    header('Location: join.html?status=invalid');
    exit;
}

// Remove line breaks from values used in email headers.
$safeName = str_replace(["\r", "\n"], ' ', $name);
$safeEmail = str_replace(["\r", "\n"], '', $email);

$to = 'chssugarcodeit@gmail.com';
$subject = 'Sugar Code It member interest: ' . $safeName;

$body = "New Sugar Code It member interest form submission\n\n";
$body .= "Name: " . $name . "\n";
$body .= "School email: " . $email . "\n";
$body .= "Grade: " . $grade . "\n";
$body .= "Primary interest: " . ($interest !== '' ? $interest : 'Not selected') . "\n\n";
$body .= "What they would like to learn or build:\n";
$body .= ($message !== '' ? $message : 'No response') . "\n";

// Use an address on the website domain as the sender so hosting mail checks are less likely to reject it.
$headers = [];
$headers[] = 'From: Sugar Code It Website <noreply@sugar-code-it.com>';
$headers[] = 'Reply-To: ' . $safeName . ' <' . $safeEmail . '>';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';

$sent = mail($to, $subject, $body, implode("\r\n", $headers));

if ($sent) {
    header('Location: join.html?status=success');
} else {
    header('Location: join.html?status=error');
}
exit;
