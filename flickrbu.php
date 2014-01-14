<?
require_once("phpFlickr.php");

class flickrBu{
    private $fileToUpload;
    private $dirPath;
    private $totalUploadedFiles = 0;
    private $apiKey;
    private $apiSecret;
    private $permissions;
    private $token;
    public $tag;

    public function flickrBu($apiKey, $apiSecret, $permissions, $token){
    	global $argv;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->permissions = $permissions;
        $this->token = $token;
        $this->dirPath = isset($argv[1]) ? $argv[1] : '.';
        if(substr($this->dirPath, -1) !== '/')
            $this->dirPath .= '/';
    }


    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
        return $this;
    }

    public function uploadPhoto($path, $title) {
        $f = new phpFlickr($this->apiKey, $this->apiSecret, false);
        $f->setToken($this->token);
        return $f->async_upload($path, $title, "", $this->tag, "0", "0", "0");
    }

    public function buinfoExists($buinfo = 'buinfo'){
        return file_exists($this->dirPath . $buinfo);
    }

    public function createBuinfo($buinfo = 'buinfo'){
        $files = scandir($this->dirPath);
        $bui = fopen($this->dirPath . $buinfo, 'w');
        $fileCounter = 0;
        foreach($files as $f){
            if(strtolower(substr($f,-4)) === '.jpg' || strtolower(substr($f,-5)) === '.jpeg'){
                fprintf($bui, "%d|%s|ready|0000-00-00 00:00:00\n", ++$fileCounter, $f);
            }
        }
        fclose($bui);
        print "buinfo file is created.\n";
    }

    public function updateBuinfo($uploadStatus, $buinfo = 'buinfo'){
    	$newContent = array();
        $contents = file($this->dirPath . $buinfo);
        foreach ($contents as $line) {
			$data = explode('|', $line);
			if($data[1] === $this->fileToUpload){
			//	$dateTime = $uploadStatus === 'uploaded' ? date('Y-m-d H:i:s') : '0000-00-00 00:00:00';
        		$toAddLine = $data[0] . '|' . $data[1] . '|' . $uploadStatus . '|' . date('Y-m-d H:i:s') . "\n";
			}else{
				$toAddLine = $line;
			}
			array_push($newContent, $toAddLine);
		}

		file_put_contents($this->dirPath . $buinfo, $newContent);

    }

    public function getFileToUpload($uploadFailedFiles = false, $buinfo = 'buinfo'){
    	$buinfoFile = file($this->dirPath . $buinfo);
    	foreach ($buinfoFile as $line) {
    		$data = explode('|', $line);
    	    if($uploadFailedFiles && $data[2] === 'failed'){
                return $data[1];
            }elseif($data[2] === 'ready'){
                return $data[1];
            }
    	}
    	return false;
    }

    public function startUpload($uploadFailedFiles = false){
        if(!$this->buinfoExists()){
            $this->createBuinfo();
        }

        $this->fileToUpload = $uploadFailedFiles ? $this->getFileToUpload(true) : $this->getFileToUpload();
        if($this->fileToUpload){
        	print $this->fileToUpload . " is being uploaded.\n";
            $response = $this->uploadPhoto($this->dirPath . $this->fileToUpload, $this->fileToUpload);
            if($response){
                $uploadStatus = 'uploaded';
                print $this->fileToUpload . " is uploaded! (total: " . ++$this->totalUploadedFiles . " files)\n\n";
            }else{
                $uploadStatus = 'failed';
                print $this->fileToUpload . " cannot be uploaded!\n\n";
            }
        }else{
            print "no files to upload!\n";
            return false;
        }
        $this->updateBuinfo($uploadStatus);
        $this->startUpload($uploadFailedFiles);
    }
}

$fbu = new flickrBu('API_KEY', 'API_SECRET', 'write', 'TOKEN');
$fbu->tag = "";
$fbu->startUpload(true);

// TODO
// skip if fails twice
// upload failed files option as argument: $fbu->startUpload($argv[2] === "failed")
// after finishing once check for the fails and call startupload("failed")
// accept all options as arguments: tag=exampletag failed=1


?>
