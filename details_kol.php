<?php
/**
* Description:
*
*  @package application.views.kols
*  @author:Developer
*
*  @created on:Dec 13, 2010
*
*/
$autoSearchOptions = "width: 278, delimiter: /(,|;)\s*/, deferRequestBy: 200, noCache: true, minChars: 3,onSelect : function(event, ui) {doSearchFilter1(-1);}";
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
</style>
<script type="text/javascript" language="javascript">
	//--------------Start of "Jqgrid" methods-----------------------//	
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
	
	function locationTab(){
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
	function deleteSelectedlocation(eduIds){
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
	//--------------End of "Jqgrid" methods-----------------------//
	$(document).ready(function(){				
		$('.nav-tabs li a').click(function(){
			loadSelectedTab(this);
		});
		locationTab();
	});
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
	                  				<form action="save_kol_location" method="post" id="saveKolLocationForm" name="saveKolLocationForm" class="form-horizontal">
	                  					<div class="form-group" style="border-bottom: 1px solid #ccc; padding-bottom:10px;">
											<div class="col-md-12">
												<label class="control-label">Primary Address : </label>
												<input type="checkbox" name="first_name" id="first_name" value="<?php echo $arrKol['first_name'];?>" onkeyup="makeFirstLetterCapltal(this.value,this)" style="vertical-align: middle;margin-top:0px;"></input>
											</div>
										</div>
	                  					<div class="form-group">
											<div class="col-md-6">
												<label class="control-label">Institution <span class="required">*</span> :</label>
												<input type="text" name="primary_email" id="primary_email" value="<?php echo $arrKol['primary_email'];?>" class="form-control email"></input>
											</div>
											<div class="col-md-2">
												<label class="control-label">Institution Type :</label>
												<input type="text" name="primary_phone" id="primary_phone" value="<?php echo $arrKol['primary_phone'];?>" onkeyup="allowNumericOnly1(this)" class="form-control required gray"></input>
											</div>
											<div class="col-md-2">
												<label class="control-label">Department :</label>
												<input type="text" name="fax" id="fax" value="<?php echo $arrKol['fax'];?>" onkeyup="allowNumericOnly(this)" class="form-control"></input>
											</div>
											<div class="col-md-2">
												<label class="control-label">Position :</label>
												<input type="text" name="fax" id="fax" value="<?php echo $arrKol['fax'];?>" onkeyup="allowNumericOnly(this)" class="form-control"></input>
											</div>
										</div>
										<div class="clearfix"></div>
										<div class="form-group">
											<div class="col-md-6">
												<label class="control-label">Address 1 <span class="required">*</span> :</label>
												<input type="text" name="address1" id="address1" value="<?php echo $arrKol['address1'];?>" class="form-control required gray"></input>
											</div>
											<div class="col-md-6">
												<label class="control-label">Address 2 :</label>
														<input type="text" name="address2" id="address2" value="<?php echo $arrKol['address2'];?>" class="form-control gray"></input>
											</div>
										</div>
										<div class="form-group">
										<div class="col-md-3">
											<label class="control-label">Country <span class="required">*</span> :</label>
											<select name="country_id" id="country_id" onchange="getStatesByCountryId();" class="form-control required">
																<option value="">-- Select --</option>
																<?php 
																foreach( $arrCountry as $country){
																	if($country['country_id'] == $arrKol['country_id'])
																		echo '<option value="'.$country['country_id'].'" selected="selected">'.$country['country_name'].'</option>';
																	else
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
																	if($state['state_id'] == $arrKol['state_id'])
																		echo '<option value="'.$state['state_id'].'" selected="selected">'.$state['state_name'].'</option>';
																	else
																		echo '<option value="'.$state['state_id'].'">'.$state['state_name'].'</option>';
																}
																?>
											</select>
											<img id="loadingStates" src="<?php echo base_url()?>/images/ajax_loader_black.gif" style="display:none"/>
										</div>
										<div class="col-md-3">
											<label class="control-label">City <span class="required">*</span> :</label>
											<select name="city_id" id="city_id" class="form-control required">
																<option value="">-- Select City --</option>
																<?php 
																foreach( $arrCities as $city){
																	if($city['city_id'] == $arrKol['city_id'])
																		echo '<option value="'.$city['city_id'].'" selected="selected">'.$city['city_name'].'</option>';
																	else
																		echo '<option value="'.$city['city_id'].'">'.$city['city_name'].'</option>';
																}
																?>												
											</select>
											<img id="loadingCities" src="<?php echo base_url()?>/images/ajax_loader_black.gif" style="display:none"/>
										</div>
										<div class="col-md-3">
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
                  			<div class="col-md-12 gridData">
                  				Grid Data
                  			</div>	
                  </div>
                  <div role="tabpanel" class="tab-pane" id="phone_number">
                  			<div class="panel panel-default"> 
	                  			<div class="panel-heading"> <h3 class="panel-title">Add Phone Number</h3> </div> 
	                  			<div class="panel-body">
	                  			<form action="save_kol_location" method="post" id="saveKolLocationForm" name="saveKolLocationForm" class="form-horizontal">
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
	                  		<div class="col-md-12 gridData">
                  				Grid Data
                  			</div>	
                  </div>
                  <div role="tabpanel" class="tab-pane" id="staff">
                  			<div class="panel panel-default"> 
	                  			<div class="panel-heading"> <h3 class="panel-title">Add Staff Details</h3> </div> 
	                  			<div class="panel-body">
	                  				<form action="save_kol_location" method="post" id="saveKolLocationForm" name="saveKolLocationForm" class="form-horizontal">
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
	                  		<div class="col-md-12 gridData">
                  				Grid Data
                  			</div>	
                  </div>
                  <div role="tabpanel" class="tab-pane" id="email">
                  			<div class="panel panel-default"> 
	                  			<div class="panel-heading"> <h3 class="panel-title">Add Emails</h3> </div> 
	                  			<div class="panel-body">
	                  			<form action="save_kol_location" method="post" id="saveKolLocationForm" name="saveKolLocationForm" class="form-horizontal">
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
	                  		<div class="col-md-12 gridData">
                  				Grid Data
                  			</div>
                  </div>
                  <div role="tabpanel" class="tab-pane" id="state_license">
                  			<div class="panel panel-default"> 
	                  			<div class="panel-heading"> <h3 class="panel-title">Add State License Details</h3> </div> 
	                  			<div class="panel-body">
	                  			<form action="save_kol_location" method="post" id="saveKolLocationForm" name="saveKolLocationForm" class="form-horizontal">
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
	                  		<div class="col-md-12 gridData">
                  				Grid Data
                  			</div>
                  </div>
                  <div role="tabpanel" class="tab-pane" id="assign_profile">
                  			<div class="panel panel-default"> 
	                  			<div class="panel-heading"> <h3 class="panel-title">Add Assign Profile</h3> </div> 
	                  			<div class="panel-body">
	                  				<form action="save_kol_location" method="post" id="saveKolLocationForm" name="saveKolLocationForm" class="form-horizontal">
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
	                  		<div class="col-md-12 gridData">
                  				Grid Data
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
