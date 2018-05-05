<?php
class Kol extends CI_Model {
	function getKOLsLists(){        //get list of clients
		$this->db->order_by('id');
		$this->db->select('kols.id,kols.first_name,kols.middle_name,kols.last_name,specialties.specialty as speciality,organizations.name as org_name,cities.City,countries.Country,states.name');
		$this->db->join('specialties','kols.specialty=specialties.id','left');
		$this->db->join('organizations','kols.org_id=organizations.id','left');
		$this->db->join('cities','kols.city_id=cities.CityId','left');
		$this->db->join('countries','kols.country_id=countries.CountryId','left');
		$this->db->join('states','kols.state_id=states.id','left');
		$result=$this->db->get('kols');
		return $result->result_array ();
	}
	function getNotesById($noteId) {
		$this->db->select("kol_notes.id,kol_notes.kol_id,kol_notes.note,kol_notes.created_by,kol_notes.client_id,kol_notes.created_on,kol_notes.document,kol_notes.document_name,kol_notes.modified_by,kol_notes.modified_by,kol_notes.modified_on,CONCAT(client_users.first_name,' ',client_users.last_name) as name", false);
		$this->db->where('kol_notes.id', $noteId);
		$this->db->join("client_users", "client_users.id = kol_notes.created_by", "left");
		$result = $this->db->get("kol_notes");
		$arr = array();
		foreach ($result->result_array() as $row) {
			$arr[] = $row;
		}
		return $arr;
	}
	function getUserNameByClientId($clientId){
		$arrUser = array();
		$this->db->select('CONCAT(COALESCE(client_users.first_name,"")," ",COALESCE(client_users.last_name,"")) as user_name',false);
		$this->db->select('client_users.id as user_id');
		$this->db->where_in('client_users.status',array(ACTIVATED_USER));
		$this->db->where('client_users.client_id',$clientId);
		$result=$this->db->get('client_users');
		foreach ($result->result_array() as $row)
		{
			$arrUser['user_name']	=	$row['user_name'];
			$arrUser['user_id']	=	$row['user_id'];
		}
		return $result->result_array();
	}
	function deleteLogActivity($id) {
		$this->db->where('kols_or_org_id', $id);
		$where = "(kols_or_org_type ='kol' OR kols_or_org_type ='kols')";
		$this->db->where($where);
		if($query = $this->db->delete('log_activities')){
			return true;
		}else{
			return false;
		}
	}
	function getKolNameById($id) {
		$kolName = array();
		$this->db->select('id,first_name,middle_name,last_name');
		$this->db->where_in('id', $id);
		$kolNameRusult = $this->db->get('kols');
		foreach ($kolNameRusult->result_array() as $key => $row) {
			$kolName[$row['id']] = $row['first_name'].' '.$row['middle_name'].' '.$row['last_name'];
		}
		return $kolName;
	}
	function editKol($id) {
		$arrKolDetails = array();
		$this->db->select('kols.*, titles.title as title_name, specialties.specialty as specialty_name, degrees.degree,kol_locations.private_practice,kol_locations.latitude,kol_locations.longitude, kols_client_visibility.id as vid');
		$this->db->where('kols_client_visibility.id', $id);
		//$this->db->where('kol_locations.is_primary', '1');
		$this->db->join('titles', 'kols.title = titles.id', 'left');
		$this->db->join('specialties', 'kols.specialty = specialties.id', 'left');
		$this->db->join('degrees', 'kols.degree_id = degrees.id', 'left');
		$this->db->join('kol_locations', 'kols.id = kol_locations.kol_id and kol_locations.is_primary = 1', 'left',false);
		$this->db->join('kols_client_visibility', 'kols_client_visibility.kol_id = kols.id', 'left');
		if ($arrKolDetailResult = $this->db->get('kols')) {
			// If the results are not available
			if ($arrKolDetailResult->num_rows() == 0) {
				return false;
			}
			foreach ($arrKolDetailResult->result_array() as $arrKol){
				$arrKolDetails = $arrKol;
				if (is_numeric($arrKolDetails['title']))
					$arrKolDetails['title_id'] = $arrKolDetails['title'];
					$arrKolDetails['title'] = $arrKolDetails['title_name'];
			}
			return $arrKolDetails;
		}else {
			return false;
		}
	}
	function getKolsLike1($arrKeywords, $arrFilterFields, $limit, $startFrom, $doCount, $doGroupBy = false, $groupByCategory = null, $arrKolIds = null, $isFrmInfluenceMap = false, $arrQueryOptions = array(),$checkClientBasedVisibilityByClientId=0) {
		$sizeOfKeywords = sizeof($arrKeywords);
		if ($sizeOfKeywords > 0 && empty($arrKeywords[0])) {
			$sizeOfKeywords = 0;
		}
		$currentController = $this->uri->segment(1);
		$currentControllerIpad = $this->uri->segment(2);
		
		$arrKols = array();
		$client_id = $this->session->userdata('client_id');
		$user_id = $this->session->userdata('user_id');
		
		if ($doCount == false && $doGroupBy == false) {
			$this->db->select('concat(m.first_name," ",m.last_name) as modified_by, concat(c.first_name," ",c.last_name) as created_by, kols.opt_in_out_status,kols.opt_in_out_date,kols.id,kols.salutation,kols.first_name,kols.unique_id,kols.middle_name,kols.last_name,kols.gender,kols.profile_image,kols.primary_phone,kols.primary_email,kols.specialty,kols.org_id,organizations.name,specialties.specialty as specs,countries.country,states.name as state,cities.City AS city,kol_locations.latitude as lat,kol_locations.longitude as lang,cities.city,kols.profile_type, kols.request_status,kols.created_on, kols.modified_on,kols.created_by as created_by_id,kols.status', false);
			$this->db->join('client_users as c', 'c.id = kols.created_by', 'left');
			$this->db->join('client_users as m', 'm.id = kols.created_by', 'left');
			$this->db->join('kol_locations', 'kols.id = kol_locations.kol_id and kol_locations.is_primary=1', 'left');
		}
		$this->db->join('organizations', 'organizations.id = kols.org_id', 'left');
		$this->db->join('titles', 'titles.id = kols.title', 'left');
		$this->db->join('specialties', 'specialties.id = kols.specialty', 'left');
		$this->db->join('countries', 'countries.countryId = kols.country_id', 'left');
		$this->db->join('country_regions', 'country_regions.country_id = countries.countryId','left');
		$this->db->join('states', 'states.id = kols.state_id', 'left');
		$this->db->join('cities', 'cities.CityId = kols.city_id', 'left');
		$this->db->select('opt_inout_statuses.name as opt_status_name');
		$this->db->join('opt_inout_statuses', 'opt_inout_statuses.id = kols.opt_in_out_status', 'left');
		if ($arrFilterFields != null && $arrFilterFields['education'] != null && isset($arrFilterFields['education']) && sizeof($arrFilterFields['education']) > 0 && !($doGroupBy == true && $groupByCategory == 'education')) {
			$this->db->distinct();
			$this->db->join('kol_educations', 'kol_educations.kol_id = kols.id', 'left');
			$this->db->join('institutions', 'kol_educations.institute_id = institutions.id', 'left');
			$this->db->where_in('institutions.id', $arrFilterFields['education']);
			$this->db->where('kol_educations.type', 'education');
		}
		if ($arrFilterFields != null && $arrFilterFields['event_id'] != null && isset($arrFilterFields['event_id']) && sizeof($arrFilterFields['event_id']) > 0 && !($doGroupBy == true && $groupByCategory == 'event')) {
			$this->db->distinct();
			$this->db->join('kol_events', 'kol_events.kol_id = kols.id', 'left');
			$this->db->join('events', 'kol_events.event_id = events.id', 'left');
			$this->db->where_in('kol_events.event_id', $arrFilterFields['event_id']);
		}
		if ($arrFilterFields != null && $arrFilterFields['list_id'] != null && isset($arrFilterFields['list_id']) && sizeof($arrFilterFields['list_id']) > 0 && !($doGroupBy == true && $groupByCategory == 'list')) {
			$this->db->distinct();
			$this->db->join('list_kols', 'list_kols.kol_id=kols.id', 'left');
			$this->db->join('list_names', 'list_kols.list_name_id=list_names.id', 'left');
			$this->db->where_in('list_names.id', $arrFilterFields['list_id']);
		}
		if ($arrFilterFields != null && $arrFilterFields['type'] != null && isset($arrFilterFields['type']) && sizeof($arrFilterFields['type']) > 0 && !($doGroupBy == true && $groupByCategory == 'type')) {
			$this->db->distinct();
			$this->db->join('organization_types', 'organization_types.id=organizations.type_id', 'left');
			$this->db->where_in('organization_types.id', $arrFilterFields['type']);
		}
		if ($arrFilterFields != null && $arrFilterFields['title'] != null && isset($arrFilterFields['title']) && $arrFilterFields['title'] != '' && sizeof($arrFilterFields['title']) > 0 && !($doGroupBy == true && $groupByCategory == 'title')) {
			$this->db->distinct();
			$this->db->where_in('titles.id', $arrFilterFields['title']);
		}
		if ($arrFilterFields != null && $arrFilterFields['region'] != null && isset($arrFilterFields['region']) && sizeof($arrFilterFields['region']) > 0 && !($doGroupBy == true && $groupByCategory == 'region')) {
			$this->db->distinct();
			$this->db->where_in('country_regions.region_id', $arrFilterFields['region']);
			$this->db->where('country_regions.client_id',$client_id);
		}
		if ($arrFilterFields != null && $arrFilterFields['opt_inout'] != null && isset($arrFilterFields['opt_inout']) && sizeof($arrFilterFields['opt_inout']) > 0 && !($doGroupBy == true && $groupByCategory == 'opt_inout')) {
			$this->db->distinct();
			$this->db->where_in('opt_inout_statuses.id', $arrFilterFields['opt_inout']);
		}
		$keywordSearchByAutoComplete = 0;
		$keywordSearchByAutoComplete = $this->session->userdata('keywordSearchByAutoComplete');
		switch ($sizeOfKeywords) {
			case 1:
				$name = trim($arrKeywords[0]);
				$where = '(kols.first_name LIKE "%' . $name . '%" OR kols.last_name LIKE "%' . $name . '" OR kols.middle_name LIKE "%' . $name . '%")';
				if ($keywordSearchByAutoComplete) {
					$where = '(kols.first_name = "' . $name . '")';
				}
				$this->db->where($where);
				break;
			case 2:
				$name1 = trim($arrKeywords[0]);
				$name2 = trim($arrKeywords[1]);
				$where = "(kols.first_name IN('" . $name1 . "','" . $name2 . "') OR kols.last_name IN ('" . $name1 . "','" . $name2 . "') OR kols.middle_name IN ('" . $name1 . "','" . $name2 . "'))";
				if ($keywordSearchByAutoComplete) {
					$where = '((kols.first_name = "' . $name1 . '" AND kols.last_name="' . $name2 . '") or (kols.first_name = "' . $name1 . '" AND kols.middle_name="' . $name2 . '"))';
				}
				$this->db->where($where);
				break;
			case 3:
				$name1 = trim($arrKeywords[0]);
				$name2 = trim($arrKeywords[1]);
				$name3 = trim($arrKeywords[2]);
				$where = "(kols.first_name IN('" . $name1 . "','" . $name2 . "','" . $name3 . "') OR kols.last_name IN ('" . $name1 . "','" . $name2 . "','" . $name3 . "') OR kols.middle_name IN ('" . $name1 . "','" . $name2 . "','" . $name3 . "'))";
				if ($keywordSearchByAutoComplete) {
					$where = '(kols.first_name = "' . $name1 . '" AND kols.middle_name="' . $name2 . '" AND kols.last_name="' . $name3 . '")';
				}
				$this->db->where($where);
				break;
		}
		if ($arrFilterFields != null && $arrFilterFields['country'] != '' && isset($arrFilterFields['country']) && sizeof($arrFilterFields['country']) > 0 && !($doGroupBy == true && $groupByCategory == 'country'))
			$this->db->where_in('countries.countryId', $arrFilterFields['country']);
			if ($arrFilterFields != null && $arrFilterFields['state'] != '' && isset($arrFilterFields['state']) && sizeof($arrFilterFields['state']) > 0 && !($doGroupBy == true && $groupByCategory == 'state')) {
				$this->db->where_in('states.id', $arrFilterFields['state']);
			}
			if ($arrFilterFields != null && $arrFilterFields['city'] != '' && isset($arrFilterFields['city']) && sizeof($arrFilterFields['city']) > 0 && !($doGroupBy == true && $groupByCategory == 'city')) {
				$this->db->where_in('cities.CityId', $arrFilterFields['city']);
			}
			if ($arrFilterFields != null && $arrFilterFields['organization'] != '' && isset($arrFilterFields['organization']) && sizeof($arrFilterFields['organization']) > 0 && !($doGroupBy == true && $groupByCategory == 'organization')) {
				$this->db->where_in('organizations.id', $arrFilterFields['organization']);
			}
			if ($arrFilterFields != null && $arrFilterFields['organization'] != '' && isset($arrFilterFields['organization']) && sizeof($arrFilterFields['organization']) > 0 && !($doGroupBy == true && $groupByCategory == 'organization')) {
				$this->db->where_in('organizations.id', $arrFilterFields['organization']);
			}
			if ($arrFilterFields != null && $arrFilterFields['specialty'] != '' && isset($arrFilterFields['specialty']) && sizeof($arrFilterFields['specialty']) > 0 && !($doGroupBy == true && $groupByCategory == 'specialty')) {
				$this->db->where_in('specialties.id', $arrFilterFields['specialty']);
			}
			if ($arrFilterFields != null && $arrFilterFields['kol_id'] != '') {
				$this->db->where_in('kols.id', $arrFilterFields['kol_id']);
			}
			if ($arrFilterFields != null && $arrFilterFields['profile_type'] != '' && $arrFilterFields['profile_type'] != "undefined") {
				$this->db->where('kols.profile_type', $arrFilterFields['profile_type']);
			}
			if ($arrKolIds != null && isset($arrKolIds) && sizeof($arrKolIds) > 0) {
				$this->db->where_in('kols.id', $arrKolIds);
			}
			if ($arrFilterFields != null && $arrFilterFields['global_region'] != '' && isset($arrFilterFields['global_region']) && sizeof($arrFilterFields['global_region']) > 0 && !($doGroupBy == true && $groupByCategory == 'global_region'))
				$this->db->where_in('countries.GlobalRegion', $arrFilterFields['global_region']);
				if(KOL_CONSENT){
					if ($arrFilterFields != null && $arrFilterFields['opt_inout'] != '' && isset($arrFilterFields['opt_inout']) && sizeof($arrFilterFields['opt_inout']) > 0 && !($doGroupBy == true && $groupByCategory == 'opt_inout'))
						$this->db->where_in('opt_inout_statuses.id', $arrFilterFields['opt_inout']);
				}
				if ($doCount) {
					if ($currentController == 'maps' or $currentControllerIpad == 'maps') {
						$this->db->where_in('kols.status', array(COMPLETED));
					} else {
						$this->db->where('kols.customer_status', "ACTV");
					}
					
					$this->db->select('COUNT(DISTINCT kols.id) AS count');
					if (isset($arrFilterFields) && sizeof($arrFilterFields['viewType']) > 0) {
						$this->db->where_in('kols.id', $arrFilterFields['viewType']);
					}
					if ($arrFilterFields['profile_type'] != "" && $arrFilterFields['profile_type'] != "undefined") {
						$this->db->where('kols.profile_type', $arrFilterFields['profile_type']);
					}
					$loggedInuserClientId	= $client_id;
					if($checkClientBasedVisibilityByClientId>0){
						$loggedInuserClientId	= $checkClientBasedVisibilityByClientId;
					}
					if($loggedInuserClientId !== INTERNAL_CLIENT_ID){
						$this->db->join('kols_client_visibility', 'kols_client_visibility.kol_id = kols.id', 'left');
						$this->db->where('kols_client_visibility.client_id', $loggedInuserClientId);
					}
					$arrKolDetailsResult = $this->db->get('kols');
					$resultRow = $arrKolDetailsResult->row();
					$count = $resultRow->count;
					return $count;
				} else {
					if ($doGroupBy) {
						if ($groupByCategory == 'country') {
							$this->db->select('kols.country_id,countries.country,COUNT(DISTINCT kols.id) as count');
							$this->db->group_by('country_id');
						}
						if ($groupByCategory == 'state') {
							$this->db->select('kols.state_id,states.name as state,COUNT(DISTINCT kols.id) as count');
							$this->db->group_by('state_id');
						}
						if ($groupByCategory == 'city') {
							$this->db->select('kols.city_id,cities.City as city,COUNT(DISTINCT kols.id) as count');
							$this->db->group_by('city_id');
						}
						if ($groupByCategory == 'specialty') {
							$this->db->select('kols.specialty,specialties.specialty as specs,COUNT(DISTINCT kols.id) as count');
							$this->db->group_by('specialty');
						}
						if ($groupByCategory == 'organization') {
							$this->db->select('organizations.id as org_id,organizations.name,COUNT(DISTINCT kols.id) as count');
							$this->db->group_by('organizations.id');
						}
						if ($groupByCategory == 'education') {
							$this->db->select('COUNT(DISTINCT kols.id) as count,institutions.name as institute_name,kol_educations.institute_id');
							$this->db->join('kol_educations', 'kol_educations.kol_id = kols.id', 'left');
							$this->db->join('institutions', 'kol_educations.institute_id = institutions.id', 'left');
							$this->db->where('kol_educations.type', 'education');
							$this->db->where('institutions.id > 0');
							$this->db->group_by('kol_educations.institute_id');
						}
						if ($groupByCategory == 'event') {
							$this->db->select('COUNT(DISTINCT kols.id) as count,events.name as event_name,kol_events.event_id as event_id');
							$this->db->join('kol_events', 'kol_events.kol_id = kols.id', 'left');
							$this->db->join('events', 'kol_events.event_id = events.id', 'left');
							$this->db->where('events.id > 0');
							$this->db->group_by('kol_events.event_id');
						}
						if ($groupByCategory == 'list') {
							$this->db->select('list_kols.list_name_id,list_names.list_name,list_categories.category,COUNT(DISTINCT kols.id) AS count');
							$this->db->join('list_kols', 'list_kols.kol_id=kols.id', 'left');
							$this->db->join('list_names', 'list_kols.list_name_id=list_names.id', 'left');
							$this->db->join('list_categories', 'list_names.category_id=list_categories.id', 'left');
							$this->db->where('list_categories.client_id', $client_id);
							$where = "(list_categories.user_id=$user_id OR list_categories.is_public=1)";
							$this->db->where($where);
							$this->db->group_by('list_kols.list_name_id');
						}
						if ($groupByCategory == 'global_region') {
						}
						
						if ($groupByCategory == 'type') {
							$this->db->select('organization_types.type,organization_types.id as org_type_id,COUNT(DISTINCT kols.id) as count');
							$this->db->join('organization_types', 'organization_types.id=organizations.type_id', 'left');
							$this->db->group_by('organization_types.id');
						}
						
						if ($groupByCategory == 'title') {
							$this->db->select('titles.title,titles.id as title_id,COUNT(DISTINCT kols.id) as count');
							$this->db->group_by('titles.id');
						}
						
						if ($groupByCategory == 'region') {
							$this->db->select('regions.name as global_region_name,regions.id as global_region_id,COUNT(DISTINCT kols.id) as count');
							$this->db->join('regions','country_regions.region_id=regions.id', 'left');
							$this->db->group_by('regions.id');
						}
						if(KOL_CONSENT){
							if ($groupByCategory == 'opt_inout') {
								$this->db->select('opt_inout_statuses.name as opt_name, opt_inout_statuses.id as opt_in_out_id,COUNT(DISTINCT kols.id) as count');
								$this->db->where('kols.opt_in_out_status !=', 0);
								$this->db->group_by('opt_inout_statuses.name');
							}
						}
						$this->db->order_by('count', 'desc');
					} else {
						if (!$isFrmInfluenceMap)
							$this->db->limit($limit, $startFrom);
					}
					if ($currentController == 'maps' or $currentControllerIpad == 'maps') {
					} else {
						$this->db->where('kols.customer_status', "ACTV");
					}
					if ($arrFilterFields['profile_type'] != "" && $arrFilterFields['profile_type'] != "undefined") {
						$this->db->where('kols.profile_type', $arrFilterFields['profile_type']);
					}
					if (sizeof($arrQueryOptions) > 0) {
						switch ($arrQueryOptions['sort_by']) {
							case 'name':
								if ($this->session->userdata('name_order') == 2) {
									$this->db->order_by('kols.last_name', $arrQueryOptions['sort_order']);
								} else {
									$this->db->order_by('kols.first_name', $arrQueryOptions['sort_order']);
									$this->db->order_by('kols.middle_name', $arrQueryOptions['sort_order']);
									$this->db->order_by('kols.last_name', $arrQueryOptions['sort_order']);
								}
								break;
							case 'specialty':$this->db->order_by('specialties.specialty', $arrQueryOptions['sort_order']);
							break;
							case 'state':$this->db->order_by('states.name', $arrQueryOptions['sort_order']);
							break;
							case 'city':$this->db->order_by('cities.City', $arrQueryOptions['sort_order']);
							break;
							case 'country':$this->db->order_by('countries.country', $arrQueryOptions['sort_order']);
							break;
							case 'opt_status':$this->db->order_by('kols.opt_in_out_status', $arrQueryOptions['sort_order']);
							break;
						}
					} else if (!$doGroupBy) {
						if ($this->session->userdata('name_order') == 2) {
							$this->db->order_by('kols.last_name', $arrQueryOptions['sort_order']);
						} else {
							$this->db->order_by('kols.first_name');
						}
					}
					if (isset($arrFilterFields) && sizeof($arrFilterFields['viewType']) > 0) {
						$this->db->where_in('kols.id', $arrFilterFields['viewType']);
					}
					$loggedInuserClientId	= $client_id;
					if($checkClientBasedVisibilityByClientId>0){
						$loggedInuserClientId	= $checkClientBasedVisibilityByClientId;
					}
					if($loggedInuserClientId !== INTERNAL_CLIENT_ID){
						$this->db->join('kols_client_visibility', 'kols_client_visibility.kol_id = kols.id', 'left');
						$this->db->where('kols_client_visibility.client_id', $loggedInuserClientId);
					}
					$arrKolDetailsResult = $this->db->get('kols');
					foreach ($arrKolDetailsResult->result_array() as $row) {
						$arrKols[] = $row;
					}
					return $arrKols;
				}
	}
	function getMyKolsView($userId) {
		$this->db->select('kol_id');
		$this->db->where('user_id', $userId);
		$this->db->join('kols','user_kols.kol_id = kols.id','left');
		$this->db->where('kols.customer_status',"ACTV");
		$result = $this->db->get('user_kols');
		foreach ($result->result_array() as $row) {
			$arr[$row['kol_id']] = $row['kol_id'];
		}
		return $arr;
	}
	function getAllCustomFilterByUser($userId) {
		$arrData = array();
		$this->db->select();
		$this->db->where('created_by', $userId);
		$this->db->where('filter_type', 1);
		$this->db->order_by('applied_on', 'desc');
		$this->db->order_by('name', 'asc');
		$result = $this->db->get('custom_filters');
		foreach ($result->result_array() as $row) {
			$arrData[] = $row;
		}
		return $arrData;
	}
	function getFilterById($arrData) {
		$this->db->where('id', $arrData['id']);
		if ($this->db->update('custom_filters', $arrData)) {
			$this->db->where('id', $arrData['id']);
			$result = $this->db->get('custom_filters');
			if ($result->num_rows() > 0) {
				foreach ($result->result() as $row) {
					$arrData = $row->filter_value;
				}
			}
			return $arrData;
		} else {
			return false;
		}
	}
	function saveFilter($arrDetails){
		$id=$arrDetails['filter_id'];
		$data['name']=$arrDetails['filter_name'];
		$data['filter_type']=1;
		$data['filter_value']= json_encode(str_replace(";","",$arrDetails['filter_data']));
		if($id!='' && $id>0){
			$data['modified_on']=date('Y-m-d H:i:s');
			$this->db->where('id',$id);
			$this->db->update('custom_filters',$data);
			if($id)
				return $id;
			else
				return false;
		}else{
			$data['created_by']=$this->session->userdata('user_id');
			$data['created_on']=date('Y-m-d H:i:s');
			$this->db->insert('custom_filters',$data);
			$id=$this->db->insert_id();
			return $id;
		}
	}
	function getSpecialtiesById($arrId){
		$arrSpecialties	= array();
		$this->db->where_in('id', $arrId);
		$results	= $this->db->get('specialties');
		foreach($results->result_array() as $row){
			$arrSpecialties[$row['id']]=$row['specialty'];
		}
		return $arrSpecialties;
	}
	function getGlobalRegionsById($arrId){
		$arrGlobalRegions= array();
		$this->db->where_in('id', $arrId);
		$results	= $this->db->get('regions');
		foreach($results->result_array() as $row){
			$arrGlobalRegions[$row['id']]=$row['name'];
		}
		return $arrGlobalRegions;
	}
	function getPositionsById($arrId){
		$arrPositions= array();
		$this->db->where_in('id', $arrId);
		$results	= $this->db->get('titles');
		foreach($results->result_array() as $row){
			$arrPositions[$row['id']]=$row['title'];
		}
		return $arrPositions;
	}
	function getOrgTypeById($arrTypeId){
		$arrTypes = array();
		$this->db->where_in('id',$arrTypeId);
		$arrResultSet = $this->db->get('organization_types');
		foreach($arrResultSet->result_array() as $row){
			$arrTypes[$row['id']] = $row['type'];
		}
		return $arrTypes;
	}
	function getInstituteNameById($arrId) {
		$instituteName = array();
		$arrayIds = array();
		if(!empty($arrId)){
			foreach($arrId as $key=>$value){
				if(is_numeric($value)){
					$arrayIds[$key] =  $value;
				}
			}
			$this->db->select('id,name');
			$this->db->where_in('id', $arrayIds);
			$resultSet = $this->db->get('institutions');
			foreach ($resultSet->result_array() as $row) {
				$instituteName[$row['id']] = $row['name'];
			}
		}
		return $instituteName;
	}
	function getEventNameById($arrId) {		
		$eventName = array();
		$this->db->select('id as event_id,name');
		$this->db->where_in('id', $arrId);
		$eventNameRusult = $this->db->get('events');
		foreach ($eventNameRusult->result_array() as $row) {
			$eventName[$row['event_id']] = $row['name'];
		}
		return $eventName;
	}
	function editListName($listId){
		$this->db->select('list_names.*,list_categories.category');
		$this->db->join('list_categories','list_categories.id=list_names.category_id','left');
		$this->db->where('list_names.id',$listId);
		$arrResult=$this->db->get('list_names');
		foreach($arrResult->result_array() as $row){
			$arrListName=$row;
		}
		return $arrListName;
	}
	function getOptNameById($arrOptIds){
		$arrOptNames = array();
		$this->db->where_in('id',$arrOptIds);
		$arrResultSet = $this->db->get('opt_inout_statuses');
		foreach($arrResultSet->result_array() as $row){
			$arrOptNames[$row['id']] = $row['name'];
		}
		return $arrOptNames;
	}
	function getFilterNameById($arrfilterIds){
		$arrFilterNames = array();
		$this->db->where_in('id',$arrfilterIds);
		$arrResultSet = $this->db->get('custom_filters');
		foreach($arrResultSet->result_array() as $row){
			$arrFilterNames[$row['id']] = $row['name'];
		}
		return $arrFilterNames;
	}
	function getKolDetailsById($kolId) {
		$arrKolDetails = array();
		$arrSalutations = array(0 => '', 'Dr.', 'Prof.', 'Mr.', 'Ms.');
		$this->db->where('kols.id', $kolId);
		$this->db->select(array('kols.*', 'countries.Country', 'states.name', 'cities.City',  'titles.title as kol_title','specialties.specialty as specialty_name'));
		$this->db->join('countries', 'CountryId = country_id', 'left');
		$this->db->join('states', 'states.id = state_id', 'left');
		$this->db->join('cities', 'cityId = city_id', 'left');
		$this->db->join('specialties', 'specialties.id = kols.specialty', 'left');
		$this->db->join('titles', 'kols.title = titles.id', 'left');
		$arrKolDetailsResult = $this->db->get('kols');
		foreach ($arrKolDetailsResult->result_array() as $row) {
			if (!empty($row['org_id']))
				$row['org_id'] = $this->getOrgId($row['org_id']);
			if ($row['salutation'] != 0) {
				$row['salutation'] = $arrSalutations[$row['salutation']];
			} else
				$row['salutation'] = "";
			$row['title']=$row['kol_title'];
			$arrKolDetails[] = $row;
		}
// 		echo $this->db->last_query();
		return $arrKolDetails;
	}
	function getOrgId($orgId) {
		$name = '';
		$this->db->where('id', $orgId);
		$result = $this->db->get('organizations');
		foreach ($result->result_array() as $row) {
			$name = $row['name'];
		}
		return $name;
	}
	function getKolsIdAndPin() {
		$kolResult = array();
		$arrKols = array();
		$this->db->select('id,pin');
		$kolResult = $this->db->get('kols');		
		foreach ($kolResult->result_array() as $row) {
			$arrKols[$row['id']] = $row['pin'];
		}
		return $arrKols;
	}
	function getEducationDetailById($kolId) {
		$arrEducationDetails = array();
		$clientId = $this->session->userdata('client_id');
		$this->db->where('kol_id', $kolId);
		$this->db->select(array('kol_educations.*', 'institutions.name'));
		$this->db->join('institutions', 'institutions.id = kol_educations.institute_id', 'left');
		
		if ($clientId != INTERNAL_CLIENT_ID) {
			$this->db->where("(kol_educations.client_id=$clientId or kol_educations.client_id=" . INTERNAL_CLIENT_ID . ")");
		}
		$arrEducationDetailsResult = $this->db->get('kol_educations');
		foreach ($arrEducationDetailsResult->result_array() as $arrRow) {
			$arrEducationDetails[] = $arrRow;
		}
		return $arrEducationDetails;
	}
	function listAllMembershipsDetails($kolId = null) {
		$arrMembershipDetails = array();
		$clientId = $this->session->userdata('client_id');
		$arrMembership = array();
		if ($kolId != null) {
			//Getting the data from 'kol_memberships' table and 'name' from 'institutions' table
			$this->db->select(array('kol_memberships.*', 'institutions.name'));
			$this->db->join('institutions', 'institutions.id = kol_memberships.institute_id', 'left');
			$this->db->where('kol_id', $kolId);
			//Getting the data from 'kol_memberships' table and 'engagement_type' from 'engagement_types' table
			$this->db->select(array('kol_memberships.*', 'engagement_types.engagement_type'));
			$this->db->join('engagement_types', 'engagement_types.id=engagement_id', 'left');
		}
		if ($clientId != INTERNAL_CLIENT_ID) {
			$this->db->where("(client_id=$clientId or client_id=" . INTERNAL_CLIENT_ID . ")");
		}		
		$this->db->order_by('type', 'asc');
		$this->db->order_by('engagement_type', 'asc');
		if ($arrMembershipDetailResult = $this->db->get('kol_memberships')) {
			if ($arrMembershipDetailResult->num_rows() == 0) {
				return false;
			}			
			foreach ($arrMembershipDetailResult->result_array() as $arrMembership) {
				//setting the institute name
				$arrMembership['institute_id'] = $arrMembership['name'];
				if ($arrMembership['url1'] != '') {
					$arrMembership['url1ForExport'] = $arrMembership['url1'];
					$arrMembership['url1'] = '<a href=\'' . $arrMembership['url1'] . '\' target="_new">URl1</a>';
				}if ($arrMembership['url2'] != '') {
					$arrMembership['ur21ForExport'] = $arrMembership['url2'];
					$arrMembership['url2'] = '<a href=\'' . $arrMembership['url2'] . '\' target="_new">URl2</a>';
				}
				//setting the engagement name
				$arrMembership['engagement_id'] = $arrMembership['engagement_type'];
				$arrMembershipDetails[] = $arrMembership;
			}
			return $arrMembershipDetails;
		} else {
			return false;
		}
	}
	function listAllEvents($kolId = null, $limit = 0) {
		$arrEventsDetails = array();
		//Get the Events of KolId
		$clientId = $this->session->userdata('client_id');
		if ($kolId != null) {
			$this->db->where('kol_id', $kolId);
			$this->db->select(array('kol_events.*', 'event_sponsor_types.type as stype', 'event_organizer_types.type as otype', 'events.name', 'countries.Country',  'states.name as Region', 'cities.City', 'conf_session_types.session_type'));
			$this->db->join('events', 'events.id = event_id', 'left');
			$this->db->join('countries', 'CountryId = country_id', 'left');
			$this->db->join('states', 'states.id = state_id', 'left');
			$this->db->join('cities', 'cityId = city_id', 'left');
			$this->db->join('conf_session_types', 'conf_session_types.id = kol_events.session_type', 'left');
			$this->db->join('event_sponsor_types', 'kol_events.sponsor_type = event_sponsor_types.id', 'left');
			$this->db->join('event_organizer_types', 'kol_events.organizer_type = event_organizer_types.id', 'left');
		}
		if ($clientId != INTERNAL_CLIENT_ID) {
			$this->db->where("(kol_events.client_id=$clientId or kol_events.client_id=" . INTERNAL_CLIENT_ID . ")");
		}
		$this->db->order_by('conf_session_types.session_type', 'asc');
		$this->db->order_by('kol_events.end', 'desc');
		if ($limit == 20) {
			$this->db->limit('20');
		}
		if ($arrEventsDetailsResult = $this->db->get('kol_events')) {
			foreach ($arrEventsDetailsResult->result_array() as $arrEvent) {
				if ($arrEvent['type'] == 'conference') {
					if ($arrEvent['event_type'] != '0') {
						$arrEvent['event_type'] = $this->getConferenceEventTypeById($arrEvent['event_type']);
					} else {
						$arrEvent['event_type'] = '';
					}
				}
				if ($arrEvent['type'] == 'online') {
					if ($arrEvent['event_type'] != '0') {
						$arrEvent['event_type'] = $this->getOnlineEventTypeById($arrEvent['event_type']);
					} else {
						$arrEvent['event_type'] = '';
					}
				}
				$arrEvent['start'] = $this->convertDateToMM_DD_YYYY($arrEvent['start']);
				if ($arrEvent['start'] == '00/00/0000') {
					$arrEvent['start'] = '';
				}
				$arrEvent['end'] = $this->convertDateToMM_DD_YYYY($arrEvent['end']);
				if ($arrEvent['end'] == '00/00/0000') {
					$arrEvent['end'] = '';
				}
				if ($arrEvent['url1'] != '') {
					$arrEvent['url1ForExport'] = $arrEvent['url1'];
					$arrEvent['url1'] = '<a href=\'' . $arrEvent['url1'] . '\' target="_new">URl1</a>';
				}
				if ($arrEvent['url2'] != '') {
					$arrEvent['url2ForExport'] = $arrEvent['url2'];
					$arrEvent['url2'] = '<a href=\'' . $arrEvent['url2'] . '\' target="_new">URl2</a>';
				}
				$arrEventsDetails[] = $arrEvent;
			}
			return $arrEventsDetails;
		} else {
			return false;
		}
	}
	function getConferenceEventTypeById($eventTypeId){
		$eventType='';
		if($eventTypeId != ''){
			$this->db->where('id', $eventTypeId);
			$this->db->select('event_type');
			$eventType	= $this->db->get('conf_event_types');
			foreach($eventType->result_array() as $row){
				$eventType = $row['event_type'];
			}
			return $eventType;
		}else
			return $eventType;
	}
	function convertDateToMM_DD_YYYY($inputDate, $delimiter = '/') {
		$ddDate = substr($inputDate, 8, 2);
		$mmDate = substr($inputDate, 5, 2);
		$yyDate = substr($inputDate, 0, 4);
		return ($mmDate . $delimiter . $ddDate . $delimiter . $yyDate);
	}
	function getTopicName($topicId) {
		$topicName = "";
		$this->db->where('id', $topicId);
		$result = $this->db->get('event_topics');
		if ($result->num_rows() != 0) {
			$resultObject = $result->row();
			$topicName = $resultObject->name;
		}
		return $topicName;
	}
	function getNotes($kolId) {
		$rows = array();
		$client_id = $this->session->userdata('client_id');
		$this->db->select('kol_notes.*,client_users.first_name,client_users.client_id as post_id,modified.client_id as modif_id,client_users.last_name,modified.first_name as modified_by_first_name, modified.last_name as modified_by_last_name');
		$this->db->select('kols.first_name as ktl_fname,kols.last_name as ktl_lname');
		$this->db->where('kol_notes.kol_id', $kolId);
		$this->db->join('kols', 'kols.id = kol_notes.kol_id', 'left');
		$this->db->join('client_users', 'kol_notes.created_by = client_users.id', 'left');
		$this->db->join('client_users as modified', 'kol_notes.modified_by = modified.id', 'left');
		if($client_id !== INTERNAL_CLIENT_ID){
			$this->db->join('kols_client_visibility', 'kols_client_visibility.kol_id = kol_notes.kol_id', 'left');
			$this->db->where('kols_client_visibility.client_id', $client_id);
			$this->db->where("(kol_notes.client_id = $client_id or kol_notes.client_id = 1)");
		}
		$this->db->order_by('created_on', 'desc');
		$res = $this->db->get('kol_notes');
		if ($res->num_rows() > 0)
			$rows = $res->result_array();
		return $rows;
	}
	function getDetailsInfoById($kolIds,$type){
		switch($type){
			case 'Location':
				$this->db->select("kols.pin,organizations.name as org_name,titles.title,kol_locations.division,kol_locations.address1,kol_locations.address2,cities.City,states.name,countries.Country,kol_locations.postal_code");
				$this->db->select('CASE WHEN kol_locations.is_primary = 1 THEN "Yes" ELSE "No" END AS is_primary',false);
				$this->db->join("kol_locations","kol_locations.kol_id = kols.id","left");
				$this->db->join("countries","countries.CountryId = kol_locations.country_id","left");
				$this->db->join("states","states.id = kol_locations.state_id","left");
				$this->db->join("cities","cities.CityId = kol_locations.city_id","left");
				$this->db->join("titles","titles.id = kol_locations.title","left");
				$this->db->join("organizations","organizations.id = kol_locations.org_institution_id","left");
				$this->db->where_in("kol_locations.kol_id",$kolIds);
				$query = $this->db->get("kols");
				return $query->result_array();
				break;
			case 'Phone':
				$this->db->select("kols.pin,phone_type.name,organizations.name as org_name,phone_numbers.number");
				$this->db->select('CASE WHEN phone_numbers.is_primary = 1 THEN "Yes" ELSE "No" END AS is_primary',false);
				$this->db->join("phone_numbers","phone_numbers.contact = kols.id","left");
				$this->db->join("kol_locations","kol_locations.id = phone_numbers.location_id","left");
				$this->db->join("organizations","organizations.id = kol_locations.org_institution_id","left");
				$this->db->join("phone_type","phone_type.id = phone_numbers.type","left");
				$this->db->where_in("phone_numbers.contact",$kolIds);
				$query = $this->db->get("kols");
				return $query->result_array();
				break;
			case 'Email':
				$this->db->select("kols.pin,emails.email,emails.type");
				$this->db->select('CASE WHEN emails.is_primary = 1 THEN "Yes" ELSE "No" END AS is_primary',false);
				$this->db->join("emails","emails.contact = kols.id","left");
				$this->db->where_in("kols.id",$kolIds);
				$query = $this->db->get("kols");
				return $query->result_array();
				break;
			case 'License':
				$this->db->select("kols.pin,state_licenses.state_license,states.name,countries.Country");
				$this->db->select('CASE WHEN state_licenses.is_primary = 1 THEN "Yes" ELSE "No" END AS is_primary',false);
				$this->db->join("state_licenses","state_licenses.contact = kols.id","left");
				$this->db->join("states","states.id = state_licenses.region","left");
				$this->db->join("countries","countries.CountryId = states.country_id","left");
				$this->db->where_in("state_licenses.contact",$kolIds);
				$query = $this->db->get("kols");
				return $query->result_array();
				break;
			case 'Specialty':
				$this->db->select("kols.pin,specialties.specialty");
				$this->db->select('CASE WHEN kol_sub_specialty.priority = 1 THEN "Primary Specialty" WHEN  kol_sub_specialty.priority = 2 THEN "Additional Specialty" ELSE "Sub-Specialty" END AS priority',false);
				$this->db->select('CASE WHEN kol_sub_specialty.priority = 1 THEN "Yes" ELSE "No" END AS is_primary',false);
				$this->db->join("kol_sub_specialty","kol_sub_specialty.kol_id = kols.id","left");
				$this->db->join("specialties","specialties.id = kol_sub_specialty.kol_sub_specialty_id","left");
				$this->db->where_in("kol_sub_specialty.kol_id",$kolIds);
				$this->db->where("kol_sub_specialty.kol_sub_specialty_id !=",0);
				$query = $this->db->get("kols");
				return $query->result_array();
				break;
			case 'Staff':
				$this->db->select("kols.pin,staffs.name,staffs.phone_number,organizations.name as org_name,staffs.email,staff_title.name as staff_title,phone_type.name as phone_type,kol_locations.address1,kol_locations.address2,cities.City,states.name,countries.Country,kol_locations.postal_code");
				$this->db->join("staffs","staffs.contact = kols.id","left");
				$this->db->join("kol_locations","kol_locations.id = staffs.location_id","left");
				$this->db->join("organizations","organizations.id = kol_locations.org_institution_id","left");
				$this->db->join("countries","countries.CountryId = kol_locations.country_id","left");
				$this->db->join("states","states.id = kol_locations.state_id","left");
				$this->db->join("cities","cities.CityId = kol_locations.city_id","left");
				$this->db->join("staff_title","staff_title.id = staffs.title","left");
				$this->db->join("phone_type","phone_type.id = staffs.phone_type","left");
				$this->db->where_in("staffs.contact",$kolIds);
				$query = $this->db->get("kols");
				return $query->result_array();
				break;
		}
	}
	function listEducationDetails($type, $kolId = null) {
		$clientId = $this->session->userdata('client_id');
		$arrEducationDetails = array();
		//Get the Events of KolId
		if ($kolId != null) {
			$this->db->where('kol_id', $kolId);
			if ($type != 'all') {
				$this->db->where('type', $type);
			}else{
				$this->db->where('type !=', 'honors_awards');
			}
			//if ($type != 'honors_awards') {
			$this->db->select(array('kol_educations.*', 'institutions.name','client_users.first_name','client_users.last_name'));
			$this->db->join('institutions', 'institutions.id = kol_educations.institute_id', 'left');
			$this->db->join('client_users', 'client_users.id = kol_educations.created_by', 'left');
			//}
		}
		if ($clientId != INTERNAL_CLIENT_ID) {
			$this->db->where("(kol_educations.client_id=$clientId or kol_educations.client_id=" . INTERNAL_CLIENT_ID . ")");
		}
		$this->db->order_by('kol_educations.start_date','desc');
		
		if ($arrEducationDetailsResult = $this->db->get('kol_educations')) {
			foreach ($arrEducationDetailsResult->result_array() as $arrEducation) {
				if ($type != 'honors_awards') {
					$arrEducation['institute_id'] = $arrEducation['name'];
				}
				if ($arrEducation['url1'] != '') {
					$arrEducation['url1'] = '<a href=\'' . $arrEducation['url1'] . '\' target="_new">URL1</a>';
				}
				if ($arrEducation['url2'] != '') {
					$arrEducation['url2'] = '<a href=\'' . $arrEducation['url2'] . '\' target="_new">URL2</a>';
				}
				$arrEducation['eAllowed'] = $this->common_helper->isActionAllowed('kol_details', 'edit', $arrEducation);
				$arrEducationDetails[] = $arrEducation;
			}
			return $arrEducationDetails;
		} else {
			return false;
		}
	}
	function listAdditionalContacts($kolId) {
		$locationType = array(
				'1' => 'Private practice',
				'2' => 'Hospital',
				'3' => 'Institution',
				'4' => 'Physical',
				'5' => 'Others'
		);
		$arrContacts = array();
		$this->db->select('additionalcontacts.*');
		$this->db->where('kol_id', $kolId);
		$arrContactsResultSet = $this->db->get('additionalcontacts');
		foreach ($arrContactsResultSet->result_array() as $row) {
			if (isset($row['type']) && $row['type'] > 0) {
				$row['type'] = $locationType[$row['type']];
				$row['state'] = $this->Country_helper->getStateById($row['state_id']);
				$row['city'] = $this->Country_helper->getCityeById($row['city_id']);
			}
			$arrContacts[] = $row;
		}
		return $arrContacts;
	}
	function listAllAffiliationsDetails($kolId = null) {
		$clientId = $this->session->userdata('client_id');
		$arrMembershipDetails = array();
		if ($kolId != null) {
			//Getting the data from 'kol_memberships' table and 'name' from 'institutions' table
			$this->db->select(array('kol_memberships.*', 'institutions.name', 'engagement_types.engagement_type','CONCAT(COALESCE(client_users.first_name,"")," ",COALESCE(client_users.last_name,"")) as created_by_full_name'));
			$this->db->join('institutions', 'institutions.id = kol_memberships.institute_id', 'left');
			$this->db->join('engagement_types', 'engagement_types.id = kol_memberships.engagement_id', 'left');
			$this->db->join('client_users', 'kol_memberships.created_by = client_users.id','left');
			$this->db->where('kol_id', $kolId);
			
			if ($clientId != INTERNAL_CLIENT_ID) {
				$this->db->where("(kol_memberships.client_id=$clientId or kol_memberships.client_id=" . INTERNAL_CLIENT_ID . ")");
			}
			//$this->db->order_by('institutions.name', 'asc');
			$this->db->order_by('kol_memberships.start_date', 'desc');
			
			if ($arrMembershipDetailResult = $this->db->get('kol_memberships')) {
				if ($arrMembershipDetailResult->num_rows() == 0) {
					return false;
				}
				
				foreach ($arrMembershipDetailResult->result_array() as $arrMembership) {
					$arrMembership['eAllowed'] =  $this->common_helper->isActionAllowed('kol_details', 'edit', $arrMembership);
					$arrMembershipDetails[] = $arrMembership;
				}
				return $arrMembershipDetails;
			} else
				return false;
		}
	}
	function getInteractionsCountByUsers($kolId,$group_by = false){
		$clientId = $this->session->userdata("client_id");
		$lastSixMonths = date("Y-m-d", strtotime("-6 months"));
		if($group_by)
			$this->db->select("interactions.created_by,count(interactions.id)");
		else
			$this->db->select("interactions.created_by");
		$this->db->join("interactions","interactions.id = interactions_attendees.interaction_id","left");
		$this->db->where("interactions_attendees.kol_id",$kolId);
		$this->db->where("interactions.date >",$lastSixMonths);
		$this->db->where("interactions.client_id",$clientId);
		if($group_by)
			$this->db->group_by("interactions.created_by");
			
			$arrInteractions = $this->db->get('interactions_attendees');
			return $arrInteractions->num_rows();
	}
	
	function countTrials($kolId) {
		$count = '';
		$this->db->where('kol_id', $kolId);
		$this->db->where('is_verified', 1);
		if ($count = $this->db->count_all_results('kol_clinical_trials')) {
			return $count;
		} else {
			return $count;
		}
	}
	function countPublications($kolId) {
		$count = '';
		$this->db->where('kol_id', $kolId);
		$this->db->where('is_deleted', 0);
		$this->db->where('is_verified', 1);
		if ($count = $this->db->count_all_results('kol_publications')) {
			return $count;
		} else {
			return $count;
		}
	}
	function updateKolProfileSummary($jsonData,$id){
		$data = array(
				"biography"=>$jsonData
		);
		$this->db->where('id', $id);
		$query	= $this->db->update('kols', $data);
		if($query){
			return true;
		}else{
			return false;
		}
	}
	function listAllEducationDetails($kolId = null) {
		$clientId = $this->session->userdata('client_id');
		$arrEducationDetails = array();
		//Get the Events of KolId
		if ($kolId != null) {
			$this->db->where('kol_id', $kolId);
			
			$this->db->select(array('kol_educations.*', 'institutions.name'));
			$this->db->join('institutions', 'institutions.id = kol_educations.institute_id', 'left');
		}
		if ($clientId != INTERNAL_CLIENT_ID) {
			$this->db->where("(kol_educations.client_id=$clientId or kol_educations.client_id=" . INTERNAL_CLIENT_ID . ")");
		}
		if ($arrEducationDetailsResult = $this->db->get('kol_educations')) {
			foreach ($arrEducationDetailsResult->result_array() as $arrEducation) {
				$arrEducation['institute_id'] = $arrEducation['name'];
				if ($arrEducation['url1'] != '') {
					$arrEducation['url1'] = '<a href=\'' . $arrEducation['url1'] . '\' target="_new">URL1</a>';
				}
				if ($arrEducation['url2'] != '') {
					$arrEducation['url2'] = '<a href=\'' . $arrEducation['url2'] . '\' target="_new">URL2</a>';
				}
				$arrEducationDetails[] = $arrEducation;
			}
			return $arrEducationDetails;
		} else {
			return false;
		}
	}
	function listContacts($kolId = null) {
		$arrContactDetails = array();
		if ($kolId != null) {
			$this->db->where('kol_id', $kolId);
		}
		if ($arrContactDetailsResult = $this->db->get('kol_additional_contacts')) {
			if ($arrContactDetailsResult->num_rows() == 0) {
				return false;
			}
			foreach ($arrContactDetailsResult->result_array() as $row) {
				$arrContactDetails[] = $row;
			}
			return $arrContactDetails;
		}else{
			return false;
		}
	}
	function getAllActiveTitles($listAll='') {
		$client_id = $this->session->userdata('client_id');
		$this->db->select('titles.*');
		$this->db->where('is_active', 1);
		$this->db->group_by('title');
		$this->db->order_by('title', 'asc');
		$this->db->order_by('id', 'asc');
		if ($client_id != INTERNAL_CLIENT_ID  && $this->session->userdata('user_role_id') != ROLE_ADMIN)
			$this->db->where('client_id', $client_id);
			$res = $this->db->get('titles');
			return $res->result_array();
	}
	function getAllActiveProfessionalSuffixes() {
		$this->db->select('professional_suffix.*');
		$this->db->where('is_active', 1);
		$this->db->order_by('suffix', 'asc');
		$this->db->group_by('suffix');
		$res = $this->db->get('professional_suffix');
		return $res->result_array();
	}
	function getPhoneType(){
		$arrDatas=array();
		$arrPhoneType = $this->db->get('phone_type');
		foreach($arrPhoneType->result_array() as $row){
			$arrDatas[$row['id']]	= $row['name'];
		}
		return $arrDatas;
	}
	function getOrganizationNamesWithStateCity($organizationName,$restrictByRegion=0) {
		$client_id = $this->session->userdata('client_id');
		$this->db->select('organizations.id,organizations.name,states.name as state,cities.city as city');
		$this->db->join('states', 'states.id = organizations.state_id', 'left');
		$this->db->join('cities', 'cities.cityID = organizations.city_id', 'left');
		if(ORGS_VISIBILITY){
			if ($client_id !== INTERNAL_CLIENT_ID) {
				$this->db->join('org_client_visibility','organizations.id = org_client_visibility.org_id','left');
				$this->db->where(array('org_client_visibility.client_id'=>$client_id,'org_client_visibility.is_visible'=>1));
			}
		}
		if($restrictByRegion){
			if(INTERNAL_CLIENT_ID != $client_id && $this->session->userdata('user_role_id') != ROLE_ADMIN){
				if($this->session->userdata('user_role_id') == ROLE_USER || $this->session->userdata('user_role_id') == ROLE_MANAGER){
					$group_names = explode(',', $this->session->userdata('group_names'));
					$this->db->join ( 'countries', 'countries.CountryId=organizations.country_id', 'left' );
					$this->db->where_in( 'countries.GlobalRegion', $group_names);
				}
			}
		}
		$this->db->like('organizations.name', $organizationName);
		$arrResultSet = $this->db->get('organizations');
		$arrOrganizationNames = array();
		foreach ($arrResultSet->result_array() as $arrRow) {
			$arrOrganizationNames[] = $arrRow;
		}
		return $arrOrganizationNames;
	}
	function getKolPrimaryLocation($kolId) {
		$this->db->select('kol_locations.*');
		$this->db->where('kol_id', $kolId);
		$this->db->where('is_primary', 1);
		$res = $this->db->get('kol_locations');
		return $res->result_array();
	}
	function getKolPrimaryPhoneDetails($kolId) {
		$this->db->where('contact', $kolId);
		$this->db->where('is_primary', 1);
		$arrRes = $this->db->get('phone_numbers');
		$arr = array();
		foreach ($arrRes->result_array() as $row) {
			$arr = $row;
		}
		return $arr;
	}
	function getKolProducts($kolId) {
		$this->db->select('products.id,products.name');
		$this->db->join('kol_products', 'kol_products.product_id = products.id');
		$this->db->where('kol_products.kol_id', $kolId);
		$res = $this->db->get('products');
		$arrRes = $res->result_array();
		return $arrRes;
	}
	function listLocationDetails($kolId) {
		$this->db->select('kol_locations.id, concat(COALESCE(kol_locations.address1,""), ", ", COALESCE(kol_locations.address2,""), ", ", COALESCE(kol_locations.address3,"")) as address, organizations.name as org_name,organization_types.type as org_type, cities.City as city,states.name as state,kol_locations.postal_code,kol_locations.is_primary,kol_locations.address_type,kol_locations.private_practice,kol_locations.created_by,kol_locations.data_type_indicator,client_users.client_id,CONCAT(COALESCE(client_users.first_name,"")," ",COALESCE(client_users.last_name,"")) as created_by_full_name', false);
		$this->db->where('kol_id', $kolId);
		$this->db->join('client_users', 'kol_locations.created_by = client_users.id','left');
		$this->db->join('states', 'kol_locations.state_id = states.id', 'left');
		$this->db->join('organizations', 'kol_locations.org_institution_id = organizations.id', 'left');
		$this->db->join('organization_types', 'organizations.type_id = organization_types.id', 'left');
		$this->db->join('cities', 'kol_locations.city_id = cities.CityId', 'left');
		$this->db->order_by('is_primary', "DESC");
		$res = $this->db->get('kol_locations');
		return $res->result_array();
	}
	function getStaffs($id, $type) {
		$this->db->select('organizations.name as loc_name,staffs.*,kol_locations.address1,staff_title.name as staff_title,phone_type.name as phone_type,staffs.data_type_indicator,client_users.client_id,CONCAT(COALESCE(client_users.first_name,"")," ",COALESCE(client_users.last_name,"")) as created_by_full_name',false);
		//            $this->db->where('contact',$id);
		//            $this->db->where('contact_type',$type);
		if ($type == 'location') {
			$this->db->where('location_id', $id);
		}
		if ($type == 'kol') {
			$this->db->where('staffs.contact', $id);
			$this->db->where('contact_type !=', 'organization');
		}
		//			$this->db->where('location_id',$id);
		$this->db->join('client_users', 'staffs.created_by = client_users.id','left');
		$this->db->join("staff_title", "staff_title.id = staffs.title", "left");
		$this->db->join("phone_type", "phone_type.id = staffs.phone_type", "left");
		$this->db->join('kol_locations', 'kol_locations.id = staffs.location_id', 'left');
		$this->db->join('organizations', 'organizations.id = kol_locations.org_institution_id', 'left');
		$res = $this->db->get('staffs');
		$arrData = array();
		foreach ($res->result_array() as $row) {
			if ($row['loc_name'] == '')
				$row['loc_name'] = $row['address1'];
				
				$arrData[] = $row;
		}
		return $arrData;
	}
	
	function getPhones($id, $type) {
		$this->db->select('organizations.name,phone_numbers.*,kol_locations.address1,phone_type.name as phone_type,phone_numbers.data_type_indicator,client_users.client_id,CONCAT(COALESCE(client_users.first_name,"")," ",COALESCE(client_users.last_name,"")) as created_by_full_name',false);
		//            $this->db->where('contact',$id);
		//            $this->db->where('contact_type',$type);
		if ($type == 'location') {
			$this->db->where('location_id', $id);
		}
		if ($type == 'kol') {
			$this->db->where('phone_numbers.contact', $id);
			$this->db->where('contact_type !=', 'organization');
		}
		$this->db->join('client_users', 'phone_numbers.created_by = client_users.id','left');
		$this->db->join("phone_type", "phone_type.id = phone_numbers.type", "left");
		$this->db->join('kol_locations', 'kol_locations.id = phone_numbers.location_id', 'left');
		$this->db->join('organizations', 'organizations.id = kol_locations.org_institution_id', 'left');
		$res = $this->db->get('phone_numbers');
		$arrData = array();
		foreach ($res->result_array() as $row) {
			if ($row['name'] == '')
				$row['name'] = $row['address1'];
				
				$arrData[] = $row;
		}
		//print $this->db->last_query();
		//exit();
		return $arrData;
	}
	
	function getEmails($id) {
		$this->db->select('emails.*,client_users.client_id,CONCAT(COALESCE(client_users.first_name,"")," ",COALESCE(client_users.last_name,"")) as created_by_full_name',false);
		$this->db->join('client_users', 'emails.created_by = client_users.id','left');
		$this->db->where('emails.contact', $id);
		$res = $this->db->get('emails');
		return $res->result_array();
	}
	
	function getStateLicences($id) {
		$this->db->select('state_licenses.*,states.name as state_name,client_users.client_id,CONCAT(COALESCE(client_users.first_name,"")," ",COALESCE(client_users.last_name,"")) as created_by_full_name',false);
		$this->db->join('client_users', 'state_licenses.created_by = client_users.id','left');
		$this->db->join('states', 'states.id = state_licenses.region', 'left');
		$this->db->where('state_licenses.contact', $id);
		$res = $this->db->get('state_licenses');
		return $res->result_array();
	}
	function checkDuplicateOL($rowData) {
		$arrResults = array();
		$this->db->select('kols.id,salutation,first_name,middle_name,last_name,org_id,kols.status,kols.profile_type,cities.City as city, states.name as state,specialties.specialty,kols.postal_code');
		$this->db->join('cities', 'kols.city_id = cities.CityId', 'left');
		$this->db->join('states', 'kols.state_id = states.id', 'left');
		$this->db->join('specialties', 'kols.specialty= specialties.id', 'left');
		$this->db->where('kols.first_name', $rowData['first_name']);
		$this->db->where('kols.last_name', $rowData['last_name']);
		if ($rowData['kol_id'] != '')
			$this->db->where('kols.id !=', $rowData['kol_id']);
		if (isset($rowData['specialty']))
			$this->db->where('kols.specialty', $rowData['specialty']);
		if (isset($rowData['city_id']) && $rowData['city_id'] != '')
			$this->db->where('kols.city_id', $rowData['city_id']);
		if (isset($rowData['state_id']) && $rowData['state_id'] != '')
			$this->db->where('kols.state_id', $rowData['state_id']);
		$results = $this->db->get('kols');
		if (is_object($results) && $results->num_rows() > 0)
			$arrResults = $results->result_array();
		return $arrResults;
	}
	function deleteKolProducts($kolId) {
		$this->db->where('kol_id', $kolId);
		$this->db->delete('kol_products');
	}
	function insertKolProducts($kolId, $arrProducts) {
		foreach ($arrProducts as $value) {
			$this->db->insert('kol_products', array("kol_id" => $kolId,
					"product_id" => $value));
		}
	}
	function insertKolSubSpecialty($kolId, $arrProducts) {
		foreach ($arrProducts as $value) {
			$this->db->insert('kol_sub_specialty', array("kol_id" => $kolId,
					"kol_sub_specialty_id" => $value));
		}
	}
	function deleteKolSpeakerProducts($kolId) {
		$this->db->where('kol_id', $kolId);
		$this->db->delete('ol_speaker_product');
	}
	function insertKolSpakerProducts($kolId, $arrProducts) {
		foreach ($arrProducts as $value) {
			$this->db->insert('ol_speaker_product', array("kol_id" => $kolId,
					"product_id" => $value));
		}
	}
	function updateKolInfo($arrKolData, $kolId) {
		$this->db->where('id', $kolId);
		if ($this->db->update('kols', $arrKolData)) {
			return true;
		} else {
			return false;
		}
	}
	function saveKolSpecialty($arrData){
		$checkSpecialty = $this->check_kolSpecialty($arrData['kol_sub_specialty_id'],$arrData['kol_id'],$arrData['priority']);
		if (!$checkSpecialty) {
			if($arrData['priority']==1){
				$this->db->where('id', $arrData['kol_id']);
				$this->db->update('kols', array('specialty' => $arrData['kol_sub_specialty_id']));
			}
			$this->db->insert('kol_sub_specialty',$arrData);
		}else{
			if($arrData['priority']==1){
				$get = $this->db->get_where("kol_sub_specialty",array("kol_id"=>$arrData['kol_id'],"priority"=>$arrData['priority']));
				if($get->num_rows()>0){
					$row = $get->row();
					if (isset($row))
					{
						$this->db->where('id', $row->id);
						$this->db->update('kol_sub_specialty', array("kol_sub_specialty_id"=>$arrData['kol_sub_specialty_id']));
					}
				}
			}
		}
	}
	function deleteStateLicense($id,$kolId) {
		$this->db->where('id', $id);
		if ($query = $this->db->delete('state_licenses')) {
			return true;
		}else{
			return true;
		}
	}
	// to check weather specialty aligned to kol with priority
	function check_kolSpecialty($specialty,$kol_id,$priority){
		$get = $this->db->get_where("kol_sub_specialty",array("kol_sub_specialty_id"=>$specialty,"kol_id"=>$kol_id,"priority"=>$priority));
		if($get->num_rows()>0){
			return true;
		}else{
			return false;
		}
	}
	function updateContactRestrictions($arrContactData, $id) {
		$this->db->where('contact', $id);
		if ($this->db->update('contact_restrictions', $arrContactData)) {
			return true;
		} else {
			return false;
		}
	}
	function getContactRestrictions($kolId) {
		$this->db->select('contact_restrictions.*');
		$this->db->where('contact', $kolId);
		$res = $this->db->get('contact_restrictions');
		return $res->result_array();
	}
	function getKolLocationByOrgInstId($arrLocation){
		$data = '';
		$this->db->where('org_institution_id', $arrLocation['org_institution_id']);
		$this->db->where('kol_id', $arrLocation['kol_id']);
		$query = $this->db->get('kol_locations');
		if ($query->num_rows() > 0) {
			$rowInfo = $query->row();
			$data = $rowInfo->id;
			return $data;
		} else {
			return false;
		}
	}
	function updateKolPrimaryLocation($arrLocationData, $kolId) {
		$this->db->where('kol_id', $kolId);
		$this->db->update('kol_locations', array('is_primary' => "0"));
		if ($arrLocationData["is_primary"] == "1" && $arrLocationData["title"] > 0) {
			$title_id = array('title' => $arrLocationData["title"],'division' => $arrLocationData["division"]);
			$this->db->where('id', $kolId);
			$this->db->update('kols', $title_id);
		}
		$this->db->where('kol_id', $kolId);
		$this->db->where('org_institution_id', $arrLocationData['org_institution_id']);
		if ($this->db->update('kol_locations', $arrLocationData)) {
			return true;
		} else {
			return false;
		}
	}
	//Function to save association or disassociation KOLs to particular client
	function saveKolClientAssociation($arrAssociationData){
		$associationFlag = $arrAssociationData['associationFlag'];
		$kolId = $arrAssociationData['kol_id'];
		$clientId = $arrAssociationData['client_id'];
		$status = '';
		if($associationFlag == 'associate'){
			if (strpos($kolId, ',') !== false) {
				$arrKolIds = explode(",",$kolId);
				foreach($arrKolIds as $kolId){
					$arrReturnData = $this->getKolProfileType($kolId);
					$arrData['profile_type'] = $arrReturnData[0]['profile_type'];
					$arrData['kol_id'] = $kolId;
					$arrData['client_id'] = $clientId;
					$arrData['is_visible'] = '1';
					
					$this->db->where($arrData);
					$query = $this->db->get('kols_client_visibility');
					$result = $query->result_array();
					if(sizeof($result)==0){
						if($this->db->insert('kols_client_visibility',$arrData)){
							$status = 'success';
						}else{
							$status = 'fail';
						}
					}
				}
			}else{
				$arrReturnData = $this->getKolProfileType($kolId);
				$arrData['profile_type'] = $arrReturnData[0]['profile_type'];
				$arrData['kol_id'] = $kolId;
				$arrData['client_id'] = $clientId;
				$arrData['is_visible'] = '1';
				$this->db->where($arrData);
				$query = $this->db->get('kols_client_visibility');
				$result = $query->result_array();
				$this->db->where($arrData);
				$query = $this->db->get('kols_client_visibility');
				$result = $query->result_array();
				if(sizeof($result)==0){
					if($this->db->insert('kols_client_visibility',$arrData)){
						$status = 'success';
					}else{
						$status = 'fail';
					}
				}
			}
		}else{
			if (strpos($kolId, ',') !== false) {
				$arrKolIds = explode(",",$kolId);
				foreach($arrKolIds as $kolId){
					$this->db->where('kol_id', $kolId);
					$this->db->where('client_id', $clientId);
					if($this->db->delete('kols_client_visibility')){
						$status = 'success';
						
					}else{
						$status = 'fail';
					}
				}
			}else{
				$this->db->where('kol_id', $kolId);
				$this->db->where('client_id', $clientId);
				if($this->db->delete('kols_client_visibility')){
					$status = 'success';
				}else{
					$status = 'fail';
				}
			}
			
		}
		return $status;
	}
	//Gets the profile type of a KOL for given KOL id
	function getKolProfileType($kolId){
		$this->db->select('profile_type');
		$this->db->where('id',$kolId);
		$arrResult = $this->db->get('kols');
		return $arrResult->result_array();
	}
	function updateOlEmail($arrEmailData){
		$this->db->where("is_primary", 1);
		$this->db->where("email", $arrEmailData['email']);
		$this->db->where("contact", $arrEmailData['contact']);
		$results = $this->db->get("emails");
		if($results->num_rows() > 0){
			return false;
		}else{
			$this->db->where("is_primary", 1);
			$this->db->where("contact", $arrEmailData['contact']);
			$arrEmails = $this->db->get("emails");
			foreach ($arrEmails->result_array() as $row) {
				$id = $row['id'];
			}
			if (isset($id)) {
				$this->db->where("id", $id);
				$this->db->update("emails", $arrEmailData);
			} else {
				$dataType = 'User Added';
				$client_id =$this->session->userdata('client_id');
				if($client_id == INTERNAL_CLIENT_ID){
					$dataType = 'Aissel Analyst';
				}
				$arrEmailData['data_type_indicator'] = $dataType;
				$this->db->insert("emails", $arrEmailData);
				$lastEmailId = $this->db->insert_id();
			}
			return true;
		}
	}
	function getKoPhoneByLocationId($arrPhone){
		$data = '';
		$this->db->where('location_id', $arrPhone['location_id']);
		$this->db->where('contact', $arrPhone['contact']);
		$query = $this->db->get('phone_numbers');
		if ($query->num_rows() > 0) {
			$rowInfo = $query->row();
			$data = $rowInfo->id;
			return $data;
		} else {
			return false;
		}
	}
	function updateOlPhone($arrPhoneData) {
		$id = '';
		$this->db->where('contact', $arrPhoneData['contact']);
		$this->db->update('phone_numbers', array('is_primary' => "0"));
		
		$this->db->where("location_id", $arrPhoneData['location_id']);
		$this->db->where("contact", $arrPhoneData['contact']);
		$arrPhone = $this->db->get("phone_numbers");
		foreach ($arrPhone->result_array() as $row) {
			$id = $row['id'];
		}
		if(isset($id)){
			$this->db->where("id", $id);
			$this->db->update("phone_numbers", $arrPhoneData);
		}else{
			$dataType = 'User Added';
			$client_id =$this->session->userdata('client_id');
			if($client_id == INTERNAL_CLIENT_ID){
				$dataType = 'Aissel Analyst';
			}
			$arrPhoneData['data_type_indicator'] = $dataType;
			$this->db->insert("phone_numbers", $arrPhoneData);
			$lastPhoneId = $this->db->insert_id();
		}
		return true;
	}
	function getAssignedUsers($kolId){
		$clientId = $this->session->userdata('client_id');
		$this->db->select("user_kols.id,user_kols.user_id,user_kols.data_type_indicator,client_created_by.client_id,CONCAT(client_users.first_name,' ',client_users.last_name) AS name,client_users.email,kol_user_conatct_type.name as type,CONCAT(client_created_by.first_name,' ',client_created_by.last_name) as created_by",false);
		$this->db->join('client_users','client_users.id=user_kols.user_id');
		$this->db->join('client_users as client_created_by','client_created_by.id=user_kols.created_by');
		$this->db->join('kols','kols.id = user_kols.kol_id','left');
		$this->db->join('kol_user_conatct_type','kol_user_conatct_type.id=user_kols.type','left');
		$this->db->where('user_kols.kol_id',$kolId);
		if($clientId!=INTERNAL_CLIENT_ID){
			$this->db->where("client_users.client_id",$clientId);
		}
		$arrResult = $this->db->get('user_kols');
		return $arrResult->result_array();
	}
	function listUsers($clientId){
		$arrUsers	= array();
		$group_ids = explode(',', $this->session->userdata('group_ids'));
		if($clientId!=INTERNAL_CLIENT_ID){
			$this->db->where("client_users.client_id",$clientId);
		}
		if($this->session->userdata('user_role_id')==ROLE_USER || $this->session->userdata('user_role_id')==ROLE_READONLY_USER){
			$userId	= $this->session->userdata('user_id');
			$this->db->where("client_users.id",$userId);
		}
		$this->db->where_in('client_users.status',array(ACTIVATED_USER));
		$this->db->select("client_users.id,client_users.first_name,client_users.last_name");
		if($this->session->userdata('user_role_id')==ROLE_MANAGER){
			$this->db->join('user_groups','user_groups.user_id = client_users.id');
			$this->db->where_in("user_groups.group_id",$group_ids);
			$this->db->group_by('client_users.id');
		}
		$this->db->order_by('client_users.first_name, client_users.last_name');
		$arrUserResult = $this->db->get('client_users');
		return $arrUserResult->result_array();
	}
	//Function to fetch all User type
	function getAllClientsType(){
		$this->db->select('id,name');
		$arrResultSet = $this->db->get('kol_user_conatct_type');
		return $arrResultSet->result_array();
	}
	function insertOrUpdateAssignClient($arrAssignData){
		$id=$arrAssignData['id'];
		$data['status'] = false;
		$dataType 		= 'User Added';
		$client_id 		=$this->session->userdata('client_id');
		$user_id 		=$this->session->userdata('user_id');
		if($client_id == INTERNAL_CLIENT_ID){
			$dataType = 'Aissel Analyst';
		}
		$arrData['user_id'] = $arrAssignData['client'];
		$arrData['kol_id'] 	=  $arrAssignData['kol_id'];
		$arrData['type'] 	= $arrAssignData['client_type'];
		if ($id >0) {
			$arrData['modified_by'] =$user_id;
			$arrData['modified_on'] = date('Y-m-d H:i:s');
			$this->db->where('id',$id);
			if ($this->db->update('user_kols',$arrData)){
				$data['status'] =true;
			}
		}else{
			$arrData['created_by'] = $user_id;
			$arrData['created_on'] = date('Y-m-d H:i:s');
			$arrData['data_type_indicator'] = $dataType;
			$lastAssignId = $this->db->insert('user_kols',$arrData);
			if($lastAssignId){
				$data['status'] = true;
				//$data['id'] = $lastAssignId;
			}
		}
		return $data;
	}
	function insertOrUpdateStateLicense($arrLicenseData){
		$id=$arrLicenseData['id'];
		$data['status'] = false;
		$dataType = 'User Added';
		$client_id 		=$this->session->userdata('client_id');
		$user_id 		=$this->session->userdata('user_id');
		if($client_id == INTERNAL_CLIENT_ID){
			$dataType = 'Aissel Analyst';
		}
		$arrData['state_license'] = $arrLicenseData['state_license_number'];
		$arrData['region'] = $arrLicenseData['state_id'];
		$arrData['country_id'] = $arrLicenseData['country_id'];
		$arrData['contact'] = $arrLicenseData['contact'];
		$arrData['modified_by'] = $user_id;
		$arrData['modified_on'] = date('Y-m-d H:i:s');
		if (trim($arrLicenseData['license_is_primary']) == "on")
			$arrData['is_primary'] = 1;
		else
			$arrData['is_primary'] = 0;
		$arrData['data_type_indicator'] = $dataType;
		if ($arrData['is_primary'] == "1") {
			$this->db->where('contact', $arrData['contact']);
			$this->db->update('state_licenses', array('is_primary' => "0"));
			$this->db->where('id', $arrData['contact']);
			$this->db->update('kols', array('license' => $arrData['state_license']));
		}
		if ($id>0) {
			$this->db->where('id', $id);
			if ($this->db->update('state_licenses', $arrData)) {
				$data['status']=true;
			}
		}else{
			$arrData['created_by'] = $user_id;
			$arrData['created_on'] = date('Y-m-d H:i:s');
			$lastLicenseId=$this->db->insert('state_licenses', $arrData);
			if ($lastLicenseId) {
				$data['status'] = true;
				//$data['id'] = $lastLicenseId;
			}
		}
		return $data;
	}
	function insertOrUpdatePhoneNumber($arrPhoneData){
		$id=$arrPhoneData['id'];
		$data['status'] = false;
		$dataType = 'User Added';
		$client_id =$this->session->userdata('client_id');
		$user_id 		=$this->session->userdata('user_id');
		if($client_id == INTERNAL_CLIENT_ID){
			$dataType = 'Aissel Analyst';
		}
		$arrData['type'] = trim($arrPhoneData['phone_type']);
		$arrData['number'] = trim($arrPhoneData['phone_number']);
		$arrData['contact_type'] = trim($arrPhoneData['contact_type']);
		$arrData['contact'] = trim($arrPhoneData['contact']);
		$arrData['is_primary'] = (trim($arrPhoneData['phone_is_primary']) == "on") ? "1" : "0";
		$arrData['modified_by'] =$user_id;
		$arrData['modified_on'] = date('Y-m-d H:i:s');
		$arrData['location_id'] = trim($arrPhoneData['phone_location']);
		$arrData['data_type_indicator'] = $dataType;
		if($arrData['is_primary'] == 1){
			$data['details'] = $arrData;
		}else{
			$data['details'] = '';
		}
		if ($arrData['is_primary'] == "1") {
			$this->db->where('contact', $arrData['contact']);
			$this->db->update('phone_numbers', array('is_primary' => "0"));
			
			if ($arrData['contact_type'] == "kol") {
				$this->db->where('id', $arrData['contact']);
				$this->db->update('kols', array('primary_phone' => $arrData['number']));
			}
			if ($arrData['contact_type'] == "organization") {
				$this->db->where('id', $arrData['contact']);
				$this->db->update('organizations', array('phone' => $arrData['number']));
			}
		}
		if ($id != "" || $id != null) {
			$this->db->where('id', $id);
			if ($this->db->update('phone_numbers', $arrData)) {
				$data['status'] = true;
			} else {
				$data['status'] = false;
			}
		} else {
			$arrData['created_by'] = $user_id;
			$arrData['created_on'] = date('Y-m-d H:i:s');
			if ($this->db->insert('phone_numbers', $arrData)) {
			 	$lastPhoneId =$this->db->insert_id();
			} else {
				$lastPhoneId =false;
			}
			if ($lastPhoneId) {
				$data['status'] = true;
				$data['id'] = $lastPhoneId;
			}
		}
		return $data;
	}
	function insertOrUpdateEmail($arrEmailData){
		$id=$arrEmailData['id'];
		$data['status'] = false;
		$dataType = 'User Added';
		$client_id =$this->session->userdata('client_id');
		$user_id 		=$this->session->userdata('user_id');
		if($client_id == INTERNAL_CLIENT_ID){
			$dataType = 'Aissel Analyst';
		}
		$data=array();
		$arrData['type'] = trim($arrEmailData['email_type']);
		$arrData['email'] = trim($arrEmailData['email']);
		if (trim($arrEmailData['email_is_primary']) == "on"){
			$arrData['is_primary'] = 1;
			$data['details'] = $arrData['email'];
		}else{
			$arrData['is_primary'] = 0;
			$data['details'] = '';
		}
		$arrData['contact'] = trim($arrEmailData['contact']);
		$arrData['contact_type'] = trim($arrEmailData['contact_type']);
		$arrData['modified_by'] = $user_id;
		$arrData['modified_on'] = date('Y-m-d H:i:s');
		$arrData['data_type_indicator'] = $dataType;
		
		$contact_type = $arrData['contact_type'];
		unset($arrData['contact_type']);
		if ($arrData['is_primary'] == "1") {
			$this->db->where('contact', $arrData['contact']);
			$this->db->update('emails', array('is_primary' => "0"));
			if($contact_type == "kol") {
				$this->db->where('id', $arrData['contact']);
				$this->db->update('kols', array('primary_email' => $arrData['email']));
			}
		}
		if ($id>0) {
			$this->db->where('id', $id);
			if($this->db->update('emails', $arrData)){
				$data['status']=true;
			}
		} else {
			$arrData['created_by'] = $user_id;
			$arrData['created_on'] = date('Y-m-d H:i:s');
			if ($this->db->insert('emails', $arrData)) {
				$lastEmailId = $this->db->insert_id();
			} else {
				$lastEmailId = false;
			}
			if ($lastEmailId) {
				$data['status'] = true;
				$data['id'] = $lastEmailId;
			}
		}
		return $data;
	}
	function getAllLocationsByKolId($kolId) {
		$this->db->select('organizations.name,kol_locations.*', false);
		$this->db->join('organizations', 'kol_locations.org_institution_id = organizations.id','left');
		$this->db->where('kol_locations.kol_id', $kolId);
		$this->db->order_by('is_primary',"DESC");
		$result = $this->db->get('kol_locations');
		$arr = array();
		foreach ($result->result_array() as $row) {
			if ($row['name'] != '')
				$arr[$row['id']] = $row['name'];
			else
				$arr[$row['id']] = $row['address1'];
		}
		return $arr;
	}
	function getStaffTitle(){
		$arrDatas=array();
		$arrStaffTitle = $this->db->get('staff_title');
		foreach($arrStaffTitle->result_array() as $row){
			$arrDatas[$row['id']]	= $row['name'];
		}
		return $arrDatas;
	}
	function insertOrUpdateStaff($arrStaffData){
		$id=$arrStaffData['id'];
		$data['status'] = false;
		$dataType = 'User Added';
		$client_id =$this->session->userdata('client_id');
		$user_id 		=$this->session->userdata('user_id');
		if($client_id == INTERNAL_CLIENT_ID){
			$dataType = 'Aissel Analyst';
		}
		$arrData['title'] = trim($arrStaffData['staff_title']);
		$arrData['name'] = trim($arrStaffData['staff_name']);
		$arrData['phone_number'] = trim($arrStaffData['staff_phone']);
		$arrData['phone_type'] = trim($arrStaffData['phone_type']);
		$arrData['email'] = trim($arrStaffData['email']);
		$arrData['contact_type'] = trim($arrStaffData['contact_type']);
		$arrData['contact'] = trim($arrStaffData['contact']);
		$arrData['modified_by'] = $user_id;
		$arrData['modified_on'] = date('Y-m-d H:i:s');
		$arrData['location_id'] = trim($arrStaffData['staff_location']);
		$arrData['data_type_indicator'] = $dataType;
		if ($id >0) {
			$this->db->where('id', $id);
			if ($this->db->update('staffs', $arrData)) {
				$data['status'] = true;
			}
		}else{
			$arrData['created_by'] =$user_id;
			$arrData['created_on'] = date('Y-m-d H:i:s');
			if ($this->db->insert('staffs', $arrData)) {
				$lastStaffId = $this->db->insert_id();
			}
			if ($lastStaffId) {
				$data['status'] = true;
				$data['id'] = $lastStaffId;
			}
		}
		return $data;
	}
	function saveLocation($arrLocation) {
		if (isset($arrLocation['id'])) {
			$id = $arrLocation['id'];
			unset($arrLocation['id']);
			
			if ($arrLocation["is_primary"] == "1") {
				$primary_flag = array('is_primary' => 0);
				$this->db->where('kol_id', $arrLocation['kol_id']);
				$this->db->update('kol_locations', $primary_flag);
			}
			if ($arrLocation["is_primary"] == "1" && $arrLocation["title"] > 0 && $arrLocation["division"] > 0) {
				$title_id = array('title' => $arrLocation["title"],'division' => $arrLocation["division"]);
				$this->db->where('id', $arrLocation['kol_id']);
				$this->db->update('kols', $title_id);
			}
			
			$this->db->where('id', $id);
			if ($this->db->update('kol_locations', $arrLocation)) {
				return true;
			} else {
				return false;
			}
		} else {
			$dataType = 'User Added';
			$client_id =$this->session->userdata('client_id');
			if($client_id == INTERNAL_CLIENT_ID){
				$dataType = 'Aissel Analyst';
			}
			$arrLocation['data_type_indicator'] = $dataType;
			if ($arrLocation["is_primary"] == "1") {
				$primary_flag = array('is_primary' => 0);
				$this->db->where('kol_id', $arrLocation['kol_id']);
				$this->db->update('kol_locations', $primary_flag);
			}
			if ($arrLocation["is_primary"] == "1" && $arrLocation["title"] > 0 && $arrLocation["division"] > 0) {
				$title_id = array('title' => $arrLocation["title"],'division' => $arrLocation["division"]);
				$this->db->where('id', $arrLocation['kol_id']);
				$this->db->update('kols', $title_id);
			}
			if ($this->db->insert('kol_locations', $arrLocation)) {
				return $this->db->insert_id();
			} else {
				return false;
			}
		}
	}
	function checkCityIfExistElseAdd($city,$stateId,$country){
		$this->db->select('CityId');
		$this->db->where('city',$city);
		$this->db->limit(1);
		$query = $this->db->get('cities');
		$result = $query->result_array();
		
		if($result['0']['CityId'] == ""){
			$data = array(
					'CountryID' => $country,
					'RegionID' => $stateId,
					'City'	=>	$city
			);
			$this->db->insert('cities', $data);
			return $this->db->insert_id();
		}else{
			return $result['0']['CityId'];
		}
	}   
	function deleteStaff($id, $typeId, $locId) {
		if ($id != "")
			$this->db->where('id', $id);
			if ($typeId != null){
				$this->db->where('contact', $typeId);
				$this->db->where('location_id', $locId);
			}
			$query = $this->db->delete('staffs');
	}
	function deletePhone($id, $typeId, $locId) {
		if ($id != "")
			$this->db->where('id', $id);
			if ($typeId != ""){
				$this->db->where('contact', $typeId);
				$this->db->where('location_id', $locId);
			}
			if ($query = $this->db->delete('phone_numbers')) {
				return true;
			}else{
				return false;
			}
	}

	function getLocationById($id) {
		$this->db->select('kol_locations.*,organizations.name as org_name');
		$this->db->join('organizations','organizations.id = kol_locations.org_institution_id');
		$this->db->where('kol_locations.id', $id);
		$res = $this->db->get('kol_locations');
		return $res->result_array();
	}
	function updateKol($arrKol) {
		if ($arrKol["is_primary"] == "1" && $arrKol["title"] > 0) {
			$title_id = array('title' => $arrKol["title"]);
			$this->db->where('id', $arrKol['id']);
			$this->db->update('kols', $title_id);
		}
		$id = $arrKol['id'];
		$this->db->where('id', $id);
		$arrKol = $this->db->update('kols', $arrKol);
		return $arrKol;
	}
	function getAdditionalDetails($tableName,$id){
		$arrData = array();
		switch($tableName){
			case 'phone_numbers' : $this->db->select("$tableName.*,kol_locations.address1 as name,phone_type.name as phone_type");
			$this->db->join("kol_locations", "kol_locations.id = $tableName.location_id", "left");
			$this->db->join("phone_type", "phone_type.id = $tableName.type", "left");
			$this->db->where("$tableName.id", $id);
			$resultSet = $this->db->get("$tableName");
			if ($resultSet->num_rows() > 0){
				$rowData = $resultSet->row();
				$arrData['id'] = $rowData->id;
				$arrData['type'] = $rowData->type;
				$arrData['name'] = $rowData->name;
				$arrData['number'] = $rowData->number;
				$arrData['is_primary'] = $rowData->is_primary;
				$arrData['location_id'] = $rowData->location_id;
			}
			break;
			case 'staffs' : $this->db->select("$tableName.*,staff_title.name as staff_title,phone_type.name as phone_type,phone_type.id as type_id");
			$this->db->join("staff_title", "staff_title.id = $tableName.title", "left");
			$this->db->join("phone_type", "phone_type.id = $tableName.phone_type", "left");
			$this->db->where("$tableName.id", $id);
			$resultSet = $this->db->get("$tableName");
			if ($resultSet->num_rows() > 0){
				$rowData = $resultSet->row();
				$arrData['id'] = $rowData->id;
				$arrData['title'] = $rowData->title;
				$arrData['name'] = $rowData->name;
				$arrData['number'] = $rowData->phone_number;
				$arrData['email'] = $rowData->email;
				$arrData['type'] = $rowData->type_id;
				$arrData['is_primary'] = $rowData->is_primary;
				$arrData['location_id'] = $rowData->location_id;
			}
			break;
			case 'emails' : $this->db->select("$tableName.*");
			$this->db->where("$tableName.id", $id);
			$resultSet = $this->db->get("$tableName");
			if ($resultSet->num_rows() > 0){
				$rowData = $resultSet->row();
				$arrData['id'] = $rowData->id;
				$arrData['type'] = $rowData->type;
				$arrData['email'] = $rowData->email;
				$arrData['is_primary'] = $rowData->is_primary;
			}
			break;
			case 'state_licenses' : $this->db->select("$tableName.*");
			$this->db->where("$tableName.id", $id);
			$resultSet = $this->db->get("$tableName");
			if ($resultSet->num_rows() > 0){
				$rowData = $resultSet->row();
				$arrData['id'] = $rowData->id;
				$arrData['state_license'] = $rowData->state_license;
				$arrData['region'] = $rowData->region;
				$arrData['country_id'] = $rowData->country_id;
				$arrData['is_primary'] = $rowData->is_primary;
			}
			break;
			case 'user_kols' : $this->db->select("$tableName.*");
			$this->db->where("$tableName.id", $id);
			$resultSet = $this->db->get("$tableName");
			if ($resultSet->num_rows() > 0){
				$rowData = $resultSet->row();
				$arrData['id'] = $rowData->id;
				$arrData['user_id'] = $rowData->user_id;
				$arrData['kol_id'] = $rowData->kol_id;
				$arrData['type'] = $rowData->type;
			}
			break;
		}
		return $arrData;
	}

	function getKolModifiedDate($kolId){
		$this->db->select('modified_on');
		$this->db->where('id',$kolId);
		$result = $this->db->get('kols');
		foreach ($result->result_array() as $res){
			$modifiedDate = $res['modified_on'];
		}
		return $modifiedDate;
	}
	function deleteEventById($id) {
		$this->db->where('id', $id);
		$this->db->select('kol_id,type');
		$this->db->where('id',$id);
		$queryRes = $this->db->get('kol_events');
		$row = $queryRes->row();
		if (isset($row))
		{
			$kolId = $row->kol_id;
			$type = $row->type;
		}
		$this->db->where('id',$id);
		if ($query = $this->db->delete('kol_events')){
			return true;
		}else{
			return false;
		}
	}
	function deleteMembership($id) {
		$this->db->select('kol_id,type');
		$this->db->where('id',$id);
		$queryRes = $this->db->get('kol_memberships');
		$row = $queryRes->row();
		if (isset($row))
		{
			$kolId = $row->kol_id;
			$type = $row->type;
		}
		$this->db->where('id', $id);
		if ($query = $this->db->delete('kol_memberships')) {
			return true;
		} else {
			return false;
		}
	}
	function listMemberships($type = null, $kolId = null, $startFrom = null, $limit = null) {
		$arrMembershipDetails = array();
		if ($kolId != null && $type != null) {
			$this->db->select(array('kol_memberships.*', 'institutions.name', 'engagement_types.engagement_type','CONCAT(COALESCE(client_users.first_name,"")," ",COALESCE(client_users.last_name,"")) as created_by_full_name'));
			$this->db->join('institutions', 'institutions.id = kol_memberships.institute_id', 'left');
			$this->db->join('client_users', 'kol_memberships.created_by = client_users.id','left');
			$this->db->where('kol_id', $kolId);
			if ($type != 'all') {
				$this->db->where('type', $type);
			}
			$this->db->join('engagement_types', 'engagement_types.id = kol_memberships.engagement_id', 'left');
		}
		if ($limit != null) {
			$this->db->limit($limit, $startFrom);
		}
		$this->db->order_by('engagement_types.engagement_type','asc');
		$this->db->order_by('kol_memberships.start_date','desc');
		if ($arrMembershipDetailResult = $this->db->get('kol_memberships')) {
			if ($arrMembershipDetailResult->num_rows() == 0) {
				return false;
			}
			foreach ($arrMembershipDetailResult->result_array() as $arrMembership) {
				$arrMembership['institute_id'] = $arrMembership['name'];
				if ($arrMembership['url1'] != '') {
					$arrMembership['url1'] = '<a href=\'' . $arrMembership['url1'] . '\' target="_new">URL 1</a>';
				}if ($arrMembership['url2'] != '') {
					$arrMembership['url2'] = '<a href=\'' . $arrMembership['url2'] . '\' target="_new">URL 2</a>';
				}
				$arrMembership['engagement_id'] = ($arrMembership['engagement_type'] != null) ? $arrMembership['engagement_type'] : '';
					$arrMembershipDetails[] = $arrMembership;
			}
			return $arrMembershipDetails;
		} else {
			return false;
		}
	}
	function getAffiliationsByParam($fromYear, $toYear, $arrKolIds = 0, $arrEngTypes = '', $arrOrgType = '', $arrCountries = 0, $arrSpecialities = 0, $selectType, $arrListNamesIds = 0, $arrStates = 0, $arrProfileType = '', $viewType=array(), $arrGlobalRegionIds = 0, $analystSelectedClientId = 0,$fromType=0) {
		$arrAffiliationsDetail = array();
		$isKolsJoined = false;
		
		$referer = $_SERVER['HTTP_REFERER'];
		$arrSegments = explode('/', $referer);
		$referer = $arrSegments[sizeof($arrSegments) - 1];
		
		$countType = 'kol_memberships.id';
		if ($referer == 'affiliations' || $referer == 'affiliations#')
			$countType = 'kol_memberships.institute_id';
			
			$this->db->select($selectType . ',count(DISTINCT ' . $countType . ') as count');
			if ($fromYear != 0 && $toYear != 0) {
				//	$this->db->where("(kol_memberships.start_date between  ".$fromYear."  and  ".$toYear."  or kol_memberships.start_date=0)");
				$this->db->where("((kol_memberships.start_date between  " . $fromYear . "  and  " . $toYear . ") or (kol_memberships.start_date=0 or kol_memberships.start_date=''))");
			}
			$this->db->join('engagement_types', 'engagement_types.id=kol_memberships.engagement_id', 'left');
			
			//Adding where condition for KolId if Exist
			if (is_array($arrKolIds) && sizeof($arrKolIds)>0) {
				$this->db->where_in('kol_memberships.kol_id', $arrKolIds);
			} else if ($arrKolIds != 0) {
				$this->db->where('kol_memberships.kol_id', $arrKolIds);
			}
			
			//Adding where condition for EngTypeId if Exist
			if (is_array($arrEngTypes)) {
				if (is_numeric($arrEngTypes[0]))
					$this->db->where_in('engagement_types.id', $arrEngTypes);
					else
						$this->db->where_in('engagement_types.engagement_type', $arrEngTypes);
			}else if ($arrEngTypes != '') {
				if (is_numeric($arrEngTypes))
					$this->db->where('engagement_types.id', $arrEngTypes);
					else
						$this->db->where('engagement_types.engagement_type', $arrEngTypes);
			}
			
			//Adding where condition for OrgType if Exist
			if (is_array($arrOrgType)) {
				$this->db->where_in('kol_memberships.type', $arrOrgType);
			} else if ($arrOrgType != '') {
				$this->db->where('kol_memberships.type', $arrOrgType);
			}
			
			//Adding where condition for Gobal Region's if Exist
			if (is_array($arrGlobalRegionIds)) {
				$this->db->join('kols', 'kols.id=kol_memberships.kol_id', 'left');
				$isKolsJoined = true;
				$this->db->join('countries','kols.country_id=countries.countryId','left');
				$this->db->where_in('countries.GlobalRegion', $arrGlobalRegionIds);
			} else if ($arrGlobalRegionIds != 0) {
				$this->db->join('kols', 'kols.id=kol_memberships.kol_id', 'left');
				$isKolsJoined = true;
				$this->db->join('countries','kols.country_id=countries.countryId','left');
				$this->db->where('countries.GlobalRegion', $arrGlobalRegionIds);
			}
			
			//Adding where condition for Country's if Exist
			if (is_array($arrCountries)) {
				if (!$isKolsJoined)
					$this->db->join('kols', 'kols.id=kol_memberships.kol_id', 'left');
					$isKolsJoined = true;
					$this->db->where_in('kols.country_id', $arrCountries);
			} else if ($arrCountries != 0) {
				if (!$isKolsJoined)
					$this->db->join('kols', 'kols.id=kol_memberships.kol_id', 'left');
					$isKolsJoined = true;
					$this->db->where('kols.country_id', $arrCountries);
			}
			if (is_array($arrStates)) {
				if (!$isKolsJoined)
					$this->db->join('kols', 'kols.id=kol_memberships.kol_id', 'left');
					$isKolsJoined = true;
					$this->db->where_in('kols.state_id', $arrStates);
			}else if ($arrStates != 0) {
				if (!$isKolsJoined)
					$this->db->join('kols', 'kols.id=kol_memberships.kol_id', 'left');
					$isKolsJoined = true;
					$this->db->where('kols.state_id', $arrStates);
			}
			
			//Adding where condition for Speciality's if Exist
			if (is_array($arrSpecialities)) {
				if (!$isKolsJoined)
					$this->db->join('kols', 'kols.id=kol_memberships.kol_id', 'left');
					$isKolsJoined = true;
					$this->db->where_in('kols.specialty', $arrSpecialities);
			}else if ($arrSpecialities != 0) {
				if (!$isKolsJoined)
					$this->db->join('kols', 'kols.id=kol_memberships.kol_id', 'left');
					$isKolsJoined = true;
					$this->db->where('kols.specialty', $arrSpecialities);
			}
			
			if ($arrListNamesIds != '' && $arrListNamesIds != 0) {
				$userId = $this->session->userdata('user_id');
				$clientId = $this->session->userdata('client_id');
				$this->db->join('list_kols', 'list_kols.kol_id=kol_memberships.kol_id', 'left');
				$this->db->join('list_names', 'list_kols.list_name_id=list_names.id', 'left');
				$this->db->join('list_categories', 'list_names.category_id=list_categories.id', 'left');
				$this->db->where_in('list_names.id', $arrListNamesIds);
				$this->db->where("(list_categories.client_id=$clientId and (list_categories.user_id=$userId or list_categories.is_public=1 ))");
			}
			if($fromType=='1'){
				if ($arrProfileType != ''){
					$this->db->where('kols.profile_type', $arrProfileType);
				}
			}else{
				if ($arrProfileType != ''){
					if($arrProfileType == DISCOVERY){
						$this->db->where('(kols.imported_as = 2 or kols.imported_as = 3)', null, false);
					}else{
						$this->db->where('kols.profile_type', $arrProfileType);
						$this->db->where('(kols.imported_as IS NULL or kols.imported_as = 2 or kols.imported_as = 0 or kols.imported_as = 3)', null, false);
					}
					//             $this->db->where('kols.profile_type', $arrProfileType);
				}else{
					$this->db->where('(kols.imported_as IS NULL or kols.imported_as = 2 or kols.imported_as = 0 or kols.imported_as = 3)', null, false);
				}
			}
			$this->db->where('(kols.deleted_by is null or kols.deleted_by=0)','',false);
			if (sizeof($viewType) > 0) {
				$this->db->where_in('kols.id', $viewType);
			}
			//$this->db->where_not_in('kol_memberships.type','others');
			// even if the records with KOL ID = 0 or type = 0 then it should be ignored
			$this->db->where_not_in('type', array('0',''));
			$this->db->where('kol_memberships.kol_id>',0,false);
			if (!$isKolsJoined)
				$this->db->join('kols', 'kols.id=kol_memberships.kol_id', 'left');
				
				
				//$this->db->where('kols.status',COMPLETED);// To Load Charts Comment on Profile Request as well Non Profile Request
				
				
				$this->db->group_by($selectType);
				
				if($analystSelectedClientId != null || $analystSelectedClientId > 0){
					$client_id = $analystSelectedClientId;
				}else{
					$client_id = $this->session->userdata('client_id');
				}
				if($client_id !== INTERNAL_CLIENT_ID){
					$this->db->join('kols_client_visibility', 'kols_client_visibility.kol_id = kols.id', 'left');
					$this->db->where('kols_client_visibility.client_id', $client_id);
				}
				
				$arrAffiliations = $this->db->get('kol_memberships');
				//         pr($this->db->last_query());exit;
				foreach ($arrAffiliations->result_array() as $row) {
					$arrAffiliationsDetail[] = $row;
				}
				return $arrAffiliationsDetail;
	}
	function getEventsByRoleByKolId($kolId, $startDate, $endDate, $roleName) {
		$this->db->select("kol_events.*,events.name");
		$this->db->join('events', 'events.id = kol_events.event_id', 'left');
		$this->db->where('kol_id', $kolId);
		$this->db->where('kol_events.role', $roleName);
		$this->db->where("(year(kol_events.start) between  " . $startDate . "  and  " . $endDate . "  or year(kol_events.start)=0)");
		$arrResult = $this->db->get('kol_events');
		foreach ($arrResult->result_array() as $row) {
			$arrEvents[] = $row;
		}
		return $arrEvents;
	}
	
	
	function getEventTypesCountByTimeLine($fromYear = 0, $toYear = 0, $arrKolIds = 0, $arrCountries = 0, $arrSpecialities = 0, $arrListNamesIds = 0, $arrStatesIds = 0, $profileType, $viewType, $arrGlobalRegionIds = 0) {
		
		$sqlQuery = "SELECT conf_event_types.event_type, COUNT(distinct kol_events.event_id) as count, YEAR(kol_events.start) as year
										FROM kol_events
										LEFT JOIN conf_event_types ON conf_event_types.id=kol_events.event_type
										LEFT JOIN kols ON kols.id=kol_events.kol_id
        								left join countries on kols.country_id=countries.countryId
										";
		
		$client_id = $this->session->userdata('client_id');
		if($client_id !== INTERNAL_CLIENT_ID){
			$sqlQuery .= "left join kols_client_visibility on kols_client_visibility.kol_id = kols.id";
		}
		if ($arrListNamesIds != '' && $arrListNamesIds != 0) {
			
			$userId = $this->session->userdata('user_id');
			$clientId = $this->session->userdata('client_id');
			
			$commaistNamesIds = $this->common_helper->convertArrayToCommaSeparatedElements($arrListNamesIds);
			$sqlQuery .= " left join list_kols on list_kols.kol_id=kol_events.kol_id
	 		                 left join  list_names on list_kols.list_name_id=list_names.id
	 		                 left join list_categories on list_names.category_id=list_categories.id
            				 ";
		}
		$sqlQuery .= " WHERE 	`kol_events`.`event_type` != 'null'
						AND YEAR(kol_events.start)!='' ";
		
		if($client_id !== INTERNAL_CLIENT_ID){
			$sqlQuery .= " AND kols_client_visibility.client_id=".$client_id;
		}
		//Adding where condition for Kol's if Exist
		if (is_array($arrKolIds)) {
			$commaKolIds = $this->common_helper->convertArrayToCommaSeparatedElements($arrKolIds);
			$sqlQuery .= " AND kol_events.kol_id IN($commaKolIds) ";
		} else if ($arrKolIds != 0) {
			$sqlQuery .= " AND kol_events.kol_id='$arrKolIds' ";
		}
		//Adding where condition for Country's if Exist
		if (is_array($arrGlobalRegionIds) && sizeof($arrGlobalRegionIds) > 0) {
			$count = sizeof($arrGlobalRegionIds);
			$i;
			$commaGlobalRegion = '';
			$seprator = '';
			foreach($arrGlobalRegionIds as $value){
				$commaGlobalRegion .= $seprator."'".$value."'";
				$seprator = ',';
				$i++;
			}
			$sqlQuery .= " AND countries.GlobalRegion IN($commaGlobalRegion) ";
		}
		//Adding where condition for Country's if Exist
		if (is_array($arrCountries) && sizeof($arrCountries) > 0) {
			$commaCountries = $this->common_helper->convertArrayToCommaSeparatedElements($arrCountries);
			$sqlQuery .= " AND kols.country_id IN($commaCountries) ";
		}
		if (is_array($arrStatesIds)) {
			$commaStates = $this->common_helper->convertArrayToCommaSeparatedElements($arrStatesIds);
			$sqlQuery .= " AND kols.state_id IN($commaStates) ";
		} else if ($arrStatesIds != 0) {
			$sqlQuery .= " AND kols.state_id='$arrStatesIds' ";
		}
		//Adding where condition for Speciality's if Exist
		if (is_array($arrSpecialities) && sizeof($arrSpecialities) > 0) {
			$arrEventDatails = array();
			$commaSpecialities = $this->common_helper->convertArrayToCommaSeparatedElements($arrSpecialities);
			$sqlQuery .= " AND kols.specialty IN($commaSpecialities) ";
		}
		//Adding where condition for Country's if Exist
		if (is_array($arrCountries) && sizeof($arrCountries) > 0) {
			$sqlQuery .= " AND kols.country_id IN($commaCountries) ";
		}
		
		if (is_array($arrStatesIds)) {
			$sqlQuery .= " AND kols.state_id IN($commaStates) ";
		} else if ($arrStatesIds != 0) {
			$sqlQuery .= " AND kols.state_id='$arrStatesIds' ";
		}
		
		//Adding where condition for Speciality's if Exist
		if (is_array($arrSpecialities) && sizeof($arrSpecialities) > 0) {
			$sqlQuery .= " AND kols.specialty IN($commaSpecialities) ";
		}
		if ($profileType != '') {
			if($profileType == DISCOVERY){
				$sqlQuery .= " AND (kols.imported_as = 2 or kols.imported_as = 3)";
			}else{
				$sqlQuery .= " AND kols.profile_type='$profileType' ";
				$sqlQuery .= " AND (kols.imported_as IS NULL or kols.imported_as = 2 or kols.imported_as = 0 or kols.imported_as = 3)";
			}
			//             $this->db->where('kols.profile_type', $arrProfileType);
		}else{
			//$sqlQuery .= " AND (kols.imported_as IS NULL or kols.imported_as = 2 or kols.imported_as = 0 or kols.imported_as = 3)";
		}
		if (isset($viewType) && sizeof($viewType) > 0) {
			$viewType = implode(",", $viewType);
			$sqlQuery .= " AND kols.id IN($viewType) ";
			//			$this->db->where_in('kols.id',$viewType);
		}
		if ($arrListNamesIds != '' && $arrListNamesIds != 0) {
			$sqlQuery .= " and list_names.id in ($commaistNamesIds) and ((list_categories.client_id=$clientId and (list_categories.user_id=$userId or list_categories.is_public=1 )))";
		}
		//Adding year range condition
		if ($fromYear != 0 && $toYear != 0)
			$sqlQuery .=" AND (YEAR(kol_events.start) BETWEEN '$fromYear' AND '$toYear' OR year(kol_events.start)=0)";
			$sqlQuery .=" GROUP BY kol_events.event_type, YEAR(kol_events.start)";
			$result = $this->db->query($sqlQuery);
			foreach ($result->result_array() as $row) {
				$arrEventDatails[] = $row;
			}
			return $arrEventDatails;
	}
	function getKolName($id) {
		$arrKol = array();
		$this->db->select('id,unique_id,salutation,first_name,middle_name,last_name,suffix,status');
		$this->db->where('id', $id);
		$arrKolNameresult = $this->db->get('kols');
		foreach ($arrKolNameresult->result_array() as $row) {
			$arrKol = $row;
		}
		return $arrKol;
		
	}
	function getCoEventedKols($arrKolIds = null) {
		//Old Querry
		$arrKols = array();
		$this->db->select("e2.kol_id, COUNT(DISTINCT e2.event_id) as count");
		$this->db->join('kol_events as e2', 'kol_events.event_id=e2.event_id', 'left');
		$this->db->join('kols', 'e2.kol_id = kols.id', 'left');
		//$where="kol_events.kol_id='$arrKolIds' AND e2.kol_id!='$arrKolIds'";
		//$this->db->where($where);
		$this->db->where('kol_events.kol_id', $arrKolIds);
		$this->db->where('e2.kol_id !=', $arrKolIds);
		//$this->db->where('kols.status', COMPLETED);
		$client_id = $this->session->userdata('client_id');
		if($client_id !== INTERNAL_CLIENT_ID){
			$this->db->join('kols_client_visibility', 'kols_client_visibility.kol_id = kols.id', 'left');
			$this->db->where('kols_client_visibility.client_id', $client_id);
		}
		$this->db->where('(kols.imported_as IS NULL or kols.imported_as = 2 or kols.imported_as = 0 or kols.imported_as = 3)', null, false);
		$this->db->where('(kols.deleted_by is null or kols.deleted_by=0)','',false);
		$this->db->group_by('e2.kol_id');
		$results = $this->db->get('kol_events');
		foreach ($results->result_array() as $row) {
			$arrKols[] = $row;
		}
		return $arrKols;
	}
	function getCoAffiliatedKols($arrKolIds = null) {
		//Old Querry
		$arrKols = array();
		$this->db->select("m2.kol_id, COUNT(DISTINCT m2.institute_id) as count");
		$this->db->join('kol_memberships as m2', 'kol_memberships.institute_id=m2.institute_id', 'left');
		$this->db->join('kols', 'm2.kol_id = kols.id', 'left');
		//$where="kol_memberships.kol_id='$arrKolIds' AND m2.kol_id!='$arrKolIds'";
		//$this->db->where($where);
		$this->db->where('kol_memberships.kol_id', $arrKolIds);
		$this->db->where('m2.kol_id !=', $arrKolIds);
		$this->db->where('kol_memberships.type', "university");
		$this->db->where('m2.type', "university");
		//$this->db->where('kols.status', COMPLETED);
		$client_id = $this->session->userdata('client_id');
		if($client_id !== INTERNAL_CLIENT_ID){
			$this->db->join('kols_client_visibility', 'kols_client_visibility.kol_id = kols.id', 'left');
			$this->db->where('kols_client_visibility.client_id', $client_id);
		}
		$this->db->where('(kols.deleted_by is null or kols.deleted_by=0)','',false);
		$this->db->where('(kols.imported_as IS NULL or kols.imported_as = 2 or kols.imported_as = 0 or kols.imported_as = 3)', null, false);
		$this->db->group_by('m2.kol_id');
		$results = $this->db->get('kol_memberships');
		foreach ($results->result_array() as $row) {
			$arrKols[] = $row;
		}
		return $arrKols;
	}
	function getCoEducatedKols($arrKolIds = null) {
		//Old Querry
		$arrKols = array();
		$this->db->select("edu2.kol_id, COUNT(DISTINCT edu2.kol_id) as count");
		$this->db->join('kol_educations as edu2', 'kol_educations.institute_id=edu2.institute_id', 'inner');
		$this->db->join('kols', 'edu2.kol_id = kols.id', 'left');
		$this->db->where('kol_educations.kol_id', $arrKolIds);
		$this->db->where('edu2.kol_id !=', $arrKolIds);
		$this->db->where('edu2.institute_id !=', 0);
		$this->db->where('kol_educations.type', 'education');
		$this->db->where('edu2.type', 'education');
		//         $this->db->where('kols.status', COMPLETED);
		$client_id = $this->session->userdata('client_id');
		if($client_id !== INTERNAL_CLIENT_ID){
			$this->db->join('kols_client_visibility', 'kols_client_visibility.kol_id = kols.id', 'left');
			$this->db->where('kols_client_visibility.client_id', $client_id);
		}
		$this->db->where('(kols.imported_as IS NULL or kols.imported_as = 2 or kols.imported_as = 0 or kols.imported_as = 3)', null, false);
		$this->db->where('(kols.deleted_by is null or kols.deleted_by=0)','',false);
		$this->db->group_by('edu2.kol_id');
		$results = $this->db->get('kol_educations');
		foreach ($results->result_array() as $row) {
			$arrKols[] = $row;
		}
		return $arrKols;
	}
	function getCoOrganizedKols($arrKolIds = null) {
		//Old Querry
		$arrKols = array();
		$client_id = $this->session->userdata('client_id');
		$this->db->select("k2.id as kol_id, COUNT(DISTINCT k2.id) as count");
		$this->db->join('kols as k2', 'kols.org_id=k2.org_id', 'inner');
		$this->db->where('kols.id', $arrKolIds);
		$this->db->where('k2.id !=', $arrKolIds);
		//         $this->db->where('kols.status', COMPLETED);
		//         $this->db->where('k2.status', COMPLETED);
		$this->db->where('kols.org_id !=', '');
		
		if($client_id !== INTERNAL_CLIENT_ID){
			$this->db->join('kols_client_visibility', 'kols_client_visibility.kol_id = k2.id', 'left');
			$this->db->where('kols_client_visibility.client_id', $client_id);
		}
		$this->db->where('(k2.imported_as IS NULL or k2.imported_as = 2 or k2.imported_as = 0 or k2.imported_as = 3)', null, false);
		$this->db->where('(kols.deleted_by is null or kols.deleted_by=0)','',false);
		
		$results = $this->db->get('kols');
		foreach ($results->result_array() as $row) {
			$arrKols[] = $row;
		}
		return $arrKols;
	}
	function getAllKolIdsSpecificToClient(){
		$clientId = $this->session->userdata('client_id');
		$arrRetData= array();
		if($clientId!=INTERNAL_CLIENT_ID){
			$this->db->select("kols_client_visibility.kol_id");
			$this->db->where("kols_client_visibility.client_id",$clientId);
			$arrKolsIdsResult = $this->db->get("kols_client_visibility");
			foreach ( $arrKolsIdsResult->result_array () as $row ) {
				$arrRetData[] = $row['kol_id'];
			}
		}else{
			$arrResult = $this->db->select("kols.id as kol_id")->from('kols')->get()->result_array();
			foreach ( $arrResult as $row ) {
				$arrRetData[] = $row['kol_id'];
			}
		}
		return $arrRetData;
	}
	function getKolCoAuthFreqency($id, $fromYear, $toYear, $arrKolName, $forInfluence = null, $keyword) {
		
		$arrSuffixes = array("Sr.", "Sr", "Jr.", "Jr", "I", "II", "III", "IV");
		$arrLastNameWords = explode(" ", $arrKolName['last_name']);
		$numWords = sizeof($arrLastNameWords);
		if ($numWords > 1) {
			$lastWord = $arrLastNameWords[$numWords - 1];
			if (in_array($lastWord, $arrSuffixes)) {
				unset($arrLastNameWords[$numWords - 1]);
				$arrKolName['last_name'] = implode(" ", $arrLastNameWords);
			}
		}
		
		$lastName = trim($arrKolName['last_name'], ".");
		$middleName = trim($arrKolName['middle_name'], ".");
		//If Fore name contains only first name
		$foreName = trim($arrKolName['first_name'], ".");
		
		$firstCharOfFMname = substr($foreName, 0, 1) . ' ' . substr($middleName, 0, 1);
		
		$FnameMnameOfFchar = $foreName . ' ' . substr($arrKolName['middle_name'], 0, 1);
		
		//If Fore name contains first name & middle name
		$foreNameFM = $arrKolName['first_name'] . ' ' . $arrKolName['middle_name'];
		;
		
		//If Fore name contains first name of First Char
		$foreNameFirstChar = substr($arrKolName['first_name'], 0, 1);
		
		//If Fore name contains Middle name of First Char
		$foreNameMiddleNameFirstChar = substr($arrKolName['middle_name'], 0, 1);
		
		//If Fore name contains first name of First Char  & middle name
		$foreNameFirstCharM = substr($arrKolName['first_name'], 0, 1) . ' ' . $arrKolName['middle_name'];
		
		$firstNameMiddleNameFirstChar = trim(substr($arrKolName['first_name'], 0, 1) . "" . substr($arrKolName['middle_name'], 0, 1));
		
		//Get KOL name combinations from the database if any
		$enteredNameCombinations = '';
		//Old method given name combinaions
		/* $commaSepNames = implode(",",$arrNameCombinations);
		 $commaSepNames = "(\"" . str_replace(",", "\",\"", $commaSepNames) . "\")";
		 $enteredNameCombinations = 'OR CONCAT(pubmed_authors.last_name," ",pubmed_authors.initials) IN '.$commaSepNames.' OR CONCAT(pubmed_authors.last_name," ",pubmed_authors.fore_name) IN'.$commaSepNames; */
		//New method, using inline query
		$enteredNameCombinations = 'OR CONCAT(pubmed_authors.last_name," ",pubmed_authors.initials) IN (select name from kol_name_combinations where kol_id =' . $id . ') OR CONCAT(pubmed_authors.last_name," ",pubmed_authors.fore_name) IN (select name from kol_name_combinations where kol_id =' . $id . ')';
		
		$arrCOAuthCount = array();
		$this->db->select('pubmed_authors.id,pubmed_authors.last_name,pubmed_authors.initials,pubmed_authors.fore_name,COUNT(distinct kol_publications.pub_id) as count');
		$this->db->join('publications', 'kol_publications.pub_id=publications.id', 'left');
		$this->db->join('publications_authors', 'publications_authors.pub_id = kol_publications.pub_id', 'left');
		$this->db->join('pubmed_authors', 'publications_authors.alias_id = pubmed_authors.id', 'left');
		if ($keyword != '') {
			$this->db->join('publication_mesh_terms', 'publication_mesh_terms.pub_id = publications.id', 'left');
			$this->db->join('pubmed_mesh_terms', 'publication_mesh_terms.term_id = pubmed_mesh_terms.id', 'left');
			$this->db->join('publication_substances', 'publication_substances.pub_id=publications.id', 'left');
			$this->db->join('pubmed_substances', 'pubmed_substances.id=publication_substances.substance_id', 'left');
			$this->db->where("(publications.article_title  LIKE '%$keyword%' OR  publications.abstract_text  LIKE '%$keyword%' OR pubmed_substances.name LIKE '%$keyword%' OR pubmed_mesh_terms.term_name LIKE '%$keyword%')");
		}
		$this->db->group_by('pubmed_authors.last_name,pubmed_authors.initials,pubmed_authors.fore_name');
		$this->db->order_by('count', 'desc');
		if (!isset($forInfluence))
			$this->db->limit('20');
			$this->db->where('kol_publications.kol_id', $id);
			$this->db->where('kol_publications.is_deleted', 0);
			$this->db->where('kol_publications.is_verified', 1);
			if ($fromYear != 0 && $toYear != 0)
				$this->db->where('year(publications.created_date) between ' . $fromYear . ' and ' . $toYear);
				$where = "!((last_name='Not Available' ) and (fore_name='Not Available'))";
				$where1 = "!((last_name=\"$lastName\" and fore_name=\"$foreName\") OR (last_name=\"$lastName\" and fore_name=\"$foreNameFM\") OR (last_name=\"$lastName\" and fore_name=\"$foreNameFirstCharM\") OR (last_name=\"$lastName\" and fore_name=\"$foreNameFirstChar\") OR (last_name=\"$lastName\" and fore_name=\"$middleName\") OR (last_name=\"$lastName\" and fore_name=\"$foreNameMiddleNameFirstChar\") OR(last_name=\"$lastName\" and fore_name=\"$firstCharOfFMname\")OR(last_name=\"$lastName\" and fore_name=\"$FnameMnameOfFchar\") OR(last_name=\"$lastName\" and initials=\"$firstNameMiddleNameFirstChar\") $enteredNameCombinations)";
				//$where="!(last_name='' and fore_name='Christopher P')";
				$this->db->where($where);
				$this->db->where($where1);
				
				$arrCOAuth = $this->db->get('kol_publications');
				foreach ($arrCOAuth->result_array() as $row) {
					$arrCOAuthCount[$row['id']] = $row;
				}
				return $arrCOAuthCount;
	}
	
	function getAllEngagementTypes(){
		$arrEngagementTypes = array();
		$this->db->order_by('engagement_type','asc');
		$arrEngagementTypesResult=$this->db->get('engagement_types');
		foreach($arrEngagementTypesResult->result_array() as $arrEngagementType){
			$arrEngagementTypes[$arrEngagementType['id']]	= $arrEngagementType['engagement_type'];
		}
		return 	$arrEngagementTypes;
	}
	function getAffiliationsDataById($affId){
		$this->db->select('kol_memberships.type as org_type,kol_memberships.institute_id,kol_memberships.department,kol_memberships.start_date,kol_memberships.end_date,kol_memberships.role,kol_memberships.engagement_id,institutions.name as org_name');
		$this->db->join('institutions', 'institutions.id = kol_memberships.institute_id');
		$this->db->where('kol_memberships.id', $affId);
		$arrResultSet = $this->db->get('kol_memberships');
		$result = $arrResultSet->row_array();
		return $result;
	}
	function getInstituteIdElseSave($institutionDetails) {
		if ($institutionDetails['name'] != null || !empty($institutionDetails['name'])) {
			$this->db->select('id');
			$this->db->where('name', $institutionDetails['name']);
			$arrResultSet = $this->db->get('institutions');
			if ($arrResultSet->num_rows() == 0) {
				$institutionDetails['created_by'] = $this->session->userdata('user_id');
				$institutionDetails['created_on'] = date("Y-m-d H:i:s");
				$this->db->insert('institutions', $institutionDetails);
				return $this->db->insert_id();
			} else {
				foreach ($arrResultSet->result_array() as $arrRow) {
					return $arrRow['id'];
				}
			}
		}
		return false;
	}
	function updateMembership($arrMembership) {
		$id = $arrMembership['id'];
		$this->db->where('id', $id);
		if ($arrMembership = $this->db->update('kol_memberships', $arrMembership))
			return true;
			else
				return false;
	}
	function getEngagementName($id){
		$engagement='';
		$this->db->where('id',$id);
		$result = $this->db->get('engagement_types');
		foreach($result->result_array() as $row){
			$engagement=$row['engagement_type'];
		}
		return $engagement;
	}
	function getInstituteName($id) {
		$instituteName = '';
		$this->db->select('name');
		$this->db->where('id', $id);
		$instituteName = $this->db->get('institutions');
		foreach ($instituteName->result_array() as $row) {
			$instituteName = $row['name'];
		}
		return $instituteName;
	}
	
	function getKolYearsOfExperience($type,$kolId){
		$this->db->select("kol_educations.*");
		$this->db->where("kol_educations.type",$type);
		$this->db->where("kol_educations.kol_id",$kolId);
		if($type == 'education' || $type == 'training'){
			$this->db->where("(kol_educations.end_date is not null and kol_educations.end_date != '')");
			$this->db->order_by("kol_educations.end_date","ASC");
		}
		if($type == 'board_certification'){
			$this->db->where("(kol_educations.start_date is not null and kol_educations.start_date != '')");
			$this->db->order_by("kol_educations.start_date","ASC");
		}
		$arrEdu = $this->db->get("kol_educations");
		$arrEduExpe = array();
		foreach ($arrEdu->result_array() as $edu){
			$arrEduExpe[] = $edu;
		}
		return $arrEduExpe;
	}
	function getKolYearsOfExperienceFromAff($kolId){
		$arrMembershipDetails = array();
		$this->db->select(array('kol_memberships.*'));
		$this->db->where("(kol_memberships.start_date is not null and kol_memberships.start_date != '')");
		$this->db->where('kol_id', $kolId);
		$this->db->order_by('start_date',"ASC");
		if($arrMembershipDetailResult =	$this->db->get('kol_memberships')){
			foreach($arrMembershipDetailResult->result_array() as $arrMembership){
				$arrMembershipDetails[]=$arrMembership;
			}
			return $arrMembershipDetails;
		}
	}
	function getEducationById($educationId) {
		$arrEducationDetails = array();
		$clientId = $this->session->userdata('client_id');
		$this->db->where('kol_educations.id', $educationId);
		$this->db->select(array('kol_educations.*', 'institutions.name','institutions.id as institutionsId','client_users.first_name','client_users.last_name'));
		$this->db->join('institutions', 'institutions.id = kol_educations.institute_id', 'left');
		$this->db->join('client_users', 'client_users.id = kol_educations.created_by', 'left');
		if ($clientId != INTERNAL_CLIENT_ID) {
			//$this->db->where("(kol_educations.client_id=$clientId or kol_educations.client_id=".INTERNAL_CLIENT_ID.")");
		}
		$arrEducationDetailsResult = $this->db->get('kol_educations');
		foreach ($arrEducationDetailsResult->result_array() as $arrRow) {
			$arrEducationDetails = $arrRow;
		}
		return $arrEducationDetails;
	}
	function updateEducationDetail($educationDetails) {
		$result = array();
		$this->db->where('id', $educationDetails['id']);
		if ($this->db->update('kol_educations', $educationDetails)) {
			return true;
		} else {
			return false;
		}
	}
	function getInstituteNames($instituteName) {
		$this->db->select('institutions.id,name');
		$this->db->like('name', $instituteName, 'after');
		$this->db->limit(10);
		$this->db->distinct();
		$this->db->join('kol_educations', 'kol_educations.institute_id=institutions.id', 'inner');
		$arrResultSet = $this->db->get('institutions');
		$arrInstituteNames = array();
		foreach ($arrResultSet->result_array() as $arrRow) {
			$arrInstituteNames[$arrRow['id']] = $arrRow['name'];
		}
		return $arrInstituteNames;
	}
	
	function getKolNames($arrIds = "",$restrictByRegion=0) {
		$client_id = $this->session->userdata('client_id');
		$arrKolDetail = array();
		$this->db->select('kols.id,kols.unique_id,kols.salutation,kols.first_name,kols.middle_name,kols.last_name,kols.status');
		if ($arrIds != "" && is_array($arrIds))
			$this->db->where_in('kols.id', $arrIds);
				
			if($client_id !== INTERNAL_CLIENT_ID){
				$this->db->join('kols_client_visibility', 'kols_client_visibility.kol_id = kols.id', 'left');
				$this->db->where('kols_client_visibility.client_id', $client_id);
	
				if($restrictByRegion && $this->session->userdata('user_role_id') != ROLE_ADMIN){
					$group_names = explode(',', $this->session->userdata('group_names'));
					$this->db->join ( 'countries', 'countries.CountryId=kols.country_id', 'left' );
					$this->db->where_in( 'countries.GlobalRegion', $group_names);
				}
			}
			if(KOL_CONSENT){
				$this->db->where('(kols.opt_in_out_status is NULL OR kols.opt_in_out_status = 0 OR kols.opt_in_out_status =4)','',false);
			}
			$this->db->where('(kols.deleted_by is null or kols.deleted_by=0)','',false);
			$this->db->where('(kols.imported_as IS NULL or kols.imported_as = 2 or kols.imported_as = 0 or kols.imported_as = 3)', null, false);
			$this->db->order_by('kols.first_name');
			$arrKolDetailResult = $this->db->get('kols');
				
			foreach ($arrKolDetailResult->result_array() as $row) {
				$arrKolDetail[] = $row;
			}
			return $arrKolDetail;
	}
	function updateNote($arrData) {
		$this->db->where('id', $arrData['id']);
		if ($this->db->update("kol_notes", $arrData)) {
			return true;
		} else {
			return false;
		}
	}
	function getAllKolNamesForAllAutocomplete($kolName,$restrictByRegion=0,$restrictOptInVisbility=0) {
		$client_id = $this->session->userdata('client_id');
		$kolName = str_replace ( ",", " ", $kolName );
		$kolName = preg_replace ( '!\s+!', ' ', $kolName );
		$kolName = $this->db->escape_like_str ( $kolName );
		$arrKols = array ();
		$this->db->select ( "kols.id,kols.unique_id,first_name,middle_name,last_name,organizations.name as name,kols.status,kols.do_not_call_flag,states.name as state,cities.city as city,kol_locations.private_practice" );
		$this->db->join ( 'organizations', 'kols.org_id=organizations.id', 'left' );
		$this->db->join ( 'states', 'states.id = kols.state_id', 'left' );
		$this->db->join ( 'cities', 'cities.cityID = kols.city_id', 'left' );
		$this->db->join ( 'kol_locations', 'kols.id = kol_locations.kol_id', 'left' );
		$likeNameFormatOrder	= 'first_name,middle_name,last_name';
		$this->db->where('replace(concat(coalesce(first_name,"")," ",coalesce(middle_name,"")," ",coalesce(last_name,"")),"  "," ") like "%'.$kolName.'%"', '',false);
		$this->db->where ('kols.customer_status', "ACTV" );
		if(KOL_CONSENT && $restrictOptInVisbility==1){
			$this->db->where('(kols.opt_in_out_status is NULL OR kols.opt_in_out_status = 0 OR kols.opt_in_out_status =4)','',false);
		}
		$this->db->where('(kols.deleted_by is null or kols.deleted_by=0)','',false);
		$this->db->where('(kols.imported_as IS NULL or kols.imported_as = 2 or kols.imported_as = 0 or kols.imported_as = 3)', null, false);
		$nameFormat = $this->session->userdata('name_order');
		if ($nameFormat == 1 || $nameFormat == 3)
			$this->db->order_by("first_name",'asc');
			else if ($nameFormat == 2)
				$this->db->order_by("last_name",'asc');
				if($client_id !== INTERNAL_CLIENT_ID){
					$this->db->join('kols_client_visibility', 'kols_client_visibility.kol_id = kols.id', 'left');
					$this->db->where('kols_client_visibility.client_id', $client_id);
					if($restrictByRegion==1  && $this->session->userdata('user_role_id') != ROLE_ADMIN && $this->session->userdata('user_role_id') != ROLE_READONLY_USER){
						$group_names = explode(',', $this->session->userdata('group_names'));
						$this->db->join ( 'countries', 'countries.CountryId=kols.country_id', 'left' );
						$this->db->where_in( 'countries.GlobalRegion', $group_names);
					}
				}
				$arrKolsResult = $this->db->get ( 'kols' );
				$arrCompletedKols = array ();
				$arrMyCustomers = array ();
				foreach ( $arrKolsResult->result_array () as $row ) {
					$arrMyCustomers [$row ['id']] [] = str_replace ( '  ', ' ', $this->common_helper->get_name_format ( $row ['first_name'], $row ['middle_name'], $row ['last_name'] ) );
					if (! empty ( $row ['name'] ))
						$arrMyCustomers [$row ['id']] [] = $row ['name'];
						else
							$arrMyCustomers [$row ['id']] [] = $row ['private_practice'];
							$arrMyCustomers [$row ['id']] [] = $row ['state'];
							$arrMyCustomers [$row ['id']] [] = $row ['city'];
							$arrMyCustomers [$row ['id']] [] = $row ['unique_id'];
							if ($row ['do_not_call_flag'] == 1)
								$arrMyCustomers [$row ['id']] ['do_not_call_flag'] = "Do Not Call";
								else
									$arrMyCustomers [$row ['id']] ['do_not_call_flag'] = "";
				}
				$arrKols ['kols'] = $arrCompletedKols;
				$arrKols ['customers'] = $arrMyCustomers;
				return $arrKols;
	}
	function getOrgDetailsById($kolId) {
		$arrOrgDetails = array();
		$arrSalutations = array(0 => '', 'Dr.', 'Prof.', 'Mr.', 'Ms.');
		$this->db->where('organizations.id', $kolId);
		$this->db->select(array('organizations.*', 'countries.Country', 'states.name as Region', 'cities.City'));
		$this->db->join('countries', 'CountryId = country_id', 'left');
		$this->db->join('states', 'states.id = state_id', 'left');
		$this->db->join('cities', 'cityId = city_id', 'left');
		$arrOrgDetailsResult = $this->db->get('organizations');
		//echo $this->db->last_query(); exit;
		foreach ($arrOrgDetailsResult->result_array() as $row) {
			$arrOrgDetails[] = $row;
		}
		return $arrOrgDetails;
	}
	function saveKolInfo($arrKolData) {
		if ($this->db->insert('kols', $arrKolData)) {
			$id = $this->db->insert_id();
			$this->db->where("id", $id);
			$this->db->set("pin", $id);
			$this->db->set("unique_id", md5($id));
			if ($this->db->update("kols")) {
				return $id;
			}
		} else {
			return false;
		}
	
	}
	function saveAssignClient($arrAssignData) {
		if ($this->db->insert('user_kols', $arrAssignData)) {
			return $this->db->insert_id();
		} else {
			return false;
		}
	}
	function saveContactRestrictions($arrContactData) {
		if ($this->db->insert('contact_restrictions', $arrContactData)) {
			return $this->db->insert_id();
		} else {
			return false;
		}
	}
	function getKolShortDetails($kol_id) {
		$this->db->select("kols.first_name,kols.middle_name,kols.last_name,kols.profile_type,kols.gender,kols.salutation,kols.specialty,kols.profile_image");
		$this->db->where('kols.id',$kol_id);
		$this->db->limit(1);
		$arrResultSet =$this->db->get('kols');
		foreach ($arrResultSet->result_array() as $row) {
			$arrReturnData=$row;
		}
		return $arrReturnData;
	}
	function deleteStaffByKolId($id) {
		$this->db->where('contact', $id);
		$this->db->where('contact_type !=','organization');
		if ($query = $this->db->delete('staffs')) {
			return true;
		}else{
			return false;
		}
	}
	function deletePhoneByKolId($id) {
		$this->db->where('contact', $id);
		$this->db->where('contact_type !=','organization');
		if ($query = $this->db->delete('phone_numbers')) {
			return true;
		}else{
		}
	}
	function updateDeleteComment($arrData){
		$this->db->set('delete_comment',$arrData['delete_comment']);
		$this->db->set('deleted_by',$arrData['delete_by']);
		$this->db->set('deleted_on',date("Y-m-d H:i:s"));
		$this->db->set('delete_ticket_no',$arrData['delete_ticket_no']);
		$this->db->where('id',$arrData['id']);
		$this->db->update('kols');
	}
	function insertKolStatusDetails($arrDetails){
		if($arrDetails['kol_status_id']==1){
			$description = 'Deleted Kol';
		}else{
			$description = 'Removed from contact';
		}
		if ($this->db->insert('kol_status_details',$arrDetails)) {
			return true;
		}else{
			return true;
		}
	}
	function getTopConceptDataForChart($kolId, $fromYear, $toYear, $keyword, $limit = '') {
		$arrMajorMeshterm = array();
		$this->db->select('pubmed_mesh_terms.id,pubmed_mesh_terms.term_name as name,count(distinct kol_publications.pub_id) as count,pubmed_mesh_terms.parent_id,pubmed_mesh_terms.tree_id');
		$this->db->join('publication_mesh_terms', 'publication_mesh_terms.term_id = pubmed_mesh_terms.id', 'left');
		$this->db->join('publications', 'publications.id = publication_mesh_terms.pub_id', 'left');
		$this->db->join('kol_publications', 'kol_publications.pub_id = publications.id', 'left');
	
		if ($keyword != '') {
			//$this->db->join('publication_mesh_terms', 'publication_mesh_terms.pub_id = publications.id','left');
			//$this->db->join('pubmed_mesh_terms', 'publication_mesh_terms.term_id = pubmed_mesh_terms.id','left');
			$this->db->join('publication_substances', 'publication_substances.pub_id=publications.id', 'left');
			$this->db->join('pubmed_substances', 'pubmed_substances.id=publication_substances.substance_id', 'left');
			$this->db->where("(publications.article_title  LIKE '%$keyword%' OR  publications.abstract_text  LIKE '%$keyword%' OR pubmed_substances.name LIKE '%$keyword%' OR pubmed_mesh_terms.term_name LIKE '%$keyword%')");
		}
		$this->db->where('kol_publications.is_deleted', 0);
		$this->db->where('kol_publications.is_verified', 1);
		$this->db->where('kol_id', $kolId);
		//$this->db->where('publication_mesh_terms.is_major','0');
		$where = 'year(publications.created_date)';
		$where .= 'between '.$fromYear.' and '.$toYear;
		$this->db->where($where);
		$this->db->group_by('pubmed_mesh_terms.term_name');
		$this->db->order_by('count', 'desc');
		if ($limit != 'all')
			$this->db->limit('20');
				
			$arrMajorMeshtermResult = $this->db->get('pubmed_mesh_terms');
			// 		echo $this->db->last_query();exit;
			foreach ($arrMajorMeshtermResult->result_array() as $row) {
				$arrMajorMeshterm[] = $row;
			}
			return $arrMajorMeshterm;
	}
	function getEventLoaction($kolId) {
		$this->db->select('cities.city,cities.Latitude,cities.Longitude');
		$this->db->join('cities', 'cities.CityId=kol_events.city_id', 'inner');
		$this->db->where('kol_id', $kolId);
		$this->db->group_by('city');
		$this->db->order_by('kol_events.end', 'desc');
		//$this->db->limit(1,0);
		$arrResult = $this->db->get('kol_events');
		//print $this->db->last_query();exit;
		foreach ($arrResult->result_array() as $row) {
			$arrEvents[] = $row;
		}
		return $arrEvents;
	}
	function getTopicsBySpecialty($specialtyId) {
		$arrTopics = array();
		$this->db->where('specialty_id', $specialtyId);
		$this->db->order_by("name", "asc");
		$result = $this->db->get('event_topics');
		foreach ($result->result_array() as $row) {
			$arrTopics[$row['id']] = $row;
		}
		return $arrTopics;
	}
	function getEventRoles() {
		$this->db->order_by('role asc');
		$arrResultSet = $this->db->get('event_roles');
		foreach ($arrResultSet->result_array() as $row) {
				
			$arrRoles[$row['id']] = $row['role'];
		}
		return $arrRoles;
	}
	function getEventById($eventId) {
		$arrEventDetails = array();
		$clientId = $this->session->userdata('client_id');
		$this->db->where('kol_events.id', $eventId);
		$this->db->select(array('event_sponsor_types.type as sponsor_type', 'event_sponsor_types.type as sponsor_type_name', 'kol_events.*', 'events.name', 'conf_event_types.event_type as conf_event_type', 'countries.country', 'event_topics.name as event_topic','event_topics.specialty_id as et_specialty_id', 'conf_session_types.session_type as conf_session_type', 'states.name','client_users.first_name','client_users.last_name'));
		//$this->db->select(array('event_sponsor_types.type as sponsor_type', 'event_sponsor_types.type as sponsor_type_name', 'kol_events.*', 'events.name', 'conf_event_types.event_type as conf_event_type', 'countries.country', 'event_topics.name as event_topic', 'conf_session_types.session_type as conf_session_type', 'regions.region'));
		$this->db->join('events', 'events.id = kol_events.event_id', 'left');
		//$this->db->join('conf_event_types','conf_event_types.id = kol_events.event_type', 'left');
		$this->db->join('countries', 'countries.countryId = kol_events.country_id', 'left');
		$this->db->join('states', 'states.id = kol_events.state_id', 'left');
		$this->db->join('conf_event_types', 'conf_event_types.id = kol_events.event_type', 'left');
		$this->db->join('conf_session_types', 'conf_session_types.id = kol_events.session_type', 'left');
		$this->db->join('event_topics', 'event_topics.id = kol_events.topic', 'left');
		$this->db->join('event_sponsor_types', 'event_sponsor_types.id = kol_events.sponsor_type', 'left');
		$this->db->join('client_users', 'client_users.id = kol_events.created_by', 'left');
		if ($clientId != INTERNAL_CLIENT_ID) {
			//$this->db->where("(kol_educations.client_id=$clientId or kol_educations.client_id=".INTERNAL_CLIENT_ID.")");
		}
		$arrEventDetailsResult = $this->db->get('kol_events');
		foreach ($arrEventDetailsResult->result_array() as $arrRow) {
			$arrEventDetails = $arrRow;
		}
		return $arrEventDetails;
	}
	function getEventIdElseSave($eventDetails) {
		if ($eventDetails['name'] != null) {
			$this->db->select('id');
			$this->db->where('name', $eventDetails['name']);
			$arrResultSet = $this->db->get('events');
			if ($arrResultSet->num_rows() == 0) {
				$eventDetails['created_by'] = $this->session->userdata('user_id');
				$eventDetails['created_on'] = date("Y-m-d H:i:s");
				$this->db->insert('events', $eventDetails);
				return $this->db->insert_id();
			}
			$arrEventId = array();
			foreach ($arrResultSet->result_array() as $arrRow) {
				return $arrRow['id'];
			}
		} else
			return false;
	}
	function updateEvent($eventDetails) {
		$this->db->where('id', $eventDetails['id']);
		if ($this->db->update('kol_events', $eventDetails)) {
			return true;
		} else {
			return false;
		}
	}
	function getEventLookupName($id) {
		$eventName = '';
		$this->db->select('name');
		$this->db->where('id', $id);
		$eventNameRusult = $this->db->get('events');
		foreach ($eventNameRusult->result_array() as $row) {
			$eventName = $row['name'];
		}
		return $eventName;
	}
	function saveEvent($eventDetails,$eventId) {
		if ($this->db->insert('kol_events', $eventDetails)) {
			return $this->db->insert_id();
		} else {
			return false;
		}
	}
	
	function deleteNote($noteId) {
		$get = $this->db->get_where("kol_notes",array("id"=>$noteId))->row();
		$filename = $get->document;
		$this->db->where('id', $noteId);
		if ($this->db->delete("kol_notes")) {
			if($filename!='')
				unlink(APPPATH."/documents/kol_note_documents/".$filename);
				return true;
		} else {
			return false;
		}
	}
	function deleteNoteAttachment($noteId){
		$get = $this->db->get_where("kol_notes",array("id"=>$noteId))->row();
		$filename = $get->document;
		if($filename!=''){
			unlink($_SERVER['DOCUMENT_ROOT']."/".$this->config->item('app_folder_path')."/documents/kol_note_documents/".$filename);
			$this->db->update("kol_notes",array("document"=>'',"document_name"=>'',"orginal_doc_name"=>''),array("id"=>$noteId));
			return true;
		} else {
			return false;
		}
	}
	
	function saveEducationDetail($educationDetails) {
		if ($this->db->insert('kol_educations', $educationDetails)) {
			return $this->db->insert_id();
		} else {
			return false;
		}
	}
	
	/*
	 * Lists all processed kols for analyst application, it's for ajax pagination,search and sort version of jqgrid
	 * @author Ramesh B
	 * @since 22 Feb 2013
	 * @version otsuka1.0.11
	 * @return Array or Integer
	 */
	
	function getProcessedKols($limit = null, $startFrom = null, $doCount = null, $sidx = '', $sord = '', $where = '') {
		if (!$doCount) {
			$this->db->select(array('kols.id', 'kols.pin', 'kols.is_imported', 'kols.salutation', 'kols.first_name', 'kols.middle_name', 'kols.last_name', 'kols.gender', 'specialties.specialty', 'organizations.name as org_name', 'kols.is_pubmed_processed', 'kols.is_clinical_trial_processed', 'kols.status', 'client_users.user_name as user_full_name', 'kols_client_visibility.id as vid'));
		}
		$this->db->join('client_users', 'client_users.id = kols.created_by', 'left');
		$this->db->join('specialties', 'kols.specialty = specialties.id', 'left');
		$this->db->join('organizations', 'kols.org_id = organizations.id', 'left');
		$this->db->join('kols_client_visibility', 'kols_client_visibility.kol_id = kols.id', 'left');
	
		//Add the where conditions for any jqgrid filters
		if (isset($where['kol_name'])) {
			$this->db->where("(kols.first_name LIKE '%" . $where['kol_name'] . "%' or kols.middle_name LIKE '%" . $where['kol_name'] . "%' or kols.last_name LIKE '%" . $where['kol_name'] . "%')");
			//			$this->db->like('kols.first_name',$where['kol_name']);
			//			$this->db->like('kols.middle_name',$where['kol_name']);
			//			$this->db->like('kols.last_name',$where['kol_name']);
		}
		if (isset($where['specialty'])) {
			$this->db->like('specialties.specialty', $where['specialty']);
		}
		if (isset($where['gender'])) {
			$this->db->like('kols.gender', $where['gender']);
		}
		if (isset($where['organization'])) {
			$this->db->like('organizations.name', $where['organization']);
		}
		if (isset($where['pubmed_processed'])) {
			if (preg_match("/[yes]{1,}/i", $where['pubmed_processed']))
				$where['pubmed_processed'] = 1;
				elseif (preg_match("/[no]{1,}/i", $where['pubmed_processed']))
				$where['pubmed_processed'] = 0;
				elseif (preg_match("/[re crawl]{1,}/i", $where['pubmed_processed']))
				$where['pubmed_processed'] = 2;
				$this->db->like('kols.is_pubmed_processed', $where['pubmed_processed']);
		}
		if (isset($where['trial_processed'])) {
			if (preg_match("/[yes]{1,}/i", $where['trial_processed']))
				$where['trial_processed'] = 1;
				elseif (preg_match("/[no]{1,}/i", $where['trial_processed']))
				$where['trial_processed'] = 0;
				$this->db->like('kols.is_clinical_trial_processed', $where['trial_processed']);
		}
		if (isset($where['created_by'])) {
			$this->db->like('client_users.user_name', $where['created_by']);
		}
		if (isset($where['pin'])) {
			$this->db->like('kols.pin', $where['pin']);
		}
		if (isset($where['status'])) {
			$this->db->like('kols.status', $where['status']);
		}
	
		//Client specific condition
		$analystSelectedclientId = $this->session->userdata('analyst_client');
		$this->db->where('kols_client_visibility.client_id', $analystSelectedclientId);
	
		//   $this->db->where("(client_users.client_id=" . INTERNAL_CLIENT_ID . " or kols.status='" . COMPLETED . "')");
		//   $this->db->where("(client_users.client_id=" . INTERNAL_CLIENT_ID . ")");
	
		if ($doCount) {
			//$this->db->distinct();
			$count = $this->db->count_all_results('kols');
			return $count;
		} else {
			if ($sidx != '' && $sord != '') {
				switch ($sidx) {
					case 'kol_name' : $this->db->order_by("kols.first_name", $sord);
					break;
					case 'specialty' :$this->db->order_by("specialties.specialty", $sord);
					break;
					case 'gender' :$this->db->order_by("kols.gender", $sord);
					break;
					case 'organization' :$this->db->order_by("organizations.name", $sord);
					break;
					case 'pubmed_processed' :$this->db->order_by("kols.is_pubmed_processed", $sord);
					break;
					case 'trial_processed' :$this->db->order_by("kols.is_clinical_trial_processed", $sord);
					break;
					case 'created_by' :$this->db->order_by("client_users.user_name", $sord);
					break;
					case 'status' :$this->db->order_by("kols.status", $sord);
					break;
				}
				//$this->db->order_by($sidx,$sord);
			}
			$this->db->order_by('first_name', 'asc');
	
			$arrKolDetail = $this->db->get('kols', $limit, $startFrom);
			//pr($this->db->last_query());exit;
			return $arrKolDetail;
		}
	}
	
	/**
	 * Returns all the InstituteNames from the lookuptable
	 * @return array	- ID, and InstituteNames
	 */
	function getAllInstituteNames() {
		$arrAllInstituteNames = array();
	
		$arrInstituteNamesResult = $this->db->get('institutions');
		foreach ($arrInstituteNamesResult->result_array() as $arrInstituteName) {
			$arrAllInstituteNames[$arrInstituteName['id']] = $arrInstituteName['name'];
		}
		return $arrAllInstituteNames;
	}
	// if suffix not exists add suffix
	function saveSuffix($spec) {
		$get = $this->db->get_where("professional_suffix",array("suffix"=>$spec));
		if($get->num_rows()==0){
			$this->db->insert("professional_suffix",array("suffix"=>$spec,"is_active"=>1));
			return $this->db->insert_id();
		}
		return $get->row()->id;
	}
	// if title not exists add title
	function saveTitle($tit) {
		$get = $this->db->get_where("titles",array("title"=>$tit));
		if($get->num_rows()==0){
			$this->db->insert("titles",array("title"=>$tit,"is_active"=>1,"abbr"=>' ',"client_id"=>INTERNAL_CLIENT_ID));
			return $this->db->insert_id();
		}
		return $get->row()->id;
	}
	/**
	 * INSERT kol details into DB
	 * @author Vinayak
	 * @since 3.3
	 * @param $arrKol
	 * @return true/false
	 */
	function saveImportedKol($arrKol) {
	
		$this->db->where('pin', $arrKol['pin']);
	
		$arrKolDetail = $this->db->get('kols');
	
		if ($arrKolDetail->num_rows() != 0) {
			return false;
		} else {
			$this->db->where('first_name', $arrKol['first_name']);
			$this->db->where('middle_name', $arrKol['middle_name']);
			$this->db->where('last_name', $arrKol['last_name']);
			if ($arrKolDetail->num_rows() != 0) {
				return false;
			} else {
				if ($this->db->insert('kols', $arrKol)) {
					$id = $this->db->insert_id();
					$data = array('unique_id' => md5($id)); // to genrate unique id and update
					$this->db->where('id', $id);
					$this->db->update('kols', $data);
					return $id;
				} else {
					return false;
				}
			}
		}
	}
	// to save loctions while importing kols
	function saveKolLocation($arrLocation) {
		$checkLoc = $this->check_kolLocation($arrLocation['org_institution_id'],$arrLocation['kol_id']);
		if ($checkLoc) {
			if ($arrLocation["is_primary"] == "1") {
				$primary_flag = array('is_primary' => 0);
				$this->db->where('kol_id', $arrLocation['kol_id']);
				$this->db->update('kol_locations', $primary_flag);
					
				if ($arrLocation["title"] > 0) {
					$title_id = array('title' => $arrLocation["title"]);
					$this->db->where('id', $arrLocation['kol_id']);
					$this->db->update('kols', $title_id);
				}
					
				if ($arrLocation["division"] > 0) {
					$division_id = array('division' => $arrLocation["division"]);
					$this->db->where('id', $arrLocation['kol_id']);
					$this->db->update('kols', $division_id);
				}
			}
			$this->db->where('id', $checkLoc);
			if ($this->db->update('kol_locations', $arrLocation)) {
				return $checkLoc;
			} else {
				return false;
			}
		} else {
	
			if ($arrLocation["is_primary"] == "1") {
				$primary_flag = array('is_primary' => 0);
				$this->db->where('kol_id', $arrLocation['kol_id']);
				$this->db->update('kol_locations', $primary_flag);
					
				if ($arrLocation["title"] > 0) {
					$title_id = array('title' => $arrLocation["title"]);
					$this->db->where('id', $arrLocation['kol_id']);
					$this->db->update('kols', $title_id);
				}
					
				if ($arrLocation["division"] > 0) {
					$division_id = array('division' => $arrLocation["division"]);
					$this->db->where('id', $arrLocation['kol_id']);
					$this->db->update('kols', $division_id);
				}
			}
	
			if ($this->db->insert('kol_locations', $arrLocation)) {
				return $this->db->insert_id();
			} else {
				return false;
			}
		}
	}
	/**
	 * Update the kol record matching the 'cin_num'
	 * @author 	Ambarish N
	 * @since	2.5
	 * @return
	 * @created 27-06-2011
	 */
	function updateImportedKol($kolsDetails) {
		$this->db->where('pin', $kolsDetails['pin']);
		$arrKolDetail = $this->db->get('kols');
		if ($arrKolDetail->num_rows() == 0) {
			return false;
		} else {
			$this->db->where('pin', $kolsDetails['pin']);
			if ($this->db->update('kols', $kolsDetails)) {
				//return true;
				$resultObject = $arrKolDetail->row();
				return $resultObject->id;
			} else
				return false;
		}
	}
	/**
	 * Retruns the kolId by matching the 'pin'
	 * @author 	Ambarish N
	 * @since	2.5
	 * @return
	 * @created 27-06-2011
	 */
	function getKolIdByPin($pin) {
		$id = 0;
		$this->db->where('pin', $pin);
		$this->db->select('id');
		$result = $this->db->get('kols');
		$data = $result->row();
		if ($data != null)
			$id = $data->id;
			return $id;
	}
	// to check weather kol_location already exists
	function check_kolLocation($org_id,$kol_id){
		$get = $this->db->get_where("kol_locations",array("org_institution_id"=>$org_id,"kol_id"=>$kol_id));
		if($get->num_rows()>0){
			return $get->row()->id;
		}
		return false;
	}
	// to save emails while importing kols
	function saveKolEmails($arrEmails) {
		$checkEmails = $this->check_kolEmails($arrEmails['email'],$arrEmails['contact']);
		if ($checkEmails) {
			if ($arrEmails["is_primary"] == "1") {
				$primary_flag = array('is_primary' => 0);
				$this->db->where('contact', $arrEmails['contact']);
				$this->db->update('emails', $primary_flag);
				if(!empty($arrEmails['email'])){
					$this->db->where('id', $arrEmails['contact']);
					$this->db->update('kols', array('primary_email' => $arrEmails['email']));
				}
			}
			$this->db->where('id', $checkEmails);
			if ($this->db->update('emails', $arrEmails)) {
				return $checkEmails;
			} else {
				return false;
			}
		} else {
	
			if ($arrEmails["is_primary"] == "1") {
				$primary_flag = array('is_primary' => 0);
				$this->db->where('contact', $arrEmails['contact']);
				$this->db->update('emails', $primary_flag);
				if(!empty($arrEmails['email'])){
					$this->db->where('id', $arrEmails['contact']);
					$this->db->update('kols', array('primary_email' => $arrEmails['email']));
				}
			}
			if ($this->db->insert('emails', $arrEmails)) {
				return $this->db->insert_id();
			} else {
				return false;
			}
		}
	}
	// to check weather emails already exists
	function check_kolEmails($email,$kol_id){
		$get = $this->db->get_where("emails",array("email"=>$email,"contact"=>$kol_id));
		if($get->num_rows()>0){
			return $get->row()->id;
		}
		return false;
	}
	
	// to save phone_number while importing kols
	function saveKolPhones($arrPhone) {
		$checkPhone = $this->check_kolPhones($arrPhone['number'],$arrPhone['contact'],$arrPhone['location_id']);
		if ($checkPhone) {
			if ($arrPhone["is_primary"] == "1") {
				$primary_flag = array('is_primary' => 0);
				$this->db->where('contact', $arrPhone['contact']);
				$this->db->update('phone_numbers', $primary_flag);
					
				$phNo = $arrPhone['number'];
				$phoneNumber = array('primary_phone' => $phNo);
				$this->db->where('id',$arrPhone['contact']);
				$this->db->update('kols', $phoneNumber);
			}
			$this->db->where('id', $checkPhone);
			if ($this->db->update('phone_numbers', $arrPhone)) {
				return $checkPhone;
			} else {
				return false;
			}
		} else {
	
			if ($arrPhone["is_primary"] == "1") {
				$primary_flag = array('is_primary' => 0);
				$this->db->where('contact', $arrPhone['contact']);
				$this->db->update('phone_numbers', $primary_flag);
					
				$phNo = $arrPhone['number'];
				$phoneNumber = array('primary_phone' => $phNo);
				$this->db->where('id',$arrPhone['contact']);
				$this->db->update('kols', $phoneNumber);
			}
			if ($this->db->insert('phone_numbers', $arrPhone)) {
				return $this->db->insert_id();
			} else {
				return false;
			}
		}
	}
	// to check weather phone_number already exists
	function check_kolPhones($number,$kol_id,$loc_id){
		$get = $this->db->get_where("phone_numbers",array("number"=>$number,"contact"=>$kol_id,"location_id"=>$loc_id));
		if($get->num_rows()>0){
			return $get->row()->id;
		}
		return false;
	}
	// to save sate licence while importing kols
	function saveKolLicense($arrLicence) {
		$checkLicence = $this->check_kolLicense($arrLicence['state_license'],$arrLicence['contact'],$arrLicence['region']);
		if ($checkLicence) {
			if ($arrLicence["is_primary"] == "1") {
				$primary_flag = array('is_primary' => 0);
				$this->db->where('contact', $arrLicence['contact']);
				$this->db->update('state_licenses', $primary_flag);
			}
			$this->db->where('id', $checkLicence);
			if ($this->db->update('state_licenses', $arrLicence)) {
				return $checkLicence;
			} else {
				return false;
			}
		} else {
	
			if ($arrLicence["is_primary"] == "1") {
				$primary_flag = array('is_primary' => 0);
				$this->db->where('contact', $arrLicence['contact']);
				$this->db->update('state_licenses', $primary_flag);
			}
			if ($this->db->insert('state_licenses', $arrLicence)) {
				return $this->db->insert_id();
			} else {
				return false;
			}
		}
	}
	// to check weather sate licence already exists
	function check_kolLicense($licence,$kol_id,$region){
		$get = $this->db->get_where("state_licenses",array("state_license"=>$licence,"contact"=>$kol_id,"region"=>$region));
		if($get->num_rows()>0){
			return $get->row()->id;
		}
		return false;
	}
	function getKolsAssociatedWithClient($clientId){
		$this->db->select(array('kols.id as id', 'kols.id as kol_id', 'kols.salutation', 'concat(COALESCE(kols.first_name,"")," ", COALESCE(kols.middle_name,"")," ", COALESCE(kols.last_name,"")) as kol_name', 'specialties.specialty as kol_speciality', 'kols.gender as kol_gender', 'organizations.name as kol_org', 'concat(COALESCE(client_users.first_name,"")," ", COALESCE(client_users.last_name,"")) as kol_created_by', 'kols.pin as kol_pin', 'kols.status as kol_status','kols.is_pubmed_processed','kols.is_clinical_trial_processed'));
		$this->db->join ( 'kols_client_visibility', 'kols_client_visibility.kol_id = kols.id', 'left' );
		$this->db->join ( 'specialties', 'specialties.id = kols.specialty', 'left' );
		$this->db->join ( 'organizations', 'organizations.id = kols.org_id', 'left' );
		$this->db->join ( 'client_users', 'client_users.id = kols.created_by', 'left' );
		$this->db->where ( 'kols_client_visibility.client_id', $clientId );
		$this->db->order_by( 'kol_name', 'asc' );
		$arrKolsResult = $this->db->get ( 'kols' );
		//pr($this->db->last_query());exit;
		return $arrKolsResult->result_array();
	}
	/**
	 *  INSERT kol details into DB
	 * @param $arrKol
	 * @return true/false
	 */
	function saveKol($arrKol) {
		$this->db->where('first_name', $arrKol['first_name']);
		$this->db->where('middle_name', $arrKol['middle_name']);
		$this->db->where('last_name', $arrKol['last_name']);
		$this->db->where('specialty', $arrKol['specialty']);
		$arrKolDetail = $this->db->get('kols');
	
		if ($arrKolDetail->num_rows() != 0) {
			return false;
		} else {
			if ($arrKol['org_id'] == 0 || empty($arrKol['org_id'])) {
				$arrKol['org_id'] = null;
			}
			if ($this->db->insert('kols', $arrKol)) {
				return $this->db->insert_id();
			} else {
				return false;
			}
		}
	}
	
	/**
	 *  Delete kol details into DB
	 * @param $arrKol
	 * @return true/false
	 */
	function delete_kol_data($tableName,$columnName,$columnValue){
		if($tableName=='json_store'){
			$columnValue = 'kol_id:'.$columnValue;
		}
		if(!empty($tableName) && !empty($columnName) && !empty($columnValue)){
			if (is_array($columnValue)) {
				$this->db->where_in($columnName, $columnValue);
			} else {
				$this->db->where($columnName, $columnValue);
			}
			$this->db->delete($tableName);
			//             echo $this->db->last_query();
			return true;
		}
		return false;
	}
	function delete_kol_data_all($tableName, $condition){
		foreach((array)$condition as $key => $value){
			if (is_array($value)) {
				$this->db->where_in($key, $value);
			} else {
				$this->db->where($key, $value);
			}
		}
		$this->db->delete($tableName);
	}
	/*
	 * Get Organization name from 'organizations' Table
	 * @param $orgName
	 * @return $id
	 */
	
	function getOrgName($orgName) {
		$id = '';
		$this->db->where('name', $orgName);
		$result = $this->db->get('organizations');
		foreach ($result->result_array() as $row) {
			$id = $row['id'];
		}
		return $id;
	}
	function updateKolLocationLatitudeLongitude($arrKolLatLongData){
		$this->db->where('kol_id', $arrKolLatLongData['kol_id']);
		$this->db->where('is_primary', 1);
		if ($this->db->update('kol_locations', $arrKolLatLongData)) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Saves the Contact Details Data to DB
	 *
	 * @return true/false
	 */
	function saveContact($contactDetails) {
		if ($this->db->insert('kol_additional_contacts', $contactDetails)) {
			return $this->db->insert_id();
		} else {
			return false;
		}
	}
	/**
	 * Updates the Contact Detail Data
	 *
	 * @return true/false
	 */
	function updateContact($contactDetails) {
		$this->db->where('id', $contactDetails['id']);
		if ($this->db->update('kol_additional_contacts', $contactDetails)) {
			return true;
		} else {
			return false;
		}
	}
	
	function saveEducationDetailByBulk($educationDetail) {
	
		foreach ($educationDetail as $row) {
			$row['honor_name'] = mysql_real_escape_string($row['honor_name']);
			$row['degree'] = mysql_real_escape_string($row['degree']);
			$row['specialty'] = mysql_real_escape_string($row['specialty']);
			$bulkInsert.="('" . $row['type'] . "','" . $row['institute_id'] . "','" . $row['degree'] . "','" . $row['honor_name'] . "','" . $row['specialty'] . "','" . $row['year'] . "','" . $row['start_date'] . "','" . $row['end_date'] . "','" . $row['created_by'] . "',
	
			'" . $row['created_on'] . "','" . $row['client_id'] . "','" . $row['kol_id'] . "','" . $row['data_type_indicator'] . "')" . ',';
			;
			//,$row['institute_id'],'".$row['degree']."','".$row['specialty']."','".$row['start_date']."','".$row['end_date']."','".$row['end_date']."')";
		}
	
		$eduString = substr($bulkInsert, 0, -1);
		//echo $new;
		$this->db->query('insert into kol_educations(`type`,`institute_id`,`degree`,`honor_name`,`specialty`,`year`,`start_date`,`end_date`,`created_by`,`created_on`,`client_id`,`kol_id`,`data_type_indicator`) values ' . $eduString . '');
	
		//	$this->db->insert_batch('kol_events', $educationDetail);
	}
	
	/**
	 * Saves the Institution Detail to DB
	 *
	 * @return Last Insert Id/false
	 */
	function saveInstitution($institutionDetails) {
	
		$instituteName = '';
		$instituteName = $institutionDetails['name'];
		$this->db->where('name', $instituteName);
		if ($result = $this->db->get('institutions')) {
			if ($result->num_rows() != 0) {
				return false;
			} else {
				if ($this->db->insert('institutions', $institutionDetails)) {
					return $this->db->insert_id();
				} else {
					return false;
				}
			}
		}
	}
	
	/**
	 * Returns the Institute Id
	 *
	 * @param String $name
	 * @return Array $arrInstituteId
	 */
	function getInstituteId($name = null) {
		if ($name != null) {
			$this->db->select('id');
			$this->db->where('name', $name);
			$arrResultSet = $this->db->get('institutions');
	
			if ($arrResultSet->num_rows() == 0) {
				return false;
			}
			$arrInstituteId = array();
			foreach ($arrResultSet->result_array() as $arrRow) {
				return $arrRow['id'];
			}
	
			return $arrInstituteId;
		} else
			return false;
	}
	
	//-------------Start of Social Media DAO functions-------------------------------
	/**
	 * Saves the Social Media Data to DB
	 *
	 * @return true/false
	 */
	function saveSocialMedia($socialMediaDetails) {
		$this->db->where('id', $socialMediaDetails['id']);
		if ($this->db->update('kols', $socialMediaDetails)) {
			return true;
		} else {
			return false;
		}
	}
	
	//-------------End of Social Media DAO functions-------------------------------	
}