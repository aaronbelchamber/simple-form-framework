<?php


class adminBaseController {

    protected $userDataArr=array(); //User's entire record from user_profile table

    protected $wpUserCookieName='wordpress_logged_in_wp-front-end-user-profile-plugin-cookie';

    public $mainDbArr   =array('dbName'=>'dbName','dbUser'=>'dbUser','dbPassword'=>'dbPassword');
    public $mainDbConn;

    public $wpDbArr     =array('dbName'=>'dbName','dbUser'=>'dbUser','dbPassword'=>'dbPassword');
    public $wpDbConn;

    public $loginPath='/account/login/';  // If not logged in, user gets redirected here

    public $appRootPath;

	public $viewBaseFormObj;

    /**
     *  Establish connection to both main db and WP site for total interfacing between both.
     *
     *  Initializes User Info authentication, too
     *
     *  Base methods to display forms and process data
     *
     */
    public function __construct(){

        $this->mainDbConn=new PDO('mysql:host=localhost;dbname='.$this->mainDbArr['dbName'].';charset=utf8',
            $this->mainDbArr['dbUser'], $this->mainDbArr['dbPassword']);

        $this->wpDbConn=new PDO('mysql:host=localhost;dbname='.$this->wpDbArr['dbName'].';charset=utf8',
            $this->wpDbArr['dbUser'], $this->wpDbArr['dbPassword']);

        $this->appRootPath=APP_ROOT_PATH;

       $this->userDataArr=$this->getUserData();

	  //  include($this->appRootPath.'/main/views/Base/viewBaseForm.php');
	  //  $this->viewBaseFormObj=new viewBaseForm($this->userDataArr);

       //echo "DEBUG ".__FUNCTION__.": ".var_dump($this->userDataArr); exit;
        //  new PDO('mysql:host=localhost;dbname=testdb;charset=utf8', 'username', 'password');

    }

    /**
     *  Authenticates user and passes user info on
     *
     *  WP Login plugin cookie:
     *   [wordpress_logged_in_2ffb8bdb98e2e71b31a9992d235b98a6] =>
     *      Rent Minion|1452343298|AhuJOtN0QotdAkPqkapfEE0CIe4YrY6IAo17oyrtuqg|047e38ce4a977c6da18871a1425ed53d040b2d73cee7a6181af17decaeee3986
     *
     *  wp_usermeta table:  Looks like below s:5 keys:5:"login";i:1451133698;
     *  a:1:{s:64:"47c2ef8a0a40f3dc29fb686306fd2183e9854455da77fd7bb147231706f9d1c4";
     *  a:4:{s:10:"expiration";i:1452343298;s:2:"ip";s:13:"71.199.245.32";s:2:"ua";
     *  s:73:"Mozilla/5.0 (Windows NT 10.0; WOW64; rv:43.0) Gecko/20100101 Firefox/43.0";s:5:"login";i:1451133698;}}
     */
    public function intiializeGlobals(){



    }



    /**
     * @param $wpUserInfoArr
     * @return query result of entire user_profiles table
     */
    public function getUserData(){

        $wpUserInfoArr=$this->getUserInfoFromWpCookie();
        $email=@$wpUserInfoArr['user_email'];
        if(empty($email)){ return false;}

        $query="SELECT * FROM user_profiles WHERE email=?";

        //  echo $query."<br/>$email";
        $userRecs= $this->dbGetData($this->mainDbConn,$query,array($email),$email.' is an invalid email',true);
        return @$userRecs[0];
    }

    /**
     * @return array from query with elements ID,user_email,display_name
     */
    public function getUserInfoFromWpCookie(){

        $userArr=explode('|',@$_COOKIE[$this->wpUserCookieName]);
        $user_login=$userArr[0];

	    if(!$user_login){
	        // Redirect if can't login

	    }

        //WP user_email is the key to user_profile table
        $query="SELECT ID,user_email,display_name FROM wp_users WHERE user_login LIKE ?";

        $resArr= $this->dbGetData($this->wpDbConn,$query,array($user_login),'User could not be determined.',true);
        return @$resArr[0];
        /*try {
                //connect as appropriate as above
                $stmt=$this->wpDbConn->prepare($query);
                $stmt->execute(array($user_login));
                $res= $stmt->fetchAll(PDO::FETCH_ASSOC);
                return @$res[0];

        } catch(PDOException $ex) {
            echo "User could not be determined."; //user friendly message
            //some_logging_function($ex->getMessage());
            return false;
        }*/
    }

    /**
     * @param $dbConn
     * @param $query
     * @param $prepareValsArr
     * @param string $errorMsg
     * @param bool $showErrorMsg        If acts like an API, this would be the failure response
     * @param bool  $isApi              If is an API, returns query as a JSON structured string
     *
     * @return bool
     *
     *
     */
    public function dbGetData($dbConn,$query,$prepareValsArr,$errorMsg='',$showErrorMsg=true,$isApi=false){

        try {
                //connect as appropriate as above
                $stmt=$dbConn->prepare($query);
                $stmt->execute($prepareValsArr);
                $res= $stmt->fetchAll(PDO::FETCH_ASSOC);

                if($isApi) return json_decode($res);
                return @$res;

            } catch(PDOException $ex) {
                if($showErrorMsg){
                    echo $errorMsg; //user friendly message
                    //some_logging_function($ex->getMessage());
                    return false;
                }
                //TODO: else put message into "alerts" flash array
            }

        return false;
    }

	/**
	 * @param        $dbConn
	 * @param        $query
	 * @param        $prepareValsArr
	 * @param string $errorMsg
	 * @param bool   $showErrorMsg
	 * @param bool   $isApi
	 * @return bool
	 *
	 * Runs Delete queries and other executables that aren't SELECTS or INSERTS
	 */
	public function dbExecute($dbConn, $query, $prepareValsArr,$errorMsg='',$showErrorMsg=true,$isApi=false){

		try {
				//connect as appropriate as above
				$stmt=$dbConn->prepare($query);
				$res=$stmt->execute($prepareValsArr);
				return $res;

		} catch(PDOException $ex) {
			if($showErrorMsg){
				echo $errorMsg; //user friendly message
				//some_logging_function($ex->getMessage());
				return false;
			}
			//TODO: else put message into "alerts" flash array
		}

		return false;

	}
        /*
         *
         *
         $stmt = $db->prepare("SELECT * FROM table WHERE id=? AND name=?");
         $stmt->execute(array($id, $name));
         $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        function getData($db) {
            $stmt = $db->query("SELECT * FROM table");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        //then much later
                try {
                    getData($db);
                } catch(PDOException $ex) {
                    //handle me.
                }
        */

    public function showDataAsHtml($arr){

        if(count($arr)==0){ return false;}

        echo '<div class="form-summary">';
        foreach($arr as $row){

            foreach ($row as $key=>$val){
                 echo '<div class="col-md-4">'.$key.'<div><div calss="col-md-8">'.$val.'</div>';
            }
        }
        echo '</div>';

        return true;
    }

    /**
     * @param $responseArr
     *
     * As a default, this will show the values of the form data submitted with a "Success" message
     *
     */
    public function formSuccessResponse($responseArr){

        $entity= @$responseArr['entity_name'];
        $recId = @$responseArr['id'];

        $query="SELECT * FROM $entity WHERE id=?";
        $tableArr= $this-> dbGetData($this->mainDbConn,$query,array($recId),'',true,false);
        return $tableArr;
    }


    public function formFailureResponse($responseArr){

        return $this->displayFormContent();

    }


    function redirectToLogin(){

        echo "<p>Simulating redirect for debugging...</p>"; return false;

        header("HTTP/1.1 301 Moved Permanently");
        header("Location:$this->loginPath");
        exit;
    }

    /**
     * @param array|\query $userDataArr
     */
    public function setUserDataArr($userDataArr)
    {
        $this->userDataArr = $userDataArr;
    }

    /**
     * @return array|\query
     */
    public function getUserDataArr()
    {
        return $this->userDataArr;
    }




}