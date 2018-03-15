<?php
/**
* @link http://gist.github.com/385876
*/
  require 'vendor/autoload.php';

  global $servername;
  global $username;
  global $password;
  global $dbname;

  $searchResult = array();
  $searchFlag = 0;

  $manager = new MongoDB\Driver\Manager('mongodb://127.0.0.1:27017'); // server code
  // $manager = new MongoDB\Driver\Manager('mongodb://localhost:27017'); // local codee

  error_reporting(E_ALL);

  if(isset($_POST['submit'])) {

    $uploaddir = 'uploads/';

    if (!file_exists('uploads/')) {
      mkdir('uploads/', 0777, true);
    }
    
    //FilePath with File name.
    $uploadfile = $uploaddir . basename($_FILES["filename"]["name"]);

      //Check if uploaded file is CSV and not any other format. //application/vnd.ms-excel
      if (($_FILES["filename"]["type"] == "text/csv")
        || ($_FILES["filename"]["type"] == "application/vnd.ms-excel")){

        //Move uploaded file to our Uploads folder.
        if (move_uploaded_file($_FILES["filename"]["tmp_name"], $uploadfile)) {

          //Import uploaded file to Database

          $bulk = new MongoDB\Driver\BulkWrite;

          $headerArray = array("Reservation_ID","Reservation_Code","Group_ID","Channel_ID","Guest_name","Guest_Email","Room_Name","Adults","Children","Infants","Total","Paid","Balance","Country","Arrival_Date","Departure_Date","Status","Created", "unitCode", "apartmentID", "interval","arrival_timestamp", "departure_timestamp");

          $index = 0;
          $headIdx = 0;
          $headerCount = count($headerArray);

          $update_reservationID = "";

          if (($handle = fopen($uploadfile, "r")) !== FALSE) {

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

              if($index!=0){

                $rowCount = count($data);

                $valArray = array();
                // we add two extra data fields - unitCode, apartmentID, interval, arrival_timestamp, departure_timestamp
                if ($headerCount == ($rowCount + 5)) {

                  for ($headIdx=0; $headIdx < $headerCount; $headIdx++) {

                    if ($headIdx < $rowCount) {

                      if ($headIdx == 7 || $headIdx == 8 || $headIdx == 9) {

                        $valArray[$headerArray[$headIdx]] = intval($data[$headIdx]);

                      } else if ($headIdx == 10 || $headIdx == 11 || $headIdx == 12) {

                        $valArray[$headerArray[$headIdx]] = floatval($data[$headIdx]);

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

                      } elseif ($headIdx == ($rowCount + 2)) {

                        $date_arrived = new DateTime($data[14]);
                        $date_depature = new DateTime($data[15]);

                        $interval = $date_arrived->diff($date_depature);

                        $valArray[$headerArray[$headIdx]] = intval($interval->format("%a"));

                      } elseif ($headIdx == ($rowCount + 3)) {

                        $date_arrived = new DateTime($data[14]);
                        $date_arrived->setTime(0,0,0);

                        $arrival_timestamp = $date_arrived->getTimestamp();

                        $valArray[$headerArray[$headIdx]] = $arrival_timestamp;

                      } elseif ($headIdx == ($rowCount + 4)) {

                        $date_depature = new DateTime($data[15]);
                        $date_depature->setTime(0,0,0);

                        $departure_timestamp = $date_depature->getTimestamp();

                        $valArray[$headerArray[$headIdx]] = $departure_timestamp;

                      }


                    }
                  }

                  // $bulk->insert($valArray);

                  $update_reservationID = $data[0];

                  $bulk->update(["Reservation_ID" => $update_apartmentID], $valArray, ['multi' => false, 'upsert' => true]);

                }
              }

              $index++;
            }

            $result = $manager->executeBulkWrite('booking.collection', $bulk);

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
  else if(isset($_GET['search_but'])) {

    $searchFlag = 1;

    $form_guest_name = $_GET["guestname"];
    $form_from_date = $_GET["fromdate"];
    $form_end_date = $_GET["enddate"];
    $form_unitid = $_GET["unitid"];

    $filter = [];

    if (!empty($form_guest_name)) {
      $filter["Guest_name"] = new MongoDB\BSON\Regex("{$form_guest_name}", "m");
    }

    if (!empty($form_unitid)) {
      $filter["apartmentID"] = $form_unitid;
    }

    $fromdate = null;
    $enddate = null;

    if (!empty($form_end_date)) {
      $enddate = DateTime::createFromFormat("Y-m-d", $form_end_date);
      $enddate->setTime(0,0,0);

      // $filter["departure_timestamp"] = array('$gte' => $enddate->getTimestamp());
    }

    if (!empty($form_from_date)) {
      
      $fromdate = DateTime::createFromFormat("Y-m-d", $form_from_date);
      $fromdate->setTime(0,0,0);

      if ($enddate == null) {

        $filter["arrival_timestamp"] = array('$gte' => $fromdate->getTimestamp());

      } else {

        $filter["arrival_timestamp"] = array('$gte' => $fromdate->getTimestamp(), '$lte' => $enddate->getTimestamp());

      }

      
    }

    

    $options = [];

    $query = new MongoDB\Driver\Query($filter, $options);
    $searchResult = $manager->executeQuery('booking.collection', $query);
    

  }
  else if(isset($_GET['search_current_guest'])) {

    $searchFlag = 2;

    $form_unitid = $_GET["search_unitid"];

    $filter = [];

    if (!empty($form_unitid)) {
      $filter["apartmentID"] = $form_unitid;
    }

    $today = new DateTime();
    $today->setTime(0,0,0);

    $today_timestamp = $today->getTimestamp();

    $filter["arrival_timestamp"] = array('$lte' => $today_timestamp);
    $filter["departure_timestamp"] = array('$gt' => $today_timestamp);
    $filter["Status"] = "Confirmed";

    $options = [];

    $query = new MongoDB\Driver\Query($filter, $options);
    $searchResult = $manager->executeQuery('booking.collection', $query);
  }

  function getApartmentID ($text) {

    $Address = array(
    array('Room_Name' => 'Schwabacher 65 - 2. OG links Room 1','unitId' => '23'),
    array('Room_Name' => 'Schwabacher 65 - 2. OG links Room 2','unitId' => '24'),
    array('Room_Name' => 'Schwabacher 65 - 2. OG links Room 3','unitId' => '25'),
    array('Room_Name' => 'Schwabacher 65 - 2. OG links Room 4','unitId' => '26'),
    array('Room_Name' => 'Schwabacherstr 65 - 3. OG rechts Room 1','unitId' => '27'),
    array('Room_Name' => 'Schwabacherstr 65 - 3. OG rechts Room 2','unitId' => '28'),
    array('Room_Name' => 'Schwabacherstr 65 - 3. OG rechts Room 3','unitId' => '29'),
    array('Room_Name' => 'Schwabacherstr 65 - 4. OG rechts Room 1','unitId' => '30'),
    array('Room_Name' => 'Schwabacherstr 65 - 4. OG rechts Room 2','unitId' => '31'),
    array('Room_Name' => 'Schwabacherstr 65 - 4. OG rechts Room 3','unitId' => '32'),
    array('Room_Name' => 'Kornstr 4, 3. OG Links, 4 Zimmer','unitId' => '1'),
    array('Room_Name' => 'Kormstr 18, 2. OG Rechts, 2 Zimmer','unitId' => '10'),
    array('Room_Name' => 'Kornstr 18, 1.OG Rechts, 2 Schlafzimmer','unitId' => '11'),
    array('Room_Name' => 'Kornstr 18, 3.OG Rechts, 4 Zimmer','unitId' => '12'),
    array('Room_Name' => 'Kornstr 18, EG Links, 2 Schlafzimmer','unitId' => '13'),
    array('Room_Name' => 'Heiligenstr 12, 2. OG, Zimmer 3','unitId' => '14'),
    array('Room_Name' => 'Kornstr 4, EG Links, 2 Schlafzimmer','unitId' => '15'),
    array('Room_Name' => 'Heiligenstr 12, 1.OG, Zimmer 12','unitId' => '16'),
    array('Room_Name' => 'Kornstr 4, Hinterhaus','unitId' => '17'),
    array('Room_Name' => 'Kornstr. 4, 1 Bedroom Ground Floor Apt. #2','unitId' => '18'),
    array('Room_Name' => 'Nürnberger 43, 2. OG Links, 3 Schlafzimmer','unitId' => '19'),
    array('Room_Name' => 'Heiligenstr 12, 1. OG, Studio Nr 10','unitId' => '2'),
    array('Room_Name' => 'Heiligenstr 12, 1.OG, Zimmer 11','unitId' => '20'),
    array('Room_Name' => 'Nürnberger 43, Ground Floor Left','unitId' => '21'),
    array('Room_Name' => 'Schwabacherstr 65, 1.OG Rechts, 4 Zimmer','unitId' => '22'),
    array('Room_Name' => 'Nürnberger 43, 2. OG rechts, 2 Schlafzimmer','unitId' => '3'),
    array('Room_Name' => 'Kornstr 4, DG Rechts','unitId' => '4'),
    array('Room_Name' => 'The Hat Shop Nürnberger 43, Ground Floor','unitId' => '5'),
    array('Room_Name' => 'Heiligenstr 12, 2. OG, Zimmer 8','unitId' => '6'),
    array('Room_Name' => 'Heiligenstr 12, DG Links, Nr. 1','unitId' => '7'),
    array('Room_Name' => 'Heiligenstr 12, DG Rechts, Nr. 2','unitId' => '8'),
    array('Room_Name' => 'Heiligenstrasse 12, 2. OG Apartment 6','unitId' => '9')
    );

    foreach($Address as $key=>$value) {

        foreach($value as $c=>$d) {

          $val = $value['Room_Name'];

          if (preg_match("/$val/i", $text))

            return $value['unitId'];    
      }
    }
  }

  
  $fieldArray = ["Reservation ID","Guest name","Guest Email","Room Name", "Apartment ID", "Adults","Total","Paid","Balance","Country","Arrival Date","Departure Date","Status","Created"];

  // Global search for non query
  if ($searchFlag == 0) {

    $options = [];
    $initFilter = [];

    $today = new DateTime();
    $today->setTime(0,0,0);

    $today_timestamp = $today->getTimestamp();

    $initFilter["arrival_timestamp"] = array('$lte' => $today_timestamp);
    $initFilter["departure_timestamp"] = array('$gt' => $today_timestamp);
    $initFilter["Status"] = "Confirmed";

    $query = new MongoDB\Driver\Query($initFilter, $options);
    $searchResult = $manager->executeQuery('booking.collection', $query);
    
  } 

?>

<html>
    <body>
      <form enctype='multipart/form-data' action='#' method='post'>

        <h3>Stage 1 : CSV to MongoDB</h3>
        <h3>Select the booking file to import: </h3>

        <input size='50' type='file' name='filename'><br />

        <input type='submit' name='submit' value='Upload'>

      </form>

      <form enctype='multipart/form-data' action='#' method='get'>

        <h3>Stage 2 : Search</h3>
        <h3>Advanced Search</h3>

        Guest name: <input type="text" name="guestname"><br/>
        Unit ID(Apartment ID): <input type="text" name="unitid"><br/>
        Date Range : <input type="date" name="fromdate"> - <input type="date" name="enddate"> <br /><br />

        <input type='submit' name='search_but' value='Search'>

      </form>

      <form enctype='multipart/form-data' action='#' method='get'>

        <h3>Current Guests</h3>

        Unit ID(Apartment ID): <input type="text" name="search_unitid"><br/>

        <input type='submit' name='search_current_guest' value='Search'>

      </form>

      <table border="1">
        
        <tr>
          <?php

            foreach ($fieldArray as $key => $value) :
            ?>
            <th><?php echo $value; ?></th>

          <?php endforeach; ?>
        </tr>

        <?php
          foreach($searchResult as $document):
        ?>
        <tr>
          <td><?php echo $document->Reservation_ID ?></td>
          <td><?php echo $document->Guest_name ?></td>
          <td><?php echo $document->Guest_Email ?></td>
          <td><?php echo $document->Room_Name ?></td>
          <td><?php echo $document->apartmentID ?></td>
          <td><?php echo $document->Adults ?></td>
          <td><?php echo $document->Total ?></td>
          <td><?php echo $document->Paid ?></td>
          <td><?php echo $document->Balance ?></td>
          <td><?php echo $document->Country ?></td>
          <td><?php echo $document->Arrival_Date ?></td>
          <td><?php echo $document->Departure_Date ?></td>
          <td><?php echo $document->Status ?></td>
          <td><?php echo $document->Created ?></td>
        </tr>
      <?php endforeach; ?>

      </table>
      
    </body>
</html>