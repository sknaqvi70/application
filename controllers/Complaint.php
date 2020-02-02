<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 
class Complaint extends CI_Controller {
	public function __construct(){
		parent::__construct();
		if(!isset($_SESSION['login'])) {
			echo 'Unauthorised access is not allowed! Click <a href="'.base_url().'">here</a> to login';
			redirect(base_url());
			
		}
		$this->load->model('ComplaintModel', 'CM');
	}
	// This function performs following fucnctions:
	// 1. 	Validate data receveid from complaintReg form 
	// 2. 	Store User Complaint Data
	// 3. 	Sends an Email for to User for successfull register complaint
	public function complaintRegistration(){

		// 1 Contact Person Name cannot be blank
			$this->form_validation->set_rules('CM_USER_NANE','Contact Person Name','required|alpha_numeric_spaces|max_length[50]');

			// 2 E-Mail Id cannot be blank 			
			$this->form_validation->set_rules('CM_USER_EMAIL','E-Mail Id','required|valid_email');

			//3 Mobile Number cannot be blank
			$this->form_validation->set_rules('CM_USER_MOBILE','Mobile Number','required|max_length[10]');

			//4 Complaint Location cannot be blank
			$this->form_validation->set_rules('CM_USER_LOCATION','Complaint Location','required');

			//5 Complaint Type must be Selected
			$this->form_validation->set_rules('CM_COMPLAINT_TYPE','Complaint Type','required|is_natural_no_zero');

			//6 Complaint Sub Type must be Selected
			$this->form_validation->set_rules('CM_COMPLAINT_SUB_TYPE','Complaint Sub Type','required|is_natural_no_zero');

			//7 Brief Description of Complaint is required
			$this->form_validation->set_rules('CM_COMPLAINT_DESC','Brief Description of Complaint','required|max_length[400]');

			//Set Error Delimeter

			$this->form_validation->set_error_delimiters("<p class='text-danger'>",'</p>');

			$UserType= $_SESSION['usertype'];
			$data['ComplaintTypeList'] = $this->CM->getComplaintCat($UserType);
			
			if ($this->form_validation->run() == FALSE) {		               
	     		
				$this->load->view('auth/complaintReg',$data);

	        }
	        else {

	       		$VerificationString = '';
	       		$TicketNo = '';
	       		$FtsNo = '';
	       		if($_SESSION['usertype'] == 1){
	       			$dept = $_SESSION['depid'];
	       			$deptdesc = $_SESSION['depdesc'];
	       		}

	       		if($_SESSION['usertype'] == 2){
	       			$dept = $_SESSION['empdepid'];
	       			$deptdesc = $_SESSION['empdepdesc'];
	       		}
	       		
	       		$UserId= $_SESSION['login'];
	       		$CM_USER_NANE			= $this->input->post('CM_USER_NANE');
	       		$CM_USER_EMAIL			= $this->input->post('CM_USER_EMAIL');
	       		$CM_USER_MOBILE			= $this->input->post('CM_USER_MOBILE');
	       		$CM_USER_LOCATION		= $this->input->post('CM_USER_LOCATION');
	       		$CM_COMPLAINT_TYPE 		= $this->input->post('CM_COMPLAINT_TYPE');
	       		$CM_COMPLAINT_SUB_TYPE 	= $this->input->post('CM_COMPLAINT_SUB_TYPE');
	       		$CM_COMPLAINT_DESC		= $this->input->post('CM_COMPLAINT_DESC'); 	       		

				$data['message']  = $this->CM->RegisterComplaint(
	       				$dept,
	       				$UserId,
	       				$CM_COMPLAINT_TYPE,
	       				$CM_COMPLAINT_SUB_TYPE,
	       				$CM_COMPLAINT_DESC,
	       				$CM_USER_LOCATION,
	       				$CM_USER_NANE,
	       				$CM_USER_MOBILE,
	       				$CM_USER_EMAIL,	       				
						$VerificationString,
						$TicketNo,
						$FtsNo
				);
				if ($data['message'] == 'OK') {

					$CM_COMPLAINT_TYPE_DESC= $this->CM->fetch_complaint_type_desc($CM_COMPLAINT_TYPE);

					$CM_COMPLAINT_SUB_TYPE_DESC= $this->CM->fetch_complaint_sub_type_desc($CM_COMPLAINT_SUB_TYPE);

					$this->SendMailToUser($CM_USER_EMAIL,$TicketNo,$CM_USER_NANE,$deptdesc,$CM_COMPLAINT_TYPE_DESC,$CM_COMPLAINT_SUB_TYPE_DESC,$CM_COMPLAINT_DESC,$CM_USER_LOCATION,$CM_USER_MOBILE,$FtsNo);
					if ($FtsNo) {
					$data= "Your Ticket No. - ".$TicketNo.' For Complain '.$CM_COMPLAINT_SUB_TYPE_DESC.' And FTS Number is '.$FtsNo.'. An email has been sent to '.$this->MaskUserEMail($CM_USER_EMAIL). '. Please login to your mailbox to see your complaint Details.';
					}else{
					$data= "Your Ticket No. - ".$TicketNo.' For Complain '.$CM_COMPLAINT_SUB_TYPE_DESC.'. An email has been sent to '.$this->MaskUserEMail($CM_USER_EMAIL). '. Please login to your mailbox to see your complaint Details.';
					}
					$this->session->set_flashdata('message',$data);
					redirect('Complaint/complaintRegistration');
					
				}
				}
	}

	public function ComplaintRegistered(){
		
	}
	// This function performs to fetch sub category of complaint
	public function getComplaintSubCategory(){
		$UserType= $_SESSION['usertype'];
		$postData = $this->input->post('v_MJ_COMPLAINT_TYPE');    
    	$data = $this->CM->getComplaintSubCat($postData,$UserType);        
    	echo json_encode($data); 
	}

	function SendMailToUser($CM_USER_EMAIL,$TicketNo,$CM_USER_NANE,$deptdesc,$CM_COMPLAINT_TYPE_DESC,$CM_COMPLAINT_SUB_TYPE_DESC,$CM_COMPLAINT_DESC,$CM_USER_LOCATION,$CM_USER_MOBILE,$FtsNo){
		
		$this->load->library('email');
		$to = $CM_USER_EMAIL;
		$subject = 'MyJamia Complaint Registration.';
		$from = 'kazim.jmi@gmail.com';
		//$ccmail = 'rkhaleeque.jmi.ac.in';
		$emailContaint ='<!DOCTYPE><html><head></head><body><center>
            <p style="font-size:25px; font-family:Calibri;"><strong>JAMIA MILLIA ISLAMIA</strong></p>
            <p style="font-size:20px; font-family:Calibri;">Complaint Acknowledgement</p>
            <br>
          	</center>';
        $emailContaint .='Dear Sir/Madam,<br>'.
						'With refrence to Your Complaint, this is to aknowledged you that the registration of your Complaint/Service request as per details given below:<br>';
		$emailContaint .='<table width="80%" border="0" cellpadding="5" cellspacing="10">
						<tr>
					  		<td ><strong>Ticket No. :</strong></td><td>'.$TicketNo.'</std>
					  	</tr>
					  	<tr>
					  		<td ><strong>Contact Person Name :</strong></td><td>'.$CM_USER_NANE.'</td>
						</tr>
						<tr>
							<td><strong>Department : </b></strong><td>'.$deptdesc.'<td>
						</tr>
						<tr>
							<td><strong>Complaint Type :</strong></td><td>'.$CM_COMPLAINT_TYPE_DESC.'</td>
						</tr>
						<tr>
							<td><strong>Complaint Sub Type : </strong></td>
							<td>'.$CM_COMPLAINT_SUB_TYPE_DESC.'</td>					  		
						</tr>
						<tr>
							<td ><strong>Complaint Description :</strong></td>
							<td>'.$CM_COMPLAINT_DESC.'</td>							  		
						</tr>
						<tr>
							<td><strong>Complaint Location :</strong></td>
							<td>'.$CM_USER_LOCATION.'</td>							  		
						</tr>
						<tr>
							<td><strong>Contact Number :</strong></td>
							<td>'.$CM_USER_MOBILE.'</td>							  		
						</tr>
						</table>';
		if ($FtsNo) {
			$emailContaint .="You may track your complaint in MIS using File Number :'.$FtsNo.'<br>Any Complaint or suggestion may be sent to the <a href='mailto:skanqvi@jmi.ac.in'>Additional Director, FTK-CIT, JMI</a>.<br><br><br><br><b>FTK-Centre for Information Technology,<br>JMI</b>	
			</body></html>";
		}else{
		$emailContaint .="<br>Any Complaint or suggestion may be sent to the <a href='mailto:skanqvi@jmi.ac.in'>Additional Director, FTK-CIT, JMI</a>.<br><br><br><br><b>FTK-Centre for Information Technology,<br>JAMIA MILLIA ISLAMIA</b>	
			</body></html>";
		}

		$config['protocol']			='smtp';
		$config['smtp_host']		='ssl://smtp.googlemail.com';
		$config['smtp_port']		='465';
		$config['smtp_timeout']		='60';

		$config['smtp_user']		='kazim.jmi@gmail.com';
		$config['smtp_pass']		='Sknc@1234';

		$config['charset']			='utf-8';
		$config['newline']			="\r\n";
		$config['mailtype']			='html';
		$config['validation']		=TRUE;

		$this->email->initialize($config);
		$this->email->set_mailtype("html");
		$this->email->from($from, 'Additional Director, CIT');
		$this->email->to($to);
		//$this->email->cc($ccmail);
		$this->email->subject($subject);
		$this->email->message($emailContaint);
		$this->email->send();
		echo $this->email->print_debugger();
		
	}

	//Function to Mask User EMail
	function MaskUserEMail($CM_USER_EMAIL){

		$maskedEMail = '';
		$positionOfAt = strpos($CM_USER_EMAIL, '@');
		$maskedEMail .= substr($CM_USER_EMAIL, 0,1);
		for($i=1; $i < strlen($CM_USER_EMAIL); $i++) {
			if($i < $positionOfAt-1 || $i > $positionOfAt + 1)
				$maskedEMail .= '*';
			else
				$maskedEMail .= substr($CM_USER_EMAIL, $i,1);
		}
		$maskedEMail .= substr($CM_USER_EMAIL, $i-1,1);
		return $maskedEMail;
	}


}
?>