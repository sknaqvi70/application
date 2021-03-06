<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 
class FeedbackModel extends CI_Model {
	public function __construct(){
		parent::__construct();
		$this->load->database();		
	}

	// This function is used for validate the record is already available or not
	public function ValidateRecordForFeedback($CUID,$cmno,$RID){
		$User = substr($RID, 1,1);
		if (ord($User) >= 65 && ord($User) <= 90 ) {
			$this->db->select('COUNT(MJ_FCD_EMP_ID) NO');
			$this->db->where('MJ_FCD_CM_NO',$cmno);
			$this->db->where('MJ_FCD_USER_ID',$CUID);
			$this->db->where('MJ_FCD_EMP_ID',$RID);

			$query = $this->db->get('MJ_FEEDBACK_COMPLAINT_DTL');

		}else{
			$this->db->select('COUNT(MJ_FCD_CMM_ID) NO');
			$this->db->where('MJ_FCD_CM_NO',$cmno);
			$this->db->where('MJ_FCD_USER_ID',$CUID);
			$this->db->where('MJ_FCD_CMM_ID',$RID);

			$query = $this->db->get('MJ_FEEDBACK_COMPLAINT_DTL');

		}

		if($query->num_rows() > 0) 
			return $query->row()->NO;
			else
				return -1;
		
	}

	//This function verifies if the link sent to the user has been generated by his/her email account
	public function VerifyEMail($cmno, $RText, $to) {

		$this->db->select('CM_COMPLAINT_CONTACT_EMAIL');
		$this->db->where('CM_NO',$cmno);
		$this->db->where('VERIFICATIONSTRING',$RText);

		$query = $this->db->get('COMPLAINT_MST');

		if($query->num_rows() > 0) {
			// Return verified if complaint mail id and mail received mail id is same;
			if ($query->row()->CM_COMPLAINT_CONTACT_EMAIL == $to)
				return 1;
			// Return -1 indicating that Account has already been created.
			else
				return -1;
		}
			
		else
			return 0;
	}

	//This function fetch registration date
	public function ComplaintRegistrationDate($cmno, $RText) {
		$dateFormate 	= "DD-Mon-YYYY HH:MI:SS am";
		$this->db->select('TO_CHAR(CM_COMPLAINT_DATE, '."'$dateFormate'".') REGDATE');
		$this->db->where('CM_NO',$cmno);
		$this->db->where('VERIFICATIONSTRING',$RText);

		$query = $this->db->get('COMPLAINT_MST');

		if($query->num_rows() > 0) 
				return $query->row()->REGDATE;
			else
				return '0'; //Error
	}

	//This function fetch Assigned date
	public function ComplaintAssignedDate($cmno, $RID) {
		$dateFormate 	= "DD-Mon-YYYY HH:MI:SS am";
		$where			= "MJ_CA_ACTION ='Assigned'";
		$this->db->select('TO_CHAR(A.MJ_CA_ACTION_DATE, '."'$dateFormate'".') ASSGDATE');
		$this->db->join('MJ_COMPLAINT_ASSIGN_DTL B', 'A.MJ_CA_CM_NO=B.MJ_CAD_CM_NO');
		$this->db->where('MJ_CA_CM_NO',$cmno);
		$this->db->where('MJ_CAD_CMM_ID',$RID);
		$this->db->where($where);
		$query1 = $this->db->get_compiled_select('MJ_COMPLAINT_ACTION_DTL A');

		$this->db->select('TO_CHAR(A.MJ_CA_ACTION_DATE, '."'$dateFormate'".') ASSGDATE');
		$this->db->join('MJ_COMPLAINT_ASSIGN_DTL B', 'A.MJ_CA_CM_NO=B.MJ_CAD_CM_NO');
		$this->db->where('MJ_CA_CM_NO',$cmno);
		$this->db->where('MJ_CAD_EMP_ID',$RID);
		$this->db->where($where);
		$query2 = $this->db->get_compiled_select('MJ_COMPLAINT_ACTION_DTL A');

		$data = $this->db->query($query1 . ' UNION ' . $query2);

		if($data->num_rows() > 0) 
				return $data->row()->ASSGDATE;
			else
				return '0'; //Error
	}

	//This function fetch Closed date
	public function ComplaintClousreDate($cmno, $RID) {
		$dateFormate 	= "DD-Mon-YYYY HH:MI:SS am";
		$where			= "MJ_CA_ACTION ='Closed'";
		$this->db->select('TO_CHAR(A.MJ_CA_ACTION_DATE, '."'$dateFormate'".') CLSDATE');
		$this->db->join('MJ_COMPLAINT_ASSIGN_DTL B', 'A.MJ_CA_CM_NO=B.MJ_CAD_CM_NO');
		$this->db->where('MJ_CA_CM_NO',$cmno);
		$this->db->where('MJ_CAD_CMM_ID',$RID);
		$this->db->where($where);
		$query1 = $this->db->get_compiled_select('MJ_COMPLAINT_ACTION_DTL A');

		$this->db->select('TO_CHAR(A.MJ_CA_ACTION_DATE, '."'$dateFormate'".') CLSDATE');
		$this->db->join('MJ_COMPLAINT_ASSIGN_DTL B', 'A.MJ_CA_CM_NO=B.MJ_CAD_CM_NO');
		$this->db->where('MJ_CA_CM_NO',$cmno);
		$this->db->where('MJ_CAD_EMP_ID',$RID);
		$this->db->where($where);
		$query2 = $this->db->get_compiled_select('MJ_COMPLAINT_ACTION_DTL A');

		$data = $this->db->query($query1 . ' UNION ' . $query2);
		if($data->num_rows() > 0) 
				return $data->row()->CLSDATE;
			else
				return '0'; //Error
	}

	// THIS FUNCTION IS USED FOR INSERT FEEDBACK INTO MJ_FEEDBACK_COMPLAINT_DTL TABLE
	public function AddResourceFeedback($CUID,$cmno,$RID,$Q1_feedback,$Q2_feedback,$Q3_feedback,$Q4_feedback,$Q5_feedback,$FEEDBACK_SUGGESTION){
		
		$User = substr($RID, 1,1);
		if (ord($User) >= 65 && ord($User) <= 90 ) {
			$MJ_FCD_ID = $this->get_New_MJ_FCD_ID();
			// when complaint assign to regular employee this else condition run
			$data = array(
		    'MJ_FCD_ID' 				=>  $MJ_FCD_ID,
		    'MJ_FCD_USER_ID' 			=> 	$CUID,
		    'MJ_FCD_CM_NO'  			=> 	$cmno,
		    'MJ_FCD_EMP_ID'				=>  $RID,
		    'MJ_FCD_CMM_ID' 			=> 	'',
		    'MJ_FCD_CFQ_NO'				=>	'1',	// Assign to Engineer
		    'MJ_FCD_RESP_ID' 			=>	$Q1_feedback
			);			
			$response1 = $this->db->insert('MJ_FEEDBACK_COMPLAINT_DTL', $data);	
		if ($response1) {
			$MJ_FCD_ID = $this->get_New_MJ_FCD_ID();
			$data = array(
		    'MJ_FCD_ID' 				=>  $MJ_FCD_ID,
		    'MJ_FCD_USER_ID' 			=> 	$CUID,
		    'MJ_FCD_CM_NO'  			=> 	$cmno,
		    'MJ_FCD_EMP_ID'				=>  $RID,
		    'MJ_FCD_CMM_ID' 			=> 	'',
		    'MJ_FCD_CFQ_NO'				=>	'2',	// Assign to Engineer
		    'MJ_FCD_RESP_ID' 			=>	$Q1_feedback
			);				
			$response2 = $this->db->insert('MJ_FEEDBACK_COMPLAINT_DTL', $data);
		}
		if ($response2) {
			$MJ_FCD_ID = $this->get_New_MJ_FCD_ID();
			$data = array(
		    'MJ_FCD_ID' 				=>  $MJ_FCD_ID,
		    'MJ_FCD_USER_ID' 			=> 	$CUID,
		    'MJ_FCD_CM_NO'  			=> 	$cmno,
		    'MJ_FCD_EMP_ID'				=>  $RID,
		    'MJ_FCD_CMM_ID' 			=> 	'',
		    'MJ_FCD_CFQ_NO'				=>	'3',	// Assign to Engineer
		    'MJ_FCD_RESP_ID' 			=>	$Q1_feedback
			);				
			$response3 = $this->db->insert('MJ_FEEDBACK_COMPLAINT_DTL', $data);
		}
		if ($response3) {
			$MJ_FCD_ID = $this->get_New_MJ_FCD_ID();
			$data = array(
		    'MJ_FCD_ID' 				=>  $MJ_FCD_ID,
		    'MJ_FCD_USER_ID' 			=> 	$CUID,
		    'MJ_FCD_CM_NO'  			=> 	$cmno,
		    'MJ_FCD_EMP_ID'				=>  $RID,
		    'MJ_FCD_CMM_ID' 			=> 	'',
		    'MJ_FCD_CFQ_NO'				=>	'4',	// Assign to Engineer
		    'MJ_FCD_RESP_ID' 			=>	$Q1_feedback
			);				
			$response4 = $this->db->insert('MJ_FEEDBACK_COMPLAINT_DTL', $data);
		}
		if ($response4) {
			$MJ_FCD_ID = $this->get_New_MJ_FCD_ID();
			$data = array(
		    'MJ_FCD_ID' 				=>  $MJ_FCD_ID,
		    'MJ_FCD_USER_ID' 			=> 	$CUID,
		    'MJ_FCD_CM_NO'  			=> 	$cmno,
		    'MJ_FCD_EMP_ID'				=>  $RID,
		    'MJ_FCD_CMM_ID' 			=> 	'',
		    'MJ_FCD_CFQ_NO'				=>	'5',	// Assign to Engineer
		    'MJ_FCD_RESP_ID' 			=>	$Q1_feedback
			);				
			$result = $this->db->insert('MJ_FEEDBACK_COMPLAINT_DTL', $data);
			}					
		}
		else // when complaint assign to contractor employee this else condition run
		{
			$MJ_FCD_ID = $this->get_New_MJ_FCD_ID();
			$data = array(
		    'MJ_FCD_ID' 				=>  $MJ_FCD_ID,
		    'MJ_FCD_USER_ID' 			=> 	$CUID,
		    'MJ_FCD_CM_NO'  			=> 	$cmno,
		    'MJ_FCD_EMP_ID'				=>  '',
		    'MJ_FCD_CMM_ID' 			=> 	$RID,
		    'MJ_FCD_CFQ_NO'				=>	'1',	// Assign to Engineer
		    'MJ_FCD_RESP_ID' 			=>	$Q1_feedback
			);			
			$response1 = $this->db->insert('MJ_FEEDBACK_COMPLAINT_DTL', $data);	
		if ($response1) {
			$MJ_FCD_ID = $this->get_New_MJ_FCD_ID();
			$data = array(
		    'MJ_FCD_ID' 				=>  $MJ_FCD_ID,
		    'MJ_FCD_USER_ID' 			=> 	$CUID,
		    'MJ_FCD_CM_NO'  			=> 	$cmno,
		    'MJ_FCD_EMP_ID'				=>  '',
		    'MJ_FCD_CMM_ID' 			=> 	$RID,
		    'MJ_FCD_CFQ_NO'				=>	'2',	// Assign to Engineer
		    'MJ_FCD_RESP_ID' 			=>	$Q1_feedback
			);				
			$response2 = $this->db->insert('MJ_FEEDBACK_COMPLAINT_DTL', $data);
		}
		if ($response2) {
			$MJ_FCD_ID = $this->get_New_MJ_FCD_ID();
			$data = array(
		    'MJ_FCD_ID' 				=>  $MJ_FCD_ID,
		    'MJ_FCD_USER_ID' 			=> 	$CUID,
		    'MJ_FCD_CM_NO'  			=> 	$cmno,
		    'MJ_FCD_EMP_ID'				=>  '',
		    'MJ_FCD_CMM_ID' 			=> 	$RID,
		    'MJ_FCD_CFQ_NO'				=>	'3',	// Assign to Engineer
		    'MJ_FCD_RESP_ID' 			=>	$Q1_feedback
			);				
			$response3 = $this->db->insert('MJ_FEEDBACK_COMPLAINT_DTL', $data);
		}
		if ($response3) {
			$MJ_FCD_ID = $this->get_New_MJ_FCD_ID();
			$data = array(
		    'MJ_FCD_ID' 				=>  $MJ_FCD_ID,
		    'MJ_FCD_USER_ID' 			=> 	$CUID,
		    'MJ_FCD_CM_NO'  			=> 	$cmno,
		    'MJ_FCD_EMP_ID'				=>  '',
		    'MJ_FCD_CMM_ID' 			=> 	$RID,
		    'MJ_FCD_CFQ_NO'				=>	'4',	// Assign to Engineer
		    'MJ_FCD_RESP_ID' 			=>	$Q1_feedback
			);				
			$response4 = $this->db->insert('MJ_FEEDBACK_COMPLAINT_DTL', $data);
		}
		if ($response4) {
			$MJ_FCD_ID = $this->get_New_MJ_FCD_ID();
			$data = array(
		    'MJ_FCD_ID' 				=>  $MJ_FCD_ID,
		    'MJ_FCD_USER_ID' 			=> 	$CUID,
		    'MJ_FCD_CM_NO'  			=> 	$cmno,
		    'MJ_FCD_EMP_ID'				=>  '',
		    'MJ_FCD_CMM_ID' 			=> 	$RID,
		    'MJ_FCD_CFQ_NO'				=>	'5',	// Assign to Engineer
		    'MJ_FCD_RESP_ID' 			=>	$Q1_feedback
			);				
			$result = $this->db->insert('MJ_FEEDBACK_COMPLAINT_DTL', $data);
		}
		}
		return $result;


	}
	//This function Finds New FEEDBACK COMPLAINT Id from MJ_FEEDBACK_COMPLAINT_DTL table
	public function get_New_MJ_FCD_ID(){

		$this->db->select_max('MJ_FCD_ID');
		$query = $this->db->get('MJ_FEEDBACK_COMPLAINT_DTL'); 
		$row = $query->row();

		if (isset($row))
		     return $row->MJ_FCD_ID + 1;
	}
}