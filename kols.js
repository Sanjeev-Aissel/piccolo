var modalBoxLayout = {
	title: "",
	modal: true,
	width:400,
	position:['center',100],
	resizable: true
};
var modalBoxLayout2 = {
		title: "",
		modal: true,
		width:750,
		position:['center',100],
		resizable: true
	};
function get_field_value(divID){
	return $("#"+divID).val();
}
function close_dialog(){
	$("#modalContainer").dialog("close");
}
function addName(thisEle) {
	var id = thisEle.options[thisEle.selectedIndex].value;
	var name = thisEle.options[thisEle.selectedIndex].text;
	if(id != "addNew" && id != ''){
		document.getElementsByName("filter_id")[0].value = id;
		$("#filter_name").val(name);
		$("#filter_name").prop('disabled',true);
		$("#filter_name").css('background-color','#d6d6d6');
		$("#btn").attr('value','Update');
		$("#btn").attr('onclick','updateFilters()');
	}else{
		document.getElementsByName("filter_id")[0].value = "";
		$("#filter_name").val("");
		$("#filter_name").css('background-color','#ffffff');
		$("#filter_name").removeAttr('disabled',false);
		$("#btn").attr('value','Save');
		$("#btn").attr('onclick','saveFilters()');
		}
}
function doSearchFilter(offset=0,thisEle){
	var data = $("#searchFilterForm").serializeArray();
	data.push({name: 'page', value: offset});
	data.push({name: 'records_per_page', value: $('#noOfRecordsPerPage').val()});
	$.ajax({
		type:'POST',
		url:base_url+'kols/reload_filters/'+offset,
		dataType:'json',
		data:data,
		success:function(respData){
			if(respData.status){
				 $("#rightSideBarWrapper").html(respData.filter_content);
				 $("#kolsListing").html(respData.kol_listing_content);
			}
			else{
				 $("#rightSideBarWrapper").html('<div class="alert alert-danger" role="alert">Error in loading filters</div>');
			}
		},
		error:function()
		{
			$("#rightSideBarWrapper").html('<div class="alert alert-danger" role="alert">Error......!!!!!</div>');
		}
	});
}
function save_filter(){
	var filter_data=$("#searchFilterForm").serialize();
	$(".modalcontent").html("<div class='microViewLoading'>Loading...</div>");
	$("#userContainer").dialog(modalBoxLayout);
	$('#ui-dialog-title-userContainer').html('Save Filter');
	$(".modalcontent").load(base_url+'kols/modal_to_save_filter',{filter_data:filter_data});
	return false;
}
function activeCustmFilters(){
	var filterId = $("#savedFilterId").val();
	var viewTypeMyKols = $("#viewType").val();
	$.ajax({
		type: "post",
		dataType:"json",
		url: base_url+"kols/get_filter_by_id/"+filterId,
		success: function(returnData){
			if(returnData.status == true){
				returnData.filterData['postData'] += "&saved_filter_id="+Number(filterId);
				returnData.filterData['postData'] += "&viewTypeMyKols="+Number(viewTypeMyKols);
				var newObj = returnData.filterData['postData'].replace("search_type=advanced&","search_type=simple&");
				$.ajax({
					type: "post",
					dataType:'json',
					data: newObj,
					url: base_url+'kols/reload_filters',
					success:function(respData){
						if(respData.status){
							 $("#rightSideBarWrapper").html(respData.filter_content);
							 $("#kolsListing").html(respData.kol_listing_content);
						}
						else{
							 $("#rightSideBarWrapper").html('<div class="alert alert-danger" role="alert">Error in loading filters</div>');
						}
					},
					complete: function(){
					}
				});
			}else{
				jAlert("Refresh the Browse then try once again");
			}
		}
	});
}
function showKolExportBox($kol_id=null){ ///kol_id exists if it is from KOL within profile
	var values = new Array();
	if($kol_id!=null){
		 values.push($kol_id);
	}else{
		$.each($("input[name='list[]']:checked"), function() {
			  values.push($(this).val());
		});
	}
	if(values == ""){
		jAlert("Please select at least one KTL");
		return false;	
	}else if(values.length>10){
		jAlert("Maximum of 10 KTLs can be exported at a time.");
		return false;
	}
	$(".modalcontent").html("<div class='microViewLoading'>Loading...</div>");
	$("#userContainer").dialog(modalBoxLayout);
	$('#ui-dialog-title-userContainer').html('Please select what you want to export?');
	$(".modalcontent").load(base_url+'kols/show_kol_export_opts/'+values);
	return false;
}
function exportPdfProfile(){
	var values = new Array();
	$.each($("input[name='list[]']:checked"), function() {
	  values.push($(this).val());
	});
	if(values == ""){
		jAlert("Please select at least one KTL");
		return false;	
	}
	if(values.length > 1){
		jAlert("Multiple profiles export not allowed");
		return false;	
	}
	var currUrl=base_url+'kols/export_pdf/'+values;
	$("#kolPdfExport").attr("action",currUrl);
	$('#kolPdfExport').submit();
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
    var stateId = get_field_value('state_id');
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
function changeAutoComplete(){
	var orgType = $('#org_type').find(":selected").text();
	if(orgType == 'Private Practice'){
    	$('#private_practice').val('1');
    }else{
    	$('#private_practice').val('0');
    }
}
function showKolDeleteModalBox(kol_id){
	$(".modalcontent").html("<div class='microViewLoading'>Loading...</div>");
	$("#userContainer").dialog(modalBoxLayout);
	$('#ui-dialog-title-userContainer').html('Delete KOL');
	$(".modalcontent").load(base_url+'kols/delete_profile_kol/'+kol_id);
	return false;
}
function getLocationDataOnPageLoad(){
	var kolId =get_field_value('kol_id');
	$('#gridWrapperLocation').html('');
 	$('#gridWrapperLocation').html('<table id="JQBlistLocationResultSet"></table><div id="listLocationDetailsPage"></div>');
 	grid = $("#JQBlistLocationResultSet");
 	$("#JQBlistLocationResultSet").jqGrid({
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
	    rowNum:10,
	   	rownumbers: true,
	   	autowidth: true, 
	   	loadonce:true,
	   	multiselect: false,
	   	ignoreCase:true,
	   	hiddengrid:false,
	   	height: "auto",		   
	   	pager: '#listLocationDetailsPage',
	   	toppager:false,
	   	datatype: "json", 
	   	mtype: "POST",
	   	sortname: 'date',
	    viewrecords: true,
	    sortorder: "desc",
	    jsonReader: { repeatitems : false, id: "0" },
	    rowList:[10,20,30,50,100],
	    caption:"Locations",
        gridComplete: function () {
            var ids =$("#JQBlistLocationResultSet").jqGrid('getDataIDs');
            for (var i = 0; i < ids.length; i++) {
            	var primaryLable = '';
                var actionLink = '';
                var id = ids[i];
//                console.log(id);
//                var isAnalyst = grid.jqGrid('getCell',cl,'client_id');
//                var createdByName = grid.jqGrid('getCell',cl,'created_by_full_name');
                var rowData =$("#JQBlistLocationResultSet").jqGrid('getRowData', id);
                console.log(rowData);
	    		var dataTypeIndicator =grid.jqGrid('getCell',id,'data_type_indicator');
	    		actionLink += data_type_indicator(dataTypeIndicator);	
		    		
                var is_prime = '';
                if(rowData.is_primary_value == 1){
                	is_prime += "<span class='is_primary'></span>";
                     $("#JQBlistLocationResultSet").jqGrid('setRowData', id, {is_primary: is_prime});
                }
                actionLink += "<a onclick='addLocation("+id+");return false;'><span class='glyphicon glyphicon-edit action_icons'></span></a><a onclick='deleteLocation("+id+","+rowData.is_primary_value+");return false;'><span class='glyphicon glyphicon-remove-circle action_icons'></span></a>";
                $("#JQBlistLocationResultSet").jqGrid('setRowData', id, {act: actionLink});
                microviewLink = "<label><div class='tooltip-demo tooltop-right microViewIcon' onclick=\"viewLocationSnapshot('" + id + "'); return false;\" ><a href=\"#\" class=\"tooltipLink\" rel='tooltip' title=\"Location Snapshot\"></a></div></label>";
                $("#JQBlistLocationResultSet").jqGrid('setRowData', id, {micro: microviewLink});
            }
            grid.jqGrid('navGrid', 'hideCol', "id");
        }
	});
}
function getPhoneNumberData(){
	var phoneCount=0;
	var kolId =get_field_value('kol_id');
    $('#gridWrapperPhone').html('');
 	$('#gridWrapperPhone').html('<table id="JQBlistPhoneNumberResultSet"></table>');
 	grid = $("#JQBlistPhoneNumberResultSet");
 	$("#JQBlistPhoneNumberResultSet").jqGrid({
	    	url: base_url + 'kols/list_kol_details/phone/' + kolId,
	   		colNames: ['Id', 'Phone Type', 'Locations', 'Phone Number', 'Is Primary','','Action','created_by_full_name','client_id','data_type_indicator'],
	   		colModel:[
	   				{name:'id',index:'id', hidden:true, search:false,align:'left'},
	   				{name:'phone_type',index:'phone_type',search:true,align:'left'},
	   		   		{name:'name',index:'name',search:true,align:'left'},
	   		   		{name:'number',index:'number',search:true,align:'left'},
	   				{name:'is_primary',index:'is_primary',search:true,align:'center'},
	   				{name:'created_by', index: 'created_by', hidden: true},
//	   				{name:'eAllowed', index: 'eAllowed', hidden: true},
//	   	            {name:'dAllowed', index: 'dAllowed', hidden: true},
	   				{name:'act', resizable: true, width: 60, sortable: false},
	   				{name:'created_by_full_name',index:'created_by_full_name', hidden:true},
	   	            {name:'client_id',index:'client_id',width:175, resizable:false,search:false,hidden:true},
	   		   		{name:'data_type_indicator',index:'data_type_indicator', hidden:true}
	   		   	  ], 
	   		rowNum:10,
	   	   	rownumbers: true,
	   	   	autowidth: true, 
	   	   	loadonce:true,
	   	   	multiselect: false,
	   	   	ignoreCase:true,
	   	   	hiddengrid:false,
	   	   	height: "auto",		   
	   	   	toppager:false,
	   	   	datatype: "json", 
	   	   	mtype: "POST",
	   	   	sortname: 'date',
	   	    viewrecords: true,
	   	    sortorder: "desc",
	   	    jsonReader: { repeatitems : false, id: "0" },
	   	    rowList:[10,20,30,50,100],
           caption:"Phone Numbers",
	           gridComplete: function () {
	               var ids = $("#JQBlistPhoneNumberResultSet").jqGrid('getDataIDs');
	               console.log(ids);
	               for (var i = 0; i < ids.length; i++) {
	            	   var actionLink = '';
	                   var id = ids[i];
	                   var isAnalyst =grid.jqGrid('getCell',id,'client_id');
	                   var createdByName =grid.jqGrid('getCell',id,'created_by_full_name');
	                   var rowData =grid.jqGrid('getRowData', id);
                        	var dataTypeIndicator =grid.jqGrid('getCell',id,'data_type_indicator');
                        	actionLink += data_type_indicator(dataTypeIndicator);		    		
	                   var is_prime = '';
	                   if(rowData.is_primary == 'Yes'){
	                   	is_prime = 1;
	                   }
	                   actionLink += "<a onclick=\"addPhoneNumber('edit',"+id+");return false;\"><span class='glyphicon glyphicon-edit action_icons'></span></a><a onclick=\"addPhoneNumber('delete',"+id+","+is_prime+");return false;\"><span class='glyphicon glyphicon-remove-circle action_icons'></span></a>";
	                   $("#JQBlistPhoneNumberResultSet").jqGrid('setRowData', id, {act: actionLink});
	                   phoneCount++;
	               }
	           }
		}); 
}
function getStaffsData(){
	var kolId =get_field_value('kol_id');
    $('#gridWrapperStaff').html('');
  	$('#gridWrapperStaff').html('<table id="JQBlistStaffResultSet"></table>');
  	grid = $("#JQBlistStaffResultSet");
  	grid.jqGrid({
	       	url: base_url + 'kols/list_kol_details/staff/' + kolId,
	      	colNames: ['Id', 'Title', 'Locations', 'Name','Email', 'Phone Type', 'Phone Number','','','', 'Action','created_by_full_name','client_id','data_type_indicator'],
	      	colModel:[
	      				{name:'id',index:'id', hidden:true, search:false,align:'left'},
	      				{name:'staff_title',index:'staff_title',search:true,align:'left'},
	      		   		{name:'loc_name',index:'loc_name',search:true,align:'left'},
	      		   		{name:'name',index:'name',search:true,align:'left'},
	      		   		{name:'email',index:'email',search:true,align:'left'},
	      		   		{name:'phone_type',index:'phone_type',search:true,align:'left'},
	      				{name:'phone_number',index:'phone_number',search:true,align:'left',width:'100'},
	      				{name: 'created_by', index: 'created_by', hidden: true},
		   				{name: 'eAllowed', index: 'eAllowed', hidden: true},
		   	            {name: 'dAllowed', index: 'dAllowed', hidden: true},
		   	            {name: 'act', resizable: true, width: 80, sortable: false},
		   	         	{name:'created_by_full_name',index:'created_by_full_name', hidden:true},
		   	            {name:'client_id',index:'client_id',width:175, resizable:false,search:false,hidden:true},
		   		   		{name:'data_type_indicator',index:'data_type_indicator', hidden:true}
	      	], 
	      	rowNum:10,
	   	   	rownumbers: true,
	   	   	autowidth: true, 
	   	   	loadonce:true,
	   	   	multiselect: false,
	   	   	ignoreCase:true,
	   	   	hiddengrid:false,
	   	   	height: "auto",		   
	   //	   	pager: '#listLocationDetailsPage',
	   	   	toppager:false,
	   	   	datatype: "json", 
	   	   	mtype: "POST",
	   	 jsonReader: { repeatitems : false, id: "0" },
	      //	sortname: 'name',
	      //  rowList: [10, 20],
	        caption:"Staff",
 		  //	sortorder: "desc",
	        gridComplete: function () {
	              var ids =  $("#JQBlistStaffResultSet").jqGrid('getDataIDs');
	              for (var i = 0; i < ids.length; i++) {
	            	  var actionLink = '';
	                   var id = ids[i];
	                   var isAnalyst = grid.jqGrid('getCell',id,'client_id');
	                   var createdByName = grid.jqGrid('getCell',id,'created_by_full_name');
	                   var rowData = grid.jqGrid('getRowData', id);
                       //	jQuery("#JQBlistAllResultSet").jqGrid('setRowData',ids[i],{created_by_full_name:'Aissel Analyst'});
                       	var dataTypeIndicator =grid.jqGrid('getCell',id,'data_type_indicator');
                       	actionLink += data_type_indicator(dataTypeIndicator);	
                       	
                       actionLink += "<a onclick=\"addStaffs('edit',"+id+");return false;\"><span class='glyphicon glyphicon-edit action_icons'></span></a><a onclick=\"addStaffs('delete',"+id+");return false;\"><span class='glyphicon glyphicon-remove-circle action_icons'></span></a>";
                       $("#JQBlistStaffResultSet").jqGrid('setRowData', ids[i], {act: actionLink});
	               }
	                 
	        }
   });
}
function getEmailsData(){
	var kolId =get_field_value('kol_id');
    $('#gridWrapperEmails').html('');
  	$('#gridWrapperEmails').html('<table id="JQBlistEmailResultSet"></table>');
  	grid = $("#JQBlistEmailResultSet");
  	 $("#JQBlistEmailResultSet").jqGrid({
     	url: base_url + 'kols/list_kol_details/emails/' + kolId,
    		datatype: "json", 
    		colNames: ['Id', 'Email Type', 'Email', 'Is Primary','','Action','created_by_full_name','client_id','data_type_indicator'],
    		colModel:[
    				{name:'id',index:'id', hidden:true, search:false,align:'left'},
    				{name:'type',index:'type',search:true,align:'left'},
    		   		{name:'email',index:'email',search:true,align:'left'},
    				{name:'is_primary',index:'is_primary',search:true,align:'center'},
    				{name: 'created_by', index: 'created_by', hidden: true},
//	   				{name: 'eAllowed', index: 'eAllowed', hidden: true},
//	   	            {name: 'dAllowed', index: 'dAllowed', hidden: true},
	   	        	{name: 'act', resizable: true, width: 60, sortable: false},
	   	        	{name:'created_by_full_name',index:'created_by_full_name', hidden:true},
	   	            {name:'client_id',index:'client_id',width:175, resizable:false,search:false,hidden:true},
	   		   		{name:'data_type_indicator',index:'data_type_indicator', hidden:true}
    		   	  ], 
    		   sortname: 'type',
            rowNum: 10,
            mtype: "POST",
            autowidth: true,
            rownumbers: true,
            height: "auto",
            rowList: [10, 20],
            caption:"Emails",
            loadonce:true,    	           		   
 		  	sortorder: "desc",
 		  	jsonReader: { repeatitems : false, id: "0" },
            gridComplete: function () {
                var ids = $("#JQBlistEmailResultSet").jqGrid('getDataIDs');
                for (var i = 0; i < ids.length; i++) {
                	 var actionLink = '';
	                   var id = ids[i];
                       var isAnalyst = grid.jqGrid('getCell',id,'client_id');
                       var createdByName = grid.jqGrid('getCell',id,'created_by_full_name');
                       var rowData =  $("#JQBlistEmailResultSet").jqGrid('getRowData', id);
                     	var dataTypeIndicator =  $("#JQBlistEmailResultSet").jqGrid('getCell',id,'data_type_indicator');
                     console.log(dataTypeIndicator);
                     	actionLink += data_type_indicator(dataTypeIndicator);		    		
	                   var is_prime = '';
	                   if(rowData.is_primary == 'Yes'){
	                   	is_prime = 1;
	                   }
	                   actionLink += "<a onclick=\"addEmails('edit',"+id+");return false;\"><span class='glyphicon glyphicon-edit action_icons'></span></a><a onclick=\"addEmails('delete',"+id+","+is_prime+");return false;\"><span class='glyphicon glyphicon-remove-circle action_icons'></span></a>";
	                   $("#JQBlistEmailResultSet").jqGrid('setRowData', ids[i], {act: actionLink});
	                   emailCount++;
	               }
            }
    	});
}
function getStateLicenceData(){
	var kolId =get_field_value('kol_id');
    $('#gridWrapperStateLicense').html('');
  	$('#gridWrapperStateLicense').html('<table id="JQBliststateLicenseResultSet"></table>');
  	grid = $("#JQBliststateLicenseResultSet");
  	$("#JQBliststateLicenseResultSet").jqGrid({
         	url: base_url + 'kols/list_kol_details/statelicense/' + kolId,
    		colNames: ['Id', 'License Number', 'State', 'Is Primary','','','','Action','created_by_full_name','client_id','data_type_indicator'],
    		colModel:[
    				{name:'id',index:'id',hidden:true, search:false,align:'left'},
    				{name:'state_license',index:'state_license',search:true,align:'left'},
    		   		{name:'state_name',index:'state_name',search:true,align:'left'},
    				{name:'is_primary',index:'is_primary',search:true,align:'center'},
    				{name:'created_by',index: 'created_by',hidden: true},
   					{name:'eAllowed',index: 'eAllowed', hidden: true},
   	            	{name:'dAllowed',index: 'dAllowed', hidden: true},
   	         		{name:'act',resizable: true, width: 60, sortable: false},
  	   	         	{name:'created_by_full_name',index:'created_by_full_name', hidden:true},
	   	            {name:'client_id',index:'client_id',width:175, resizable:false,search:false,hidden:true},
	   		   		{name:'data_type_indicator',index:'data_type_indicator', hidden:true}
    		   	  ], 
    		sortname:'state_license',
            rowNum: 10,
            datatype: "json",
            mtype: "POST",
            autowidth: true,
            rownumbers: true,
            height: "auto",
            rowList: [10, 20],
            loadonce:true, 
            caption:"State License",
            sortorder: "asc",
            jsonReader: { repeatitems : false, id: "0" },
            gridComplete: function () {
                var ids = grid.jqGrid('getDataIDs');
                for (var i = 0; i < ids.length; i++) {
                	var actionLink = '';
                   	var id = ids[i];
                    var isAnalyst = grid.jqGrid('getCell',id,'client_id');
                    var createdByName = grid.jqGrid('getCell',id,'created_by_full_name');
                    var rowData = grid.jqGrid('getRowData', id);
                  if(isAnalyst != 1){                    
                 		 actionLink = "<div class='actionIcon iconCreatedByUser tooltip-demo tooltop-left' onclick=\"showUserName(this); return false;\"><a href='#' title='Added by: "+createdByName+"' class='tooltipLink' rel='tooltip' data-placement='left' data-toggle='tooltip'></a></div>";
                  }else{
                  	var dataTypeIndicator = grid.jqGrid('getCell',id,'data_type_indicator');
                  	actionLink += data_type_indicator(dataTypeIndicator);		    		
                  }
                   var is_prime = '';
                   if(rowData.is_primary == 'Yes'){
                   	is_prime = 1;
                   }
                   actionLink += "<a onclick=\"addLicenses('edit',"+id+");return false;\"><span class='glyphicon glyphicon-edit action_icons'></span></a><a onclick=\"addLicenses('delete',"+id+","+is_prime+");return false;\"><span class='glyphicon glyphicon-remove-circle action_icons'></span></a>";
                   $("#JQBliststateLicenseResultSet").jqGrid('setRowData', ids[i], {act: actionLink});
               }
            }
        });
}
function getAssignedData(){
	var kolId =get_field_value('kol_id');
    $('#gridWrapperAssign').html('');
  	$('#gridWrapperAssign').html('<table id="JQBlistAssignResultSet"></table>');
    jQuery("#JQBlistAssignResultSet").jqGrid({
     	url: base_url + 'kols/list_kol_details/assign/' + kolId,
    		datatype: "json", 
    		colNames: ['Id', 'Name','Email', 'Type','','Action','client_id','data_type_indicator'],
    		colModel:[
    				{name:'id',index:'id', hidden:true, search:false,align:'left'},
    				{name:'name',index:'name',search:true,align:'left'},
    		   		{name:'email',index:'email',search:true,align:'left'},
    		   		{name:'type',index:'type',search:true,align:'left'},
    		   		{name: 'created_by', index: 'created_by', hidden: true},
//	   				{name: 'eAllowed', index: 'eAllowed', hidden: true},
//	   	            {name: 'dAllowed', index: 'dAllowed', hidden: true},
	   	        	{name: 'act', resizable: true, width: 60, sortable: false},
	   	        	{name:'client_id',index:'client_id',width:175, resizable:false,search:false,hidden:true},
	   		   		{name:'data_type_indicator',index:'data_type_indicator', hidden:true}
    		   	  ], 
    		sortname: 'id',
            rowNum: 10,
            mtype: "POST",
            autowidth: true,
            rownumbers: true,
            height: "auto",
            rowList: [10, 20],
            caption:"Assign Profile",
            loadonce:true,    	           		   
 		  	sortorder: "asc",    
 		  	jsonReader: { repeatitems : false, id: "0" },
            gridComplete: function () {
                var ids = jQuery("#JQBlistAssignResultSet").jqGrid('getDataIDs');
                for (var i = 0; i < ids.length; i++) {
	                   var primaryLable = '';
	                   var id = ids[i];
	                   var isAnalyst = jQuery("#JQBlistAssignResultSet").jqGrid('getCell',id,'client_id');
                       var createdByName = jQuery("#JQBlistAssignResultSet").jqGrid('getCell',id,'created_by');
	                   var rowData = jQuery("#JQBlistAssignResultSet").jqGrid('getRowData', id);
	                   var actionLink = '';
	                   if(isAnalyst != 1){                    
                   		 actionLink = "<div class='actionIcon iconCreatedByUser tooltip-demo tooltop-left' onclick=\"showUserName(this); return false;\"><a href='#' title='Added by: "+createdByName+"' class='tooltipLink' rel='tooltip' data-placement='left' data-toggle='tooltip'></a></div>";
                        }else{
                        	var dataTypeIndicator = jQuery("#JQBlistAssignResultSet").jqGrid('getCell',id,'data_type_indicator');
                        	actionLink +=data_type_indicator(dataTypeIndicator);		    		
                        } 
	                   actionLink += "<a onclick=\"addAssign('edit','"+id+"');return false;\"><span class='glyphicon glyphicon-edit action_icons'></span></a><a onclick=\"addAssign('delete','"+id+"');return false;\"><span class='glyphicon glyphicon-remove-circle action_icons'></span></a>";
	                   jQuery("#JQBlistAssignResultSet").jqGrid('setRowData', ids[i], {act: actionLink});
	                   emailCount++;
	               }
            }
    		});
}
function addAssign(actionType,id) {
	var kolId =get_field_value('kol_id');
	if(actionType=='delete'){
		jConfirm("Are you sure you want to delete the Assigned Client?","Confirm box", function(r){
			if(r){
				var formAction = base_url + 'kols/delete_assign/' + id+'/'+kolId;
				$.ajax({
					url: formAction,
					dataType: "json",
					type: "post",
					success: function(retData){
						$("#JQBlistAssignResultSet").jqGrid("setGridParam", { datatype: "json" })
				        .trigger("reloadGrid", [{ current: true }]);
					}
				});
			}
		});
	}else{
		$(".modalContent").html("<div class='microViewLoading'>Loading...</div>");
		$("#modalContainer").dialog(modalBoxLayout);
		$('#ui-dialog-title-modalContainer').html("Assign Profile");
		$(".modalContent").load(base_url + 'kols/add_update_assign_client/'+kolId+'/'+actionType+'/'+id);
		return false;
	}
}
function save_assigned_client_form(){
	$("#msgBox").html('<div class="alert alert-info">Saving the data... <img src="'+base_url +'assets/images/ajax_loader_black.gif"></div>');
	if (!$("#saveKolAssignClientForm").validate().form()) {
        return false;
    }
	$.ajax({
        url: base_url+'kols/save_client_assign',
        type: 'post',
        dataType: 'json',
      	data: $('#saveKolAssignClientForm').serialize(),
        success: function (returnData) {
          if(returnData.status){
        	  close_dialog();
        	  $("#msgBox").html('');
        	  getAssignedData();
          }else{
        	  $("#msgBox").html('<div class="alert alert-danger">Kol with same details already exists..</div>');
        		return false;
          }
        }
	});
}
function addLicenses(actionType,id,isPrimary) {
	var kolId =get_field_value('kol_id');
	if(isPrimary==1){
		jAlert("Can't Delete the Primary State License", 'Alert Dialog');
	}else{
		if(actionType=='delete'){
			jConfirm("Are you sure you want to delete the state license?","Confirm box", function(r){
				if(r){
					var formAction = base_url + 'kols/delete_state_license/'+ id+'/'+kolId;
					$.ajax({
						url: formAction,
						dataType: "json",
						type: "post",
						success: function(retData){
							$("#JQBliststateLicenseResultSet").jqGrid("setGridParam", { datatype: "json" })
					        .trigger("reloadGrid", [{ current: true }]);
						}
					});
				}
			});
		}else{
			$(".modalContent").html("<div class='microViewLoading'>Loading...</div>");
			$("#modalContainer").dialog(modalBoxLayout);
			$('#ui-dialog-title-modalContainer').html("State License");
			$(".modalContent").load(base_url + 'kols/add_update_licenses/'+kolId+'/'+actionType+'/'+id);
			return false;
		}
	}
}
function save_state_license_form(){
	$("#msgBox").html('<div class="alert alert-info">Saving the data... <img src="'+base_url +'assets/images/ajax_loader_black.gif"></div>');
	if (!$("#saveKolStateLicenseForm").validate().form()) {
        return false;
    }
	$.ajax({
        url: base_url+'kols/save_state_license',
        type: 'post',
        dataType: 'json',
      	data: $('#saveKolStateLicenseForm').serialize(),
        success: function (returnData) {
          if(returnData.status){
        	  close_dialog();
        	  $("#msgBox").html('');
        	  getStateLicenceData();
          }else{
        	  $("#msgBox").html('<div class="alert alert-danger">Kol with same details already exists..</div>');
        		return false;
          }
        }
	});
}
function getStatesByCountryIdLicense() {
    var countryId = $('#saveKolStateLicenseForm #country_id').val();
    var params = "country_id=" + countryId;
    $("#saveKolStateLicenseForm #state_id").html("<option value=''>-- Select State --</option>");
    var states = document.getElementById("saveKolStateLicenseForm").elements.namedItem("state_id");
    $.ajax({
        url: base_url+'helpers/country_helpers/get_states_by_countryid',
        dataType: "json",
        data: params,
        type: "POST",
        success: function (responseText) {
            $.each(responseText, function (key, value) {
                var newState = document.createElement('option');
                newState.text = value.state_name;
                newState.value = value.state_id;
                var prev = states.options[states.selectedIndex];
                states.add(newState, prev);
            });
            $("#saveKolStateLicenseForm #state_id option[value='']").remove();
            $("#saveKolStateLicenseForm #state_id").prepend("<option value=''>-- Select State --</option>");
            $("#saveKolStateLicenseForm #state_id").val("");
        }
    });
}
function checkDuplicatesAndSave(){
	$("#msgBox").html('<div class="alert alert-warning">Saving the data... <img src="'+base_url +'assets/images/ajax_loader_black.gif"></div>');
	if (!$("#kol_form").validate().form()) {
        return false;
    }
	$.ajax({
        url: base_url+'kols/check_duplicate_ol',
        type: 'post',
        dataType: 'json',
      	data: $('#kol_form').serialize(),
        success: function (returnData) {
          if(returnData.duplicate_found == 1){
        	  $("#msgBox").html('<div class="alert alert-danger">Kol with same details already exists..</div>');
          		return false;
          }else{
        	saveKol();
          }
        }
	});
}
function saveKol(){
    $.ajax({
        url:base_url+'kols/save_ol',
        type: 'post',
        dataType: 'json',
        data: $('#kol_form').serialize(),
        success: function (returnData) {
            if (returnData.status == true) {
            	$("#msgBox").html('<div class="alert alert-success">KOL profile is successfully updated</div>');
            }
        }
    });
}function addEmails(actionType,id,isPrimary) {
	var kolId =get_field_value('kol_id');
	if(isPrimary==1){
		jAlert("Can't Delete the Primary Email", 'Alert Dialog');
	}else{
		if(actionType=='delete'){
			jConfirm("Are you sure you want to delete the email id?","Confirm box", function(r){
				if(r){
					var formAction = base_url + 'kols/delete_email/' + id+'/'+kolId;
					$.ajax({
						url: formAction,
						dataType: "json",
						type: "post",
						success: function(retData){
							$("#JQBlistEmailResultSet").jqGrid("setGridParam", { datatype: "json" })
					        .trigger("reloadGrid", [{ current: true }]);
						}
					});
				}
			});
		}else{
			$(".modalContent").html("<div class='microViewLoading'>Loading...</div>");
			$("#modalContainer").dialog(modalBoxLayout);
			$('#ui-dialog-title-modalContainer').html("Email");
			$(".modalContent").load(base_url + 'kols/add_update_emails/'+kolId+'/'+actionType+'/'+id);
			return false;
		}
	}
}
function save_email_form(){
	$("#msgBox").html('<div class="alert alert-info">Saving the data... <img src="'+base_url +'assets/images/ajax_loader_black.gif"></div>');
	if (!$("#saveKolEmailForm").validate().form()){
        return false;
    }
	$.ajax({
        url: base_url+'kols/save_email/'+id,
        type: 'post',
        dataType: 'json',
      	data: $('#saveKolEmailForm').serialize(),
        success: function (returnData) {
          if(returnData.status){
        	  close_dialog();
        	  $("#msgBox").html('');
              getEmailsData();
          }else{
        	  $("#msgBox").html('<div class="alert alert-danger">Kol with same details already exists..</div>');
        		return false;
          }
        }
	});
}
function addPhoneNumber(actionType,id,isPrimary) {
	var kolId =get_field_value('kol_id');
	if(isPrimary==1){
		jAlert("Can't Delete the Primary Phone Number", 'Alert Dialog');
	}else{
		if(actionType=='delete'){
			jConfirm("Are you sure you want to delete the phone number?","Confirm box", function(r){
				if(r){
					var formAction = base_url + 'kols/delete_phone/' + id+'/'+kolId;
					$.ajax({
						url: formAction,
						dataType: "json",
						type: "post",
						success: function(retData){
							$("#JQBlistPhoneNumberResultSet").jqGrid("setGridParam", { datatype: "json" })
					        .trigger("reloadGrid", [{ current: true }]);
						}
					});
				}
			});
		}else{
			$(".modalContent").html("<div class='microViewLoading'>Loading...</div>");
			$("#modalContainer").dialog(modalBoxLayout);
			$('#ui-dialog-title-modalContainer').html("Phone Number");
			$(".modalContent").load(base_url + 'kols/add_update_phone/'+kolId+'/'+actionType+'/'+id);
			return false;
		}
	}
}
function save_phone_number_form(){
	$("#msgBox").html('<div class="alert alert-info">Saving the data... <img src="'+base_url +'assets/images/ajax_loader_black.gif"></div>');
	if (!$("#saveKolPhoneNumberForm").validate().form()){
        return false;
    }
	$.ajax({
        url: base_url+'kols/save_phone',
        type: 'post',
        dataType: 'json',
      	data: $('#saveKolPhoneNumberForm').serialize(),
        success: function (returnData) {
          if(returnData.status){
        	  $("#msgBox").html('');
        	  close_dialog();
              getPhoneNumberData();
          }else{
        	  $("#msgBox").html('<div class="alert alert-danger">Kol with same details already exists..</div>');
        		return false;
          }
        }
	});
}
function addLocation(locationId=null) {
	var kolId =get_field_value('kol_id');
	$(".modalContent").html("<div class='microViewLoading'>Loading...</div>");
	$("#modalContainer").dialog(modalBoxLayout2);
	$('#ui-dialog-title-modalContainer').html("Location");
	$(".modalContent").load(base_url + 'kols/add_location/'+kolId+'/'+locationId);
	return false;
}
function save_location_form(){
	var kolId =get_field_value('kol_id');
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
        	  close_dialog();
        	  $("#msgBox").html('');
        	  getLocationDataOnPageLoad();
          }else{
        	  $("#msgBox").html('<div class="alert alert-danger">Kol with same details already exists..</div>');
        		return false;
          }
        }
	});
}
function addStaffs(actionType,id) {
	var kolId =get_field_value('kol_id');
	if(actionType=='delete'){
		jConfirm("Are you sure you want to delete the staff?","Confirm box", function(r){
			if(r){
				var formAction = base_url + 'kols/delete_staff/' + id+'/'+kolId;
				$.ajax({
					url: formAction,
					dataType: "json",
					type: "post",
					success: function(retData){
				        $("#JQBlistStaffResultSet").jqGrid("setGridParam", { datatype: "json" })
				        .trigger("reloadGrid", [{ current: true }]);
					}
				});
			}
		});
	}else{
		$(".modalContent").html("<div class='microViewLoading'>Loading...</div>");
		$("#modalContainer").dialog(modalBoxLayout2);
		$('#ui-dialog-title-modalContainer').html("Staff");
		$(".modalContent").load(base_url + 'kols/add_update_staffs/'+kolId+'/'+actionType+'/'+id);
		return false;
	}
}
function save_staff_form(){
	var kolId =get_field_value('kol_id');
	$("#msgBox").html('<div class="alert alert-info">Saving the data... <img src="'+base_url +'assets/images/ajax_loader_black.gif"></div>');
	if (!$("#saveKolStaffForm").validate().form()){
        return false;
    }
	$.ajax({
        url: base_url+'kols/save_staff',
        type: 'post',
        dataType: 'json',
      	data: $('#saveKolStaffForm').serialize(),
        success: function (returnData) {
          if(returnData.status){
        	  close_dialog();
        	  $("#msgBox").html('');
        	  getStaffsData();
          }else{
        	  $("#msgBox").html('<div class="alert alert-danger">Kol with same details already exists..</div>');
        		return false;
          }
        }
	});
}
function deleteLocation(id,isPrimary) {
	var kolId =get_field_value('kol_id');
	if(isPrimary==1){
		jAlert("Can't Delete the Primary Location", "Alert Dialog");
	}else{
		jConfirm("Are you sure you want to delete the location?","Confirm box", function(r){
			if(r){
				var formAction = base_url + 'kols/delete_location/' + id +'/'+ kolId
				$.ajax({
					url: formAction,
					dataType: "json",
					type: "post",
					success: function(retData){
						if(retData!=''){
							jAlert(retData, 'Alert Dialog');
						}else{
							$("#JQBlistLocationResultSet").jqGrid("clearGridData", true);
							getLocationDataOnPageLoad();
						}
					}
				});
			}
		});
	}
}
function deleteEducation(id) {
	jConfirm("Are you sure you want to delete this record?","Confirm box", function(r){
		if(r){
			var formAction = base_url + 'kols/delete_education_detail/' + id;
			$.ajax({
				url: formAction,
				dataType: "json",
				type: "post",
				success: function(retData){
					if(retData.status == 'success'){
						listHonorsAwardsDetails();
						listEducationDetails();
					}else{
						alert("Failed deleting, Try again");
					}
				}
			});
		}
	});
}
function editUsers(kolId,type){
	$(".modalcontent").html("<div class='microViewLoading'>Loading...</div>");
	$("#userContainer").dialog(modalBoxLayout);
	$(".modalcontent").load(base_url+'align_users/edit_align_users/'+kolId+'/'+type);
	return false;
}
function displayModelBox(){
	var values = new Array();
	$.each($("input[name='list[]']:checked"), function() {
	  values.push($(this).val());
	});
	console.log(values);
	if(values == ""){
		jAlert("Please select at least one KTL");
		return false;	
	}
	if(values.length > 1){
		$(".modalListContent").html("<div class='microViewLoading'>Loading...</div>");
		$("#listContainer").dialog(modalBoxLayout);
		$('#ui-dialog-title-userContainer').html('');
		$(".modalListContent").load(base_url+'kols/my_list_kols/add_list/'+values);
		return false;
	}
}
function alignUsers(){
	var values = new Array();
	$.each($("input[name='list[]']:checked"), function() {
	  values.push($(this).val());
	});
	if(values==''){
		jAlert("Please select at least one KOL");
	}else{
		$(".modalListContent").html("<div class='microViewLoading'>Loading...</div>");
		$("#listContainer").dialog(modalBoxLayout);
		$('#ui-dialog-title-userContainer').html('');
		$(".modalListContent").load(base_url+'align_users/align_kols/'+values);
	}
	return false;	
}
function showEmailModalBoxInOvearllPage(){
	var values = new Array();
	$.each($('input[name="list[]"]:checked'),function(){
		values.push($(this).val());
	});
	if(values == ""){
		jAlert("Please select at least one KTL");
		return false;	
	}
	if(values.length > 1){
		jAlert("Multiple profiles email not allowed");
		return false;	
	}
	$(".modalListContent").html("<div class='microViewLoading'>Loading...</div>");
	$("#listContainer").dialog(modalBoxLayout2);
	$('#ui-dialog-title-userContainer').html('');
	$(".modalListContent").load(base_url+'kols/send_profile_email/'+values[0]);
	return false;
}
function reset_filters(){
	$('#kolsListingDiv .filterDiv').find('input[type="checkbox"]').removeAttr('checked');
	$("#viewType").val(1);
	$("#savedFilterId").val('');
	$("#profileType").val('');
	doSearchFilter();
}