<html>
 <head>
 <Title>Registration Form</Title>
 <style type="text/css">
 	body { background-color: #fff; border-top: solid 10px #000;
 	    color: #333; font-size: .85em; margin: 20; padding: 20;
 	    font-family: "Segoe UI", Verdana, Helvetica, Sans-Serif;
 	}
 	h1, h2, h3,{ color: #000; margin-bottom: 0; padding-bottom: 0; }
 	h1 { font-size: 2em; }
 	h2 { font-size: 1.75em; }
 	h3 { font-size: 1.2em; }
 	table { margin-top: 0.75em; }
 	th { font-size: 1.2em; text-align: left; border: none; padding-left: 0; }
 	td { padding: 0.25em 2em 0.25em 0em; border: 0 none; }
 </style>
 </head>
 <body>
 <h1>Register here!</h1>
 <p>Fill in your name and email address, then click <strong>Submit</strong> to register.</p>
 <form method="post" action="index.php" enctype="multipart/form-data" >
       Name  <input type="text" name="name" id="name"/></br></br>
       Email <input type="text" name="email" id="email"/></br></br>
       Job <input type="text" name="job" id="job"/></br></br>
       Photo <input type="file" name="image" /></br></br>
       <input type="submit" name="submit" value="Submit" />
       <input type="submit" name="load_data" value="Load Data" />
 </form>
 <?php
    require_once 'vendor/autoload.php';
    require_once "./random_string.php";

    use MicrosoftAzure\Storage\Blob\BlobRestProxy;
    use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
    use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
    use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
    use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;

    // $connectionString = "DefaultEndpointsProtocol=https;AccountName=".getenv('ACCOUNT_NAME').";AccountKey=".getenv('ACCOUNT_KEY');
    $connectionString = "DefaultEndpointsProtocol=https;AccountName=muridwanwebapp;AccountKey=OS93CVicbfOXgD+6F5eWEcmOEwfq3Ylj3poJBCmD/fswrg/3S6YcjJuZD3ereosdZ+SagcpJTUEJ7XFIM9IM3g==";
    // Create blob client.
    $blobClient = BlobRestProxy::createBlobService($connectionString);
    
    $host = "muridwanwebappserver.database.windows.net";
    $user = "muridwan";
    $pass = "ridwan100%";
    $db = "muridwanwebapp";

    try {
        $conn = new PDO("sqlsrv:server = $host; Database = $db", $user, $pass);
        $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    } catch(Exception $e) {
        echo "Failed: " . $e;
    }

    if (isset($_POST['submit'])) {
      if(isset($_FILES['image'])){
          $errors= array();
          $file_name = $_FILES['image']['name'];
          $file_size =$_FILES['image']['size'];
          $file_tmp =$_FILES['image']['tmp_name'];
          $file_type=$_FILES['image']['type'];
          $file_ext=strtolower(end(explode('.',$_FILES['image']['name'])));
          
          $expensions= array("jpeg","jpg","png");
          
          if(in_array($file_ext,$expensions)=== false){
             $errors[]="extension not allowed, please choose a JPEG or PNG file.";
          }
          
          if($file_size > 2097152){
             $errors[]='File size must be excately 2 MB';
          }
          
          if(empty($errors)==true){
             //move_uploaded_file($file_tmp,"images/".$file_name);
              $fileToUpload = $file_tmp;
             // Create container options object.
              $createContainerOptions = new CreateContainerOptions();
    
              // Set public access policy. Possible values are
              // PublicAccessType::CONTAINER_AND_BLOBS and PublicAccessType::BLOBS_ONLY.
              // CONTAINER_AND_BLOBS:
              // Specifies full public read access for container and blob data.
              // proxys can enumerate blobs within the container via anonymous
              // request, but cannot enumerate containers within the storage account.
              //
              // BLOBS_ONLY:
              // Specifies public read access for blobs. Blob data within this
              // container can be read via anonymous request, but container data is not
              // available. proxys cannot enumerate blobs within the container via
              // anonymous request.
              // If this value is not specified in the request, container data is
              // private to the account owner.
              $createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);
    
              // Set container metadata.
              $createContainerOptions->addMetaData("key1", "value1");
              $createContainerOptions->addMetaData("key2", "value2");
    
              $containerName = "blockblobs".generateRandomString();
    
              try {
                  // Create container.
                  $blobClient->createContainer($containerName, $createContainerOptions);
    
                  // Getting local file so that we can upload it to Azure
                  //$myfile = fopen($fileToUpload, "w") or die("Unable to open file!");
                  //fclose($myfile);
                  
                  # Upload file as a block blob
                  echo "Uploading BlockBlob: ".PHP_EOL;
                  echo $fileToUpload;
                  echo "<br />";
                  
                  $content = fopen($fileToUpload, "r");
    
                  //Upload blob
                  $blobClient->createBlockBlob($containerName, $file_name, $content);
    
                  // List blobs.
                  $listBlobsOptions = new ListBlobsOptions();
                  $listBlobsOptions->setPrefix($file_name);
    
                  echo "These are the blobs present in the container: ";
    
                  do{
                      $result = $blobClient->listBlobs($containerName, $listBlobsOptions);
                      foreach ($result->getBlobs() as $blob)
                      {
                          echo $blob->getName().": ".$blob->getUrl()."<br />";
                          $picname = $blob->getUrl();
                      }
                  
                      $listBlobsOptions->setContinuationToken($result->getContinuationToken());
                  } while($result->getContinuationToken());
                  echo "<br />";
    
                  // Get blob.
                  echo "This is the content of the blob uploaded: ";
                  $blob = $blobClient->getBlob($containerName, $file_name);
                  fpassthru($blob->getContentStream());
                  echo "<br />";
              }
              catch(ServiceException $e){
                  // Handle exception based on error codes and messages.
                  // Error codes and messages are here:
                  // http://msdn.microsoft.com/library/azure/dd179439.aspx
                  $code = $e->getCode();
                  $error_message = $e->getMessage();
                  echo $code.": ".$error_message."<br />";
              }
              catch(InvalidArgumentTypeException $e){
                  // Handle exception based on error codes and messages.
                  // Error codes and messages are here:
                  // http://msdn.microsoft.com/library/azure/dd179439.aspx
                  $code = $e->getCode();
                  $error_message = $e->getMessage();
                  echo $code.": ".$error_message."<br />";
              }   
              try {
                $name = $_POST['name'];
                $email = $_POST['email'];
                $job = $_POST['job'];
                $date = date("Y-m-d");
                // Insert data
                $sql_insert = "INSERT INTO Registration (name, email, job, date,picname) 
                            VALUES (?,?,?,?,?)";
                $stmt = $conn->prepare($sql_insert);
                $stmt->bindValue(1, $name);
                $stmt->bindValue(2, $email);
                $stmt->bindValue(3, $job);
                $stmt->bindValue(4, $date);
                $stmt->bindValue(5,$picname);
                $stmt->execute();
              } catch(Exception $e) {
                echo "Failed: " . $e;
              }
              echo "<h3>Your're registered!</h3>";
            }else{
             print_r($errors);
            }
        }
        else
        {
            try {
                $name = $_POST['name'];
                $email = $_POST['email'];
                $job = $_POST['job'];
                $date = date("Y-m-d");
                // Insert data
                $sql_insert = "INSERT INTO Registration (name, email, job, date) 
                            VALUES (?,?,?,?,?)";
                $stmt = $conn->prepare($sql_insert);
                $stmt->bindValue(1, $name);
                $stmt->bindValue(2, $email);
                $stmt->bindValue(3, $job);
                $stmt->bindValue(4, $date);                
                $stmt->execute();
            } catch(Exception $e) {
                echo "Failed: " . $e;
            }
    
            echo "<h3>Your're registered!</h3>";
        }
    } else if (isset($_POST['load_data'])) {
        try {
            $sql_select = "SELECT * FROM Registration";
            $stmt = $conn->query($sql_select);
            $registrants = $stmt->fetchAll(); 
            if(count($registrants) > 0) {
                echo "<h2>People who are registered:</h2>";
                echo "<table>";
                echo "<tr><th>Name</th>";
                echo "<th>Email</th>";
                echo "<th>Job</th>";
                echo "<th>Date</th></tr>";
                echo "<th>Photo</th></tr>";
                foreach($registrants as $registrant) {
                    echo "<tr><td>".$registrant['name']."</td>";
                    echo "<td>".$registrant['email']."</td>";
                    echo "<td>".$registrant['job']."</td>";
                    echo "<td>".$registrant['date']."</td></tr>";
                    echo "<td>".$registrant['picname']."</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<h3>No one is currently registered.</h3>";
            }
        } catch(Exception $e) {
            echo "Failed: " . $e;
        }
    }
 ?>
 </body>
 </html>