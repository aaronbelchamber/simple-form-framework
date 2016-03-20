<?php

class PropertyInfoForm extends viewBaseForm{



    public function setFormInputWidgets($formVals=null){

        parent::setFormInputWidgets($formVals);

        $w=array();
        $w['heading']='Property information';   // This explains a section of the form
        $this->addFormField($w);

        $w=array();
        $w['fieldName']='title';
        $w['inputType']='text';
        $w['label']=true;
        $w['class']='col-md-8';
        $w['style']='font-size:15pt';
        $this->addFormField($w);

        $w=array();
        $w['fieldName']='property_subtitle';
        $w['inputType']='text';
        $w['label']=true;
        $w['labelText']='Caption';
        $w['class']='col-md-8';
        $this->addFormField($w);

	    require_once(APP_ROOT_PATH.'/main/views/ContactInfo/form.php');
	    $contactInfoForm=new ContactInfoForm(@$this->userDataArr['unique_url']);

	    /* These work but dynamic for this is not needed
	    $w=array();
	    $w['header']='Main Property Address';
	    $w['fieldName']='available_contact_info_ids';
	    $w['inputType']='ajax widget';  // or 'ajax popup tool'?
	    $w['ajaxWidget']='available_contacts';
	    $w['ajaxApiUrl']='/library/main/controllers/test.php';
	    $w['ajaxValuesToSend']=array('user_unique_url'=>$this->userDataArr['unique_url'],'contactTypes'=>'propertyOwners');
	    $w['triggerText']="View your available addresses";
	    $w['behaviors']='load start:show';  // This will show the widget by default
	    $this->addFormField($w);

	    $w=array();
	    $w['fieldName']='contact_info_collection';
	    $w['inputType']='collection';
	    $w['collectionHeader']='New Property Address';  // This explains the sub form
	    $w['collectionEntityName']='ContactInfo';
	    $w['triggerText']="+ Add new "; // If has behaviors what goes in trigger (plusIcon) as HTML?
	    $w['behaviors']='load start:hidden,reveal:only on click->plusIcon';   // This means this collection will only show
	    $w['fields']=  $contactInfoForm->setFormInputWidgets();

	    $this->addFormField($w);
		*/

	    $w=array();
	    $w['fieldName']='contact_info_collection';
	    $w['inputType']='collection';
	    $w['collectionHeader']='Property Address';  // This explains the sub form
	    $w['collectionEntityName']='ContactInfo';   // This is the subfolder of the form view for this table (entity)
	    $w['fields']=  $contactInfoForm->setFormInputWidgets();
	    $this->addFormField($w);

        $w=array();
        $w['fieldName']='property_desc_body';
        $w['inputType']='html';
        $w['label']=true;
        $w['labelText']='Description';
        $w['class']='col-md-12';
        $this->addFormField($w);

        /*
        $opt['values']=
        $opt['showValues']=explode(',','Choose,For rent annual,For rent seasonally,For sale,Available for trade');
        $opt['validation']='not first'; // TODO: Wire this SELECT validation condition up
        $w=array('fieldName'=>'listing_type','inputType'=>'choice','label'=>true,'options'=>$opt );
        $this->addFormField($w);
        //TODO: Add different widgets that are composites, directives that need processing of the data

        //TODO: Check number of properties under this user and create select pulldown if 10 or less else make AJAX
        //      Like an AJAX call when picking the property this form applies to, how that interface looks & acts!

        // user_unique_url - Based on this User's ID the API will show available property info to list
        $this->addFormField(array('fieldName'=>'property_info_id',// Only allowed ONE else it'd be plural field name
                                  'inputType'=>'ajax popup tool',
                                  'ajaxApiUrl'=>'/local/path/to/API/call',
                                  'ajaxValuesToSend'=>'user_unique_url='.$this->userDataArr['unique_url']
            )
        );
        */

	    $w=array();
	    $w['fieldName']='custom_data_json';
	    $w['inputType']='google_map';
	    $w['label']=true;
	    $w['labelText']='Map It';

	    // Tells handler to store as json data in fieldName's field.  All field names are prepended with "json_"
	    $w['jsonCollectionFields']='property_information:latitude,longitude,zoom';
	    $this->addFormField($w);


        //echo "DEBUG ".__FUNCTION__.": ".print_r($this->collectionFormInputWidgets,true); exit;
        //TODO: If this user has users as children, this user can assign another user the listing else make hidden
        $w=array();
        $w['fieldName']='owner_user_id';
        $w['inputType']='hidden';
	    $w['label']=false;
        $w['value']=@$this->userDataArr['unique_url'];
        $this->addFormField($w);


        $w=array();
        $w['fieldName']=$w['value']='Save';
        $w['inputType']='submit';
        $w['label']=false;
        $w['unmapped']=true;
	    $w['class']=' btn-success';
	    $w['fieldElementClass']='col-lg-2 col-sm-12';
        //$w['preSeperatorHtml']='<div style="clear:both;width:100%;height:10px"></div>';
        $this->addFormField($w);

        $w=array();
        $w['fieldName']='save_as_draft';
        $w['value']='Save as Draft';
        $w['inputType']='submit';
        //$w['style']="float:right;max-width:120px";
	    $w['class']='btn-default';
	    $w['fieldElementClass']='col-lg-2 col-sm-12';
        $w['label']=false;
        $w['unmapped']=true;
        //$w['preSeperatorHtml']='<div style="clear:both;width:100%;height:10px"></div>';
        $this->addFormField($w);


       // var_dump($this->collectionFormInputWidgets); exit;
        return $this->collectionFormInputWidgets;
    }


	public function setDeleteForm($id=null){

		$id=intval($id);
		if(empty($id)) {
			echo "<p>No ID record was provided, so no Delete Form can be shown.</p>";
			return false;
		}
		$updateRes=array();
		$abc=$this->adminBaseController=$this->getAdminBaseController();

		$query="SELECT contact_info_id FROM property_info WHERE id=?";
		$resArr=$abc->dbGetData($abc->mainDbConn,$query,array('id'=>$id));
		$contactInfoId=@$resArr[0]['contact_info_id'];

		$w=array();
		$w['html']='<h3>Would you like to delete this record?</h3>';
		$w['html'].='Contact Info ID (for debuggingg: '.$contactInfoId; // TODO: Allow easy way to show a form's values, either all or some at a time for a summary
		$this->addFormField($w);

		$w=array();
		$w['fieldName']='id';
		$w['inputType']='hidden';
		$w['value']=$id;
		$this->addFormField($w);

		$w=array();
		$w['fieldName']='delete';
		$w['value']='Delete';
		$w['inputType']='submit';
		//$w['style']="float:right;max-width:120px";
		$w['class']='btn-warning';
		$w['fieldElementClass']='col-lg-2 col-sm-12';
		$w['label']=false;
		$w['unmapped']=true;
		//$w['preSeperatorHtml']='<div style="clear:both;width:100%;height:10px"></div>';
		$this->addFormField($w);


		return $updateRes;
	}


	public function deleteAction($id=null){
		$query="UPDATE contact_info SET status='X' WHERE id=?";

		$abc=$this->adminBaseController=$this->getAdminBaseController();
		$resArr=$abc->dbExecute($abc->mainDbConn,$query, array('id',$contactInfoId));
		$updateRes[]=$resArr[0];

		$query="UPDATE property_info SET status='X' WHERE id=?";
		$resArr=$abc->dbExecute($abc->mainDbConn,$query, array('id',$contactInfoId));
		$updateRes[]=$resArr[0];
	}

    // This must be defined to link up this form with its entity
    public function getFormEntityName(){
        return 'PropertyInfo';
    }

	// This is also required, this links this entity back to its db table
	public function getEntityTableName(){
		return 'property_info';
	}

}

