<?php
/**
 * File to 'add' Training details
 *
 * @author: Ambarish
 * @created on: 9-12-10
 * @package application.views.eduaction_training
 */

	$autoSearchOptions = "width: 255, delimiter: /(,|;)\s*/, deferRequestBy: 200, noCache: true, minChars: 3";

	//To show row list in jqgrid
	function listRecordsPerPage($maxRecords=100,$increament=10){
		$rowList="";
		for($i=10;$i<=$maxRecords;$i+=$increament){
			$rowList.=$i.",";
		}
		$rowList=substr($rowList,0,-1);
		return $rowList;
	} 
?>
<style>
.navbar-global {
  background-color: indigo;
}

.navbar-global .navbar-brand {
  color: white;
}

.navbar-global .navbar-user > li > a
{
  color: white;
}

.navbar-primary {
  background-color: #ccc;
  bottom: 0px;
  left: 0px;
  position: absolute;
  top: 88px;
  width: 200px;
  z-index: 8;
  overflow: hidden;
  -webkit-transition: all 0.1s ease-in-out;
  -moz-transition: all 0.1s ease-in-out;
  transition: all 0.1s ease-in-out;
}

.navbar-primary.collapsed {
  width: 60px;
}

.navbar-primary.collapsed .glyphicon {
  font-size: 22px;
}

.navbar-primary.collapsed .nav-label {
  display: none;
}

.btn-expand-collapse {
    position: absolute;
    display: block;
    left: 0px;
    bottom:0;
    width: 100%;
    padding: 8px 0;
    border-top:solid 1px #666;
    color: grey;
    font-size: 20px;
    text-align: center;
}

.btn-expand-collapse:hover,
.btn-expand-collapse:focus {
    background-color: #222;
    color: white;
}

.btn-expand-collapse:active {
    background-color: #111;
}

.navbar-primary-menu,
.navbar-primary-menu li {
  margin:0; padding:0;
  list-style: none;
}

.navbar-primary-menu li a {
  display: block;
  padding: 10px 18px;
  text-align: left;
  border-bottom:solid 1px #e7e7e7;
  color: #333;
}

.navbar-primary-menu li a:hover {
  background-color: #fff;
  text-decoration: none;
  color: #000;
}

.navbar-primary-menu li a .glyphicon {
  margin-right: 6px;
}

.navbar-primary-menu li a:hover .glyphicon {
  color: #4285F4;
}

.main-content {
  margin-left: 200px;
  padding: 5px 20px;
}

.collapsed + .main-content {
  margin-left: 60px;
}
.nav-tabs { 
	border-bottom: 2px solid #DDD; 
}
.nav-tabs > li.active > a, .nav-tabs > li.active > a:focus, .nav-tabs > li.active > a:hover { 
	border-width: 0;
}
.nav-tabs > li > a { 
	border: none; 
	color: #666; 
}
.nav-tabs > li.active > a, .nav-tabs > li > a:hover {
	border: none; 
	color: #4285F4 !important; 
	background: transparent; 
}
.nav-tabs > li > a::after { 
	content: ""; 
	background: #4285F4; 
	height: 2px; 
	position: absolute; 
	width: 100%; 
	left: 0px; 
	bottom: -1px; 
	transition: all 250ms ease 0s; 
	transform: scale(0); 
}
.nav-tabs > li.active > a::after, .nav-tabs > li:hover > a::after {
 	transform: scale(1); 
}
.tab-nav > li > a::after { 
	background: #21527d none repeat scroll 0% 0%; 
	color: #fff; 
}
.tab-pane {
	padding: 10px 0; 
}
.tab-content{
	padding:10px 20px;
}
img.add-org {
    position: absolute;
    left: 23pc;
    top: 23px;
}
.form-group {
    margin-bottom: 5px;
}
.imageUpload {
    margin-top: 35px;
}
.alert {
    padding: 10px  !important;
    margin-bottom: 0px !important;
    }
.close {
    top: 0px  !important;
    right: 0px  !important;
    font-size:18px;
}  
input#saveContact {
    position: absolute;
    top: 22px;
}  
.gridData{
	padding:0px 0px !important;
}
.panel-heading .colpsible-panel:after {
    
    font-family: 'Glyphicons Halflings'; 
    content: "\e114";    
    float: right;        
    color: #4285f4;         
}
.panel-heading .colpsible-panel.collapsed:after {
    content: "\e080"; 
}
.gridWrapper {
    width: 100% !important;
}
</style>
	<script type="text/javascript">
		$(function(){
			// Initiate the 'Ternary Navigation
			$("#kolOverviewTernaryNav").tabs().addClass("ui-tabs-vertical ui-helper-clearfix" );
		
			// Remove the Surrounding Border
			$("#kolOverviewTernaryNav" ).removeClass("ui-widget-content");
		
			// Remove the Round Corner
			$("#kolOverviewTernaryNav ul").removeClass("ui-corner-all ui-widget-header");
			
			$("#kolOverviewTernaryNav li").removeClass( "ui-corner-top" );
		
			// Add the Custom Class to control View
			$("#kolOverviewTernaryNav > div").addClass( "span-18 last" );
		});

		/*
		*
		* To check start Date is > endDate.. if its true it shows an error
		* @author Vinayk Mlladad
		* @since 1.5.2 
		*/
		function validate(endDate,id,buttonId){
			var startDate = $("#"+id+"StartDate").val();
			var endDate   = endDate;
			if(endDate!=''){	
				if(startDate > endDate){
					$("#"+id+"Date").show();
					disableButton(buttonId);
				}else{
					enableButton(buttonId);
					$("#"+id+"Date").hide();
				}
		}
		}
		var options, a;
	
		// Autocomplet Options for the 'Institute Name' field of type 'Education'
		// Autocomplet Options for the 'Institute Name' field searches From the lookuptable
	  	var eduInstituteNameAutoCompleteOptions = {
				serviceUrl: '<?php echo base_url();?>kols/get_institute_names', <?php echo $autoSearchOptions; ?>
			};		

		// Autocomplet Options for   'Training'
		// Autocomplet Options for the 'Degree Name' field of type 'Training'
	  	var trainDegreeAutoCompleteOptions = {
				serviceUrl: '<?php echo base_url();?>kols/get_education_degrees/training',<?php echo $autoSearchOptions; ?>
			};

		// Autocomplet Options for the 'Specialty Name' field of type 'Training'
	  	var trainSpecialtyAutoCompleteOptions = {
				serviceUrl: '<?php echo base_url();?>kols/get_education_specialtys/training',<?php echo $autoSearchOptions; ?>
			};			

		// Autocomplet Options for  'Board Certifications'
		// Autocomplet Options for the 'Specialty Name' field of type 'Board Certification'
	  	var boardSpecialtyAutoCompleteOptions = {
				serviceUrl: '<?php echo base_url();?>kols/get_education_specialtys/board_certification',<?php echo $autoSearchOptions; ?>
			};		

		var validationRules	=  {
			institute_name: {
				required:true,
				instituteName:"<?php echo base_url();?>kols/get_institute_id/"
			},
			start_date: {
				fullYear: true
			},
			end_date: {
				fullYear: true
			},
			eduUrl1: {
				url: true
			},
			eduUrl2: {
				url: true
			}
		};

		var validationMessages = {
			institute_name: {
				required: "Required",
				instituteName: "",
				remote: ""
			},
			start_date: "Only full year is allowed. Ex: 2010",
			end_date: "Only full year is allowed. Ex: 2010",
			url: "Please enter a valid URL address"
		};

		function disableButton(buttonId){
			$("#"+buttonId).attr("disabled", "disabled");
		}

		function enableButton(buttonId){
			$("#"+buttonId).removeAttr("disabled");
		}		
		
	
		$(document).ready(function(){
			$(".instNotFound").hide();

			//Remove all the 'AutoCompleteContainer' divs' created automatically. If not, too many will get created
			$('div[id^="AutocompleteContainter_"]').remove();
			
			// Trigger the Autocompleter for 'Institute Name' field of type 'Education'
	    	a = $('#eduInstituteName').autocomplete(eduInstituteNameAutoCompleteOptions);
	    	
			// Trigger the Autocompleter for 'Institute Name' field of type 'Education'
	    	// Trigger the Autocompleter for 'Institute Name' field searches From the lookuptable
	    	a = $('#trainInstituteName').autocomplete(eduInstituteNameAutoCompleteOptions);

			// Trigger the Autocompleter for 'Degree Name' field of type 'Training'
	    	a = $('#trainDegree').autocomplete(trainDegreeAutoCompleteOptions);

			// Trigger the Autocompleter for 'Degree Name' field of type 'Training'
	    	a = $('#trainSpecialty').autocomplete(trainSpecialtyAutoCompleteOptions);

			// Trigger the Autocompleter for 'Institute Name' field of type 'Board Certification'
	    	// Trigger the Autocompleter for 'Institute Name' field searches From the lookuptable
	    	a = $('#boardInstituteName').autocomplete(eduInstituteNameAutoCompleteOptions);

			// Trigger the Autocompleter for 'Degree Name' field of type 'Board Certification'
	    	a = $('#boardSpecialty').autocomplete(boardSpecialtyAutoCompleteOptions);			

			// populate the Institution form

			// For Training 
				var institutionProfileDialogOpts = {
					title: "Create Institution Profile",
					modal: true,
					autoOpen: false,
					dialogClass:'microView',
					height: 300,
					width: 600,
					open: function() {
						//display correct dialog content
					}
			};


			//	'University / Hospital dialog box'
			$("#institutionProfile").dialog(institutionProfileDialogOpts);
			
			$(".InstitutionProfileLink").click(
					function (){
						var data = {};
						
						$("#institutionProfile").dialog("open");
						
						//jAlert(name);
						var sel=$('#kolOverviewTernaryNav').tabs('option','selected');
						//jAlert(sel);
						if(sel==0)
						{
							var name=$("#eduInstituteName").val();
							var name1=name.replace(/\s/g,'%20');
						}
						if(sel==1)
						{
							var name=$("#trainInstituteName").val();
							var name1=name.replace(/\s/g,'%20');
						}
						if(sel==2)
						{
							var name=$("#boardInstituteName").val();
							var name1=name.replace(/\s/g,'%20');
						}
						//$("#instituteName").val(name1);
						$("#institutionProfileContainer").load('<?php echo base_url()?>kols/add_institution/'+name1);
						return false;
			});

			
			/*
			*common function to save/update details
			*/
			function saveDetails(idType,formId,gridId,btnId,instituteId,divId){

				$('div.eduMsgBox').removeClass('success');
				$('div.eduMsgBox').addClass('notice');
				$('div.eduMsgBox').show();
				$('div.eduMsgBox').html('Saving the data... <img src="<?php echo base_url()?>images/ajax_loader_black.gif" />');
				
				if(idType!='award')
					$("#"+idType+"InstituteId").val(instituteId);				
				
				//If Institute Id is present then perform save or update
				id = $("#"+idType+"Id").val();
				if(id == ''){
					formAction = '<?php echo base_url();?>kols/save_education_detail';
				}else{
					formAction = '<?php echo base_url();?>kols/update_education_detail';
				}					
			
				 $.post(formAction, $("#"+formId).serialize(),
						 function(returnData){
				     		if(returnData.saved == true){

								// Clear the existing form details
								if(idType!='award'){
								$("#"+idType+"InstituteName").val("");
								$("#"+idType+"Degree").val("");
								$("#"+idType+"Specialty").val("");
								$("#"+idType+"StartDate").val("");
								$("#"+idType+"EndDate").val("");
								}else{
								$("#"+idType+"HonorName").val("");
								$("#"+idType+"StartDate").val("");
								$("#"+idType+"EndDate").val("");
								}
								$("#"+idType+"Url1").val("");
								$("#"+idType+"Url2").val("");
								$("#"+idType+"Notes").val("");

								$("tr.ui-state-highlight").removeClass('ui-state-highlight');
								if(id == ''){
									if(idType!='award'){									
									var datarow = {
											id				:	returnData.lastInsertId,
											institute_id	:	returnData.data.institute_id,
											specialty 		:	returnData.data.specialty,
											degree			:	returnData.data.degree,
											start_date		:	returnData.data.start_date,
											end_date		:	returnData.data.end_date,
											url1			:	returnData.data.url1,
											url2			:	returnData.data.url2,
											notes			:	returnData.data.notes
											
										}; 
									}else{
										var datarow = {
												id				:	returnData.lastInsertId,
												honor_name		:	returnData.data.honor_name,
												start_date		:	returnData.data.start_date,
												end_date		:	returnData.data.end_date,
												url1			:	returnData.data.url1,
												url2			:	returnData.data.url2,
												notes			:	returnData.data.notes
												
											}; 
									}
									var su=jQuery("#"+gridId).jqGrid('addRowData',returnData.lastInsertId,datarow); 
									
								}else{
									//jQuery("#JQBlistEducationResultSet").trigger("reloadGrid");
									if(idType!='award'){
										jQuery("#"+gridId).jqGrid('setRowData',returnData.data.id,{
																					id				:	returnData.data.id,
																					institute_id	:	returnData.data.institute_id,
																					specialty 		:	returnData.data.specialty,
																					degree			:	returnData.data.degree,
																					start_date		:	returnData.data.start_date,
																					end_date		:	returnData.data.end_date,
																					url1			:	returnData.data.url1,
																					url2			:	returnData.data.url2,
																					notes			:	returnData.data.notes
																				}); 
										}else{
											jQuery("#AwardResultSet").jqGrid('setRowData',returnData.data.id,{
																					id				:	returnData.lastInsertId,
																					honor_name		:	returnData.data.honor_name,
																					start_date		:	returnData.data.start_date,
																					end_date		:	returnData.data.end_date,
																					url1			:	returnData.data.url1,
																					url2			:	returnData.data.url2,
																					notes			:	returnData.data.notes
																				}); 
										}
									$("tr#"+returnData.data.id).addClass('ui-state-highlight');
								}

									// If we are updating
								if(id != ''){
									// Modify the text of 'Button' from 'Update' to 'Add'
									$("#"+btnId).val("Add");

									// Re-Set the Hidden Eduction Id value
									$("#"+idType+"Id").val("");									
								}
								$("div.eduMsgBox").fadeOut(10000);
								$("#"+divId+"Date").hide();
								$(".eduMsgBox ").hide();
								
						     	enableButton(btnId);

					     	}else{
						     	enableButton(btnId);
								// Display Error Message
						     }
						},"json");				
			}

			/**
			* Save the 'Education Details'
			*/
			$("#saveEducation").click(function(){
				
					// Disable the SAVE Button
					disableButton("saveEducation");
					
					$("#educationForm").validate().resetForm();

					if(!$("#educationForm").validate().form()){
						enableButton("saveEducation");
						return false;
					}
					// Check if the Institute Name is present in Master table or not
					instituteName	=	$("#eduInstituteName").val(); 

					// URL to get the Institute ID, if Name is present
					urlAction = '<?php echo base_url();?>kols/get_institute_id/'+instituteName;
	
					// Variable to hold the Institute Id
					instituteId	= '';
					$.post(urlAction,'',
							function(returnData){
								if(returnData){
									instituteId	= returnData;
									$("#eduInstituteNameNotFound").hide();
									saveEducationDetails(instituteId);
								}else{
									enableButton("saveEducation");
									
									$("#eduInstituteNameNotFound").show();
	
									// Set the user entered name in the 'Add New Institute' form
									$("#instituteName").val(instituteName);
									return false;
								}
							}, 
							"json");
					//- End of checking the Intitute Name in Master Table
			});	
       
			function saveEducationDetails(instituteId){
				$("#eduInstituteId").val(instituteId);
				var startDate=$("#eduStartDate").val();
				var  endDate = $("#eduEndDate").val();
				if(endDate!='' && startDate!=''){
					if(startDate<=endDate){
						saveDetails('edu','educationForm','EducationResultSet','saveEducation',instituteId,'edu');
					}else{
						$("#eduDate").show();
						enableButton("saveEducation");
					}
				}else{
						saveDetails('edu','educationForm','EducationResultSet','saveEducation',instituteId,'edu');
					}
			}			

			/**
			* Save the 'Training Details'
			*/
			$("#saveTraining").click(function(){
				
					// Disable the SAVE Button
					disableButton("saveTraining");

					$("#trainingForm").validate().resetForm();

					if(!$("#trainingForm").validate().form()){
						enableButton("saveTraining");
						return false;
					}
				
					// Check if the Institute Name is present in Master table or not
					instituteName	=	$("#trainInstituteName").val(); 
					// URL to get the Institute ID, if Name is present
					urlAction = '<?php echo base_url();?>kols/get_institute_id/'+instituteName;
	
					// Variable to hold the Institute Id
					instituteId	= '';
					$.post(urlAction,'',
							function(returnData){
								if(returnData){
									instituteId	= returnData;
									$("#trainInstituteNameNotFound").hide();
									saveTrainingDetails(instituteId);
								}else{
									enableButton("saveTraining");

									$("#trainInstituteNameNotFound").show();
	
									// Set the user entered name in the 'Add New Institute' form
									$("#instituteName").val(instituteName);
									return false;
								}
							}, 
							"json");
				//- End of checking the Intitute Name in Master Table
			});	
			
			/**
			* Save the 'Training Details '
			*/
			function saveTrainingDetails(instituteId){
				$("#trainInstituteId").val(instituteId);
				var startDate =$("#trainStartDate").val();
				var endDate =$("#trainEndDate").val();
				if(startDate!='' && endDate!=''){
					if(startDate<=endDate){
						saveDetails('train','trainingForm','TrainingResultSet','saveTraining',instituteId,'train');
					}else{
						$("#trainDate").show();
						enableButton("saveTraining");
					}
				}else{
					saveDetails('train','trainingForm','TrainingResultSet','saveTraining',instituteId,'train');
				}
			};	

			/**
			* Save the 'Board Certification Details'
			*/
			$("#saveBoard").click(function(){

					// Disable the SAVE Button
					disableButton("saveBoard");

					$("#boardForm").validate().resetForm();

					if(!$("#boardForm").validate().form()){
						enableButton("saveBoard");
						return false;
					}
				
					// Check if the Institute Name is present in Master table or not
					instituteName	=	$("#boardInstituteName").val(); 
					// URL to get the Institute ID, if Name is present
					urlAction = '<?php echo base_url();?>kols/get_institute_id/'+instituteName;
	
					// Variable to hold the Institute Id
					instituteId	= '';
					$.post(urlAction,'',
							function(returnData){
								if(returnData){
									instituteId	= returnData;
									$("#boardInstituteNameNotFound").hide();
									saveBoardDetails(instituteId);
								}else{
									enableButton("saveBoard");

									$("#boardInstituteNameNotFound").show();
	
									// Set the user entered name in the 'Add New Institute' form
									$("#instituteName").val(instituteName);
									return false;
								}
							}, 
							"json");
					//- End of checking the Intitute Name in Master Table
			});	
			
			/**
			* Save the 'Board Certification Details'
			*/
			function saveBoardDetails(instituteId){
				$("#boardInstituteId").val(instituteId);
				var startDate =$("#boardStartDate").val();
				var endDate =$("#boardEndDate").val();
				if(startDate!='' && endDate!=''){
					if(startDate<=endDate){
						saveDetails('board','boardForm','BoardResultSet','saveBoard',instituteId,'board');
						
					}else{
						$("#boardDate").show();
						enableButton("saveBoard");
					}
				}else{
					saveDetails('board','boardForm','BoardResultSet','saveBoard',instituteId,'board');
				}
			};	

			/**
			* Save the 'Honors and Awards Details'
			*/
			$("#saveAward").click(function(){
				disableButton("saveAward");

				$("#awardForm").validate().resetForm();


				if(!$("#awardForm").validate().form()){
					enableButton("saveAward");
					return false;
				}
				// To make name field Mandatory
				var name = $("#awardHonorName").val();
				saveDetails('award','awardForm','AwardResultSet','saveAward');
			});	

			// validate signup form on keyup and submit
			$("#educationForm").validate({
				debug:true,
				onkeyup:true,
				rules: validationRules,
				messages: validationMessages
			});

			$("#trainingForm").validate({
				onkeyup:true,
				rules: validationRules,
				messages: validationMessages
			});

			$("#boardForm").validate({
				onkeyup:true,
				rules: validationRules,
				messages: validationMessages
			});

			$("#awardForm").validate({
				onkeyup:true,
				rules: validationRules,
				messages: validationMessages
			});
					
		});

//------------Start of "Edit" methods------------------------------- 

		/**
		*common edit method
		*/
		function editRow(rowId,idType,gridId,btnId){
			id=rowId;			
			var rd=jQuery("#"+gridId).jqGrid('getRowData',id);			
			// Get the values from TR - Table Row, which is under editing
			id 				= rd.id;
			if(idType!='award'){
			name 			= rd.institute_id;
			degree			= rd.degree;
			specialty		= rd.specialty;
			start			= rd.start_date;
			end				= rd.end_date;
			}else{
			honor_name 		= rd.honor_name;
			year			= rd.year;	
			start			= rd.start_date;
			end				= rd.end_date;
			}
			url1			= $(rd.url1).attr("href");
			url2			= $(rd.url2).attr("href");
			notes			= rd.notes;
			
			// Add the values to the form
			if(idType!='award'){
			$("#"+idType+"InstituteName").val(name);
			$("#"+idType+"Degree").val(degree);
			$("#"+idType+"Specialty").val(specialty);
			$("#"+idType+"StartDate").val(start);
			$("#"+idType+"EndDate").val(end);
			}else{
			$("#"+idType+"HonorName").val(honor_name);
			$("#"+idType+"StartDate").val(start);
			$("#"+idType+"EndDate").val(end);
			}
			$("#"+idType+"Url1").val(url1);
			$("#"+idType+"Url2").val(url2);
			$("#"+idType+"Notes").val(notes);

			// Modify the text of 'Button' from 'Add' to 'Update'
			$("#"+btnId).val("Update");

			$("#"+idType+"Id").val(id);			
			$("tr").removeClass('ui-state-highlight');
			$("tr#"+id).addClass('ui-state-highlight');
			
		}
		/**
		* Edit the 'Education Details'
		*/
		function editEducation(id){			
			editRow(id,'edu','EducationResultSet','saveEducation');				
		}

		/**
		* Edit the 'Training Details'
		*/
		function editTraining(id){
			editRow(id,'train','TrainingResultSet','saveTraining');				
		}

		/**
		* Edit the 'Board Certification Details'
		*/
		function editBoard(id){
			editRow(id,'board','BoardResultSet','saveBoard');					
		}

		/**
		* Edit the 'Honors and Awards Details'
		*/
		function editAward(id){
			editRow(id,'award','AwardResultSet','saveAward');
		}		

//------------End of "Edit" methods------------------------------- 				
		
		/**
		* Validate the text for 'Numeric Only'
		*/
		function allowNumericOnly(src) {
			
			if(!src.value.match(/^\d{4}$/)) {
				src.value=src.value.replace(/[^0-9]/g,'');  
			}
			
		}

//-----------Start of "delete methods"----------------------
		/**
		* Delete the 'Education Details'
		*/
		function deleteEducation(id){								
			var formAction = '<?php echo base_url();?>kols/delete_education_detail/'+id;
			jQuery("#EducationResultSet").jqGrid('delGridRow',id,{reloadAfterSubmit:false,url:formAction});     
		}	
		
		/**
		* Delete the 'Training Details'
		*/
		function deleteTraining(id){								
			formAction = '<?php echo base_url();?>kols/delete_education_detail/'+id;
			jQuery("#TrainingResultSet").jqGrid('delGridRow',id,{reloadAfterSubmit:false,url:formAction});     
		}	

		/**
		* Delete the 'Board Certification Details'
		*/
		function deleteBoard(id){								

			var formAction = '<?php echo base_url();?>kols/delete_education_detail/'+id;
			jQuery("#BoardResultSet").jqGrid('delGridRow',id,{reloadAfterSubmit:false,url:formAction});     
		}

		/**
		* Delete the 'Awards details'
		*/
		function deleteAward(id){								

			var formAction = '<?php echo base_url();?>kols/delete_education_detail/'+id;
			jQuery("#AwardResultSet").jqGrid('delGridRow',id,{reloadAfterSubmit:false,url:formAction});     
		}					
//--------------End of "delete methods"----------------------		

//--------------Start of "Jqgrid" methods--------------------------	
		
		/*
		*Common method for Jqgrid
		*/
		var eduColSettings = [		{name:'id',index:'id', hidden:true},
							   		{name:'institute_id',index:'institute_id', width:300,editable:true},
							   		{name:'degree',index:'degree',width:155,editable:true},
							   		{name:'specialty',index:'specialty',width:160,editable:true},
							   		{name:'start_date',index:'start_date',width:50,resizable:true,editable:true},
							   		{name:'end_date',index:'end_date',width:50,resizable:true,editable:true},
							   		{name:'url1',index:'url1',width:50,editable:true, search:false},
							   		{name:'url2',index:'url2',width:50,resizable:true,editable:true, search:false},
							   		{name:'notes',index:'notes',width:100,resizable:true,editable:true,search:false},
							   		{name:'act',resizable:true, search:false,width:60}		   			
						   	];
		var commonJQGridConfiguration = {
				datatype: "json",
			   	rowNum:10,
			   	rownumbers: true, 
			   	autowidth:true,
			   	loadonce:true,
			   	ignoreCase:true,
			   	hiddengrid:false,
			   	height: "auto",	
			   	resizable:true,   
			   	mtype: "POST",
			    viewrecords: true,
			    sortorder: "desc",
			   	rowList:paginationValues,
			    jsonReader: { repeatitems : false, id: "0" },
			    multiselect: true
		};

	
		function educationTab()
		{ 
		/*
		*jqgrid for Education table
		*/
		var kolId = $('#kolId').val();
		var educationGridConfiguration = {
			   	url:'<?php echo base_url();?>kols/list_education_details/education/'+kolId,
			   	colNames:['Id','Name','Degree', 'Specialty', 'Start','End','Url1','Url2','Notes','Action'],
			   	colModel:eduColSettings,
			   	pager: '#listEducationPage',
			   	sortname: 'name',
			    gridComplete: function(){ 
				    var ids = jQuery("#EducationResultSet").jqGrid('getDataIDs'); 
				    	for(var i=0;i < ids.length;i++){ 
					    	var cl = ids[i];				    	
					    	be = "<a href='#' onclick=\"editEducation('"+cl+"');\" ><img title='Edit' src='<?php echo base_url()?>images/edit.png'></a>";
					    	be += " | ";
					    	be += "<a href='#' onclick=\"deleteEducation('"+cl+"');\" ><img title='Delete' src='<?php echo base_url()?>images/delete.png'></a>";				    	
					    	jQuery("#EducationResultSet").jqGrid('setRowData',ids[i],{act:be}); 
					    	} 
				    	jQuery("#EducationResultSet").jqGrid('navGrid','hideCol',"id"); 
				    	},
				    	 
		    	loadComplete: function() {
		    	    $("option[value=100000000]").text('All');
	    				},       	
			    editurl:"<?php echo base_url();?>kols/update_education_detail",		   
			    caption:"Education Details"
			    
			};

			$.extend(educationGridConfiguration, commonJQGridConfiguration);

			jQuery("#EducationResultSet").jqGrid(educationGridConfiguration);

			jQuery("#EducationResultSet").jqGrid('navGrid','#listEducationPage',{edit:false,add:false,del:false,search:false,refresh:false});	

			//Toolbar search bar below the Table Headers
			jQuery("#EducationResultSet").jqGrid('filterToolbar',{stringResult: true,searchOnEnter : false, defaultSearch:"cn"}); 		
			//Toolbar search bar above the Table Headers
			//jQuery("#t_EducationResultSet").height(25).jqGrid('filterGrid',"EducationResultSet",{gridModel:true,gridToolbar:true});
			jQuery("#EducationResultSet").jqGrid('gridResize',{'minWidth':550, 'maxWidth':2000}); 		

			// Delete selected row(s)
			jQuery("#EducationResultSet").jqGrid('navButtonAdd',"#listEducationPage",{caption:"Delete",buttonicon:"ui-icon-trash",title:"Delete Select Row(s)",
				onClickButton:function (){
					var selectedEdus	= $(this).getGridParam('selarrrow');
					if(selectedEdus.length>0){
						deleteSelectedEducations(selectedEdus);
					}else{
						jAlert('Please select atleast one Education');
					}
				}
			}); 
			//Toggle Toolbar Search 
			jQuery("#EducationResultSet").jqGrid('navButtonAdd',"#listEducationPage",{caption:"Search",title:"Toggle Search",
				onClickButton:function(){ 			
					if(jQuery(".ui-search-toolbar").css("display")=="none") {
						jQuery(".ui-search-toolbar").css("display","");
					} else {
						jQuery(".ui-search-toolbar").css("display","none");
					}
					
				} 
			});
		}

		function deleteSelectedEducations(eduIds){
			jConfirm("Are you sure you want to delete selected Educations's?","Please confirm",function(r){
				if(r){
					$.ajax({
						url:'<?php echo base_url()?>kols/delete_selected_educations/'+eduIds,
						type:'post',
						dataType:"json",
						success:function(returnMsg){
							if(returnMsg.status)
								$('a[href="#educationTabId"]').trigger('click');
							}
					});
					}else{
							return false;
						}
			});
			} 
		
		function trainingTab()
		{
		/*
		*jqgrid for Training table
		*/
		var kolId = $('#kolId').val();
		var  TrainingGridConfiguration = {
		   	url:'<?php echo base_url();?>kols/list_education_details/training/'+kolId,
		   	colNames:['Id','Name','Degree', 'Specialty', 'Start','End','Url1','Url2','Notes','Action'],
		   	colModel:eduColSettings,
		   	pager: '#listTrainingPage',
		   	sortname: 'name',
		    gridComplete: function(){ 
			    var ids = jQuery("#TrainingResultSet").jqGrid('getDataIDs'); 
			    	for(var i=0;i < ids.length;i++){ 
				    	var cl = ids[i];				    	
				    //	be = "<a href='#' onclick=\"editTraining('"+cl+"');\" >edit</a>/<a href='#' onclick=\"deleteTraining('"+cl+"');\" >delete</a>"; 	
				    	be = "<a href='#' onclick=\"editTraining('"+cl+"');\" ><img title='Edit' src='<?php echo base_url()?>images/edit.png'></a>";
				    	be += " | ";
				    	be += "<a href='#' onclick=\"deleteTraining('"+cl+"');\" ><img title='Delete' src='<?php echo base_url()?>images/delete.png'></a>";				    	
				    				    	
				    	jQuery("#TrainingResultSet").jqGrid('setRowData',ids[i],{act:be}); 
				    	} 
			    	jQuery("#TrainingResultSet").jqGrid('navGrid','hideCol',"id"); 
			    	}, 

	    	loadComplete: function() {
	    	    $("option[value=100000000]").text('All');
    				},
		    editurl:"<?php echo base_url();?>kols/update_education_detail",		   
		    caption:"Training Details"		    
		};
		$.extend(TrainingGridConfiguration, commonJQGridConfiguration);

		jQuery("#TrainingResultSet").jqGrid(TrainingGridConfiguration);

		jQuery("#TrainingResultSet").jqGrid('navGrid','#listTrainingPage',{edit:false,add:false,del:false,search:false,refresh:false});	
		//Toolbar search bar below the Table Headers
		jQuery("#TrainingResultSet").jqGrid('filterToolbar',{stringResult: true,searchOnEnter : false, defaultSearch:"cn"}); 		
		//Toolbar search bar above the Table Headers
		//jQuery("#t_EducationResultSet").height(25).jqGrid('filterGrid',"EducationResultSet",{gridModel:true,gridToolbar:true});

		jQuery("#TrainingResultSet").jqGrid('gridResize',{'minWidth':550, 'maxWidth':2000}); 		

		// Delete button
		jQuery("#TrainingResultSet").jqGrid('navButtonAdd',"#listTrainingPage",{caption:"Delete",buttonicon:"ui-icon-trash",title:"Delete Select Row(s)",
				onClickButton:function (){
					var selectedTrainings	= $(this).getGridParam('selarrrow');
					if(selectedTrainings.length>0){
						deleteSelectedTrainings(selectedTrainings);
					}else{
						jAlert('Please select atleast one Training');
					}
				}
			});
		//Toggle Toolbar Search 
		jQuery("#TrainingResultSet").jqGrid('navButtonAdd',"#listTrainingPage",{caption:"Search",title:"Toggle Search",
			onClickButton:function(){ 			
				if(jQuery(".ui-search-toolbar").css("display")=="none") {
					jQuery(".ui-search-toolbar").css("display","");
				} else {
					jQuery(".ui-search-toolbar").css("display","none");
				}
				
			} 
		}); 
		}
		function deleteSelectedTrainings(traniIds){
			jConfirm("Are you sure you want to delete selected Training's?","Please confirm",function(r){
				if(r){
					$.ajax({
						url:'<?php echo base_url()?>kols/delete_selected_educations/'+traniIds,
						type:'post',
						dataType:"json",
						success:function(returnMsg){
							if(returnMsg.status)
								$('a[href="#trainingTabId"]').trigger('click');
							}
					});
					}else{
							return false;
						}
			});
			}
		function boardTab()
		{
		var kolId = $('#kolId').val();
		/*
		*jqgrid for Board Certification table
		*/
		var  BoardGridConfiguration = {
		   	url:'<?php echo base_url();?>kols/list_education_details/board_certification/'+kolId,
			datatype: "json",
		   	colNames:['Id','Name','Specialty', 'Start','End','Url1','Url2','Notes','Action'],
		   	colModel:[
				{name:'id',index:'id', hidden:true},
		   		{name:'institute_id',index:'institute_id', width:350,editable:true},
		   		{name:'specialty',index:'specialty',width:225,editable:true},
		   		{name:'start_date',index:'start_date',width:50,resizable:true,editable:true},
		   		{name:'end_date',index:'end_date',width:50,resizable:true,editable:true},
		   		{name:'url1',index:'url1',width:50,editable:true, search:false},
		   		{name:'url2',index:'url2',width:50,resizable:true,editable:true, search:false},
		   		{name:'notes',index:'notes',width:150,resizable:true,editable:true,search:false},
		   		{name:'act',resizable:true, search:false,width:60}		   			
		   	],
		   	pager: '#listBoardPage',
		   	sortname: 'institute_id',
		    gridComplete: function(){ 
			    var ids = jQuery("#BoardResultSet").jqGrid('getDataIDs'); 
			    	for(var i=0;i < ids.length;i++){ 
				    	var cl = ids[i];				    	
				   // 	be = "<a href='#' onclick=\"editBoard('"+cl+"');\" >edit</a>/<a href='#' onclick=\"deleteBoard('"+cl+"');\" >delete</a>"; 	
				    	be = "<a href='#' onclick=\"editBoard('"+cl+"');\" ><img title='Edit' src='<?php echo base_url()?>images/edit.png'></a>";
				    	be += " | ";
				    	be += "<a href='#' onclick=\"deleteBoard('"+cl+"');\" ><img title='Delete' src='<?php echo base_url()?>images/delete.png'></a>";					    	
				    	jQuery("#BoardResultSet").jqGrid('setRowData',ids[i],{act:be}); 
				    	} 
			    	jQuery("#BoardResultSet").jqGrid('navGrid','hideCol',"id"); 
			    	}, 

    		loadComplete: function() {
	    	    $("option[value=100000000]").text('All');
    				},    	
		    editurl:"<?php echo base_url();?>kols/update_education_detail",		   
		    caption:"Board Certification Details"		    
		};

		$.extend(BoardGridConfiguration, commonJQGridConfiguration);

		jQuery("#BoardResultSet").jqGrid(BoardGridConfiguration);

		jQuery("#BoardResultSet").jqGrid('navGrid','#listBoardPage',{edit:false,add:false,del:false,search:false,refresh:false});	

		//Toolbar search bar below the Table Headers
		jQuery("#BoardResultSet").jqGrid('filterToolbar',{stringResult: true,searchOnEnter : false, defaultSearch:"cn"}); 		
		//Toolbar search bar above the Table Headers
		//jQuery("#t_EducationResultSet").height(25).jqGrid('filterGrid',"EducationResultSet",{gridModel:true,gridToolbar:true});
		
		jQuery("#BoardResultSet").jqGrid('gridResize',{'minWidth':550, 'maxWidth':2000}); 

		// Delete button
		jQuery("#BoardResultSet").jqGrid('navButtonAdd',"#listBoardPage",{caption:"Delete",buttonicon:"ui-icon-trash",title:"Delete Select Row(s)",
				onClickButton:function (){
					var selectedBoards	= $(this).getGridParam('selarrrow');
					if(selectedBoards.length>0){
						deleteSelectedBoards(selectedBoards);
					}else{
						jAlert('Please select atleast one Board certifications');
					}
				}
			});
		//Toggle Toolbar Search 
		jQuery("#BoardResultSet").jqGrid('navButtonAdd',"#listBoardPage",{caption:"Search",title:"Toggle Search",
			onClickButton:function(){ 			
				if(jQuery(".ui-search-toolbar").css("display")=="none") {
					jQuery(".ui-search-toolbar").css("display","");
				} else {
					jQuery(".ui-search-toolbar").css("display","none");
				}
				
			} 
		}); 		
		}
		function deleteSelectedBoards(boardIds){
			jConfirm("Are you sure you want to delete selected Board Certification's?","Please confirm",function(r){
				if(r){
					$.ajax({
						url:'<?php echo base_url()?>kols/delete_selected_educations/'+boardIds,
						type:'post',
						dataType:"json",
						success:function(returnMsg){
							if(returnMsg.status)
								$('a[href="#boardTabId"]').trigger('click');
							}
					});
					}else{
							return false;
						}
			});
			}
		
		function awardTab()
		{
		var kolId = $('#kolId').val();
		/*
		*jqgrid for Honors and Awards  table
		*/
		//$([AwardResultSet]).setGridWidth(800,true);
		var   AwardGridConfiguration = {
		   	url:'<?php echo base_url();?>kols/list_education_details/honors_awards/'+kolId,
			datatype: "json",
		   	colNames:['Id','Name','Start','End','Url1','Url2','Notes','Action'],
		   	colModel:[
				{name:'id',index:'id', hidden:true},
		   		{name:'honor_name',index:'honor_name', width:450,editable:true},
		   		{name:'start_date',index:'start_date',width:50,resizable:true,editable:true},
		   		{name:'end_date',index:'end_date',width:50,resizable:true,editable:true},
		   		{name:'url1',index:'url1',width:75,editable:true,search:false},
		   		{name:'url2',index:'url2',width:75,resizable:true,editable:true, search:false},
		   		{name:'notes',index:'notes',width:250,resizable:true,editable:true,search:false},
		   		{name:'act',resizable:true, search:false,width:60}		   			
		   	],
		   
		   	pager: '#listAwardPage',
		   	sortname: 'honor_name',
		    gridComplete: function(){ 
			    var ids = jQuery("#AwardResultSet").jqGrid('getDataIDs'); 
			    	for(var i=0;i < ids.length;i++){ 
				    	var cl = ids[i];				    	
				    	//be = "<a href='#' onclick=\"editAward('"+cl+"');\" >edit</a>/<a href='#' onclick=\"deleteAward('"+cl+"');\" >delete</a>"; 	
				    	be = "<a href='#' onclick=\"editAward('"+cl+"');\" ><img title='Edit' src='<?php echo base_url()?>images/edit.png'></a>";
				    	be += " | ";
				    	be += "<a href='#' onclick=\"deleteAward('"+cl+"');\" ><img title='Delete' src='<?php echo base_url()?>images/delete.png'></a>";				    	
				    	jQuery("#AwardResultSet").jqGrid('setRowData',ids[i],{act:be}); 
				    	} 
			    	jQuery("#AwardResultSet").jqGrid('navGrid','hideCol',"id"); 
			    	}, 

	    	loadComplete: function() {
	    	    $("option[value=100000000]").text('All');
    				},       	
		    editurl:"<?php echo base_url();?>kols/update_education_detail",		   
		    caption:"Award Details"		    
		};
		$.extend(AwardGridConfiguration, commonJQGridConfiguration);

		jQuery("#AwardResultSet").jqGrid(AwardGridConfiguration);


		jQuery("#AwardResultSet").jqGrid('navGrid','#listAwardPage',{edit:false,add:false,del:false,search:false,refresh:false});	
		//Toolbar search bar below the Table Headers
		jQuery("#AwardResultSet").jqGrid('filterToolbar',{stringResult: true,searchOnEnter : false, defaultSearch:"cn"}); 		
		//Toolbar search bar above the Table Headers
		//jQuery("#t_EducationResultSet").height(25).jqGrid('filterGrid',"EducationResultSet",{gridModel:true,gridToolbar:true});
		
		jQuery("#AwardResultSet").jqGrid('gridResize',{'minWidth':550, 'maxWidth':2000}); 

		// Delete button
		jQuery("#AwardResultSet").jqGrid('navButtonAdd',"#listAwardPage",{caption:"Delete",buttonicon:"ui-icon-trash",title:"Delete Select Row(s)",
				onClickButton:function (){
					var selectedAwards	= $(this).getGridParam('selarrrow');
					if(selectedAwards.length>0){
						deleteSelectedAward(selectedAwards);
					}else{
						jAlert('Please select atleast one Awards');
					}
				}
			});
		//Toggle Toolbar Search 
		jQuery("#AwardResultSet").jqGrid('navButtonAdd',"#listAwardPage",{caption:"Search",title:"Toggle Search",
			onClickButton:function(){ 			
				if(jQuery(".ui-search-toolbar").css("display")=="none") {
					jQuery(".ui-search-toolbar").css("display","");
				} else {
					jQuery(".ui-search-toolbar").css("display","none");
				}
				
			} 
		}); 			
		}

		function deleteSelectedAward(awardIds){
			jConfirm("Are you sure you want to delete selected Award's?","Please confirm",function(r){
				if(r){
					$.ajax({
						url:'<?php echo base_url()?>kols/delete_selected_educations/'+awardIds,
						type:'post',
						dataType:"json",
						success:function(returnMsg){
							if(returnMsg.status)
								$('a[href="#honorsTabId"]').trigger('click');
							}
					});
					}else{
							return false;
						}
			});
			}
		/*
		* To Load Selected Tab
		* @created on 18-5-2011
		* @author Amit
		* @since 2.2
		*/

		$(document).ready(function(){				
			$('.nav-tabs li a').click(function(){
				loadSelectedTab(this);
			});
			educationTab();
		});

		/*
		* To Load Selected Tab
		* @created on 18-5-2011
		* @author Amit
		* @since 2.2
		*/

		function loadSelectedTab(selected){
				var sel= $(selected).attr('aria-controls');
				switch(sel){
					case 'education': $("#genericGridContainer").html("");
							
							// Append the required div and table
							$("#genericGridContainer").html('<table id="EducationResultSet"></table><div id="listEducationPage"></div>');

							educationTab();
							break;
					
					case 'training': $("#genericGridContainer").html("test");
							// Append the required div and table
							$("#genericGridContainer").html('<table id="TrainingResultSet"></table><div id="listTrainingPage"></div>');
							trainingTab();
							break;
					
					case 'board-certification': $("#genericGridContainer").html("");
							
							// Append the required div and table
							$("#genericGridContainer").html('<table id="BoardResultSet"></table><div id="listBoardPage"></div>');

							boardTab();
							break;
									
					case 'honors-awards': $("#genericGridContainer").html("");
							
							// Append the required div and table
							$("#genericGridContainer").html('<table id="AwardResultSet"></table><div id="listAwardPage"></div>');

							awardTab();
							break;	
								
				}
			}
			
</script>
<?php $this->load->view('kols/secondary_menu');?>
<div class="main-content">
	<div class="row">
		<div id="kolTernaryNav" class="col-md-12">
        <!-- Start Nav tabs -->
               <ul class="nav nav-tabs" role="tablist">
                  <li role="Details" class="active"><a href="#education" aria-controls="education" role="tab" data-toggle="tab">Education</a></li>
                  <li role="Details"><a href="#training" aria-controls="training" role="tab" data-toggle="tab">Training</a></li>
                  <li role="Details"><a href="#board-certification" aria-controls="board-certification" role="tab" data-toggle="tab">Board Certification</a></li>
                  <li role="Details"><a href="#honors-awards" aria-controls="honors-awards" role="tab" data-toggle="tab">Honors &amp; Awards </a></li>                  
               </ul>
		<!-- End Nav tabs -->
               <div class="tab-content">
               <div>
               	<h5 style="font-weight:bold;color:#656565;">Profile of : Kols Name</h5>
               </div>
        <!-- Start Tab panels -->
                  <div role="tabpanel" class="tab-pane active" id="education">
                  			<div class="panel panel-default"> 
	                  			<div class="panel-heading">
									<h3 class="panel-title"><a class="colpsible-panel collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapse3" title="Click to Expand">Add Education Details</a></h3> 
	                  				</a>
	                  			</div> 
	                  			<div id="collapse3" class="panel-collapse collapse">
	                  			<div class="panel-body">
	                  				<div class="col-md-8 col-md-offset-2">
		                  				<form action="save_education_detail" method="post" id="educationForm" name="educationForm" class="validateForm form-horizontal">
		                  					<input type="hidden" name="type" value="education"></input>
											<input type="hidden" name="id" id="eduId" value=""></input>
											<input type="hidden" name="kol_id" id="kolId" value="<?php echo $arrKol['vid']?>"></input>
											<div class="form-group">
												<div class="col-md-10">
													<label class="control-label">Name <span class="required">*</span> :</label>
													<input type="hidden" name="institute_id" id="eduInstituteId" value=""></input>
													<input type="text" name="institute_name" value="" id="eduInstituteName" class="required institute_name form-control" />
													<div id="eduInstituteNameNotFound" class="instNotFound">Sorry! The institute name is not found in our database. <a href="#" class="InstitutionProfileLink">Click here</a> to add this institute</div>
												</div>
											</div>
											<div class="form-group">
												<div class="col-md-4">
													<label class="control-label">Degree :</label>
													<input type="text" name="degree" value="" id="eduDegree" class="form-control"></input>
												</div>
												<div class="col-md-4 col-md-offset-2">
													<label class="control-label">Specialty :</label>
													<input type="text" name="specialty" value="" id="eduSpecialty" class="form-control"></input>
												</div>
											</div>
											<div class="form-group">
												<div class="col-md-4">
													<label class="control-label">Start :</label>
													<input type="text" name="start_date" value="" id="eduStartDate" maxlength="4" onkeyup="allowNumericOnly(this)" class="fullYear form-control"></input>
												</div>
												<div class="col-md-4 col-md-offset-2">
													<label class="control-label">End :</label>
													<input type="text" name="end_date" value="" id="eduEndDate" maxlength="4" onkeyup="allowNumericOnly(this)" class="fullYear form-control" onmouseout="validate(this.value,'edu','saveEducation')"></input>
												</div>
												<div id="eduDate" class="instNotFound">Invalid Year.End Year Should be Greater than Start Year</div>
											</div>
											<div class="form-group">
												<div class="col-md-4">
													<label class="control-label">Url1 :</label>
													<input type="text" name="url1" value="" id="eduUrl1" class="url form-control"></input>
												</div>
												<div class="col-md-4 col-md-offset-2">
													<label class="control-label">Url2 :</label>
													<input type="text" name="url2" value="" id="eduUrl2" class="url form-control"></input>
												</div>
											</div>
											<div class="form-group">
												<div class="col-md-10">
													<label class="control-label">Notes :</label>
													<textarea id="eduNotes" rows="2" cols="40" name="notes" class="analystNotes form-control"></textarea>
												</div>
											</div>  
											<div style="text-align: center;">
												<input type="button" value="Add" name="submit" id="saveEducation" class="btn btn-primary pull-center"/>
											</div>
		                  				</form>
	                  				</div>
	                  			</div> 
	                  			</div>
	                  		</div>	
                  </div>
                  <div role="tabpanel" class="tab-pane" id="training">
                  			<div class="panel panel-default"> 
	                  			<div class="panel-heading">
									<h3 class="panel-title"><a class="colpsible-panel collapsed" data-toggle="collapse" data-parent="#accordion" href="#trainingToggle" title="Click to Expand">Add Training Details</a></h3> 
	                  				</a>
	                  			</div> 
	                  			<div id="trainingToggle" class="panel-collapse collapse">
	                  			<div class="panel-body">
	                  				<div class="col-md-8 col-md-offset-2">
		                  				<form action="save_education_detail" method="post" id="trainingForm" name="trainingForm" class="validateForm form-horizontal">
		                  					<input type="hidden" name="type" value="education"></input>
											<input type="hidden" name="id" id="eduId" value=""></input>
											<input type="hidden" name="kol_id" id="kolId" value="<?php echo $arrKol['vid']?>"></input>
											<div class="form-group">
												<div class="col-md-10">
													<label class="control-label">Name <span class="required">*</span> :</label>
													<input type="hidden" name="institute_id" id="trainInstituteId" value=""></input>
														<input type="text" name="institute_name" value="" id="trainInstituteName" class="required institute_name form-control"></input>
													<div id="trainInstituteNameNotFound" class="instNotFound">Sorry! The institute name is not found in our database. <a href="#" class="InstitutionProfileLink">Click here</a> to add this institute</div>
												</div>
											</div>
											<div class="form-group">
												<div class="col-md-4">
													<label class="control-label">Degree :</label>
													<input type="text" name="degree" value="" id="trainDegree" class="form-control"></input>
												</div>
												<div class="col-md-4 col-md-offset-2">
													<label class="control-label">Specialty :</label>
													<input type="text" name="specialty" value="" id="trainSpecialty" class="form-control"></input>
												</div>
											</div>
											<div class="form-group">
												<div class="col-md-4">
													<label class="control-label">Start :</label>
													<input type="text" name="start_date" value="" id="trainStartDate" maxlength="4" onkeyup="allowNumericOnly(this)" class="fullYear form-control"></input>
												</div>
												<div class="col-md-4 col-md-offset-2">
													<label class="control-label">End :</label>
													<input type="text" name="end_date" value="" id="trainEndDate" maxlength="4" onkeyup="allowNumericOnly(this)" class="fullYear form-control" onmouseout="validate(this.value,'train','saveTraining')"></input>
												</div>
												<div id="trainDate" class="instNotFound">Invalid Year.End Year Should be Greater than Start Year</div>
											</div>
											<div class="form-group">
												<div class="col-md-4">
													<label class="control-label">Url1 :</label>
													<input type="text" name="url1" value="" id="trainUrl1" class="url form-control"></input>
												</div>
												<div class="col-md-4 col-md-offset-2">
													<label class="control-label">Url2 :</label> 
													<input type="text" name="url2" value="" id="trainUrl2" class="url form-control"></input>
												</div>
											</div>
											<div class="form-group">
												<div class="col-md-10">
													<label class="control-label">Notes :</label>
													<textarea id="trainNotes" rows="2" cols="40" name="notes" class="analystNotes form-control"></textarea>
												</div>
											</div>  
											<div style="text-align: center;">
												<input type="button" value="Add" name="submit" id="saveTraining" class="btn btn-primary pull-center"/>
											</div>
		                  				</form>
	                  				</div>
	                  			</div> 
	                  			</div>
	                  		</div>
                  </div>
                  <div role="tabpanel" class="tab-pane" id="board-certification">
                  			<div class="panel panel-default"> 
	                  			<div class="panel-heading">
									<h3 class="panel-title"><a class="colpsible-panel collapsed" data-toggle="collapse" data-parent="#accordion" href="#boardCertificationToggle" title="Click to Expand">Add Board Certification Details</a></h3> 
	                  				</a>
	                  			</div> 
	                  			<div id="boardCertificationToggle" class="panel-collapse collapse">
	                  			<div class="panel-body">
	                  				<div class="col-md-8 col-md-offset-2">
	                  					<form action="save_education_detail" method="post" id="boardForm" name="boardForm" class="validateForm form-horizontal">
	                  						<input type="hidden" name="type" value="board_certification"></input>
											<input type="hidden" name="id" id="boardId" value=""></input>
											<input type="hidden" name="kol_id" id="kolId" value="<?php echo $arrKol['vid']?>"></input>
											<div class="form-group">
												<div class="col-md-4">
													<label class="control-label">Name <span class="required">*</span> :</label>
													<input type="hidden" name="institute_id" id="boardInstituteId" value=""></input>
													<input type="text" name="institute_name" value="" id="boardInstituteName" class="required form-control"></input>
													<div id="boardInstituteNameNotFound" class="instNotFound">Sorry! The institute name is not found in our database. <a href="#" class="InstitutionProfileLink">Click here</a> to add this institute</div>
												</div>
												<div class="col-md-4 col-md-offset-2">
													<label class="control-label">Specialty :</label>
													<input type="text" name="specialty" value="" id="boardSpecialty" class="form-control"></input>
												</div>
											</div>
											<div class="form-group">
												<div class="col-md-4">
													<label class="control-label">Start :</label>
													<input type="text" name="start_date" value="" id="boardStartDate" maxlength="4" onkeyup="allowNumericOnly(this)" class="fullYear form-control"></input>
												</div>
												<div class="col-md-4 col-md-offset-2">
													<label class="control-label">End :</label>
													<input type="text" name="end_date" value="" id="boardEndDate" maxlength="4" onkeyup="allowNumericOnly(this)" class="fullYear form-control" onmouseout="validate(this.value,'board','saveBoard')"></input>
												</div>
												<div id="boardDate" class="instNotFound">Invalid Year.End Year Should be Greater than Start Year</div>
											</div>
											<div class="form-group">
												<div class="col-md-4">
													<label class="control-label">Url1 :</label>
													<input type="text" name="url1" value="" id="boardUrl1" class="url form-control"></input>
												</div>
												<div class="col-md-4 col-md-offset-2">
													<label class="control-label">Url2 :</label>
													<input type="text" name="url2" value="" id="boardUrl2" class="url form-control"></input>
												</div>
											</div>
											<div class="form-group">
												<div class="col-md-10">
													<label class="control-label">Notes :</label>
													<textarea id="boardNotes" rows="2" cols="40" name="notes" class="analystNotes form-control"></textarea>
												</div>
											</div>  
											<div style="text-align: center;">
												<input type="button" value="Add" name="submit" id="saveBoard" class="btn btn-primary pull-center"/>
											</div>
	                  					</form>
	                  				</div>
	                  			</div> 
	                  			</div>
	                  		</div>
                  </div>
                  <div role="tabpanel" class="tab-pane" id="honors-awards">
                  			<div class="panel panel-default"> 
	                  			<div class="panel-heading">
									<h3 class="panel-title"><a class="colpsible-panel collapsed" data-toggle="collapse" data-parent="#accordion" href="#honorsAwardsToggle" title="Click to Expand">Add Honors and Awards Detail</a></h3> 
	                  				</a>
	                  			</div> 
	                  			<div id="honorsAwardsToggle" class="panel-collapse collapse">
	                  			<div class="panel-body">
	                  				<div class="col-md-8 col-md-offset-2">
	                  					<form action="save_education_detail" method="post" id="awardForm" name="awardForm" class="validateForm form-horizontal">
	                  						<input type="hidden" name="type" value="honors_awards"></input>
											<input type="hidden" name="id" id="awardId" value=""></input>
											<input type="hidden" name="kol_id" id="kolId" value="<?php echo $arrKol['vid']?>"></input>
											<div class="form-group">
												<div class="col-md-10">
													<label class="control-label">Name <span class="required">*</span> :</label>
													<input type="text" name="honor_name" value="" id="awardHonorName" class="required institute_name form-control"></input>
												</div>
											</div>
											<div class="form-group">
												<div class="col-md-4">
													<label class="control-label">Start :</label>
													<input type="text" name="start_date" value="" id="awardStartDate" maxlength="4" onkeyup="allowNumericOnly(this)" class="fullYear form-control"></input>
												</div>
												<div class="col-md-4 col-md-offset-2">
													<label class="control-label">End :</label>
													<input type="text" name="end_date" value="" id="awardEndDate" maxlength="4" onkeyup="allowNumericOnly(this)" class="fullYear form-control" onmouseout="validate(this.value,'award','saveAward')"></input>
												</div>
												<div id="awardDate" class="instNotFound">Invalid Year.End Year Should be Greater than Start Year</div>
											</div>
											<div class="form-group">
												<div class="col-md-4">
													<label class="control-label">Url1 :</label>
													<input type="text" name="url1" value="" id="awardUrl1" class="url form-control"></input>
												</div>
												<div class="col-md-4 col-md-offset-2">
													<label class="control-label">Url2 :</label>
													<input type="text" name="url2" value="" id="awardUrl2" class="url form-control"></input>
												</div>
											</div>
											<div class="form-group">
												<div class="col-md-10">
													<label class="control-label">Notes :</label>
													<textarea id="awardNotes" rows="2" cols="40" name="notes" class="analystNotes form-control"></textarea>
												</div>
											</div>  
											<div style="text-align: center;">
												<input type="button" value="Add" name="submit" id="saveAward" class="btn btn-primary pull-center"/>
											</div>
	                  					</form>
	                  				</div>
	                  			</div> 
	                  			</div>
	                  		</div>	
                  </div>
        <!-- End Tab panels --> 
        <!-- Generic Grid Panel to load all four respective grid content --> 
			<div class="col-md-12 gridData">
                  <div class="gridWrapper" id="genericGridContainer">
					<table id="EducationResultSet"></table>
					<div id="listEducationPage"></div>
				  </div>
            </div>
		<!-- End of Grid Panel -->   
               </div>     
        </div>
	</div>
</div>		
<div id="institutionProfile">
	<div id="institutionProfileContainer"></div>
</div>