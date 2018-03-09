<?php
/**
* @link http://gist.github.com/385876
*/
  require 'vendor/autoload.php';

	global $servername;
	global $username;
	global $password;
	global $dbname;

  error_reporting(E_ALL);

	if(isset($_POST['submit'])) {

    $uploaddir = 'uploads/';

    if (!file_exists('uploads/')) {
      mkdir('uploads/', 0777, true);
    }
    
    //FilePath with File name.
    $uploadfile = $uploaddir . basename($_FILES["filename"]["name"]);

      //Check if uploaded file is CSV and not any other format.
      if (($_FILES["filename"]["type"] == "text/csv")){

        //Move uploaded file to our Uploads folder.
        if (move_uploaded_file($_FILES["filename"]["tmp_name"], $uploadfile)) {

          echo "File Uploaded successfully";

          //Import uploaded file to Database

          // Create a Mongo conenction
          $mongo = new MongoDB\Client("mongodb://localhost:27017");
          $manager = new MongoDB\Driver\Manager('mongodb://localhost:27017');
          
          $bulk = new MongoDB\Driver\BulkWrite;

          // Choose the database and collection
          $db = $mongo->test;
          $coll_work = $db->test_booking_local;

          $headerArray = array("Reservation ID","Reservation Code","Group ID","Channel ID","Guest name","Guest Email","Room Name","Adults","Children","Infants","Total","Paid","Balance","Country","Arrival Date","Departure Date","Status","Created", "unitCode", "apartmentID", "interval");

          $index = 0;
          $headIdx = 0;
          $headerCount = count($headerArray);

          if (($handle = fopen($uploadfile, "r")) !== FALSE) {

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

              if($index!=0){

                $rowCount = count($data);

                // var_dump($rowCount);

                $valArray = array();
                // we add two extra data fields - unitCode, apartmentID, interval
                if ($headerCount == ($rowCount + 3)) {

                  for ($headIdx=0; $headIdx < $headerCount; $headIdx++) {

                    if ($headIdx < $rowCount) {

                      if ($headIdx == 7 || $headIdx == 8 || $headIdx == 9) {

                        $valArray[$headerArray[$headIdx]] = intval($data[$headIdx]);

                      } else if ($headIdx == 10 || $headIdx == 11 || $headIdx == 12) {

                        $valArray[$headerArray[$headIdx]] = floatval($data[$headIdx]);

                      } else if ($headIdx == 14 || $headIdx == 15) {

                        $valArray[$headerArray[$headIdx]] = $data[$headIdx];

                      }
                      else {

                        $valArray[$headerArray[$headIdx]] = $data[$headIdx];
                      }

                    } else {

                      if ($headIdx == $rowCount ) {

                        // add unitCode
                        // $unitCode = mt_rand(10000, 99999);
                        $unitCode = substr($data[0], 0, 5);
                        $valArray[$headerArray[$headIdx]] = $unitCode;

                      } elseif ($headIdx == ($rowCount + 1)) {

                        $apartmentID = getApartmentID($data[6]);
                        $valArray[$headerArray[$headIdx]] = $apartmentID;

                        // var_dump($valArray);

                      } elseif ($headIdx == ($rowCount + 2)) {

                        $date_arrived = new DateTime($data[14]);
                        $date_depature = new DateTime($data[15]);

                        $interval = $date_arrived->diff($date_depature);

                        $valArray[$headerArray[$headIdx]] = intval($interval->format("%a"));

                      }

                    }
                }

                // var_dump($result);
                // $coll_work->insertOne($valArray);

                $bulk->insert($valArray);
                

                }
              }

              $index++;
            }

            $result = $manager->executeBulkWrite('check.collection', $bulk);

          fclose($handle);
        }

        $message = "Importing completed";
        echo "<script type='text/javascript'>alert('$message');</script>";

      }
    }
    else
    {
      //echo incase user uploads a non-csv file.
      echo "Upload a CSV file Only.";
    }
	}

  function getApartmentID ($text) {

  	$Address = array(
    array('unitId' => '1','Room_Name' => 'Kornstr 4,  3. OG Links, 4 Zimmer','addrStreet' => 'Kornstr','addrNumber' => '4','addrCity' => 'Fuerth','addrZip' => '90763','unitNumber' => '7','accessDate' => '2018-02-27 14:56:12','live_update' => ''),
    array('unitId' => '2','Room_Name' => 'Heiligenstr 12, 1. OG, Studio Nr 10','addrStreet' => 'Heiligenstr','addrNumber' => '12','addrCity' => 'Fuerth','addrZip' => '90762','unitNumber' => '10','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '3','Room_Name' => 'N?rnberger 43, 2. OG rechts, 2 Schlafzimmer','addrStreet' => 'N?rnberger','addrNumber' => '43','addrCity' => 'Fuerth','addrZip' => '90762','unitNumber' => '7','accessDate' => '2018-02-27 14:56:38','live_update' => ''),
    array('unitId' => '4','Room_Name' => 'Kornstr 4, DG Rechts','addrStreet' => 'Kornstr','addrNumber' => '4','addrCity' => 'Fuerth','addrZip' => '90763','unitNumber' => '8','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '5','Room_Name' => 'The Hat Shop Nürnberger 43, Ground Floor','addrStreet' => 'N?rnberger','addrNumber' => '43','addrCity' => 'Fuerth','addrZip' => '90762','unitNumber' => '3','accessDate' => '2018-02-27 14:56:26','live_update' => ''),
    array('unitId' => '6','Room_Name' => 'Heiligenstr 12, 2. OG, Zimmer 8','addrStreet' => 'Heiligenstr','addrNumber' => '12','addrCity' => 'Fuerth','addrZip' => '90762','unitNumber' => '8','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '7','Room_Name' => 'Heiligenstr 12, DG Links, Nr. 1','addrStreet' => 'Heiligenstr','addrNumber' => '12','addrCity' => 'Fuerth','addrZip' => '90762','unitNumber' => '1','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '8','Room_Name' => 'Heiligenstr 12, DG Rechts, Nr. 2','addrStreet' => 'Heiligenstr','addrNumber' => '12','addrCity' => 'Fuerth','addrZip' => '90762','unitNumber' => '2','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '9','Room_Name' => 'Heiligenstrasse 12, 2. OG Apartment 6','addrStreet' => 'Heiligenstr','addrNumber' => '12','addrCity' => 'Fuerth','addrZip' => '90762','unitNumber' => '6','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '10','Room_Name' => 'Kormstr 18, 2. OG Rechts, 2 Zimmer','addrStreet' => 'Kornstr','addrNumber' => '18','addrCity' => 'Fuerth','addrZip' => '90763','unitNumber' => '5','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '11','Room_Name' => 'Kornstr 18, 1.OG Rechts, 2 Schlafzimmer','addrStreet' => 'Kornstr','addrNumber' => '18','addrCity' => 'Fuerth','addrZip' => '90763','unitNumber' => '3','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '12','Room_Name' => 'Kornstr 18, 3.OG Rechts, 4 Zimmer','addrStreet' => 'Kornstr','addrNumber' => '18','addrCity' => 'Fuerth','addrZip' => '90763','unitNumber' => '7','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '13','Room_Name' => 'Kornstr 18, EG Links, 2 Schlafzimmer','addrStreet' => 'Kornstr','addrNumber' => '18','addrCity' => 'Fuerth','addrZip' => '90763','unitNumber' => '1','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '14','Room_Name' => 'Heiligenstr 12, 2. OG, Zimmer 3','addrStreet' => 'Heiligenstr','addrNumber' => '12','addrCity' => 'Fuerth','addrZip' => '90762','unitNumber' => '3','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '15','Room_Name' => 'Kornstr 4,  EG Links, 2 Schlafzimmer','addrStreet' => 'Kornstr','addrNumber' => '4','addrCity' => 'Fuerth','addrZip' => '90763','unitNumber' => '1','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '16','Room_Name' => 'Heiligenstr 12, 1.OG, Zimmer 12','addrStreet' => 'Heiligenstr','addrNumber' => '12','addrCity' => 'Fuerth','addrZip' => '90762','unitNumber' => '12','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '17','Room_Name' => 'Kornstr 4, Hinterhaus','addrStreet' => 'Kornstr','addrNumber' => '4','addrCity' => 'Fuerth','addrZip' => '90763','unitNumber' => '9','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '18','Room_Name' => 'Kornstr. 4,  1 Bedroom Ground Floor Apt. #2','addrStreet' => 'Kornstr','addrNumber' => '4','addrCity' => 'Fuerth','addrZip' => '90763','unitNumber' => '2','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '19','Room_Name' => 'N?rnberger 43, 2. OG Links, 3 Schlafzimmer','addrStreet' => 'N?rnberger','addrNumber' => '43','addrCity' => 'Fuerth','addrZip' => '90762','unitNumber' => '6','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '20','Room_Name' => 'Heiligenstr 12, 1.OG, Zimmer 11','addrStreet' => 'Heiligenstr','addrNumber' => '12','addrCity' => 'Fuerth','addrZip' => '90762','unitNumber' => '11','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '21','Room_Name' => 'N?rnberger 43, Ground Floor Left','addrStreet' => 'N?rnberger','addrNumber' => '43','addrCity' => 'Fuerth','addrZip' => '90762','unitNumber' => '1','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '22','Room_Name' => 'Schwabacherstr 65 - Apt 6 -  Room 1','addrStreet' => 'Schwabacherstr','addrNumber' => '65','addrCity' => 'Fuerth','addrZip' => '90763','unitNumber' => '6','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '23','Room_Name' => 'Schwabacherstr 65 - Apt 6 -  Room 2','addrStreet' => 'Schwabacherstr','addrNumber' => '65','addrCity' => 'Fuerth','addrZip' => '90763','unitNumber' => '6','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '24','Room_Name' => 'Schwabacherstr 65 - Apt 6 -  Room 3','addrStreet' => 'Schwabacherstr','addrNumber' => '65','addrCity' => 'Fuerth','addrZip' => '90763','unitNumber' => '6','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '25','Room_Name' => 'Schwabacherstr 65 - Apt 8 -  4th Floor Room 2','addrStreet' => 'Schwabacherstr','addrNumber' => '65','addrCity' => 'Fuerth','addrZip' => '90763','unitNumber' => '8','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '26','Room_Name' => 'Schwabacherstr 65 - Apt 8 -  4th Floor Room 3','addrStreet' => 'Schwabacherstr','addrNumber' => '65','addrCity' => 'Fuerth','addrZip' => '90763','unitNumber' => '8','accessDate' => '0000-00-00 00:00:00','live_update' => '1'),
    array('unitId' => '27','Room_Name' => 'Schwabacherstr 65, 1.OG Rechts, 4 Zimmer','addrStreet' => 'Schwabacherstr','addrNumber' => '65','addrCity' => 'Fuerth','addrZip' => '90763','unitNumber' => '8','accessDate' => '0000-00-00 00:00:00','live_update' => '1')
  );

  foreach($Address as $key=>$value) {

  	    foreach($value as $c=>$d) {

          $val = $value['Room_Name'];

          if (preg_match("/$val/i", $text))

            return $value['unitId'];		
  		}
    }
  }

	
  // echo "now going to update DB....<br>";

?>

<html>
    <body>
         <form enctype='multipart/form-data' action='#' method='post'>

         File name to import:<br />

         <input size='50' type='file' name='filename'><br />

         <input type='submit' name='submit' value='Upload'></form>
    </body>
</html>