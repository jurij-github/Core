<?php
    include_once "./include/admin/PhorumInputForm.php";

    // The place where our sanity checking modules are.
    $sanity_checks_dir = "./include/admin/sanity_checks";

    // ==============================================================================
    // Load in the available sanity checks.
    // ==============================================================================

    $sanity_checks = array();
    $dh = opendir ($sanity_checks_dir);
    if (! $dh) die("Could not open sanity checks directory");
    while ($file = readdir($dh)) {
        if (preg_match('/^(.+)\.php$/', $file, $m)) {
            unset($phorum_check);
            include("$sanity_checks_dir/$file");
            $func = "phorum_check_$m[1]";
            if (! isset($phorum_check) || ! function_exists($func)) {
                die("$sanity_checks_dir/$file is no valid check file! " .
                    "Either \$phorum_check is not set or the " .
                    "function " . htmlspecialchars($func) . " does not exist");
                continue;
            }

            $sanity_checks[] = array (
                'function'    => $func,
                'description' => $phorum_check,
            );
        }
    }

    // ==============================================================================
    // Build the sanity checking page and run all checks.
    // ==============================================================================

    // Mapping of status to display representation.
    $status2display = array(
    //  STATUS                       BACKGROUND    FONT     TEXT
        PHORUM_SANITY_OK    => array('green',      'white', 'ALL IS OK'),
        PHORUM_SANITY_WARN  => array('darkorange', 'white', 'WARNING'),
        PHORUM_SANITY_CRIT  => array('red',        'white', 'ERROR'),
    );

    $frm = new PhorumInputForm ("", "post", "Restart sanity checks");
    $frm->hidden("module", "sanity_checks");
    $frm->addbreak("Phorum System Sanity Checks");

    // Make using $php_errormsg possible for the checks.
    ini_set('track_errors', 1);

    // In the case checks take a little while, we want the user
    // to have visible feedback.
    ob_flush();
    
    // Run the sanity checks.
    foreach ($sanity_checks as $check)
    {
        // Call the sanity check function. This function is expected
        // to return an array containing an array with two elements:
        //
        // [1] A status, which can be one of
        //     PHORUM_SANITY_OK     No problem found
        //     PHORUM_SANITY_WARN   Problem found, but no fatal one
        //     PHORUM_SANITY_CRIT   Critical problem found
        //
        // [2] A description of the problem that was found.
        //
        list($status, $error) = call_user_func($check["function"]);

        $display = $status2display[$status];
        $block = "<div style=\"color:{$display[1]};background-color:{$display[0]};" .
                 "text-align:center;border:1px solid black;\">{$display[2]}</div>";
        $row = $frm->addrow($check['description'], $block);
        if (! empty($error)) {
            $frm->addhelp($row,"Sanity check failed",$error);
        }
    }

    $frm->show();

?>
