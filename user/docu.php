<?php
include_once 'head.php'; // Assuming documents are already fetched here

if(isset($_POST['document'])) {

    // Start building the query to update only the fields with uploaded files
    $updateFields = array();

    // PAN Card
    $allowed = array("jpeg", "JPEG", "JPG", "jpg", "png", "PNG", "PDF", "pdf");
    if (!empty($_FILES["conpanydocument"]["name"])) {
        $ft = explode('.', $_FILES['conpanydocument']['name']);
        $ft = end($ft);
        $file_type = strtolower($ft);
        if (in_array($file_type, $allowed)) {
            $conpanydocument = $user . 'conpany' . date('YmdHis') . '.' . $ft;
            move_uploaded_file($_FILES["conpanydocument"]["tmp_name"], 'uploads/' . $conpanydocument);
            $updateFields[] = "`conpanydocument`='$conpanydocument'";
        }
    } else {
        if (empty($user_conpanydocument)) { // Using the $user_ prefixed variable to check existing doc
            $updateFields[] = "`conpanydocument`='no'";
        }
    }

    // Aadhar Card (Personal Document)
    if (!empty($_FILES['personaldocument']['name'][0]) || !empty($_FILES['personaldocument']['name'][1])) {
        $personaldocument = array();
        for ($i = 0; $i < 2; $i++) {
            if (!empty($_FILES['personaldocument']['name'][$i])) {
                $ft = explode('.', $_FILES['personaldocument']['name'][$i]);
                $ft = end($ft);
                $file_type = strtolower($ft);
                if (in_array($file_type, $allowed)) {
                    $newFileName = $user . 'personal' . date('YmdHis') . $i . '.' . $ft;
                    move_uploaded_file($_FILES['personaldocument']['tmp_name'][$i], 'uploads/' . $newFileName);
                    $personaldocument[$i] = $newFileName;
                } else {
                    $personaldocument[$i] = "no";
                }
            }
        }
        $personaldocument = implode('#', $personaldocument);
        $updateFields[] = "`personaldocument`='$personaldocument'";
    } else {
        if (empty($user_personaldocument)) { // Check existing Aadhar
            $updateFields[] = "`personaldocument`='no'";
        }
    }

    // Bank Document
    if (!empty($_FILES['bankdocument']['name'])) {
        $ft = explode('.', $_FILES['bankdocument']['name']);
        $ft = end($ft);
        $file_type = strtolower($ft);
        if (in_array($file_type, $allowed)) {
            $bankdocument = $user . 'bank' . date('YmdHis') . '.' . $ft;
            move_uploaded_file($_FILES['bankdocument']['tmp_name'], 'uploads/' . $bankdocument);
            $updateFields[] = "`bankdocument`='$bankdocument'";
        }
    } else {
        if (empty($user_bankdocument) or $user_bankdocument == 'no') { // Check existing Bank doc
            $updateFields[] = "`bankdocument`=''";
        }
    }

    // Address Document
    if (!empty($_FILES['addressdocument']['name'])) {
        $ft = explode('.', $_FILES['addressdocument']['name']);
        $ft = end($ft);
        $file_type = strtolower($ft);
        if (in_array($file_type, $allowed)) {
            $addressdocument = $user . 'address' . date('YmdHis') . '.' . $ft;
            move_uploaded_file($_FILES['addressdocument']['tmp_name'], 'uploads/' . $addressdocument);
            $updateFields[] = "`addressdocument`='$addressdocument'";
        }
    } else {
        if (empty($user_addressdocument)) { // Check existing Address doc
            $updateFields[] = "`addressdocument`='no'";
        }
    }

    // Company ID Card
    if (!empty($_FILES['companyidcard']['name'])) {
        $ft = explode('.', $_FILES['companyidcard']['name']);
        $ft = end($ft);
        $file_type = strtolower($ft);
        if (in_array($file_type, $allowed)) {
            $companyidcard = $user . 'companyid' . date('YmdHis') . '.' . $ft;
            move_uploaded_file($_FILES['companyidcard']['tmp_name'], 'uploads/' . $companyidcard);
            $updateFields[] = "`companyidcard`='$companyidcard'";
        }
    } else {
        if (empty($user_companyidcard)) { // Check existing ID card
            $updateFields[] = "`companyidcard`='no'";
        }
    }

    // Salary Document
    if (!empty($_FILES['salarydocument']['name'])) {
        $ft = explode('.', $_FILES['salarydocument']['name']);
        $ft = end($ft);
        $file_type = strtolower($ft);
        if (in_array($file_type, $allowed)) {
            $salarydocument = $user . 'salary' . date('YmdHis') . '.' . $ft;
            move_uploaded_file($_FILES['salarydocument']['tmp_name'], 'uploads/' . $salarydocument);
            $updateFields[] = "`salarydocument`='$salarydocument'";
        }
    } else {
        if (empty($user_salarydocument)  or $user_bankdocument == 'no') { // Check existing Salary doc
            $updateFields[] = "`salarydocument`=''";
        }
    }

    // Document Passwords
    $document_password = array();
    $document_password[] = !empty($pan_pass) ? "pan" . $pan_pass . "pan" : "pan no password pan";
    $document_password[] = !empty($aadhar_pass) ? "aadhar" . $aadhar_pass . "aadhar" : "aadhar no password aadhar";
    $document_password[] = !empty($aadhar_pass2) ? "aadha2" . $aadhar_pass2 . "aadha2" : "aadha2 no password aadha2";
    $document_password[] = !empty($salary_pass) ? "salary" . $salary_pass . "salary" : "salary no password salary";
    $document_password[] = !empty($bank_pass) ? "bank" . $bank_pass . "bank" : "bank no password bank";
    $document_password[] = !empty($address_pass) ? "address" . $address_pass . "address" : "address no password address";
    $document_password[] = !empty($bank_pass2) ? "bank2" . $bank_pass2 . "bank2" : "bank2 no password bank2";
    $document_password[] = !empty($bank_pass3) ? "bank3" . $bank_pass3 . "bank3" : "bank3 no password bank3";
    $document_password[] = !empty($comidcard_pass) ? "comidcard" . $comidcard_pass . "comidcard" : "comidcard no password comidcard";
    $document_password = implode('#', $document_password);
    
    // Add document_password to the fields to update
    $updateFields[] = "`document_password`='$document_password'";

    // Execute the update query only if we have fields to update
    if (!empty($updateFields)) {
        $query = "UPDATE `user` SET " . implode(', ', $updateFields) . " WHERE mobile='$user'";
        if (towquery($query)) {
            echo "<script>alert('Your data is successfully updated'); window.location.replace('index.php');</script>";
        }
    }
}
?>
