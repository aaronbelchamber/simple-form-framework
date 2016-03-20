<?php
/**
 *      $formPath           -       If defined, this is the override (default:'')
 *      $formInputWidgets   -       { 0:{'fieldName':'','inputType':'textarea,select','options':'{}' ... }
 *      $basicFormat        -       default - 1 line per field - labels left, labels top (default)
     *                              2 columns - labels left, labels top
     *                              3 columns - labels left, labels top
     *                              Best fit float - custom line break class defined
 *
 *      $isAjax             -       If it's an AJAX post, requires $ajaxHandlerUrl
 *          $ajaxHandlerUrl -       The URL of the AJAX handler, responses for AJAX handlers are CONFORMED, like "success:redirect:URL"
 *
 */

class viewBaseForm{

    protected $counter=0;

    public $debug=false;

	protected $dbName='main';

	protected $appRootPath;

	protected $adminBaseController;

    protected $fieldIndex=0;    // This provides an increment for each form field added in the order top left to bottom right

    protected $collectionFormInputWidgets=array();

	protected $formValsArr=array();

	protected $collIndex=array();

    protected $userDataArr;

    protected $lineBreakHtml='<div class="clearfix" style="clear:both"></div>';

    // Helpful for field alignment that has a "$" in front of field
    protected $hasPreFieldSymbols=false;    // If one field has a prefield symbol, need to show the placeholder for all

    protected $jsLibrariesArr=array();  // Will automatcially inject JS libraries only as needed

    protected $jqueryUi=0;  // If incremented at least once, be sure to call UI lib


    public function __construct($userDataArr){

        if(count($userDataArr)==0){ return false;}
        $this->userDataArr=$userDataArr;
		$this->appRootPath=APP_ROOT_PATH;

    }


	public function getAdminBaseController(){
		include_once($this->appRootPath.'/main/controllers/adminBaseController.php');
		return new adminBaseController();
	}


	public function setFormInputWidgets($formVals=null){

		// When element 'label' is set to just true boolean, then show the field name in a display-friendly way
		/*
		 * `id`, `owner_user_id`, `contact_info_id`, `title`, `property_subtitle`,
		 * `property_desc_body`, `listing_manager_user_id`, `listing_company_user_id`, `custom_data_json`, `status`
		 *
		 */
		$this->formValsArr=$formVals;   // This passes to the parent method of addFormField() so any defined values pass in through there

		if(isset($formVals['id'])) {
			$w=array(); // $w is "Widgets"
			$w['fieldName']='id';  $w['inputType']='hidden';
			$this->addFormField($w);
		}

	}


	/**
     * @param null $request
     * @param $formEntity
     * @param null $subview
     * @param array $optionsArr
     * @return mixed
     *
     * The main method to show the page and the form it contains
     *
     */

    public function displayFormContent($request=null,$formEntity,$subview=null, $optionsArr=array()){
        /**
         * Placeholders:  __TITLE__, __FIRST_HEADING__, and __BODY_CONTENT__
         *
         */
        $pageBaseTemplate=file_get_contents($_SERVER['DOCUMENT_ROOT'].'/library/main/views/Base/pageBaseTemplate.php');

        $title='TESTING';
        $h1='FORM TESTING';

        $formInputWidgets=$formEntity->setFormInputWidgets();

        //  echo __FUNCTION__;  print_r($formInputWidgets); exit;
        $body=$formEntity->showForm('',$formInputWidgets,$formEntity,$subview,$optionsArr);

        $body=$this->processBodyHtml($body,$optionsArr);

        $content=str_ireplace('__TITLE__',$title,$pageBaseTemplate);
        $content=str_ireplace('__FIRST_HEADING__',$h1,$content);
        $content=str_ireplace('__BODY_CONTENT__',$body,$content);

        return $content;
    }


    // Defines the globals of form -- after submit what to do upon success or failure, etc
    /**
     * @param $responseArr - MUST have a 'result' index returned at minimum and all other info needed to
     *                       get associations for rest of data that needs to be displayed
     * @return mixed
     *
     */
    public function defineFormParams($responseArr){

        // on success, on failure...
        if($responseArr['result']=='success'){  return $this->formSuccessResponse($responseArr); }
        return $this->formFailureResponse($responseArr);

    }



    // Pass in data-view and -subview for encapsulation and unique selections in DOM
    /**
     * @param $formPath
     * @param $formInputWidgets
     * @param $formEntity
     * @param string $subview
     * @param array $optionsArr     'alert' passes on all warnings/alerts
     * @return string
     *
     */
    public function showForm($formPath,$formInputWidgets,$formEntity,$subview='form',$optionsArr=array()){

        $view=$formEntity->getFormEntityName();

        ob_start();

        // This allows more control of the form container's look
        $showFormHeader='';
        if(@$optionsArr['formHeader']){
            $showFormHeader='<h3 style="text-align:center" class="form-header">'.$optionsArr['formHeader'].'</h3>';
        }

        $extraClasses=@$optionsArr['formContainerClass'];
        if(@$optionsArr['alerts']){
            echo '<div class="text-alert">'.implode("<br/>",$optionsArr['alerts']).'</div>';
        }
        ?>
    <div class="form-container <?php echo $extraClasses?>" data-view="<?php echo $view?>" data-subview="<?php echo $subview?>">
        <?php echo $showFormHeader ?>
    <form action="<?php echo $formPath?>" method="POST" data-details="<?php echo $view.":".$subview?>">
        <?php
        foreach( $formInputWidgets as $inputWidget){
            echo $this->showInputTypeHtml($inputWidget);
        }
	    echo '<input type="hidden" name="formEntity" value="'.$view.'"/>';
        echo '</form></div>';

        echo $this->buildJsLibrariesScript();
        $html= ob_get_clean();
        return $html;
    }


	function setFormSubmitMap(){

		$inputWidgetsArr=$this->setFormInputWidgets();

		$finMapArr=array();
		foreach($inputWidgetsArr as $widget){
			if(!isset($widget['fieldName'])) continue;

			if($widget['inputType']=='collection'){
				// Need to instantiate the collection entity to get the formInputWidgets here

				$finMapArr[$widget['fieldName']]=str_ireplace('_collection','_id',$widget['fieldName']  );
				//TODO: get collection's form entity and fill its setFormSubmitMap array here $this->$widget['value'];
			}
			else {
					$finMapArr[$widget['fieldName']]=$widget['value'];
			}
		}

		return $finMapArr;
	}

    /*  $formStyle:
         *    2 columns - labels left, labels top
              3 columns - labels left, labels top
              Best fit float - custom line break class defined
         */

    function showInputTypeHtml($inputWidget,$formStyle='layout:default,labels:default'){

        if($this->debug) echo "<br/>".__FUNCTION__.';'.print_r($inputWidget,true)."<br/>";

        $htmlArr=$attrArr=array();   // implode('') this array as HTML output
	    if(!empty($inputWidget['jsonCollectionField']) )  @$inputWidget['fieldName']='json_'.@$inputWidget['fieldName'];

	    $name=@$inputWidget['fieldName'];

	    if(@$inputWidget['heading']){
            $htmlArr[]= $this->wrapHeader($inputWidget['heading']);
            if(!$name) { return implode('',$htmlArr);}
        }

        if(@$inputWidget['html']){
            $htmlArr[]= $this->wrapHeader($inputWidget['html']);
            if(!$name) { return implode('',$htmlArr);}
        }

        if(empty($name)) {return false;}

        $type=$inputWidget['inputType'];
        $formDefaultsArr=$this->getFormDefaults();

        if(@$formDefaultsArr[$type]['class'])  $class=' class="'.$formDefaultsArr[$type]['class'].'" ';
        if(@$formDefaultsArr[$type]['style'])  $style=  $style=' style="'.$formDefaultsArr[$type]['style'].'" ';

        $nameAttr=" name='$name'";

        if($type!='hidden') {
            // Hidden fields don't get counted
            $this->counter++;   // To help with layouts of form to know when to break elems, etc
        }

        //TODO: html-editing class will trigger the use of Tiny MCE Editor
        $assignInputClass=$this->assignJsLibraries($type,$name);
        if($assignInputClass) @$inputWidget['class'].=" $assignInputClass";

        if (isset($inputWidget['class'])) $class=' class="'.$inputWidget['class'].'" ';
        if (isset($inputWidget['style'])) $style=' style="'.$inputWidget['style'].'" ';

        if (isset($inputWidget['attr'])){
            foreach ($inputWidget['attr'] as $key=>$val){
                $attrArr[]=$key.'="'.$val.'"';
            }
        }

        if($type=='ajax widget'){

            /*
             *  $w['fieldName']='available_contact_info_ids';
                $w['inputType']='ajax widget';  // or 'ajax popup tool'?
                $w['ajaxWidget']='available_contacts';
                $w['ajaxApiUrl']='';
                $w['ajaxValuesToSend']=array('user_unique_url'=>$this->userDataArr['unique_url'],'contactTypes'=>'propertyOwners');
             */
            $ajaxWidget=@$inputWidget['ajaxWidget'];
            if(!$ajaxWidget){  echo '<p>Error - Need ajaxWidget defined with inputType ajax widget</p>'; return false;}

            // Output the HTML for a visible icon that will be the listener and trigger, based on the ajaxWidget
            $htmlArr[]=$this->getAjaxWidgetHtml($inputWidget);

        }

        $attr=implode(' ',$attrArr);
        $mainAttrs=$nameAttr.@$class.@$style.$attr;

        if (isset($inputWidget['value'])) {$val=$inputWidget['value'];}
        else {$val=@$inputWidget['data'];}

        $preseparatorHtml=@$inputWidget['preSeperatorHtml'];  // Defining HTML BEFORE any form displayables (wherever a label shows)

        if(@$inputWidget['label']===false) {$labelPrepend='';}
        else {
                if(@$inputWidget['labelText']) {$labelText=$inputWidget['labelText'];}
                    else {
		                    $labelText=str_ireplace('_',' ',$name);
		                        if(stristr($labelText,']')){
			                        if(substr($labelText,-1,1)==']')    $labelText=substr($labelText,0,strlen($labelText)-1);

			                        $labelTextArr=explode(']',$labelText);
			                        $lastFieldNode=array_pop($labelTextArr);
			                        if(!empty($lastFieldNode)){$labelText=str_ireplace('[','',$lastFieldNode);}
		                        }
	                        $labelText=ucwords(str_ireplace('_',' ',$labelText)    );
                    }

                $labelClass=@$inputWidget['labelClass'];
                if(!empty($labelClass)) {$labelClass=' class="'.$labelClass.'"';}

                $labelStyle=@$inputWidget['labelStyle'];
                if(!empty($labelStyle)) {$labelStyle=' style="'.$labelStyle.'"';}

                $labelText='<label '.$labelStyle.$labelClass.' for="'.$name.'">'.$labelText.'</label>';  //TODO: Add label attr & class to this tag
                if(!empty($labels) && $labels=='top' )  {$labelPrepend=$labelText."<br/>";}
                else {    $labelPrepend=$labelText.' ';}
        }

        $labelPrepend=$preseparatorHtml.$labelPrepend;

        $preFieldSymbol=@$inputWidget['preFieldSymbol'];
        if(!empty($preFieldSymbol)){
            $labelPrepend.="<div class='preFieldSymbol'>$preFieldSymbol</div>";
        }

        if ($type=='hidden')     {
            $htmlArr[]=  '<input type="hidden" value="'.$val.'" '.$mainAttrs.'/>';
            return implode('',$htmlArr);    //Since it's hidden, no need to do anything else
        }

        $textAreaBody='<textarea '.$mainAttrs.'>'.$val.'</textarea>';

	    // Any HTML that goes at the START of this widget
	    if(@$inputWidget['preHtml'])  $htmlArr[]= $inputWidget['preHtml'];

        if ($type=='textarea')  $htmlArr[]= $labelPrepend.$textAreaBody;
        if ($type=='html')      $htmlArr[]= $labelPrepend.'<div class="html-editor-container">'.$textAreaBody.'</div>';
        if ($type=='text')      $htmlArr[]= $labelPrepend.'<input '.$mainAttrs.' value="'.$val.'"/>';

        // Datepicker directives like minDate, maxDate, etc pass to the library creation at buildJsLibrariesScript

        if ($type=='date')      $htmlArr[]= $labelPrepend.'<input '.$mainAttrs.' value="'.$val.'" class="datepicker "/>';

        if ($type=='submit')    $htmlArr[]=  $labelPrepend.'<input '.$mainAttrs.' type="submit" value="'.$val.'"/>';
        if ($type=='button')    $htmlArr[]=  $labelPrepend.'<input '.$mainAttrs.' type="button" value="'.$val.'" />';

	    /*
	     *  $w['fieldName']='custom_data_json';
		    $w['inputType']='google_map';
		    $w['jsonCollectionField']='latitude_longitude';
	     */

	    if($type=='google_map'){
		    $html=$this->jSLibraryGoogleMap();
			$html.=$labelPrepend.
					'<fieldset class="gllpLatlonPicker">
						<input type="text" class="gllpSearchField" name="json_map_search">
						<input type="button" class="gllpSearchButton" value="search">
						<br/><br/>
						<div class="gllpMap"></div>
						<br/>
						Latitude/Longitude:
							<input type="text" class="gllpLatitude"  name="json_latitude"/>
							/
							<input type="text" class="gllpLongitude"  name="json_longitude"/>
						<input type="hidden" class="gllpZoom" value="2" name="json_zoom"/>
					</fieldset>';
		    $htmlArr[]=$html;
	    }

        //var_dump($optionsArr); exit;
        if($type=='choice'){

            $optionsArr=@$inputWidget['options'];
            $html=$labelPrepend.'<select '.$mainAttrs.'>';

            $valuesArr=$optionsArr['values'];
            $showValuesArr=$optionsArr['showValues'];

            for($i=0;$i<count($valuesArr); $i++){
                $sel='';
                if($val==@$valuesArr[$i]) $sel='selected="selected"';
                $html.='<option value="'.$valuesArr[$i].'" '.$sel.'>'.$showValuesArr[$i].'</option>';
            }
            $html.='</select>';
            $htmlArr[]=  $html;
        }

	    // Class .loadHidden can be assigned to the collection DIV containers
        if($type=='collection'){

            $htmlArr=array();
            $collectionName=$inputWidget['fieldName'];
	        if(!@$this->collIndex[$collectionName]) { $this->collIndex[$collectionName]=0;}

	        $collIndex=$this->collIndex[$collectionName];
            if(@$inputWidget['collectionHeader']) $htmlArr[]=$this->wrapHeader($inputWidget['collectionHeader']);

            foreach(@$inputWidget['fields'] as $field){
                $field['fieldName']=$collectionName."[$collIndex][".$field['fieldName']."]";
                $htmlArr[]=$this->showInputTypeHtml($field);

            }

	        // This ensures the field index count for a collection's field group increments by one
	        $this->collIndex[$collectionName]++;
            if(count($htmlArr)==0){ return false;}
            if(@!$inputWidget['fieldGroup'])   $inputWidget['fieldGroup']='collection-'.$collectionName;
        }

        $layoutArr=$this->convertFormStyle($formStyle);
        $labels=$layoutArr['labels'];   // left or top?

        if(count($htmlArr)==0) { return false;}

        $lineBreakHtml=$this->lineBreakHtml;
        if(empty($layoutArr) || $layoutArr['layout']=='default') {
            // Standard layouts' custom HTML
            $htmlArr[]= $lineBreakHtml;
        }
        else {
                $layout=$layoutArr['layout'];
                if (stristr($layout,'2 col')){
                    if($this->counter%2){
                        $htmlArr[]= $lineBreakHtml;
                    }
                }
                if (stristr($layout,'3 col')){
                    if($this->counter%3){
                        $htmlArr[]= $lineBreakHtml;
                    }
                }
        }

		// Any HTML that goes at the END of this widget
        if(@$inputWidget['postHtml'])  $htmlArr[]= $inputWidget['postHtml'];

	    // Should any behaviors require an ID if it's not a collection or go by fieldName for selector?
	    $htmlArr[]=$behavior=$this->processBehaviors($inputWidget);

	    //  echo "<hr/><p>DEBUG behavior:".htmlentities($behavior)."</p>";

	    $html=implode("",$htmlArr);
        if(@$inputWidget['fieldGroup']){

            $fieldGroupClass=@$inputWidget['fieldGroupClass'];
            $fieldGroupStyle=@$inputWidget['fieldGroupStyle'];

            if(!empty($fieldGroupStyle)){$fieldGroupStyle=' style="'.$fieldGroupStyle.'"';}
            return '<div class="form-group '.$fieldGroupClass.'" '.$fieldGroupStyle.' data-field-group="'.$inputWidget['fieldGroup'].'">'.$html.'</div>';
        }
        else {
                 if ($type=='collection'){  return $html;}
                    else {
	                        $fieldElementClass=@$inputWidget['fieldElementClass'];
	                        if(!empty($fieldElementClass)){  $fieldElementClass=' '.$fieldElementClass;}
		                    $fieldElementStyle=@$inputWidget['fieldElementStyle'];
		                    if(!empty($fieldElementStyle)){  $fieldElementStyle=' style="'.$fieldElementStyle.'"';}
	                        return '<div class="form-elem'.$fieldElementClass.$fieldElementStyle.'">'.$html.'</div>';
                    }
        }
    }


    function convertFormStyle($formStyle=null){

        // form styles are defined by key:value pairs like "layout:default,labels:default"
        // "default" layout means it builds from top to bottom and labels are to the left of the field each label field on one row
        if (empty($formStyle)) return false;

        $layoutArr=explode(',',$formStyle);
        $finArr=array();
        foreach($layoutArr as $layoutElem){
            $l=explode(':',$layoutElem);
            $finArr[$l[0]]=@$l[1];   // Turns layout:default into ASSOC array
        }

        return $finArr;
    }


    function getAjaxWidgetHtml($w){

        $ajaxWidget=$w['ajaxWidget'];
        $htmlArr=array();
	    $triggerText=@$w['triggerText'];

        if($ajaxWidget=='available_contacts'){
            $htmlArr[]='<div class="widget '.$ajaxWidget.'" data-field-name="'.$w['fieldName'].'">'.$triggerText.'</div>';
        }
        ob_start();
        ?>
        <script type="text/javascript">
          var actionDiv="div[data-field-name='<?php echo $w['fieldName']?>']";
          $(actionDiv).on('click',function(){
	            elem=$(this);
	            elem.unbind('click');
	            elem.css('cursor','default');
                $.post("<?php echo @$w['ajaxApiUrl']?>",function(data){
                      $(actionDiv).html(data);
                })
           });
        </script><?php
        return implode('',$htmlArr).ob_get_clean();
    }

    /**
     * @param $body
     * @return mixed
     *
     * Analyzes $body HTML and adds datepicker automatically, etc
     *
     * //TODO: Expand this function to see if there's a datepicker so it includes the JQuery UI and adds datepicker to HTML, etc
     */
    public function processBodyHtml($body,$optionsArr){
        // Special $optionsArr directives:  load_datepicker=false - won't detect and automatically initialize datepicker fields
        if(!@$optionsArr['load_datepicker']===false){
            //TODO: Scan body HTML for presence of fields mentioning "date" or explicity has .datepicker class

        }
        return $body;
    }

    /**
     * @param $fieldName - Needs to be fully addressable field name since can have the same field name in nested arrays
     * @param $val
     *
     * Depending on form entity, key and value will return different validation and clean the values
     *
     * This can be overridden by individual entity's form
     *
     */
    public function cleanAndValidate($fieldName,$val){

        // Based on $formEntity validation rules, process the value and return
        /**
         * "fieldName":{"value":"cleaned value","valid":true/false,
         *                                      "errors":"none" or "List of errors"}
         *
         * Validation directives like
         *      "integer only", "text only", "date only", "email only"
         *      "max length", "min length"
         *      "on invalid" - "reject show warning", "reject no warning", "sanitize and continue"
         *
         */
	    $processFieldArr=$this->processField($fieldName,$val);
	    $isValid=$processFieldArr['valid'];
	    if($isValid)  return array("value"=>filter_var($val,FILTER_SANITIZE_SPECIAL_CHARS),"valid"=>true);

	    $error=$processFieldArr['error_msg'];
	    return array("value"=>filter_var($val,FILTER_SANITIZE_SPECIAL_CHARS),"valid"=>false, "error_msg"=>$error);
    }

	/**
	 * Takes field name, value and validation rule and returns 'valid' and 'error_msg' and based on action if will reject or just sanitize and continue
	 *
	 * Called from formBaseController:processFieldNodes() and processFieldNodes can be specific to each entity/table
	 *
	 * @param $fieldName
	 * @param $val
	 * @param $validationRuleArr
	 * @return array    - Return a multi-element array to the field  so it's ['fieldName']=['valid']=1,['value']='value'
	 */
	public function checkFieldValidity($fieldName,$val){

		$processFieldArr['valid']=true;
		$processFieldArr['value']=$val;

		$validationRuleArr=$this->validationRules($fieldName);
		if(isset($validationRuleArr[$fieldName])){

		}

		return $processFieldArr;
	}

	// Based on each form field name, defines format restriction, violation check and action to take if violated
	/**
	 * @param $fieldName
	 * @param $val
	 * @return mixed    Returns a value with the matching field name as key and the validation rules assigned to each element
	 *
	 */
	public function validationRules($fieldName){

		// Make sure forms inherit this then override
		$intText='type:int;action:sanitize only';

		$intSanitizeArr=array('type'=>'int','action'=>'sanitize only');
		$textSanitizeArr=array('type'=>'text','action'=>'sanitize only');

		// If text length limit has a min or max:
		if($fieldName=='title'){
			$textSanitizeArr['action']='sanitize and notify';
			$textSanitizeArr['conditions']=
					array(  'minWidth'=>array('value'=>10,'error_msg'=>'__FIELDLABEL__ must be at least 10 characters'),
							'maxWidth'=>array('value'=>45,'error_msg'=>'__FIELDLABEL__ cannot be more than 45 characters'   )
						 );
			$rulesArr[$fieldName]=$textSanitizeArr;
		}

		$rulesArr['id']=$intSanitizeArr; // action can be sanitize only, sanitize and notify, or notify which will pass to violation action or reject
		if(substr($fieldName,-3,3)=='_id') $rulesArr[$fieldName]=$intSanitizeArr;

		return $rulesArr;
	}

    /**
     * @param $inputType
     * @param $class
     *
     * Returns the name of the class to assign this form input, if applicable
     *
     * Using the class's jsLibrariesArr will make sure the library loads and sets up the proper trigger
     *  and assign it to the field name as a listener like "textarea[name*='$inputFieldName']"
     *
     *  Some libraries, like html-editor needs to only be loaded once with selection of class ".html-editor"
     *
     */
    function assignJsLibraries($inputType,$inputFieldName){

        if($inputType=='html' && !in_array($inputType,$this->jsLibrariesArr)   ){
            $this->jsLibrariesArr[]=array('html'=>$inputFieldName);
            return 'html-editor';
        }

        if($inputType=='date') {$this->jqueryUi++; return 'datepicker';}

        return false;
    }


    function buildJsLibrariesScript(){

        $htmlArr=array();
        foreach($this->jsLibrariesArr as $key=>$val){
            if($key=='html') {  $htmlArr[]=$this->jsLibraryHtmlEditor();}
        }

        if($this->jqueryUi>0){ $htmlArr[]=$this->jsLibraryJqueryUi();}
        return implode("",$htmlArr);
    }


    function jsLibraryHtmlEditor(){

        return "<script src='//cdn.tinymce.com/4/tinymce.min.js'></script>
                <script>tinymce.init({ selector:'.html-editor' });</script>";
    }


    function jsLibraryJqueryUi(){
        return '<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
                <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
                <script>$(".datepicker").datepicker();</script>
                ';
	    /*
	     * <fieldset class=”gllpLatlonPicker”>
				<input type=”text” class=”gllpSearchField”>
				<input type=”button” class=”gllpSearchButton” value=”search”>
				<br/>
				<div class=”gllpMap”>Google Maps</div>
				lat/lon: <input type=”text” class=”gllpLatitude” value=”20″/> / <input type=”text” class=”gllpLongitude” value=”20″/>, zoom: <input type=”text” class=”gllpZoom” value=”3″/> <input type=”button” class=”gllpUpdateButton” value=”update map”>
			</fieldset>
	     */
    }


    function jsLibraryDropzone(){
        return '<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/dropzone/4.2.0/min/basic.min.css">
                <script src="//cdnjs.cloudflare.com/ajax/libs/dropzone/4.2.0/min/dropzone-amd-module.min.js"></script>
                <script src="//cdnjs.cloudflare.com/ajax/libs/dropzone/4.2.0/min/dropzone.min.js"></script>
                ';
    }


	function jSLibraryGoogleMap(){
		return '<script src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
				<link rel="stylesheet" type="text/css" href="/library/vendors/jquery/jquery-latitude-longitude-picker-gmaps/css/jquery-gmaps-latlon-picker.css"/>
				<script src="/library/vendors/jquery/jquery-latitude-longitude-picker-gmaps/js/jquery-gmaps-latlon-picker.js"></script>';
	}


    function handleUploads(){
        /**
            http://www.dropzonejs.com/#server-side-implementation

         <form action="/file-upload" class="dropzone">
            <div class="fallback">
            <input name="file" type="file" multiple />
            </div>
        </form>

        <form action="/file-upload"
        class="dropzone"
        id="my-awesome-dropzone"></form>

        <form action="/file-upload" class="dropzone">
        <div class="fallback">
        <input name="file" type="file" multiple />
        </div>
        </form>

            <form action="" method="post" enctype="multipart/form-data">

            <!-- MAX_FILE_SIZE must precede the file input field -->
            <input type="hidden" name="MAX_FILE_SIZE" value="30000" />
            <!-- Name of input element determines name in $_FILES array -->
            <p>Pictures:

            <input type="file" name="pictures[]" />
            <input type="file" name="pictures[]" />
            <input type="file" name="pictures[]" />
            <input type="submit" value="Send" />
            </p>
            </form>

            <?php
            foreach ($_FILES["pictures"]["error"] as $key => $error) {
                if ($error == UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES["pictures"]["tmp_name"][$key];
                    $name = $_FILES["pictures"]["name"][$key];
                    move_uploaded_file($tmp_name, "data/$name");
                }
            }
            ?>
        */
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


    function addFormField($fieldArr){

	    if(isset($this->formValsArr[$this->fieldIndex])) {$fieldArr['value']=$this->formValsArr[$this->fieldIndex];}
        $finArr[$this->fieldIndex]=$fieldArr;
        $this->fieldIndex++;

        $this->collectionFormInputWidgets+=$finArr;
    }


    function wrapHeader($hdrText){

        return '<h3 class="collection-header">'.$hdrText.'</h3>';
    }


    function getFormDefaults(){
        /**
         *  Basic template for each part of form, including labels and different inputTypes
         *
         *  To override these defaults, just include this method in its child following this pattern:
         *
         *  Some typical behaviors setting:  'load start:hidden,reveal:only on click->plus icon'
         */
        $f['text']['class']='col-md-8';
        $f['textarea']['class']='col-md-8';
        $f['choice']['class']='col-md-8';

	    //TODO: Make plusIcon CSS
        $f['behaviors']='load start:hidden,reveal:only on click->plusIcon';
        return $f;
    }

	/**
	 * @param $w        - The single $inputWidget
	 * @return string
	 *
	 * Creates special JS and CSS for the behaviors needed
	 *
	 */
	function processBehaviors($w){

		if(!isset($w['behaviors'])) return false;

        $wArr=explode(',',$w['behaviors']);

        $html=array();
        $js=array();

        // Each behavior requires a directive COLON then command
        foreach($wArr as $behavior){
            $behaviorArr=explode(':',$behavior);
            $directive=$behaviorArr[0];
            $command=$behaviorArr[1];

            if($directive=='load start'){
                if ($command=='hidden'){
	                // Form collections get hidden by the parent container
	                if($w['inputType']!='collection'){
		                ob_start();
		                ?>
	                    $(".fieldType[name*='<?php echo $w['fieldName']?>']").hide();
		                $("label[for*='<?php echo $w['fieldName']?>']").hide();
	                    <?php
		                $js[]=ob_get_clean();
	                }
                }
            }

            if($directive=='reveal'){
	            $html[]=$this->processBehaviorCommand($directive,$command,$w);
            }
        }

        if(count($js)>0){
            $html[]="<script type='text/javascript'>".implode('',$js)."</script>";
        }

        if(count($html)>0){
            return implode('',$html);
        }

    }

	function processBehaviorCommand($directive,$command,$w){

		$html=$js=array();
		$triggerText=@$w['triggerText'];

		// Check if command is complex like 'reveal:only on click->plusIcon' where will need to generate html plusIcon object
		if(stristr($command,'->')){
			$commandArr=explode('->',$command);

			// if $left has ' on ' then it's a listener
			$left=$commandArr[0];
			$right=$commandArr[1];

			$listener=null;

			// Check if it's a listener
			if(stristr($left,' on ')){
				$leftArr=explode(' on ',$left);

				foreach($leftArr as $wordCmd){
					if($wordCmd=='click'){ $listener='click';}
				}
			}

			$fieldSel=$this->setFieldSelectorFromWidget($w);

			//TODO Figure out best way for action to pass with JS returned from here
			if($directive=='reveal') {
				$action='$("'.$fieldSel.'").toggle();';
				//  $action.='$("label[for=\''.$w['fieldName'].'\'").toggle();';

				$js[]='$("'.$fieldSel.'").hide();';
			}

			if($listener=='click' && $action){

				if($right) {    echo '<div class="'.$right.'">'.$triggerText.'</div>';}

				ob_start();
				?>
				$(".<?php echo $right?>").on("click", function(){
						<?php echo $action ?>
					  });
                <?php
				$js[]=ob_get_clean();
			}
		}

		if(count($js)>0){   $html[]='<script type="text/javascript">'.implode('',$js).'</script>';}
		if(count($html)==0) { return false;}
		return implode('',$html);
	}


	function setFieldSelectorFromWidget($w){
		$inputType=@$w['inputType'];
		$fieldName=@$w['fieldName'];
		$collectionEntityName=@$w['collectionEntityName'];

		$formInputHtmlType='input';
		if($inputType=='collection'){ $formInputHtmlType="div[data-field-group='collection-".$w['fieldName']."']";}
		if($inputType=='textarea')  { $formInputHtmlType=$inputType;}
		if($inputType=='choice')    { $formInputHtmlType='select';}

		return $formInputHtmlType;
	}


	//TODO: Processes the form request then puts new, updates or deletes existing
	function processRequest($requestArr){

		// Based on value of button -- Save, Save as Draft, Update, or New or if it has the "id" keys passed through, we know it's an UPDATE and not an INSERT


	}


	// TODO:  Cleans and validates form request
	function validateRequest($requestArr){

	}


	function getCollection($collName){
		echo "<p>DEBUG ".__FUNCTION__.": collName: $collName</p>";
		return $this;
	}

	function getStateArr(){

		$jsonText='{"AL" : "Alabama", "AK" : "Alaska", "AZ" : "Arizona", "AR" : "Arkansas", "CA" : "California", "CO" : "Colorado", "CT" : "Connecticut", "DE" : "Delaware", "FL" : "Florida", "GA" : "Georgia", "HI" : "Hawaii", "ID" : "Idaho", "IL" : "Illinois", "IN" : "Indiana", "IA" : "Iowa", "KS" : "Kansas", "KY" : "Kentucky", "LA" : "Louisiana", "ME" : "Maine", "MD" : "Maryland", "MA" : "Massachusetts", "MI" : "Michigan", "MN" : "Minnesota", "MS" : "Mississippi", "MO" : "Missouri", "MT" : "Montana", "NE" : "Nebraska", "NV" : "Nevada", "NH" : "New Hampshire", "NJ" : "New Jersey", "NM" : "New Mexico", "NY" : "New York", "NC" : "North Carolina", "ND" : "North Dakota", "OH" : "Ohio", "OK" : "Oklahoma", "OR" : "Oregon", "PA" : "Pennsylvania", "RI" : "Rhode Island", "SC" : "South Carolina", "SD" : "South Dakota", "TN" : "Tennessee", "TX" : "Texas", "UT" : "Utah", "VT" : "Vermont", "VA" : "Virginia", "WA" : "Washington", "WV" : "West Virginia", "WI" : "Wisconsin", "WY" : "Wyoming"}';
		$jsonArr=json_decode($jsonText,true);

		return $jsonArr;
	}


	function getTableFields($table){

	$query="SELECT column_name, data_type,character_maximum_length
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_NAME = '".$table."' and TABLE_SCHEMA='$this->dbName'";
	}


	/**
	 * @param int $fieldIndex
	 */
	public function setFieldIndex($fieldIndex)
	{
		$this->fieldIndex = $fieldIndex;
	}

	/**
	 * @return int
	 */
	public function getFieldIndex()
	{
		return $this->fieldIndex;
	}



}