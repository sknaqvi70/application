<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 
class ComplaintModel extends CI_Model {
	public function __construct(){
		parent::__construct();
		$this->load->database();		
	}
	//
	public function getDepartmentList(){
		$this->db->select('DEP_ID, DEP_DESC');
		$this->db->from('ALL_DEP_MST');
		$query = $this->db->get();

		$DList[0] = 'Select Department';      	
      	foreach($query->result() as $Dept) 
        	$DList[$Dept->DEP_ID] = $Dept->DEP_DESC;     
    	return $DList;
	}
	//get employee list for on call comlaint registration from database
	public function getEmployeeList($postData){
		$this->db->select('EMP_ID, EMP_NAME');
		$this->db->from('ALL_EMP_MST A');
		$this->db->where(['A.EMP_DEP_ID'=>$postData]);
		$q = $this->db->get();				
    	$response = $q->result_array();
    	return $response;
	}

	//this function is use to fetch complaint category
	public function getComplaintCat($UserType){
    	$response = array();
    	$where="MJ_CSC_NO is NULL AND CC_NO = 1";
     	// Select record
		$this->db->order_by('CC_NAME', 'ASC');          			
		$this->db->select('CC_NO, CC_NAME');
		$this->db->join('MJ_USER_COMP_TYPE_AUTH M','M.MJ_CC_NO=C.CC_NO');
		$this->db->from('COMPLAINT_CATEGORY C');
		$this->db->where(['M.MJ_CC_USER_TYPE'=>$UserType]);
		$this->db->where($where);
		$q = $this->db->get();
		$CTList[0] = 'Select Complaint Type';      	
      	foreach($q->result() as $ComplaintType) 
        	$CTList[$ComplaintType->CC_NO] = $ComplaintType->CC_NAME;     
    	return $CTList;
  	}
  	//this function is use to fetch complaint sub category
  	public function getComplaintSubCat($postData,$UserType){
    	$response = array();
     	// Select record
		$this->db->order_by('CSC_NAME', 'ASC');          			
		$this->db->select('CSC_NO, CSC_CC_NO, CSC_NAME, EMAILID, EMAILCC, RECEIVERID, CSC_FTS_BASED');
		$this->db->join('MJ_USER_COMP_TYPE_AUTH M','M.MJ_CSC_NO=A.CSC_NO');
		$this->db->from('COMPLAINT_SUB_CATEGORY A');
		$this->db->where(['A.CSC_CC_NO'=>$postData]);
		$this->db->where('M.MJ_CC_USER_TYPE',$UserType);
		$q = $this->db->get();				
    	$response = $q->result_array();
    	return $response;
  	}

  	//this function is to insert complaint into complaint_mst table and when check_fts_based is N then it also inserted complaint into FILE_MST and FILE_MV_DTL table
  	public function RegisterComplaint($dept,$UserId,$CM_COMPLAINT_TYPE,$CM_COMPLAINT_SUB_TYPE,$CM_COMPLAINT_DESC,$CM_USER_LOCATION,$CM_USER_NANE,$CM_USER_MOBILE,$CM_USER_EMAIL,&$VerificationString,&$TicketNo,&$FtsNo,$CM_NO_UNIT){
  		//current date of register
  	
  		$reg_date=date('d-m-Y'); //date('d-m-Y H:i:s');
  		//Generate random string for email account verification
		$str = rand(); 
		$VerificationString = hash("sha256", $str); 	

		$TicketNo = $this->get_New_CM_NO();
		$ActionTicketNo = $this->get_New_CA_NO();
		$AssignmentNo 	= $this->get_New_CAD_NO($TicketNo);
		$check_fts_based = $this->check_fts_status($CM_COMPLAINT_SUB_TYPE);
		if ($CM_NO_UNIT == '') {
			$no_of_faulty_eq = 1;
		}else{
			$no_of_faulty_eq = $CM_NO_UNIT;
		}

		if($check_fts_based == 'N'){ //this function is to insert complaint without FTS number
			$Users = substr($UserId, 1,1);		

			$number = substr($UserId,2);
			$User = rtrim($UserId, $number);		
			if (ord($Users) >= 65 && ord($Users) <= 90 ) { //checking user				
			$data = array(
		    	'CM_NO' 						=>  $TicketNo,
		    	'CM_DEP_ID' 					=> 	$dept,
		    	'CM_EMP_ID' 					=> 	'EMP\\'.$UserId,
		    	'CM_COMPLAINT_CATEGORY' 		=> 	$CM_COMPLAINT_TYPE,
		    	'CM_COMPLAINT_SUB_CATEGORY' 	=> 	$CM_COMPLAINT_SUB_TYPE,
		    	'CM_COMPLAINT_TEXT'				=>	$CM_COMPLAINT_DESC,
		    	'CM_COMPLAINT_LOCATION' 		=>	$CM_USER_LOCATION,
		    	'CM_COMPLAINT_CONTACT_PERSON' 	=> 	$CM_USER_NANE,
		    	'CM_COMPLAINT_CONTACT_MOBILE' 	=> 	$CM_USER_MOBILE,
		    	'CM_COMPLAINT_CONTACT_EMAIL' 	=> 	$CM_USER_EMAIL,
		    	'CM_COMPLAINT_FTS_NO' 			=> 	'',
		    	'CM_COMPLAINT_STATUS'			=>	'R',
		    	'VERIFICATIONSTRING' 			=>	$VerificationString,
		    	//'CM_COMPLAINT_DATE'				=>	$reg_date,
		    	'CM_STU_ID'						=>	'',
		    	'CM_CMM_ID'						=>	'',
		    	'CM_NO_UNIT'					=>	$no_of_faulty_eq
			);
			
			$cm_response = $this->db->insert('COMPLAINT_MST', $data);
			if ($cm_response) {
				$data = array(
		    	'MJ_CAD_ID' 				=>  $AssignmentNo,
		    	'MJ_CAD_CM_NO' 				=> 	$TicketNo,
		    	'MJ_CAD_EMP_ID' 			=> 	'',
		    	'MJ_CAD_CMM_ID' 			=> 	'',
		    	//'MJ_CAD_ASSIGN_DATE' 		=> 	$reg_date,
		    	'MJ_CAD_COMPLAINT_STATUS'	=>	'Registered',	// Assign to Engineer
		    	'MJ_CAD_CLOSED_DATE' 		=>	'',
		    	'MJ_CAD_PRIORITY'			=>	'',
		    	'MJ_CAD_REMARKS'			=>	$CM_COMPLAINT_DESC
				);
			$cad_response = $this->db->insert('MJ_COMPLAINT_ASSIGN_DTL', $data);			
			}
			if ($cad_response) {
				$data = array(
		    	'MJ_CA_ID' 			=>  $ActionTicketNo,
		    	'MJ_CA_CAD_ID' 		=> 	$AssignmentNo,
		    	'MJ_CA_CM_NO' 		=> 	$TicketNo,
		    	'MJ_CA_ACTION' 		=> 	'Registered',
		    	//'MJ_CA_ACTION_DATE' => 	$reg_date,
		    	'MJ_CA_REMARKS'		=>	$CM_COMPLAINT_DESC
				);			
			$result = $this->db->insert('MJ_COMPLAINT_ACTION_DTL', $data);			
			}
			return $result;
			}elseif (preg_match('/[0-9]/', $User)) {
			$data = array(
		    	'CM_NO' 						=>  $TicketNo,
		    	'CM_DEP_ID' 					=> 	$dept,
		    	'CM_EMP_ID' 					=> 	'',
		    	'CM_COMPLAINT_CATEGORY' 		=> 	$CM_COMPLAINT_TYPE,
		    	'CM_COMPLAINT_SUB_CATEGORY' 	=> 	$CM_COMPLAINT_SUB_TYPE,
		    	'CM_COMPLAINT_TEXT'				=>	$CM_COMPLAINT_DESC,
		    	'CM_COMPLAINT_LOCATION' 		=>	$CM_USER_LOCATION,
		    	'CM_COMPLAINT_CONTACT_PERSON' 	=> 	$CM_USER_NANE,
		    	'CM_COMPLAINT_CONTACT_MOBILE' 	=> 	$CM_USER_MOBILE,
		    	'CM_COMPLAINT_CONTACT_EMAIL' 	=> 	$CM_USER_EMAIL,
		    	'CM_COMPLAINT_FTS_NO' 			=> 	'',
		    	'CM_COMPLAINT_STATUS'			=>	'R',
		    	'VERIFICATIONSTRING' 			=>	$VerificationString,
		    	//'CM_COMPLAINT_DATE'				=>	$reg_date,
		    	'CM_STU_ID'						=>	$UserId,
		    	'CM_CMM_ID'						=>	'',
		    	'CM_NO_UNIT'					=>	$no_of_faulty_eq
			);
			
			$cm_response = $this->db->insert('COMPLAINT_MST', $data);
			if ($cm_response) {
				$data = array(
		    	'MJ_CAD_ID' 				=>  $AssignmentNo,
		    	'MJ_CAD_CM_NO' 				=> 	$TicketNo,
		    	'MJ_CAD_EMP_ID' 			=> 	'',
		    	'MJ_CAD_CMM_ID' 			=> 	'',
		    	//'MJ_CAD_ASSIGN_DATE' 		=> 	$reg_date,
		    	'MJ_CAD_COMPLAINT_STATUS'	=>	'Registered',	// Assign to Engineer
		    	'MJ_CAD_CLOSED_DATE' 		=>	'',
		    	'MJ_CAD_PRIORITY'			=>	'',
		    	'MJ_CAD_REMARKS'			=>	$CM_COMPLAINT_DESC
				);
			$cad_response = $this->db->insert('MJ_COMPLAINT_ASSIGN_DTL', $data);			
			}
			if ($cad_response) {
				$data = array(
		    	'MJ_CA_ID' 			=>  $ActionTicketNo,
		    	'MJ_CA_CAD_ID' 		=> 	$AssignmentNo,
		    	'MJ_CA_CM_NO' 		=> 	$TicketNo,
		    	'MJ_CA_ACTION' 		=> 	'Registered',
		    	//'MJ_CA_ACTION_DATE' => 	$reg_date,
		    	'MJ_CA_REMARKS'		=>	$CM_COMPLAINT_DESC
				);			
			$result = $this->db->insert('MJ_COMPLAINT_ACTION_DTL', $data);			
			}
			return $result;
			}else
			$data = array(
		    	'CM_NO' 						=>  $TicketNo,
		    	'CM_DEP_ID' 					=> 	$dept,
		    	'CM_EMP_ID' 					=> 	'',
		    	'CM_COMPLAINT_CATEGORY' 		=> 	$CM_COMPLAINT_TYPE,
		    	'CM_COMPLAINT_SUB_CATEGORY' 	=> 	$CM_COMPLAINT_SUB_TYPE,
		    	'CM_COMPLAINT_TEXT'				=>	$CM_COMPLAINT_DESC,
		    	'CM_COMPLAINT_LOCATION' 		=>	$CM_USER_LOCATION,
		    	'CM_COMPLAINT_CONTACT_PERSON' 	=> 	$CM_USER_NANE,
		    	'CM_COMPLAINT_CONTACT_MOBILE' 	=> 	$CM_USER_MOBILE,
		    	'CM_COMPLAINT_CONTACT_EMAIL' 	=> 	$CM_USER_EMAIL,
		    	'CM_COMPLAINT_FTS_NO' 			=> 	'',
		    	'CM_COMPLAINT_STATUS'			=>	'R',
		    	'VERIFICATIONSTRING' 			=>	$VerificationString,
		    	//'CM_COMPLAINT_DATE'				=>	$reg_date,
		    	'CM_STU_ID'						=>	'',
		    	'CM_CMM_ID'						=>	$UserId,
		    	'CM_NO_UNIT'					=>	$no_of_faulty_eq
			);
			
			$cm_response = $this->db->insert('COMPLAINT_MST', $data);
			if ($cm_response) {
				$data = array(
		    	'MJ_CAD_ID' 				=>  $AssignmentNo,
		    	'MJ_CAD_CM_NO' 				=> 	$TicketNo,
		    	'MJ_CAD_EMP_ID' 			=> 	'',
		    	'MJ_CAD_CMM_ID' 			=> 	'',
		    	//'MJ_CAD_ASSIGN_DATE' 		=> 	$reg_date,
		    	'MJ_CAD_COMPLAINT_STATUS'	=>	'Registered',	// Assign to Engineer
		    	'MJ_CAD_CLOSED_DATE' 		=>	'',
		    	'MJ_CAD_PRIORITY'			=>	'',
		    	'MJ_CAD_REMARKS'			=>	$CM_COMPLAINT_DESC
				);
			$cad_response = $this->db->insert('MJ_COMPLAINT_ASSIGN_DTL', $data);			
			}
			if ($cad_response) {
				$data = array(
		    	'MJ_CA_ID' 			=>  $ActionTicketNo,
		    	'MJ_CA_CAD_ID' 		=> 	$AssignmentNo,
		    	'MJ_CA_CM_NO' 		=> 	$TicketNo,
		    	'MJ_CA_ACTION' 		=> 	'Registered',
		    	//'MJ_CA_ACTION_DATE' => 	$reg_date,
		    	'MJ_CA_REMARKS'		=>	$CM_COMPLAINT_DESC
				);			
			$result = $this->db->insert('MJ_COMPLAINT_ACTION_DTL', $data);			
			}
			
		return $result;
		}
		else  //this condition for to insert complaint with FTS number
		{
		$FtsNo = $this->get_New_FTS_No($CM_COMPLAINT_SUB_TYPE);

		$CM_COMPLAINT_TYPE_DESC= $this->fetch_complaint_type_desc($CM_COMPLAINT_TYPE);

		$CM_COMPLAINT_SUB_TYPE_DESC= $this->fetch_complaint_sub_type_desc($CM_COMPLAINT_SUB_TYPE);

		$data = array(
		    'CM_NO' 						=>  $TicketNo,
		    'CM_DEP_ID' 					=> 	$dept,
		    'CM_EMP_ID' 					=> 	'EMP\\'.$UserId,
		    'CM_COMPLAINT_CATEGORY' 		=> 	$CM_COMPLAINT_TYPE,
		    'CM_COMPLAINT_SUB_CATEGORY' 	=> 	$CM_COMPLAINT_SUB_TYPE,
		    'CM_COMPLAINT_TEXT'				=>	$CM_COMPLAINT_DESC,
		    'CM_COMPLAINT_LOCATION' 		=>	$CM_USER_LOCATION,
		    'CM_COMPLAINT_CONTACT_PERSON' 	=> 	$CM_USER_NANE,
		    'CM_COMPLAINT_CONTACT_MOBILE' 	=> 	$CM_USER_MOBILE,
		    'CM_COMPLAINT_CONTACT_EMAIL' 	=> 	$CM_USER_EMAIL,
		    'CM_COMPLAINT_FTS_NO' 			=> 	$FtsNo,
		    'CM_COMPLAINT_STATUS'			=>	'R',
		    'VERIFICATIONSTRING' 			=>	$VerificationString,
		    'CM_NO_UNIT'					=>	$no_of_faulty_eq
		    //'CM_COMPLAINT_DATE'				=>	$reg_date
			);
			
			$response = $this->db->insert('COMPLAINT_MST', $data);
			if ($response) {
				$data = array(
		    	'MJ_CAD_ID' 				=>  $AssignmentNo,
		    	'MJ_CAD_CM_NO' 				=> 	$TicketNo,
		    	'MJ_CAD_EMP_ID' 			=> 	'',
		    	'MJ_CAD_CMM_ID' 			=> 	'',
		    	//'MJ_CAD_ASSIGN_DATE' 		=> 	$reg_date,
		    	'MJ_CAD_COMPLAINT_STATUS'	=>	'Registered',	// Assign to Engineer
		    	'MJ_CAD_CLOSED_DATE' 		=>	'',
		    	'MJ_CAD_PRIORITY'			=>	'',
		    	'MJ_CAD_REMARKS'			=>	$CM_COMPLAINT_DESC
				);
			$respose = $this->db->insert('MJ_COMPLAINT_ASSIGN_DTL', $data);			
			}
			if ($respose) {
				$data = array(
		    	'MJ_CA_ID' 			=>  $ActionTicketNo,
		    	'MJ_CA_CAD_ID' 		=> 	$AssignmentNo,
		    	'MJ_CA_CM_NO' 		=> 	$TicketNo,
		    	'MJ_CA_ACTION' 		=> 	'Registered',
		    	//'MJ_CA_ACTION_DATE' => 	$reg_date,
		    	'MJ_CA_REMARKS'		=>	$CM_COMPLAINT_DESC
				);			
			$result = $this->db->insert('MJ_COMPLAINT_ACTION_DTL', $data);			
			}

			if ($CM_COMPLAINT_TYPE == 1) {
        			$FILE_TYPE		= 'FTS016';
        		}else
        		{
        			$FILE_TYPE		= 'FTS023';
        		}

			if ($result) {
			//data insert into file_mst table
			$data_view = array(
        		'FM_FILE_ID' 		=> $FtsNo,
        		'FM_SENDER_ID' 		=> $UserId,
        		'FM_SIGNED_BY'		=> $UserId,
        		'FM_SUBJECT'		=> 'Complaint regarding '.$CM_COMPLAINT_SUB_TYPE_DESC,
        		'FM_DISPATCH_DT'	=> $reg_date,
        		'FM_FILE_TYPE'		=> $FILE_TYPE,
        		'FM_SENDER_DEPT'	=> $dept,
        		'FM_PAGES'			=> '',
        		'FM_REMARKS'		=> $CM_COMPLAINT_DESC.' Location '.$CM_USER_LOCATION.' Contact '
        							   .$CM_USER_NANE.	' Number '.$CM_USER_MOBILE,
        		'FM_SENDER_OTH'		=> '',
        		'FM_EMAIL_ID'		=> $CM_USER_EMAIL
       			);
        	$filemst_result = $this->db->insert('FILE_MST', $data_view);

        	if ($filemst_result) {
        	//data insert into file_mv_dtl table
        		$PBNO= $this->getPBNO($dept);

        		if ($CM_COMPLAINT_TYPE == 1) {
        				$ADDRESSED_TO		= 'Hony. Director, FTK-CIT';
        				$RECD_DEP			= 'ADCIT';
        				$RECV_DT			= $reg_date;
        				$RECEIVER_ID		= 'BCOFTS';
        				$ACTUAL_RECV		= 'BCOFTS';
        			}else
        			{
        				$ADDRESSED_TO		= 'Professor Incharge, B&C';
        				$RECD_DEP			= 'ADBAC';
        				$RECV_DT			= $reg_date;
        				$RECEIVER_ID		= 'BCOFTS';
        				$ACTUAL_RECV		= 'BCOFTS';
        			}
        		
        		$data_view = array(
        			'FMD_FILE_ID' 			=> $FtsNo,
        			'FMD_PB_NO' 			=> $FtsNo.'/'.$dept.'-'.$PBNO,
        			'FMD_SENDER_DEPT'		=> $dept,
        			'FMD_SEND_DT'			=> $reg_date,
        			'FMD_SENDER_ID'			=> $UserId,
        			'FMD_ACTUAL_SEND'		=> $UserId,
        			'FMD_ADDRESSED_TO'		=> $ADDRESSED_TO,
        			'FMD_RECD_DEP'			=> $RECD_DEP,
        			'FMD_RECV_DT'			=> $RECV_DT,
        			'FMD_RECEIVER_ID'		=> $RECEIVER_ID,
        			'FMD_ACTUAL_RECV'		=> $ACTUAL_RECV,
        			'FMD_RECD_PAGES'		=> 0,
        			'FMD_RECD_STATUS'		=> 'Y',
        			'FMD_ACTION_TAKEN'		=> '',
        			'FMD_ACTION_DT'			=> '',
        			'FMD_REC_NO'			=> 1,
        			'FMD_LEVEL_NO'			=> 1,
        			'FMD_FWD_NO'			=> '',
        			'FMD_LEVEL_REC'			=> '',
        			'FMD_REMARKS'			=> '',
        			'FMD_ADDRESSED_TO_1'	=> '',
        			'FMD_PRINT_STATUS'		=> 'N'
       			);
        		return $this->db->insert('FILE_MV_DTL', $data_view);
        	}
        	return $filemst_result;
   		}
   		return $result;
		}
  	}

  	//This function Finds New COMPLAINT Id from COMPLAINT_MST table
	public function get_New_CM_NO(){

		$this->db->select_max('CM_NO');
		$query = $this->db->get('COMPLAINT_MST'); 

		$row = $query->row();

		if (isset($row))
		     return $row->CM_NO + 1;
	}
	//This function Finds New COMPLAINT ACTION Id from MJ_COMPLAINT_ACTION_DTL table
	public function get_New_CA_NO(){
		$this->db->select_max('MJ_CA_ID');
		$query = $this->db->get('MJ_COMPLAINT_ACTION_DTL'); 
		$row = $query->row();
    		
		if (isset($row))
		     return $row->MJ_CA_ID + 1;
	}

	//This function Finds New COMPLAINT ASSIGN Id from MJ_COMPLAINT_ASSIGN_DTL table
	public function get_New_CAD_NO(){

		$this->db->select_max('MJ_CAD_ID');
		$query = $this->db->get('MJ_COMPLAINT_ASSIGN_DTL'); 
		$row = $query->row();

		if (isset($row))
		     return $row->MJ_CAD_ID + 1;
	}

	//This function Finds Status of FTS_BASED  from COMPLAINT_SUB_CATEGORY table
	public function check_fts_status($CM_COMPLAINT_SUB_TYPE){
		$this->db->select('CSC_FTS_BASED');
		$this->db->where('CSC_NO',$CM_COMPLAINT_SUB_TYPE);
		$query = $this->db->get('COMPLAINT_SUB_CATEGORY');
  		$row = $query->row();
		if (isset($row))
		     return $row->CSC_FTS_BASED;
	}

	//This function Finds New FTS Number from FILE_MST table
	public function get_New_FTS_No(){

		$this->db->select_max('FM_FILE_ID');
		$query = $this->db->get('FILE_MST');
		$row = $query->row();

		if (isset($row))
		     return $row->FM_FILE_ID + 1;
	}

	//get employee Name for on call comlaint registration from database
	public function getEmpName($UserId){
		$this->db->select('EMP_NAME');
		$this->db->from('ALL_EMP_MST A');
		$this->db->where(['A.EMP_ID'=>'EMP\\'.$UserId]);
		$query = $this->db->get();				
    	$row = $query->row();

		if (isset($row))
		     return $row->EMP_NAME;
	}

	// this function fetch emp department
	public function getEmpDep($dept){
		$this->db->select('DEP_DESC');
		$this->db->from('ALL_DEP_MST');
		$this->db->where('DEP_ID',$dept);
		$query = $this->db->get();
		if($query->num_rows() > 0) 
				return $query->row()->DEP_DESC;
			else
				return '0';

	}

	// This function find complaint type desc from complaint_category table
	public function fetch_complaint_type_desc($CM_COMPLAINT_TYPE){
		$this->db->select('CC_NAME');
		$this->db->where('CC_NO',$CM_COMPLAINT_TYPE);
		$query = $this->db->get('COMPLAINT_CATEGORY');
  		$row = $query->row();
		if (isset($row))
		     return $row->CC_NAME;
	}

	// This function find complaint sub type desc from complaint_category table
	public function fetch_complaint_sub_type_desc($CM_COMPLAINT_SUB_TYPE){
		$this->db->select('CSC_NAME');
		$this->db->where('CSC_NO',$CM_COMPLAINT_SUB_TYPE);
		$query = $this->db->get('COMPLAINT_SUB_CATEGORY');
  		$row = $query->row();
		if (isset($row))
		     return $row->CSC_NAME;
	}

	// This function find departmental email id for from email id
	public function fetch_cc_email_id($CM_COMPLAINT_TYPE){
		$this->db->distinct('DEP_EMAIL');
		$this->db->select('DEP_EMAIL');
		$this->db->join('MJ_USER_COMP_TYPE_AUTH M','M.MJ_UCTA_DEPID=A.DEP_ID');
		$this->db->where('M.MJ_CC_NO',$CM_COMPLAINT_TYPE);
		$query = $this->db->get('ALL_DEP_MST A');
  		$row = $query->row();
		if (isset($row))
		     return $row->DEP_EMAIL;
	}
	// This function find departmental email id for from email id
	public function fetch_cm_department($CM_COMPLAINT_TYPE){
		$this->db->distinct('DEP_DESC');
		$this->db->select('DEP_DESC');
		$this->db->join('MJ_USER_COMP_TYPE_AUTH M','M.MJ_UCTA_DEPID=A.DEP_ID');
		$this->db->where('M.MJ_CC_NO',$CM_COMPLAINT_TYPE);
		$query = $this->db->get('ALL_DEP_MST A');
  		$row = $query->row();
		if (isset($row))
		     return $row->DEP_DESC;
	}

	// This function find complaint sub type desc from complaint_category table
	public function fetch_from_email_id($CM_COMPLAINT_SUB_TYPE){
		$this->db->select('EMAILCC');
		$this->db->where('CSC_NO',$CM_COMPLAINT_SUB_TYPE);
		$query = $this->db->get('COMPLAINT_SUB_CATEGORY');
  		$row = $query->row();
		if (isset($row))
		     return $row->EMAILCC;
	}


	// this function fine PBNo from table file_mv_dtl
	public function getPBNO($dept){	

		$response=$this->db->query("SELECT max(to_number(substr(FMD_PB_NO, instr(FMD_PB_NO,'-')+1))) PBNO 
					FROM FILE_MV_DTL
					WHERE FMD_SENDER_DEPT = '$dept'
					")->row(); 

        if (isset($response))
		return $response->PBNO;    
	}

	//fetch complaint details on the basis of user id from database
	public function getComplaintDtl($UserId){
		$orderBy = "CM_NO DESC";
		$condition_date = "01-JAN-2020";
		$dateFormate 	= "DAY, DD-Mon-YYYY HH:MI:SS am";
		//for Employee 
		$where = "CM_EMP_ID IS NOT NULL";         			
		$this->db->select('CM_NO,CC_NAME,CSC_NAME,CM_COMPLAINT_TEXT,TO_CHAR(CM_COMPLAINT_DATE, '."'$dateFormate'".') REGDATE,TO_CHAR(CM_COMPLAINT_CLOSE_DATE, '."'$dateFormate'".') CLOSEDDATE');
		$this->db->from('COMPLAINT_MST A');
		$this->db->join('COMPLAINT_CATEGORY D', 'A.CM_COMPLAINT_CATEGORY=D.CC_NO');
		$this->db->join('COMPLAINT_SUB_CATEGORY B', 'A.CM_COMPLAINT_SUB_CATEGORY=B.CSC_NO');
		$this->db->where(['A.CM_EMP_ID'=>'EMP\\'.$UserId]);
		$this->db->where($where);
		$this->db->where('CM_COMPLAINT_DATE >=', $condition_date);
		$query1 = $this->db->get_compiled_select();	

		//for Student 
		$where = "CM_STU_ID IS NOT NULL";		
		$this->db->select('CM_NO,CC_NAME,CSC_NAME,CM_COMPLAINT_TEXT,TO_CHAR(CM_COMPLAINT_DATE, '."'$dateFormate'".') REGDATE,TO_CHAR(CM_COMPLAINT_CLOSE_DATE, '."'$dateFormate'".') CLOSEDDATE');
		$this->db->from('COMPLAINT_MST A');
		$this->db->join('COMPLAINT_CATEGORY D', 'A.CM_COMPLAINT_CATEGORY=D.CC_NO');
		$this->db->join('COMPLAINT_SUB_CATEGORY B', 'A.CM_COMPLAINT_SUB_CATEGORY=B.CSC_NO');
		$this->db->where('A.CM_STU_ID',$UserId);
		$this->db->where($where);
		$this->db->where('CM_COMPLAINT_DATE >=', $condition_date);
		$query2 = $this->db->get_compiled_select();

		//for contrator and profession staff
		$where = "CM_CMM_ID IS NOT NULL";
		$this->db->select('CM_NO,CC_NAME,CSC_NAME,CM_COMPLAINT_TEXT,TO_CHAR(CM_COMPLAINT_DATE, '."'$dateFormate'".') REGDATE,TO_CHAR(CM_COMPLAINT_CLOSE_DATE, '."'$dateFormate'".') CLOSEDDATE');
		$this->db->from('COMPLAINT_MST A');
		$this->db->join('COMPLAINT_CATEGORY D', 'A.CM_COMPLAINT_CATEGORY=D.CC_NO');
		$this->db->join('COMPLAINT_SUB_CATEGORY B', 'A.CM_COMPLAINT_SUB_CATEGORY=B.CSC_NO');
		$this->db->join('COMPANY_MST C', 'A.CM_CMM_ID = C.CMM_ID');
		$this->db->where('A.CM_CMM_ID',$UserId);
		$this->db->where($where);
		$this->db->where('CM_COMPLAINT_DATE >=', $condition_date);
		$this->db->order_by($orderBy);
		$query3 = $this->db->get_compiled_select();

		$data = $this->db->query($query1 . ' UNION ' . $query2 . ' UNION ' . $query3);

		return $data->result();
	}

	//this function used for fetch sigle fee details to view and print
	public function getSingleComplaintDetails($COM_NO,$UserId){
		$orderBy = "MJ_CA_ID DESC";	
		$dateFormate 	= "DAY, DD-Mon-YYYY HH:MI:SS am";
		$where = "CM_EMP_ID IS NOT NULL";         			
		$this->db->select('MJ_CA_ID,CM_NO,CC_NAME,CSC_NAME,CM_COMPLAINT_TEXT,MJ_CA_REMARKS,
			MJ_CA_ACTION,CM_COMPLAINT_DATE,	CM_COMPLAINT_CLOSE_DATE,TO_CHAR(MJ_CA_ACTION_DATE, '."'$dateFormate'".') ACTIONDATE');
		$this->db->from('COMPLAINT_MST A');
		$this->db->join('COMPLAINT_CATEGORY D', 'A.CM_COMPLAINT_CATEGORY=D.CC_NO');
		$this->db->join('COMPLAINT_SUB_CATEGORY B', 'A.CM_COMPLAINT_SUB_CATEGORY=B.CSC_NO');
		$this->db->join('MJ_COMPLAINT_ACTION_DTL C', 'A.CM_NO = C.MJ_CA_CM_NO');
		$this->db->where(['A.CM_EMP_ID'=>'EMP\\'.$UserId]);
		$this->db->where('C.MJ_CA_CM_NO',$COM_NO);
		$this->db->where($where);
		$query1 = $this->db->get_compiled_select();	

		//for Student 
		$where = "CM_STU_ID IS NOT NULL";
		$this->db->select('MJ_CA_ID,CM_NO,CC_NAME,CSC_NAME,CM_COMPLAINT_TEXT,MJ_CA_REMARKS,
			MJ_CA_ACTION,CM_COMPLAINT_DATE,CM_COMPLAINT_CLOSE_DATE,TO_CHAR(MJ_CA_ACTION_DATE, '."'$dateFormate'".') ACTIONDATE');
		$this->db->from('COMPLAINT_MST A');
		$this->db->join('COMPLAINT_CATEGORY D', 'A.CM_COMPLAINT_CATEGORY=D.CC_NO');
		$this->db->join('COMPLAINT_SUB_CATEGORY B', 'A.CM_COMPLAINT_SUB_CATEGORY=B.CSC_NO');
		$this->db->join('MJ_COMPLAINT_ACTION_DTL C', 'A.CM_NO = C.MJ_CA_CM_NO');
		$this->db->where('A.CM_STU_ID',$UserId);
		$this->db->where('C.MJ_CA_CM_NO',$COM_NO);
		$this->db->where($where);
		$query2 = $this->db->get_compiled_select();

		//for contrator and profession staff
		$where = "CM_CMM_ID IS NOT NULL";
		$this->db->select('MJ_CA_ID,CM_NO,CC_NAME,CSC_NAME,CM_COMPLAINT_TEXT,MJ_CA_REMARKS,
			MJ_CA_ACTION,CM_COMPLAINT_DATE,CM_COMPLAINT_CLOSE_DATE,TO_CHAR(MJ_CA_ACTION_DATE, '."'$dateFormate'".') ACTIONDATE');
		$this->db->from('COMPLAINT_MST A');
		$this->db->join('COMPLAINT_CATEGORY D', 'A.CM_COMPLAINT_CATEGORY=D.CC_NO');
		$this->db->join('COMPLAINT_SUB_CATEGORY B', 'A.CM_COMPLAINT_SUB_CATEGORY=B.CSC_NO');
		$this->db->join('MJ_COMPLAINT_ACTION_DTL C', 'A.CM_NO = C.MJ_CA_CM_NO');
		$this->db->where('A.CM_CMM_ID',$UserId);
		$this->db->where('C.MJ_CA_CM_NO',$COM_NO);
		$this->db->where($where);
		$this->db->order_by($orderBy);
		$query3 = $this->db->get_compiled_select();
		$data = $this->db->query($query1 . ' UNION ' . $query2 . ' UNION ' . $query3);
		foreach($data->result() as $v_csdtl){
  		$output ='<table class="table table-striped table-bordered table-hover " width="550" align="center" style="font-size:14px; font-family:Calibri; border-radius: 10px;
  			border: 1px solid;">';
  		$output .='<tbody>
  					<tr>
                    	<td valign="top" height="20" align="left"><strong>Complaint No.: </strong></td>
                    	<td valign="top" align="left">&nbsp;&nbsp;'.$v_csdtl->CM_NO.'</td>
                    	<td valign="top" align="left"><strong>&nbsp;&nbsp;&nbsp;&nbsp;Complaint Category: </strong></td>
                    	<td valign="top" align="left">&nbsp;&nbsp;'.$v_csdtl->CC_NAME.'</td>
                    	<td valign="top" align="left"><strong>&nbsp;&nbsp;&nbsp;&nbsp;Sub Category: </strong></td>
                    	<td valign="top" align="left">&nbsp;&nbsp;'.$v_csdtl->CSC_NAME.'</td>
                  	</tr>
                  	</tbody><br><br>';
        $output .= '</table>';
        }
		$output .= '<table class="table table-striped table-bordered table-hover " width="550" align="center" style="font-size:14px; font-family:Calibri; border-radius: 10px;
  			border: 1px solid;">';
  		
  		$output .='<thead>    
            		<tr>
              			<th>S.No</th>
              			<th>Remarks</th>
              			<th>Action Date</th>
              			<th>Complaint Status</th>
            		</tr>
            	</thead>';
		 	$no = 0;
            foreach($data->result() as $v_csdtl):
            $no++;
		 	$output .='<tbody>
		 		 <tr>
              		<td>'.$no.'</td>
              		<td>'.$v_csdtl->MJ_CA_REMARKS.'</td>
              		<td>'.$v_csdtl->ACTIONDATE.'</td>
              		<td>'.$v_csdtl->MJ_CA_ACTION.'</td>
            	</tr>                 
                </tbody>
              ';
		 	endforeach;
		$output .= '</table>';
		return $output; 
	}

		//this function is use for fetch details of complaint section wise for print
	public function getComplaints($cc_no, $UserType){ 
		$dateFormate 	= "DAY, DD-Mon-YYYY HH:MI:SS am";         			
		$this->db->select('SUM(MJ_CAD_CM_NO_UNIT) NO_UNIT,A.CM_NO, DEP_DESC, EMP_NAME(A.CM_EMP_ID) NAME,CSC_NAME,A.CM_COMPLAINT_TEXT,CMM_DESC EMPNAME,A.CM_COMPLAINT_LOCATION,A.CM_COMPLAINT_CONTACT_PERSON,A.CM_COMPLAINT_CONTACT_MOBILE,A.CM_COMPLAINT_CONTACT_EMAIL,TO_CHAR(CM_COMPLAINT_DATE, '."'$dateFormate'".') REGDATE,TO_CHAR(CM_COMPLAINT_CLOSE_DATE, '."'$dateFormate'".') CLOSEDDATE,MJ_CAD_ID');
		$this->db->from('COMPLAINT_MST A');
		$this->db->join('COMPLAINT_SUB_CATEGORY B','A.CM_COMPLAINT_SUB_CATEGORY=B.CSC_NO');
		$this->db->join('MJ_COMPLAINT_ASSIGN_DTL C', 'A.CM_NO= C.MJ_CAD_CM_NO ');
		$this->db->join('DEP_MST D', 'A.CM_DEP_ID= D.DEP_ID ');
		$this->db->join('COMPANY_MST F', 'C.MJ_CAD_CMM_ID= F.CMM_ID ');
		$this->db->where('A.CM_COMPLAINT_CATEGORY', $cc_no);
		$this->db->where('B.CSC_USER_TYPE', $UserType);
		$this->db->group_by('A.CM_NO, DEP_DESC, A.CM_EMP_ID,CSC_NAME,
		A.CM_COMPLAINT_TEXT,CMM_DESC,A.CM_COMPLAINT_LOCATION,A.CM_COMPLAINT_CONTACT_PERSON,A.CM_COMPLAINT_CONTACT_MOBILE,A.CM_COMPLAINT_CONTACT_EMAIL,CM_COMPLAINT_DATE,CM_COMPLAINT_CLOSE_DATE,MJ_CAD_ID');
		//$this->db->order_by('CM_COMPLAINT_DATE', 'DESC');
		$query1 = $this->db->get_compiled_select();

		$this->db->select('SUM(MJ_CAD_CM_NO_UNIT) NO_UNIT,A.CM_NO, DEP_DESC, EMP_NAME(A.CM_EMP_ID) NAME,CSC_NAME, A.CM_COMPLAINT_TEXT,EMP_NAME(EMP_ID) EMPNAME,				A.CM_COMPLAINT_LOCATION,A.CM_COMPLAINT_CONTACT_PERSON,A.CM_COMPLAINT_CONTACT_MOBILE,A.CM_COMPLAINT_CONTACT_EMAIL,TO_CHAR(CM_COMPLAINT_DATE, '."'$dateFormate'".') REGDATE,TO_CHAR(CM_COMPLAINT_CLOSE_DATE, '."'$dateFormate'".') CLOSEDDATE,MJ_CAD_ID');
		$this->db->from('COMPLAINT_MST A');
		$this->db->join('COMPLAINT_SUB_CATEGORY B','A.CM_COMPLAINT_SUB_CATEGORY=B.CSC_NO');
		$this->db->join('MJ_COMPLAINT_ASSIGN_DTL C', 'A.CM_NO= C.MJ_CAD_CM_NO ');
		$this->db->join('DEP_MST D', 'A.CM_DEP_ID= D.DEP_ID ');
		$this->db->join('EMP_MST E', 'C.MJ_CAD_EMP_ID= E.EMP_ID ');
		$this->db->where('A.CM_COMPLAINT_CATEGORY', $cc_no);
		$this->db->where('B.CSC_USER_TYPE', $UserType);
		$this->db->group_by('A.CM_NO, DEP_DESC, A.CM_EMP_ID,CSC_NAME, A.CM_COMPLAINT_TEXT,EMP_ID,
			A.CM_COMPLAINT_LOCATION,A.CM_COMPLAINT_CONTACT_PERSON,A.CM_COMPLAINT_CONTACT_MOBILE,A.CM_COMPLAINT_CONTACT_EMAIL,CM_COMPLAINT_DATE,CM_COMPLAINT_CLOSE_DATE,MJ_CAD_ID');
		//$this->db->order_by('CM_COMPLAINT_DATE', 'DESC');
		$query2 = $this->db->get_compiled_select();
		$data = $this->db->query($query1 . ' UNION ' . $query2);
		return $data->result();			
	}

	//this function is used to get complaint section desc from mj_user_type table
	public function getUserTypeName($UserType){
		$this->db->select('MJ_USER_TYPE_NAME');
		$this->db->where('MJ_USER_TYPE_ID',$UserType);
		$query = $this->db->get('MJ_USER_TYPE');
  		$row = $query->row();
		if (isset($row))
		     return $row->MJ_USER_TYPE_NAME;
	}

	//
	public function getComplaintDetails($COM_NO,$TicketNo){
		$dateFormate 	= "DD-Mon-YYYY";
		$where = "CM_EMP_ID IS NOT NULL";         			
		$this->db->select('CM_NO, "DEP_DESC", EMP_NAME(A.CM_EMP_ID) NAME,"CC_NAME","CSC_NAME", CM_COMPLAINT_TEXT,CM_COMPLAINT_LOCATION,CM_COMPLAINT_CONTACT_PERSON,CM_COMPLAINT_CONTACT_MOBILE, CM_COMPLAINT_CONTACT_EMAIL, CM_COMPLAINT_FTS_NO, CM_COMPLAINT_STATUS,TO_CHAR(CM_COMPLAINT_DATE, '."'$dateFormate'".') REGDATE,CM_NO_UNIT,CM_EMP_ID COMPLAINT_USER_ID');
		$this->db->from('COMPLAINT_MST A');
		$this->db->join('COMPLAINT_CATEGORY D', 'A.CM_COMPLAINT_CATEGORY=D.CC_NO');
		$this->db->join('COMPLAINT_SUB_CATEGORY B', 'A.CM_COMPLAINT_SUB_CATEGORY=B.CSC_NO');
		//$this->db->join('MJ_COMPLAINT_ASSIGN_DTL M', 'M.MJ_CAD_CM_NO=A.CM_NO');
		$this->db->join('DEP_MST C', 'A.CM_DEP_ID= C.DEP_ID ');	
		$this->db->where('A.CM_NO',$COM_NO);
		$this->db->where($where);
		//$this->db->where($IsNoNull);
		$query1 = $this->db->get_compiled_select();	

		//for Student 
		$where = "CM_STU_ID IS NOT NULL";
		$this->db->select('CM_NO, "DEP_DESC", STU_NAME(A.CM_STU_ID) NAME,"CC_NAME","CSC_NAME", CM_COMPLAINT_TEXT, CM_COMPLAINT_LOCATION, CM_COMPLAINT_CONTACT_PERSON,CM_COMPLAINT_CONTACT_MOBILE, CM_COMPLAINT_CONTACT_EMAIL, CM_COMPLAINT_FTS_NO, CM_COMPLAINT_STATUS,TO_CHAR(CM_COMPLAINT_DATE, '."'$dateFormate'".') REGDATE,CM_NO_UNIT,CM_STU_ID COMPLAINT_USER_ID');
		$this->db->from('COMPLAINT_MST A');
		$this->db->join('COMPLAINT_CATEGORY D', 'A.CM_COMPLAINT_CATEGORY=D.CC_NO');
		$this->db->join('COMPLAINT_SUB_CATEGORY B', 'A.CM_COMPLAINT_SUB_CATEGORY=B.CSC_NO');
		//$this->db->join('MJ_COMPLAINT_ASSIGN_DTL M', 'M.MJ_CAD_CM_NO=A.CM_NO');
		$this->db->join('DEP_MST C', 'A.CM_DEP_ID= C.DEP_ID ');	
		$this->db->where('A.CM_NO',$COM_NO);
		$this->db->where($where);
		//$this->db->where($IsNoNull);
		$query2 = $this->db->get_compiled_select();

		//for contrator and profession staff
		$where = "CM_CMM_ID IS NOT NULL";
		$this->db->select('CM_NO, "DEP_DESC", CMM_DESC NAME,"CC_NAME","CSC_NAME", CM_COMPLAINT_TEXT, CM_COMPLAINT_LOCATION, CM_COMPLAINT_CONTACT_PERSON,CM_COMPLAINT_CONTACT_MOBILE, CM_COMPLAINT_CONTACT_EMAIL, CM_COMPLAINT_FTS_NO, CM_COMPLAINT_STATUS,TO_CHAR(CM_COMPLAINT_DATE, '."'$dateFormate'".') REGDATE,CM_NO_UNIT,A.CM_CMM_ID COMPLAINT_USER_ID');
		$this->db->from('COMPLAINT_MST A');
		$this->db->join('COMPLAINT_CATEGORY D', 'A.CM_COMPLAINT_CATEGORY=D.CC_NO');
		$this->db->join('COMPLAINT_SUB_CATEGORY B', 'A.CM_COMPLAINT_SUB_CATEGORY=B.CSC_NO');
		//$this->db->join('MJ_COMPLAINT_ASSIGN_DTL M', 'M.MJ_CAD_CM_NO=A.CM_NO');
		$this->db->join('COMPANY_MST C', 'A.CM_CMM_ID = C.CMM_ID');
		$this->db->join('DEP_MST C', 'A.CM_DEP_ID= C.DEP_ID ');	
		$this->db->where('A.CM_NO',$COM_NO);
		$this->db->where($where);
		//$this->db->where($IsNoNull);
		$query3 = $this->db->get_compiled_select();

		$data = $this->db->query($query1 . ' UNION ' . $query2 . ' UNION ' . $query3);

		$output = '<table class="table table-striped table-hover" width="550" align="center" style="font-size:14px; font-family:Calibri; border-radius: 10px;border: 1px solid;">';
		 	foreach($data->result() as $v_cdtl){
		 	$output .='<tbody>
              <tr>
                <td valign="top" height="20" align="left"><strong>Complaint No: </strong></td>
                <td valign="top" align="left"><b>'.$COM_NO.'</b></td>
                <td valign="top" align="left"><strong>Ticket No: </strong></td>
                <td valign="top" align="left">'.$TicketNo.'</td>
                <td valign="top" align="left"><strong>Complaint Reg. Date: </strong></td>
                <td valign="top" align="left">'.$v_cdtl->REGDATE.'</td>
              </tr>              
              <tr>                
                <td valign="top" height="20" align="left"><strong>Name Of Complainent: </strong></td>
                <td valign="top" align="left">'.ucwords(strtoupper("$v_cdtl->CM_COMPLAINT_CONTACT_PERSON")).
                '</td>
                <td valign="top" align="left"><strong>Email Id: </strong></td>
                <td valign="top" align="left">'.$v_cdtl->CM_COMPLAINT_CONTACT_EMAIL.
                '</td>
          		<td valign="top" align="left"><strong>Mobile No: </strong></td>
                <td valign="top" align="left">'.$v_cdtl->CM_COMPLAINT_CONTACT_MOBILE.'
                </td>                
              </tr>
              <tr>                
                <td valign="top" height="20"align="left"><strong>Complaint Type.: </strong></td>
                <td valign="top" align="left">'.$v_cdtl->CC_NAME.
                '</td>
                <td valign="top" align="left"><strong>Complaint Sub Type: </strong></td>
                <td valign="top" align="left">'.$v_cdtl->CSC_NAME.
                '</td>
          		<td valign="top" align="left"><strong>No. of Faulty Equipment/Service Unit: </strong></td>
                <td valign="top" align="left">'.$v_cdtl->CM_NO_UNIT.
                '</td>                
              </tr>              
              <tr>                
                <td valign="top" height="20"align="left"><strong>Description of problem: </strong></td>
                <td valign="top" align="left">'.ucwords(strtolower("$v_cdtl->CM_COMPLAINT_TEXT")).
                '</td>
                <td valign="top" align="left"><strong>Complaint Department: </strong></td>
                <td valign="top" align="left">'.ucwords(strtoupper("$v_cdtl->DEP_DESC")).
                '</td>
          		<td valign="top" align="left"><strong>Complaint Location: </strong></td>
                <td valign="top" align="left">'.$v_cdtl->CM_COMPLAINT_LOCATION.
                '</td>                
              </tr>
            </tbody>';
		 	} 
		$output .= '</table><font size="16"><strong>Ticket History:</strong></font>';

		return $output;
	}

	//
	public function getComplaintHistory($COM_NO){
		$orderBy = "MJ_CA_ID ASC";	
		$dateFormate 	= "DD-Mon-YYYY HH:MI:SS am";
		$where = "CM_EMP_ID IS NOT NULL";         			
		$this->db->select('MJ_CA_ID,CM_NO,CC_NAME,CSC_NAME,CM_COMPLAINT_TEXT,MJ_CA_REMARKS,
			MJ_CA_ACTION,CM_COMPLAINT_DATE,	CM_COMPLAINT_CLOSE_DATE,TO_CHAR(MJ_CA_ACTION_DATE, '."'$dateFormate'".') ACTIONDATE');
		$this->db->from('COMPLAINT_MST A');
		$this->db->join('COMPLAINT_CATEGORY D', 'A.CM_COMPLAINT_CATEGORY=D.CC_NO');
		$this->db->join('COMPLAINT_SUB_CATEGORY B', 'A.CM_COMPLAINT_SUB_CATEGORY=B.CSC_NO');
		$this->db->join('MJ_COMPLAINT_ACTION_DTL C', 'A.CM_NO = C.MJ_CA_CM_NO');
		$this->db->where('C.MJ_CA_CM_NO',$COM_NO);
		$this->db->where($where);
		$this->db->order_by($orderBy);
		$query = $this->db->get();	
		
		$output .= '<table class="table table-striped table-bordered table-hover " width="550" align="center" style="font-size:14px; font-family:Calibri; border-radius: 10px;
  			border: 1px solid;">';
  		
  		$output .='<thead>    
            		<tr>
              			<th>S.No</th>
              			<th>Remarks</th>
              			<th>Action Date</th>
              			<th>Complaint Status</th>
            		</tr>
            	</thead>';
		 	$no = 0;
            foreach($query->result() as $v_csdtl):
            $no++;
		 	$output .='<tbody>
		 		 <tr>
              		<td>'.$no.'</td>
              		<td>'.$v_csdtl->MJ_CA_REMARKS.'</td>
              		<td>'.$v_csdtl->ACTIONDATE.'</td>
              		<td>'.$v_csdtl->MJ_CA_ACTION.'</td>
            	</tr>                 
                </tbody>
              ';
		 	endforeach;
		$output .= '</table>';
		return $output;

	}

	//
	public function getComplaintAttended($COM_NO,$TicketNo){
		$dateFormate 		= "DD-MM-YYYY";
		$printDateFormate 	= "DAY, DD-Mon-YYYY HH:MI:SS am";
		$printdate	=date('d-m-Y');
		$this->db->select('CMM_DESC EMPNAME,TO_CHAR(TO_DATE('."'$printdate'".', '."'$dateFormate'".'), '."'$printDateFormate'".') PRINTDATE,MJ_CAD_COMPLAINT_STATUS,MJ_CAD_REMARKS');
		$this->db->from('MJ_COMPLAINT_ASSIGN_DTL A');
		$this->db->join('COMPANY_MST F', 'A.MJ_CAD_CMM_ID= F.CMM_ID ');
		$this->db->where('A.MJ_CAD_CM_NO', $COM_NO);
		$this->db->where('A.MJ_CAD_ID', $TicketNo);
		$query1 = $this->db->get_compiled_select();

		$this->db->select('EMP_NAME(A.MJ_CAD_EMP_ID) EMPNAME,TO_CHAR(TO_DATE('."'$printdate'".', '."'$dateFormate'".'), '."'$printDateFormate'".') PRINTDATE,MJ_CAD_COMPLAINT_STATUS,MJ_CAD_REMARKS');
		$this->db->from('MJ_COMPLAINT_ASSIGN_DTL A');
		$this->db->join('EMP_MST E', 'A.MJ_CAD_EMP_ID= E.EMP_ID ');
		$this->db->where('A.MJ_CAD_CM_NO', $COM_NO);
		$this->db->where('A.MJ_CAD_ID', $TicketNo);
		$query2 = $this->db->get_compiled_select();
		$data = $this->db->query($query1 . ' UNION ' . $query2);
		foreach($data->result() as $v_csdtl){
			if ($v_csdtl->MJ_CAD_COMPLAINT_STATUS == 'Closed') {
				$output .= '<table class="table table-striped table-bordered table-hover " width="550" align="center" style="font-size:14px; font-family:Calibri; border-radius: 10px;
  					border: 1px solid;">
  				<tr>
  					<td>&nbsp;</td>
  					<td>&nbsp;</td>
  					<td>&nbsp;</td>
  				</tr>
  				<tr>
  					<td>Whether Ticket is Closed/Put on Hold (Yes/No): </td>
  					<td>Yes - ('.$v_csdtl->MJ_CAD_COMPLAINT_STATUS.')</td>
  					<td>&nbsp;</td>
  				</tr>
  				<tr>
					<td>Comments :</td>
					<td colspan="2">'.$v_csdtl->MJ_CAD_REMARKS.'</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
  					<td>&nbsp;</td>
  					<td>&nbsp;</td>
  					<td>&nbsp;</td>
  				</tr>
  				</table>';
			}elseif ($v_csdtl->MJ_CAD_COMPLAINT_STATUS == 'Put On Hold') {
				$output .= '<table class="table table-striped table-bordered table-hover " width="550" align="center" style="font-size:14px; font-family:Calibri; border-radius: 10px;
  					border: 1px solid;">
  				<tr>
  					<td>&nbsp;</td>
  					<td>&nbsp;</td>
  					<td>&nbsp;</td>
  				</tr>
  				<tr>
  					<td>Whether Ticket is Closed/Put on Hold (Yes/No): </td>
  					<td>Yes - ('.$v_csdtl->MJ_CAD_COMPLAINT_STATUS.')</td>
  					<td>&nbsp;</td>
  				</tr>
  				<tr>
					<td>Comments :</td>
					<td colspan="2">'.$v_csdtl->MJ_CAD_REMARKS.'</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
  					<td>&nbsp;</td>
  					<td>&nbsp;</td>
  					<td>&nbsp;</td>
  				</tr>
  				</table>';
  				$output .= 'Whether Ticket is Closed (Yes/No):  ............................. <br>
				Comment :
					<textarea rows="7"cols="200"></textarea>';

			}else{
				$output .= '<br><br>Whether Ticket is Closed/Put on Hold (Yes/No):  ............................. <br>
				Comment :
					<textarea rows="7"cols="200"></textarea>';
			}
		}
		$output .= '<br><br>';
  		foreach($data->result() as $v_csdtl):
  		$output .='<table align="right"><tr><td>( .......................................... )</td></tr><tr><td>User Signature with </td></tr><tr><td>( Name, Date & Office Stamp )</td></tr></table><br><br><br><br>';
       
		$output .='<table align="center"<tr><td>------------------------------------------- For Office Use ----------------------------------------</td></tr></table><br><br>';
		$output .='<table align="center"><tr><td>( .......................................... )</td><td>&nbsp;&nbsp;</td><td>( .......................................... )</td></tr><tr><td>Signature of Section Head </td>&nbsp;&nbsp;<td></td><td>Sign. of Ticket Attended By</td></tr></table>
		<br><br> ';
		$output .='<footer>
            Copyright &copy; Jamia Millia Islamia, Printed On : ('.$v_csdtl->PRINTDATE.') 
        	</footer>';
		endforeach;
		return $output;
	}
}
