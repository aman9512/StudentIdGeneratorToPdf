<html>
<style>
.container {
  position:fixed;
 top: 50%;
 left: 50%;
 width:30em;
 height:18em;
 margin-top: -9em; /*set to a negative number 1/2 of your height*/
 margin-left: -15em; /*set to a negative number 1/2 of your width*/
 border: 1px solid #ccc;
    background-color: rgba(0, 153, 255, 0.8);
    padding: 20px;
}
</style>
<body style="background-color: rgba(255, 153, 60, 0.7)">

    <div class="container">
      <h3> Your file path is:  <?php echo $_POST["filePath"]; ?></h3><br>
      <h3>Your images folder path is: <?php echo  $_POST["imagesPath"]; ?> </h3><br>
      <h3>Your pdf is exported to this folde: <?php echo  $_POST["pdfPath"]; ?> </h3><br>
    </div>
</body>
</html>

<?php
 // create new PDF document
 // Include the main TCPDF library (search for installation path).
 $csvFile = $_POST["filePath"];
 $studentImages = $_POST["imagesPath"];
 $pdfPath = $_POST["pdfPath"];

 if(empty($_POST["NumberOfId"]) || $_POST["NumberOfId"] > 6) $GLOBALS["NumberOfId"] = 6;
 else $GLOBALS["NumberOfId"] = $_POST["NumberOfId"];

 function createSqlDb()
 {
       DEFINE('DB_USERNAME', 'root');
       DEFINE('DB_PASSWORD', 'root');
       DEFINE('DB_HOST', 'localhost');

       $mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);

       if (mysqli_connect_error()) {
         die('Connect Error ('.mysqli_connect_errno().') '.mysqli_connect_error());
       }

       if(!$mysqli->query("DROP DATABASE IF EXISTS TempKS")) echo 'error in dropping db';
       if(!$mysqli->query("CREATE DATABASE IF NOT EXISTS TempKS")) echo $mysqli->error;
       if(!$mysqli->query("use TempKS")) echo $mysqli->error;

       $createStudentTable = "CREATE TABLE IF NOT EXISTS students"
                              . "(id VARCHAR(20) NOT NULL PRIMARY KEY,"
                              . "firstName VARCHAR(30) NOT NULL,"
                              . "lastName VARCHAR(30) NOT NULL,"
                              . "class VARCHAR(30) NOT NULL)";
       if(!$mysqli->query($createStudentTable)) echo $mysqli->error . ' error issue with creating table';

       insertCsvIntoTable($mysqli);
 }

 function insertCsvIntoTable(&$mysqli)
 {
    $tempPath = $_POST["filePath"];
    $insertDataIntoTable = "LOAD DATA LOCAL INFILE '$tempPath' INTO TABLE students"
                          . " FIELDS TERMINATED BY ','"
                          . " LINES TERMINATED BY '\r\n'"
                          . " IGNORE 1 LINES";

    if(!$mysqli->query($insertDataIntoTable)) echo $mysqli->error. ' error inserting csv file';
    fetchDataFromTable($mysqli);
 }

 function fetchDataFromTable(&$mysqli)
 {
   $qry = "SELECT * FROM students";
   $GLOBALS['$result'] = $mysqli->query($qry);
 }
 if(file_exists($csvFile) && file_exists($studentImages) && !file_exists($pdfPath))
 {
   createSqlDb();

   require_once('PhpPdfGenerator/TCPDF/tcpdf.php');

   // create new PDF document
   $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
   $pdf->SetCreator(PDF_CREATOR);

   $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
   $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

   // set default monospaced font
   $pdf->SetDefaultMonospacedFont('helvetica');

   // set margins
   $pdf->SetMargins(PDF_MARGIN_LEFT, '5', PDF_MARGIN_RIGHT);
   $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
   $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
   $pdf->setPrintHeader(false); //removes header line
   $pdf->setPrintFooter(false);
   // set auto page breaks
   $pdf->SetAutoPageBreak(false, 0); //to automatically break page
   //second parameter defines the space from the bottom to when to break the page

   // set image scale factor
   $pdf->setImageScale(1.47);

   // set some language-dependent strings (optional)
   if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
       require_once(dirname(__FILE__).'/lang/eng.php');
       $pdf->setLanguageArray($l);
   }
   $counter  = 1;
   $PosX = 0;
   $PosY = 0;
   $pdf->AddPage(); //for the first time
   $numPerRow = 3;
   $idWidth = 60;
   $idHeight = 95;
   while ($row = $GLOBALS['$result']->fetch_assoc()) {

       if($counter % ($GLOBALS["NumberOfId"]+1) == 0)
       {
         $pdf->AddPage();
         $counter = 1;
         $PosX = 0;
         $PosY = 0;
       }

       if($counter % ($numPerRow + 1) == 0) $PosY = $idHeight + 2;
       if($counter == 1 || $counter == 4) $PosX = (65*(($counter-1)%$numPerRow));
       else $PosX = (65*(($counter-1)%$numPerRow)) + 2;

       $pdf->SetLineStyle( array( 'width' => .5, 'color' => array(0,0,0)));
       $pdf->Rect(2 + $PosX, 5 + $PosY, $idWidth, $idHeight); //x,y,w,h

       $tempStudentImage =  $studentImages . "/" . $row['id'] . ".jpg";
       if(!file_exists($tempStudentImage)) $tempStudentImage = 'SikhAvatar.jpg';

       $pdf->Image($tempStudentImage, 2 + $PosX, 5 + $PosY, $idWidth, 56, 'JPG', '', '', false, 200, '', false, false, 0, false, false, true);

       $pdf->SetAlpha(0.6); //for image transparency
       $pdf->Image('Khalsa School Logo_Final.jpg', 44.5 + $PosX, 5 + $PosY, 18, 18, 'JPG', '', '', false, 150, '', false, false, 0, false, false, true);

       $pdf->SetAlpha(1.0);
       $html = "<h5 style=text-align:center>Guru Angad Dev Khalsa School</h5>";
       $pdf->writeHTMLCell(56, 10, 6 + $PosX, 62 + $PosY, $html, 0, 0, 0, true, 'C', false); //w,h,x,y
       $html = "<h5 style=text-align:center> El Sobrante </h5>";
       $pdf->writeHTMLCell(56, 10, 4 + $PosX, 66 + $PosY, $html, 0, 0, 0, true, 'C', false);

       $pdf->SetFont('times', 'B', 12, '');
       $pdf->SetXY(2 + $PosX,68 + $PosY);
       $pdf->Cell(55, 12, 'ID: ' . $row['id'] . " ". 'Class: ' . $row['class'], 0, 0, 'C', 0, '', 0, false, 'T', 'C');
       $pdf->Ln(3);
       $pdf->SetFont('times', 'B', 18, '');
       $pdf->SetXY(4 + $PosX,73 + $PosY);
       $pdf->Cell(55, 18, $row['firstName'], 0, 0, 'C', 0, '', 0, false, 'T', 'C');
       $pdf->Ln(7);
       $pdf->SetXY(4+ $PosX,80 + $PosY);
       $pdf->Cell(55, 18, $row['lastName'], 0, 0, 'C', 0, '', 0, false, 'T', 'C');
       $pdf->SetFont('times', '', 12, '');

       $counter++;
 }

   $pdf->Output($pdfPath, 'F');
   $GLOBALS['$result']->free();
   $mysqli->close();
}
else {
  echo 'There was an error in generating pdf file';
}
?>
