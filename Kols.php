<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Kols extends MX_Controller {
	private $loggedUserId = null;
	public function __construct()
	{
		parent::__construct();
		$this->load->model('kol');
		$this->load->model('kol_rating');
		$this->load->model('align_users/align_user');
		$this->load->model('logins/login');
		$this->load->model('helpers/country_helper');
		$this->load->model('helpers/common_helper');
		$this->load->model('organizations/organization');
		$this->load->model('specialities/speciality');
		$this->load->model('pubmeds/pubmed');
		$this->load->model('clinical_trials/clinical_trial');
		$this->load->library('ajax_pagination');
		$this->loggedUserId = $this->session->userdata('user_id');
		$this->clientId		=$this->session->userdata('client_id');
	}
	function delete_staff($id=null) {
		$arrdetails['table']='staffs';
		$arrdetails['id']=$id;
		$this->common_helper->deleteById($arrdetails);
	}
	function index()
	{
		$clientId			=$this->session->userdata('client_id');
		
		$module_name		='kols';
		$data['module_id']	=$this->common_helper->getModuleIdByModuleName($module_name);
		
		$arrFiltersAndKOls	=$this->get_filter_and_kol_listing(1);
		
		$arrcontentData=$arrFiltersAndKOls['arrKolsList']; 

		$data['options_page']			='export_options';
		$data['options_data']			='';
		
		$data['contentPage']			='kols';
		$data['contentData']			=$arrcontentData;
		
		$filter_data					=array();
		$filter_data					=$arrFiltersAndKOls['filter_data'];
		$filter_data['saved_filters']	=$this->kol->getAllCustomFilterByUser($this->loggedUserId);
		
		$data['right_side_bar_page'] = 'right_side_bar';
		$data['right_side_bar_data'] = $filter_data;
		
		$this->load->view(CLIENT_LAYOUT,$data);
	}
	function reload_filters($offset=1) {
		$clientId			=$this->session->userdata('client_id');
		
		$arrFiltersAndKOls			=$this->get_filter_and_kol_listing($offset);
		$filter_data				=$arrFiltersAndKOls['filter_data'];
		
		$content_data				=$arrFiltersAndKOls['arrKolsList']; 
		$module_name				='kols';
		$content_data['module_id']=$this->common_helper->getModuleIdByModuleName($module_name);
		
		$returnData['status']						=true;
		$returnData['filter_content']				=$this->load->view('format_right_side_bar',$filter_data,true);
		$returnData['kol_listing_content']			=$this->load->view('format_content_page',$content_data,true);
		
		echo json_encode($returnData);
	}
	function modal_to_save_filter(){
		$filterData=$_POST['filter_data'];
		$saved_filters=$this->kol->getAllCustomFilterByUser($this->loggedUserId);
		$arrSavedfilters=array();
		$arrSavedfilters['addNew']='Add new name';
		foreach ($saved_filters as $key=>$value){
			$arrSavedfilters[$value['id']]=$value['name'];
		}
		$hidden_fields=array('filter_id'=>'','filterData'=>$filterData);
		$form_inputs_details[]=array('type'=>'select','label'=>array('label_name'=>'Select existing Filter','required'=>0),'name'=>'filter','data'=>array('id'=>'menu_level_disabled','class'=>'required form-control','onchange'=>'addName(this)'),'options'=>$arrSavedfilters,'selected'=>'');
		$form_inputs_details[]=array('type'=>'text','label'=>array('label_name'=>'Enter new name','required'=>1),'data'=>array('name'=>'filter_name','id'=>'filter_name','class'=>'form-control','value'=>''));
		$form_details=array('form_inputs_details'=>$form_inputs_details,
				'hidden_ids'=>$hidden_fields,
				'form_id'=>'saveFilterForm',
				'submit_function'=>'save_filter();',
				'cancel_function'=>'close_dialog();'
		);
		$data['html_form']=get_html_form($form_details);
		$this->load->view('save_filter',$data);
	}
	function get_filter_by_id($id){
		$arrData['id'] = $id;
		$arrData['applied_on'] = date('Y-m-d H:i:s');
		$data = $this->kol->getFilterById($arrData);
		if ($data) {
			$data1['status'] = true;
			$data1['filterData'] = json_decode($data);
		} else {
			$data1['status'] = false;
		}
		echo json_encode($data1);
	}
	function save_filters(){
		$retdata['status']= false;
		if($this->kol->saveFilter($_POST)>0){
			$retdata['status']= true;
		}
		echo json_encode($retdata);
	}
	function assign_id_as_key($arrDetails,$key_name){
		foreach($arrDetails as $row){
			$arrData[$row[$key_name]]=$row;
			$Kolscount+=$row['count'];
		}
		$arrReturnData['count']=$Kolscount;
		$arrReturnData['filtered_array']=$arrData;
		return $arrReturnData;
	}
	function get_filter_and_kol_listing($offset=1) {
		$recordsPerPage	= $this->input->post("records_per_page");
		if (!empty($recordsPerPage)) {
			$this->ajax_pagination->set_records_per_page($recordsPerPage);
		}
		$limit 		= $this->ajax_pagination->per_page;
		$startFrom	= $offset;
		
		$viewTypeMyKols = ($offset=='1')? 1:trim($this->input->post('view_type'));
		$profileType 	= trim($this->input->post('profile_type'));
		$savedFilterId	= trim($this->input->post('saved_filter_id'));
		
		$kol_id 		= trim($this->input->post('influence_kol_id'));
		$regionType		= trim($this->input->post('region_type_id'));
		$country		= trim($this->input->post('country_id'));
		$position		= trim($this->input->post('kol_position_id'));
		$specialty		= trim($this->input->post('specialty_id'));
		$orgType		= trim($this->input->post('org_type_id'));
		$organization	= trim($this->input->post('organization_id'));
		$state			= trim($this->input->post('state_id'));
		$education		= trim($this->input->post('education_id'));
		$event			= trim($this->input->post('event_id'));
		$list	 		= trim($this->input->post('list_id'));
		
		$arrKolIds			=$this->input->post('influence_kol_ids');
		$arrGlobalRegions	=$this->input->post('global_region_ids');
		$arrCountries 		=$this->input->post('country_ids');
		$arrKolPositions	=$this->input->post('kol_position_ids');
		$arrSpecialties 	=$this->input->post('specialty_ids');
		$arrOrgTypes		=$this->input->post('org_type_ids');
		$arrOrganizations	=$this->input->post('organization_ids');
		$arrStates 			=$this->input->post('state_ids');
		$arrEducations		=$this->input->post('education_ids');
		$arrEvents 			=$this->input->post('event_ids');
		$arrOptInOutTypes	=$this->input->post('opt_in_out_ids');
		$arrLists 			=$this->input->post('list_ids');
		
		if ($viewTypeMyKols == MY_RECORDS) {
			$viewMyKols = $this->kol->getMyKolsView($this->loggedUserId);
			if (sizeof($viewMyKols) > 0) {
				$arrFilterFields['viewType'] = $viewMyKols;
			} else {
				$arrFilterFields['viewType'] = array(0);
			}
		}

		if ($kol_id!= '')
			$arrKolIds[] 		=$kol_id;
		if($regionType!='')
			$arrGlobalRegions[]	=$regionType;
		if ($country != '')
			$arrCountries[]		=$country;
		if($position!='')
			$arrKolPositions[]	=$position;
		if ($specialty != '')
			$arrSpecialties[] 	=$specialty;
		if($orgType!='')
			$arrOrgTypes[]		=$orgType;
		if ($organization != '')
			$arrOrganizations[] =$organization;
		if ($state != '')
			$arrStates[] 		=$state;
		if ($education != '')
			$arrEducations[] 	=$education;
		if ($event!= '')
			$arrEvents[] 		=$event;
		if ($list!= '')
			$arrLists[] 		=$list;
		
		$arrFilterFields['view_type'] 	= array($viewTypeMyKols);
		$arrFilterFields['profile_type']= $profileType;
		$arrFilterFields['region'] 		= $arrGlobalRegions;
		$arrFilterFields['country']		= $arrCountries;
		$arrFilterFields['title'] 		= $arrKolPositions;
		$arrFilterFields['specialty'] 	= $arrSpecialties;
		$arrFilterFields['type'] 		= $arrOrgTypes;
		$arrFilterFields['organization']= $arrOrganizations;
		$arrFilterFields['state']		= $arrStates;
		$arrFilterFields['education'] 	= $arrEducations;
		$arrFilterFields['event_id']	= $arrEvents;
		$arrFilterFields['opt_inout'] 	= $arrOptInOutTypes;
		$arrFilterFields['list_id'] 	= $arrLists;
		$arrFilterFields['kol_id'] 		= $arrKolIds;
		
		$arrKeywords=array();
		$arrKols 				= $this->kol->getKolsLike1($arrKeywords, $arrFilterFields, $limit, $startFrom, false);
		$count					= $this->kol->getKolsLike1($arrKeywords, $arrFilterFields, $limit, $startFrom, true);
		$arrRegionCount			= $this->kol->getKolsLike1($arrKeywords, $arrFilterFields, $limit, $startFrom, false, true, 'region', $arrKolIds);
		$arrKolsByCountryCount	= $this->kol->getKolsLike1($arrKeywords, $arrFilterFields, $limit, $startFrom, false, true, 'country', $arrKolIds);
		$arrKolByTypeCount 		= $this->kol->getKolsLike1($arrKeywords, $arrFilterFields, $limit, $startFrom, false, true, 'title', $arrKolIds);
		$arrKolsBySpecialtyCount= $this->kol->getKolsLike1($arrKeywords, $arrFilterFields, $limit, $startFrom, false, true, 'specialty', $arrKolIds);
		$arrOrgByTypeCount 		= $this->kol->getKolsLike1($arrKeywords, $arrFilterFields, $limit, $startFrom, false, true, 'type', $arrKolIds);
		$arrKolsByOrgCount 		= $this->kol->getKolsLike1($arrKeywords, $arrFilterFields, $limit, $startFrom, false, true, 'organization', $arrKolIds);
		$arrKolsByStateCount	= $this->kol->getKolsLike1($arrKeywords, $arrFilterFields, $limit, $startFrom, false, true, 'state', $arrKolIds);
		$arrKolsByEduCount 		= $this->kol->getKolsLike1($arrKeywords, $arrFilterFields, $limit, $startFrom, false, true, 'education', $arrKolIds);
		$arrKolsByEventCount	= $this->kol->getKolsLike1($arrKeywords, $arrFilterFields, $limit, $startFrom, false, true, 'event', $arrKolIds);
		$arrOptInCount			= $this->kol->getKolsLike1($arrKeywords, $arrFilterFields, $limit, $startFrom, false, true, 'opt_inout', $arrKolIds);
		$arrKolsByListCount 	= $this->kol->getKolsLike1($arrKeywords, $arrFilterFields, $limit, $startFrom, false, true, 'list', $arrKolIds);

		foreach ($arrFilterFields as $section => $arrValues) {
			if ((sizeof(array_filter($arrValues))) > 0) {
				$separator = ' | ';
				switch ($section) {
					case 'kol_id'	:	$arrKolName = $this->kol->getKolNameById($arrValues);
										$arrFiltersApplied['kol_id'] = '<a href="#" onclick="return false;" rel="tooltip" title="' . implode($separator, $arrKolName) . '">KOL name</a>';
										break;
					case 'view_type':	if ($arrValues[0] == 1)
											$viewTypeString = "My Contacts";
										else
											$viewTypeString = "All Contacts";
										$arrFiltersApplied['viewType'] = '<a href="#" onclick="return false;" rel="tooltip" title="' . $viewTypeString . '">View Type</a>';
										break;
					case 'specialty':	$arrSelectedSpecialties = $this->speciality->getSpecialtiesById($arrValues);
										$arrFiltersApplied['specialty'] = '<a href="#" onclick="return false;" rel="tooltip" title="' . implode($separator, $arrSelectedSpecialties) . '">Specialty</a>';
										break;
					case 'region'	: 	$arrSelectedRegion =$this->kol->getGlobalRegionsById($arrValues);
										$arrFiltersApplied['region'] = '<a href="#" onclick="return false;" rel="tooltip" title="' . implode($separator, $arrSelectedRegion) . '">Region</a>';
										break;
					case 'country'	: 	$arrSelectedCountries = $this->country_helper->getCountryNameById($arrValues);
										$arrFiltersApplied['country'] = '<a href="#" onclick="return false;" rel="tooltip" title="' . implode($separator, $arrSelectedCountries) . '">Country</a>';
										break;
					case 'title'	: 	$arrKolTitles =$this->kol->getPositionsById($arrValues);
										$arrFiltersApplied['kol_id'] = '<a href="#" onclick="return false;" rel="tooltip" title="' .implode($separator,$arrKolTitles) . '">Position</a>';
										break;
					case 'organization':$arrSelectedOrgs = $this->kol->getOrgsById($arrValues);
										$arrFiltersApplied['organization'] = '<a href="#" onclick="return false;" rel="tooltip" title="' . implode($separator, $arrSelectedOrgs) . '">Organization</a>';
										break;
					case 'type'		:	$arrSelectedOrgs = $this->organization->getOrgTypeById($arrValues);
										$arrFiltersApplied['type'] = '<a href="#" onclick="return false;" rel="tooltip" title="' . implode($separator, $arrSelectedOrgs) . '">Org Type</a>';
										break;
					case 'state'	:	$arrSelectedStates = $this->country_helper->getStateNameById($arrValues);
										$arrFiltersApplied['state'] = '<a href="#" onclick="return false;" rel="tooltip" title="' . implode($separator, $arrSelectedStates) . '">State</a>';
										break;
					case 'education':	$arrSelectedEducations = $this->kol->getInstituteNameById($arrValues);
										$arrFiltersApplied['education'] = '<a href="#" onclick="return false;" rel="tooltip" title="' . implode($separator, $arrSelectedEducations) . '">Education</a>';
										break;
					case 'event_id'	:	$arrSelectedEvents = $this->kol->getEventNameById($arrValues);
										$arrFiltersApplied['event'] = '<a href="#" onclick="return false;" rel="tooltip" title="' . implode($separator, $arrSelectedEvents) . '">Event</a>';
										break;
					case 'list_id'	:	$listNames = array();
										foreach ($arrValues as $key => $value) {
											$row = $this->kol->editListName($value);
											$listNames[] = $row['list_name'];
										}
										$arrFiltersApplied['list'] = '<a href="#" onclick="return false;" rel="tooltip" title="' . implode($separator, $listNames) . '">List</a>';
										break;
					case 'profile_type':$arrFiltersApplied['profile_type'] = '<a href="#" onclick="return false;" rel="tooltip"  title="' . $profileType . '">Profile Type</a>';
										break;
					case 'opt_inout'	:$arrSelectedOpt = $this->kol->getOptNameById($arrValues);
										$arrFiltersApplied['opt_inout'] = '<a href="#" onclick="return false;" rel="tooltip" title="' . implode($separator, $arrSelectedOpt) . '">Opt-in/Opt-out</a>';
										break;
				}
			}
		}
		if($savedFilterId!=''){
			$filter_names 	= $this->kol->getFilterNameById($savedFilterId);
			$savedFilterName= '<a href="#" onclick="return false;">'.implode($separator, $filter_names).'</a>';
		}
		$allRegionCount			=0;
		$assoArrKolByRegionCount=array();
		$arrFilteredArray		=$this->assign_id_as_key($arrRegionCount,'global_region_id');
		$allRegionCount			=$arrFilteredArray['count'];
		$assoArrKolByRegionCount=$arrFilteredArray['filtered_array'];
		
		$allCountryCount 			=0;
		$assoArrKolsByCountryCount 	=array();
		$arrFilteredArray			=$this->assign_id_as_key($arrKolsByCountryCount,'country_id');
		$allCountryCount			=$arrFilteredArray['count'];
		$assoArrKolsByCountryCount	=$arrFilteredArray['filtered_array'];
		
		$allKolPositionCount		=0;
		$assoArrKolByTypeCount		=array();
		$arrFilteredArray			=$this->assign_id_as_key($arrKolByTypeCount,'title_id');
		$allKolPositionCount		=$arrFilteredArray['count'];
		$assoArrKolByTypeCount		=$arrFilteredArray['filtered_array'];
		
		$allSpecialtyCount 			=0;
		$assoArrKolsBySpecialtyCount=array();
		$arrFilteredArray			=$this->assign_id_as_key($arrKolsBySpecialtyCount,'specialty');
		$allSpecialtyCount			=$arrFilteredArray['count'];
		$assoArrKolsBySpecialtyCount=$arrFilteredArray['filtered_array'];
		
		$allOrgTypeCount			=0;
		$assoArrKolsByOrgTypeCount = array();
		$arrFilteredArray			=$this->assign_id_as_key($arrOrgByTypeCount,'org_type_id');
		$allOrgTypeCount			=$arrFilteredArray['count'];
		$assoArrKolsByOrgTypeCount	=$arrFilteredArray['filtered_array'];
		
		$allOrgCount 				=0;
		$assoArrKolsByOrgCount		=array();
		$arrFilteredArray			=$this->assign_id_as_key($arrKolsByOrgCount,'org_id');
		$allOrgCount				=$arrFilteredArray['count'];
		$assoArrKolsByOrgCount		=$arrFilteredArray['filtered_array'];
		
		$allStateCount 				=0;
		$assoArrKolsByStateCount 	=array();
		$arrFilteredArray			=$this->assign_id_as_key($arrKolsByStateCount,'state_id');
		$allStateCount				=$arrFilteredArray['count'];
		$assoArrKolsByStateCount	=$arrFilteredArray['filtered_array'];
		
		$allEduCount 				=0;
		$assoArrKolsByEduCount 		=array();
		$arrFilteredArray			=$this->assign_id_as_key($arrKolsByEduCount,'institute_id');
		$allEduCount				=$arrFilteredArray['count'];
		$assoArrKolsByEduCount		=$arrFilteredArray['filtered_array'];
		
		$allEventCount 				=0;
		$assoArrKolsByEventCount 	=array();
		$arrFilteredArray			=$this->assign_id_as_key($arrKolsByEventCount,'event_id');
		$allEventCount				=$arrFilteredArray['count'];
		$assoArrKolsByEventCount	=$arrFilteredArray['filtered_array'];
		
		$allOptInOutCount			=0;
		$assoOptInCount				=array();
		$arrFilteredArray			=$this->assign_id_as_key($arrOptInCount,'opt_in_out_id');
		$allOptInOutCount			=$arrFilteredArray['count'];
		$assoOptInCount				=$arrFilteredArray['filtered_array'];

		$allListCount 				=0;
		$assoArrKolsByListCount 	=array();
		$arrFilteredArray			=$this->assign_id_as_key($arrKolsByListCount,'list_name_id');
		$allListCount				=$arrFilteredArray['count'];
		$assoArrKolsByListCount		=$arrFilteredArray['filtered_array'];
		
		
		$filterData['allRegionCount']				=$allRegionCount;
		$filterData['arrRegionsByKolsCount']		=$assoArrKolByRegionCount;
		
		
		$filterData['allCountryCount']				=$allCountryCount;
		$filterData['arrCountryByKolsCount'] 		=$assoArrKolsByCountryCount;
		
		$filterData['allKolPositionCount']			=$allKolPositionCount;
		$filterData['arrKolPositionsByKolsCount']	=$assoArrKolByTypeCount;
		
		$filterData['allSpecialtyCount'] 			=$allSpecialtyCount;
		$filterData['arrKolsBySpecialtyCount'] 		=$assoArrKolsBySpecialtyCount;
		
		$filterData['allOrgTypeCount']				=$allOrgTypeCount;
		$filterData['arrOrgByTypeCount']			=$assoArrKolsByOrgTypeCount;
		
		$filterData['allOrgCount'] 					=$allOrgCount;
		$filterData['arrKolsByOrgCount'] 			=$assoArrKolsByOrgCount;
		
		$filterData['allStateCount'] 				=$allStateCount;
		$filterData['arrKolsByStateCount'] 			=$assoArrKolsByStateCount;
		
		$filterData['allEduCount'] 					=$allEduCount;
		$filterData['arrKolsByEduCount'] 			=$assoArrKolsByEduCount;
		
		$filterData['allEventCount'] 				=$allEventCount;
		$filterData['arrKolsByEventCount'] 			=$assoArrKolsByEventCount;
		
		$filterData['allOptInOutCount'] 			=$allOptInOutCount;
		$filterData['arrOptInOutCount']				=$assoOptInCount;
		
		$filterData['allListCount'] 				=$allListCount;
		$filterData['arrKolsByListCount'] 			=$assoArrKolsByListCount;
		
		$filterData['viewType']						=$viewTypeMyKols;
		$filterData['profileType']					=$profileType;

		if(sizeof($arrKolIds)>0){
			$arrKolIds=$this->kol->getKolNameById($arrKolIds);
		}
		$filterData['selectedKols'] 				=$arrKolIds;
		$filterData['selectedGlobalRegions']		=$arrGlobalRegions;
		$filterData['selectedCountries']			=$arrCountries;
		$filterData['selectedKolPositions'] 		=$arrKolPositions;
		$filterData['selectedSpecialties'] 			=$arrSpecialties;
		$filterData['selectedOrgTypes']				=$arrOrgTypes;
		$filterData['selectedOrgs']					=$arrOrganizations;
		$filterData['selectedStates'] 				=$arrStates;
		$filterData['selectedEdus'] 				=$arrEducations;
		$filterData['selectedEvents'] 				=$arrEvents;
		$filterData['selectedOptInOut']				=$arrOptInOutTypes;
		$filterData['selectedLists']				=$arrLists;
		
		$filterData['savedFilterId']				=$savedFilterId;
		$filterData['customFilters'] 				=$this->kol->getAllCustomFilterByUser($this->loggedUserId);

		$arrKolsData['filtersApplied'] 				=implode(', ', $arrFiltersApplied);
		$arrKolsData['savedFilterName'] 			=$savedFilterName;
		
		$arrKolsData['kol_details']					=$arrKols;
		$arrKolsData['kols_count']					=$count;
		
		$returnData['arrKolsList']					=$arrKolsData;
		$returnData['filter_data']					=$filterData;
		return $returnData;
	}
	function delete_profile_kol($kolId,$type=0) {
		$data['kolId'] = $kolId;
		$arrKolName = $this->kol->getKolName($kolId);
		$data['arrClients'] =  $this->common_helper->getEntityById('clients',array ());
		$kolName = '';
		$kolName = $this->common_helper->get_name_format($arrKolName['first_name'],$arrKolName['middle_name'], $arrKolName['last_name']);
		$data['kolName'] = $kolName;
		if($type=='1'){
			$this->load->view('remove_confirm_kol', $data);
		}else{
			$this->load->view('delete_confirm_kol', $data);
		}
	}
	function get_user_name_by_clientId(){
		$client_id=$this->input->post('client_id');
		$arrUsers=$this->kol->getUserNameByClientId($client_id);
		echo json_encode($arrUsers);
	}
	function show_kol_export_opts($kols) {
		$data['arrKols'] = $kols;
		$this->load->view('kol_export_opts', $data);
	}
	function export_kol($email=false,$kolId,$kolName) {
		$clientId			=$this->session->userdata('client_id');
		$kolArray = $this->kol->getKolsIdAndPin();
		if(!$email){
			$exportOpts = $this->input->post('export_opts');
			$kols 		= $this->input->post('kol_id');
			$arrKolIds 	= explode(',', $kols);
		}else{
			$exportOpts = array('education', 'affiliation', 'event', 'publication', 'trial', 'professional', 'biography', 'user_notes','details');
			$arrKolIds = array($kolId);
		}
		foreach ($arrKolIds as $kolsId) {
			$result = $this->kol->getKolDetailsById($kolsId);
			$arrKolDetails[$kolsId]=$result[0];
		}
		if (in_array('professional', $exportOpts)){
			$data=array();
			$data[0]=array('PIN','Salutation','First Name','Middle Name','Last Name','Suffix','Gender','Specialty','Sub-Specialty','Company Name','Division','Title','Profile Type','URL');
			$i=1;
				foreach ($arrKolDetails as $row) {
					$data[$i]=array($kolArray[$row['id']],$row['salutation'],$row['first_name'],$row['middle_name'],$row['last_name'],$row['suffix'],$row['gender'],$arrSpecialties[$row['specialty']],rtrim($subSpecialties,','),$row['org_id'],$row['division'],$row['title'],$row['profile_type'],$row['url']
					);
					$i++;
				}
				$sheet['title']					='Prof_Info';
				$sheet['content']				=$data;
				$sheets[]=$sheet;
		}
		if (in_array('details', $exportOpts)){
			$data		=array();
			$maximum_number_of_cols=52;
			//main headings of sheet
			$data[0]	= array_fill(0,$maximum_number_of_cols,' ');
			$data[0][0]	='Location';
			$data[0][13]='Phone';
			$data[0][20]='Email';
			$data[0][26]='Specialty';
			$data[0][32]='Staff';
			$data[0][47]='License';
			//sub headings of sheet
			$data[1]= array_fill(0,$maximum_number_of_cols,' ');
			// 					[Location]
			$data[1][0]='PIN';
			$data[1][1]='Is Primary';
			$data[1][2]='Institution Name';
			$data[1][3]='Department';
			$data[1][4]='Title';
			$data[1][5]='Address 1';
			$data[1][6]='Address 2';
			$data[1][7]='City';
			$data[1][8]='State/ Province';
			$data[1][9]='Country';
			$data[1][10]='Postal Code';
			// 					[Phone]
			$data[1][13]= 'PIN';
			$data[1][14]='Is Primary';
			$data[1][15]='Institution Name';
			$data[1][16]='Phone Type';
			$data[1][17]='Phone';
			// 					[Email]
			$data[1][20]='PIN';
			$data[1][21]='Is Primary';
			$data[1][22]='Email Type';
			$data[1][23]='Email';
			// 					[Specialty]
			$data[1][26]='PIN';
			$data[1][27]='Is Primary';
			$data[1][28]='Specialty';
			$data[1][29]='Specialty Type';
			// 					[Staff]
			$data[1][32]= 'PIN';
			$data[1][33]='Institution Name';
			$data[1][34]='Title';
			$data[1][35]='Phone Type';
			$data[1][36]='Phone';
			$data[1][37]='Email';
			$data[1][38]='Address 1';
			$data[1][39]='Address 2';
			$data[1][40]='City';
			$data[1][41]='State/ Province';
			$data[1][42]='Country';
			$data[1][43]='Postal Code';
			$data[1][44]='Staff Name';
			// 					[License]
			$data[1][47]= 'PIN';
			$data[1][48]='Is Primary';
			$data[1][49]='License';
			$data[1][50]='State/ Province';
			$data[1][51]='Country';
			
			//details of sheet
			$locationData 	= $this->kol->getDetailsInfoById($arrKolIds,'Location');
			$phoneData 		= $this->kol->getDetailsInfoById($arrKolIds,'Phone');
			$emailData 		= $this->kol->getDetailsInfoById($arrKolIds,'Email');
			$specialtyData 	= $this->kol->getDetailsInfoById($arrKolIds,'Specialty');
			$staffData 		= $this->kol->getDetailsInfoById($arrKolIds,'Staff');
			$licenseData 	= $this->kol->getDetailsInfoById($arrKolIds,'License');

			$maximum_number_of_rows=max(sizeof($locationData),sizeof($phoneData),sizeof($emailData),sizeof($specialtyData),sizeof($staffData),sizeof($licenseData));
			for($i=2;$i<=$maximum_number_of_rows+2;$i++){
				$data[$i]= array_fill(0,$maximum_number_of_cols,' ');
			}
			$i=2;
			foreach ($locationData as $row) {
				$data[$i][0]=isset($row['pin'])?$row['pin']:'';
				$data[$i][1]=isset($row['is_primary'])?$row['is_primary']:'';
				$data[$i][2]=isset($row['org_name'])?$row['org_name']:'';
				$data[$i][3]=isset($row['division'])?$row['division']:'';
				$data[$i][4]=isset($row['title'])?$row['title']:'';
				$data[$i][5]=isset($row['address1'])?$row['address1']:'';
				$data[$i][6]=isset($row['address2'])?$row['address2']:'';
				$data[$i][7]=isset($row['City'])?$row['City']:'';
				$data[$i][8]=isset($row['Region'])?$row['Region']:'';
				$data[$i][9]=isset($row['Country'])?$row['Country']:'';
				$data[$i][10]=isset($row['postal_code'])?$row['postal_code']:'';
				$i++;
			} 
			$i=2;
			foreach ($phoneData as $row) {
				$data[$i][13]=isset($row['pin'])?$row['pin']:'';
				$data[$i][14]=isset($row['is_primary'])?$row['is_primary']:'';
				$data[$i][15]=isset($row['org_name'])?$row['org_name']:'';
				$data[$i][16]=isset($row['name'])?$row['name']:'';
				$data[$i][17]=isset($row['number'])?$row['number']:'';
			$i++;
			} 
			$i=2;
			foreach ($emailData as $row) {
				$data[$i][20]=isset($row['pin'])?$row['pin']:'';
				$data[$i][21]=isset($row['is_primary'])?$row['is_primary']:'';
				$data[$i][22]=isset($row['type'])?$row['type']:'';
				$data[$i][23]=isset($row['email'])?$row['email']:'';
				$i++;
			}
			$i=2;
			foreach ($specialtyData as $row) {
				$data[$i][26]=isset($row['pin'])?$row['pin']:'';
				$data[$i][27]=isset($row['is_primary'])?$row['is_primary']:'';
				$data[$i][28]=isset($row['specialty'])?$row['specialty']:'';
				$data[$i][29]=isset($row['priority'])?$row['priority']:'';
				$i++;
			} 
			$i=2;
			foreach ($staffData as $row) {
				$data[$i][32]=isset($row['pin'])?$row['pin']:'';
				$data[$i][33]=isset($row['org_name'])?$row['org_name']:'';
				$data[$i][34]=isset($row['staff_title'])?$row['staff_title']:'';
				$data[$i][35]=isset($row['phone_type'])?$row['phone_type']:'';
				$data[$i][36]=isset($row['phone_number'])?$row['phone_number']:'';
				$data[$i][37]=isset($row['email'])?$row['email']:'';
				$data[$i][38]=isset($row['address1'])?$row['address1']:'';
				$data[$i][39]=isset($row['address2'])?$row['address2']:'';
				$data[$i][40]=isset($row['City'])?$row['City']:'';
				$data[$i][41]=isset($row['Region'])?$row['Region']:'';
				$data[$i][42]=isset($row['Country'])?$row['Country']:'';
				$data[$i][43]=isset($row['postal_code'])?$row['postal_code']:'';
				$data[$i][44]=isset($row['name'])?$row['name']:'';
				$i++;
			}
			$i = 2;
			foreach ($licenseData as $row) {
				$data[$i][47]=isset($row['pin'])?$row['pin']:'';
				$data[$i][48]=isset($row['is_primary'])?$row['is_primary']:'';
				$data[$i][49]=isset($row['state_license'])?$row['state_license']:'';
				$data[$i][50]=isset($row['Region'])?$row['Region']:'';
				$data[$i][51]=isset($row['Country'])?$row['Country']:'';
				$i++;
			}
			$sheet['title']					='Details';
			$sheet['content']				=$data;
			$sheets[]=$sheet;
		}
		if (in_array('media', $exportOpts)){
			$data=array();
			$data[0]=array('PIN', 'Blog','Linkedin','Facebook','Twitter','YouTube');
			$i=1;
			foreach ($arrKolDetails as $row) {
				$data[$i]=array($kolArray[$row['id']],$row['blog'],$row['linked_in'],$row['facebook'],$row['twitter'],$row['you_tube']);
					$i++;
			}
			$sheet['title']					='Social Media';
			$sheet['content']				=$data;
			$sheets[]=$sheet;
		}
		if (in_array('education', $exportOpts)) {
			$data=array();
			if ($clientId == INTERNAL_CLIENT_ID) {
				$data[0]=array('PIN','Education Type','Institution Name','Degree','Specialty','Time Frame','Url1','Url2');
			} else {
				$data[0]=array('PIN','Education Type','Institution Name','Degree','Specialty','Time Frame');
			}			
			$i=1;
			foreach ($arrKolIds as $kolsId) {
				$arrEducationDetails = $this->kol->getEducationDetailById($kolsId);
				foreach ($arrEducationDetails as $row) {
					$data[$i][]=$kolArray[$kolsId];
					$data[$i][]=$row['type'];
					if ($row['type'] == 'honors_awards') {
						$data[$i][]=$row['honor_name'];
					}else {
						$data[$i][]=$row['name'];
					}
					$data[$i][]=$row['degree'];
					$data[$i][]=$row['specialty'];
					$eduDate = '';
					if ($row['start_date'] != '' && $row['start_date'] != 0)
						$eduDate .= $row['start_date'];
					if (($row['start_date'] != '') && ($row['end_date']))
						$eduDate .= " - ";
					if ($row['end_date'] != '')
						$eduDate .= $row['end_date'];
					$data[$i][]=$eduDate;
					if ($clientId == INTERNAL_CLIENT_ID) {
						$data[$i][]=$row['url1'];
						$data[$i][]=$row['url2'];
					}
					$i++;
				}
			}
			$sheet['title']					='Education';
			$sheet['content']				=$data;
			$sheets[]=$sheet;
		}
		if (in_array('trial', $exportOpts)) {
			if($this->common_helper->check_module("clinical_trials")){
				$this->load->model('clinical_trials/clinical_trial');
				$data=array();
				$data[0]=array('PIN','CTID','Study Type','Trial Name','Condition','Intervention','Phase','Role','Number of enrollees','Number of trial sites','Sponsors','Status','Start Date','End Date','Minimum Age','Maximum Age','Gender','Investigators','Collaborator','Purpose','Official Title','Keywords','MeSH Terms','Url');
				$i=1;
				foreach ($arrKolIds as $kolsId) {
					$arrClinicalTrials = array();
					if ($arrClinicalTrialsResults = $this->clinical_trial->listClinicalTrialsDetails($kolsId)) {
						foreach ($arrClinicalTrialsResults as $arrClinicalTrialsResult) {
							$arrClinicalTrial['id'] = $arrClinicalTrialsResult['id'];
							$arrClinicalTrial['ct_id'] = $arrClinicalTrialsResult['ct_id'];
							$arrClinicalTrial['trial_name'] = $arrClinicalTrialsResult['trial_name'];
							$arrClinicalTrial['status'] = $this->clinical_trial->getStatusNameById($arrClinicalTrialsResult['status_id']);
							$this->load->module('clinical_trials');
							$arrClinicalTrial['sponsors'] = $this->clinical_trials->get_sponsers($arrClinicalTrialsResult['id']);
							$arrClinicalTrial['condition'] = $arrClinicalTrialsResult['condition'];
							$arrClinicalTrial['interventions'] = $this->clinical_trials->get_interventions($arrClinicalTrialsResult['id']);
							$arrClinicalTrial['phase'] = $arrClinicalTrialsResult['phase'];
							$arrClinicalTrial['investigators'] = $this->clinical_trials->get_investigators($arrClinicalTrialsResult['id']);
							$arrClinicalTrial['kol_id'] = $kolId;
							$arrClinicalTrial['study_type'] = $arrClinicalTrialsResult['study_type'];
							$arrClinicalTrial['kol_role'] = $arrClinicalTrialsResult['kol_role'];
							$arrClinicalTrial['no_of_enrollees'] = $arrClinicalTrialsResult['no_of_enrollees'];
							$arrClinicalTrial['no_of_trial_sites'] = $arrClinicalTrialsResult['no_of_trial_sites'];
							$arrClinicalTrial['start_date'] = $arrClinicalTrialsResult['start_date'];
							$arrClinicalTrial['end_date'] = $arrClinicalTrialsResult['end_date'];
							$arrClinicalTrial['min_age'] = $arrClinicalTrialsResult['min_age'];
							$arrClinicalTrial['max_age'] = $arrClinicalTrialsResult['max_age'];
							$arrClinicalTrial['gender'] = $arrClinicalTrialsResult['gender'];
							$arrClinicalTrial['collaborator'] = $arrClinicalTrialsResult['collaborator'];
							$arrClinicalTrial['purpose'] = $arrClinicalTrialsResult['purpose'];
							$arrClinicalTrial['official_title'] = $arrClinicalTrialsResult['official_title'];
							$arrKeywordsData = '';
							$separator = '';
							foreach ($this->clinical_trial->listCTIKeyWords($arrClinicalTrialsResult['id']) as $key => $row) {
								$arrKeywordsData .= $separator . $row['name'];
								$separator = ',';
							}
							$arrClinicalTrial['keywords'] = $arrKeywordsData;
							$arrMeshtermsData = '';
							$separator = '';
							foreach ($this->clinical_trial->listCTMeshTerms($arrClinicalTrialsResult['id']) as $key => $row) {
								$arrMeshtermsData .= $separator . $row['term_name'];
								$separator = ',';
							}
							$arrClinicalTrial['mesh_terms'] = $arrMeshtermsData;
							$arrClinicalTrial['url'] = $arrClinicalTrialsResult['link'];
							$arrClinicalTrials[] = $arrClinicalTrial;
						}
					}
					foreach ($arrClinicalTrials as $row) {
						$data[$i]=array($kolArray[$kolsId],$row['ct_id'],$row['study_type'],$row['trial_name'],$row['condition'],$row['interventions'],$row['phase'],$row['kol_role'],$row['no_of_enrollees'],$row['no_of_trial_sites'],$row['sponsors'],$row['status'],$row['start_date'],$row['end_date'],$row['min_age'],$row['max_age'],$row['gender'],$row['investigators'],$row['collaborator'],$row['purpose'],$row['official_title'],$row['keywords'],$row['mesh_terms'],$row['url']);
						$i++;
					}
				}
				$sheet['title']					='Trial';
				$sheet['content']				=$data;
				$sheets[]=$sheet;
			}
		}
		if (in_array('affiliation', $exportOpts)) {
			$data=array();
			if ($clientId == INTERNAL_CLIENT_ID) {
				$data[0]=array('PIN', 'Organization Name','Dept/Committee', 'Title/Purpose','Time frame','Organization Type','Engagement Type','Url1','Url2');
			} else {
				$data[0]=array( 'PIN','Organization Name','Dept/Committee','Title/Purpose','Time frame','Organization Type','Engagement Type');
			}
			$i=1;
			foreach ($arrKolIds as $kolsId) {
				$arrMembershipDetails = $this->kol->listAllMembershipsDetails($kolsId);
				foreach ($arrMembershipDetails as $row) {
					$data[$i][]=$kolArray[$kolsId];
					$data[$i][]=$row['name'];
					$data[$i][]=$row['department'];
					$data[$i][]=$row['role'];
					$affDate = '';
					if ($row['start_date'] != '' && $row['start_date'] != 0)
						$affDate .= $row['start_date'];
					if (($row['start_date'] != '') && ($row['end_date']))
						$affDate .= " - ";
					if ($row['end_date'] != '')
						$affDate .= $row['end_date'];
					$data[$i][]=$affDate;
					$data[$i][]=ucwords($row['type']);
					$data[$i][]= $row['engagement_type'];
					if ($clientId == INTERNAL_CLIENT_ID) {
						if (isset($row['url1ForExport'])) {
							$data[$i][]=$row['url1'];
						}
						if (isset($row['ur12ForExport'])) {
							$data[$i][]=$row['url2'];
						}
					}
					$i++;
				}
			}
			$sheet['title']					='Affiliations';
			$sheet['content']				=$data;
			$sheets[]=$sheet;
		}
		if (in_array('user_notes', $exportOpts)) {
			$data=array();
			$data[0]=array('PIN','Notes','Added By','Created On');
			$i=1;
			foreach ($arrKolIds as $kolsId) {
				$arrUserNotesDetails = $this->kol->getNotes($kolsId);
				foreach ($arrUserNotesDetails as $row) {
					$data[$i][]= $kolArray[$kolsId];
					$data[$i][]= $row['note'];
					if(KOL_CONSENT){
						if($row['is_from_opt_in'] == 0) {
							if($row['post_id']!=INTERNAL_CLIENT_ID){
								$added_by=$row['first_name']." ".$row['last_name'];
							}else{
								$added_by='Aissel Analyst';
							}
						}else{
							$added_by=$row['ktl_fname']." ".$row['ktl_lname'];
						}
						
					}else{
						if($row['post_id']!=INTERNAL_CLIENT_ID){
							$added_by=$row['first_name']." ".$row['last_name'];
						}else{
							$added_by='Aissel Analyst';
						}
					}
					$data[$i][]= $added_by;
					$data[$i][]= date('d M Y, h:i A', strtotime($row['created_on']));
					$i++;
				}
			}
			$sheet['title']					='User Notes';
			$sheet['content']				=$data;
			$sheets[]=$sheet;
		}
		if(in_array('events', $exportOpts)){
				$data=array();
				if ($clientId == INTERNAL_CLIENT_ID) {
					$data[0]=array('PIN', 'Event Name', 'Event Type', 'Session Type', 'Session Name', 'Role', 'Topic', 'Start', 'End', 'Organizer', 'Organizer Type', 'Session Sponsor', 'Sponsor Type', 'Location', 'Address', 'Country', 'State', 'City', 'Postal Code', 'Url1', 'Url2');
				} else {
					$data[0]=array('PIN', 'Event Name', 'Event Type', 'Session Type', 'Session Name', 'Role', 'Topic', 'Start', 'End', 'Organizer', 'Organizer Type', 'Session Sponsor', 'Sponsor Type', 'Location', 'Address', 'Country', 'State', 'City', 'Postal Code');
				}
				$i=1;
				foreach ($arrKolIds as $kolsId) {
					$arrEventsDetails = $this->kol->listAllEvents($kolsId);
					foreach ($arrEventsDetails as $row) {
						if ($row['type'] == 'conference') {
							$data[$i]=array($kolArray[$kolsId],$row['name'],$row['event_type'],$row['session_type'],$row['session_name'],$row['role'],$this->kol->getTopicName($row['topic']),$row['start'],$row['end'],$row['organizer'],$row['otype'], $row['session_sponsor'], $row['stype'], $row['location'], $row['address'], $row['Country'], $row['Region'], $row['City'], $row['postal_code']);
						}
						if ($row['type'] == 'online') {
							$data[$i]=array($kolArray[$kolsId],ucwords($row['type']),$row['name'],$row['event_type'],$row['session_type'],$row['role'],$this->kol->getTopicName($row['topic']),$row['start'],$row['end'],$row['organizer'],'','','',$row['location'],$row['Address'],$row['Country'],$row['Region'],$row['City'],'');
						}
						if ($clientId == INTERNAL_CLIENT_ID) {
							if (isset($row['url1ForExport'])) {
								$data[$i][]=$row['url1ForExport'];
							}
							if (isset($row['ur12ForExport'])) {
								$data[$i][]=$row['ur12ForExport'];
							}
						}
						$i++;
					}
				}
			$sheet['title']					='Event';
			$sheet['content']				=$data;
			$sheets[]=$sheet;
		}
		if(in_array('biography',$exportOpts)){
			$data=array();
			$data[0]=array('PIN','Profile Summary','Clinical Research Interests');
			$i=1;
			foreach ($arrKolDetails as $row) {
					$biography_excel = $this->display_profile_summary($row['biography'], 'excel');
					$data[$i]=array($kolArray[$kolsId],$biography_excel,$row['research_interests']);
					$i++;
			}
			$sheet['title']					='Profile_Summary';
			$sheet['content']				=$data;
			$sheets[]=$sheet;
		}
		if(in_array('publication', $exportOpts)){
			if($this->common_helper->check_module("pubmeds")){
				$this->load->model('pubmeds/pubmed');
				$data=array();
				$data[0]=array('PIN', 'Article Title', 'PMID','Journal Name','Date','Authors','Authorship Position');
				$i=1;
				foreach ($arrKolIds as $kolsId){
					$arrPublications = array();
					if ($arrPublicationsResults = $this->pubmed->listPublicationDetails($kolsId,'export')) {
						foreach ($arrPublicationsResults as $arrPublicationsResult) {
							$arrPublication['id'] = $arrPublicationsResult['id'];
							$arrPublication['pmid'] =str_replace('-', '', $arrPublicationsResult['pmid']);
							$arrPublication['journal_name'] = $this->pubmed->getJournalNameById($arrPublicationsResult['journal_id']);
							$arrPublication['article_title'] = $arrPublicationsResult['article_title'];
							$arrPublication['affiliation'] = $arrPublicationsResult['affiliation'];
							$arrPublication['date'] = $this->kol->convertDateToMM_DD_YYYY($arrPublicationsResult['created_date']);
							$arrPublication['authors'] = $this->get_pub_authors($arrPublication['id']);
							$arrPublication['auth_pos'] = $arrPublicationsResult['auth_pos'];
							$arrPublication['kol_id'] = $kolId;
							$arrPublications[] = $arrPublication;
						}
					}
					foreach ($arrPublications as $row) {
						$data[$i]=array($kolArray[$kolsId],$row['article_title'],$row['pmid'],$row['journal_name'],$row['date'],$row['authors'],$row['auth_pos']);
						$i++;
					}
				}
				$sheet['title']					='publication';
				$sheet['content']				=$data;
				$sheets[]=$sheet;
			}
		}
		if($email){
			$arr_export_details['file_name']	=$kolName.'.xls';
			$arr_export_details['sheets']		=$sheets;
			$file_path=export_as_xls($arr_export_details,true);
			return $file_path;
		}else{
			$arr_export_details['file_name']	='KTL_profiles.xls';
			$arr_export_details['sheets']		=$sheets;
			export_as_xls($arr_export_details);
		}
	}
	function display_profile_summary($details_json_format, $type = null) {		
		$biographyTextDetails = json_decode($details_json_format, true);
		$html_container = '';
		if($biographyTextDetails['arrCarrier']['experience']>0){
			if ($biographyTextDetails['arrCarrier']['experience']==1) {
				$html_container .= '<li><span>' .$biographyTextDetails['arrCarrier']['experience']. " Year of experience</span></li>";
			} else {
				$html_container .= '<li><span>' .$biographyTextDetails['arrCarrier']['experience']. " Years of experience</span></li>";
			}
		}
		if (count ( $biographyTextDetails ['arrCarrier'] ['education'] ) > 0) {
			foreach ( $biographyTextDetails ['arrCarrier'] ['education'] as $edu )
				$eduText .=  $edu  . ", ";
				$html_container .= "<li><span>Studied in " . trim ( $eduText, ' ,' ) . "</span></li>";
		}
		if ($biographyTextDetails ['arrCarrier'] ['affiliations'] ['affiliations_left'] > 0) {
			$html_container .= "<li><span>";
			$html_container .= $biographyTextDetails ['arrCarrier'] ['affiliations'] ['university_name'];
			if ($biographyTextDetails ['arrCarrier'] ['affiliations'] ['affiliations_left'] > 0) {
				$html_container .= " and ";
				$html_container .= $biographyTextDetails ['arrCarrier'] ['affiliations'] ['affiliations_left'];
				$html_container .= " other ";
				if ($biographyTextDetails ['arrCarrier'] ['affiliations'] ['affiliations_left'] > 1) {
					$html_container .= "affiliations";
				} else {
					$html_container .= "affiliation";
				}
			}
			$html_container .= "</span></li>";
		} else if ($biographyTextDetails ['arrCarrier'] ['affiliations'] ['university_name'] != '') {
			$html_container .= "<li><span>";
			$html_container .= "Affiliated with ";
			$html_container .= $biographyTextDetails ['arrCarrier'] ['affiliations'] ['university_name'];
			$html_container .= "</span></li>";
		}
		if ($biographyTextDetails ['arrCarrier'] ['speaker_events'] ['count'] > 0) {
			$html_container .= "<li><span>Speaker in ";
			if ($biographyTextDetails ['arrCarrier'] ['speaker_events'] ['count'] > 1) {
				$html_container .= $biographyTextDetails ['arrCarrier'] ['speaker_events'] ['count'] . " sessions";
			} else {
				$html_container .= $biographyTextDetails ['arrCarrier'] ['speaker_events'] ['count'] . " session";
			}
			$html_container .= " in ";
			if ($biographyTextDetails ['arrCarrier'] ['speaker_events'] ['count'] > 1) {
				$html_container .= "conferences";
			} else {
				$html_container .= "conference";
			}
			$html_container .= "</span></li>";
		}
		if ($biographyTextDetails ['arrCarrier'] ['publications'] ['count'] > 0) {
			$html_container .= "<li><span>";
			$html_container .= $biographyTextDetails ['arrCarrier'] ['publications'] ['count'];
			if ($biographyTextDetails ['arrCarrier'] ['publications'] ['count'] == 1) {
				$html_container .= " Publication";
			} else {
				$html_container .= " Publications";
			}
			if ($biographyTextDetails ['arrCarrier'] ['publications'] ['pub_auth_pos_count'] > 0) {
				if ($biographyTextDetails ['arrCarrier'] ['publications'] ['pub_auth_pos_count'] > 1) {
					$html_container .= " with " . $biographyTextDetails ['arrCarrier'] ['publications'] ['pub_auth_pos_count'] . " articles as lead author";
				} else {
					$html_container .= " with " . $biographyTextDetails ['arrCarrier'] ['publications'] ['pub_auth_pos_count'] . " article as lead author";
				}
			}
			$html_container .= "</span></li>";
		}
		if ($biographyTextDetails ['arrCarrier'] ['trails'] ['no_of_trails'] > 0) {
			$html_container .= "<li><span>Investigator in ";
			if ($biographyTextDetails ['arrCarrier'] ['trails'] ['no_of_trails'] > 1) {
				$html_container .= $biographyTextDetails ['arrCarrier'] ['trails'] ['no_of_trails'] . " clinical studies";
			} else {
				$html_container .= $biographyTextDetails ['arrCarrier'] ['trails'] ['no_of_trails'] . " clinical study";
			}
			$html_container .= "</span></li>";
		}
		if ($biographyTextDetails ['arrCarrier'] ['interactions'] ['interactions_by_users_count'] > 0) {
			$html_container .= "<li><span>";
			$html_container .= $biographyTextDetails ['arrCarrier'] ['interactions'] ['interactions_by_users_count'];
			if ($biographyTextDetails ['arrCarrier'] ['interactions'] ['interactions_by_users_count'] > 1) {
				$html_container .= " interactions";
			} else {
				$html_container .= " interaction";
			}
			$html_container .= " entered by ";
			$html_container .= $biographyTextDetails ['arrCarrier'] ['interactions'] ['interactions_count'];
			if ($biographyTextDetails ['arrCarrier'] ['interactions'] ['interactions_count'] > 1) {
				$html_container .= " employees ";
			} else {
				$html_container .= " employee ";
			}
			$html_container .= "in last 6 months</span></li>";
		}
		if (count ( $biographyTextDetails ['arrTopics'] ['topic'] ) > 0) {
			foreach ( $biographyTextDetails ['arrTopics'] ['topic'] as $event ) {
				$arrTopThreeEvents [] = $event ["name"];
			}
			if (count ( $arrTopThreeEvents ) > 0) {
				$html_container .= "<p><label><b>Top Event Topics : </b></label> <span>" . implode ( $arrTopThreeEvents, " | " ) . "</span></p>";
			}
		}
		if (count ( $biographyTextDetails ['arrTopics'] ['pub_mesh_terms'] ) > 0) {
			foreach ( $biographyTextDetails ['arrTopics'] ['pub_mesh_terms'] as $pubTerms ) {
				$arrTopThreePubTerms [] = $pubTerms ["name"];
			}
			if (count ( $arrTopThreePubTerms ) > 0) {
				$html_container .= "<p><label><b>Top Publication Topics : </b></label> <span>" . implode ( $arrTopThreePubTerms, " | " ) . "</span></p>";
			}
		}
		if ($type == 'excel') {
			$replace_list_tags = str_replace("</li>", "\r", $html_container);
			$replace_para_tags = str_replace("<p>", "\r", $replace_list_tags);
			$add_pointer_list = str_replace("<li>", "&#8226; ", $replace_para_tags);
			$str_decode_entity = html_entity_decode($add_pointer_list,ENT_QUOTES,'UTF-8');
			$space_remover = str_replace("	", "", $str_decode_entity);
			$prepared_str_excel = trim(strip_tags($space_remover));
			return $prepared_str_excel;
		} else {
			return $html_container;
		}
	}
	function view($kol_id){
		$arrSalutations = array(0 => '', 'Dr.', 'Prof.', 'Mr.', 'Ms.');
		$data['arrSalutations'] = $arrSalutations;
		// Getting the KOL details
		$kolId = $this->common_helper->getFieldValueByEntityDetails('kols','unique_id',$kol_id,'id');
		// Get the list of Specialties
		$arrSpecialties = $this->speciality->getAllSpecialties('all');
		$data['arrSpecialties'] = $arrSpecialties;
		$arrSubSpecialties = $this->speciality->getAllKolSubSpecialties($kolId);
		$data['arrSubSpecialties'] = $arrSubSpecialties;
		//bio Details
		$arrKolDetail=$this->kol->editKol($kolId);
		$arrKolDetail['org_name'] = $this->kol->getOrgId($arrKolDetail['org_id']);
		if ($arrKolDetail['country_id'] != 0) {
			$arrKolDetail['country_name'] =$this->common_helper->getFieldValueByEntityDetails('countries','CountryID',$arrKolDetail['country_id'],'Country');
		}
		if ($arrKolDetail['city_id'] != 0) {
			$arrKolDetail['city_name'] = $this->country_helper->getCityeById($arrKolDetail['city_id']);
		}
		if ($arrKolDetail['state_id'] != 0) {
			$arrKolDetail['state_name'] = $this->country_helper->getStateById($arrKolDetail['state_id']);
		}
		if($this->common_helper->check_module("clinical_trials")){
			$this->load->model('clinical_trials/clinical_trial');
			$noOfTrials = $this->clinical_trial->countTrials($kolId);
			$data['noOfTrials'] = $noOfTrials;
		}
		if($this->common_helper->check_module("pubmeds")){
			$this->load->model('pubmeds/pubmed');
			
			$noOfPublications = $this->pubmed->countPublications($kolId);
			$data['noOfPublications'] = $noOfPublications;
			
			$biographyTextDetails = array();
			$arrPubYearRange = $this->pubmed->getKolPubsYearsRange($kolId);
			if($arrPubYearRange['max_year']=='')
				$arrPubYearRange['max_year']	=0;
			if($arrPubYearRange['min_year']=='')
				$arrPubYearRange['min_year']	=0;
		}

		//Prepare Experience
		$arrEducations = $this->kol->getKolYearsOfExperience("education",$kolId);
		$yearOfExp = $arrEducations[0]['end_date'];
		
		if($yearOfExp == ''){
			$arrEducations = $this->kol->getKolYearsOfExperience("training",$kolId);
			$yearOfExp = $arrEducations[0]['end_date'];
		}
		if($yearOfExp == ''){
			$arrEducations = $this->kol->getKolYearsOfExperience("board_certification",$kolId);
			$yearOfExp = $arrEducations[0]['start_date'];
		}
		if($yearOfExp == ''){
			$arrEducations		= $this->kol->getKolYearsOfExperienceFromAff($kolId);
			$yearOfExp = $arrEducations[0]['start_date'];
		}
		if($yearOfExp == ''){
			if($this->common_helper->check_module("pubmeds")){
				$this->load->model('pubmeds/pubmed');
				$pubDetails['sidx'] = 'created_date';
				$pubDetails['sord'] = 'asc';
				$arrEducations	= $this->pubmed->searchPublicationsByParameters('','','','','',$kolId,$arrPubYearRange['min_year'],$arrPubYearRange['max_year'],$pubDetails);
				foreach ($arrEducations as $pubData){
					if($pubData['created_date'] != '0000-00-00')
						$arrPubData[] = $pubData;
				}
				if(count($arrPubData)>0)
					$yearOfExp = date("Y",strtotime($arrPubData[0]['created_date']));
			}
		}
		if($yearOfExp != '')
			$biographyTextDetails['experience'] = date("Y") - $yearOfExp;
		
		//Prepare Education
		$arrEducationsDetails = $this->kol->listEducationDetails("education",$kolId);
		$biographyTextDetails['educations'] = $arrEducationsDetails;
		
		//Affiliation
		$arrAffliationsData = $this->kol->listAllAffiliationsDetails($kolId);
		$arrAffliations = array();
		foreach ($arrAffliationsData as $affData){
			$arrAffliations[] = $affData;
		}
		foreach ($arrAffliations as $key => $row) {
			$affName[$key]  = $row['start_date'];
		}
		array_multisort($affName, SORT_DESC,$arrAffliations);
		if($arrAffliations)
			$biographyTextDetails['affiliations'] = $arrAffliations;
		else
			$biographyTextDetails['affiliations'] = array();
		//interactions
		$biographyTextDetails['interactionsCount'] = $this->kol->getInteractionsCountByUsers($kolId,true);
		$biographyTextDetails['interactionsByUsersCount'] = $this->kol->getInteractionsCountByUsers($kolId);
		
		if($this->common_helper->check_module("events")){
			//events topic
			$this->load->model('events/event');
			$arrYearRange					=	$this->event->getEventsYearsRange($kolId);
			if($arrYearRange['max_year']=='')
				$arrYearRange['max_year']	=date("Y");
			if($arrYearRange['min_year']=='')
				$arrYearRange['min_year']	=(int)$arrYearRange['max_year']-35;
			$arrTopEvents = $this->event->getDataForEventsTopicChart($kolId,$arrYearRange['min_year'],$arrYearRange['max_year']);
			$biographyTextDetails['topEventsTopic'] = $arrTopEvents; 
			//speaker events
			$speakerEvents = $this->event->listEvents('conference',$kolId,'','','');
			$arrExcludeEventTopic = array("Admin. & Management", "Admin & Management", "Admin. and Management", "Admin and Management");
			foreach ($speakerEvents as $events){
				if(!in_array($events['topic_name'],$arrExcludeEventTopic)){ //if($events['role'] == 'Speaker')
					$biographyTextDetails['speakerEvents'][] = $events;
				}
			}
		}
		//Publications and mesh terms
		if($this->common_helper->check_module("pubmeds")){
			$this->load->model('pubmeds/pubmed');
			$arrMajorMeshterm 	= $this->pubmed->getTopConceptDataForChart($kolId, $arrPubYearRange['min_year'],$arrPubYearRange['max_year'],'','all');
			
			$this->load->module('pubmeds');
			$topThreeConceptData1 = $this->pubmeds->prepareTopConcepts($arrMajorMeshterm,'','C');
			$arrTopMeshTerms = array_merge($topThreeConceptData1['C']['categoryData']);
			foreach ($arrTopMeshTerms as $key => $row) {
				$meshCount[$key]  = $row['count'];
			}
			$arrSingleAuthPubs		=	$this->pubmed->getKolPubsWithSingleAuthor($kolId,$start,$end,0,0,0,0,$keyWord);
			$arrFirstAuthPubs		=	$this->pubmed->getKolPubsWithFirstAuthorship($kolId,$start,$end,0,0,0,0,$keyWord);
			$arrLastAuthPubs		=	$this->pubmed->getKolPubsWithLastAuthorship($kolId,$start,$end,0,0,0,0,$keyWord);
			$arrMiddleAuthPubs		=	$this->pubmed->getKolPubsWithMiddleAuthorship($kolId,$start,$end,0,0,0,0,$keyWord);
			
			$arrSingleAuthPubsCount		=	sizeof($arrSingleAuthPubs);
			$arrFirstAuthPubsCount		=	sizeof($arrFirstAuthPubs);
			$arrLastAuthPubsCount		=	0;
			$arrMiddleAuthPubsCount		=	0;
			
			foreach($arrLastAuthPubs as $lastAuthPub){
				if($lastAuthPub['auth_pos']==$lastAuthPub['max_pos'] && $lastAuthPub['max_pos']!=1)
					$arrLastAuthPubsCount++;
			}
			$biographyTextDetails['pubAuthPosCount'] = (int)$arrLastAuthPubsCount+(int)$arrFirstAuthPubsCount+(int)$arrSingleAuthPubsCount;
			array_multisort($meshCount, SORT_DESC,$arrTopMeshTerms);
			$biographyTextDetails['topPubMeshTerms'] = $arrTopMeshTerms;
		}
		//calculate profile summary
		$get_profile_summary = $this->generate_profile_summary ($biographyTextDetails, $noOfPublications, $noOfTrials, $arrKolDetail);
		$data ['biographyTextDetails'] = json_decode($get_profile_summary, true);
		
		$data['arrKol']					= $arrKolDetail;
		$data['arrNotes'] 				= $this->kol->getNotes($kolId);
		$data['contentPage']			='view_kol';
		//export options
		$arr_option_data['kol_id']			=$kolId;
		$arr_option_data['assignedUsers'] 	= $this->align_user->getAssignedUsers($kolId);
		$data['options_page']				='export_options_within_kol';
		$data['options_data']				=$arr_option_data;		
		
		$this->load->view(CLIENT_LAYOUT,$data);
	}
	function generate_profile_summary($biographyTextDetails, $noOfPublications, $noOfTrials, $arrKolDetail) {
		$arrCarrier = array ();
		$arrTopics = array ();
		// Experience section
		if ($biographyTextDetails ['experience'] != '' && $biographyTextDetails ['experience'] != 0) {
			if ($biographyTextDetails ['experience'] == 1) {
				$arrCarrier ['experience'] = $biographyTextDetails ['experience'];
			} else {
				$arrCarrier ['experience'] = $biographyTextDetails ['experience'];
				;
			}
		}
		// Educational section
		if (count ( $biographyTextDetails ['educations'] ) > 0) {
			$i = 1;
			foreach ( $biographyTextDetails ['educations'] as $edu ) {
				if (!in_array($edu['institute_id'], $arrBioEduData )){
					if($i>2)
						continue;
						$arrCarrier ['education'] [$i] .=  $edu ['institute_id'];
						$i ++;
				}
				$arrBioEduData[] = $edu ['institute_id'];
			}
		}
		// Affiliation section
		if (count ( $biographyTextDetails ['affiliations'] ) > 0) {
			$primaryAff = trim($arrKolDetail ['org_name']);
			if ($arrKolDetail ['org_name'] != '') {
				$affText = trim($arrKolDetail ['org_name']) . ", ";
			} elseif ($arrKolDetail ['org_id'] != '') {
				$affText = trim($arrKolDetail ['org_id']) . ", ";
			} else {
				$affText = "";
			}
			$arrUniqueAff = array ();
			$count = 0;
			foreach ( $biographyTextDetails ['affiliations'] as $key => $aff ) {
				if (! in_array ( $aff ['name'], $arrUniqueAff ) && trim($aff['name']) != trim($arrKolDetail['org_name'])) {
					if ($count < 1 && $primaryAff != $aff ['name'] && $aff ['type'] == 'university') {
						$affText .= trim($aff ['name']) . ", ";
						$count++;
						
					}else{
						$arrUniqueAff [] = trim($aff ['name']);
					}
					
				}
			}
			if ($affText != '') {
				$numAffLeft = count ( $arrUniqueAff ) ;
			} else {
				$numAffLeft = count ( $arrUniqueAff ) ;
				$affText = trim($arrUniqueAff [0]);
			}
		}
		if ($numAffLeft > 0) {
			$arrCarrier ['affiliations'] ['university_name'] =  trim ( $affText, " ," ) ;
			$arrCarrier ['affiliations'] ['affiliations_left'] = $numAffLeft;
		} else if ($affText != '') {
			$arrCarrier ['affiliations'] ['university_name'] = trim ( $affText, " ,"  );
		}
		// Events section
		if (count ( $biographyTextDetails ['speakerEvents'] ) > 0) {
			$arrCarrier ['speaker_events'] ['count'] = count ( $biographyTextDetails ['speakerEvents'] );
		}
		// Publications Section
		if ($noOfPublications > 0) {
			$arrCarrier ['publications'] ['count'] = $noOfPublications;
			$arrCarrier ['publications'] ['pub_auth_pos_count'] = $biographyTextDetails ['pubAuthPosCount'];
		}
		// Trails Section
		if ($noOfTrials > 0) {
			$arrCarrier ['trails'] ['no_of_trails'] = $noOfTrials;
		}
		// Interactions By Users Count
		if ($biographyTextDetails ['interactionsByUsersCount'] > 0) {
			$arrCarrier ['interactions'] ['interactions_by_users_count'] = $biographyTextDetails ['interactionsByUsersCount'];
			$arrCarrier ['interactions'] ['interactions_count'] = $biographyTextDetails ['interactionsCount'];
		}
		// Event Topics
		if (count ( $biographyTextDetails ['topEventsTopic'] ) > 0) {
			$arrTopicNames = array (
					"Therapeutic and Diagnostic Procedure",
					"Therapeutic & Diagnostic Procedure",
					"Education and Research",
					"Education & Research",
					"Patient and Healthcare",
					"Patient and Healthcare",
					"Professional Ethics",
					"Etiology/Cause of Disease",
					"Disease Complication",
					"Disease Prognosis",
					"Disease Management",
					"Admin. & Management",
					"Admin. and Management",
					"Admin & Management",
					"Admin and Management",
					"Pharmacy Management",
					"Statistics"
			);
			foreach ( $biographyTextDetails ['topEventsTopic'] as $event ) {
				if (! in_array ( $event ["name"], $arrTopicNames )) {
					$arrTopEvents [] = $event;
				}
			}
			$arrTopEvents = array_slice ( $arrTopEvents, 0, 3 );
			$i = 1;
			foreach ( $arrTopEvents as $individual_event ) {
				$arrTopics ['topic'] [$i] ['name'] = $individual_event ['name'];
				$i ++;
			}
		}
		// Publication Mesh Terms
		if (count ( $biographyTextDetails ['topPubMeshTerms'] ) > 0) {
			$arrTopPubMeshTerms = array ();
			$arrTopPubMeshTerms = array_slice ( $biographyTextDetails ['topPubMeshTerms'], 0, 3 );
			$i = 1;
			foreach ( $arrTopPubMeshTerms as $individual_pubmeshterms ) {
				$arrTopics ['pub_mesh_terms'] [$i] ['name'] = $individual_pubmeshterms ['name'];
				$i ++;
			}
		}
		$arrGroup = array ();
		$arrGroup ['arrCarrier'] = $arrCarrier;
		$arrGroup ['arrTopics'] = $arrTopics;
		$structured_json_data = json_encode ( $arrGroup );
		// Store structured_json_data in database
		$update_profile_summary = $this->kol->updateKolProfileSummary ( $structured_json_data, $arrKolDetail ['id'] );
		if ($update_profile_summary == true) {
			return $structured_json_data;
		} else {
			return false;
		}
	}
	function dashboard($kol_id){
		$kolId = $this->common_helper->getFieldValueByEntityDetails('kols','unique_id',$kol_id,'id');
		$arrKolDetail = $this->kol->editKol($kolId);
		$data['arrKol']					= $arrKolDetail;
		$arrcontentData['clientKols'] = implode(',',$this->kol->getAllKolIdsSpecificToClient());
		
		$arr_option_data['kol_id']			=$arrKolDetail['id'];
		$arr_option_data['assignedUsers'] 	= $this->align_user->getAssignedUsers($kolId);
		$data['options_page']				='export_options_within_kol';
		$data['options_data']				=$arr_option_data;
		
		$data['contentPage']			='dashboard';
		$data['contentData']			=$arrcontentData;
		
		$this->load->view(CLIENT_LAYOUT,$data);
	}
	function list_education_grid_details($type, $kolId = null, $isGrid=false) {
		$arrEducationResults = array();
		$arrEducationDetails = array();
		$arrEducationGridResults = array();
		$arrEducation = array();
		$data = array();
		
		if ($arrEducationResults = $this->kol->listEducationDetails($type, $kolId)) {
			foreach ($arrEducationResults as $row) {
				$row['date'] = '';
				if ($row['start_date'] != '')
					$row['date'] .= $row['start_date'];
				else
					$row['date'] .= 'NA';
						//	if(($row['start_date'] != '') && ($row['end_date']))
				$row['date'] .= " - ";
				if ($row['end_date'] != '')
					$row['date'] .= $row['end_date'];
				else
					$row['date'] .= 'NA';
				if ($row['date'] == 'NA - NA') {
					$row['date'] = '';
				}
				$row['year'] = date_display($row['year']);
				$arrEducationGridResults[] = $row;
			}
			foreach($arrEducationGridResults as $key=>$value){
				if($value['type']=='education'){
					$arrEducationGridResults[$key]['type_name']	= "Education";
					$arrEducationGridResults[$key]['type'] = "education";
					$arrEducation['education'][]			= $arrEducationGridResults[$key];
				}
				if($value['type']=='training'){
					$arrEducationGridResults[$key]['type_name']	= "Training";
					$arrEducationGridResults[$key]['type'] = "training";
					$arrEducation['training'][]				= $arrEducationGridResults[$key];
				}
				if($value['type']=='honors_awards'){
					$arrEducationGridResults[$key]['type_name']	= "Honors and Awards";
					$arrEducationGridResults[$key]['type'] = "honors";
					$arrEducationGridResults[$key]['institute_id']	= $arrEducationGridResults[$key]['honor_name'];
					$arrEducation['honors_awards'][]		= $arrEducationGridResults[$key];
				}
				if($value['type']=='board_certification'){
					$arrEducationGridResults[$key]['type_name']	= "Board Certification";
					$arrEducationGridResults[$key]['type'] = "board_certification";
					$arrEducation['board_certification'][]	= $arrEducationGridResults[$key];
				}
				$arrEducation['allEduDetails'][]	= $arrEducationGridResults[$key];
			}
			//$data['rows'] 		= 	$arrEducationGridResults;
		}
		if ($arrContactsResults = $this->kol->listAdditionalContacts($kolId)) {
			foreach ($arrContactsResults as $row) {
				$arrEducation['additionalContacts'][] = $row;
			}
			//pr($arrEducation['additionalContacts']);
		}
		if($isGrid){
			$data= array();
			foreach ($arrEducation['allEduDetails'] as $row) {
				$arr[] = $row;
			}
			$page = (int)$this->input->post('page'); // get the requested page
			$limit = (int)$this->input->post('rows'); // get how many rows we want to have into the grid
			$count = sizeof($arr);
			if ($count > 0) {
				$total_pages = ceil($count / $limit);
			} else {
				$total_pages = 0;
			}
			$data['records'] = $count;
			$data['total'] = $total_pages;
			$data['page'] = $page;
			$data['rows'] = $arr;
			echo json_encode($data);
		}else{
			echo json_encode($arrEducation);
		}
	}
	function list_all_affiliations_details($kolId = null) {
		$client_id = $this->session->userdata('client_id');
		$page = (int) $this->input->post('page'); // get the requested page
		$limit = (int) $this->input->post('rows'); // get how many rows we want to have into the grid
		$arrMembershipResult = array();
		$data = array();
		$arrMembership = array();
		if ($arrMembershipResult = $this->kol->listAllAffiliationsDetails($kolId)) {
			foreach ($arrMembershipResult as $row) {
				if ($row['type'] == "university") {
					$row['type'] = "Univ/Hospital";
				} else {
					$row['type'] = ucfirst($row['type']);
				}
				//$row['type'] = ucwords($row['type']);
				$row['date'] = '';
				if ($row['start_date'] != '')
					$row['date'] .= $row['start_date'];
				else
					$row['date'] .= 'NA';
				$row['date'] .= " - ";
				if ($row['end_date'] != '')
					$row['date'] .= $row['end_date'];
				else
					$row['date'] .= 'NA';
				if ($row['date'] == 'NA - NA') {
					$row['date'] = '';
				}
				$arrMembership[] = $row;
			}
			$count = sizeof($arrMembership);
			if ($count > 0) {
				$total_pages = ceil($count / $limit);
			} else {
				$total_pages = 0;
			}
			$data['records'] = $count;
			$data['total'] = $total_pages;
			$data['page'] = $page;
			$data['rows'] = $arrMembership;
		}
		ob_start('ob_gzhandler');
		echo json_encode($data);
	}
	function export_pdf($kolId) {
// 		ini_set('memory_limit', "800M");
		$arrhtml = $this->get_mini_profile_as_html($kolId, 'Pdf');
		$html = $arrhtml[0];
		$filename = $arrhtml[1];
		export_as_pdf($filename,$html);
	}
	function get_mini_profile_as_html($kolId, $returnTo) {
		ini_set('memory_limit', "-1");
		ini_set("max_execution_time", 0);
		//$this->load->plugin('export_pdf');
		$arrEducationResults = array();
		$arrEducationDetails = array();
		$arrEducationGridResults = array();
		$data = array();
		$arrMembershipResult = array();
		$arrEventsResults = array();
		$arrMembership = array();
		$arrEvent = array();
		// Getting the Education Details
		if ($arrEducationResults = $this->kol->listAllEducationDetails($kolId)) {
			foreach ($arrEducationResults as $row) {
				$row['date'] = $row['start_date'] . '-' . $row['end_date'];
				if ($row['start_date'] != '') {
					$row['date'] = $row['start_date'];
				}
				if ($row['end_date'] != '' && $row['start_date'] != '') {
					$row['date'] .= '-' . $row['end_date'];
				}
				if ($row['start_date'] == '' && $row['end_date'] != '') {
					$row['date'] = $row['end_date'];
				}
				if ($row['start_date'] == '' && $row['end_date'] == '') {
					$row['date'] = ' ';
				}
				$arrEducationGridResults[] = $row;
			}
		}
		$data['arrEducation'] = $arrEducationGridResults;
		// Getting the Membership Details
		if ($arrMembershipResult = $this->kol->listAllMembershipsDetails($kolId)) {
			foreach ($arrMembershipResult as $row) {
				if ($row['start_date'] != '') {
					$row['date'] = $row['start_date'];
				}
				if ($row['end_date'] != '' && $row['start_date'] != '') {
					$row['date'] .= '-' . $row['end_date'];
				}
				if ($row['start_date'] == '' && $row['end_date'] != '') {
					$row['date'] = $row['end_date'];
				}
				if ($row['start_date'] == '' && $row['end_date'] == '') {
					$row['date'] = ' ';
				}
				$arrMembership[] = $row;
			}
		}
		foreach ($arrMembership as $key1 => $row) {
			if ($key1 < (sizeof($arrMembership) - 1)) {
				if ($arrMembership[$key1 + 1]['engagement_type'] == $row['engagement_type'] && $arrMembership[$key1 + 1]['type'] == $row['type']) {
					$arrMembership[$key1 + 1]['engagement_type'] = '';
				}
			}
		}
		$data['arrMembership'] = $arrMembership;
		// Getting the Events Details
		if ($arrEventsResults = $this->kol->listAllEvents($kolId, $limit = 20)) {
			foreach ($arrEventsResults as $row) {
				if ($row['start'] != '') {
					$row['date'] = $row['start'];
				}
				if ($row['end'] != '' && $row['start'] != '') {
					$row['date'] .= '-' . $row['end'];
				}
				if ($row['start'] == '' && $row['end'] != '') {
					$row['date'] = $row['end'];
				}
				if ($row['start'] == '' && $row['end'] == '') {
					$row['date'] = ' ';
				}
				$arrEvent[] = $row;
			}
		}
		foreach ($arrEvent as $key1 => $row) {
			if ($key1 < (sizeof($arrEvent) - 1)) {
				if ($arrEvent[$key1 + 1]['session_type'] == $row['session_type']) {
					$arrEvent[$key1 + 1]['session_type'] = '';
				}
			}
		}
		$data['arrEvent'] = $arrEvent;
		// Getting the KOL details
		$arrKolDetail = array();
		$arrKolDetail = $this->kol->editKol($kolId);
		if(!empty($arrKolDetail['org_id']) || $arrKolDetail['org_id'] != 0)
			$arrKolDetail['org_name'] = $this->kol->getOrgId($arrKolDetail['org_id']);
		else
			$arrKolDetail['org_name'] = $arrKolDetail['private_practice'];
				
		if ($arrKolDetail['country_id'] != 0) {
			$arrKolDetail['country_id'] = $this->country_helper->getCountryById($arrKolDetail['country_id']);
		}
		if ($arrKolDetail['state_id'] != 0) {
			$arrKolDetail['state_id'] = $this->country_helper->getStateById($arrKolDetail['state_id']);
		}
		if ($arrKolDetail['city_id'] != 0) {
			$arrKolDetail['city_id'] = $this->country_helper->getCityeById($arrKolDetail['city_id']);
		}
		if ($arrKolDetail['specialty'] != 0) {
			$specialty = $this->speciality->getSpecialtyById($arrKolDetail['specialty']);
			$arrKolDetail['specialtyName'] = $this->common_helper->isDataNull($specialty) ? "" : $specialty;
		}
		if($arrKolDetail['biography'] != null){
			$arrKolDetail['biography'] = $this->display_profile_summary($arrKolDetail['biography'], 'html');
		}
		$arrSubSpecialties = $this->speciality->getAllKolSubSpecialties($kolId);
		$arrKolDetail['sub_specialty'] = array_filter($arrSubSpecialties);
		$data['arrKol'] = $arrKolDetail;
		//pr($arrKolDetail);
		// Getting the ContactDetails
		$arrContactDetails = array();
		if ($arrContactDetails = $this->kol->listContacts($kolId)) {
			$data['arrContact'] = $arrContactDetails;
		}
		// Getting the Salutations
		$arrSalutations = array(0 => '', 'Dr.', 'Prof.', 'Mr.', 'Ms.');
		$data['arrSalutations'] = $arrSalutations;
		
		//Start of publications
		if($this->common_helper->check_module("pubmeds")){
			$this->load->model('pubmeds/pubmed');
			$arrPublications = array();
			$limitValue = '20';
			if ($arrPublicationsResults = $this->pubmed->listPublicationDetailsByLimit($kolId, $limitValue)) {
				foreach ($arrPublicationsResults as $arrPublicationsResult) {
					$arrPublication['issn_number'] = $arrPublicationsResult['issn_number'];
					$arrPublication['volume'] = $arrPublicationsResult['volume'];
					$arrPublication['id'] = $arrPublicationsResult['id'];
					$arrPublication['pmid'] = '<a href=\'' . $arrPublicationsResult['link'] . '\' target="_new">' . $arrPublicationsResult['pmid'] . '</a>';
					$arrPublication['journal_name'] = $this->pubmed->getJournalNameById($arrPublicationsResult['journal_id']);
					$arrPublication['article_title'] = $arrPublicationsResult['article_title'];
					$arrPublication['affiliation'] = $arrPublicationsResult['affiliation'];
					$arrPublication['date'] = $arrPublicationsResult['created_date'];
					$arrPublication['authors'] = $this->get_pub_authors($arrPublication['id']);
					$arrPublication['subject'] = '';
					$arrPublications[] = $arrPublication;
				}
				$data['arrPublications'] = $arrPublications;
			}
		}
		//End of publications
		//Start of Clinical trials
		$arrClinicalTrials = array();
		if($this->common_helper->check_module("clinical_trials")){
			$this->load->model('clinical_trials/clinical_trial');
			if ($arrClinicalTrialsResults = $this->clinical_trial->listClinicalTrialsDetails($kolId)) {
				foreach ($arrClinicalTrialsResults as $arrClinicalTrialsResult) {
					$arrClinicalTrial['id'] = $arrClinicalTrialsResult['id'];
					if ($arrClinicalTrialsResult['start_date'] != '') {
						$arrClinicalTrialsResult['date'] = $arrClinicalTrialsResult['start_date'];
					}
					if ($arrClinicalTrialsResult['end_date'] != '' && $arrClinicalTrialsResult['start_date'] != '') {
						$arrClinicalTrialsResult['date'] .= '-' . $arrClinicalTrialsResult['end_date'];
					}
					if ($arrClinicalTrialsResult['start_date'] == '' && $arrClinicalTrialsResult['end_date'] != '') {
						$arrClinicalTrialsResult['date'] = $arrClinicalTrialsResult['end_date'];
					}
					if ($arrClinicalTrialsResult['start_date'] == '' && $arrClinicalTrialsResult['end_date'] == '') {
						$arrClinicalTrialsResult['date'] = ' ';
					}
					$arrClinicalTrial['date'] = $arrClinicalTrialsResult['date'];
					$arrClinicalTrial['ct_id'] = '<a href=\'' . $arrClinicalTrialsResult['link'] . '\' target="_new">' . $arrClinicalTrialsResult['ct_id'] . '</a>';
					$arrClinicalTrial['trial_name'] = $arrClinicalTrialsResult['trial_name'];
					$arrClinicalTrial['status'] = $this->clinical_trial->getStatusNameById($arrClinicalTrialsResult['status_id']);
					$this->load->module('clinical_trials');//load controller
					$arrClinicalTrial['sponsors'] = $this->clinical_trials->get_sponsers($arrClinicalTrialsResult['id']);
					$arrClinicalTrial['condition'] = $arrClinicalTrialsResult['condition'];
					$arrClinicalTrial['interventions'] = $this->clinical_trials->get_interventions($arrClinicalTrialsResult['id']);
					$arrClinicalTrial['phase'] = $arrClinicalTrialsResult['phase'];
					$arrClinicalTrial['investigators'] = $this->clinical_trials->get_investigators($arrClinicalTrialsResult['id']);
					$arrClinicalTrial['study_type'] = $arrClinicalTrialsResult['study_type'];
					$arrClinicalTrial['kol_role'] = $arrClinicalTrialsResult['kol_role'];
					$arrClinicalTrial['no_of_enrollees'] = $arrClinicalTrialsResult['no_of_enrollees'];
					$arrClinicalTrial['no_of_trial_sites'] = $arrClinicalTrialsResult['no_of_trial_sites'];
					$arrClinicalTrial['start_date'] = $arrClinicalTrialsResult['start_date'];
					$arrClinicalTrial['end_date'] = $arrClinicalTrialsResult['end_date'];
					$arrClinicalTrial['min_age'] = $arrClinicalTrialsResult['min_age'];
					$arrClinicalTrial['max_age'] = $arrClinicalTrialsResult['max_age'];
					$arrClinicalTrial['gender'] = $arrClinicalTrialsResult['gender'];
					$arrClinicalTrial['collaborator'] = $arrClinicalTrialsResult['collaborator'];
					$arrClinicalTrial['purpose'] = $arrClinicalTrialsResult['purpose'];
					$arrClinicalTrial['official_title'] = $arrClinicalTrialsResult['official_title'];
					$arrKeywordsData = '';
					$separator = '';
					foreach ($this->clinical_trial->listCTIKeyWords($arrClinicalTrialsResult['id']) as $key => $row) {
						$arrKeywordsData .= $separator . $row['name'];
						$separator = ',';
					}
					$arrClinicalTrial['keywords'] = $arrKeywordsData;
					$arrMeshtermsData = '';
					$separator = '';
					foreach ($this->clinical_trial->listCTMeshTerms($arrClinicalTrialsResult['id']) as $key => $row) {
						$arrMeshtermsData .= $separator . $row['term_name'];
						$separator = ',';
					}
					$arrClinicalTrial['mesh_terms'] = $arrMeshtermsData;
					$arrClinicalTrial['url'] = $arrClinicalTrialsResult['link'];
					$arrClinicalTrials[] = $arrClinicalTrial;
				}
				$data['arrClinicalTrials'] = $arrClinicalTrials;
			}
		}
		//End of Clinical trials
		//start of mesh terms
		if($this->common_helper->check_module("pubmeds")){
			$this->load->model('pubmeds/pubmed');
			$arrMajorMeshterm = $this->pubmed->getPubMajorMeshTermChartForPdf($kolId);
			$meshTerms = array();
			$count = array();
			foreach ($arrMajorMeshterm as $meshterm) {
				$termName = '';
				$parentId = $meshterm['parent_id'];
				if ($parentId != 0 && $parentId != null) {
					$parentName = $this->pubmed->getMeshTermName($parentId);
					$termName = $parentName . "/" . $meshterm['mesh_term'];
				} else {
					$termName = $meshterm['mesh_term'];
				}
				$meshTerms[] = ucwords($termName);
			}
			$data['arrMajorTerms'] = implode(', ', $meshTerms);
			$arrMinorMeshterm = $this->pubmed->getPubMinorMeshTermChartForPdf($kolId);
			$meshTerms1 = array();
			foreach ($arrMinorMeshterm as $mesTerm1) {
				$termName1 = '';
				$termName1 = $mesTerm1['mesh_term'];
				$meshTerms1[] = ucwords($termName1);
			}
		}
		
		$data['locationData'] = $this->kol->getDetailsInfoById($kolId,'Location');
		$data['phoneData'] = $this->kol->getDetailsInfoById($kolId,'Phone');
		$data['emailData'] = $this->kol->getDetailsInfoById($kolId,'Email');
		$data['licenseData'] = $this->kol->getDetailsInfoById($kolId,'License');
		$data['specialtyData'] = $this->kol->getDetailsInfoById($kolId,'Specialty');
		$data['staffData'] = $this->kol->getDetailsInfoById($kolId,'Staff');
		$data['arrMinorTerms'] = implode(', ', $meshTerms1);
		if ($returnTo == 'Pdf') {
			$html = $this->load->view("kols/kol_pdf", $data, true);
		} else {
			$html = $this->load->view("kols/export/kol_profile_for_email", $data, true);
		}
		$filename = $this->common_helper->get_name_format($arrKolDetail['first_name'],$arrKolDetail['middle_name'],$arrKolDetail['last_name']);
		$arrHtml = array($html, $filename);
		return $arrHtml;
	}
	function add_kol($kol_id=NULL,$sub_content_page=''){
		$arrKolDetail=array();
		if($kol_id!=NULL){
			$kolId 									= $this->common_helper->getFieldValueByEntityDetails('kols','unique_id',$kol_id,'id');
			$arrKolDetail							= $this->kol->editKol($kolId);
			$selectedSuffixes 						= explode(',',$arrKolDetail['suffix']);
			$data['selectedSuffixes'] 				= array_map('trim',$selectedSuffixes);
			$data['arrKolProducts'] 				= $this->kol->getKolProducts($kolId);
			$data['arrSubSpecialties']				= $this->speciality->getAllKolSubSpecialties($kolId);
			$kolLocationDetails 					= $this->kol->getKolPrimaryLocation($kolId);
			$data['arrLocationData'] 				= $kolLocationDetails[0];
			$arr 									= $this->kol->getKolPrimaryPhoneDetails($kolId);
			$data['arrLocationData']['phone_number']= $arr['number'];
			$data['arrLocationData']['phone_type'] 	= $arr['type'];
			$data['arrLocationData']['org_institution_name'] = $this->organization->getOrgNameByOrgId($data['arrLocationData']['org_institution_id']);
			$data['arrOrgDetails'] 							= $this->organization->editOrganization($data['arrLocationData']['org_institution_id']);
			$data['orgTypeId'] 								= $data['arrOrgDetails']['type_id'];
			$data['arrStates'] 								= $this->country_helper->getStatesByCountryId($arrKolDetail['country_id']);
			if ($arrKolDetail['state_id'] != '' && $arrKolDetail['state_id'] != 0){
				$data['arrCities'] = $this->country_helper->getCitiesByStateId($arrKolDetail['state_id']);
			}
			//export options
			$arr_option_data['kol_id']			=$kolId;
			$arr_option_data['assignedUsers'] 	= $this->align_user->getAssignedUsers($kolId);
			$data['options_page']				='export_options_within_kol';
			$data['options_data']				=$arr_option_data;
		}
		$arrKoldetails['arrSpecialties'] 			= $this->speciality->getAllSpecialties('all');
		$arrKoldetails['arrTitles'] 				= $this->kol->getAllActiveTitles('all');
		$arrKoldetails['arrProfessionalSuffixes']	= $this->kol->getAllActiveProfessionalSuffixes();
		$arrKoldetails['arrProducts'] 				= $this->common_helper->getUserProducts($this->loggedUserId);
		$arrKoldetails['arrCountries'] 				= $this->country_helper->listCountries();
		$arrKoldetails['arrOrganizationTypes'] 		= $this->organization->getAllOrganizationTypes();
		$arrKoldetails['arrPhoneType'] 				= $this->kol->getPhoneType();
		$data['sub_content_page']		=$sub_content_page;
		$data['arrKolData'] 			= $arrKolDetail;
		
		$module_name		='kols';
		$data['module_id']	=$this->common_helper->getModuleIdByModuleName($module_name);
		
// 		echo $this->db->last_query();
		$data['contentPage']			='add_kol';
		$data['contentData']			=$arrKoldetails;
		$this->load->view(CLIENT_LAYOUT,$data);
	}
	function get_organization_names($organizationName,$restrictByRegion=0) {
		if($organizationName!="keyword"){
			$currentkolName  = $organizationName;
			$organizationName   = $restrictByRegion;
			$restrictByRegion  = $currentkolName;
		}
		$organizationName = urldecode($this->input->post($organizationName));
		$arrOrgNames = $this->kol->getOrganizationNamesWithStateCity($organizationName,$restrictByRegion);
		$arrOrgs = array();
		if (sizeof($arrOrgNames) == 0) {
			$arrSuggestOrgs[0] = 'No results found for ' . $organizationName;
		} else {
			$flag = 1;
			foreach ($arrOrgNames as $row) {
				$cityState=$row['city'];
				if(isset($row['city']) && isset($row['state']))
					$cityState.= ', ';
					$cityState.=$row['state'];
					if ($flag) {
						$arrSuggestOrgs[] = '<div class="autocompleteHeading">Organizations</div><div class="dataSet"><label name="' . $row['id'] . '" class="organizations" style="display:block">' . $row['name'] . "</label><label>$cityState</label></div>";
						$flag = 0;
					} else {
						$arrSuggestOrgs[] = '<div class="dataSet"><label name="' . $row['id'] . '" class="organizations" style="display:block">' . $row['name'] . "</label><label>$cityState</label></div>";
					}
			}
		}
		$arrReturnData['query'] = $organizationName;
		$arrReturnData['suggestions'] = $arrSuggestOrgs;
		echo json_encode($arrReturnData);
	}
	function list_locations($kolId = null) {
		$arrLocations	= array();
		$page = (int) $this->input->post('page'); // get the requested page
		$limit = (int) $this->input->post('rows'); // get how many rows we want to have into the grid
		$locations = array();
		$locations = $this->kol->listLocationDetails($kolId);
		foreach ($locations as $row) {
			$row['kol_id'] = $kolId;
			$row['address'] = trim($row['address'], ', ');
			$row['eAllowed'] = $this->common_helper->isActionAllowed('kol_details', 'edit', $row);
			$row['dAllowed'] = $this->common_helper->isActionAllowed('kol_details', 'delete', $row);
			if ($row['is_primary']){
				$row['is_primary'] = "<span class='is_primary'>&nbsp;</span>";
				$row['is_primary_value'] = 1;
			}else{
				$row['is_primary'] = "";
				$row['is_primary_value'] = 0;
			}
			if(!empty($row['private_practice']))
				$row['org_name'] = $row['private_practice'];
			else
				$row['org_name'] = $row['org_name'];
				$arrLocations[] = $row;
		}
// 		$data['kolId'] = $kolId;
// 		$data['locations'] = $arrLocations;
		//$data	= $arrLocations;
		$total_pages	= 0;
		$data	= array();
		$count = sizeof($arrLocations);
		if ($count > 0) {
			$total_pages = ceil($count / $limit);
		} else {
			$total_pages = 0;
		}
		$data['records'] = $count;
		$data['total'] = $total_pages;
		$data['page'] = $page;
		$data['rows'] = $arrLocations;
		echo json_encode($data);
	}
	function list_kol_details($type,$kolId = null) {
		$page = (int) $this->input->post('page'); // get the requested page
		$limit = (int) $this->input->post('rows'); // get how many rows we want to have into the grid
		
		if($type == 'phone'){
			$responce = array();
			$phone = array();
			$phone = $this->kol->getPhones($kolId, 'kol');
			foreach ($phone as $row) {
				$responce->rows[$i]['id']=$row['id'];
				$row['kol_id'] = $kolId;
				$row['eAllowed'] = $this->common_helper->isActionAllowed('kol_details', 'edit', $row);
				$row['dAllowed'] = $this->common_helper->isActionAllowed('kol_details', 'delete', $row);
				if ($row['is_primary']==0)
					$row['is_primary'] = "No";
				else
					$row['is_primary'] = "Yes";
				$responce[] = $row;
			}
		}
		if($type == 'staff'){
			$responce = array();
			$staff = array();
			$staff = $this->kol->getStaffs($kolId, 'kol');
			foreach ($staff as $row) {
				$responce->rows[$i]['id']=$row['id'];
				$row['kol_id'] = $kolId;
				$row['eAllowed'] = $this->common_helper->isActionAllowed('kol_details', 'edit', $row);
				$row['dAllowed'] = $this->common_helper->isActionAllowed('kol_details', 'delete', $row);
				$responce[] = $row;
			}
		}
		if($type == 'emails'){
			$responce = array();
			$emails = array();
			$emails = $this->kol->getEmails($kolId, 'kol');
			foreach ($emails as $row) {
				$responce->rows[$i]['id']=$row['id'];
				$row['kol_id'] = $kolId;
				$row['eAllowed'] = $this->common_helper->isActionAllowed('kol_details', 'edit', $row);
				$row['dAllowed'] = $this->common_helper->isActionAllowed('kol_details', 'delete', $row);
				if ($row['is_primary']==0)
					$row['is_primary'] = "No";
				else
					$row['is_primary'] = "Yes";
				$responce[] = $row;
			}
		}
		if($type == 'statelicense'){
			$responce = array();
			$statelicense = array();
			$statelicense = $this->kol->getStateLicences($kolId, 'kol');
			foreach ($statelicense as $row) {
				$responce->rows[$i]['id']=$row['id'];
				$row['kol_id'] = $kolId;
				$row['eAllowed'] = $this->common_helper->isActionAllowed('kol_details', 'edit', $row);
				$row['dAllowed'] = $this->common_helper->isActionAllowed('kol_details', 'delete', $row);
				if ($row['is_primary']==0)
					$row['is_primary'] = "No";
				else
					$row['is_primary'] = "Yes";
				$responce[] = $row;
			}
		}
		if($type == 'assign'){
			$responce = array();
			$assignedUsers = array();
			$assignedUsers = $this->kol->getAssignedUsers($kolId);
			foreach ($assignedUsers as $row) {
				$responce->rows[$i]['id']=$row['id'];
				$row['kol_id'] = $kolId;
				if(ROLE_USER && $this->session->userdata('user_id') == $row['user_id']){
					$row['eAllowed'] = true;
					$row['dAllowed'] = true;
				}else{
					$row['eAllowed'] = $this->common_helper->isActionAllowed('kol_details', 'edit', $row);
					$row['dAllowed'] = $this->common_helper->isActionAllowed('kol_details', 'delete', $row);
				}
				$responce[] = $row;
			}
		}
		$total_pages	= 0;
		$data	= array();
		$count = sizeof($responce);
		if ($count > 0) {
			$total_pages = ceil($count / $limit);
		} else {
			$total_pages = 0;
		}
		$data['records'] = $count;
		$data['total'] = $total_pages;
		$data['page'] = $page;
		$data['rows'] = $responce;
		echo json_encode($data);
	}
	function check_duplicate_ol() {
		$data = array();
		$arrData['kol_id'] = $this->input->post('kol_id');
		$arrData['first_name'] = $this->input->post('first_name');
		$arrData['last_name'] = $this->input->post('last_name');
		$arrData['specialty'] = $this->input->post('specialty');
		$arrDuplicates = $this->kol->checkDuplicateOL($arrData);
		if (count($arrDuplicates) > 0) {
			$data['duplicate_found'] = 1;
		} else
			$data['duplicate_found'] = 0;
			echo json_encode($data);
	}
	function save_ol($replaceFlag = 0) {
		$dataType = 'User Added';
		$client_id =$this->session->userdata('client_id');
		if($client_id == INTERNAL_CLIENT_ID){
			$dataType = 'Aissel Analyst';
		}
		$kol_id = $this->input->post('kol_id');
		$arrKolData = array();
		$arrKolData['fma_speciality_id'] = '';
		$arrKolData['npi_num'] = trim($this->input->post('npi_num'));
		$arrProducts = $this->input->post('products');
		$suffix = implode(',',$this->input->post('prof_suffix'));
		$arrSpeakerProducts = $this->input->post('speaker_products');
		$arrSubSpecialty = $this->input->post('sub_specialty');
		$arrKolData['additional_role_id'] = trim($this->input->post('additional_role_id'));
		$arrKolData['is_speaker'] = $this->input->post('is_speaker');
		$arrKolData['degree_id'] = trim($this->input->post('degree_id'));
		$arrKolData['first_name'] = trim($this->input->post('first_name'));
		$arrKolData['middle_name'] = trim($this->input->post('middle_name'));
		$arrKolData['primary_phone'] = trim($this->input->post('phone_number_loc'));
		$arrKolData['external_profile_id'] = trim($this->input->post('crm_id'));
		$arrKolData['last_name'] = trim($this->input->post('last_name'));
		$arrKolData['suffix'] = $suffix;
		$arrKolData['specialty'] = trim($this->input->post('specialty'));
		$org_id = trim($this->input->post('org_institution_id'));
		$is_private_practice = trim($this->input->post('private_practice'));
		$city_id = trim($this->input->post('city_id'));
		if (empty($org_id)) {
			$arrOrgData = array();
			$arrOrgData['address'] = trim($this->input->post('address1')) . " " . trim($this->input->post('address2'));
			$arrOrgData['status'] = '';
			$city_id = trim($this->input->post('city_id'));
			if (!empty($city_id)) {
				if(is_numeric($city_id)){
					$arrOrgData['city_id'] = trim($this->input->post('city_id'));
					$city_id = $arrOrgData['city_id'];
				}else{
					$city_id = $this->kol->checkCityIfExistElseAdd($city_id,trim($this->input->post('state_id')),trim($this->input->post('country_id')));
					$arrOrgData['city_id'] = $city_id;
				}
			} else {
				$arrOrgData['city_id'] = "";
			}
			$arrOrgData['name'] = trim($this->input->post('organization'));
			$arrOrgData['state_id'] = trim($this->input->post('state_id'));
			$arrOrgData['country_id'] = trim($this->input->post('country_id'));
			$arrOrgData['postal_code'] = trim($this->input->post('postal_code'));
			$arrOrgData['created_by'] = $this->loggedUserId;
			$arrOrgData['created_on'] = date('Y-m-d H:i:s');
			$arrOrgData['modified_by'] = $this->loggedUserId;
			$arrOrgData['modified_on'] = date('Y-m-d H:i:s');
			$arrOrgData['type_id'] = trim($this->input->post('org_type'));
			$arrOrgData['profile_type'] = 1;
			$arrOrgData['status_otsuka'] = "ACTV";
			$arrOrgData['status'] = "Completed";
			$org_id = $this->organization->saveOrganization($arrOrgData);
			//Save org visibility
			if(ORGS_VISIBILITY){
				$orgVisibility = array();
				$orgVisibility['org_id'] = $org_id;
				$orgVisibility['client_id'] = $this->session->userdata('client_id');
				$orgVisibility['associationFlag'] = 'associate';
				$this->organization->saveOrgClientAssociation($orgVisibility);
			}
			//{Add Log activity}
			$orgData['org_id'] 			= $org_id;
			$orgData['address1'] 			= trim($this->input->post('address1'));
			$orgData['address2'] 			= trim($this->input->post('address2'));
			$orgData['address_type'] 		= trim($this->input->post('address_type'));
			$orgData['country_id'] 	= $this->input->post('country_id');
			$orgData['state_id'] 	= $this->input->post('state_id');
			$orgData['city_id'] 		= $this->input->post('city_id');
			$orgData['postal_code'] 	= $this->input->post('postal_code');
			$orgData['phone_number_primary'] 	= $this->input->post('phone_number_loc');
			$orgData['phone_type_primary'] 	= $this->input->post('phone_type_loc');
			$orgData['is_primary'] 			= 1;
			$orgData['created_by'] 		= $this->loggedUserId;
			$orgData['created_on'] 		= date('Y-m-d H:i:s');
			$orgData['modified_by'] 		= $this->loggedUserId;
			$orgData['modified_on'] 		= date('Y-m-d H:i:s');
			$orgLocLasId = $this->organization->saveLocation($orgData);
			//Add Log activity
			if(isset($orgData['phone_type_primary']) && $orgData['phone_number_primary'] > 0){
				$orgPhone = array();
				$orgPhone['type'] = $this->input->post('phone_type_loc');
				$orgPhone['number'] = $this->input->post('phone_number_loc');
				$orgPhone['contact_type'] = 'organization';
				$orgPhone['contact'] = $org_id;
				$orgPhone['is_primary'] = 1;
				$orgPhone['location_id'] = $orgLocLasId;
				$orgPhone['created_by'] = $this->loggedUserId;
				$orgPhone['created_on'] = date('Y-m-d H:i:s');
				$lastPhoneId = $this->kol->savePhone($orgPhone);
				//Add Log activity
			}
		}
		$typeId = trim($this->input->post('org_type'));
		if (empty($typeId)) {
			$arrOrgType = array();
			$arrOrgType['id'] = $org_id;
			$arrOrgType['type_id'] = 7;
			$this->organization->updateOrgTypeForOrganization($arrOrgType);
		}else{
			$arrOrgType = array();
			$arrOrgType['id'] = $org_id;
			$arrOrgType['type_id'] = $typeId;
			$this->organization->updateOrgTypeForOrganization($arrOrgType);
		}
		$arrKolData['org_id'] = $org_id;
		$arrKolData['title'] = trim($this->input->post('title'));
		$arrKolData['primary_email'] = trim($this->input->post('email'));
		$arrKolData['address1'] = trim($this->input->post('address1'));
		$arrKolData['address2'] = trim($this->input->post('address2'));
		$city_id = trim($this->input->post('city_id'));
		if (!empty($city_id)) {
			if(is_numeric($city_id)){
				$arrKolData['city_id'] = trim($this->input->post('city_id'));
				$city_id=$arrKolData['city_id'];
			}else{
				$city_id = $this->kol->checkCityIfExistElseAdd($city_id,trim($this->input->post('state_id')),trim($this->input->post('country_id')));
				$arrKolData['city_id'] = $city_id;
			}
		}
		$arrKolData['state_id'] = trim($this->input->post('state_id'));
		$arrKolData['country_id'] = trim($this->input->post('country_id'));
		$arrKolData['postal_code'] = trim($this->input->post('postal_code'));
		if(empty($kol_id)){
			$arrKolData['created_by'] = $this->loggedUserId;
			$arrKolData['created_on'] = date('Y-m-d H:i:s');
			$arrKolData['modified_by'] = '';
			$arrKolData['modified_on'] = '';
		}else{
			$arrKolData['modified_by'] = $this->loggedUserId;
			$arrKolData['modified_on'] = date('Y-m-d H:i:s');
		}
		$arrKolData['status'] = 'Completed';
		if($this->input->post('profile_type')==''){
			$arrKolData['profile_type'] = USER_ADDED;
		}else{
			$arrKolData['profile_type'] = trim($this->input->post('profile_type'));
		}		
		$arrKolData['patients_range'] = trim($this->input->post('patients_range'));
		if ($this->input->post('compliance_flag') == "on")
			$arrKolData['compliance_flag'] = 1;
		else
			$arrKolData['compliance_flag'] = 0;
		if ($this->input->post('is_kol') == "on")
			$arrKolData['is_kol'] = 1;
		else
			$arrKolData['is_kol'] = 0;
		if (!empty($kol_id)) {
			$this->kol->deleteKolProducts($kol_id);
			$this->kol->insertKolProducts($kol_id, $arrProducts);
			//deleteKolSubSpecialty
			$this->common_helper->deleteEntityByWhereCondition('kol_sub_specialty',array('kol_id'=>$kol_id));
			$this->kol->insertKolSubSpecialty($kol_id, $arrSubSpecialty);
			$this->kol->deleteKolSpeakerProducts($kol_id);
			$this->kol->insertKolSpakerProducts($kol_id, $arrSpeakerProducts);
			$this->kol->updateKolInfo($arrKolData, $kol_id);
			if($arrKolData['specialty'] > 0){
				$arrData=array();
				$arrData['kol_id']=$kol_id;
				$arrData['kol_sub_specialty_id']=$arrKolData['specialty'];
				$arrData['priority'] = 1;
				$this->kol->saveKolSpecialty($arrData);
			}
			$kolId = $kol_id;
		}else{
			$fname = $arrKolData['first_name'];
			$mname = $arrKolData['middle_name'];
			$lname = $arrKolData['last_name'];
			$specialty = $arrKolData['specialty'];
			$arrKolData['is_duplicate_case'] = $this->input->post('is_duplicate_case');
			
			$kolId = $this->kol->saveKolInfo($arrKolData);
			if($arrKolData['specialty'] > 0){
				$arrData=array();
				$arrData['kol_id']=$kolId;
				$arrData['kol_sub_specialty_id']=$arrKolData['specialty'];
				$arrData['priority'] = 1;
				$this->kol->saveKolSpecialty($arrData);
			}
			//Assign User
			$arrData = array();
			$arrData['created_by'] = $this->loggedUserId;
			$arrData['created_on'] = date('Y-m-d H:i:s');
			$arrData['user_id'] = $this->loggedUserId;
			$arrData['kol_id'] = $kolId;
			$arrData['data_type_indicator'] = $dataType;
			$arrData['type'] = 1;
			$saveAssignId = $this->kol->saveAssignClient($arrData);
			
			$this->kol->insertKolProducts($kolId, $arrProducts);
			$this->kol->insertKolSpakerProducts($kolId, $arrSpeakerProducts);
			$this->kol->insertKolSubSpecialty($kolId, $arrSubSpecialty);
		}
		if($kolId){
			$arrLocationData = array();
			$arrContactData = array();
			if ($this->input->post('visit') == "on")
				$arrContactData['visit'] = 1;
			else
				$arrContactData['visit'] = 0;
			if ($this->input->post('call') == "on")
				$arrContactData['call'] = 1;
			else
				$arrContactData['call'] = 0;
			if ($this->input->post('fax') == "on")
				$arrContactData['fax'] = 1;
			else
				$arrContactData['fax'] = 0;
			if ($this->input->post('mail') == "on")
				$arrContactData['mail'] = 1;
			else
				$arrContactData['mail'] = 0;
			if ($this->input->post('cr_text') == "on")
				$arrContactData['text'] = 1;
			else
				$arrContactData['text'] = 0;
			if ($this->input->post('cr_email') == "on")
				$arrContactData['email'] = 1;
			else
				$arrContactData['email'] = 0;
			if ($this->input->post('cr_video_call') == "on")
				$arrContactData['video_call'] = 1;
			else
				$arrContactData['video_call'] = 0;
			$arrContactData['contact_type'] = 'kol';
			if (!empty($kol_id)) {
				$lastId = $this->kol->updateContactRestrictions($arrContactData, $kol_id);
				$arrContRests = $this->kol->getContactRestrictions($kol_id);
				if(is_array($arrContRests) && count($arrContRests) > 0){
				}else{
					$arrContactData['contact'] = $kolId;
					$lastId = $this->kol->saveContactRestrictions($arrContactData);
				}
			} else {
				$arrContactData['contact'] = $kolId;
				$lastId = $this->kol->saveContactRestrictions($arrContactData);
			}
			$arrLocationData['org_institution_id'] = $arrKolData['org_id'];
			$arrLocationData['address1'] = trim($this->input->post('address1'));
			$arrLocationData['address2'] = trim($this->input->post('address2'));
			$arrLocationData['address3'] = trim($this->input->post('address3'));
			$arrLocationData['validation_status'] = '';
			$arrLocationData['address_type'] = trim($this->input->post('address_type'));
			$arrLocationData['country_id'] = $this->input->post('country_id');
			$arrLocationData['state_id'] = $this->input->post('state_id');
			if ($arrLocationData['state_id'] == '')
				unset($arrLocationData['state_id']);
			$arrLocationData['city_id'] = $city_id;
			if ($arrLocationData['city_id'] == '')
				unset($arrLocationData['city_id']);
			$arrLocationData['postal_code'] = $this->input->post('postal_code');
			$arrLocationData['phone_type'] = $this->input->post('phone_type_loc');
			$arrLocationData['phone_number'] = $this->input->post('phone_number_loc');
			if (empty($is_private_practice)) {
				$arrLocationData['private_practice'] == '';
			} else {
				$arrLocationData['private_practice'] = $this->input->post('organization');
			}
			$arrLocationData['is_primary'] = 1;
			$arrLocationData['division'] = $this->input->post('department_loc');
			$arrLocationData['title'] = $arrKolData['title'];
			$arrLocationData['created_by'] = $this->loggedUserId;
			$arrLocationData['created_on'] = date('Y-m-d H:i:s');
			$arrLocationData['modified_by'] = $this->loggedUserId;
			$arrLocationData['modified_on'] = date('Y-m-d H:i:s');
			$lastInsertedLocationId = '';
			$isPresentOrg = '';
			$updatedId = '';
			if (!empty($kol_id)) {
				$arrLocationDataExist['kol_id'] = $kolId;
				$arrLocationDataExist['org_institution_id'] = $arrKolData['org_id'];
				$isExist = $this->kol->getKolLocationByOrgInstId($arrLocationDataExist);
				if($isExist > 0){
					$updatedId = $isExist;
					$isPresentOrg = true;
					$lastId = $this->kol->updateKolPrimaryLocation($arrLocationData, $kol_id);
					//Add Log activity
					$data['id'] = $kol_id;
				}else{
					$isPresentOrg = false;
					$data['id'] = $kolId;
					$arrLocationData['kol_id'] = $kolId;
					$genericId = $this->common_helper->getGenericId("Location Form");
					$arrLocationData['generic_id'] = $genericId;
					$lastId = $this->kol->saveLocation($arrLocationData);
					$lastInsertedLocationId = $lastId;
					//Add Log activity
				}
			}else{
				$data['id'] = $kolId;
				$arrLocationData['kol_id'] = $kolId;
				$genericId = $this->common_helper->getGenericId("Location Form");
				$arrLocationData['generic_id'] = $genericId;
				$lastId = $this->kol->saveLocation($arrLocationData);
				$lastInsertedLocationId = $lastId;
				//Add Log activity
			}			
			$this->save_kol_client_association($kolId,'fromKol');
			$data['status'] = true;
		}else{
			$data['status'] = false;
		}
		if ($this->input->post("kol_id") == ''){
			$arrEmailData = array();
			/* Save email to emails table */
			if ($this->input->post('email') != '') {
				$arrEmailData = array();
				$arrEmailData['type'] = 'Work';
				$arrEmailData['email'] = trim($this->input->post('email'));
				$arrEmailData['is_primary'] = 1;
				$arrEmailData['contact'] = $kolId;
				$arrEmailData['created_by'] = $this->loggedUserId;
				$arrEmailData['created_on'] = date('Y-m-d H:i:s');
				$arrEmailData['modified_by'] = $this->loggedUserId;
				$arrEmailData['modified_on'] = date('Y-m-d H:i:s');
				$arrEmailData['data_type_indicator'] = $dataType;
				$this->db->insert('emails', $arrEmailData);
				$lastEmailId = $this->db->insert_id();
				//Add Log activity
			}
			/* Save phone to phone_number table */
			if ($this->input->post('phone_number_loc') != '') {
				$arrPhoneData = array();
				$arrPhoneData['type'] = trim($this->input->post('phone_type_loc'));
				$arrPhoneData['number'] = trim($this->input->post('phone_number_loc'));
				$arrPhoneData['is_primary'] = 1;
				$arrPhoneData['contact'] = $kolId;
				$arrPhoneData['location_id'] = $lastId;
				$arrPhoneData['contact_type'] = "location";
				$arrPhoneData['created_by'] = $this->loggedUserId;
				$arrPhoneData['created_on'] = date('Y-m-d H:i:s');
				$arrPhoneData['modified_by'] = $this->loggedUserId;
				$arrPhoneData['modified_on'] = date('Y-m-d H:i:s');
				$arrPhoneData['data_type_indicator'] = $dataType;
				$this->db->insert('phone_numbers', $arrPhoneData);
				$lastPhoneId = $this->db->insert_id();
				//Add Log activity
			}
			$data['status'] = true;
		} else {
			/* Save email to emails table */
			if ($this->input->post('email') != '') {
				$arrEmailData = array();
				$arrEmailData['type'] = 'Work';
				$arrEmailData['email'] = trim($this->input->post('email'));
				$arrEmailData['is_primary'] = 1;
				$arrEmailData['contact'] = $this->input->post("kol_id");
				$arrEmailData['created_by'] = $this->loggedUserId;
				$arrEmailData['created_on'] = date('Y-m-d H:i:s');
				$arrEmailData['modified_by'] = $this->loggedUserId;
				$arrEmailData['modified_on'] = date('Y-m-d H:i:s');
				$this->kol->updateOlEmail($arrEmailData);
			}
			if($isPresentOrg){
				/* Update phone to phone_number table */
				$arrPhoneDataExist['contact'] = $kolId;
				if($updatedId ==''){
					$arrPhoneDataExist['location_id'] = $this->input->post("location_id");
				}else{
					$arrPhoneDataExist['location_id'] = $updatedId;
				}
				$isExist = $this->kol->getKoPhoneByLocationId($arrPhoneDataExist);
				if($isExist > 0){
					if ($this->input->post('phone_number_loc') != '' || $this->input->post('phone_type_loc') != '') {
						$arrPhoneData = array();
						$arrPhoneData['type'] = trim($this->input->post('phone_type_loc'));
						$arrPhoneData['number'] = trim($this->input->post('phone_number_loc'));
						$arrPhoneData['is_primary'] = 1;
						$arrPhoneData['contact'] = $this->input->post("kol_id");
						$arrPhoneData['contact_type'] = "location";
						if($updatedId ==''){
							$arrPhoneData['location_id'] = $this->input->post("location_id");
						}else{
							$arrPhoneData['location_id'] = $updatedId;
						}
						$arrPhoneData['created_by'] = $this->loggedUserId;
						$arrPhoneData['created_on'] = date('Y-m-d H:i:s');
						$arrPhoneData['modified_by'] = $this->loggedUserId;
						$arrPhoneData['modified_on'] = date('Y-m-d H:i:s');
						$this->kol->updateOlPhone($arrPhoneData);
					}
				}else{
					if ($this->input->post('phone_number_loc') != '') {
						$arrPhoneData = array();
						$arrPhoneData['type'] = trim($this->input->post('phone_type_loc'));
						$arrPhoneData['number'] = trim($this->input->post('phone_number_loc'));
						$arrPhoneData['is_primary'] = 1;
						$arrPhoneData['contact'] = $kolId;
						if($updatedId ==''){
							$arrPhoneData['location_id'] = $this->input->post("location_id");
						}else{
							$arrPhoneData['location_id'] = $updatedId;
						}
						$arrPhoneData['contact_type'] = "location";
						$arrPhoneData['created_by'] = $this->loggedUserId;
						$arrPhoneData['created_on'] = date('Y-m-d H:i:s');
						$arrPhoneData['modified_by'] = $this->loggedUserId;
						$arrPhoneData['modified_on'] = date('Y-m-d H:i:s');
						$arrPhoneData['data_type_indicator'] = $dataType;
						$lastPhoneId = $this->kol->savePhone($arrPhoneData);
						//Add Log activity
					}
				}
			}else{
				/* Save phone to phone_number table */
				if ($this->input->post('phone_number_loc') != '') {
					$arrPhoneData = array();
					$arrPhoneData['type'] = trim($this->input->post('phone_type_loc'));
					$arrPhoneData['number'] = trim($this->input->post('phone_number_loc'));
					$arrPhoneData['is_primary'] = 1;
					$arrPhoneData['contact'] = $kolId;
					$arrPhoneData['location_id'] = $lastId;
					$arrPhoneData['contact_type'] = "location";
					$arrPhoneData['created_by'] = $this->loggedUserId;
					$arrPhoneData['created_on'] = date('Y-m-d H:i:s');
					$arrPhoneData['modified_by'] = $this->loggedUserId;
					$arrPhoneData['modified_on'] = date('Y-m-d H:i:s');
					$arrPhoneData['data_type_indicator'] = $dataType;
					$lastPhoneId = $this->kol->savePhone($arrPhoneData);
					//Add Log activity
				}
			}
			$data['status'] = true;
		}
		//Add Log activity
		echo json_encode($data);
	}
	//Function to save association or disassociation KOLs to particular client
	function save_kol_client_association($kolId=null,$fromKol=''){
		if($kolId == null){
			$arrAssociationData['kol_id'] = $this->input->post('kol_id');
			$arrAssociationData['client_id'] = $this->input->post('client');
			$arrAssociationData['associationFlag'] = $this->input->post('associationFlag');
		}else{
			$arrAssociationData['kol_id'] = $kolId;
			$arrAssociationData['client_id'] = $this->session->userdata('client_id');
			$arrAssociationData['associationFlag'] = 'associate';
		}
		$returnData = $this->kol->saveKolClientAssociation($arrAssociationData);
		if($returnData){
			$status = true;
		}else{
			$status = false;
		}
		if($fromKol==''){
			echo json_encode($status);
		}else{
			return $status;
		}
	}
	function add_update_assign_client($kolId = null,$type,$id=null) {
		$client_id = $this->session->userdata('client_id');
		if($type=='edit'){
			$tableName = 'user_kols';
			$getSavedDetails= $this->kol->getAdditionalDetails($tableName,$id);
		}
		$arrResult= $this->kol->listUsers($client_id);
		$arrClientUsers['']='---Select---';
		foreach ($arrResult as $row){
			$arrClientUsers[$row['id']]=$row['first_name'].' '.$row['last_name'];
		}
		$arrResult= $this->kol->getAllClientsType();
		$arrClientsType['']='---Select---';
		foreach ($arrResult as $row){
			$arrClientsType[$row['id']]=$row['name'];
		}

		$hidden_fields=array('kol_id'=>$kolId,'id'=>$id);
		$form_inputs_details[]=array('type'=>'select',   'label'=>array('label_name'=>'User','required'=>1),'name'=>'client','data'=>array('id'=>'client','class'=>'required form-control'),'options'=>$arrClientUsers,'selected'=>$getSavedDetails['user_id']);
		$form_inputs_details[]=array('type'=>'select',   'label'=>array('label_name'=>'Type','required'=>1),'name'=>'client_type','data'=>array('id'=>'client_type','class'=>'required form-control'),'options'=>$arrClientsType,'selected'=>$getSavedDetails['type']);
		$form_details=array('form_inputs_details'=>$form_inputs_details,
				'hidden_ids'=>$hidden_fields,
				'form_id'=>'saveKolAssignClientForm',
				'submit_function'=>'save_assigned_client_form();',
				'cancel_function'=>'close_dialog();'
		);
		$data['html_form']=get_html_form($form_details);///cals helper function to get html content
		$this->load->view('kols/add_update_assign_client',$data);
	}
	function save_client_assign(){
		$data= $this->kol->insertOrUpdateAssignClient($_POST);
		echo json_encode($data);
	}
	function delete_email($id = null,$kolId) {
		$data = $this->common_helper->deleteEntityByWhereCondition('emails',array('id'=>$id));
		echo json_encode($data);
	}
	function add_update_licenses($kolId = null,$type,$id) {
		$arrStates		= array();
		$arrResult			= $this->country_helper->listCountries();
		$arrCountries['']	='---Select---';
		foreach ($arrResult as $row){
			$arrCountries[$row['country_id']]=$row['country_name'];
		}
		if($type=='edit'){
			$tableName = 'state_licenses';
			$getSavedDetails= $this->kol->getAdditionalDetails($tableName,$id);
			$is_primary=($getSavedDetails['is_primary']==1)?'on':'';
			if ($getSavedDetails['country_id'] != '' && $getSavedDetails['country_id'] != 0)
				$arrResult= $this->country_helper->getStatesByCountryId($getSavedDetails['country_id']);
				$arrStates['']	='---Select---';
				foreach ($arrResult as $row){
					$arrStates[$row['state_id']]=$row['state_name'];
				}
		}
		$hidden_fields=array('id'=>$getSavedDetails['id'],'contact'=>$kolId);
		$form_inputs_details[]=array('type'=>'text',     'label'=>array('label_name'=>'Number','required'=>1),'data'=>array('name'=>'state_license_number','id'=>'state_license_number','class'=>'required form-control','value'=>$getSavedDetails['state_license']));
		$form_inputs_details[]=array('type'=>'select','label'=>array('label_name'=>'Country','required'=>1),'name'=>'country_id','data'=>array('id'=>'country_id','class'=>'required form-control','onchange'=>'getStatesByCountryIdLicense();'),'options'=>$arrCountries,'selected'=>$getSavedDetails['country_id']);
		$form_inputs_details[]=array('type'=>'select','label'=>array('label_name'=>'State','required'=>1),'name'=>'state_id','data'=>array('id'=>'state_id','class'=>'required form-control'),'options'=>$arrStates,'selected'=>$getSavedDetails['region']);
		$form_inputs_details[]=array('type'=>'checkbox','label'=>array('label_name'=>'Is Primary','required'=>0),'name'=>'license_is_primary','values'=>array($is_primary),'options'=>array(''=>'on'),'data'=>array('id'=>'license_is_primary','class'=>''));
		$form_details=array('form_inputs_details'=>$form_inputs_details,
				'hidden_ids'=>$hidden_fields,
				'form_id'=>'saveKolStateLicenseForm',
				'submit_function'=>'save_state_license_form();',
				'cancel_function'=>'close_dialog();'
		);
		$data['html_form']=get_html_form($form_details);///cals helper function to get html content
		$this->load->view('kols/add_update_licenses',$data);
	}
	function save_state_license() {
		$data= $this->kol->insertOrUpdateStateLicense($_POST);
		echo json_encode($data);
	}
	function add_update_emails($kolId = null,$type,$id) {
		$arrType = array('Work'=>'Work','Other'=>'Other');
		if($type=='edit'){
			$tableName = 'emails';
			$getSavedDetails= $this->kol->getAdditionalDetails($tableName,$id);
			$getSavedDetails['is_primary']=($getSavedDetails['is_primary']=='1')?'on':'';
		}
		$hidden_fields=array('id'=>$getSavedDetails['id'],'contact'=>$kolId,'contact_type'=>'kol');
		$form_inputs_details[]=array('type'=>'select','label'=>array('label_name'=>'Type','required'=>1),'name'=>'email_type','data'=>array('id'=>'email_type','class'=>'required form-control'),'options'=>$arrType,'selected'=>$getSavedDetails['type']);
		$form_inputs_details[]=array('type'=>'text',   'label'=>array('label_name'=>'Email','required'=>1),'data'=>array('name'=>'email','id'=>'email','class'=>'required form-control','value'=>$getSavedDetails['email']));
		$form_inputs_details[]=array('type'=>'checkbox','label'=>array('label_name'=>'Is Primary','required'=>0),'name'=>'email_is_primary','values'=>array($getSavedDetails['is_primary']),'options'=>array(''=>'on'),'data'=>array('id'=>'email_is_primary','class'=>''));
		$form_details=array('form_inputs_details'=>$form_inputs_details,
				'hidden_ids'=>$hidden_fields,
				'form_id'=>'saveKolEmailForm',
				'submit_function'=>'save_email_form();',
				'cancel_function'=>'close_dialog();'
		);
		$data['html_form']=get_html_form($form_details);
		$this->load->view('kols/add_update_emails',$data);
	}
	function add_update_phone($kolId = null,$type,$id) {
		if($type=='edit'){
			$tableName = 'phone_numbers';
			$getSavedDetails= $this->kol->getAdditionalDetails($tableName,$id);
			$is_primary=($getSavedDetails['is_primary']==1)?'on':'';
		}
		$arrPhoneType= $this->kol->getPhoneType();
		$arrLocations= $this->kol->getAllLocationsByKolId($kolId);
		
		$hidden_fields=array('id'=>$getSavedDetails['id'],'contact'=>$kolId,'contact_type'=>'kol');
		$form_inputs_details[]=array('type'=>'select','label'=>array('label_name'=>'Type','required'=>1),'name'=>'phone_type','data'=>array('id'=>'phone_type','class'=>'required form-control'),'options'=>$arrPhoneType,'selected'=>$getSavedDetails['type']);
		$form_inputs_details[]=array('type'=>'select','label'=>array('label_name'=>'Location','required'=>1),'name'=>'phone_location','data'=>array('id'=>'phone_location','class'=>'required form-control'),'options'=>$arrLocations,'selected'=>$getSavedDetails['location_id']);
		$form_inputs_details[]=array('type'=>'text',   'label'=>array('label_name'=>'Phone Number','required'=>1),'data'=>array('name'=>'phone_number','id'=>'phone_number','class'=>'required form-control','value'=>$getSavedDetails['number']));
		$form_inputs_details[]=array('type'=>'checkbox','label'=>array('label_name'=>'Is Primary','required'=>0),'name'=>'phone_is_primary','values'=>array($is_primary),'options'=>array(''=>'on'),'data'=>array('id'=>'phone_is_primary','class'=>''));
		$form_details=array('form_inputs_details'=>$form_inputs_details,
				'hidden_ids'=>$hidden_fields,
				'form_id'=>'saveKolPhoneNumberForm',
				'submit_function'=>'save_phone_number_form();',
				'cancel_function'=>'close_dialog();'
		);
		$data['html_form']=get_html_form($form_details);///cals helper function to get html content
		$this->load->view('kols/add_update_phone',$data);
	}
	function save_phone() {
		$data= $this->kol->insertOrUpdatePhoneNumber($_POST);
		echo json_encode($data);
	}	
	function save_email() {
		$data= $this->kol->insertOrUpdateEmail($_POST);
		echo json_encode($data);
	}
	function delete_phone($id = null,$kolId) {
		$data = $this->kol->deletePhone($id,"");
		echo json_encode($data);
	}
	function add_location($kolId = null,$location_id=null) {
		$arrStates		= array();
		$arrCities= array();
		//get titles list
		$arrResult=  $this->kol->getAllActiveTitles('all');
		$arrTitles['']='---Select---';
		foreach ($arrResult as $row){
			$arrTitles[$row['id']]=$row['title'];
		}
		//get countries list
		$arrResult			= $this->country_helper->listCountries();
		$arrCountries['']	='---Select---';
		foreach ($arrResult as $row){
			$arrCountries[$row['country_id']]=$row['country_name'];
		}
		//get org types list
		$arrOrganizationTypes= $this->organization->getAllOrganizationTypes();
		//if location_id is not null.
		if($location_id!=null){
			//,get the details of location by location_id
			$locationData= $this->kol->getLocationById($location_id);
			$locationData= $locationData[0];
			//get org_type by org_id
			$arrOrgDetails= $this->organization->editOrganization($locationData['org_institution_id']);
			$locationData['orgTypeId'] = $arrOrgDetails['type_id'];
			//if country_id is not null, get states list
			if ($locationData['country_id'] != '' && $locationData['country_id'] != 0)
				$arrResult= $this->country_helper->getStatesByCountryId($locationData['country_id']);
			$arrStates['']	='---Select---';
			foreach ($arrResult as $row){
				$arrStates[$row['state_id']]=$row['state_name'];
			}
			//if state_id is not null, get cities list
			if ($locationData['state_id'] != '' && $locationData['state_id'] != 0)
				$arrResult= $this->country_helper->getCitiesByStateId($locationData['state_id']);
			$arrStates['']	='---Select---';
			foreach ($arrResult as $row){
				$arrCities[$row['city_id']]=$row['city_name'];
			}
		}
		$hidden_fields=array('id'=>$locationData['id'],'kol_id'=>$kolId,'org_inst_selected'=>$locationData['org_institution_id'],'org_institution_id'=>$locationData['org_institution_id'],'private_practice'=>'1');
		$form_inputs_details[]=array('type'=>'checkbox','label'=>array('label_name'=>'Primary Address'),'name'=>'is_primary','values'=>array($locationData['is_primary']),'options'=>array(''=>'1'),'data'=>array('id'=>'is_primary','class'=>''));
		$form_inputs_details[]=array('type'=>'text','label'=>array('label_name'=>'Institution','required'=>1),'data'=>array('name'=>'organization','id'=>'organizationLocation','class'=>'required form-control autocompleteInputBox','placeholder'=>'Enter Organization','value'=>$locationData['org_name']));
		$form_inputs_details[]=array('type'=>'select','label'=>array('label_name'=>'Institution Type'),'name'=>'org_type','data'=>array('id'=>'org_typeLocation','class'=>'required form-control','onchange'=>'changeAutoComplete();'),'options'=>$arrOrganizationTypes,'selected'=>$locationData['orgTypeId']);
		$form_inputs_details[]=array('type'=>'text',   'label'=>array('label_name'=>'Address Line 1','required'=>1),'data'=>array('name'=>'address1','id'=>'address1','class'=>'required form-control','value'=>$locationData['address1']));
		$form_inputs_details[]=array('type'=>'text',   'label'=>array('label_name'=>'Address Line 2'),'data'=>array('name'=>'address2','id'=>'address2','class'=>'form-control','value'=>$locationData['address2']));
		$form_inputs_details[]=array('type'=>'text',   'label'=>array('label_name'=>'Department'),'data'=>array('name'=>'department_loc','id'=>'department_loc','class'=>'form-control','value'=>$locationData['division']));
		$form_inputs_details[]=array('type'=>'select','label'=>array('label_name'=>'Position'),'name'=>'title_loc','data'=>array('id'=>'title_loc','class'=>'chosenMultipleSelect form-control'),'options'=>$arrTitles,'selected'=>$locationData['title']);
		$form_inputs_details[]=array('type'=>'text',   'label'=>array('label_name'=>'Postal Code'),'data'=>array('name'=>'postal_code','id'=>'postal_code','class'=>'form-control','value'=>$locationData['postal_code']));
		$form_inputs_details[]=array('type'=>'select','label'=>array('label_name'=>'Country','required'=>1),'name'=>'country_id','data'=>array('id'=>'country_id','class'=>'required form-control','onchange'=>'getStatesByCountryIdLocation();'),'options'=>$arrCountries,'selected'=>$locationData['country_id']);
		$form_inputs_details[]=array('type'=>'select','label'=>array('label_name'=>'State/Province'),'name'=>'state_id','data'=>array('id'=>'state_id','class'=>'form-control','onchange'=>'getCitiesByStateIdLocation();'),'options'=>$arrStates,'selected'=>$locationData['state_id']);
		$form_inputs_details[]=array('type'=>'select','label'=>array('label_name'=>'City'),'name'=>'city_id','div_id'=>'cityDiv','data'=>array('id'=>'city_id','class'=>'form-control'),'options'=>$arrCities,'selected'=>$locationData['city_id']);
		
		$form_details=array('form_inputs_details'=>$form_inputs_details,
				'hidden_ids'=>$hidden_fields,
				'form_id'=>'saveKolLocationForm',
				'submit_function'=>'save_location_form();',
				'cancel_function'=>'close_dialog();'
		);
		$data['html_form']=get_html_form2($form_details);
		$this->load->view('add_location',$data);
	}
	function add_update_staffs($kolId = null,$type,$id=null) {
		if($type=='edit'){
			$tableName = 'staffs';
			$getSavedDetails= $this->kol->getAdditionalDetails($tableName,$id);
		}
		$arrPhoneType= $this->kol->getPhoneType();
		$arrStaffTitle= $this->kol->getStaffTitle();
		$arrLocations= $this->kol->getAllLocationsByKolId($kolId);
		
		$hidden_fields=array('id'=>$id,'contact'=>$kolId,'contact_type'=>'kol');
		$form_inputs_details[]=array('type'=>'select','label'=>array('label_name'=>'Title','required'=>1),'name'=>'staff_title','data'=>array('id'=>'staff_title','class'=>'required form-control'),'options'=>$arrStaffTitle,'selected'=>$getSavedDetails['title']);
		$form_inputs_details[]=array('type'=>'select','label'=>array('label_name'=>'Location'),'name'=>'staff_location','data'=>array('id'=>'staff_location','class'=>'form-control'),'options'=>$arrLocations,'selected'=>$getSavedDetails['location_id']);
		$form_inputs_details[]=array('type'=>'text',   'label'=>array('label_name'=>'Name','required'=>1),'data'=>array('name'=>'staff_name','id'=>'staff_name','class'=>'required form-control','value'=>$getSavedDetails['name']));
		$form_inputs_details[]=array('type'=>'text',   'label'=>array('label_name'=>'Email'),'data'=>array('name'=>'email','id'=>'email','class'=>'form-control','value'=>$getSavedDetails['email']));
		$form_inputs_details[]=array('type'=>'select','label'=>array('label_name'=>'Phone Type'),'name'=>'phone_type','data'=>array('id'=>'phone_type','class'=>'form-control'),'options'=>$arrPhoneType,'selected'=>$getSavedDetails['type']);
		$form_inputs_details[]=array('type'=>'text',   'label'=>array('label_name'=>'Phone Number'),'data'=>array('name'=>'staff_phone','id'=>'staff_phone','class'=>'form-control','value'=>$getSavedDetails['number']));
		$form_details=array('form_inputs_details'=>$form_inputs_details,
				'hidden_ids'=>$hidden_fields,
				'form_id'=>'saveKolStaffForm',
				'submit_function'=>'save_staff_form();',
				'cancel_function'=>'close_dialog();'
		);
		$data['html_form']=get_html_form2($form_details);
		$this->load->view('add_update_staff',$data);
	}
	function save_location() {
		$arrLocationDataExist['kol_id'] 			= $this->input->post('kol_id');
		$arrLocationDataExist['org_institution_id'] = $this->input->post('org_institution_id');
		$isExist 									= $this->kol->getKolLocationByOrgInstId($arrLocationDataExist);
		$dataType 									= 'User Added';
		$client_id									=$this->session->userdata('client_id');
		if($client_id == INTERNAL_CLIENT_ID){
			$dataType = 'Aissel Analyst';
		}
		if(((!empty($this->input->post('id'))) && ($this->input->post('org_inst_selected') != $this->input->post('org_institution_id')) && ($isExist >0)) || (empty($this->input->post('id')) && ($isExist >0))) {
			$data['status'] = true;
			$data['duplicate_location'] = true;
		}else{
			$arrData['kol_id'] = $this->input->post('kol_id');
			$org_id = $this->input->post('org_institution_id');
			$private_practice = $this->input->post('organization');
			$arrData['division'] = $this->input->post('department_loc');
			$arrData['title'] = $this->input->post('title_loc');
			if (empty($org_id)) {
				$arrOrgData['address'] = trim($this->input->post('address1')) . " " . trim($this->input->post('address2'));
				$arrOrgData['status'] = 'Completed';
				
				$city_id = trim($this->input->post('city_id'));
				if (!empty($city_id)) {
					$arrOrgData['city_id'] = trim($this->input->post('city_id'));
				} else {
					$arrOrgData['city_id'] = "";
				}
				$arrOrgData['name'] = trim($this->input->post('organization'));
				$arrOrgData['state_id'] = trim($this->input->post('state_id'));
				$arrOrgData['country_id'] = trim($this->input->post('country_id'));
				$arrOrgData['postal_code'] = trim($this->input->post('postal_code'));
				$arrOrgData['created_by'] = $this->loggedUserId;
				$arrOrgData['created_on'] = date('Y-m-d H:i:s');
				$arrOrgData['modified_by'] = $this->loggedUserId;
				$arrOrgData['modified_on'] = date('Y-m-d H:i:s');
				$arrOrgData['type_id'] = trim($this->input->post('org_type'));
				$arrOrgData['profile_type'] = 1;
				$arrOrgData['status_otsuka'] = "ACTV";
				$arrOrgData['status'] = "";
				$org_id = $this->organization->saveOrganization($arrOrgData);
				//Save org visibility
				if(ORGS_VISIBILITY){
					$orgVisibility = array();
					$orgVisibility['org_id'] = $org_id;
					$orgVisibility['client_id'] = $this->session->userdata('client_id');
					$orgVisibility['associationFlag'] = 'associate';
					$this->organization->saveOrgClientAssociation($orgVisibility);
				}
				$orgData['org_id'] 			= $org_id;
				$orgData['address1'] 			= trim($this->input->post('address1'));
				$orgData['address2'] 			= trim($this->input->post('address2'));
				$orgData['address_type'] 		= trim($this->input->post('address_type'));
				$orgData['country_id'] 	= $this->input->post('country_id');
				$orgData['state_id'] 	= $this->input->post('state_id');
				$orgData['city_id'] 		= $this->input->post('city_id');
				$orgData['postal_code'] 	= $this->input->post('postal_code');
				$orgData['phone_number_primary'] 	= $this->input->post('phone_number_loc');
				$orgData['phone_type_primary'] 	= $this->input->post('phone_type_loc');
				$orgData['is_primary'] 			= 1;
				$orgData['created_by'] 		= $this->loggedUserId;
				$orgData['created_on'] 		= date('Y-m-d H:i:s');
				$orgData['modified_by'] 		= $this->loggedUserId;
				$orgData['modified_on'] 		= date('Y-m-d H:i:s');
				$orgLocLasId = $this->organization->saveLocation($orgData);
				if(isset($orgData['phone_type_primary']) && $orgData['phone_number_primary'] > 0){
					$orgPhone = array();
					$orgPhone['type'] = $this->input->post('phone_type_loc');
					$orgPhone['number'] = $this->input->post('phone_number_loc');
					$orgPhone['contact_type'] = 'organization';
					$orgPhone['contact'] = $org_id;
					$orgPhone['is_primary'] = 1;
					$orgPhone['location_id'] = $orgLocLasId;
					$orgPhone['created_by'] = $this->loggedUserId;
					$orgPhone['created_on'] = date('Y-m-d H:i:s');
					$lastPhoneId = $this->kol->savePhone($orgPhone);
				}
			}
			$typeId = trim($this->input->post('org_type'));
			if (empty($typeId)) {
				$arrOrgType = array();
				$arrOrgType['id'] = $org_id;
				$arrOrgType['type_id'] = 7;
				$this->organization->updateOrgTypeForOrganization($arrOrgType);
			}else{
				$arrOrgType = array();
				$arrOrgType['id'] = $org_id;
				$arrOrgType['type_id'] = $typeId;
				$this->organization->updateOrgTypeForOrganization($arrOrgType);
			}
			$arrData['org_institution_id'] = $org_id;
			$arrData['address1'] = trim($this->input->post('address1'));
			$arrData['address2'] = trim($this->input->post('address2'));
			$arrData['address3'] = trim($this->input->post('address3'));
			if (!empty($private_practice))
				$arrData['private_practice'] = $this->input->post('organization');
			else
				$arrData['private_practice'] == '';
			$arrData['validation_status'] = trim($this->input->post('validation_status'));
			$arrData['address_type'] = trim($this->input->post('address_type'));
			$arrData['country_id'] = $this->input->post('country_id');
			$arrData['state_id'] = $this->input->post('state_id');
			$genericId = $this->common_helper->getGenericId("Location Form");
			$arrData['generic_id'] = $genericId;
			
			if ($arrData['state_id'] == '')
				unset($arrData['state_id']);
			$city_id = $this->input->post('city_id');
			if (!empty($city_id)) {
				if(is_numeric($city_id)){
					$arrOrgData['city_id'] = trim($this->input->post('city_id'));
					$arrData['city_id'] = $arrOrgData['city_id'];
				}else{
					$cityId = $this->kol->checkCityIfExistElseAdd($city_id,trim($this->input->post('state_id')),trim($this->input->post('country_id')));
					$arrData['city_id'] = $cityId;
				}
			} else {
				$arrData['city_id'] = "";
			}
			$arrData['postal_code'] = $this->input->post('postal_code');
			$arrData['phone_type'] = $this->input->post('phone_type_loc');
			$arrData['phone_number'] = $this->input->post('phone_number_loc');
			if ($this->input->post('is_primary') == "1")
				$arrData['is_primary'] = $this->input->post('is_primary');
			$arrData['modified_by'] = $this->loggedUserId;
			$arrData['modified_on'] = date('Y-m-d H:i:s');
			$arrData['data_type_indicator'] = $dataType;
			$id = $this->input->post('id');
			if (!empty($id)) {
				$arrData['id'] = $this->input->post('id');
				$lastId = $this->kol->saveLocation($arrData);
				if ($arrData['is_primary'] == '1') {
// 					log_user_activity(null,true);
				}
				if ($lastId) {
					$data['status'] = true;
				} else {
					$data['status'] = false;
				}
				$lastId = $arrData['id'];
			} else {
				$arrData['created_by'] = $this->loggedUserId;
				$arrData['created_on'] = date('Y-m-d H:i:s');
				$lastId = $this->kol->saveLocation($arrData);
				if ($lastId) {
					$data['status'] = true;
					$data['id'] = $lastId;
				} else {
					$data['status'] = false;
				}
			}
			//if is primary then update the kols table address information
			if ($arrData['is_primary'] == '1') {
				$arrKolDetails = array();
				$arrKolDetails['id'] = $arrData['kol_id'];
				$arrKolDetails['org_id'] = $arrData['org_institution_id'];
				$arrKolDetails['address1'] = $arrData['address1'];
				$arrKolDetails['address2'] = $arrData['address2'];
				$arrKolDetails['country_id'] = $arrData['country_id'];
				$arrKolDetails['state_id'] = $arrData['state_id'];
				$arrKolDetails['city_id'] = $arrData['city_id'];
				$arrKolDetails['postal_code'] = $arrData['postal_code'];
				$arrKolDetails['modified_by'] = $this->loggedUserId;
				$arrKolDetails['modified_on'] = date('Y-m-d H:i:s');
				$arrKolDetails['division'] = $arrData['division'];
				$arrKolDetails['title'] =$arrData['title'];
				$this->kol->updateKol($arrKolDetails);
				$arrKolDetails['org_type']  =  trim($this->input->post('org_type'));
				$arrKolDetails['org_name']  =  $this->input->post('organization');
				$arrKolDetails['address_type']  =  $arrData['address_type'];
				$data['details'] = $arrKolDetails;
			}else{
				$data['details'] = '';
			}
		}
		echo json_encode($data);
	}
	function save_staff() {
		$data= $this->kol->insertOrUpdateStaff($_POST);
		echo json_encode($data);
	}
	function delete_state_license($id = null,$kolId) {
		$data = $this->kol->deleteStateLicense($id);
		echo json_encode($data);
	}
	function delete_location($id = null,$kolId) {
		$msg = '';
		$rowPhoneData = $this->db->get_where('phone_numbers', array('contact' => $kolId,'location_id' => $id))->result();
		$rowStaffData = $this->db->get_where('staffs', array('contact' => $kolId,'location_id' => $id))->result();
		if(sizeof($rowPhoneData) > 0 && sizeof($rowStaffData) > 0){
			$msg = 'Kindly delete associated records from Phone/Staff Section';
		}else if(sizeof($rowPhoneData) > 0){
			$msg = 'Kindly delete associated record from Phone Section';
		}else if(sizeof($rowStaffData) > 0){
			$msg = 'Kindly delete associated record from Staff Section';
		}else{
			$this->kol->deleteStaff("", $kolId, $id);
			$this->kol->deletePhone("", $kolId, $id);
			//delete location by id
			$this->common_helper->deleteEntityByWhereCondition('kol_locations',array('id'=>$id));
		}
		echo json_encode($msg);
	}
	function delete_assign($id = null,$kolId) {
		//delete Assigned User id
		$data = $this->common_helper->deleteEntityByWhereCondition('user_kols',array('id'=>$id));
		echo json_encode($data);
	}
	function getKolProfileScore($kolId) {
		$kolTotalActivitiesCount = $this->kol_rating->getKolTotalActivityCount1($kolId);
		$spcilatyId = $this->kol_rating->gerSpecilatyIdByKol($kolId);
		$maxCount = $this->kol_rating->getMaxTotalCountOfKolBySpecialty($spcilatyId);
		if ($maxCount > 0) {
			$kolsPforfileScore = ($kolTotalActivitiesCount / $maxCount) * 100;
		} else {
			$kolsPforfileScore = 0;
		}
		$kolsPforfileScore = $this->kol_rating->getProfileScore($kolId);
		return $kolsPforfileScore;
	}
	function delete_event($id) {
		if ($this->kol->deleteEventById($id)) {
			$arrResult['status'] = 'success';
		} else {
			$arrResult['status'] = 'fail';
		}
		echo json_encode($arrResult);
	}
	function get_kol_other_details($kolId = null) {
		if ($kolId == null || $kolId == '') {
			$kolName = $this->input->post('kol_name');
			$kolId = $this->kol->getKolId($kolName);
		}
		if(!(is_numeric($kolId))){
			$kolId= $this->common_helper->getFieldValueByEntityDetails('kols','unique_id',$kolId,'id');
		}
		$arrKolDetails = $this->kol->getKolDetailsById($kolId);
		$this->load->model('specialities/speciality');
		$data['specialtyId'] = $arrKolDetails[0]['specialty'];
		$data['title'] = $arrKolDetails[0]['title'];
		$data['specialtyName'] = $this->speciality->getSpecialtyById($data['specialtyId']);
		echo json_encode($data);
	}
	function view_affiliations($kolId, $subContentPage = '') {
		// Getting the KOL details
		$kolId 									= $this->common_helper->getFieldValueByEntityDetails('kols','unique_id',$kolId,'id');
		$arrKolDetail = $this->kol->editKol($kolId);
		// If there is no record in the database
		if (!$arrKolDetail) {
			return false;
		}
		$module_name				='kols';
		$data['module_id']=$this->common_helper->getModuleIdByModuleName($module_name);
		
		$data['arrKol'] = $arrKolDetail;
		$data['subContentPage'] = $subContentPage;
		$data['contentPage'] = 'view_affiliations';
		$data['contentData']			=$data;
		$this->load->view(CLIENT_LAYOUT,$data);
	}
	function delete_membership($id) {
		if ($this->kol->deleteMembership($id)) {
// 			$this->update->deleteUpdateEntry(KOL_PROFILE_AFFILITION_ADD, $id, MODULE_KOL_AFFILIATION);
// 			$this->update->deleteUpdateEntry(KOL_PROFILE_AFFILITION_UPDATE, $id, MODULE_KOL_AFFILIATION);
			$arrReturnData['status'] = 'success';
		} else{
			$arrReturnData['status'] = 'fail';
		}
		echo json_encode($arrReturnData);
	}
	function list_memberships_grid($type, $kolId = null) {
		$page = (int) $this->input->post('page'); // get the requested page
		$limit = (int) $this->input->post('rows'); // get how many rows we want to have into the grid
		$arrMembershipResult = array();
		$data = array();
		$arrMembership = array();
// 		$kolId 									= $this->common_helper->getFieldValueByEntityDetails('kols','unique_id',$kolId,'id');
// 		pr($kolId);exit;
		if ($arrMembershipResult = $this->kol->listMemberships($type, $kolId)) {
			foreach ($arrMembershipResult as $row) {
				$row['date'] = '';
				if ($row['start_date'] != '')
					$row['date'] .= $row['start_date'];
				else
					$row['date'] .= 'NA';
					
				$row['date'] .= " - ";
				
				if ($row['end_date'] != '')
					$row['date'] .= $row['end_date'];
				else
					$row['date'] .= 'NA';
				
				if ($row['date'] == 'NA - NA') {
					$row['date'] = '';
				}
				$arrMembership[] = $row;
			}
			$count = sizeof($arrMembership);
			if ($count > 0) {
				$total_pages = ceil($count / $limit);
			} else {
				$total_pages = 0;
			}
			$data['records'] = $count;
			$data['total'] = $total_pages;
			$data['page'] = $page;
			$data['rows'] = $arrMembership;
		}
		echo json_encode($data);
	}
	function view_charts(){
		$data['contentPage'] = 'charts';
		$data['contentData']			=$data;
		$this->load->view(CLIENT_LAYOUT,$data);
	}
	function chart_for_engagement_post($fromType) {
		$currController = $this->uri->segment(1);
		$arrFilterById = array();
		if ($currController == "reports") {
			$arrFilterById = false;
			$arrFilterFields = array();
			$arrFilterFields = $arrFilterById['arrFilterFields'];
		}
		$arrKolIds = array();
		$fromYear = ($this->input->post('from_year') == null) ? 0 : $this->input->post('from_year');
		$toYear = ($this->input->post('to_year') == null) ? 0 : $this->input->post('to_year');
		$arrKolNames = ($arrFilterFields['kol_id']) ? $arrFilterFields['kol_id'] : (($this->input->post('kol_id') == null) ? 0 : $this->input->post('kol_id'));
		$arrSpecialities = ($arrFilterFields['specialty']) ? $arrFilterFields['specialty'] : (($this->input->post('specialty') == null) ? 0 : $this->input->post('specialty'));
		$arrCountries = ($arrFilterFields['country']) ? $arrFilterFields['country'] : (($this->input->post('country') == null) ? 0 : $this->input->post('country'));
		$arrStates = ($arrFilterFields['state']) ? $arrFilterFields['state'] : (($this->input->post('state') == (null || '')) ? 0 : $this->input->post('state'));
		$arrEngTypes = ($this->input->post('engType') == '') ? '' : $this->input->post('engType');
		$arrOrgType = ($this->input->post('orgType') == '') ? '' : $this->input->post('orgType');
		$arrListNames = ($arrFilterFields['list_id']) ? $arrFilterFields['list_id'] : (($this->input->post('listName') == (null || '')) ? 0 : $this->input->post('listName'));
		$arrProfileType = ($arrFilterFields['profile_type']) ? $arrFilterFields['profile_type'] : (($this->input->post('profile_type')) ? $this->input->post('profile_type') : '');
		
		if ($arrOrgType != '' && $arrOrgType == "University/Hospital") {
			$arrOrgType = "university";
		}
		if ($arrSpecialities != '0' && $arrSpecialities != '') {
			foreach ($arrSpecialities as $key => $value)
				$arrSpecialityIds[] = $this->kol->getSpecialtyId($value);
		} else {
			$arrSpecialityIds = $arrSpecialities;
		}
		if ($arrCountries != '0' && $arrCountries != '') {
			foreach ($arrCountries as $key => $value)
				$arrCountriesIds[] = $this->country_helper->getConcountryId($value);
		} else {
			$arrCountriesIds = $arrCountries;
		}
		if ($arrStates != '0' && $arrStates != '') {
			foreach ($arrStates as $key => $value)
				$arrStatesIds[] = $this->country_helper->getStateId($value);
		} else {
			$arrStatesIds = $arrStates;
		}
		if ($arrListNames != '0' && $arrListNames != '') {
			foreach ($arrListNames as $key => $value)
				$arrListNamesIds[] = $this->My_list_kol->getListNameId($value);
		} else {
			$arrListNamesIds = $arrListNames;
		}
		if ($arrKolNames != '0' && $arrKolNames != '') {
			foreach ($arrKolNames as $key => $kolName) {
				if (!is_numeric($kolName)) {
					$arrKolIds[] = $this->kol->getKolId($kolName);
				} else {
					$arrKolIds[] = $kolName;
				}
			}
		} else {
			$arrKolIds = $arrKolNames;
		}
		$arrAffiliations = $this->kol->getAffiliationsByParam($fromYear, $toYear, $arrKolIds, $arrEngTypes = '', $arrOrgType, $arrCountriesIds, $arrSpecialityIds, $selectType = 'engagement_types.engagement_type', $arrListNamesIds, $arrStatesIds, $arrProfileType,array(),'',null,$fromType);
		$arrEngagementCounts = array();
		foreach ($arrAffiliations as $row) {
			$arr = array();
			if ($row['engagement_type'] != '') {
				$arr[] = $row['engagement_type'];
				$arr[] = (int) $row['count'];
				$arrEngagementCounts[] = $arr;
			}
		}
		$logKolIds = '';
		$i=1;
		foreach($arrKolNames as $rowId){
			if(sizeof($arrKolNames) !=$i){
				$logKolIds .= $rowId.',';
			}else{
				$logKolIds .= $rowId;
			}
			$i++;
		}
		echo json_encode($arrEngagementCounts);
	}
	function chart_for_organization_post($fromType) {
		$viewTypeMyKols = $this->input->post("viewTypeMyKols");
		$fromYear = ($this->input->post('from_year') == null) ? 0 : $this->input->post('from_year');
		$toYear = ($this->input->post('to_year') == null) ? 0 : $this->input->post('to_year');
		$arrKolNames = ($this->input->post('kol_id') == null) ? 0 : $this->input->post('kol_id');
		$arrSelectedKol = ($this->input->post('selected_kol_id') == null) ? 0 : $this->input->post('selected_kol_id');
		$arrGlobalRegions = ($this->input->post('global_region') == (null || '')) ? 0 : $this->input->post('global_region');
		$arrSpecialities = ($this->input->post('specialty') == null) ? 0 : $this->input->post('specialty');
		$arrCountries = ($this->input->post('country') == null) ? 0 : $this->input->post('country');
		//	$arrStates								= ($this->input->post('state')==(null || '')) ? 0:$this->input->post('state');
		$arrStatesIds = ($this->input->post('state') == (null || '')) ? 0 : $this->input->post('state');
		$arrEngTypes = ($this->input->post('engType') == '') ? '' : $this->input->post('engType');
		$arrListNames = ($this->input->post('listName') == (null || '')) ? 0 : $this->input->post('listName');
		$profileType = ($this->input->post('profile_type')) ? $this->input->post('profile_type') : '';
		$arrGlobalRegions = urldecode($arrGlobalRegions);
		if ($arrGlobalRegions != '0' && $arrGlobalRegions != '') {
			if (!is_array($arrGlobalRegions))
				$arrGlobalRegionIds = explode(",", $arrGlobalRegions);
				else
					$arrGlobalRegionIds = $arrGlobalRegions;
		}
		
		if ($arrSpecialities != 0 && $arrSpecialities != '') {
			if (!is_array($arrSpecialities))
				$arrSpecialityIds = explode(",", $arrSpecialities);
				else
					$arrSpecialityIds = $arrSpecialities;
		}
		if ($arrCountries != 0 && $arrCountries != '') {
			if (!is_array($arrCountries))
				$arrCountriesIds = explode(",", $arrCountries);
				else
					$arrCountriesIds = $arrCountries;
		}
		if ($arrStatesIds != 0 && $arrStatesIds != '') {
			if (!is_array($arrStatesIds))
				$arrStatesIds = explode(",", $arrStatesIds);
				else
					$arrStatesIds = $arrStatesIds;
		}
		if ($arrKolNames != '0' && $arrKolNames != '') {
			if (!is_array($arrKolNames))
				$kolIds = $arrSelectedKol;
				else
					$kolIds = $arrKolNames;
		}else {
			$kolIds = $arrSelectedKol;
		}
		if ($arrListNames != '0' && $arrListNames != '') {
			if (!is_array($arrListNames))
				$arrListNamesIds = explode(",", $arrListNames);
				else
					$arrListNamesIds = $arrListNames;
		}
		if ($viewTypeMyKols == MY_RECORDS) {
			$viewMyKols = $this->kol->getMyKolsView($this->session->userdata('user_id'));
			if (sizeof($viewMyKols) > 0) {
				$viewType = $viewMyKols;
				$viewTypeMyKols = MY_RECORDS;
			} else {
				$viewType = array(0);
				$viewTypeMyKols = MY_RECORDS;
			}
		} else {
			$viewTypeMyKols = ALL_RECORDS;
		}
		$arrAffiliations = $this->kol->getAffiliationsByParam($fromYear, $toYear, $kolIds, $arrEngTypes, $arrOrgType = '', $arrCountriesIds, $arrSpecialityIds, $selectType = 'kol_memberships.type', $arrListNamesIds, $arrStatesIds, $profileType, $viewType, $arrGlobalRegionIds,null,$fromType);
		$arrOrgCounts = array();
		foreach ($arrAffiliations as $row) {
			$arr = array();
			if ($row['type'] == "university") {
				$arr[] = "University/Hospital";
			} else {
				$arr[] = ucwords($row['type']);
			}
			$arr[] = (int) $row['count'];
			$arrOrgCounts[] = $arr;
		}
		$logKolIds = '';
		$i=1;
		foreach($arrKolNames as $rowId){
			if(sizeof($arrKolNames) !=$i){
				$logKolIds .= $rowId.',';
			}else{
				$logKolIds .= $rowId;
			}
			$i++;
		}
		echo json_encode($arrOrgCounts);
	}
	function get_dashboard_charts_data($kolId) {
// 		$this->load->model('json_store');
		$jsonFilter = "kol_id:" . $kolId;
		$arrStoredJson =false;//$this->json_store->getJsonByParamFromStore(JSON_STORE_DASHBOARD, $jsonFilter);
		if ($arrStoredJson == false) {
			$data = array();
			$data['affByOrgTypeData'] = array();
			$data['affByEngTypeData'] = array();
			$data['eventsTimelineData'] = array();
			$data['eventsByTopicData'] = array();
			$data['pubsByTimeData'] = array();
			$data['pubsAuthPosData'] = array();
			
			//-------------------------Affiliations by Org Type chart----------------------------------
			$arrKolIds[] = $kolId;
			$arrAffiliations = $this->kol->getAffiliationsByParam(0, 0, $arrKolIds, $arrEngTypes = '', $arrOrgType = '', $arrCountriesIds = '', $arrSpecialityIds = '', $selectType = 'kol_memberships.type', $arrListNamesIds = '', $arrStatesIds = '','',array(),'','',1);
			$arrOrgCounts = array();
			foreach ($arrAffiliations as $row) {
				$arr = array();
				if ($row['type'] == "university") {
					$arr[] = "University/Hospital";
				} else {
					$arr[] = ucwords($row['type']);
				}
				$arr[] = (int) $row['count'];
				$arrOrgCounts[] = $arr;
			}
			$data['affByOrgTypeData'] = $arrOrgCounts;
			
			//-------------------------Affiliations by Eng Type chart----------------------------------
			$arrAffiliations = array();
			$arrAffiliations = $this->kol->getAffiliationsByParam($fromYear, $toYear, $arrKolIds, $arrEngTypes = '', $arrOrgType, $arrCountriesIds, $arrSpecialityIds, $selectType = 'engagement_types.engagement_type', $arrListNamesIds, $arrStatesIds,'',array(),'','',1);
			$arrEngagementCounts = array();
			foreach ($arrAffiliations as $row) {
				$arr = array();
				if ($row['engagement_type'] != '') {
					$arr[] = $row['engagement_type'];
					$arr[] = (int) $row['count'];
					$arrEngagementCounts[] = $arr;
				}
			}
			$data['affByEngTypeData'] = $arrEngagementCounts;
			//-------------------------Events by timeline chart----------------------------------
			if($this->common_helper->check_module("events")){
				$this->load->model('events/event');
				
				$arrEventsTimelineData = $this->get_event_types_timeline_chart($kolId);
				$data['eventsTimelineData'] = $arrEventsTimelineData;
				
				//-------------------------Events by Topic chart----------------------------------
				$arrEventTopics = array();
				$arrEventsTopic = $this->event->getDataForEventsTopicChart($kolId, 0, 0);
				foreach ($arrEventsTopic as $row) {
					$arrTopic = array();
					$arrTopic[] = $row['name'];
					$arrTopic[] = (int) $row['count'];
					$arrEventTopics[] = $arrTopic;
				}
				$data['eventsByTopicData'] = $arrEventTopics;
			}
			if($this->common_helper->check_module("pubmeds")){
				$this->load->model('pubmeds/pubmed');
				//-------------------------Publications by timeline chart----------------------------------
				
				$arrPublications = $this->pubmed->getPublicationChart($kolId, 0, 0);
				$pubsByTimeData = array();
				$years = array();
				$count = array();
				
				foreach ($arrPublications as $publication) {
					if($publication['year']>0){
						$years[] = $publication['year'];
						$count[] = (int) $publication['count'];
					}
				}
				$pubsByTimeData[] = array_reverse($years);
				$pubsByTimeData[] = array_reverse($count);
				$data['pubsByTimeData'] = $pubsByTimeData;
				
				//-------------------------Publicatons authorship position chart----------------------------------
				$arrSingleAuthPubs = $this->pubmed->getKolPubsWithSingleAuthor($kolId, 0, 0);
				// 			echo $this->db->last_query();exit;
				$arrFirstAuthPubs = $this->pubmed->getKolPubsWithFirstAuthorship($kolId, 0, 0);
				$arrLastAuthPubs = $this->pubmed->getKolPubsWithLastAuthorship($kolId, 0, 0);
				$arrMiddleAuthPubs = $this->pubmed->getKolPubsWithMiddleAuthorship($kolId, 0, 0);
				
				$arrSingleAuthPubsCount = sizeof($arrSingleAuthPubs);
				$arrFirstAuthPubsCount = sizeof($arrFirstAuthPubs);
				$arrLastAuthPubsCount = 0;
				$arrMiddleAuthPubsCount = 0;
				
				foreach ($arrLastAuthPubs as $lastAuthPub) {
					if ($lastAuthPub['auth_pos'] == $lastAuthPub['max_pos'] && $lastAuthPub['max_pos'] != 1)
						$arrLastAuthPubsCount++;
				}
				
				foreach ($arrMiddleAuthPubs as $middleAuthPub) {
					if ($middleAuthPub['auth_pos'] != $middleAuthPub['max_pos'])
						$arrMiddleAuthPubsCount++;
				}
				$arrSectors = array(
						array("First Authorship", (int) $arrFirstAuthPubsCount),
						array("Single Authorship", (int) $arrSingleAuthPubsCount),
						array("Middle Authorship", (int) $arrMiddleAuthPubsCount),
						array("Last Authorship", (int) $arrLastAuthPubsCount),
				);
				$arrSectorsData = array();
				foreach ($arrSectors as $sector) {
					if ($sector[1] != 0)
						$arrSectorsData[] = $sector;
				}
				$data['pubsAuthPosData'] = $arrSectorsData;
			}
			
			//-------------------------Influence Map data calculation----------------------------------
			$arrFilterFields = array();
			$arrKeywords = array();
			$name = '';
			$arrPubDegrees = array('edu', 'org', 'aff', 'event', 'pub', 'trial');
			$kolName = $this->kol->getKolName($kolId);
			$arrKolIds = array();
			$arrKeywords[0] = $name;
			$arrKolDetailResult = $this->kol->getKolsLike1($arrKeywords, $arrFilterFields, 0, 0, false, false, null, $arrKolIds, true);
			
			$arrKolDetails = array();
			foreach ($arrKolDetailResult as $row) {
				$details = array();
				$details['first_name'] = $row['first_name'];
				$details['last_name'] = $row['last_name'];
				
				$arrKolDetails[$row['id']] = $details;
			}
			//pr($arrKolDetails);
			$arrData = array();
			$arrPubKols = array();
			$arrEventKols = array();
			$arrAffKols = array();
			$arrEduKols = array();
			$arrOrgKols = array();
			$arrTrialKols = array();
			
			if (in_array('pub', $arrPubDegrees)){
				if($this->common_helper->check_module("pubmeds")){
					$this->load->model('pubmeds/pubmed');
					$arrPubKols = $this->pubmed->getCoAuthoredKols($kolId);
				}
			}
			if (in_array('event', $arrPubDegrees))
				$arrEventKols = $this->kol->getCoEventedKols($kolId);
			if (in_array('aff', $arrPubDegrees))
				$arrAffKols = $this->kol->getCoAffiliatedKols($kolId);
			if (in_array('edu', $arrPubDegrees))
				$arrEduKols = $this->kol->getCoEducatedKols($kolId, $arrFilterFields);
			if (in_array('org', $arrPubDegrees))
				$arrOrgKols = $this->kol->getCoOrganizedKols($kolId, $arrFilterFields);
			if (in_array('trial', $arrPubDegrees)){
				if($this->common_helper->check_module("clinical_trials")){
					$this->load->model('clinical_trials/clinical_trial');
					$arrTrialKols = $this->clinical_trial->getCoTrialledKols($kolId, $arrFilterFields);
				}
			}
			$arrKols = $this->get_unique_kolids_with_weightages($arrPubKols, $arrEventKols, $arrAffKols, $arrEduKols, $arrOrgKols, $arrTrialKols);
			if (sizeof($arrKols) > 0) {
				$arrData = $arrKols;
			}
			//Prepares the Json data as required by the TheJit Radial/ForeceDirected graph
			$nodeData = array();
			$nodeData['$lineWidth'] = 5;
			$nodeData['$color'] = "#ddeeff";
			$nodeData['$dim'] = 0;
			$influenceData = array();
			$centerNode = array();
			$influenceData['id'] = 'kol-' . $kolId;
			$influenceData['name'] = $kolName['first_name'] . " " . $kolName['middle_name'] . " " . $kolName['last_name'];
			$influenceData['data'] = $nodeData;
			$influenceData['children'] = array();
			foreach ($arrData as $key => $value) {
				$nodeData = array();
				$nodeData['$color'] = "#555555";
				$nodeData['connections'] = $value['count'];
				$nodeDetails = array();
				$nodeDetails['id'] = $key;
				$nodeDetails['name'] = $arrKolDetails[$key]['first_name'] . " " . $arrKolDetails[$key]['last_name'];
				$nodeDetails['data'] = $nodeData;
				$arrAdjecencies = array();
				$nodeDetails['children'] = $arrAdjecencies;
				$influenceData['children'][] = $nodeDetails;
			}
			$retunData['connectionData'] = $influenceData;
			$retunData['coAuthorsKOLIds'] = array();
			$data['influenceData'] = $retunData;
			
			$rowData['json_data'] = json_encode($data);
			$rowData['ref_id'] = JSON_STORE_DASHBOARD;
			$rowData['filter'] = "kol_id:" . $kolId;
// 			$this->json_store->insertJsonToStore($rowData);
			
			ob_start('ob_gzhandler');
			echo json_encode($data);
		} else {
			$data = $arrStoredJson['json_data'];
			ob_start('ob_gzhandler');
			echo $data;
		}
	}
	function get_event_types_timeline_chart($kolId) {
		$arrKolIds = array();
		$arrKolIds[] = $kolId;
		$congressData = array();
		$conferenceData = array();
		$grData = array();
		$amData = array();
		$cmeData = array();
		$webcastData = array();
		$webinarData = array();
		$podcastData = array();
		$arrEventTypeDetails = $this->kol->getEventTypesCountByTimeLine(0, 0, $arrKolIds, $arrCountriesIds, $arrSpecialityIds, $arrListNamesIds, $arrStatesIds);
		
		//get unique years
		$arrUniqueYears = $this->get_unique_years($arrEventTypeDetails);
		$arrEventTypeCount = array();
		$arrEventTypes = array();
		$arrEventTypesData = array();
		$arrUniqueYear = array();
		foreach ($arrEventTypeDetails as $eventTypeDetail) {
			$arrEventTypeCount[$eventTypeDetail['year']][$eventTypeDetail['event_type']] += (int) $eventTypeDetail['count'];
			//$arrEventTypeCount[$eventTypeDetail['event_type']][$eventTypeDetail['year']] += (int)$eventTypeDetail['count'];
			$arrEventTypes[$eventTypeDetail['event_type']] = 1;
		}
		ksort($arrEventTypeCount);
		foreach ($arrEventTypeCount as $year => $arrRow) {
			$arrUniqueYear[] = (string) $year;
			foreach ($arrEventTypes as $eventType => $value) {
				if (isset($arrRow[$eventType])) {
					$arrEventTypesData[$eventType][] = $arrRow[$eventType];
				} else {
					$arrEventTypesData[$eventType][] = 0;
				}
			}
		}
		$arrTypesData = array();
		$arrData[] = array_values($arrUniqueYear);
		foreach ($arrEventTypes as $eventType => $value) {
			$arrTypesData[] = array('name' => $eventType, 'data' => $arrEventTypesData[$eventType]);
		}
		$arrData[] = $arrTypesData;
		return $arrData;
	}
	function get_unique_years($arrEventTypeDetails) {
		$arrYears = array();
		foreach ($arrEventTypeDetails as $eventTypeDetail) {
			$arrYears[] = $eventTypeDetail['year'];
		}
		$arrUniqueYears = array_unique($arrYears);
		sort($arrUniqueYears);
		return $arrUniqueYears;
	}
	function get_unique_kolids_with_weightages($arrPubKols, $arrEventKols, $arrAffKols, $arrEduKols, $arrOrgKols, $arrTrialKols) {
		$arrKols = array();
		if (sizeof($arrPubKols) > 0) {
			foreach ($arrPubKols as $row) {
				$arrKols[$row['kol_id']] = $row;
			}
		}
		
		if (sizeof($arrEventKols) > 0) {
			foreach ($arrEventKols as $row) {
				if (array_key_exists($row['kol_id'], $arrKols)) {
					$arrKols[$row['kol_id']]['count'] = (int) $arrKols[$row['kol_id']]['count'] + (int) $row['count'];
				} else {
					$arrKols[$row['kol_id']] = $row;
				}
			}
		}
		
		if (sizeof($arrAffKols) > 0) {
			foreach ($arrAffKols as $row) {
				if (array_key_exists($row['kol_id'], $arrKols)) {
					$arrKols[$row['kol_id']]['count'] = (int) $arrKols[$row['kol_id']]['count'] + (int) $row['count'];
				} else {
					$arrKols[$row['kol_id']] = $row;
				}
			}
		}
		if (sizeof($arrOrgKols) > 0) {
			foreach ($arrOrgKols as $row) {
				if (array_key_exists($row['kol_id'], $arrKols)) {
					$arrKols[$row['kol_id']]['count'] = (int) $arrKols[$row['kol_id']]['count'] + (int) $row['count'];
				} else {
					$arrKols[$row['kol_id']] = $row;
				}
			}
		}
		if (sizeof($arrEduKols) > 0) {
			foreach ($arrEduKols as $row) {
				if (array_key_exists($row['kol_id'], $arrKols)) {
					$arrKols[$row['kol_id']]['count'] = (int) $arrKols[$row['kol_id']]['count'] + (int) $row['count'];
				} else {
					$arrKols[$row['kol_id']] = $row;
				}
			}
		}
		if (sizeof($arrTrialKols) > 0) {
			foreach ($arrTrialKols as $row) {
				if (array_key_exists($row['kol_id'], $arrKols)) {
					$arrKols[$row['kol_id']]['count'] = (int) $arrKols[$row['kol_id']]['count'] + (int) $row['count'];
				} else {
					$arrKols[$row['kol_id']] = $row;
				}
			}
		}
		return $arrKols;
	}
	function add_client_affiliations($kolId,$affId=null) {
		$data['kolId'] = $kolId;
		$data['affId'] = $affId;
		if($affId!=null){
			$data['arrAffiliationsData'] =  $this->kol->getAffiliationsDataById($affId);
		}
		$data['arrMembership'] = array('engagement_id' => '');
		$arrEngagementTypes = $this->kol->getAllEngagementTypes();
		$key = array_search('Other', $arrEngagementTypes);
		unset($arrEngagementTypes[$key]);
		$arrEngagementTypes[$key] = 'Other';
		$data['arrEngagementTypes'] = $arrEngagementTypes;
		$data['contentPage'] = 'add_client_affiliations';
		$data['contentData']			=$data;
		$this->load->view(CLIENT_LAYOUT,$data);
	}
	function get_institute_id_else_save() {
		$name = $this->input->post('name');
		$institutionDetails = array('name' => $name,
				'notes' => $this->input->post('notes'),
				'created_by' => $this->loggedUserId,
				'created_on' => date('Y-m-d H:i:s'));
		$instituteId = $this->kol->getInstituteIdElseSave($institutionDetails);
		echo json_encode($instituteId);
	}
	function update_membership() {
		$dataType = 'User Added';
		$client_id =$this->session->userdata('client_id');
		if($client_id == INTERNAL_CLIENT_ID){
			$dataType = 'Aissel Analyst';
		}
		$arrMembership['id'] = $this->input->post('id');
		$arrMembership['type'] = $this->input->post('type');
		$arrMembership['institute_id'] = $this->input->post('institute_id');
		$arrMembership['committee'] = ucwords(trim($this->input->post('committee')));
		$arrMembership['department'] = ucwords(trim($this->input->post('department')));
		$arrMembership['title'] = ucwords(trim($this->input->post('title')));
		$arrMembership['start_date'] = $this->input->post('start_date');
		$arrMembership['end_date'] = $this->input->post('end_date');
		$arrMembership['role'] = ucwords($this->input->post('role'));
		$arrMembership['division'] = ucwords($this->input->post('division'));
		$arrMembership['purpose'] = $this->input->post('purpose');
		$arrMembership['amount'] = $this->input->post('amount');
		$arrMembership['url1'] = $this->input->post('url1');
		$arrMembership['url2'] = $this->input->post('url2');
		$arrMembership['notes'] = $this->input->post('notes');
		$arrMembership['engagement_id'] = $this->input->post('engagement_id');
		$arrMembership['client_id'] = $this->session->userdata('client_id');
		$arrMembership['modified_by'] = $this->loggedUserId;
		$arrMembership['modified_on'] = date("Y-m-d H:i:s");
		$arrMembership['data_type_indicator'] = $dataType;
		// Create an array to return the result
		$arrResult = array();
		$this->load->Model('kol');
		if ($this->kol->updateMembership($arrMembership)) {
// 			$this->update->insertUpdateEntry(KOL_PROFILE_AFFILITION_UPDATE, $arrMembership['id'], MODULE_KOL_AFFILIATION, $this->input->post('kol_id'));
			$arrResult['saved'] = true;
			$arrResult['msg'] = 'Affiliation Details are successfully updated.';
			$arrResult['lastInsertId'] = $arrMembership['id'];
			$arrMembership['engagement_id'] = $this->kol->getEngagementName($arrMembership['engagement_id']);
			$arrMembership['institute_id'] = $this->kol->getInstituteName($arrMembership['institute_id']);
			if ($arrMembership['url1'] != '') {
				$arrMembership['url1'] = '<a href=\'' . $arrMembership['url1'] . '\' target="_new">URl1</a>';
			}
			if ($arrMembership['url2'] != '') {
				$arrMembership['url2'] = '<a href=\'' . $arrMembership['url2'] . '\' target="_new">URl2</a>';
			}
			$arrResult['data'] = $arrMembership;
		} else {
			$arrResult['saved'] = false;
			$arrResult['msg'] = 'Error in saving affiliation details.';
		}
		echo json_encode($arrResult);
	}
	function save_membership() {
		//getting post Details
		$dataType = 'User Added';
		$client_id =$this->session->userdata('client_id');
		if($client_id == INTERNAL_CLIENT_ID){
			$dataType = 'Aissel Analyst';
		}
		$arrMembership['kol_id'] = $this->input->post('kol_id');
		$arrMembership['type'] = $this->input->post('type');
		$arrMembership['institute_id'] = $this->input->post('institute_id');
		$arrMembership['committee'] = ucwords(trim($this->input->post('committee')));
		$arrMembership['department'] = ucwords(trim($this->input->post('department')));
		$arrMembership['title'] = ucwords(trim($this->input->post('title')));
		$arrMembership['start_date'] = $this->input->post('start_date');
		$arrMembership['end_date'] = $this->input->post('end_date');
		$arrMembership['role'] = ucwords(trim($this->input->post('role')));
		$arrMembership['division'] = ucwords(trim($this->input->post('division')));
		$arrMembership['purpose'] = ucwords(trim($this->input->post('purpose')));
		$arrMembership['amount'] = $this->input->post('amount');
		$arrMembership['url1'] = $this->input->post('url1');
		$arrMembership['url2'] = $this->input->post('url2');
		$arrMembership['notes'] = $this->input->post('notes');
		$arrMembership['created_by'] = $this->loggedUserId;
		$arrMembership['created_on'] = date("Y-m-d H:i:s");
		$arrMembership['notes'] = $this->input->post('notes');
		$arrMembership['engagement_id'] = $this->input->post('engagement_id');
		$arrMembership['client_id'] = $this->session->userdata('client_id');
		$arrMembership['data_type_indicator'] = $dataType;
		// Create an array to return the result
		$arrResult = array();
		if ($lastInsertId = $this->common_helper->insertEntity('kol_memberships',$arrMembership)) {
			if ($arrMembership['type'] == "university") {
				$arrMembership['type'] = "Univ/Hospital";
			} else {
				$arrMembership['type'] = ucwords($arrMembership['type']);
			}
			$arrResult['saved'] = true;
			$arrResult['msg'] = "Saved Successfully";
			$arrResult['lastInsertId'] = $lastInsertId;
			$arrMembership['engagement_id'] = $this->kol->getEngagementName($arrMembership['engagement_id']);
			$arrMembership['institute_id'] = $this->kol->getInstituteName($arrMembership['institute_id']);
			$arrMembership['institute_id'] = ucwords($arrMembership['institute_id']);
			if ($arrMembership['url1'] != '') {
				$arrMembership['url1'] = '<a href=\'' . $arrMembership['url1'] . '\' target="_new">URl1</a>';
			}
			if ($arrMembership['url2'] != '') {
				$arrMembership['url2'] = '<a href=\'' . $arrMembership['url2'] . '\' target="_new">URl2</a>';
			}
			$arrMembership['date'] = '';
			 if ($arrMembership['start_date'] != '')
			 	$arrMembership['date'] .= $arrMembership['start_date'];
		 	else
		 		$arrMembership['date'] .= 'NA';
	 		$arrMembership['date'] .= " - ";
	 		if ($arrMembership['end_date'] != '')
	 			$arrMembership['date'] .= $arrMembership['end_date'];
 			else
 				$arrMembership['date'] .= 'NA';
 			if ($arrMembership['date'] == 'NA - NA') {
 				$arrMembership['date'] = '';
 			}
 			$arrResult['data'] = $arrMembership;
		} else {
			$arrResult['saved'] = false;
		}
		echo json_encode($arrResult);
	}
	function get_pub_authors($pubId) {
		$authNames = '';
		$arrAuthors = $this->pubmed->listPublicationAuthors($pubId);
		foreach ($arrAuthors as $author) {
			$authName = $author['last_name'] . " " . $author['initials'];
			if ($authNames == '') {
				$authNames = $authName;
			} else {
				$authNames = $authNames . "," . $authName;
			}
		}
		return $authNames;
	}

	function calculate_dashboard_data($kolId = null){
		ini_set('memory_limit', "-1");
		ini_set("max_execution_time", 0);
		$this->load->model('json_store/json_store');
		if ($kolId != null)
			$arrKols = array($kolId => 0);
		else
			$arrKols = $this->kol->getKolsIdAndPin();
				
		foreach ($arrKols as $kolId => $pin){
			$jsonFilter = "kol_id:" . $kolId;
			$this->json_store->deleteFromStore(JSON_STORE_DASHBOARD, $jsonFilter);
			$data = array();
			$data['affByOrgTypeData'] = array();
			$data['affByEngTypeData'] = array();
			$data['eventsTimelineData'] = array();
			$data['eventsByTopicData'] = array();
			$data['pubsByTimeData'] = array();
			$data['pubsAuthPosData'] = array();
			
			//-------------------------Affiliations by Org Type chart----------------------------------
			$arrKolIds[] = $kolId;
			$arrAffiliations = $this->kol->getAffiliationsByParam(0, 0, $arrKolIds, $arrEngTypes = '', $arrOrgType = '', $arrCountriesIds = '', $arrSpecialityIds = '', $selectType = 'kol_memberships.type', $arrListNamesIds = '', $arrStatesIds = '');
			$arrOrgCounts = array();
			foreach ($arrAffiliations as $row) {
				$arr = array();
				if ($row['type'] == "university") {
					$arr[] = "University/Hospital";
				} else {
					$arr[] = ucwords($row['type']);
				}
				$arr[] = (int) $row['count'];
				$arrOrgCounts[] = $arr;
			}
			$data['affByOrgTypeData'] = $arrOrgCounts;
			
			//-------------------------Affiliations by Eng Type chart----------------------------------
			$arrAffiliations = array();
			$arrAffiliations = $this->kol->getAffiliationsByParam($fromYear, $toYear, $arrKolIds, $arrEngTypes = '', $arrOrgType, $arrCountriesIds, $arrSpecialityIds, $selectType = 'engagement_types.engagement_type', $arrListNamesIds, $arrStatesIds);
			$arrEngagementCounts = array();
			foreach ($arrAffiliations as $row) {
				$arr = array();
				if ($row['engagement_type'] != '') {
					$arr[] = $row['engagement_type'];
					$arr[] = (int) $row['count'];
					$arrEngagementCounts[] = $arr;
				}
			}
			$data['affByEngTypeData'] = $arrEngagementCounts;
			if($this->common_helper->check_module("events")){
				$this->load->model('events/event');
				//-------------------------Events by timeline chart----------------------------------
				$arrEventsTimelineData = $this->get_event_types_timeline_chart($kolId);
				$data['eventsTimelineData'] = $arrEventsTimelineData;
				
				//-------------------------Events by Topic chart----------------------------------
				$arrEventTopics = array();
				$arrEventsTopic = $this->event->getDataForEventsTopicChart($kolId, 0, 0);
				foreach ($arrEventsTopic as $row) {
					$arrTopic = array();
					$arrTopic[] = $row['name'];
					$arrTopic[] = (int) $row['count'];
					$arrEventTopics[] = $arrTopic;
				}
				$data['eventsByTopicData'] = $arrEventTopics;
			}
			//-------------------------Publications by timeline chart----------------------------------
			if($this->common_helper->check_module("pubmeds")){
				$this->load->model('pubmeds/pubmed');
				$arrPublications = $this->pubmed->getPublicationChart($kolId, 0, 0);
				$pubsByTimeData = array();
				$years = array();
				$count = array();
				foreach ($arrPublications as $publication) {
					$years[] = $publication['year'];
					$count[] = (int) $publication['count'];
				}
				$pubsByTimeData[] = array_reverse($years);
				$pubsByTimeData[] = array_reverse($count);
				$data['pubsByTimeData'] = $pubsByTimeData;
			
				//-------------------------Publicatons authorship position chart----------------------------------
				$arrSingleAuthPubs = $this->pubmed->getKolPubsWithSingleAuthor($kolId, 0, 0);
				$arrFirstAuthPubs = $this->pubmed->getKolPubsWithFirstAuthorship($kolId, 0, 0);
				$arrLastAuthPubs = $this->pubmed->getKolPubsWithLastAuthorship($kolId, 0, 0);
				$arrMiddleAuthPubs = $this->pubmed->getKolPubsWithMiddleAuthorship($kolId, 0, 0);
				
				$arrSingleAuthPubsCount = sizeof($arrSingleAuthPubs);
				$arrFirstAuthPubsCount = sizeof($arrFirstAuthPubs);
				$arrLastAuthPubsCount = 0;
				$arrMiddleAuthPubsCount = 0;
				
				foreach ($arrLastAuthPubs as $lastAuthPub) {
					if ($lastAuthPub['auth_pos'] == $lastAuthPub['max_pos'] && $lastAuthPub['max_pos'] != 1)
						$arrLastAuthPubsCount++;
				}
				
				foreach ($arrMiddleAuthPubs as $middleAuthPub) {
					if ($middleAuthPub['auth_pos'] != $middleAuthPub['max_pos'])
						$arrMiddleAuthPubsCount++;
				}
				$arrSectors = array(
						array("First Authorship", (int) $arrFirstAuthPubsCount),
						array("Single Authorship", (int) $arrSingleAuthPubsCount),
						array("Middle Authorship", (int) $arrMiddleAuthPubsCount),
						array("Last Authorship", (int) $arrLastAuthPubsCount),
				);
				$arrSectorsData = array();
				foreach ($arrSectors as $sector) {
					if ($sector[1] != 0)
						$arrSectorsData[] = $sector;
				}
				$data['pubsAuthPosData'] = $arrSectorsData;
			}
			//-------------------------Influence Map data calculation----------------------------------
			$arrFilterFields = array();
			$name = '';
			$arrPubDegrees = array('edu', 'org', 'aff', 'event', 'pub', 'trial');
			$kolName = $this->kol->getKolName($kolId);
			$arrKolIds = array();
			$arrKeywords[0] = $name;
			$arrKolDetailResult = $this->kol->getKolsLike1($arrKeywords, $arrFilterFields, 0, 0, false, false, null, $arrKolIds, true);
			
			$arrKolDetails = array();
			foreach ($arrKolDetailResult as $row) {
				$details = array();
				$details['first_name'] = $row['first_name'];
				$details['last_name'] = $row['last_name'];
				
				$arrKolDetails[$row['id']] = $details;
			}
			//pr($arrKolDetails);exit;
			$arrData = array();
			$arrPubKols = array();
			$arrEventKols = array();
			$arrAffKols = array();
			$arrEduKols = array();
			$arrOrgKols = array();
			$arrTrialKols = array();
			
			if (in_array('pub', $arrPubDegrees))
				$arrPubKols = $this->pubmed->getCoAuthoredKols($kolId);
			if (in_array('event', $arrPubDegrees))
				$arrEventKols = $this->kol->getCoEventedKols($kolId);
			if (in_array('aff', $arrPubDegrees))
				$arrAffKols = $this->kol->getCoAffiliatedKols($kolId);
			if (in_array('edu', $arrPubDegrees))
				$arrEduKols = $this->kol->getCoEducatedKols($kolId, $arrFilterFields);
			if (in_array('org', $arrPubDegrees))
				$arrOrgKols = $this->kol->getCoOrganizedKols($kolId, $arrFilterFields);
			if (in_array('trial', $arrPubDegrees)){
				if($this->common_helper->check_module("clinical_trials")){
					$this->load->model('clinical_trials/clinical_trial');
					$arrTrialKols = $this->clinical_trial->getCoTrialledKols($kolId, $arrFilterFields);
				}
			}
			$arrKols = $this->get_unique_kolids_with_weightages($arrPubKols, $arrEventKols, $arrAffKols, $arrEduKols, $arrOrgKols, $arrTrialKols);
			if (sizeof($arrKols) > 0) {
				$arrData = $arrKols;
			}
									
			//Prepares the Json data as required by the TheJit Radial/ForeceDirected graph
			$nodeData = array();
			$nodeData['$lineWidth'] = 5;
			$nodeData['$color'] = "#ddeeff";
			$nodeData['$dim'] = 0;
			$influenceData = array();
			$centerNode = array();
			$influenceData['id'] = 'kol-' . $kolId;
			$influenceData['name'] = $kolName['first_name'] . " " . $kolName['middle_name'] . " " . $kolName['last_name'];
			$influenceData['data'] = $nodeData;
			$influenceData['children'] = array();
			foreach ($arrData as $key => $value) {
				//echo "KolId : ".$key."<br>";
				$nodeData = array();
				$nodeData['$color'] = "#555555";
				$nodeData['connections'] = $value['count'];
				$nodeDetails = array();
				$nodeDetails['id'] = $key;
				$nodeDetails['name'] = $arrKolDetails[$key]['first_name'] . " " . $arrKolDetails[$key]['last_name'];
				$nodeDetails['data'] = $nodeData;
				$arrAdjecencies = array();
				$nodeDetails['children'] = $arrAdjecencies;
				$influenceData['children'][] = $nodeDetails;
			}
			$retunData['connectionData'] = $influenceData;
			$retunData['coAuthorsKOLIds'] = array();
			$data['influenceData'] = $retunData;
			if (sizeof($influenceData['children']) > 0)
				$sampleName = $influenceData['children'][0]['name'];
			else
				$sampleName = 'NA';
			if ($sampleName != '') {
				$rowData['json_data'] = json_encode($data);
				$rowData['ref_id'] = JSON_STORE_DASHBOARD;
				$rowData['filter'] = "kol_id:" . $kolId;
				$this->json_store->insertJsonToStore($rowData);
				echo "Id :" . $kolId . '<br>';
			} else {
				echo $kolId . '<br>';
			}
		}
		echo "Calculation Complete.";
		$filePath = $_SERVER['DOCUMENT_ROOT'] . "/" . $this->config->item('app_folder_path') . "system/logs/jobs/cron_job_status.txt";
		$content = "dashboard_data_cron.php :: " . date("Y-m-d H:i:s") . "::success :: no comments \r\n";
		//Log Activity
		$arrLogDetails = array(
				'type'=>CRON_JOBS,
				'description'=>"Calculation Complete",
				'status' => STATUS_SUCCESS,
				'transaction_table_id'=>'',
				'transaction_name'=>'calculate dashboard data',
				'miscellaneous1'=>$url
		);
		$this->config->set_item('log_details', $arrLogDetails);
		file_put_contents($filePath, $content, FILE_APPEND | LOCK_EX);
	}
	function note_document_download($noteId){
		$arrNotes = $this->common_helper->getEntityById('kol_notes',array('id'=>$noteId));
		$this->load->helper('download');
		ob_clean();
		$data =  file_get_contents(APPPATH."documents/kol_note_documents/".$arrNotes[0]['document']);
		$arrFileName    = explode(".",$arrNotes[0]['document']);
		$name = $arrNotes[0]['document_name'].".".$arrFileName[sizeof($arrFileName)-1];
		force_download($name, $data);
	}
	function delete_notes($noteId) {
		$arrData = array();
		if ($this->kol->deleteNote($noteId)) {
			$arrData['status'] = true;
		} else {
			$arrData['status'] = false;
		}
		echo json_encode($arrData);
	}
	function save_notes($kolId) {
		ini_set('memory_limit',"-1");
		ini_set("max_execution_time",0);
		$arr['note'] = trim($this->input->post('user_note'));
		$arr['created_by'] = $this->loggedUserId;
		$arr['created_on'] = date("Y-m-d H:i:s");
		$arr['client_id'] = $this->session->userdata['client_id'];
		$arr['kol_id'] = $kolId;
		$arr['document']= '';
		$arr['document_name']= '';
		if($_FILES["note_file"]['name']!=''){
			$target_path = APPPATH."/documents/kol_note_documents/";
			$path_info = pathinfo($_FILES["note_file"]['name']);
			$newFileName	= random_string('unique', 20).".".$path_info['extension'];
			$overview_file_target_path = $target_path ."/". $newFileName;
			if(move_uploaded_file($_FILES['note_file']['tmp_name'],$overview_file_target_path)){
				$arr['document'] = $newFileName;
				$fname = explode('.', $_FILES["note_file"]['name']);
				$arr['orginal_doc_name']= $_FILES["note_file"]['name'];
				$arr['document_name']= $fname[0];
				if($this->input->post('fileName')){
					$arr['document_name']= trim($this->input->post('fileName'));
				}
			}
		}
		$arr['id'] = $this->common_helper->insertEntity('kol_notes',$arr);
		
		if($this->session->userdata['client_id']!=INTERNAL_CLIENT_ID)
			$arr['name'] = $this->session->userdata('user_full_name');
		else
			$arr['name'] = 'Aissel Analyst';
		$currentDateTime = $arr['created_on'];
		
		$arr['created_on'] = date('d M Y, h:i A', strtotime($currentDateTime));
		$arr['note'] = nl2br($arr['note']);
		$formData = $_POST;
		$formData = json_encode($formData);
		echo json_encode($arr);
	}
	function delete_notes_attachment($noteId){
		$arrData = array();
		if ($this->kol->deleteNoteAttachment($noteId)) {
			$arrData['status'] = true;
		} else {
			$arrData['status'] = false;
		}
		echo json_encode($arrData);
	}
	function get_notes_by_id($noteId) {
		$arr = $this->kol->getNotesById($noteId);
		$arr = $arr[0];
		if ($arr['modified_on'] != '')
			$currentDateTime = $arr['modified_on'];
		else
			$currentDateTime = $arr['created_on'];
		$arr['created_on'] = date('Y-m-d,h:i A', strtotime($currentDateTime));
		$arr['created_on'] = str_replace(',', " at ", $arr['created_on']);
		echo json_encode($arr);
	}
	function add_client_education($kolId,$eId=null,$type='') {
		$data['kolId'] = $kolId;
		$data['locationType'] = array(
				'1' => 'Private practice',
				'2' => 'Hospital',
				'3' => 'Institution',
				'4' => 'Physical',
				'5' => 'Others'
		);
		if(eId!=null){
			$data['eId'] = $eId;
			$data['type'] = $type;
			$data['eduData'] = $this->kol->getEducationById($eId); 
		}
		$this->load->view('add_client_education', $data);
	}
	function get_institute_names($instituteName) {
		$instituteName = urldecode($this->input->post($instituteName));
		$arrInstituteNames = $this->kol->getInstituteNames($instituteName);
		$arrSuggestInstitutes = array();
		if (sizeof($arrInstituteNames) == 0) {
			$arrSuggestInstitutes[0] = 'No results found for ' . $instituteName;
		} else {
			$flag = 1;
			foreach ($arrInstituteNames as $id => $name) {
				if ($flag) {
					$arrSuggestInstitutes[] = '<div class="autocompleteHeading">Institute Names</div><div class="dataSet"><label name="' . $id . '" class="educations" style="display:block">' . $name . "</label></div>";
					$flag = 0;
				} else {
					$arrSuggestInstitutes[] = '<div class="dataSet"><label name="' . $id . '" class="educations" style="display:block">' . $name . "</label></div>";
				}
			}
		}
		$arrReturnData['query'] = $instituteName;
		$arrReturnData['suggestions'] = $arrSuggestInstitutes;
		echo json_encode($arrReturnData);
	}
	function delete_education_detail($id) {
		$arrdetails['table']='kol_educations';
		$arrdetails['id']=$id;
		if ($this->common_helper->deleteById($arrdetails)) {
// 			$this->update->deleteUpdateEntry(KOL_PROFILE_EDUCATION_ADD, $id, MODULE_KOL_EDUCATION);
// 			$this->update->deleteUpdateEntry(KOL_PROFILE_EDUCATION_UPDATE, $id, MODULE_KOL_EDUCATION);
			//echo 'success';
			$data['status'] = 'success';
		} else {
			//echo 'failed to delete';
			$data['status'] = 'fail';
		}
		echo json_encode($data);
	}
	function save_education_detail() {
		if (isset($_POST) && count($_POST) > 0) {
			// Getting the POST details of Education
			$dataType = 'User Added';
			$client_id =$this->session->userdata('client_id');
			if($client_id == INTERNAL_CLIENT_ID){
				$dataType = 'Aissel Analyst';
			}
			$educationDetails = array('type' => $this->input->post('type'),
					'institute_id' => $this->input->post('institute_id'),
					'degree' => ucwords(trim($this->input->post('degree'))),
					'specialty' => ucwords(trim($this->input->post('specialty'))),
					'start_date' => trim($this->input->post('start_date')),
					'end_date' => trim($this->input->post('end_date')),
					'honor_name' => $this->input->post('honor_name'),
					'year' => $this->input->post('year'),
					'url1' => $this->input->post('url1'),
					'url2' => $this->input->post('url2'),
					'notes' => $this->input->post('notes'),
					'created_by' => $this->loggedUserId,
					'created_on' => date("Y-m-d H:i:s"),
					'kol_id' => $this->input->post('kol_id'),
					'client_id' => $this->session->userdata('client_id'),
					'data_type_indicator' => $dataType
			);
			if($educationDetails['type'] == 'board_certification'){
				$educationDetails['degree'] = '';
			}
			// Create an array to return the result
			$arrResult = array();
			if (!isset($educationDetails['institute_id']) || $educationDetails['institute_id'] == 0 || $educationDetails['institute_id'] == '')
				$educationDetails['institute_id'] = null;
			if ($lastInsertId = $this->kol->saveEducationDetail($educationDetails)) {
				$arrEducationDetails = $this->kol->getEducationById($lastInsertId);
				$educationDetails['first_name'] = $arrEducationDetails['first_name'];
				$educationDetails['last_name'] = $arrEducationDetails['last_name'];
				$educationDetails['is_analyst'] = $arrEducationDetails['is_analyst'];
				// $this->update->insertUpdateEntry(KOL_PROFILE_EDUCATION_ADD, $lastInsertId, MODULE_KOL_EDUCATION, $educationDetails['kol_id']);
				$transaction_name = ucfirst($educationDetails['type']);
				$arrResult['saved'] = true;
				$arrResult['lastInsertId'] = $lastInsertId;
				
				//This field value doesn't apply's to "honors_awards"
				if ($educationDetails['type'] != 'honors_awards') {
					//Getting the name of the  Institute By Passing The Id
					$educationDetails['institute_id'] = $this->kol->getInstituteName($educationDetails['institute_id']);
				}
				//Additional prameter 'date' for client view use only. It doesn't afeect or include in the Analyst app
				$educationDetails['date'] = '';
				 if ($educationDetails['start_date'] != '')
				 	$educationDetails['date'] .=$educationDetails['start_date'];
			 	else
			 		$educationDetails['date'] .='NA';
			 		
		 		$educationDetails['date'] .=" - ";
		 		
		 		if ($educationDetails['end_date'] != '')
		 			$educationDetails['date'] .=$educationDetails['end_date'];
	 			else
	 				$educationDetails['date'] .='NA';
 				
 				if ($educationDetails['date'] == 'NA - NA') {
 					$educationDetails['date'] = '';
 				}
 				//End of Additional prameter 'date' for client view use only
 				if ($educationDetails['url1'] != '') {
 					$educationDetails['url1'] = '<a href=\'' . $educationDetails['url1'] . '\' target="_new">URL1</a>';
 				}
 				if ($educationDetails['url2'] != '') {
 					$educationDetails['url2'] = '<a href=\'' . $educationDetails['url2'] . '\' target="_new">URL2</a>';
 				}
 				$arrResult['data'] = $educationDetails;
			} else {
				$arrResult['saved'] = false;
			}
			echo json_encode($arrResult);
		}
	}
	function update_education_detail() {
		if (isset($_POST) && count($_POST) > 0) {
			$inst_id = null;
			if($this->input->post('institute_id')){
				$inst_id = $this->input->post('institute_id');
			}
			// Getting the POST details of Education
			$educationDetails = array('id' => $this->input->post('id'),
					'type' => $this->input->post('type'),
					'institute_id' => $inst_id,
					'degree' => ucwords(trim($this->input->post('degree'))),
					'specialty' => ucwords(trim($this->input->post('specialty'))),
					'start_date' => $this->input->post('start_date'),
					'end_date' => $this->input->post('end_date'),
					'honor_name' => $this->input->post('honor_name'),
					'year' => $this->input->post('year'),
					'url1' => $this->input->post('url1'),
					'url2' => $this->input->post('url2'),
					'notes' => $this->input->post('notes'),
					'modified_by' => $this->loggedUserId,
					'modified_on' => date("Y-m-d H:i:s")
			);
			if($educationDetails['type'] == 'board_certification'){
				$educationDetails['degree'] = '';
			}
			// Create an array to return the result
			$arrResult = array();
			if ($this->kol->updateEducationDetail($educationDetails)) {
				//$this->update->insertUpdateEntry(KOL_PROFILE_EDUCATION_UPDATE, $educationDetails['id'], MODULE_KOL_EDUCATION, $this->input->post('kol_id'));
				$transaction_name = ucfirst($educationDetails['type']);
				$arrResult['saved'] = true;
				$arrResult['lastInsertId'] = $educationDetails['id'];
				//This field value doesn't apply's to "honors_awards"
				if ($educationDetails['type'] != 'honors_awards') {
					//Getting the name of the  Institute By Passing The Id
					$educationDetails['institute_id'] = $this->kol->getInstituteName($educationDetails['institute_id']);
				}
				if ($educationDetails['url1'] != '') {
					$educationDetails['url1'] = '<a href=\'' . $educationDetails['url1'] . '\' target="_new">URL1</a>';
				}
				if ($educationDetails['url2'] != '') {
					$educationDetails['url2'] = '<a href=\'' . $educationDetails['url2'] . '\' target="_new">URL2</a>';
				}
				$arrResult['data'] = $educationDetails;
			} else {
				$arrResult['saved'] = false;
			}
			echo json_encode($arrResult);
		}
	}
	function send_profile_email($kolId) {
		$data['kolId'] = $kolId;
		$arrKolName = $this->kol->getKolName($kolId);
		$kolName = '';
		$kolName = $this->common_helper->get_name_format($arrKolName['first_name'],$arrKolName['middle_name'], $arrKolName['last_name']);
		$data['kolName'] = $kolName;
		$this->load->view('email/email_form', $data);
	}
	function send_email() {
		$format = $this->input->post('format');
		$kolId = $this->input->post('kolId');
		$from = $this->input->post('from');
		$to = $this->input->post('to');
		$email = $this->input->post('email');
		$note = $this->input->post('note');
		$note = nl2br($note);
		$config['protocol'] = PROTOCOL;
		
		$config['smtp_host'] = HOST;
		$config['smtp_port'] = PORT;
		$config['smtp_user'] = USER;
		$config['smtp_pass'] = PASS;
		$config['mailtype'] = 'html';
		$this->load->library('email', $config);
		$this->email->set_newline("\r\n");
		$this->email->initialize($config);
		$this->email->clear();
		$this->email->set_newline("\r\n");
		$this->email->from(USER, $from);
		$this->email->to($to);
		//Get KOl name
		$arrKolName = $this->kol->getKolName($kolId);
		$kolName = '';
		$kolName = $this->common_helper->get_name_format($arrKolName['first_name'],$arrKolName['middle_name'], $arrKolName['last_name']);
		//If Request of format is Pdf then get Pdf file Content for emial
		switch ($format){
			case 'Pdf':$logDescription = $note;
						$this->email->message($note);
						$pdfPath = $this->get_mini_profile_as_pdf($kolId);
						$this->email->attach($pdfPath, 'attachment');
						break;
			case 'Excel':$logDescription = $note;
						$this->email->message($note);
						$excelPath = $this->export_kol(true,$kolId,$kolName);
						$this->email->attach($excelPath, 'attachment');
						break;
			case 'html':$arrHtml = $this->get_mini_profile_as_html($kolId, 'Email');
						$html = $arrHtml[0];
						$this->email->message(nl2br($note) . "<br /><hr /><br />" . $html);
						$logDescription = nl2br($note) . "<br /><hr /><br />" . $html;
						break;
		}
		$this->email->subject("Dr. " . $kolName . " Profile");
		$this->email->set_crlf("\r\n");
		if ($this->email->send()){
			$emailData['status'] = 'Your message has been sent';
			if (isset($excelPath)) {
				unlink($excelPath);
			}
			if (isset($pdfPath)) {
				unlink($pdfPath);
			}
		} else {
			$emailData['status'] = 'Mail not sent';
		}
		show_error($this->email->print_debugger());
		echo json_encode($emailData);
	}
	function get_mini_profile_as_pdf($kolId) {
		$arrHtml = $this->get_mini_profile_as_html($kolId,'Pdf');
		$html = $arrHtml[0];
		$fileName = $arrHtml[1];
		require_once APPPATH."third_party/dompdf/dompdf_config.inc.php";
		spl_autoload_register('DOMPDF_autoload');
		$dompdf = new DOMPDF();
		$dompdf->load_html($html);
		$dompdf->render();
		$pdf = $dompdf->output();
		$path = APPPATH. "documents/kols_pdf/" . $fileName . ".pdf";
		file_put_contents($path, $pdf);
		return $path;
	}
	function update_notes($noteId,$orginal_doc='',$kolId){
		ini_set('memory_limit',"-1");
		ini_set("max_execution_time",0);
		$deskNote = trim($this->input->post('user_note'));
		if($deskNote==''){
			$arr['note'] = trim($this->input->post('note'));
		}else{
			$arr['note'] = $deskNote;
		}
		$arr['is_from_opt_in'] = 0;
		$arr['modified_by'] = $this->loggedUserId;
		$arr['modified_on'] = date("Y-m-d H:i:s");
		if(trim($this->input->post('fileName'))!=''){
			$arr['document_name']= trim($this->input->post('fileName'));
		}
		$arr['orginal_doc_name'] = '';
		if($orginal_doc!='undefined'){
			$arr['orginal_doc_name']= trim($orginal_doc);
		}
		$arr['id'] = $noteId;
		if($_FILES["note_file"]['name']!=''){
			$target_path = APPPATH."/documents/kol_note_documents/";
			$path_info = pathinfo($_FILES["note_file"]['name']);
			$newFileName	= random_string('unique', 20).".".$path_info['extension'];
			$overview_file_target_path = $target_path ."/". $newFileName;
			if(move_uploaded_file($_FILES['note_file']['tmp_name'],$overview_file_target_path)){
				$arr['document'] = $newFileName;
				$fname = explode('.', $_FILES["note_file"]['name']);
				$arr['orginal_doc_name']= $_FILES["note_file"]['name'];
				$arr['document_name']= $fname[0];
				if($this->input->post('fileName')){
					$arr['document_name']= trim($this->input->post('fileName'));
				}
			}
		}
		$arrUpdateData  = $arr;
		if ($this->common_helper->updateEntity('kol_notes',$arrUpdateData,array('id'=>$arrUpdateData['id']))) {
			if($this->session->userdata['client_id']!=INTERNAL_CLIENT_ID)
				$arr['name'] = $this->session->userdata('user_full_name');
			else
				$arr['name'] = 'Aissel Analyst';
				$currentDateTime = $arr['modified_on'];
				$arr['created_on'] = date('d M Y, h:i A', strtotime($currentDateTime));
				$arr['created_on'] = str_replace(',', " at ", $arr['created_on']);
				$arr['note'] = nl2br($arr['note']);
				echo json_encode($arr);
		}
	}
	function get_kol_names_for_all_autocomplete($restrictByRegion=0,$restrictOptInVisbility=0) {
		$kolName = urldecode($this->input->post('keyword'));
		$kolName = $this->db->escape_like_str($kolName);
		$arr['query'] = $kolName;
		$arr['suggestions'] = '';
		$arrKolNames1 = array();
		$arrKolNames = $this->kol->getAllKolNamesForAllAutocomplete($kolName,$restrictByRegion,$restrictOptInVisbility);
		$flag = 1;
		foreach ($arrKolNames['kols'] as $key => $row) {
			$cityState=$row[2];
			if(isset($row[2]) && isset($row[3]))
				$cityState.= ', ';
				$cityState.=$row[3];
				//$unique_id=$row[4];
				$unique_id=$key;
				$donotcall = '<span style="color: red">' . $row['do_not_call_flag'] . '</span>';
				if ($flag) {
					$arrKolNames1[] = '<div class="autocompleteHeading">Contacts</div><div class="dataSet"><label name="' . $row[0] . '" class="kolName" style="display:block">' . $row[0] . "</label><p class='orgName'>" . $row[1] . " " .$cityState. "</p>" . $donotcall . "<span style='display:none' class='id1'>" . $unique_id . "</span></div>";
					$flag = 0;
				} else {
					$arrKolNames1[] = '<div class="dataSet"><label name="' . $row[0] . '" class="kolName" style="display:block">' . $row[0] . "</label><p class='orgName'>" . $row[1] . " " . $cityState. "</p>" . $donotcall . "<span style='display:none' class='id1'>" . $unique_id . "</span></div>";
				}
		}
		$flag = 0;
		foreach ($arrKolNames['customers'] as $key => $row) {
			$cityState=$row[2];
			
			if(isset($row[2]) && isset($row[3]))
				$cityState.= ', ';
				$cityState.=$row[3];
				//$unique_id=$row[4];
				$unique_id=$key;
				$donotcall = '<span style="color: red">' . $row['do_not_call_flag'] . '</span>';
				if (!$flag && ($this->loggedUserId>0)) {
					$arrKolNames1[] = "<div class='autocompleteHeading'>Contacts</div><div class='dataSet'><label name='" . $row[0] . "' class='kolName' style='display:block'>$row[0]</label><p class='orgName'>$row[1] $cityState</p>" . $donotcall . "<span style='display:none' class='id1'>$unique_id</span></div>";
					$flag = 1;
				} else {
					if($this->loggedUserId>0)
						$arrKolNames1[] = "<div class='dataSet'><label name='" . $row[0] . "' class='kolName' style='display:block'>$row[0]</label><p class='orgName'>$row[1] $cityState</p>" . $donotcall . "<span style='display:none' class='id1'>$unique_id</span></div>";
				}
		}
		if ((sizeof($arrKolNames['kols']) < 1) && (sizeof($arrKolNames['customers']) < 1) && ($this->loggedUserId>0)) {
			$arrKolNames1[] = "<div style='padding-left:5px;'>No results found for " . str_replace(')', '', $kolName) . "</div><div><label name='No results found for " . $kolName . "' class='kolName' style='display:block'></label><label class='orgName'></label><span style='display:none' class='id1'></span></div>";
		}
		
		if(!$this->loggedUserId>0){
			$arrKolNames1[] = "<div style='padding-left:5px;padding-top: 5px;padding-bottom: 5px;'>Session is expired, please <a href='".base_url()."'>click here</a> to login</div>";
		}
		$arr['suggestions'] = $arrKolNames1;
		echo json_encode($arr);
	}
	function try_email()
	{
		$config['protocol'] = PROTOCOL;
		$config['smtp_host'] = HOST;
		$config['smtp_port'] = PORT;
		$config['smtp_user'] = USER;
		$config['smtp_pass'] = PASS;
		$config['mailtype'] = 'html';
	//	$config['charset'] = 'iso-8859-1';
	//	$config['newline'] = "\r\n";
		//$config['wordwrap'] = TRUE;
		//$this->load->library('email', $config);
		$this->email->set_crlf("\r\n");
 		$this->email->initialize($config); 
		$this->email->from('soumyashet95@gmail.com', 'Soumya R S');
		$this->email->to('soumyashet95@gmail.com');
		$this->email->subject('Email Test');
		$this->email->message('Testing the email class.');
		$this->email->send();
		show_error($this->email->print_debugger(array('headers')));
		show_error($this->email->print_debugger());
	}
// 	function update_kol_client_visibility_unique_id(){
// 		$get = $this->db->get("kols_client_visibility1");
// 		foreach ($get->result_array() as $row){
// 			$this->db->update("kols_client_visibility1",array("unique_id"=>md5($row['id'])),array("id"=>$row['id']));
// 		}
// 	}
	function get_kol_short_profile_details($kol_id=null){
		$arrData=array();
		if(!(is_numeric($kol_id))){
			$kol_id= $this->common_helper->getFieldValueByEntityDetails('kols','unique_id',$kol_id,'id');
		}
		$arrKolDetails=$this->kol->getKolShortDetails($kol_id);
		
		//Profile name
		$arrSalutations = array(0 => '', 'Dr.', 'Prof.', 'Mr.', 'Ms.');
		$arrData['name']=$arrSalutations[$arrKolDetails['salutation']]." ".nf($arrKolDetails['first_name'],$arrKolDetails['middle_name'],$arrKolDetails['last_name']);
		
		//profile type
		$arrData['profile_type']=$arrKolDetails['profile_type'];
		
		//get profile score
		$this->load->module('profile_ratings');
		$arrProfileScoreData=$this->profile_ratings->show_profile_score_chart($kol_id,$arrKolDetails['specialty']);
		$arrData['profile_ratings']=($arrProfileScoreData['Profile score']==null)?'0%':$arrProfileScoreData['Profile score'];
		
		//image path
		if($arrKolDetails['profile_image']==''){
			if($arrKolDetails['profile_type']=='Male'){
				$image_url='/assets/modules/kols/images/male_kol_profile.svg';
			}elseif($arrKolDetails['profile_type']=='Female'){
				$image_url='/assets/modules/kols/images/female_kol_profile.svg';
			}else{
				$image_url='/assets/modules/kols/images/user_doctor.jpg';
			}
		}else{
			$image_url="/images/kol_images/resized/".$arrKolDetails['profile_image'];
		}
		
		$arrData['profile_image_name']=$image_url;
		echo json_encode($arrData);
	}
	function delete_created_opt_ol(){
		$kolId = $this->input->post('kolId');
		if(!(is_numeric($kolId))){
			$kolId= $this->common_helper->getFieldValueByEntityDetails('kols','unique_id',$kolId,'id');
		}
		$deleteBy = $this->input->post('user_id');
		$ticketNo = $this->input->post('ticket_no');
		$comments = $this->input->post('comment');

		$this->kol->deleteLogActivity($kolId);
		
		$this->kol->deleteStaffByKolId($kolId);
		//delete kol locations
		$this->common_helper->deleteEntityByWhereCondition('kol_locations',array('kol_id'=>$kolId));
		//delete user_notes of kol 
		$this->common_helper->deleteEntityByWhereCondition('kol_notes',array('kol_id'=>$kolId));
		
		$this->kol->deletePhoneByKolId($kolId);
		//delete kol sub_specialities
		$this->common_helper->deleteEntityByWhereCondition('kol_sub_specialty',array('kol_id'=>$kolId));
		//delete kol emails
		$this->common_helper->deleteEntityByWhereCondition('emails',array('contact'=>$kolId));
		//delete kol state_licenses
		$this->common_helper->deleteEntityByWhereCondition('state_licenses',array('contact'=>$kolId));
		//delete kol clinical trials
		$this->common_helper->deleteEntityByWhereCondition('kol_clinical_trials',array('kol_id'=>$kolId));
		//         $this->kol->deletePaymentsByKolId($kolId);
		//         $this->kol->deleteContractsByKolId($kolId);
		
		//delete kol_publications
		$this->common_helper->deleteEntityByWhereCondition('kol_publications',array('kol_id'=>$kolId));
		
		$this->kol->deleteKol($kolId);
		
		$arrKolUpdate = array();
		$arrKolUpdate['id'] = $kolId;
		$arrKolUpdate['delete_by'] = $deleteBy;
		$arrKolUpdate['delete_ticket_no'] = $ticketNo;
		$arrKolUpdate['delete_comment'] = $comments;
		$this->kol->updateDeleteComment($arrKolUpdate);
		
		$arrKolCommentDetails = array();
		$arrKolCommentDetails['kol_id'] = $kolId;
		$arrKolCommentDetails['kol_status_id'] = 1;
		$arrKolCommentDetails['kol_action_by'] = $deleteBy;
		$arrKolCommentDetails['kol_action_on'] = date("Y-m-d H:i:s");;
		$arrKolCommentDetails['kol_action_ticket_no'] = $ticketNo;
		$arrKolCommentDetails['kol_action_comment'] = $comments;
// 		$this->kol->insertKolStatusDetails($arrKolCommentDetails);
		
		$arrStatus['deleted'] = true;
		echo json_encode($arrStatus);
	}
	
	/*
	 * Analyst Application related functionalities goes from here
	 * @author Sanjeev K
	 * @since 14 March 2018
	 * @version KOLM-HMVC Version 1.0
	 */
	function list_kols(){
		ini_set('memory_limit', '-1');
	
		$this->common_helper->checkUsers();
	
		$analyst_client = $this->session->userdata('analyst_client');
		if(!isset($analyst_client)){
			redirect('clients/analysis_client');
		}
	
		$data['user_id'] = $this->session->userdata('user_id');
		$arrSalutations = array(0 => '', 'Dr.', 'Prof.', 'Mr.', 'Ms.');
		$data['arrSalutations'] = $arrSalutations;
		// Get the list of Specialties
		//$this->load->model('Specialty');
		//$arrSpecialties = $this->Specialty->getAllSpecialties();
		//$data['arrSpecialties'] = $arrSpecialties;
		//$data['clientUser'] = $this->Client_User->getUserDetail($this->session->userdata('user_id'));
		//pr($data['clientUser']);
		$data['contentPage'] = 'list_kols';
		$data['showNavBar']=false;
		$this->load->view(ANALYST_HEADER,$data);
	}
	
	/*
	 * Lists all processed kols for analyst application, it's for ajax pagination,search and sort version of jqgrid
	 * @author Ramesh B
	 * @since 22 Feb 2013
	 * @version otsuka1.0.11
	 * @return JSON
	 */
	
	function list_kols_grid() {
		ini_set('memory_limit', '-1');
		$page = $_REQUEST['page']; // get the requested page
		$limit = $_REQUEST['rows']; // get how many rows we want
		$sidx = $_REQUEST['sidx']; // get index row - i.e. user click to sort
		$sord = $_REQUEST['sord']; // get the direction
		if (!$sidx)
			$sidx = 1;
	
			//if ($page > $total_pages) $page=$total_pages;
			//$start = $limit*$page - $limit; // do not put $limit*($page - 1)
			//if ($start < 0) $start = 0;
			$filterData = $_REQUEST['filters'];
			$arrFilter = array();
			$arrFilter = json_decode(stripslashes($filterData));
			$field = 'field';
			$op = 'op';
			$data = 'data';
			$groupOp = 'groupOp';
			$searchGroupOperator = $this->common_helper->search_nested_arrays($arrFilter, $groupOp);
			$searchString = $this->common_helper->search_nested_arrays($arrFilter, $data);
			$searchOper = $this->common_helper->search_nested_arrays($arrFilter, $op);
			$searchField = $this->common_helper->search_nested_arrays($arrFilter, $field);
			$whereResultArray = array();
			foreach ($searchField as $key => $val) {
				$whereResultArray[$val] = $searchString[$key];
			}
			$searchGroupOperator = $searchGroupOperator[0];
			$searchResults = array();
	
			$count = $this->kol->getProcessedKols($limit, $start, true, $sidx, $sord, $whereResultArray);
			if ($count > 0) {
				$total_pages = ceil($count / $limit);
			} else {
				$total_pages = 0;
			}
			if ($page > $total_pages)
				$page = $total_pages;
				$start = $limit * $page - $limit; // do not put $limit*($page - 1)
				if ($start < 0)
					$start = 0;
	
					$arrKolDetailResult = array();
					$data = array();
					$arrKolDetail = array();
					//		pr($sidx);
					//		pr($whereResultArray);
					if ($arrKolDetailResult = $this->kol->getProcessedKols($limit, $start, false, $sidx, $sord, $whereResultArray)) {
						//echo $this->db->last_query();
						foreach ($arrKolDetailResult->result_array() as $row) {
							$arrSalutations = array(0 => '', 'Dr.', 'Prof.', 'Mr.', 'Ms.');
							$kolName = $arrSalutations[$row['salutation']] . ' ' . $row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'];
							$row['kol_name'] = $kolName;
							$row['id'] = $row['id'];
							$row['is_imported'] = $row['is_imported'];
							//				$row['kol_name']	.= (isset($row['is_imported']) && $row['is_imported']==1) ? '<span class="highlightImported"> (xls)<span>':'';
							$row['created_by'] = $row['user_full_name'];
							if ($this->session->userdata('user_role_id') == ROLE_MANAGER || $this->session->userdata('user_role_id') == ROLE_ADMIN) {
								if ($row['is_pubmed_processed'] == 0)
									$pubStatusHtml = "No";
	
									if ($row['is_pubmed_processed'] == 1)
										$pubStatusHtml = "Yes";
	
										if ($row['is_pubmed_processed'] == 2)
											$pubStatusHtml = "Re crawl";
	
											$row['pubmed_processed'] = $pubStatusHtml;
							}else {
								$pubStatus = 'No';
								if ($row['is_pubmed_processed'] == 1)
									$pubStatus = 'Yes';
									if ($row['is_pubmed_processed'] == 2)
										$pubStatus = 'Re crawl';
										$row['pubmed_processed'] = $pubStatus;
							}
							$row['trial_processed'] = ($row['is_clinical_trial_processed'] == 1) ? 'Yes' : 'No';
							$row['organization'] = $row['org_name'];
							$row['action'] = '<div class="actionIcon editIcon"><a href="' . base_url() . 'kols/edit_kol/' . $row['vid'] . '" title="Edit">&nbsp;</a></div><div class="actionIcon deleteIcon"><a onclick="deleteSelectedKols(' . $row['vid'] . ');" href="#" title="delete">&nbsp;</a></div>';
							$arrKolDetail[] = $row;
						}
	
						$data['records'] = $count;
						$data['total'] = $total_pages;
						$data['page'] = $page;
						$data['rows'] = $arrKolDetail;
					}
					ob_start('ob_gzhandler');
					echo json_encode($data);
	}
	
	/*
	 *  Add 'kols' detail
	 */
	
	function analyst_add_kol(){
		//Analyst App to be accessed by only Aissel users.
		$this->common_helper->checkUsers();
		$data['arrCountry'] = $this->country_helper->listCountries();
		// Get the list of Specialties
		$arrSpecialties = $this->speciality->getAllSpecialties();
		$data['arrSpecialties'] = $arrSpecialties;
		$arrSalutations = array(1 => 'Dr.', 2 => 'Prof.', 3 => 'Mr.', 4 => 'Ms.');
		$data['arrSalutations'] = $arrSalutations;
		$data['contentPage'] = 'analyst_add_kol';
		$data['showNavBar']=false;
		$this->load->view(ANALYST_HEADER,$data);
	}
	
	/*
	 * Saving a 'kols' data
	 */
	
	function save_kol() {
		// Get all the POST data
		$arrKol['salutation'] = $this->input->post('salutation');
		$arrKol['gender'] = $this->input->post('gender');
		$arrKol['first_name'] = ucwords(trim($this->input->post('first_name')));
		$arrKol['middle_name'] = ucwords(trim($this->input->post('middle_name')));
		$arrKol['last_name'] = ucwords(trim($this->input->post('last_name')));
		$arrKol['suffix'] = ucwords(trim($this->input->post('suffix')));
		$arrKol['specialty'] = $this->input->post('specialty');
		$arrKol['org_id'] = $this->input->post('org_id');
		$arrKol['is_pubmed_processed'] = 0;
		$arrKol['created_by'] = $this->loggedUserId;
		$arrKol['created_on'] = date("Y-m-d H:i:s");
		$arrKol['status'] = New1;
		$arrKol['npi_num'] = $this->input->post('npi_num');
		$arrKol['profile_type'] = $this->input->post('profile_type');
		$arrKol['unique_id'] = uniqid();
		//ending post details
		//            check_duplicate_kols
		$kolId = $this->kol->saveKol($arrKol);
		//$this->update->insertUpdateEntry(KOL_PROFILE_ADD, $kolId, MODULE_KOL_OVERVIEW, $kolId);
		if ($kolId) {
			$updateData['id'] = $kolId;
			$updateData['pin'] = $kolId;
			$this->kol->updateKol($updateData);
			$this->session->set_flashdata('message', 'New Kol Saved Sucessfully');
		} else {
			$this->session->set_flashdata('message', 'Kol Could not be Saved! Try again');
		}
	
		redirect('kols/list_kols');
	}
	/*
	 *  Editing 'kols' detail
	 */
	
	function edit_kol($kolId = null) {
		//Analyst App to be accessed by only Aissel users.
		$this->common_helper->checkUsers();
		if (!$kolId) {
			$this->session->set_flashdata('errorMessage', 'Invalid KOL Id');
			redirect('kols/list_kols');
		}
		// Getting the KOL details
		$arrKolDetail = $this->kol->editKol($kolId);
		// If there is no record in the database
		if (!$arrKolDetail) {
			$this->session->set_flashdata('errorMessage', 'Invalid KOL Id');
			redirect('kols/list_kols');
		}
		// Set the KOL ID into the Session
		$this->session->set_userdata('kolId', $kolId);
		$arrKolDetail['org_id'] = $this->kol->getOrgId($arrKolDetail['org_id']);
		$data['arrKol'] = $arrKolDetail;
		$data['kolId'] = $kolId;
		$data['arrCountry'] = $this->country_helper->listCountries();
		$arrStates = array();
		$arrCities = array();
		if ($arrKolDetail['country_id'] != 0) {
			$arrStates = $this->country_helper->getStatesByCountryId($arrKolDetail['country_id']);
		}
		if ($arrKolDetail['state_id'] != 0) {
			$arrCities = $this->country_helper->getCitiesByStateId($arrKolDetail['state_id']);
		}
		$data['arrStates'] = $arrStates;
		$data['arrCities'] = $arrCities;
		// Get the list of Specialties
		$arrSpecialties = $this->speciality->getAllSpecialties();
		$data['arrSpecialties'] = $arrSpecialties;
		$arrSalutations = array(1 => 'Dr.', 2 => 'Prof.', 3 => 'Mr.', 4 => 'Ms.');
		$data['arrSalutations'] = $arrSalutations;
		$data['arrContactDetails'] = array();
		$arrContactDetails = array();
		if ($arrContactDetails = $this->kol->listContacts($kolId)) {
			$data[] = $arrContactDetails;
		}
		$data['latitude'] = $arrKolDetail['latitude'];
		$data['longitude'] = $arrKolDetail['longitude'];
		$data['contentPage'] = 'edit_kol';
		$data['showNavBar']=false;
		$this->load->view(ANALYST_HEADER,$data);
	}
	
	/*
	 *  Editing 'kols' detail
	 */
	
	function details_kol($kolId = null) {
		//Analyst App to be accessed by only Aissel users.
		$this->common_helper->checkUsers();
		if (!$kolId) {
			$this->session->set_flashdata('errorMessage', 'Invalid KOL Id');
			redirect('kols/list_kols');
		}
		// Getting the KOL details
		$arrKolDetail = $this->kol->editKol($kolId);
		// If there is no record in the database
		if (!$arrKolDetail) {
			$this->session->set_flashdata('errorMessage', 'Invalid KOL Id');
			redirect('kols/list_kols');
		}
		// Set the KOL ID into the Session
		$this->session->set_userdata('kolId', $kolId);
		$arrKolDetail['org_id'] = $this->kol->getOrgId($arrKolDetail['org_id']);
		$data['arrKol'] = $arrKolDetail;
		$data['kolId'] = $kolId;
		$data['arrCountry'] = $this->country_helper->listCountries();
		$arrStates = array();
		$arrCities = array();
		if ($arrKolDetail['country_id'] != 0) {
			$arrStates = $this->country_helper->getStatesByCountryId($arrKolDetail['country_id']);
		}
		if ($arrKolDetail['state_id'] != 0) {
			$arrCities = $this->country_helper->getCitiesByStateId($arrKolDetail['state_id']);
		}
		$data['arrStates'] = $arrStates;
		$data['arrCities'] = $arrCities;
		// Get the list of Specialties
		$arrSpecialties = $this->speciality->getAllSpecialties();
		$data['arrSpecialties'] = $arrSpecialties;
		$arrSalutations = array(1 => 'Dr.', 2 => 'Prof.', 3 => 'Mr.', 4 => 'Ms.');
		$data['arrSalutations'] = $arrSalutations;
		$data['arrContactDetails'] = array();
		$arrContactDetails = array();
		if ($arrContactDetails = $this->kol->listContacts($kolId)) {
			$data[] = $arrContactDetails;
		}
		$data['latitude'] = $arrKolDetail['latitude'];
		$data['longitude'] = $arrKolDetail['longitude'];
		$data['contentPage'] = 'details_kol';
		$data['showNavBar']=false;
		$this->load->view(ANALYST_HEADER,$data);
	}
	
	
	/*
	 *  Updating 'kols' details
	 */
	
	function update_kol() {
	
		// Get the current KOL ID from the Session
		$arrKol['id'] = $this->input->post('kol_id');
		;
		// Get all the POST data
		$arrKol['salutation'] = $this->input->post('salutation');
		$arrKol['first_name'] = ucwords(trim($this->input->post('first_name')));
		$arrKol['middle_name'] = ucwords(trim($this->input->post('middle_name')));
		$arrKol['last_name'] = ucwords(trim($this->input->post('last_name')));
		$arrKol['suffix'] = ucwords(trim($this->input->post('suffix')));
		$arrKol['specialty'] = $this->input->post('specialty');
		$arrKol['sub_specialty'] = ucwords(trim($this->input->post('sub_specialty')));
		$arrKol['gender'] = $this->input->post('gender');
		$arrKol['org_id'] = $this->input->post('org_id');
		$arrKol['title'] = ucwords(trim($this->input->post('title')));
		if(!empty($arrKol['title'])){
			$arrKol['title'] = $this->kol->saveTitle($arrKol['title']); // save title if not exists / if exsits get id
		}
		$arrKol['division'] = ucwords(trim($this->input->post('division')));
		$arrKol['npi_num'] = $this->input->post('npi_num');
		$arrKol['research_interests'] = $this->input->post('research_interests');
		$arrKol['license'] = $this->input->post('license');
		$arrKol['biography'] = $this->input->post('biography');
		//$arrKol['profile_image'] =	$this->input->post('profile_image');
		$arrKol['notes'] = $this->input->post('notes');
		$arrKol['url'] = $this->input->post('url');
		$arrKol['modified_by'] = $this->loggedUserId;
		$arrKol['modified_on'] = date("Y-m-d H:i:s");
		$arrKol['mdm_id'] = $this->input->post('mdm_id');
		if ($arrKol['mdm_id'] == '')
			$arrKol['mdm_id'] = null;
			//- End of getting all the POST data
	
			$arrKol['profile_type'] = $this->input->post('profile_type');
			$arrKol['org_id'] = $this->kol->getOrgName($arrKol['org_id']);
			//print_r($arrKol['org_id']);
			$returnValue = $this->kol->updateKol($arrKol);
			//$this->update->insertUpdateEntry(KOL_PROFILE_OVERVIEW_UPDATE, $arrKol['id'], MODULE_KOL_OVERVIEW, $arrKol['id']);
			echo json_encode($returnValue);
	}
	
	function update_kol_contact() {
	
		// Get the current KOL ID from the Session
		$arrKol['id'] = $this->input->post('kol_id');
		;
	
		// Get all the POST data
		$arrKol['primary_phone'] = $this->input->post('primary_phone');
		$arrKol['primary_email'] = $this->input->post('primary_email');
		$arrKol['address1'] = ucwords(trim($this->input->post('address1')));
		$arrKol['address2'] = ucwords(trim($this->input->post('address2')));
		$arrKol['country_id'] = $this->input->post('country_id');
		$arrKol['city_id'] = $this->input->post('city_id');
		$arrKol['state_id'] = $this->input->post('state_id');
		$arrKol['postal_code'] = $this->input->post('postal_code');
		$arrKol['fax'] = $this->input->post('fax');
		$arrKol['modified_by'] = $this->loggedUserId;
		$arrKol['modified_on'] = date("Y-m-d H:i:s");
		//- End of getting all the POST data
		//prepare array to update KOL latitude and longitude
		$arrKolLatLongData['kol_id'] = $arrKol['id'];
		$arrKolLatLongData['latitude'] = $this->input->post('latitude');
		$arrKolLatLongData['longitude'] = $this->input->post('longitude');
		//update KOL latitude and longitude
		$this->kol->updateKolLocationLatitudeLongitude($arrKolLatLongData);
		//update other info
		$returnValue = $this->kol->updateKol($arrKol);
		//$this->update->insertUpdateEntry(KOL_PROFILE_OVERVIEW_UPDATE, $arrKol['id'], MODULE_KOL_OVERVIEW, $arrKol['id']);
		echo json_encode($returnValue);
	}
	
	function update_kol_biography() {
	
		// Get the current KOL ID from the Session
		$arrKol['id'] = $this->input->post('kol_id');
	
		// Get all the POST data
		$arrKol['research_interests'] = $this->input->post('research_interests');
		$arrKol['biography'] = $this->input->post('biography');
		$arrKol['modified_by'] = $this->loggedUserId;
		$arrKol['modified_on'] = date("Y-m-d H:i:s");
		//- End of getting all the POST data
		//print_r($arrKol['org_id']);
	
		$returnValue = $this->kol->updateKol($arrKol);
		$this->update->insertUpdateEntry(KOL_PROFILE_OVERVIEW_UPDATE, $arrKol['id'], MODULE_KOL_OVERVIEW, $arrKol['id']);
		echo json_encode($returnValue);
	}
	
	/**
	 * @author 	Sanjeev K
	 * @since	-.-
	 * @created 13 April 2018
	 *
	 * Deletes the entire KOL Related formation. For the passed KOL's id
	 * @param Array $kolsId
	 * @functions show_kol_delete_opts, kol_delete_opts, delete_kol_data.
	 * @return -
	 */
	
	function show_kol_delete_opts($kols,$is_from_client_visibility,$clientIdFromKolVisibility) {
		$data['is_from_client_visibility'] = $is_from_client_visibility;
		$data['client_id_from_kol_visibility'] = $clientIdFromKolVisibility;
		$data['arrKols'] = $kols;
		$this->load->view('delete/kol_delete_opts', $data);
	}
	
	function kol_delete_opts(){
		//error_reporting(E_ALL);
		//is_from_client_visibility
		$isFromClientVisibility = $this->input->post('is_from_client_visibility');
		$clientIdFromKolVisibility = $this->input->post('client_id_from_kol_visibility');
		$deleteOpts = $this->input->post('delete_opts');
		$kols = $this->input->post('kol_id');
		$arrKolIds	= array_filter(explode(",",$kols));
		$arrayConditions = array();
		//$arrKolIds	= implode(",",array_filter($arrKolIds));
		foreach($deleteOpts as $row){
			switch ($row) {
				case 'contact':
					/* Query goes here */
					$arrayConditions["kol_id"] = $arrKolIds;
					$this->delete_kol_data("kol_locations",$arrayConditions);
	
					$arrayConditions = '';
					$arrayConditions["contact"] = $arrKolIds;
					$this->delete_kol_data("emails",$arrayConditions);
					$this->delete_kol_data("state_licenses",$arrayConditions);
	
					$arrayConditions["contact_type"] = "kol";
					$this->delete_kol_data("staffs",$arrayConditions);
					$this->delete_kol_data("phone_numbers",$arrayConditions);
					break;
				case 'events':
					/* Query goes here */
					$arrayConditions = array();
					$arrayConditions["kol_id"] = $arrKolIds;
					$this->delete_kol_data("kol_events",$arrayConditions);
					break;
				case 'trails':
					/* Query goes here */
					$arrayConditions["kol_id"] = $arrKolIds;
					$this->delete_kol_data("kol_clinical_trials",$arrayConditions);
					break;
				case 'publications':
					/* Query goes here */
					$arrayConditions["kol_id"] = $arrKolIds;
					$this->delete_kol_data("kol_publications",$arrayConditions);
					break;
					//check once again
				case 'dasboard':
					/* Query goes here */
					foreach($arrKolIds as $value){
						$newKolIds[] = 'kol_id:'.$value;
					}
					$arrayConditions["kol_id"] = $newKolIds;
					$this->delete_kol_data("json_store",$arrayConditions);
					break;
				case 'profile':
					/* Query goes here */
					break;
				case 'assessments':
					/* Query goes here */
					$arrayConditions = array();
					$arrayConditions["kol_id"] = $arrKolIds;
					$this->delete_kol_data("asmt_kols_rating",$arrayConditions);
					break;
				case 'media':
					/* Query goes here */
					break;
				case 'interactions':
					/* Query goes here */
					/*deleteInteractionAttendis */
					$arrayConditions = array();
					$arrayConditions["kol_id"] = $arrKolIds;
					$this->delete_kol_data("interactions_attendees",$arrayConditions);
					break;
				case 'payments':
					/* Query goes here */
					$arrayConditions = array();
					$arrayConditions["kol_id"] = $arrKolIds;
					$this->delete_kol_data("payments",$arrayConditions);
					break;
				case 'planning':
					/* Query goes here */
					$arrayConditions = array();
					$arrayConditions["kol_id"] = $arrKolIds;
					$this->delete_kol_data("plan_profiles", $arrayConditions);
					break;
				case 'contracts':
					/* Query goes here */
					$arrayConditions = array();
					$arrayConditions["kol_id"] = $arrKolIds;
					$this->delete_kol_data("contracts", $arrayConditions);
					break;
				case 'lists':
					/* Query goes here */
					$arrayConditions = array();
					$arrayConditions["kol_id"] = $arrKolIds;
					$this->delete_kol_data("list_kols",$arrayConditions);
					break;
				case 'surveys':
					/* Query goes here */
					break;
				case 'identify':
					/* Query goes here */
					//project_kols
					break;
				case 'education':
					$CheckedSubEduType = $_POST['edu_delete_opts'];
					$sizeOfCheckedSubEduType = sizeof($CheckedSubEduType);
					if($sizeOfCheckedSubEduType>0){
						foreach($CheckedSubEduType as $rowEduType){
							switch ($rowEduType) {
								case 'edu_education':
									/* Query goes here */
									$arrayConditions = array();
									$arrayConditions["kol_id"] = $arrKolIds;
									$arrayConditions["type"] = "education";
									$this->delete_kol_data("kol_educations",$arrayConditions);
									break;
								case 'edu_training':
									/* Query goes here */
									$arrayConditions = array();
									$arrayConditions["kol_id"] = $arrKolIds;
									$arrayConditions["type"] = "training";
									$this->delete_kol_data("kol_educations",$arrayConditions);
									break;
								case 'edu_certifications':
									/* Query goes here */
									$arrayConditions = array();
									$arrayConditions["kol_id"] = $arrKolIds;
									$arrayConditions["type"] = "board_certification";
									$this->delete_kol_data("kol_educations",$arrayConditions);
									break;
								case 'edu_awards':
									/* Query goes here */
									$arrayConditions = array();
									$arrayConditions["kol_id"] = $arrKolIds;
									$arrayConditions["type"] = "honors_awards";
									$this->delete_kol_data("kol_educations",$arrayConditions);
									break;
								default:
									break;
							}
						}
					}
					break;
				case 'affiliation':
					$CheckedSubAffType = $_POST['aff_delete_opts'];
					$sizeOfCheckedSubAffType = sizeof($CheckedSubAffType);
					if($sizeOfCheckedSubAffType>0){
						foreach($CheckedSubAffType as $rowAffType){
							switch ($rowAffType) {
								case 'aff_association':
									/* Query goes here */
									//     								kol_memberships and type
									$arrayConditions = array();
									$arrayConditions["kol_id"] = $arrKolIds;
									$arrayConditions["type"] = "association";
									$this->delete_kol_data("kol_memberships",$arrayConditions);
									break;
								case 'aff_government':
									/* Query goes here */
									$arrayConditions = array();
									$arrayConditions["kol_id"] = $arrKolIds;
									$arrayConditions["type"] = "government";
									$this->delete_kol_data("kol_memberships",$arrayConditions);
									break;
								case 'aff_industry':
									/* Query goes here */
									$arrayConditions = array();
									$arrayConditions["kol_id"] = $arrKolIds;
									$arrayConditions["type"] = "industry";
									$this->delete_kol_data("kol_memberships",$arrayConditions);
									break;
								case 'aff_university':
									/* Query goes here */
									$arrayConditions = array();
									$arrayConditions["kol_id"] = $arrKolIds;
									$arrayConditions["type"] = "university";
									$this->delete_kol_data("kol_memberships",$arrayConditions);
									break;
								case 'aff_others':
									/* Query goes here */
									$arrayConditions = array();
									$arrayConditions["kol_id"] = $arrKolIds;
									$arrayConditions["type"] = "others";
									$this->delete_kol_data("kol_memberships",$arrayConditions);
									break;
								default:
									break;
							}
						}
					}
					break;
				default:
					break;
			}
		}
		//check for condition
		if(isset($_POST['full_profile'])&&($_POST['full_profile']=='full')){
			/* Kol table delette script */
			foreach($arrKolIds as $kolId){
				$arrKolDetail = $this->kol->editKol($kolId);
				if ($arrKolDetail['profile_image'] != '') {
					//Delete kol Profile images
					if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/" . $this->config->item('app_folder_path') . "images/kol_images/medium/" . $arrKolDetail['profile_image'])) {
						unlink($_SERVER['DOCUMENT_ROOT'] . "/" . $this->config->item('app_folder_path') . "images/kol_images/medium/" . $arrKolDetail['profile_image']);
					}
					if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/" . $this->config->item('app_folder_path') . "images/kol_images/original/" . $arrKolDetail['profile_image'])) {
						unlink($_SERVER['DOCUMENT_ROOT'] . "/" . $this->config->item('app_folder_path') . "images/kol_images/original/" . $arrKolDetail['profile_image']);
					}
					if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/" . $this->config->item('app_folder_path') . "images/kol_images/resized/" . $arrKolDetail['profile_image'])) {
						unlink($_SERVER['DOCUMENT_ROOT'] . "/" . $this->config->item('app_folder_path') . "images/kol_images/resized/" . $arrKolDetail['profile_image']);
					}
				}
			}
			$arrayConditions = array();
			$arrayConditions["id"] = $arrKolIds;
			$this->delete_kol_data("kols",$arrayConditions);
		}
		if($isFromClientVisibility == '1'){
			redirect('kols/list_kols_based_on_client/'.$clientIdFromKolVisibility);
		}else{
			redirect('kols/list_kols');
		}
	
	}
	
	function delete_kol_data($tableName,$arrayCondition){
		$this->kol->delete_kol_data_all($tableName,$arrayCondition);
	}
	
	/**
	 * Show the page of Add Contact details
	 *
	 */
	function add_contact() {
		// Get the existing Contact details
		$data['arrContactDetails'] = array();
		$arrContactDetails = array();
		if ($arrContactDetails = $this->kol->listContacts($this->session->userdata('kolId'))) {
			$data[] = $arrContactDetails;
		}
		$arrKolDetail = $this->kol->editKol($this->session->userdata('kolId'));
		$data['arrKol'] = $arrKolDetail;
		$arrSalutations = array(0 => '', 'Dr.', 'Prof.', 'Mr.', 'Ms.');
		$data['arrSalutations'] = $arrSalutations;
		$this->load->view('contacts/add_contact', $data);
	}
	
	/**
	 * Saves the Contact Details to DB
	 *
	 */
	function save_contact() {
		if (isset($_POST) && count($_POST) > 0) {
			// Getting the POST details of Contact
			$contactDetails = array('related_to' => $this->input->post('related_to'),
					'phone' => $this->input->post('phone'),
					'email' => $this->input->post('email'),
					'created_by' => $this->loggedUserId,
					'created_on' => date("Y-m-d H:i:s"),
					'kol_id' => $this->input->post('kol_id'));
	
	
			// Create an array to return the result
			$arrResult = array();
			if ($lastInsertId = $this->kol->saveContact($contactDetails)) {
				//$this->update->insertUpdateEntry(KOL_PROFILE_EDUCATION_ADD, $lastInsertId, MODULE_KOL_EDUCATION, $contactDetails['kol_id']);
				$arrResult['saved'] = true;
				$arrResult['lastInsertId'] = $lastInsertId;
				$arrResult['data'] = $contactDetails;
			} else {
				$arrResult['saved'] = false;
			}
			echo json_encode($arrResult);
		}
	}
	
	/**
	 * List the Contacts Data
	 *
	 */
	function list_contacts() {
		$page = (int) $this->input->post('page'); // get the requested page
		$limit = (int) $this->input->post('rows'); // get how many rows we want to have into the grid
		$arrContactDetails = array();
		$data = array();
		if ($arrContactDetails = $this->kol->listContacts($this->session->userdata('kolId'))) {
			$count = sizeof($arrContactDetails);
			if ($count > 0) {
				$total_pages = ceil($count / $limit);
			} else {
				$total_pages = 0;
			}
			$data['records'] = $count;
			$data['total'] = $total_pages;
			$data['page'] = $page;
			$data['rows'] = $arrContactDetails;
		}
		echo json_encode($data);
	}
	/**
	 * Edit the Contact Detail
	 *
	 */
	function edit_contact($id) {
		if ($arrContactDetails = $this->kol->editContactById($id)) {
			foreach ($arrContactDetails->result_array() as $row) {
				$data['arrContactDetails'][] = $row;
			}
		}
		$this->load->view('contacts/edit_contact', $data);
	}
	
	/**
	 * Updates the Contact detail to DB
	 *
	 */
	function update_contact() {
		if (isset($_POST) && count($_POST) > 0) {
			// Getting the POST details of Contact
			$contactDetails = array('id' => $this->input->post('id'),
					'related_to' => $this->input->post('related_to'),
					'phone' => $this->input->post('phone'),
					'email' => $this->input->post('email'),
					'modified_by' => $this->loggedUserId,
					'modified_on' => date("Y-m-d H:i:s"));
			// Create an array to return the result
			$arrResult = array();
			if ($this->kol->updateContact($contactDetails)) {
				$arrResult['saved'] = true;
				$arrResult['lastInsertId'] = $contactDetails['id'];
				$arrResult['data'] = $contactDetails;
			} else {
				$arrResult['saved'] = false;
			}
			echo json_encode($arrResult);
		}
	}
	
	/**
	 * Delete the Contact detail From DB
	 *
	 */
	function delete_contact($id) {
		if ($this->kol->deleteContactById($id)) {
			echo 'success';
		} else {
			echo 'failed to delete';
		}
	}
	/*-------------Start of Education & Training functions-------------*/
	/**
	 * Show the page of Add Education details
	 * @author Sanjeev K
	 * @since 23 March 2018
	 * @version KOLM-HMVC Version 1.0
	 */
	function add_education_detail($kolId = null) {
		//Analyst App to be accessed by only Aissel users.
		$this->common_helper->checkUsers();
		// Get the existing education details
		$data['arrEducationDetails'] = array();
		// Get the KOL details
		$arrKolDetail = $this->kol->editKol($kolId);
		$data['arrKol'] = $arrKolDetail;
		$arrSalutations = array(0 => '', 'Dr.', 'Prof.', 'Mr.', 'Ms.');
		$data['arrSalutations'] = $arrSalutations;
		//Returns all the InstituteNames from the lookuptable
		$arrAllInstituteNames = $this->kol->getAllInstituteNames();
		$data['arrAllInstituteNames'] = $arrAllInstituteNames;
		//$this->load->view('education_training/add_education_detail', $data);
		$data['contentPage'] = 'education_training/add_education_detail';
		$data['showNavBar']=false;
		$this->load->view(ANALYST_HEADER,$data);
	}
	/**
	 * List the Education Details data
	 *
	 */
	function list_education_details($type, $kolId) {
		$page = (int) $this->input->post('page'); // get the requested page
		$limit = (int) $this->input->post('rows'); // get how many rows we want to have into the grid
		$arrEducationResults = array();
		$arrEducationDetails = array();
		$data = array();
		if ($arrEducationResults = $this->kol->listEducationDetails($type, $kolId)) {
			$count = sizeof($arrEducationResults);
			if ($count > 0) {
				$total_pages = ceil($count / $limit);
			} else {
				$total_pages = 0;
			}
			$data['records'] = $count;
			$data['total'] = $total_pages;
			$data['page'] = $page;
			$data['rows'] = $arrEducationResults;
		}
		echo json_encode($data);
	}
	/**
	 * Returns the Institute Id
	 *
	 * @param String $name
	 * @return int (Integer id)
	 */
	function get_institute_id($name) {
		$instituteId = $this->kol->getInstituteId($name);
		echo json_encode($instituteId);
	}
	/**
	 * Show the page of Add Institution details
	 *
	 */
	function add_institution($name) {
		$eventName = str_replace('%20', ' ', $name);
		$data['name'] = $eventName;
		$this->load->view('education_training/add_institution', $data);
	}
	/**
	 * Saves the Institution Detail to DB
	 *
	 */
	function save_institution() {
		if (isset($_POST) && count($_POST) > 0) {
			$institutionDetails = array('name' => ucwords(trim($this->input->post('name'))),
					'notes' => $this->input->post('notes'),
					'created_by' => $this->loggedUserId,
					'created_on' => date('Y-m-d H:i:s'));
			$arrResult = array();
			//TODO: Check even for the Unique Institute Name validation, in the later stage
			if ($lastInsertId = $this->kol->saveInstitution($institutionDetails)) {
				$arrResult['saved'] = true;
				$arrResult['lastInsertId'] = $lastInsertId;
				$arrResult['data'] = $institutionDetails;
				$arrResult['msg'] = "Successfully saved the new institute name";
			} else {
				$arrResult['saved'] = false;
				$arrResult['msg'] = "Sorry! Institute Name is  already present in database";
			}
			echo json_encode($arrResult);
		}
	}
	/*-------------End of Education & Training functions-------------*/
	
	/*-------------Start of memberships and affiliations functions-------------*/
	/**
	 * Show the page of Add affiliations details
	 * @author Sanjeev K
	 * @since 26 March 2018
	 * @version KOLM-HMVC Version 1.0
	 */
	function add_membership($kolId = null) {
		//Analyst App to be accessed by only Aissel users.
		$this->common_helper->checkUsers();
		// Get the KOL details
		$arrKolDetail = $this->kol->editKol($kolId);
		$data['arrKol'] = $arrKolDetail;
		// Get the list of EngagementTypes
		//$this->load->Model('Engagement_type');
		$arrEngagementTypes = array();//$this->Engagement_type->getAllEngagementTypes();
		$key = array_search('Other', $arrEngagementTypes);
		unset($arrEngagementTypes[$key]);
		$arrEngagementTypes[$key] = 'Other';
		$data['arrEngagementTypes'] = $arrEngagementTypes;
		// Get the list of InstituteNames
		$arrInstituteNames = $this->kol->getAllInstituteNames();
		$data['arrInstituteNames'] = $arrInstituteNames;
		$arrSalutations = array(0 => '', 'Dr.', 'Prof.', 'Mr.', 'Ms.');
		$data['arrSalutations'] = $arrSalutations;
		//pr($data);
		$data['arrCountry'] = $this->country_helper->listCountries();
		//$this->load->view('memberships_affiliations/add_memberships_affiliations',$data);
		$data['contentPage'] = 'memberships_affiliations/add_memberships_affiliations';
		$data['showNavBar']=false;
		$this->load->view(ANALYST_HEADER,$data);
	}
	/*
	 *  List 'memberships and affiliations details'
	 */
	
	function list_memberships($type, $kolId = null) {
		$page = (int) $this->input->post('page'); // get the requested page
		$limit = (int) $this->input->post('rows'); // get how many rows we want to have into the grid
		$arrMembershipResult = array();
		$data = array();
		if ($arrMembershipResult = $this->kol->listMemberships($type, $kolId)) {
			$count = sizeof($arrMembershipResult);
			if ($count > 0) {
				$total_pages = ceil($count / $limit);
			} else {
				$total_pages = 0;
			}
			$data['records'] = $count;
			$data['total'] = $total_pages;
			$data['page'] = $page;
			$data['rows'] = $arrMembershipResult;
		}
	
		echo json_encode($data);
	}
	/*-------------End of memberships and affiliations functions-------------*/
	
	/*-------------Start of Event functions-------------*/
	/**
	 * Show the page of Add Events details
	 * @author Sanjeev K
	 * @since 27 March 2018
	 * @version KOLM-HMVC Version 1.0
	 */
	function add_event($kolId = null) {
		//Analyst App to be accessed by only Aissel users.
		$this->common_helper->checkUsers();
		$data['arrCountry'] = $this->country_helper->listCountries();
		// Get the KOL details
		$arrKolDetail = $this->kol->editKol($kolId);
		$data['arrKol'] = $arrKolDetail;
		// Get the list of Specialties
		//$this->load->model('Specialty');
		$arrSpecialties = array(); //$this->Specialty->getAllSpecialties();
		$data['arrSpecialties'] = $arrSpecialties;
		$arrSalutations = array(0 => '', 'Dr.', 'Prof.', 'Mr.', 'Ms.');
		$data['arrSalutations'] = $arrSalutations;
		// Get the list of Conference Event Types
		//$this->load->model('Event_helper');
		$arrConfEventTypes = array(); //$this->Event_helper->getAllConferenceEventTypes();
		$data['arrConfEventTypes'] = $arrConfEventTypes;
		// Get the list of Conference Session Types
		$arrConfSessionTypes = array(); //$this->Event_helper->getAllConferenceSessionTypes();
		$key = array_search('Other', $arrConfSessionTypes);
		unset($arrConfSessionTypes[$key]);
		$arrConfSessionTypes[$key] = 'Other';
		$data['arrConfSessionTypes'] = $arrConfSessionTypes;
		// Get the list of Online Event Types
		$arrOnlineEventTypes = array(); //$this->Event_helper->getAllOnlineEventTypes();
		$data['arrOnlineEventTypes'] = $arrOnlineEventTypes;
		//Returns all the EventNames from the lookuptable
		$arrAllEventNames = array(); //$this->kol->getAllEventLookupNames();
		$data['arrAllEventNames'] = $arrAllEventNames;
		// Get the list of Topic belongs to kol specialty
		$arrTopics = $this->kol->getTopicsBySpecialty($arrKolDetail['specialty']);
		$data['arrTopics'] = $arrTopics;
		//$this->load->view('events/add_event',$data);
		$arrRoles = $this->kol->getEventRoles();
		$data['arrRoles'] = $arrRoles;
		$data['arrEventOrganizerTypes'] = array(); //$this->Event_helper->getOrganizerTypes();
	
		$data['arrEventSponsorTypes'] = array(); //$this->Event_helper->getSponsorTypes();
		$data['contentPage'] = 'events/add_event';
		$data['showNavBar']=false;
		$this->load->view(ANALYST_HEADER,$data);
	}
	/*-------------End of Event functions-------------*/
	  
	/*-------------Start of Social Media functions-------------*/
	/**
	 * Show the page of Add Social Media details
	 * @author Sanjeev K
	 * @since 27 March 2018
	 * @version KOLM-HMVC Version 1.0
	 */
	function add_social_media($kolId = null) {
		//Analyst App to be accessed by only Aissel users.
		$this->common_helper->checkUsers();
		// Get the KOL details
		$arrKolDetail = $this->kol->editKol($kolId);
		$data['arrKol'] = $arrKolDetail;
		$arrSalutations = array(0 => '', 'Dr.', 'Prof.', 'Mr.', 'Ms.');
		$data['arrSalutations'] = $arrSalutations;
		$data['contentPage'] = 'media/add_social_media';
		$data['showNavBar']=false;
		$this->load->view(ANALYST_HEADER,$data);
	}
	/**
	 * Saves the Social Media Data to DB
	 *
	 */
	function save_social_media() {
		if (isset($_POST) && count($_POST) > 0) {
	
			// Getting the POST details of Events
			$socialMediaDetails = array('blog' => $this->input->post('blog'),
					'linked_in' => $this->input->post('linked_in'),
					'facebook' => $this->input->post('facebook'),
					'twitter' => $this->input->post('twitter'),
					'myspace' => $this->input->post('myspace'),
					'you_tube' => $this->input->post('you_tube'),
					'id' => $this->input->post('kol_id'));
			$this->kol->saveSocialMedia($socialMediaDetails);
			//$this->update->insertUpdateEntry(KOL_PROFILE_SOCIAL_MEDIA_UPDATE, $socialMediaDetails['id'], MODULE_KOL_OVERVIEW, $socialMediaDetails['id']);
		}
	}
	/*-------------End of Social Media functions-------------*/
	
	/*-------------Start of Kols Visibility functions-------------*/
	/**
	 * Show the page of Add Social Media details
	 * @author Sanjeev K
	 * @since 06 April 2018
	 * @version KOLM-HMVC Version 1.0
	 */
	function list_kols_based_on_client($previouslySelectedClientId) {
		$data['analystSelectedClientId'] = $this->session->userdata('analyst_client');
		$data['contentPage'] = 'kols/list_kols_based_on_client';
		$data['showNavBar']=false;
		$this->load->view(ANALYST_HEADER,$data);
	}
	
	function get_kols_associated_with_client($clientId){
		$page				= (int)$this->input->post('page'); // get the requested page
		$limit				= (int)$this->input->post('rows'); // get how many rows we want to have into the grid
		$data				= array();
		$arrReturnData = array();
		$arrSalutations = array(0 => '', 1 => 'Dr.', 2 => 'Prof.', 3 => 'Mr.', 4 => 'Ms.');
		$arrKolData = $this->kol->getKolsAssociatedWithClient($clientId);
		foreach ($arrKolData as $row) {
			$arrSalutations = array(0 => '', 'Dr.', 'Prof.', 'Mr.', 'Ms.');
			$kolName = $arrSalutations[$row['salutation']] . ' ' . $row['kol_name'];
			$row['kol_link'] = '<a target="_blank" href="' . base_url() . 'kols/view/' . $row['kol_id'] . '">' . $kolName . '</a>';
			$arrReturnData[] = $row;
		}
		$count=sizeof($arrReturnData);
		if( $count >0 ){
			$total_pages = ceil($count/$limit);
		}else{
			$total_pages = 0;
		}
	
		$data['records']=$count;
		$data['total']=$total_pages;
		$data['page']=$page;
		$data['rows']=$arrReturnData;
		echo json_encode($data);
	}
	
	/*-------------End of Kols Visibility functions-------------*/
}