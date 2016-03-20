<?php

class formBaseController extends adminBaseController {

	public $idIndexArr;     // Assoc array:  "table_name":"id"

	public $procFieldNamesIndex=0;

	public $procFieldCollIndex=0;

	public $collectionFormInputWidgets;

	public $submittedFormArr;

	public $associatedTableIdsArr=array();  // When persisting a collection, keeps the FK's of each associated table


    /**
     * @param null $request
     * @param $formEntity
     * @param null $subview
     * @param array $optionsArr
     * @return mixed|string
     *
     * This starts up the page and directs the rest of the flow to other methods or controllers.
     * If form submitted, goes to processSubmitAction, if needs to output a view, passes off request and data to appropriate view
     *
     */
    public function processPageAction($request=null,$formEntity,$subview=null, $optionsArr=array()){

        if ($_SERVER['REQUEST_METHOD'] == 'POST') $validSubmitArr= $this->processSubmitAction($request,$formEntity,$optionsArr);

	    // $processSubmit will return 'success' in 'result' index if it's  valid, then pass on the 'data' values of form
	    if(@$validSubmitArr['result']=='success') {

		    echo "<p>SUBMITTED FORM: ".print_r($this->submittedFormArr,true)."</p>";
		    echo "<p>Assoociated Table IDs: ".print_r($this->associatedTableIdsArr)."</p>";

		    return $validSubmitArr;
	    }

	    //TODO: Pass $validSubmitArr errors to form again for user correction
        // No form submitted, so show the form
        $html=$formEntity->displayFormContent($request,$formEntity,$subview,$optionsArr);
        return $html;
    }


    /**
     * @param $request
     * @param $formEntity
     * @param array $optionsArr
     *
     * Will process the submitted form action and go through the form's fields' submitted values
     *
     * Will return response array:  "result":"failed/success"
     * then                         "data" element where
     * each field as so:                "fieldName":{"value":"cleaned value","valid":true/false,
     *                                              "errors":"none" or "List of errors"}
     *
     */

    public function processSubmitAction($request,$formEntity,$optionsArr=array()){

	    // First validate submission, which will return ['result']='success' to process to form action of INSERT, DELETE or UPDATE
        $validatedFormArr=$this->validateSubmitAction($request,$formEntity);
	    $formSuccess=false;

	    if($validatedFormArr['result']=='success'){
		    $dataArr=@$validatedFormArr['data'];
			if(is_array($dataArr)) {$formSuccess=true;}
	    }

        // Form submission failed, need to rerender form with the errors
		if(!$formSuccess){
			echo "<p>DEBUG FORM ERRORS</p>";
			return $validatedFormArr;
		}

	    // Look for actions, which are always defined in the form submission button pressed
	    // "save", "save_as_draft", "update", or "delete"
	    $formAction=$this->determineActionFromForm($request);
		if(empty($formAction)) {echo "<p>No form action found, form submission will be ignored.</p>"; return false;}

	    // Only need to process request if validation fails and needs to rerender form with error messages
	    // echo "<hr/><hr/>DEBUG landed at ".__FILE__.', '.__FUNCTION__.": FORM ACTION: $formAction"; exit;
	    //  echo "<p>POST CONTENT: ".print_r($validatedFormArr,true)."</p>";

	    if($formAction=='delete'){  return $this->formDeleteAction($validatedFormArr);}
	    if($formAction=='save'){  return $this->formSaveAction($validatedFormArr,$formEntity,$optionsArr);}
	    if($formAction=='save_as_draft'){
		    @$optionsArr['saveType']='draft';
		    return $this->formSaveAction($validatedFormArr,$formEntity,$optionsArr);
	    }
	    if($formAction=='update'){  return $this->formUpdateAction($validatedFormArr,$formEntity,$optionsArr);}

	    echo "<p>Form action, '$formAction' not registered so it will be ignored.</p>";
        return false;

        //  $w[2]['options']['validation']='not first'  for pulldowns, easy way to validate and make sure something was selected
    }


	function validateSubmitAction($request,$formEntity){

		//TODO: Depending on what allows as input, each field value gets validated and we build error messages and
		//      return "error" or we return "success" with any dependent details passed back

		$finArr=array();  // Cleaned POST data
		$passThruArr=array();
		$invalidCnt=0;

		// Use each Form Entity as the reference map then look for embedded arrays since they're defined as collections?!
		$processFormArr=$this->processFieldNodes($request,$formEntity);
		// Go through each field and validate based on inputType, if there are rules that violate, the validation map in the entity will provide error or warning

		// It builds the form map and it's already in the form's getFormCollectionArr()!!!!
		// var_dump($request); echo "<hr/>"; var_dump($inputWidgetsArr);   //  exit;

		echo "<hr/><hr/> PROCESS FORM ARRAY >>><br/>";  // var_dump($processFormArr);

		$invalidKeysArr = array_keys(array_column($processFormArr, 'valid'), '0');
		//echo "Invalid Keys Array: ".print_r($invalidKeysArr,true)."<hr/>";

		if(count($invalidKeysArr)>0){
			return array('result'=>'failed','data'=>$processFormArr);
		}

		return array('result'=>'success','data'=>$processFormArr);

	}

	/**
	 * Goes through all form fields, even nested, and checks values against the entitie's (table's) form map and checks validity
	 *
	 * @param      $request
	 * @param      $inputWidgetsArr
	 * @param      $formEntity
	 * @param null $collName
	 * @return array - Returns field name as index in array with two field arrays -- 'valid' and 'value'
	 *
	 */
	function processFieldNodes($request,$formEntity,$collName=null){

		//  return $request;

		$processFormArr=array();

		//This is a full map of entire form, we only need the 'inputType' and 'fieldName' mapped
		// $inputWidgetsArr=$formEntity->setFormInputWidgets();

		// Now, extract the form field map only to compare each field and process the values
		$formSubmitMapArr=$formEntity->setFormSubmitMap();

		echo "<p>".__FUNCTION__.": <pre>".print_r($request,true)."</pre><br/>";
		echo "<pre>".print_r($formSubmitMapArr,true)."</pre><br/>";
		exit;

		foreach($inputWidgetsArr as $formWidget){

			// Skip any non form field directives
			if(!isset($formWidget['inputType'])) continue;

			$field=@$formWidget['fieldName'];
			$inputType=@$formWidget['inputType'];

			// If the form's action field has value of null, it's excluded
			if(in_array($field,$this->getFormActionFields())){

				//  echo "reserved: ";  var_dump($request); exit;
				//  if(!isset($request[$field]))  continue;
				continue;
			}

			// echo "<br/>processFieldNode: $collName:$field, $inputType";

			/*
			 * from $request main key ["contact_info_collection"]=> array(8)
			 * {    [0]=> array(1) { ["organization_name"]=> string(0) "" }
			 *      [1]=> array(1) { ["first_name"]=> string(0) "" }
			 *      [2]=> array(1) { ["last_name"]=> string(0) "" }
			 *      [3]=> array(1) { ["address_1"]=> string(0) "" } [4]=> array(1) { ["address_2"]=> string(0) "" } [5]=> array(1) { ["city"]=> string(0) "" } [6]=> array(1) { ["state"]=> string(2) "AL" }
			 */
			if($inputType=='collection'){
				// Deal with nested arrays here
				$collFormEntity=$formEntity->getEntityForm($formWidget['collectionEntityName']);

				// This is recursive for nested collections, even in another collection
				$processFormArr[$field]=$this->processFieldNodes(@$request[$field],$collFormEntity,$formWidget['collectionEntityName']);
				$this->procFieldCollIndex++;
			}
			else {
					// Once we drum down to the field, even in a collection, checkFieldValidity() is critical is each entity to validate
					if(empty($collName)){
						$processFormArr[$field]=$formEntity->checkFieldValidity($field,@$request[$field]);
					}
					else {
							echo "<br/>Collection: request[$this->procFieldNamesIndex][$field]) = ".@$request[$this->procFieldNamesIndex][$field];
							//$formCollEntity=$formEntity->getEntityForm($collName);
							$processFormArr[$field]=$formEntity->checkFieldValidity($field,@$request[$this->procFieldNamesIndex][$field]);
					}
			}
		}

		$this->procFieldNamesIndex++;
		return $processFormArr;
	}

	/**
	 * Returns all qualifying, reserved field names of field actions
	 * @return array
	 *
	 */
	function getFormActionFields(){
		$actions='Save,save_as_draft,Delete,Update';
		return explode(',',$actions);
	}

	function formDeleteAction($request){

		$resOutput=array();
		foreach($request as $key=>$val){
			$resOutput[]=$this->deleteDbRec($key,$val);

		}
		echo "<p>DEBUG ".__FUNCTION__."</p>";

		return implode('<br/>',$resOutput);
	}

	/**
	 * @param $table
	 * @param $id
	 * @param $hardDelete bool - TODO: Will actually delete the record instead of marking delete
	 * @return bool
	 *
	 * Deletes in this system are NON DESTRUCTIVE, in other words set to status='X' instead of delete record outright
	 */
	function deleteDbRec($table,$id,$hardDelete=false){

		$query="UPDATE $table SET status='X' WHERE id=?";
		return $this->dbExecute($this->mainDbConn,$query,array(0=>$id));
	}

	/**
	 * This persists valid form submissions by "form group" -- either it's one table or a collection of nested arrays
	 * It needs to insert all children, get those IDs, then persist the parent.
	 *
	 * @param $formGroupArr
	 * @param $formEntity
	 * @param $optionsArr  - If it's 'draft' will set the status of records accordingly
	 * @return mixed    -   return 'result'=>'success' array to make sure successful insert doesn't show form again, but goes to confirmation msg or flash
	 */
	function formSaveAction($formGroupArr,$formEntity,$optionsArr=array()){

		echo "<br/>".__FUNCTION__.":";
		echo "formGroupArr: <pre>".print_r($formGroupArr,true)."</pre>"; exit;

		//TODO: jsonCollectionFields must strip "json_" prepended to fields and map to the json collection field

		$saveStatus='A';
		if(@$optionsArr['saveType']=='draft'){  $saveStatus='D';}

		$baseTableName=$formEntity->getEntityTableName();

		$collName=@$optionsArr['collection'];
		// All collections need to pass into the parent as field name = table_name then appended with '_collection'
		if(!empty($collName)) {

			//$tableName=$formEntity->getEntityTableName();

			$tableName=str_ireplace('_collection','',$collName);
			echo "<h3>COLLECTION FOUND IN FORM: $collName, table: $tableName.</h3>";
			$inputWidgetsArr=$formEntity->getFormCollectionArr();    // Get the field map for this form

			$insertId=$this->insertRec($tableName,$formGroupArr['data']);

			// increments the index in case there are the same names of collections/ fields in the form -- can't use assoc. array
			$this->associatedTableIdsArr[]=$tableName."_id=".$insertId;
			return $insertId;

		}

		$inputWidgetsArr=$formEntity->setFormInputWidgets();

		$collKeysArr=$this->getCollectionKeys($inputWidgetsArr);
		if(count($collKeysArr)>0) {
			/*		echo "DEBUG ".__FUNCTION__.": ".print_r($inputWidgetsArr,true).
					"<hr/>Coll Keys Array: ".print_r($collKeysArr,true); exit;//.print_r($inputWidgetsArr,true); exit;
			*/
			$this->processCollections($collName,$collKeysArr,$formGroupArr);
		}

		// Now cycle through the form's base entity and be aware of the collections and the IDs that were inserted and pass those IDs on through submittedFormArr
		$finInsertArr=array();
		foreach($inputWidgetsArr as $widgetArr){

			if(array_key_exists('inputType',$widgetArr)){

				$inputType=$widgetArr['inputType'];
				if($inputType!='collection' && $inputType!='submit')    {
					$finInsertArr[$widgetArr['fieldName']]=$formGroupArr['data'][$widgetArr['fieldName']];
				}
			}
		}

		$insertId=$this->insertRec($baseTableName,$finInsertArr);

		//  $v=$this->validateSubmitAction($request,$formEntity);
		// Ideally, sending data to this method would return each table_name and corresponding data along with relationship IDs of FKs
		//  $v should return:  ['table_name']['data']=>assoc array [field_name]=val, [field_name2]=val2...
		/*
		//TODO: This requires recursion for nested arrays
		$dataArr=$formGroupArr['data'];
		foreach($dataArr as $table){

			if(isset($table['data'])) $dataArr=$table['data'];

			if(is_array(@$dataArr)){
				// For reference of subsequent related records to pass on foreign keys
				$this->idIndexArr[$table['table_name']]=$this->insertRec($table['table_name'],$dataArr);
			}

		}
		*/

		echo "<p>DEBUG ".__FUNCTION__.": INSERT OF BASE ENTITY, collection: $collName; saveStatus=$saveStatus:<br/>
				formGroupArr=<pre>".print_r($formGroupArr,true)."</pre></p>";

		//TODO: Figure out rest of flow from here
		return array('result'=>'success','insertId'=>$insertId);
	}


	function getCollectionKeys($inputWidgetsArr){

		// Find any collections for this form first, persist those, get each ID of each, then persist parent
		$collKeysArr=array();
		foreach($inputWidgetsArr as $widgetArr){

			if(array_key_exists('inputType',$widgetArr)){

				$inputType=$widgetArr['inputType'];
				if($inputType=='collection')    $collKeysArr[]=$widgetArr['fieldName'];
			}
		}

		return $collKeysArr;
	}


	function processCollections($collName,$collKeysArr,$formGroupArr){
		// Some collections found, they have to process first recursively
		// echo '<br/><hr/>inputWidgetsArr: '.print_r($inputWidgetsArr,true);
		// echo "<br/>DEBUG ".print_r($collKeysArr,true); exit;

		if(count($collKeysArr)>0){

			foreach($collKeysArr as $key=>$val){

				if(!isset($inputWidgetsArr[$key]['fieldName' ])) continue;

				$fieldName= $inputWidgetsArr[$key]['fieldName' ];
				if(!empty($fieldName)){

					echo "<br/>Collection found: $fieldName... "; // .print_r($collKeysArr,true)."<br/>formGroupArr[$fieldName]=".print_r($formGroupArr[ $fieldName],true); exit;

					require_once($this->appRootPath.'/main/views/viewBaseForm.php');
					if(!is_object(@$viewBaseForm)) $viewBaseForm=new viewBaseForm($this->userDataArr);

					// Get this collection's form entity, since it's nested
					$collFormEntity=$viewBaseForm->getEntityForm($inputWidgetsArr[$key]['collectionEntityName']);

					if(!array_key_exists($fieldName,$formGroupArr)){
						echo "<p>Key '$fieldName' does not exist in collection ($collName) formGroupArr, <pre>".print_r($formGroupArr,true)."</pre></p>";
						//exit;
					}

					$collectionResult=$this->formSaveAction(
						$formGroupArr['data'][$fieldName],
						$collFormEntity,array('collection'=>$fieldName)
					);
					$this->submittedFormArr[$fieldName]=@$collectionResult['insertId'];
				}
			}
		}
	}

	function extractFormCollections(){

	}


	function insertRec($tableName,$dataArr){

		/**
		 *  $stmt = $dbh->prepare("INSERT INTO REGISTRY (name, value) VALUES (:name, :value)");
			$stmt->bindParam(':name', $name);
			$stmt->bindParam(':value', $value);

			// insert one row
			$name = 'one';
			$value = 1;
			$stmt->execute();
		 */
		$qph=$fieldListArr=$vals=array();   // $qph is Query PlaceHolder
		foreach($dataArr as $key=>$val){
			$fieldListArr[]=$key;
			$qph[]=':'.$key;
			$vals[]=$val;
		}

		$fieldsList=implode(',',$fieldListArr);
		$phList=implode(',',$qph);

		$query="INSERT INTO $tableName ($fieldsList) VALUES ($phList)";

		echo "<p>DEBUG insertRec: $query</p>"; return 'id-test-999';

		$stmt = $this->mainDbConn->prepare($query);

		$index=0;
		foreach($vals as $val){
			$stmt->bindParam(':'.$fieldListArr[$index], $val);
			$index++;
		}

		return $stmt->execute();
	}


	function formUpdateAction($request,$formEntity,$optionsArr){

		echo "<p>DEBUG ".__FUNCTION__."</p>";
	}

	/**
	 * @param $request
	 * @return bool|int|string
	 *
	 * From posted form, this figures out which action it gets routed to persist/update database
	 *
	 */
	function determineActionFromForm($request){

		$actionKeys=array('save','save_as_draft','update','delete');
		foreach($request as $key=>$val){
			if(in_array(strtolower($key),$actionKeys)) {  return strtolower($key);}
		}
		return false;
	}

	// Use form's setFormInputWidgets() to map the fields and their nested arrays
	function processFieldOrig($field){

		if($collName){

			foreach($field as $key=>$val){
				$this->collectionFormInputWidgets[$collName][$key]=$val;
				echo "<br/>COLL($collName[$key]) ".print_r($val,true);
			}
		}

		if(is_array($field)) {
			foreach($field as $key=>$val)  {break;}
			return $this->processField($field,$key);
		}

		$this->collectionFormInputWidgets[]=$field;

	}



	function getEntityForm($entityName=null){

		if(empty($entityName)) return false;

		$entityFormName=$entityName."Form";
		if(!class_exists($entityFormName)) {
			$path=$_SERVER['DOCUMENT_ROOT'].'/library/main/views/'.$entityName.'/form.php';
			require_once($path);
		}
		return new $entityFormName($this->userDataArr);
	}




	/**
	 * @param       $myArray
	 * @param int   $maxDepth
	 * @param int   $depth
	 * @param array $arrayKeys
	 * @return array
	 *
	 * This returns ALL keys of array containers of a multi-dimensional array to establish the mapping and retrieval
	 *
	 */
	function array_keys_recursive($myArray, $maxDepth= 5, $depth = 0, $arrayKeys = array()){

		if($depth < $maxDepth){
			$depth++;
			$keys = array_keys($myArray);
			foreach($keys as $key){
				if(is_array($myArray[$key])){
					$arrayKeys[$key] = $this->array_keys_recursive($myArray[$key], $maxDepth, $depth);
				}
			}
		}

		return $arrayKeys;
	}


	function flattenCollection($requestArr){

		$finArr=array();
		//echo "DEBUG flattenCollection: ".print_r($requestArr,true); exit;
		foreach($requestArr as $key=>$val){

			if(is_array($val)){
				foreach($val as $key2=>$val2){

					if(is_array($val2)){
						foreach($val2 as $key3=>$val3){
							$finArr[$key3]=$val3;
						}
					}
					else {
							$finArr[$key2]=$val2;
					}
				}
			}
			else {
					$finArr[$key]=$val;
			}
			echo "<br/>FLATTEN ".print_r($finArr,true)."...";
		}

		return $finArr;
	}

}