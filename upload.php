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
          echo "Success";
      }else{
         print_r($errors);
      }
   }
?>
<html>
   <body>
      
      <form action="" method="POST" enctype="multipart/form-data">
         <input type="file" name="image" />
         <input type="submit"/>
      </form>
      
   </body>
</html>