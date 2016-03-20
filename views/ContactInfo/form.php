<?php

class ContactInfoForm extends viewBaseForm{


    public function setFormInputWidgets($formVals=null){

        // When element 'label' is set to just true boolean, then show the field name in a display-friendly way
        /*
         * `id`, `owner_user_id`, `contact_info_id`, `title`, `property_subtitle`,
         * `property_desc_body`, `listing_manager_user_id`, `listing_company_user_id`, `custom_data_json`, `status`
         *
         */

	    if(isset($formVals['id'])) {
		    $w=array(); // $w is "Widgets"
		    $w['fieldName']='id';  $w['inputType']='hidden'; $this->addFormField($w);
	    }

        $w=array();
        $w['fieldName']='organization_name';
        $w['inputType']='text';
        $w['label']=true;
        $w['labelText']='Organization';
        $this->addFormField($w);

        $w=array();
        $w['fieldName']='first_name';
        $w['inputType']='text';
        $w['label']=true;
	    $w['class']='col-md-3';
        $this->addFormField($w);

	    $w=array();
	    $w['fieldName']='last_name';
	    $w['inputType']='text';
	    $w['label']=true;
	    $this->addFormField($w);

	    $w=array();
	    $w['fieldName']='address_1';
	    $w['inputType']='text';
	    $w['label']=true;
	    $w['labelText']='Address';
	    $this->addFormField($w);

	    $w=array();
	    $w['fieldName']='address_2';
	    $w['inputType']='text';
	    $w['label']=true;
	    $w['labelText']='Address 2nd Line';
	    $w['class']='col-md-5';
	    $this->addFormField($w);

	    $w=array();
	    $w['fieldName']='city';
	    $w['inputType']='text';
	    $w['label']=true;
	    $w['class']='col-md-5';
	    $this->addFormField($w);

	    $w=array();
	    $stateArr=$this->getStateArr();

	    $w['fieldName']='state';
	    $w['inputType']='choice';
	    $w['label']=true;
	    $w['class']='col-md-3';

	    $opt['values']=array('');
	    $opt['values']+= array_values(array_keys($stateArr));

	    $opt['showValues']=array('Please Select');
	    $opt['showValues']+=array_keys(array_flip($stateArr));
	    $w['options']=$opt;
	    $this->addFormField($w);

	    $w=array();
	    $w['fieldName']='zip';
	    $w['inputType']='text';
	    $w['label']=true;
	    $w['labelText']='ZIP Code';
	    $w['class']='col-md-3';
	    $this->addFormField($w);

        return $this->collectionFormInputWidgets;

    }

	public function validateFields($fieldName,$val){


	}

	/**
	 * Explicitly allows by field name validation.  Always reads "fieldName MUST BE condition", like "state MUST BE not empty"
	 * @return array
	 *
	 */
	public function getValidationRules(){
		$rules=array();
		$rules[]='state:not empty';
		$rules[]='zip:minLength=5,maxLength=20';
		$rules[]='city:minLength=2';

		return $rules;
	}

    // This must be defined to link up this form with its entity
    public function getFormEntityName(){
        return 'ContactInfo';
    }

	// This is also required, this links this entity back to its db table
	public function getEntityTableName(){
		return 'property_info';
	}

}