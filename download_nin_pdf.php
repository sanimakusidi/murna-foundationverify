<?php
require_once 'config.php';
requireLogin();

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
require_once 'endroid/vendor/autoload.php';

// Only allow these formats
$allowed_formats = ['verification', 'premium', 'card', 'standard'];
$format = $_GET['format'] ?? '';
if (!in_array($format, $allowed_formats)) {
    die('Invalid format.');
}

// Retrieve the last verification data from session (set after successful verification)
// Alternatively, fetch from database using reference_id. We'll use session for simplicity.
if (!isset($_SESSION['last_verification_data'])) {
    die('No verification data found. Please verify again.');
}
$data = $_SESSION['last_verification_data'];

// Use Dompdf
use Dompdf\Dompdf;
use Dompdf\Options;
require_once 'dompdf/vendor/autoload.php';

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$dompdf = new Dompdf($options);
$options->set('isRemoteEnabled', true);     // Allows loading external images
$options->set('chroot', __DIR__);           // Securely sets the root directory for local files
$options->set('defaultMediaType', 'screen');
$dompdf->setPaper('A4', 'portrait'); // Set to 'landscape' if needed.

// Build HTML content based on format
$html = '';
$photo_b64 = $data['image'] ?? ($data['photo'] ?? null);
$photo_html = $photo_b64 ? '<img src="data:image/jpeg;base64,' . $photo_b64 . '" style="width:5000px; height:5000px; object-fit:cover; border-radius:8px;">' : '<div style="width:800px; height:800px; background:#eee; display:flex; align-items:center; justify-content:center;">No Photo</div>';

// Common data
$tracking_id = htmlspecialchars($data['tracking_id'] ?? '');
$nin = htmlspecialchars($data['nin'] ?? '');
$fname = htmlspecialchars($data['fname'] ?? '');
$mname = htmlspecialchars($data['mname'] ?? '');
$lname = htmlspecialchars($data['lname'] ?? '');
$fullname = htmlspecialchars(trim(($data['fname']??'') . ' ' . ($data['mname']??'') . ' ' . ($data['lname']??'')));
$dob = htmlspecialchars($data['dob'] ?? '');
$phone = htmlspecialchars($data['phone'] ?? '');
$gender = htmlspecialchars($data['gender'] ?? '');
$state_origin = htmlspecialchars($data['stateOfOrigin'] ?? '');
$lga_origin = htmlspecialchars($data['lgaOfOrigin'] ?? '');
$residence_address = htmlspecialchars($data['residenceAdress'] ?? $data['residenceAddress'] ?? '');
$residence_state = htmlspecialchars($data['residenceState'] ?? '');
$ref_id = htmlspecialchars($data['reference_id'] ?? '');
$date_generated = date('d-m-Y H:i:s');
$first4 = substr(strval($nin), 0, 4);
$three = substr(strval($nin), 4, 3);
$lastt = substr(strval($nin), 7, 4);
$gnames= $fname . ' ' . $mname;
$addressa = substr(strval($residence_address), 0, 25);





// ── 1. Standard NIN Slip (NIN SLIP with QR code & inverted text) ──
if ($format === 'verification') {
    // Prepare photo (suitable size for slip)
    $photo_html = $photo_b64 
        ? '<img src="data:image/jpeg;base64,' . $photo_b64 . '" style="width:110px; height:130px; object-fit:cover; border:1px solid #aaa;">' 
        : '<div style="width:110px; height:130px; background:#f0f0f0; text-align:center; line-height:130px; border:1px solid #aaa;">No photo</div>';
    
    // Generate QR code from NIN (using free API)
     // --- Generate QR Code Data URI locally ---
    try {
        // Build the QR code
        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($nin) // Encode the NIN number
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(120) // Set size to 120x120 pixels
            ->margin(5)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        // Get the QR code as a base64-encoded data URI
        $qrDataUri = $result->getDataUri();
        // This $qrDataUri can be used directly in an HTML <img> tag.

    } catch (Exception $e) {
        // Handle QR code generation errors gracefully
        error_log("QR Code generation failed: " . $e->getMessage());
        $qrDataUri = ''; // Optionally, set a fallback text or image.
    }

    $qr_html = $qrDataUri
                ? '<img src="' . $qrDataUri . '" style="width:100px; height:100px; border:1px solid #ccc;">'
                : '<div style="width:100px; height:100px; border:1px solid #ccc; text-align:center; line-height:100px;">QR Error</div>';
   
   
   
   $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A4 Centered Square Form - No Gap</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            width: 210mm;
            height: 297mm;
            background: #fff;
           
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* A4 container – defines the printable area */
        .a4-page {
             width: 190mm;       /* 210mm - 2x10mm margins */
            min-height: 277mm;  /* 297mm - 2x10mm margins */
            background: #fff;
            position: relative;
            overflow: hidden;
        }

        /* Top instruction text */
        .instruction {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 20px;
            font-size: 12px;
            color: #333;
        }
        .instruction div {
            margin: 5px 0;
        }

        /* Container that holds both forms, centered as a group */
        .forms-group {
        
           
             position: absolute;
            
            justify-content: center;
            align-items: center;
            gap: 0;            /* no gap between the two forms */
        }

        /* Both square forms share base styles */
      
 
        /* Individual adjustments (e.g., different opacity) */
       
          .logo-on-text {
            position: relative;
            left: 70px;   /* X coordinate from left edge */
            top: 200px;   /* Y coordinate from top edge */
            
        }
        .logo-on-text img {
           max-width: 120%;    /* Limits the width to the container's width */
    max-height: 1000px;  /* Sets a maximum height if needed, preventing overflow */
    width: auto;        /* Automatically adjusts the width to respect the aspect ratio */
    height: auto;
            
}
 .photo-on-text {
            position: relative;
            left: 166px;   /* X coordinate from left edge */
            top: 280px;   /* Y coordinate from top edge */
            
        }
        .photo-on-text img {
            max-width: 105px;    /* Adjust as needed (e.g., 30px, 50px) */
            height: 110px;       /* Maintain aspect ratio */
          border: 1px solid black; 
             border-radius: 5px; /* Optional: rounded corners for the photo */
                   /* Optional: space between photo and border */
            
}
        .barcode-on-text {
            position: relative;
            left: 450px;   /* X coordinate from left edge */
            top: 75px;   /* Y coordinate from top edge */
            
        }
        .barcode-on-text img {
            max-width: 100px;    /* Adjust as needed (e.g., 30px, 50px) */
            height: 100px;       /* Maintain aspect ratio */
          
             border-radius: 5px; /* Optional: rounded corners for the photo */
                   /* Optional: space between photo and border */
            
}
         .sname-on-text {
            position: relative;
            left: 270px;   /* X coordinate from left edge */
            top: 185px;   /* Y coordinate from top edge */
            color: black;
            font-size: 12px;
            font-weight: bold;  
        }
  
         .gnames {
             position: relative;
            left: 270px;   /* X coordinate from left edge */
            top: 200px;   /* Y coordinate from top edge */
            color: black;
            font-size: 12px;
            font-weight: bold; 
        }
           
        
         .dobplht {
             position: relative;
           left: 270px;   /* X coordinate from left edge */
            top: 225px;   /* Y coordinate from top edge */
            color: black;
            font-size: 12px;
            font-weight: bold;  
        }
         
        
         .NIN-github {
             position: relative;
            left: 200px;   /* X coordinate from left edge */
            top: 250px;   /* Y coordinate from top edge */
            color: black;
            font-size: 35px;
            word-spacing: 1.2rem;
           
            font-family: 'sans-serif', sans-srif, monospace; /* Monospaced font for better readability */ 
        }
        
        
         .NIN-number {
              position: relative;
           left: 480px;   /* X coordinate from left edge */
            top: 435px;   /* Y coordinate from top edge */
            color: rgb(206, 203, 203);
            font-size: 10px;
        
             transform: rotate(180deg);  
        }
        /* Form styling – all sharp edges */
       

        /* Print styles */
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            .a4-page {
                box-shadow: none;
                width: 100%;
                height: auto;
                margin: 0;
                padding: 0;
            }
       
        }
 


        /* Responsive */
        @media (max-width: 600px) {
            .square-form, .square-form2 {
                width: 70%;
                padding: 1.2rem;
            }
            h2 {
                font-size: 1.1rem;
            }
            label, input, button {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="a4-page">
        <!-- Top instruction text -->
        <div class="instruction">
            <div>Please find below your Improved NIN Slip</div>
            <div>You may cut it out of the paper, fold and laminate as desired.</div>
            <div>For your security & privacy, please DO NOT permit others to make photocopies of this slip.</div>
        </div>

        <!-- Both forms grouped together, centered vertically with no gap -->
        
        <div class="forms-group">
             
                    <div class="logo-on-text"><img src="newslipn.jpg" alt="Logo" /></div>
                       
            
          

    
        </div>
       
         <div class="photo-on-text">$photo_html</div>
       
                    
                    <div class="sname-on-text">$lname</div>
                    
                    <div class="gnames">$gnames</div>
                    
                    <div class="dobplht">$dob</div>
                    
                    <div class="NIN-github">$first4 $three $lastt</div>
                    <div class="NIN-number">$nin</div>
                     <div class="barcode-on-text"><img src="barcode.jpg" alt="Barcode" /></div>
    </div>
</body>
</html>
HTML;
}

// ── 1. VERIFICATION PAGE (detailed report) ──────────────────────────
elseif ($format === 'premium') {
    // Ensure all required variables are extracted from $data
    $nin            = htmlspecialchars($data['nin'] ?? '');
    $tracking_id    = htmlspecialchars($data['tracking_id'] ?? $data['tracking_id'] ?? '');
   
    $fname          = htmlspecialchars($data['fname'] ?? $data['firstname'] ?? '');
    $mname          = htmlspecialchars($data['mname'] ?? $data['middlename'] ?? '');
    $lname          = htmlspecialchars($data['lname'] ?? $data['lastname'] ?? '');
    $maiden_name    = htmlspecialchars($data['maidenName'] ?? $data['maiden_name'] ?? 'not found');
    $phone          = htmlspecialchars($data['phone'] ?? '');
    $dob            = htmlspecialchars($data['dob'] ?? '');
    $gender         = htmlspecialchars($data['gender'] ?? '');
    $residence      = htmlspecialchars(
        trim(($data['residenceAdress'] ?? $data['residenceAddress'] ?? '') . ', ' .
             ($data['residenceTown'] ?? '') . ', ' .
             ($data['residenceLga'] ?? '') . ', ' .
             ($data['residenceState'] ?? ''), ', ')
    );
    $date_generated = date('d-m-Y H:i:s');

    // Photo (base64)
    $photo_b64 = $data['image'] ?? $data['photo'] ?? null;
    $photo_html = '';
    if ($photo_b64 && strlen(trim($photo_b64)) > 0) {
        // Remove possible whitespace and data URI prefix
        $clean = preg_replace('/\s+/', '', $photo_b64);
        if (strpos($clean, 'data:image') !== 0) {
            $photo_html = '<img src="data:image/jpeg;base64,' . $clean . '" style="width: 220px; height: 260px; object-fit: cover; border: 1px solid #aaa;">';
        } else {
            $photo_html = '<img src="' . $clean . '" style="width: 220px; height: 260px; object-fit: cover; border: 1px solid #aaa;">';
        }
    } else {
        $photo_html = '<div style="width:220px; height:260px; background:#f0f0f0; text-align:center; line-height:260px; border:1px solid #aaa;">No photo</div>';
    }
// Locate the logo file – adjust the path to match your setup
 $logo_path = __DIR__ . '/niglogoo.jpg';

$logo_img = '';
if (file_exists($logo_path)) {
    $logo_data = base64_encode(file_get_contents($logo_path));
    $logo_img = '<img src="data:image/jpeg;base64,' . $logo_data . '" class="logo" alt="Coat of Arms">';
} else {
    // Fallback text if logo not found (for debugging)
    $logo_img = '<div style="color:red;">[Logo missing]</div>';
    error_log("Logo not found at: " . $logo_path);
}
    // Build HTML/CSS exactly matching the uploaded PDF
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Premium NIN Slip</title>
   <style>
    body {
        font-family: "Helvetica", "Arial", sans-serif;
       
        background: white;
    }
    .container {
        max-width: 800px;
        margin: 0 auto;
      
       
        overflow: auto;
    }
    .header {
        text-align: center;
        margin-bottom: 20px;
    }
    .logo {
        max-width: 80px;
        height: auto;
        margin-bottom: 5px;
    }
    .title {
        font-size: 16px;
        font-weight: bold;
        margin: 2px 0;
    }
    .subtitle {
        font-size: 14px;
        font-weight: bold;
    }
    .photo-section {
        float: left;
        width: 33%;
       padding-top: 20px;
        text-align: center;
    }
    .photo-section img,
    .photo-section div {
     
        object-fit: cover;
    
        margin: 0 auto;
    }
    .photo-label {
        font-size: 10px;
        margin-top: 4px;
    }
    .info-table {
        float: left;
        width: 66%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    .info-table th, .info-table td {
        border: 1px solid #000;
        padding: 6px 8px;
        vertical-align: top;
    }
    .info-table th {
        background: #e0e0e0;
        text-align: center;
        font-weight: bold;
    }
    .section-title {
        background: #e0e0e0;
        text-align: center;
        font-weight: bold;
    }
    .note {
        clear: both;
        margin-top: 20px;
        font-size: 10px;
        text-align: center;
       
        padding-top: 20px;
    }
    .cell-text {
      width: 90%;
      background: #ffffff;
      padding: 2rem 1rem 2rem 8.2rem;
      vertical-align: middle;
      text-align: left;
    }

    /* SECOND CELL: occupies exactly 20% of the row width */
    .cell-logo {
      width: 10%;
      background: #ffffff;
      padding: 1.2rem 1rem;
      vertical-align: middle;
      text-align: right;
     
    }
</style>
</head>
<body>


<div class="container">
   
    <div class="header">
       <table>
        <tr>
             <td class="cell-text" style=" text-align:center;">
           
                <div class="title">National Identity Management System </div>
                <div class="title">Federal Republic of Nigeria</div>
                <div class="subtitle">National Identification Number (NIN) – Verification Page</div>
            </td>
             <td class="cell-logo">
                $logo_img
            </td>
        </tr>
    </table>
        </div>

    <div class="clearfix">
        <div class="photo-section">
            $photo_html
           
        </div>

        <table class="info-table">
            <tr>
                <th colspan="2">Personal Information</th>
            </tr>
            <tr>
                <td style="width: 40%;"><strong>National Identification Number (NIN)</strong></td>
                <td>$nin</td>
            </tr>
            <tr>
                <td><strong>Tracking ID</strong></td>
                <td>$tracking_id</td>
            </tr>
            <tr>
                <td><strong>First Name</strong></td>
                <td>$fname</td>
            </tr>
            <tr>
                <td><strong>Middle Name</strong></td>
                <td>$mname</td>
            </tr>
            <tr>
                <td><strong>Last Name</strong></td>
                <td>$lname</td>
            </tr>
            <tr>
                <td><strong>Maiden Name</strong></td>
                <td>$maiden_name</td>
            </tr>
            <tr>
                <td><strong>Phone Number</strong></td>
                <td>$phone</td>
            </tr>
            <tr>
                <td><strong>Date of Birth</strong></td>
                <td>$dob</td>
            </tr>
            <tr>
                <td><strong>Gender</strong></td>
                <td>$gender</td>
            </tr>
            <tr>
                <td><strong>Residence</strong></td>
                <td>$residence</td>
            </tr>
        </table>
    </div>

    <div class="note">
        NOTE: The National Identification Number (NIN) is your identity. It is confidential and may only be released for legitimate transactions.<br>
        Generated on $date_generated
    </div>
</div>
</body>
</html>
HTML;
}




// ── 3. CARD SLIP (wallet‑sized card, similar to PVC card layout) ──
elseif ($format === 'card') {
     $tracking_id="SX3NMOYSWE00";
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A4 Centered Square Form - No Gap</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            width: 210mm;
            height: 297mm;
            background: #fff;
           
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* A4 container – defines the printable area */
        .a4-page {
            margin: 0;
            padding: 0;
             width: 190mm;       /* 210mm - 2x10mm margins */
            min-height: 277mm;  /* 297mm - 2x10mm margins */
            background: #fff;
            position: relative;
            overflow: hidden;
        }
   .forms-group {
        
           
             position: absolute;
            
          
            gap: 0;            /* no gap between the two forms */
        }
       

        /* Both square forms share base styles */
      
 
        /* Individual adjustments (e.g., different opacity) */
       
          .logo-on-text {
            margin: 0;
            padding: 0;
            position: relative;
            left: 2px;   /* X coordinate from left edge */
            top: 0px;   /* Y coordinate from top edge */
            
        }
        .logo-on-text img {
           max-width: 80%;    /* Limits the width to the container's width */
    max-width: 733px;  /* Sets a maximum height if needed, preventing overflow */
          /* Automatically adjusts the width to respect the aspect ratio */
    height: auto;
            
}
 .photo-on-text {
            position: relative;
            left: 610px;   /* X coordinate from left edge */
            top: -413px;   /* Y coordinate from top edge */
            
        }
       
             .photo-on-text img {
            max-width: 105px;    /* Adjust as needed (e.g., 30px, 50px) */
             max-height: 140px;       /* Maintain aspect ratio */
         
             border-radius: 5px; /* Optional: rounded corners for the photo */
                   /* Optional: space between photo and border */
        
        }
      .trackingid {
            position: relative;
            left: 90px;   /* X coordinate from left edge */
            top: -282px;   /* Y coordinate from top edge */
            color: #494747; 
            font-size: 12px;
            font-weight: bold;  
        }
         .sname-on-text {
            position: relative;
            left: 275px;   /* X coordinate from left edge */
            top: -295px;   /* Y coordinate from top edge */
            color: black;
            font-size: 12px;
            font-weight: bold;  
             color: #494747; 
        }
  
         .mname {
             position: relative;
            left: 275px;   /* X coordinate from left edge */
            top: -252px;   /* Y coordinate from top edge */
            color: black;
            font-size: 12px;
            font-weight: bold; 
             color: #494747; 
        }
          .fname {
             position: relative;
            left: 275px;   /* X coordinate from left edge */
            top: -272px;   /* Y coordinate from top edge */
            color: black;
            font-size: 12px;
            font-weight: bold; 
              color: #494747; 
        }
           
        
         .gender {
             position: relative;
            left: 275px;   /* X coordinate from left edge */
            top: -237px;   /* Y coordinate from top edge */
            color: black;
            font-size: 12px;
            font-weight: bold; 
          color: #494747; 
        }
         
        
         .NIN-github {
             position: relative;
            left: 0px;   /* X coordinate from left edge */
            top: 0px;   /* Y coordinate from top edge */
            color: black;
            font-size: 35px;
            word-spacing: 1.2rem;
           
            font-family: 'sans-serif', sans-srif, monospace; /* Monospaced font for better readability */ 
        }
        
        
         .NIN-number {
              position: relative;
            left: 70px;   /* X coordinate from left edge */
            top: -317px;   /* Y coordinate from top edge */
            color: black;
            font-size: 12px;
            font-weight: bold; 
              color: #494747; 
        }
            .residence-address {
                position: relative;
                left: 420px;   /* X coordinate from left edge */
                top: -360px;   /* Y coordinate from top edge */
                color: black;
                font-size: 12px;
                font-weight: bold; 
                color: #494747; 
            }
            
             .residence-state {
                position: relative;
                left: 420px;   /* X coordinate from left edge */
                top: 200px;   /* Y coordinate from top edge */
                color: black;
                font-size: 12px;
                font-weight: bold; 
                color: #494747; 
            }
        /* Form styling – all sharp edges */
       

        /* Print styles */
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            .a4-page {
                box-shadow: none;
                width: 100%;
                height: auto;
                margin: 0;
                padding: 0;
            }
       
        }
 

    </style>
</head>
    <body>
  
        <div class="a4-page">
           
            <!-- Both forms grouped together, centered vertically with no gap -->
        
             <div class="forms-group">  
                    <div class="logo-on-text">
                    
                    <img src="standardslip.jpg" alt="Logo" />
                   
            </div>
                <div class="trackingid">$tracking_id</div>
                 <div class="sname-on-text">$lname</div>
                    
                    <div class="fname">$fname</div>
                     <div class="mname">$mname</div>
                    
                    <div class="gender">$gender</div>
                   
                    <div class="NIN-number">$nin</div>
           
                 <div class="residence-address">$addressa</div>
             <div class="residence-address">$residence_state</div>
                    
                       <div class="photo-on-text">$photo_html</div>
                   
       
                    
                    
                    
          

  
            </div>
         
                  
        </div>
    </body>
</html>
HTML;
}





// ── 4. STANDARD SLIP (simple text layout, like your regular_nin_slip) ──
elseif ($format === 'standard') {
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Standard NIN Slip</title>
    <style>
        body { font-family: 'Courier New', monospace; padding: 20px; }
        .slip { max-width: 400px; margin: auto; border: 2px solid #000; padding: 15px; }
        .line { border-top: 1px dashed #000; margin: 10px 0; }
        .field { margin: 5px 0; }
        .label { font-weight: bold; display: inline-block; width: 130px; }
        </style>
    </div>
HTML;
    // For standard slip, we simulate the plain text style
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Standard NIN Slip</title></head>
<body>
<div class="slip">
    <pre>
NIN: $nin
Name: $fullname
DOB: $dob
Phone: $phone
Gender: $gender
State of Origin: $state_origin
LGA of Origin: $lga_origin
Residence: $residence_address, $residence_state
Reference: $ref_id
Generated: $date_generated
    </pre>
    <div class="line"></div>
    <small>This is an electronically generated NIN slip. Treat as confidential.</small>
</div>
</body>
</html>
HTML;
}

// Load HTML, generate PDF, output
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("nin_{$format}_{$nin}.pdf", array("Attachment" => 1));
exit;