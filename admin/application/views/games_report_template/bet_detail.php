<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bet Details</title>
    <link rel="stylesheet" type="text/css" href="<?php echo $this->utils->cssUrl('bootstrap.min.css'); ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo $this->utils->cssUrl('datatables.min.css'); ?>">

    <style>
        body {
            margin: 0;
            padding: 0;
            width: 90%;
            margin: 0 auto;
        }
        header {
            width: 100%;
            padding: 5px;
        }
        .wrapper {
            width: 100%;
            height: 100%;
            overflow-x: hidden;
        } 
        .bold {
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <header class="bg-info w-100">
        <h2>Bet Detail</h2>
    </header>
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="dataTable" style="width:100%">
            <tbody>
            <?php
            // Function to recursively print key-value pairs
            function print_recursive($data) {
                if (is_array($data) || is_object($data) || is_json($data) || !empty($data)) {
                    if (is_string($data)) {
                        $data = json_decode($data);
                    }
                    foreach ($data as $key => $value) {
                        $key = is_string($key) ? lang('bet_detail.'.$key) : $key;              
                     
                        // $key = convertStringToReadable($key);  
                        
                        echo "<tr class='ml-2'>";
                        echo "<td class='bold'>$key</td>";
                        echo "<td>";

                        if (is_array($value) || is_object($value)) {

                            echo "<table class='table table-bordered table-striped'>";
                            print_recursive($value);
                            echo "</table>";
                        } else {

                            if (is_json($value)) {
                                echo "<table class='table table-bordered table-striped'>";
                                print_recursive($value);
                                echo "</table>";
                            } else {
                                echo
                                "<div class=''>" .
                                $value .
                                "</div>";
                            }
                        }

                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='2'>Invalid data</td></tr>";
                }
            }

            function convertStringToReadable($string){
                if (strpos($string, '_') !== false) {                         
                    if(is_string($string)){
                        $string = str_replace('_', ' ', $string);
                        // Capitalize the first letter of each word
                        $string = ucwords($string);
                    }
                }  
               
                return $string;
            }


            if (isset($data) && !empty($data)) {
                print_recursive($data);
            } else {
                echo "<tr><td colspan='2'>No data available</td></tr>";
            }

            // Function to check if a string appears to be JSON
            function is_json($string) {
                json_decode($string);
                return (json_last_error() == JSON_ERROR_NONE) && preg_match('/^[\[|\{]/', $string);
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<script type="text/javascript" src="<?php echo $this->utils->jsUrl('jquery-2.1.4.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $this->utils->jsUrl('bootstrap.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $this->utils->jsUrl('datatables.min.js'); ?>"></script>
</body>
</html>
