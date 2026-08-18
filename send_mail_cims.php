<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// ── Load PHPMailer (install via composer: composer require phpmailer/phpmailer) ──
function loadPHPMailer() {
    $composerAutoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($composerAutoload)) { require $composerAutoload; return true; }
    return false;
}
$phpMailerLoaded = loadPHPMailer();

function clean($v) { return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8'); }

// ── Which track is this submission? ──
$track = clean($_POST['track'] ?? 'individual'); // 'individual' | 'partnership'
$ref   = clean($_POST['ref']   ?? '');

$uploadDir = __DIR__ . '/uploads/cims-applications/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

function handleUpload($fieldName, $uploadDir, $ref, $maxBytes, $allowedExts) {
    if (empty($_FILES[$fieldName]['name'])) return ['ok' => false, 'path' => null, 'origName' => null];
    $file    = $_FILES[$fieldName];
    $ext     = strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION));
    $newName = $ref . '_' . $fieldName . '.' . $ext;
    $dest    = $uploadDir . $newName;
    if ($file['error'] !== UPLOAD_ERR_OK)     return ['ok' => false, 'path' => null, 'origName' => $file['name']];
    if ($file['size'] > $maxBytes)            return ['ok' => false, 'path' => null, 'origName' => $file['name']];
    if (!in_array($ext, $allowedExts))        return ['ok' => false, 'path' => null, 'origName' => $file['name']];
    if (!move_uploaded_file($file['tmp_name'], $dest)) return ['ok' => false, 'path' => null, 'origName' => $file['name']];
    return ['ok' => true, 'path' => $dest, 'name' => $newName, 'origName' => $file['name']];
}

$imgExts = ['pdf','jpg','jpeg','png'];
$docExts = ['pdf','doc','docx'];

$nl  = "\r\n";
$sep = "============================================================{$nl}";
$sub = "------------------------------------------------------------{$nl}";
$now = date('l, d F Y \a\t H:i T');

function sendEmail($phpMailerLoaded, $subject, $body, $replyToEmail, $replyToName, $uploads) {
    $sent = false; $warning = '';
    if ($phpMailerLoaded) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSendmail();
            $mail->setFrom('noreply@charteredims.com', 'CIMS Application Portal');
            $mail->addAddress('admin@charteredims.com', 'CIMS Secretariat');
            if ($replyToEmail) $mail->addReplyTo($replyToEmail, $replyToName);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->isHTML(false);
            foreach ($uploads as $up) {
                if ($up['ok'] && $up['path'] && file_exists($up['path'])) {
                    $mail->addAttachment($up['path'], $up['origName']);
                }
            }
            $mail->send();
            $sent = true;
        } catch (\Exception $e) {
            $warning = 'PHPMailer: ' . $e->getMessage();
        }
    }
    if (!$sent) {
        $headers  = "From: noreply@charteredims.com\r\n";
        if ($replyToEmail) $headers .= "Reply-To: {$replyToEmail}\r\n";
        $fallbackNote = "\r\n\r\nNOTE: File attachments could not be sent via this fallback method. Retrieve from server: uploads/cims-applications/\r\n" . ($warning ? "Error: $warning" : '');
        $sent = mail('admin@charteredims.com', $subject, $body . $fallbackNote, $headers);
    }
    return ['sent' => $sent, 'warning' => $warning];
}

// =====================================================================
// TRACK: PARTNERSHIP (organisation accreditation)
// =====================================================================
if ($track === 'partnership') {

    $partnershipType    = clean($_POST['partnershipType']    ?? '');
    $orgLegalName       = clean($_POST['orgLegalName']       ?? '');
    $orgTradingName     = clean($_POST['orgTradingName']     ?? '');
    $orgCountry         = clean($_POST['orgCountry']         ?? '');
    $orgAddress         = clean($_POST['orgAddress']         ?? '');
    $orgWebsite         = clean($_POST['orgWebsite']         ?? '');
    $orgYearEstablished = clean($_POST['orgYearEstablished'] ?? '');
    $orgLegalStatus     = clean($_POST['orgLegalStatus']     ?? '');

    $contactFullName    = clean($_POST['contactFullName']    ?? '');
    $contactPosition    = clean($_POST['contactPosition']    ?? '');
    $contactEmail       = filter_var(trim($_POST['contactEmail'] ?? ''), FILTER_VALIDATE_EMAIL);
    $contactPhone       = clean($_POST['contactPhone']       ?? '');
    $contactSecondary   = clean($_POST['contactSecondary']   ?? '');

    $orgDescription     = clean($_POST['orgDescription']     ?? '');
    $orgKeyPrograms     = clean($_POST['orgKeyPrograms']     ?? '');
    $orgTrainerCount    = clean($_POST['orgTrainerCount']    ?? '');
    $orgTargetAudience  = clean($_POST['orgTargetAudience']  ?? '');
    $orgRegionsServed   = clean($_POST['orgRegionsServed']   ?? '');
    $orgDeliveryFormat  = clean($_POST['orgDeliveryFormat']  ?? '');

    $accredQualsInterest   = clean($_POST['accredQualsInterest']   ?? '');
    $accredDeliveryFormat  = clean($_POST['accredDeliveryFormat']  ?? '');
    $accredLearnersPerYear = clean($_POST['accredLearnersPerYear'] ?? '');

    $capFacilities          = clean($_POST['capFacilities']         ?? '');
    $capClassrooms          = clean($_POST['capClassrooms']         ?? '');
    $capElearningPlatform   = clean($_POST['capElearningPlatform']  ?? '');
    $capQA                  = clean($_POST['capQA']                 ?? '');

    $orgSignatoryName   = clean($_POST['orgSignatoryName']   ?? '');
    $orgSignatoryDate   = clean($_POST['orgSignatoryDate']   ?? '');

    if (!$orgLegalName || !$contactFullName || !$contactEmail || !$ref) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    $uploads = [
        'upload_companyprofile' => handleUpload('upload_companyprofile', $uploadDir, $ref, 5*1024*1024, $docExts),
        'upload_trainercv'      => handleUpload('upload_trainercv',      $uploadDir, $ref, 5*1024*1024, $docExts),
        'upload_bizreg'         => handleUpload('upload_bizreg',         $uploadDir, $ref, 5*1024*1024, $imgExts),
    ];
    $labels = [
        'upload_companyprofile' => 'Company Profile',
        'upload_trainercv'      => 'Lead Trainer CV(s)',
        'upload_bizreg'         => 'Business Registration Certificate',
    ];

    $subject = "CIMS Partnership Accreditation | {$orgLegalName} [{$ref}]";

    $body  = "CHARTERED INSTITUTE OF MANAGEMENT SPECIALISTS (CIMS), USA{$nl}";
    $body .= "PARTNERSHIP ACCREDITATION SUBMISSION{$nl}";
    $body .= $sep;
    $body .= "Reference Number : {$ref}{$nl}";
    $body .= "Submitted On     : {$now}{$nl}";
    $body .= "Partnership Type : " . ($partnershipType ?: 'Not specified') . "{$nl}";
    $body .= $sep . $nl;

    $body .= "[ 1 ] ORGANISATION DETAILS{$nl}" . $sub;
    $body .= "Full Legal Name       : {$orgLegalName}{$nl}";
    $body .= "Trading Name          : " . ($orgTradingName ?: 'N/A') . "{$nl}";
    $body .= "Country               : {$orgCountry}{$nl}";
    $body .= "Address               : {$orgAddress}{$nl}";
    $body .= "Website               : " . ($orgWebsite ?: 'Not provided') . "{$nl}";
    $body .= "Year Established      : " . ($orgYearEstablished ?: 'Not provided') . "{$nl}";
    $body .= "Legal Status          : " . ($orgLegalStatus ?: 'Not provided') . "{$nl}";
    $body .= $nl;

    $body .= "[ 2 ] CONTACT PERSON{$nl}" . $sub;
    $body .= "Full Name             : {$contactFullName}{$nl}";
    $body .= "Position / Title      : {$contactPosition}{$nl}";
    $body .= "Email Address         : {$contactEmail}{$nl}";
    $body .= "Phone Number          : {$contactPhone}{$nl}";
    $body .= "Secondary Contact     : " . ($contactSecondary ?: 'Not provided') . "{$nl}";
    $body .= $nl;

    $body .= "[ 3 ] ORGANISATION OVERVIEW{$nl}" . $sub;
    $body .= "Description           : {$orgDescription}{$nl}";
    $body .= "Key Training Programmes: " . ($orgKeyPrograms ?: 'Not provided') . "{$nl}";
    $body .= "No. of Trainers/Faculty: " . ($orgTrainerCount ?: 'Not provided') . "{$nl}";
    $body .= "Target Audience       : " . ($orgTargetAudience ?: 'Not provided') . "{$nl}";
    $body .= "Regions Served        : " . ($orgRegionsServed ?: 'Not provided') . "{$nl}";
    $body .= "Delivery Format       : " . ($orgDeliveryFormat ?: 'Not provided') . "{$nl}";
    $body .= $nl;

    $body .= "[ 4 ] ACCREDITATION REQUEST{$nl}" . $sub;
    $body .= "CIMS Qualifications of Interest : {$accredQualsInterest}{$nl}";
    $body .= "Proposed Delivery Format         : " . ($accredDeliveryFormat ?: 'Not provided') . "{$nl}";
    $body .= "Estimated Learners per Year      : " . ($accredLearnersPerYear ?: 'Not provided') . "{$nl}";
    $body .= $nl;

    $body .= "[ 5 ] TRAINING CAPACITY{$nl}" . $sub;
    $body .= "Facilities/Platforms Used   : " . ($capFacilities ?: 'Not provided') . "{$nl}";
    $body .= "No. of Classrooms           : " . ($capClassrooms ?: 'Not provided') . "{$nl}";
    $body .= "E-learning Platform(s)      : " . ($capElearningPlatform ?: 'Not provided') . "{$nl}";
    $body .= "Quality Assurance Processes : " . ($capQA ?: 'Not provided') . "{$nl}";
    $body .= $nl;

    $body .= "[ 6 ] UPLOADED DOCUMENTS{$nl}" . $sub;
    foreach ($uploads as $key => $up) {
        $label = $labels[$key] ?? $key;
        $body .= str_pad($label . ' :', 38) . ($up['ok'] ? $up['origName'] . ' (attached)' : 'Not uploaded') . "{$nl}";
    }
    $body .= $nl;

    $body .= "[ 7 ] DECLARATION{$nl}" . $sub;
    $body .= "Signatory Name : " . ($orgSignatoryName ?: 'Not provided') . "{$nl}";
    $body .= "Date           : " . ($orgSignatoryDate ?: 'Not provided') . "{$nl}";
    $body .= $nl . $sep;
    $body .= "Uploaded files saved to: uploads/cims-applications/{$nl}";
    $body .= "Reference: {$ref}{$nl}";
    $body .= $sep;

    $result = sendEmail($phpMailerLoaded, $subject, $body, $contactEmail, $contactFullName, $uploads);

    echo json_encode([
        'success' => $result['sent'],
        'ref'     => $ref,
        'warning' => $result['warning'] ?: null
    ]);
    exit;
}

// =====================================================================
// TRACK: INDIVIDUAL (membership / qualification / upgrade / cpd)
// =====================================================================

$appType        = clean($_POST['appType']        ?? '');
$program        = clean($_POST['programSelect']  ?? '');
$studyMode      = clean($_POST['studyMode']      ?? '');

$title          = clean($_POST['title']          ?? '');
$firstName      = clean($_POST['firstName']      ?? '');
$lastName       = clean($_POST['lastName']       ?? '');
$middleName     = clean($_POST['middleName']     ?? '');
$dob            = clean($_POST['dob']            ?? '');
$gender         = clean($_POST['gender']         ?? '');
$nationality    = clean($_POST['nationality']    ?? '');
$passportNum    = clean($_POST['passportNum']    ?? '');
$country        = clean($_POST['country']        ?? '');
$stateProvince  = clean($_POST['stateProvince']  ?? '');
$email          = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone          = clean($_POST['phone']          ?? '');
$whatsapp       = clean($_POST['whatsapp']       ?? '');
$address        = clean($_POST['address']        ?? '');

$highestQual    = clean($_POST['highestQual']    ?? '');
$fieldStudy     = clean($_POST['fieldOfStudy']   ?? '');
$institution    = clean($_POST['institution1']   ?? '');
$instCountry    = clean($_POST['instCountry1']   ?? '');
$gradYear       = clean($_POST['gradYear']       ?? '');
$grade          = clean($_POST['grade']          ?? '');
$instrLang      = clean($_POST['instrLang']      ?? '');
$qual2          = clean($_POST['qual2']          ?? '');
$body2          = clean($_POST['body2']          ?? '');
$qual3          = clean($_POST['qual3']          ?? '');
$body3          = clean($_POST['body3']          ?? '');
$englishMethod  = clean($_POST['englishMethod']  ?? '');
$englishScore   = clean($_POST['englishScore']   ?? '');
$englishDate    = clean($_POST['englishDate']    ?? '');

$jobTitle       = clean($_POST['jobTitle']       ?? '');
$employer       = clean($_POST['employer']       ?? '');
$industry       = clean($_POST['industry']       ?? '');
$yearsExp       = clean($_POST['yearsExp']       ?? '');
$mgmtExp        = clean($_POST['mgmtExp']        ?? '');
$orgSize        = clean($_POST['orgSize']        ?? '');
$mgmtLevel      = clean($_POST['mgmtLevel']      ?? '');
$prevJobTitle   = clean($_POST['prevJobTitle']   ?? '');
$prevEmployer   = clean($_POST['prevEmployer']   ?? '');
$prevYears      = clean($_POST['prevYears']      ?? '');
$prevResp       = clean($_POST['prevResp']       ?? '');
$cpdActivities  = clean($_POST['cpdActivities']  ?? '');

$motivation     = clean($_POST['motivation']     ?? '');
$goals          = clean($_POST['goals']          ?? '');
$achievement    = clean($_POST['achievement']    ?? '');

$ref1name       = clean($_POST['ref1name']       ?? '');
$ref1email      = clean($_POST['ref1email']      ?? '');
$ref1title      = clean($_POST['ref1title']      ?? '');
$ref1org        = clean($_POST['ref1org']        ?? '');
$ref2name       = clean($_POST['ref2name']       ?? '');
$ref2email      = clean($_POST['ref2email']      ?? '');
$ref2title      = clean($_POST['ref2title']      ?? '');
$ref2org        = clean($_POST['ref2org']        ?? '');

if (!$firstName || !$lastName || !$email || !$program || !$ref) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

$uploads = [
    'upload_id'          => handleUpload('upload_id',          $uploadDir, $ref, 5*1024*1024, $imgExts),
    'upload_transcripts' => handleUpload('upload_transcripts', $uploadDir, $ref, 5*1024*1024, $imgExts),
    'upload_cv'          => handleUpload('upload_cv',          $uploadDir, $ref, 5*1024*1024, $docExts),
    'upload_proofcert'   => handleUpload('upload_proofcert',   $uploadDir, $ref, 5*1024*1024, $imgExts),
    'upload_english'     => handleUpload('upload_english',     $uploadDir, $ref, 5*1024*1024, $imgExts),
    'upload_refs'        => handleUpload('upload_refs',        $uploadDir, $ref, 5*1024*1024, $imgExts),
    'upload_workproof'   => handleUpload('upload_workproof',   $uploadDir, $ref, 5*1024*1024, $imgExts),
];
$labels = [
    'upload_id'          => 'Passport / National ID',
    'upload_transcripts' => 'Academic Transcripts & Certificates',
    'upload_cv'          => 'Curriculum Vitae / Résumé',
    'upload_proofcert'   => 'Professional Certifications',
    'upload_english'     => 'English Proficiency Certificate',
    'upload_refs'        => 'Letters of Reference / Recommendation',
    'upload_workproof'   => 'Proof of Work Experience',
];

$fullName = trim("$title $firstName $middleName $lastName");
$subject  = "CIMS Application | $appType | $firstName $lastName [$ref]";

$body  = "CHARTERED INSTITUTE OF MANAGEMENT SPECIALISTS (CIMS), USA{$nl}";
$body .= "APPLICATION SUBMISSION{$nl}";
$body .= $sep;
$body .= "Reference Number : {$ref}{$nl}";
$body .= "Submitted On     : {$now}{$nl}";
$body .= "Application Type : {$appType}{$nl}";
$body .= $sep . $nl;

$body .= "[ 1 ] PROGRAMME SELECTION{$nl}" . $sub;
$body .= "Programme / Grade    : {$program}{$nl}";
$body .= "Study / Engagement   : " . ($studyMode ?: 'Not specified')  . "{$nl}";
$body .= $nl;

$body .= "[ 2 ] PERSONAL INFORMATION{$nl}" . $sub;
$body .= "Full Name            : {$fullName}{$nl}";
$body .= "Date of Birth        : {$dob}{$nl}";
$body .= "Gender               : {$gender}{$nl}";
$body .= "Nationality          : {$nationality}{$nl}";
$body .= "Passport / ID No.    : {$passportNum}{$nl}";
$body .= "Country of Residence : {$country}{$nl}";
$body .= "State / Province     : " . ($stateProvince ?: 'Not provided') . "{$nl}";
$body .= "Email Address        : {$email}{$nl}";
$body .= "Phone Number         : {$phone}{$nl}";
$body .= "WhatsApp             : " . ($whatsapp ?: 'Same as phone') . "{$nl}";
$body .= "Mailing Address      : {$address}{$nl}";
$body .= $nl;

$body .= "[ 3 ] ACADEMIC BACKGROUND{$nl}" . $sub;
$body .= "Highest Qualification    : {$highestQual}{$nl}";
$body .= "Field of Study           : {$fieldStudy}{$nl}";
$body .= "Institution              : {$institution}{$nl}";
$body .= "Country of Institution   : {$instCountry}{$nl}";
$body .= "Year of Graduation       : {$gradYear}{$nl}";
$body .= "Grade / GPA / Class      : " . ($grade ?: 'Not provided') . "{$nl}";
$body .= "Language of Instruction  : {$instrLang}{$nl}";
$body .= "Additional Qualification : " . ($qual2 ?: 'None') . " — " . ($body2 ?: 'N/A') . "{$nl}";
$body .= "Third Qualification      : " . ($qual3 ?: 'None') . " — " . ($body3 ?: 'N/A') . "{$nl}";
$body .= "English Proficiency      : {$englishMethod}{$nl}";
$body .= "English Test Score       : " . ($englishScore ?: 'N/A') . "{$nl}";
$body .= "English Test Date        : " . ($englishDate  ?: 'N/A') . "{$nl}";
$body .= $nl;

$body .= "[ 4 ] PROFESSIONAL EXPERIENCE{$nl}" . $sub;
$body .= "Current Job Title        : " . ($jobTitle   ?: 'Not provided') . "{$nl}";
$body .= "Current Employer         : " . ($employer   ?: 'Not provided') . "{$nl}";
$body .= "Industry / Sector        : " . ($industry   ?: 'Not provided') . "{$nl}";
$body .= "Total Work Experience    : " . ($yearsExp   ?: 'Not provided') . "{$nl}";
$body .= "Management Experience    : " . ($mgmtExp    ?: 'Not provided') . "{$nl}";
$body .= "Organisation Size        : " . ($orgSize    ?: 'Not provided') . "{$nl}";
$body .= "Management Level         : " . ($mgmtLevel  ?: 'Not provided') . "{$nl}";
$body .= "Previous Job Title       : " . ($prevJobTitle?: 'Not provided') . "{$nl}";
$body .= "Previous Employer        : " . ($prevEmployer?: 'Not provided') . "{$nl}";
$body .= "Years in Previous Role   : " . ($prevYears  ?: 'Not provided') . "{$nl}";
$body .= "Previous Responsibilities: " . ($prevResp   ?: 'Not provided') . "{$nl}";
$body .= "Recent CPD Activities    : " . ($cpdActivities ?: 'Not provided') . "{$nl}";
$body .= $nl;

$body .= "[ 5 ] STATEMENT OF PURPOSE{$nl}" . $sub;
$body .= "MOTIVATION FOR APPLYING:{$nl}";
$body .= ($motivation ?: 'Not provided') . "{$nl}{$nl}";
$body .= "CAREER GOALS:{$nl}";
$body .= ($goals ?: 'Not provided') . "{$nl}{$nl}";
$body .= "SIGNIFICANT ACHIEVEMENT / LEADERSHIP EXPERIENCE:{$nl}";
$body .= ($achievement ?: 'Not provided') . "{$nl}";
$body .= $nl;

$body .= "[ 6 ] PROFESSIONAL REFERENCES{$nl}" . $sub;
$body .= "REFERENCE 1:{$nl}";
$body .= "  Name         : " . ($ref1name  ?: 'Not provided') . "{$nl}";
$body .= "  Email        : " . ($ref1email ?: 'Not provided') . "{$nl}";
$body .= "  Position     : " . ($ref1title ?: 'Not provided') . "{$nl}";
$body .= "  Organisation : " . ($ref1org   ?: 'Not provided') . "{$nl}";
$body .= "REFERENCE 2:{$nl}";
$body .= "  Name         : " . ($ref2name  ?: 'Not provided') . "{$nl}";
$body .= "  Email        : " . ($ref2email ?: 'Not provided') . "{$nl}";
$body .= "  Position     : " . ($ref2title ?: 'Not provided') . "{$nl}";
$body .= "  Organisation : " . ($ref2org   ?: 'Not provided') . "{$nl}";
$body .= $nl;

$body .= "[ 7 ] UPLOADED DOCUMENTS{$nl}" . $sub;
foreach ($uploads as $key => $up) {
    $label = $labels[$key] ?? $key;
    $body .= str_pad($label . ' :', 38) . ($up['ok'] ? $up['origName'] . ' (attached)' : 'Not uploaded') . "{$nl}";
}
$body .= $nl . $sep;
$body .= "Uploaded files saved to: uploads/cims-applications/{$nl}";
$body .= "Applicant email: {$email}{$nl}";
$body .= "Reference: {$ref}{$nl}";
$body .= $sep;

$result = sendEmail($phpMailerLoaded, $subject, $body, $email, $fullName, $uploads);

echo json_encode([
    'success' => $result['sent'],
    'ref'     => $ref,
    'warning' => $result['warning'] ?: null
]);
?>
