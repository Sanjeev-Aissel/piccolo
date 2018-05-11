<?php 
/**
 * File to 'add and list' kols details from analyst application
 *
 * @author: Sanjeev K
 * @package application.module.views.kols.details_kol
 * @version HMVC 1.0
 * @created on: 29-04-2018
 */
$autoSearchOptions = "width: 278, delimiter: /(,|;)\s*/, deferRequestBy: 200, noCache: true, minChars: 3,onSelect : function(event, ui) {doSearchFilter1(-1);}";
?>
<script>
//autocomplete script
var organizationNameAutoCompleteOptions = {
        serviceUrl: '<?php echo base_url(); ?>kols/get_organization_names/1',
					<?php echo $autoSearchOptions; ?>,
                onSelect: function (event, ui) {                    	
                    var selText = $(event).children('.organizations').html();
                    var selId = $(event).children('.organizations').attr('name');
                    console.log(selId);
                    selText = selText.replace(/\&amp;/g, '&');
                    $('#saveKolLocationForm #organizationLocation').val(selText);
                    $('input[name="org_institution_id"]').val(selId);
//                     $('#saveKolLocationForm #org_institution_id').val(selId);
                    if (event.length > 20) {
                        if (event.substring(0, 21) == "No results found for ") {
                            $('#saveKolLocationForm #organizationLocation').val(trim(split(' ', selText)[4]));
                            return false;
                        }
                    }                       
                }
    };
    
//Validation rules for Location
		var validationLocationRules	=  {
			organization: {
				required:true	
			},
			address1: {
				required:true
			},
			country_id: {
				required:true
			},
			state_id: {
				required:true
			},
			city_id: {
				required:true
			}			
		};
		//Validation messages for Location
		var validationLocationMessages = {
				organization: {
					required: "Required"
				},
				address1: {
					required: "Required"
				},
				country_id: {
					required: "Required"
				},
				state_id: {
					required: "Required"
				},
				city_id: {
					required: "Required"
				}
			
		};
		
//Load DOM on page gets load
$(document).ready(function(){
	var a = '';
	a = $('#saveKolLocationForm #organizationLocation').autocomplete(organizationNameAutoCompleteOptions);

	$('.nav-tabs li a').click(function(){
		loadSelectedTab(this);
	});
	
	locationTab();
	
	/**
	* Save the 'Location Details'
	*/
	$("#saveLocationInfo").click(function(){
			// Disable the SAVE Button
			disableButton("saveLocationInfo");
			$("#saveKolLocationForm").validate().resetForm();
			
			if(!$("#saveKolLocationForm").validate().form()){
				enableButton("saveLocationInfo");
				return false;
			}else{
				var kolId = $('#kolId').val();
				$("#msgBox").html('<div class="alert alert-info">Saving the data... <img src="'+base_url +'assets/images/ajax_loader_black.gif"></div>');
				if (!$("#saveKolLocationForm").validate().form()){
			        return false;
			    }
				$.ajax({
			        url: base_url+'kols/save_location/'+kolId,
			        type: 'post',
			        dataType: 'json',
			      	data: $('#saveKolLocationForm').serialize(),
			        success: function (returnData) {
			          if(returnData.status){
			        	  enableButton("saveLocationInfo");
			        	  $("#msgBox").html('');
			        	  $("#genericGridContainer").html('<table id="LocationResultSet"></table><div id="listLocationPage"></div>');
			        	  locationTab();
			          }else{
			        	  $("#msgBox").html('<div class="alert alert-danger">Kol with same details already exists..</div>');
			        		return false;
			          }
			        }
				});
				}
	});		
});
$(function(){
	$("#saveKolLocationForm").validate({
		debug:true,
		//onkeyup:true,
		rules: validationLocationRules,
		messages: validationLocationMessages
	});
});	
//Javascript Functions goes here
function disableButton(buttonId){
	$("#"+buttonId).attr("disabled", "disabled");
}

function enableButton(buttonId){
	$("#"+buttonId).removeAttr("disabled");
}	
function getStatesByCountryId() {   //get states names of that country ,onchange/selecting of a country.
    $("#loadingStates").show();
    var countryId = $('#country_id').val();
    if(countryId!=0){
	    var params = "country_id=" + countryId;
	    var states = document.getElementById('state_id');
	    $.ajax({
	        url: base_url+'helpers/country_helpers/get_states_by_countryid',
	        dataType: "json",
	        data: params,
	        type: "POST",
	        success: function (responseText) {
	        	$("#state_id").html('');
	        	$("#state_id").append("<option value=''>-- Select State --</option>");
	            $.each(responseText, function (key, value) {
	                $('#state_id').append("<option value='"+value.state_id+"'>"+value.state_name+"</option>");
	            });
	        },
	        complete: function () {
	            $("#loadingStates").hide();
	        }
	    });
    }else
    	{
    	$("#state_id").html('');
    	$("#state_id").append("<option value=''>-- Select State --</option>");
    	}
}
function getCitiesByStateId() {   //get states names of that country ,onchange/selecting of a country.
    $("#loadingStates").show();
    var stateId = $("#state_id").val();
    if(stateId!=0){
	    var params = "state_id=" + stateId;
	    var cities = document.getElementById('city_id');
	    $.ajax({
	        url: base_url+'helpers/country_helpers/get_cities_by_stateid',
	        dataType: "json",
	        data: params,
	        type: "POST",
	        success: function (responseText) {
	        	$("#city_id").html('');
	        	$("#city_id").append("<option value=''>-- Select City --</option>");
	            $.each(responseText, function (key, value) {
	                $('#city_id').append("<option value='"+value.city_id+"'>"+value.city_name+"</option>");
	            });
	        },
	        complete: function () {
	            $("#loadingStates").hide();
	        }
	    });
    }else{
    	$("#city_id").html('');
    	$("#city_id").append("<option value=''>-- Select City --</option>");
	}
}
//Grid Function Goes Here
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
			   	multiselect: false
		};
function locationTab(){
	/*
	*jqgrid for Education table
	*/
	var kolId = $('#kolId').val();
	var locationGridConfiguration = {
			url: base_url+'kols/list_locations/'+ kolId,
			colNames: ['Id', '', '', 'Institution', 'Address', 'City','State', 'Postal Code', 'Address Type', '','','Action','created_by_full_name','client_id','data_type_indicator'],
		    colModel: [
		        {name: 'id', index: 'id', hidden: true},
		        {name: 'micro', resizable: true, width: 20, sortable: false},
		        {name: 'is_primary', index: 'is_primary', resizable: true, width: 20, sortable: false},
		        {name: 'org_name', index: 'org_name', resizable: true, width: 200},
		        {name: 'address', index: 'address', resizable: true, width: 200},
		        {name: 'city', index: 'city', resizable: false, width: 80},
		        {name: 'state', index: 'state', resizable: false, width: 80},
		        {name: 'postal_code', index: 'postal_code', resizable: true, width: 80},
		        {name: 'address_type', index: 'address_type', resizable: false, width: 100,hidden:true},
		        {name: 'created_by', index: 'created_by', hidden: true},
		        {name: 'is_primary_value', index: 'is_primary_value', hidden: true},
		        {name: 'act', resizable: true, width: 70, sortable: false},
		        {name:'created_by_full_name',index:'created_by_full_name', hidden:true},
		        {name:'client_id',index:'client_id',width:175, resizable:false,search:false,hidden:true},
		   		{name:'data_type_indicator',index:'data_type_indicator', hidden:true}
		    ],
		    pager: '#listLocationPage',
		   	sortname: 'date',
		    gridComplete: function () {
	            var ids =$("#LocationResultSet").jqGrid('getDataIDs');
	            for (var i = 0; i < ids.length; i++) {
	            	var primaryLable = '';
	                var actionLink = '';
	                var id = ids[i];
//	                console.log(id);
//	                var isAnalyst = grid.jqGrid('getCell',cl,'client_id');
//	                var createdByName = grid.jqGrid('getCell',cl,'created_by_full_name');
	                var rowData =$("#LocationResultSet").jqGrid('getRowData', id);
		    		var dataTypeIndicator =$("#LocationResultSet").jqGrid('getCell',id,'data_type_indicator');
		    		actionLink += data_type_indicator(dataTypeIndicator);	
			    		
	                var is_prime = '';
	                if(rowData.is_primary_value == 1){
	                	is_prime += "<span class='is_primary'></span>";
	                     $("#LocationResultSet").jqGrid('setRowData', id, {is_primary: is_prime});
	                }
	                actionLink += "<a onclick='addLocation("+id+");return false;'><span class='glyphicon glyphicon-edit action_icons'></span></a><a onclick='deleteLocation("+id+","+rowData.is_primary_value+");return false;'><span class='glyphicon glyphicon-remove-circle action_icons'></span></a>";
	                $("#LocationResultSet").jqGrid('setRowData', id, {act: actionLink});
	                microviewLink = "<label><div class='tooltip-demo tooltop-right microViewIcon' onclick=\"viewLocationSnapshot('" + id + "'); return false;\" ><a href=\"#\" class=\"tooltipLink\" rel='tooltip' title=\"Location Snapshot\"></a></div></label>";
	                $("#LocationResultSet").jqGrid('setRowData', id, {micro: microviewLink});
	            }
	            	$("#LocationResultSet").jqGrid('navGrid', 'hideCol', "id");
	        }, 	
		    editurl:"<?php echo base_url();?>kols/update_education_detail",		   
		    caption:"Locations"
			};

			$.extend(locationGridConfiguration, commonJQGridConfiguration);

			jQuery("#LocationResultSet").jqGrid(locationGridConfiguration);

			jQuery("#LocationResultSet").jqGrid('navGrid','#listLocationPage',{edit:false,add:false,del:false,search:false,refresh:false});	

			//Toolbar search bar below the Table Headers
			jQuery("#LocationResultSet").jqGrid('filterToolbar',{stringResult: true,searchOnEnter : false, defaultSearch:"cn"}); 		
			//Toolbar search bar above the Table Headers
			//jQuery("#t_EducationResultSet").height(25).jqGrid('filterGrid',"EducationResultSet",{gridModel:true,gridToolbar:true});
			jQuery("#LocationResultSet").jqGrid('gridResize',{'minWidth':550, 'maxWidth':2000}); 		

			// Delete selected row(s)
			jQuery("#LocationResultSet").jqGrid('navButtonAdd',"#listLocationPage",{caption:"Delete",buttonicon:"ui-icon-trash",title:"Delete Select Row(s)",
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
			jQuery("#LocationResultSet").jqGrid('navButtonAdd',"#listLocationPage",{caption:"Search",title:"Toggle Search",
				onClickButton:function(){ 			
					if(jQuery(".ui-search-toolbar").css("display")=="none") {
						jQuery(".ui-search-toolbar").css("display","");
					} else {
						jQuery(".ui-search-toolbar").css("display","none");
					}
					
				} 
			});	    
	}
function deleteSelectedlocation(lcoationIds){
	jConfirm("Are you sure you want to delete selected Location?","Please confirm",function(r){
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
function staffTab(){

}

function emailTab(){

}

function stateLicenseTab(){

}

function assignProfileTab(){

}
//Function to toggle 
function loadSelectedTab(selected){
				var sel= $(selected).attr('aria-controls');
				switch(sel){
					case 'location': $("#genericGridContainer").html("");
							
							// Append the required div and table
							$("#genericGridContainer").html('<table id="LocationResultSet"></table><div id="listLocationPage"></div>');

							locationTab();
							break;
					
					case 'phone_number': $("#genericGridContainer").html("test");
							// Append the required div and table
							$("#genericGridContainer").html('<table id="PhoneNumberResultSet"></table><div id="listPhoneNumberPage"></div>');
							phoneNumberTab();
							break;
					
					case 'staff': $("#genericGridContainer").html("");
							
							// Append the required div and table
							$("#genericGridContainer").html('<table id="StaffResultSet"></table><div id="listStaffPage"></div>');

							staffTab();
							break;
									
					case 'email': $("#genericGridContainer").html("");
							
							// Append the required div and table
							$("#genericGridContainer").html('<table id="EmailResultSet"></table><div id="listEmailPage"></div>');

							emailTab();
							break;	
					case 'state_license': $("#genericGridContainer").html("");
					
							// Append the required div and table
							$("#genericGridContainer").html('<table id="StatelicenseResultSet"></table><div id="listStatelicensePage"></div>');
		
							stateLicenseTab();
							break;
					case 'assign_profile': $("#genericGridContainer").html("");
					
							// Append the required div and table
							$("#genericGridContainer").html('<table id="AssignProfileResultSet"></table><div id="listAssignProfilePage"></div>');
		
							assignProfileTab();
							break;		
								
				}
			}

</script>
<!-- Start of Html Content -->
<?php $this->load->view('kols/secondary_menu'); ?>
<div class="main-content">
	<div class="row">
		<div class="col-md-12">
        <!-- Start Nav tabs -->
               <ul class="nav nav-tabs" role="tablist">
                  <li role="Details" class="active"><a href="#location" aria-controls="location" role="tab" data-toggle="tab">Locations</a></li>
                  <li role="Details"><a href="#phone_number" aria-controls="phone_number" role="tab" data-toggle="tab">Phone Numbers</a></li>
                  <li role="Details"><a href="#staff" aria-controls="staff" role="tab" data-toggle="tab">Staff</a></li>
                  <li role="Details"><a href="#email" aria-controls="email" role="tab" data-toggle="tab">Emails</a></li>
                  <li role="Details"><a href="#state_license" aria-controls="state_license" role="tab" data-toggle="tab">State License</a></li>
                  <li role="Details"><a href="#assign_profile" aria-controls="assign_profile" role="tab" data-toggle="tab">Assign Profile</a></li>
               </ul>
		<!-- End Nav tabs -->
               <div class="tab-content">
	               <div>
	               	<h5 style="font-weight:bold;color:#656565;">Profile of : Kols Name</h5>
	               </div>
        <!-- Start Tab panels -->
                  <div role="tabpanel" class="tab-pane active" id="location">
                  			<div class="panel panel-default"> 
	                  			<div class="panel-heading"> <h3 class="panel-title">Add Location Information</h3> </div> 
	                  			<div class="panel-body">
	                  				<form action="save_kol_location" method="post" id="saveKolLocationForm" name="saveKolLocationForm" class="validateForm form-horizontal">
	                  					<input type="hidden" name="type" value="location"></input>
										<input type="hidden" name="id" id="locationId" value=""></input>
										<input type="hidden" name="kol_id" id="kolId" value="<?php echo $arrKol['vid']; ?>"></input>
	                  					<input type="hidden" name="org_inst_selected" value="">
										<input type="hidden" name="org_institution_id" value="">
										<input type="hidden" name="private_practice" value="1">
	                  					<div class="form-group" style="border-bottom: 1px solid #ccc; padding-bottom:10px;">
											<div class="col-md-12">
												<label class="control-label">Primary Address : </label>
												<input type="checkbox" name="is_primary" value="1" id="is_primary" style="vertical-align: middle;margin-top:0px;"></input>
											</div>
										</div>
	                  					<div class="form-group">
											<div class="col-md-6">
												<label class="control-label">Institution <span class="required">*</span> :</label>
												<input type="text" name="organization" value="" id="organizationLocation" class="form-control autocompleteInputBox" placeholder="Enter Organization" autocomplete="off">
											</div>
											<div class="col-md-2">
												<label class="control-label">Institution Type :</label>
												<select name="org_type" id="org_typeLocation" class="required form-control" onchange="changeAutoComplete();">
												<option value="" selected="selected">---Select---</option>
												<?php 
													foreach( $arrOrganizationTypes as $organizationTypeKey => $organizationTypeValue ){
															echo '<option value="'.$organizationTypeKey.'">'.$organizationTypeValue.'</option>';
													}
												?>
												</select>
											</div>
											<div class="col-md-2">
												<label class="control-label">Department :</label>
												<input type="text" name="department_loc" value="" id="department_loc" class="form-control"></input>
											</div>
											<div class="col-md-2">
												<label class="control-label">Position :</label>
												<select name="title_loc" id="title_loc" class="chosenMultipleSelect form-control">
												<option value="" selected="selected">---Select---</option>
												<?php 
													foreach( $arrTitles as $titleKey => $titleValue ){
															echo '<option value="'.$titleKey.'">'.$titleValue.'</option>';
													}
												?>
												</select>
											</div>
										</div>
										<div class="clearfix"></div>
										<div class="form-group">
											<div class="col-md-6">
												<label class="control-label">Address 1 <span class="required">*</span> :</label>
												<input type="text" name="address1" value="" id="address1" class="form-control required gray"></input>
											</div>
											<div class="col-md-6">
												<label class="control-label">Address 2 :</label>
														<input type="text" name="address2" value="" id="address2" class="form-control gray"></input>
											</div>
										</div>
										<div class="form-group">
										<div class="col-md-3">
											<label class="control-label">Country <span class="required">*</span> :</label>
											<select name="country_id" id="country_id" onchange="getStatesByCountryId();" class="form-control">
																<option value="">-- Select --</option>
																<?php 
																foreach( $arrCountry as $country){
																		echo '<option value="'.$country['country_id'].'">'.$country['country_name'].'</option>';
																}
																?>
															</select>
										</div>
										<div class="col-md-3">
											<label class="control-label">State <span class="required">*</span> :</label>
											<select name="state_id" id="state_id" onchange="getCitiesByStateId();" class="form-control">		
																<option value="">-- Select State --</option>
																<?php 
																foreach( $arrStates as $state){
																		echo '<option value="'.$state['state_id'].'">'.$state['state_name'].'</option>';
																}
																?>
											</select>
											<img id="loadingStates" src="<?php echo base_url()?>/images/ajax_loader_black.gif" style="display:none"/>
										</div>
										<div class="col-md-3">
											<label class="control-label">City <span class="required">*</span> :</label>
											<select name="city_id" id="city_id" class="form-control">
																<option value="">-- Select City --</option>
																<?php 
																foreach( $arrCities as $city){
																		echo '<option value="'.$city['city_id'].'">'.$city['city_name'].'</option>';
																}
																?>												
											</select>
											<img id="loadingCities" src="<?php echo base_url()?>/images/ajax_loader_black.gif" style="display:none"/>
										</div>
										<div class="col-md-3">
											<label class="control-label">Postal Code :</label>
											<input type="text" name="postal_code" value="" id="postal_code" class="form-control"></input>
										</div>
									</div>
									<div style="text-align: center;">
										<input type="button" value="Add" name="submit" id="saveLocationInfo" class="btn btn-primary pull-center"/>
										<!-- button type="button" class="btn btn-primary" name="submit" id="saveLocationInfo" onclick="save_location_form();return false;">Save</button> -->
									</div>
	                  				</form>
	                  			</div> 
	                  		</div>	
                  </div>
                  <div role="tabpanel" class="tab-pane" id="phone_number">
                  			<div class="panel panel-default"> 
	                  			<div class="panel-heading"> <h3 class="panel-title">Add Phone Number</h3> </div> 
	                  			<div class="panel-body">
	                  			<form action="save_kol_location" method="post" id="saveKolLocationForm" name="saveKolLocationForm" class="form-horizontal">
	                  					<input type="hidden" name="type" value="phone_number"></input>
										<input type="hidden" name="id" id="phoneNumberId" value=""></input>
										<input type="hidden" name="kol_id" id="kolId" value="<?php echo $arrKol['vid']?>"></input>
	                  					<div class="form-group" style="border-bottom: 1px solid #ccc; padding-bottom:10px;">
											<div class="col-md-12">
												<label class="control-label">Primary Address : </label>
												<input type="checkbox" name="first_name" id="first_name" value="<?php echo $arrKol['first_name'];?>" onkeyup="makeFirstLetterCapltal(this.value,this)" style="vertical-align: middle;margin-top:0px;"></input>
											</div>
										</div>
										<div class="form-group">
										<div class="col-md-4">
											<label class="control-label">Type <span class="required">*</span> :</label>
											<select name="country_id" id="country_id" onchange="getStatesByCountryId();" class="form-control required">
																<option value="">-- Select --</option>
															</select>
										</div>
										<div class="col-md-4">
											<label class="control-label">Location <span class="required">*</span> :</label>
											<select name="state_id" id="state_id" onchange="getCitiesByStateId();" class="form-control">		
																<option value="">-- Select State --</option>
											</select>
											</div>
										<div class="col-md-4">
											<label class="control-label">Postal Code :</label>
											<input type="text" name="postal_code" id="postal_code" value="<?php echo $arrKol['postal_code'];?>" class="form-control"></input>
										</div>
									</div>
									<div style="text-align: center;">
										<input type="button" value="Save" name="submit" id="saveContactInfo" class="btn btn-primary"></input>
									</div>
	                  				</form>
	                  			</div> 
	                  		</div>	
                  </div>
                  <div role="tabpanel" class="tab-pane" id="staff">
                  			<div class="panel panel-default"> 
	                  			<div class="panel-heading"> <h3 class="panel-title">Add Staff Details</h3> </div> 
	                  			<div class="panel-body">
	                  				<form action="save_kol_location" method="post" id="saveKolLocationForm" name="saveKolLocationForm" class="form-horizontal">
										<input type="hidden" name="type" value="staff"></input>
										<input type="hidden" name="id" id="staffId" value=""></input>
										<input type="hidden" name="kol_id" id="kolId" value="<?php echo $arrKol['vid']?>"></input>	
										<div class="form-group">
											<div class="col-md-4">
												<label class="control-label">Title <span class="required">*</span> :</label>
												<select name="country_id" id="country_id" onchange="getStatesByCountryId();" class="form-control required">
																	<option value="">-- Select --</option>
																</select>
											</div>
											<div class="col-md-4">
												<label class="control-label">Name <span class="required">*</span> :</label>
												<select name="state_id" id="state_id" onchange="getCitiesByStateId();" class="form-control">		
																	<option value="">-- Select State --</option>
												</select>
												</div>
											<div class="col-md-4">
												<label class="control-label">Email :</label>
												<input type="text" name="postal_code" id="postal_code" value="<?php echo $arrKol['postal_code'];?>" class="form-control"></input>
											</div>
										</div>
										<div class="form-group">
											<div class="col-md-4">
												<label class="control-label">Location <span class="required">*</span> :</label>
												<select name="country_id" id="country_id" onchange="getStatesByCountryId();" class="form-control required">
																	<option value="">-- Select --</option>
																</select>
											</div>
											<div class="col-md-4">
												<label class="control-label">Phone Type <span class="required">*</span> :</label>
												<select name="state_id" id="state_id" onchange="getCitiesByStateId();" class="form-control">		
																	<option value="">-- Select State --</option>
												</select>
												</div>
											<div class="col-md-4">
												<label class="control-label">Phone Number :</label>
												<input type="text" name="postal_code" id="postal_code" value="<?php echo $arrKol['postal_code'];?>" class="form-control"></input>
											</div>
									</div>
									<div style="text-align: center;">
										<input type="button" value="Save" name="submit" id="saveContactInfo" class="btn btn-primary"></input>
									</div>
	                  				</form>
	                  			</div> 
	                  		</div>		
                  </div>
                  <div role="tabpanel" class="tab-pane" id="email">
                  			<div class="panel panel-default"> 
	                  			<div class="panel-heading"> <h3 class="panel-title">Add Emails</h3> </div> 
	                  			<div class="panel-body">
	                  			<form action="save_kol_location" method="post" id="saveKolLocationForm" name="saveKolLocationForm" class="form-horizontal">
	                  					<input type="hidden" name="type" value="email"></input>
										<input type="hidden" name="id" id="emailId" value=""></input>
										<input type="hidden" name="kol_id" id="kolId" value="<?php echo $arrKol['vid']?>"></input>		
	                  					<div class="form-group" style="border-bottom: 1px solid #ccc; padding-bottom:10px;">
											<div class="col-md-12">
												<label class="control-label">Primary Address : </label>
												<input type="checkbox" name="first_name" id="first_name" value="<?php echo $arrKol['first_name'];?>" onkeyup="makeFirstLetterCapltal(this.value,this)" style="vertical-align: middle;margin-top:0px;"></input>
											</div>
										</div>
										<div class="form-group">
											<div class="col-md-6">
												<label class="control-label">Type<span class="required">*</span> :</label>
												<input type="text" name="address1" id="address1" value="<?php echo $arrKol['address1'];?>" class="form-control required gray"></input>
											</div>
											<div class="col-md-6">
												<label class="control-label">Email :</label>
														<input type="text" name="address2" id="address2" value="<?php echo $arrKol['address2'];?>" class="form-control gray"></input>
											</div>
										</div>
									<div style="text-align: center;">
										<input type="button" value="Save" name="submit" id="saveContactInfo" class="btn btn-primary"></input>
									</div>
	                  				</form>
	                  			</div> 
	                  		</div>	
                  </div>
                  <div role="tabpanel" class="tab-pane" id="state_license">
                  			<div class="panel panel-default"> 
	                  			<div class="panel-heading"> <h3 class="panel-title">Add State License Details</h3> </div> 
	                  			<div class="panel-body">
	                  			<form action="save_kol_location" method="post" id="saveKolLocationForm" name="saveKolLocationForm" class="form-horizontal">
	                  					<input type="hidden" name="type" value="state_license"></input>
										<input type="hidden" name="id" id="stateLicenseId" value=""></input>
										<input type="hidden" name="kol_id" id="kolId" value="<?php echo $arrKol['vid']?>"></input>		
	                  					<div class="form-group" style="border-bottom: 1px solid #ccc; padding-bottom:10px;">
											<div class="col-md-12">
												<label class="control-label">Primary Address : </label>
												<input type="checkbox" name="first_name" id="first_name" value="<?php echo $arrKol['first_name'];?>" onkeyup="makeFirstLetterCapltal(this.value,this)" style="vertical-align: middle;margin-top:0px;"></input>
											</div>
										</div>
										<div class="form-group">
											<div class="col-md-4">
												<label class="control-label">Number <span class="required">*</span> :</label>
												<select name="country_id" id="country_id" onchange="getStatesByCountryId();" class="form-control required">
																	<option value="">-- Select --</option>
																</select>
											</div>
											<div class="col-md-4">
												<label class="control-label">Country <span class="required">*</span> :</label>
												<select name="state_id" id="state_id" onchange="getCitiesByStateId();" class="form-control">		
																	<option value="">-- Select State --</option>
												</select>
												</div>
											<div class="col-md-4">
												<label class="control-label">State :</label>
												<input type="text" name="postal_code" id="postal_code" value="<?php echo $arrKol['postal_code'];?>" class="form-control"></input>
											</div>
										</div>
									<div style="text-align: center;">
										<input type="button" value="Save" name="submit" id="saveContactInfo" class="btn btn-primary"></input>
									</div>
	                  				</form>
	                  			</div> 
	                  		</div>	
                  </div>
                  <div role="tabpanel" class="tab-pane" id="assign_profile">
                  			<div class="panel panel-default"> 
	                  			<div class="panel-heading"> <h3 class="panel-title">Add Assign Profile</h3> </div> 
	                  			<div class="panel-body">
	                  				<form action="save_kol_location" method="post" id="saveKolLocationForm" name="saveKolLocationForm" class="form-horizontal">
	                  					<input type="hidden" name="type" value="assign_user"></input>
										<input type="hidden" name="id" id="assignUserId" value=""></input>
										<input type="hidden" name="kol_id" id="kolId" value="<?php echo $arrKol['vid']?>"></input>		
	                  					<div class="form-group" style="border-bottom: 1px solid #ccc; padding-bottom:10px;">
											<div class="col-md-12">
												<label class="control-label">Primary Address : </label>
												<input type="checkbox" name="first_name" id="first_name" value="<?php echo $arrKol['first_name'];?>" onkeyup="makeFirstLetterCapltal(this.value,this)" style="vertical-align: middle;margin-top:0px;"></input>
											</div>
										</div>
										<div class="form-group">
											<div class="col-md-6">
												<label class="control-label">User <span class="required">*</span> :</label>
												<select name="country_id" id="country_id" onchange="getStatesByCountryId();" class="form-control required">
																	<option value="">-- Select --</option>
																</select>
											</div>
											<div class="col-md-6">
												<label class="control-label">Type <span class="required">*</span> :</label>
												<select name="state_id" id="state_id" onchange="getCitiesByStateId();" class="form-control">		
																	<option value="">-- Select State --</option>
												</select>
												</div>
										</div>
									<div style="text-align: center;">
										<input type="button" value="Save" name="submit" id="saveContactInfo" class="btn btn-primary"></input>
									</div>
	                  				</form>
	                  			</div> 
	                  		</div>	
                  </div>
        <!-- End Tab panels -->   
        <!-- Generic Grid Panel to load all four respective grid content --> 
			<div class="col-md-12 gridData">
                  <div class="gridWrapper" id="genericGridContainer">
					<table id="LocationResultSet"></table>
					<div id="listLocationPage"></div>
				  </div>
            </div>
		<!-- End of Grid Panel -->    
               </div>     
        </div>
	</div>
</div>
<!-- End of Html Content -->