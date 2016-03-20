<?php

class PropertyListingForm extends viewBaseForm{



    public function setFormInputWidgets($formVals=null){

        // When element 'label' is set to just true boolean, then show the field name in a display-friendly way

	    if(isset($formVals['id'])) {
		    $w=array(); // $w is "Widgets"
		    $w['fieldName']='id';  $w['inputType']='hidden'; $this->addFormField($w);
	    }

        $w['fieldName']='listing_title';
        $w['inputType']='text';
        $w['label']=true;
        $w['class']='col-md-8';
        $w['style']='font-size:15pt';
        $this->addFormField($w);

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


        //TODO: If this user has users as children, this user can assign another user the listing else make hidden
        $w=array();
        $w['fieldName']='list_agent_user_id';
        $w['inputType']='hidden';
        $w['value']=@$this->userDataArr['unique_url'];
        $this->addFormField($w);

        //TODO:  maxDate, minDate and other directives for libraries pass through via data-js-library-vals attribute
                // Each attribute declared has an assoc key index then values separated with a semi-colon
        $w=array();
        $w['fieldName']='start_date';
        $w['inputType']='date';
        $w['label']=true;
        $w['labelText']='Available Start';
            $w['attr']['data-js-library-vals']='minDate='.date("m/d/Y",strtotime('+1 day'));
        $this->addFormField($w);

        $w=array();
        $w['fieldName']='end_date';
        $w['inputType']='date';
        $w['label']=true;
        $w['labelText']='End Date';
        $this->addFormField($w);

        $w=array();
        $w['fieldName']='asking_price';
        $w['inputType']='text';
        $w['label']=true;
        $w['labelText']='Asking Price';
        //$w['class']='col-lg-4';
        $w['preFieldSymbol']='$';   //TODO: This will show $ in front of field and will also create placeholder space for all fields without for alignment
        //$w['fieldGroup']='asking_price';
        //$w['fieldGroupStyle']='max-width:40%;float:left';

        $this->addFormField($w);

        $w=array();
        $w['fieldName']='price_unit';
        $w['inputType']='choice';
        $w['label']=true;
        $w['labelText']=' per ';
        $w['labelStyle']='max-width:85px';
        $opt['values']=
        $opt['showValues']=explode(',','Choose,per day,per week,per month,per year');
        $opt['validation']='not first'; // TODO: Wire this SELECT validation condition up
        $w['options']=$opt;
        //$w['fieldGroup']='asking_price';
        //$w['fieldGroupStyle']='max-width:40%;float:left';
        $this->addFormField($w);

        //TODO: Identifying special JSON fields like this for processing and storing JSON arrays properly
        $w=array();
        $w['fieldName']='json_data:listing_description';
        $w['inputType']='html';
        $w['label']=true;
        $w['labelText']='Listing Description';
        $w['preSeperatorHtml']='<div style="clear:both;width:100%;height:10px"></div>';
        $this->addFormField($w);

        $w=array();
        $w['fieldName']=$w['value']='Save';
        $w['inputType']='submit';
        $w['label']=false;
        $w['unmapped']=true;
        //$w['preSeperatorHtml']='<div style="clear:both;width:100%;height:10px"></div>';
        $this->addFormField($w);

        $w=array();
        $w['fieldName']='save_as_draft';
        $w['value']='Save as Draft';
        $w['inputType']='submit';
        $w['style']="float:right;max-width:120px";
        $w['label']=false;
        $w['unmapped']=true;
        //$w['preSeperatorHtml']='<div style="clear:both;width:100%;height:10px"></div>';
        $this->addFormField($w);

        // var_dump($this->formCollectionArr); exit;
        return $this->collectionFormInputWidgets;
    }

/*
    function addFormField($fieldArr){

        $finArr[$this->fieldIndex]=$fieldArr;
        $this->fieldIndex++;

        $this->collectionFormInputWidgets+=$finArr;
    }

    /**
     * @param $key
     * @param $val
     *
     * Depending on form entity, key and value will return different validation and clean the values
     *
     * This can be overridden by individual entity's form
     *
     */
    public function cleanAndValidate($key,$val){

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
        return array("value"=>filter_var($val,FILTER_SANITIZE_SPECIAL_CHARS),"valid"=>true);
    }

    // This must be defined to link up this form with its entity
    public function getFormEntityName(){
        return 'PropertyListings';
    }

	// This is also required, this links this entity back to its db table
	public function getEntityTableName(){
		return 'property_info';
	}

}

