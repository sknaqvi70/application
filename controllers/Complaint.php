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

			//8 No Of Unit cannot be blank
			$this->form_validation->set_rules('CM_NO_UNIT','Number of Faulty Equipment','trim|is_natural_no_zero');

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
	       		if ($UserType == 1 || $UserType == 4){
	       			$dept = $_SESSION['depid'];
	       			$deptdesc = $_SESSION['depdesc'];
	       		}elseif ($UserType == 2 || $UserType == 5){
	       			$dept = $_SESSION['empdepid'];
	       			$deptdesc = $_SESSION['empdepdesc'];
	       		}else{
	       			$dept = $_SESSION['admindepid'];
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
	       		$CM_NO_UNIT				= $this->input->post('CM_NO_UNIT');
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
						$FtsNo,
						$CM_NO_UNIT
				);
				if ($data['message'] == 'OK') {

					$CM_COMPLAINT_TYPE_DESC= $this->CM->fetch_complaint_type_desc($CM_COMPLAINT_TYPE);

					$CM_COMPLAINT_SUB_TYPE_DESC= $this->CM->fetch_complaint_sub_type_desc($CM_COMPLAINT_SUB_TYPE);

					$cc_emailid = $this->CM->fetch_cc_email_id($CM_COMPLAINT_TYPE);

					$from_emailid = $this->CM->fetch_from_email_id($CM_COMPLAINT_SUB_TYPE);

					$this->SendMailToUser($CM_USER_EMAIL,$TicketNo,$CM_USER_NANE,$deptdesc,$CM_COMPLAINT_TYPE_DESC,$CM_COMPLAINT_SUB_TYPE_DESC,$CM_COMPLAINT_DESC,$CM_USER_LOCATION,$CM_USER_MOBILE,$FtsNo,$from_emailid,$cc_emailid);
					if ($FtsNo) {
					$data= "Your Ticket No. - ".$TicketNo.' For Complain '.$CM_COMPLAINT_SUB_TYPE_DESC.' And FTS Number is '.$FtsNo.'. An email has been sent to '.$this->MaskUserEMail($CM_USER_EMAIL). '. Please login to your mailbox to see your complaint Details.';
					}else{
					$data= "Your comlaint has been registered with Ticket No - ".$TicketNo.'. An email has been sent to '.$this->MaskUserEMail($CM_USER_EMAIL). '. We shall keep you posted about the ticket on your email.';
					}
					$this->session->set_flashdata('message',$data);
					redirect('Complaint/complaintRegistration');					
					
				}
			}
	}

	public function ComplaintRegisteredOnCall(){
			$this->form_validation->set_rules('CM_COMPLAINT_DEP','Department','required');
	// 1 Contact Person Name cannot be blank
			$this->form_validation->set_rules('CM_USER_NANE','Contact Person Name','required');

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

			//8 No Of Unit cannot be blank
			$this->form_validation->set_rules('CM_NO_UNIT','Number of Faulty Equipment','trim|is_natural_no_zero');

			//Set Error Delimeter

			$this->form_validation->set_error_delimiters("<p class='text-danger'>",'</p>');

			$UserType= $_SESSION['usertype'];
			$data['ComplaintTypeList'] = $this->CM->getComplaintCat($UserType);
			$data['DepartmentList'] = $this->CM->getDepartmentList();

			
			if ($this->form_validation->run() == FALSE) {		               
	     		
				$this->load->view('auth/complaintRegOnCall',$data);

	        }
	        else {

	       		$VerificationString = '';
	       		$TicketNo = '';
	       		$FtsNo = ''; 		
	       		
	       		
	       		$dept 					= $this->input->post('CM_COMPLAINT_DEP');
	       		$CM_USER_ID				= $this->input->post('CM_USER_NANE');
	       		$UserId					= substr($CM_USER_ID, 4);
	       		$CM_USER_NANE 			= $this->CM->getEmpName($UserId);
	       		$CM_USER_EMAIL			= $this->input->post('CM_USER_EMAIL');
	       		$CM_USER_MOBILE			= $this->input->post('CM_USER_MOBILE');
	       		$CM_USER_LOCATION		= $this->input->post('CM_USER_LOCATION');
	       		$CM_COMPLAINT_TYPE 		= $this->input->post('CM_COMPLAINT_TYPE');
	       		//echo " ,Complaint type ".$CM_COMPLAINT_TYPE;
	       		$CM_COMPLAINT_SUB_TYPE 	= $this->input->post('CM_COMPLAINT_SUB_TYPE');
	       		//echo " ,Complaint sub type ".$dept;
	       		//exit();
	       		$CM_COMPLAINT_DESC		= $this->input->post('CM_COMPLAINT_DESC'); 
	       		$CM_NO_UNIT				= $this->input->post('CM_NO_UNIT');
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
						$FtsNo,
						$CM_NO_UNIT
				);
				if ($data['message'] == 'OK') {

					$deptdesc = $this->CM->getEmpDep($dept);
					
					$CM_COMPLAINT_TYPE_DESC= $this->CM->fetch_complaint_type_desc($CM_COMPLAINT_TYPE);

					$CM_COMPLAINT_SUB_TYPE_DESC= $this->CM->fetch_complaint_sub_type_desc($CM_COMPLAINT_SUB_TYPE);

					$cc_emailid = $this->CM->fetch_cc_email_id($CM_COMPLAINT_TYPE);

					$from_emailid = $this->CM->fetch_from_email_id($CM_COMPLAINT_SUB_TYPE);

					$this->SendMailToUser($CM_USER_EMAIL,$TicketNo,$CM_USER_NANE,$deptdesc,$CM_COMPLAINT_TYPE_DESC,$CM_COMPLAINT_SUB_TYPE_DESC,$CM_COMPLAINT_DESC,$CM_USER_LOCATION,$CM_USER_MOBILE,$FtsNo,$from_emailid,$cc_emailid);
					if ($FtsNo) {
					$data= "Your Ticket No. - ".$TicketNo.' For Complain '.$CM_COMPLAINT_SUB_TYPE_DESC.' And FTS Number is '.$FtsNo.'. An email has been sent to '.$this->MaskUserEMail($CM_USER_EMAIL). '. Please login to your mailbox to see your complaint Details.';
					}else{
					$data= "Your comlaint has been registered with Ticket No - ".$TicketNo.'. An email has been sent to '.$this->MaskUserEMail($CM_USER_EMAIL). '. We shall keep you posted about the ticket on your email.';
					}
					$this->session->set_flashdata('message',$data);
					redirect('Complaint/ComplaintRegisteredOnCall');					
					
				}
				}
		
	}
	//get employee name for on call complaint registration
	public function getEmployeeList(){
		$postData = $this->input->post('v_CM_COMPLAINT_DEP');
		$data = $this->CM->getEmployeeList($postData);
		echo json_encode($data);
	}
	// This function performs to fetch sub category of complaint
	public function getComplaintSubCategory(){
		$UserType= $_SESSION['usertype'];
		$postData = $this->input->post('v_MJ_COMPLAINT_TYPE');    
    	$data = $this->CM->getComplaintSubCat($postData,$UserType);        
    	echo json_encode($data); 
	}

	function SendMailToUser($CM_USER_EMAIL,$TicketNo,$CM_USER_NANE,$deptdesc,$CM_COMPLAINT_TYPE_DESC,$CM_COMPLAINT_SUB_TYPE_DESC,$CM_COMPLAINT_DESC,$CM_USER_LOCATION,$CM_USER_MOBILE,$FtsNo,$from_emailid,$cc_emailid){
		
		$this->load->library('email');
		$to = $CM_USER_EMAIL;
		$subject = 'MyJamia Complaint Registration.';
		$from = 'admin.myjamia@jmi.ac.in'; //$from_emailid;
		$cc = $from_emailid;  //cc_emailid;
		//$ccmail = 'rkhaleeque.jmi.ac.in';
		$emailContaint ='<!DOCTYPE><html><head></head><body>';
        /*$emailContaint .= '<center><img src="<?php base_url() ?>application/assets/images/appllogo1.png" alt="JMI" style="width:100px;height:100px;" align="middle"> </center>';*/
        $emailContaint .='Dear '.$CM_USER_NANE.',<br><br>'.
						'With refrence to Your Complaint, this is to aknowledge the registration of your Complaint/Service request as per details given below:<br><br>';
		$emailContaint .='<table table-striped table-bordered table-hover " width="600"style="font-size:14px; font-family:Calibri; border-radius: 10px;border: 1px solid;">
						<tr>
					  		<td ><strong>&nbsp;&nbsp;Ticket No. :</strong></td><td>'.$TicketNo.'</std>
					  	</tr>
					  	<tr>
					  		<td ><strong>&nbsp;&nbsp;Complained Person :</strong></td><td>'.$CM_USER_NANE.'</td>
						</tr>
						<tr>
							<td><strong>&nbsp;&nbsp;Department : </b></strong><td>'.$deptdesc.'<td>
						</tr>
						<tr>
							<td><strong>&nbsp;&nbsp;Complaint Type :</strong></td><td>'.$CM_COMPLAINT_TYPE_DESC.'</td>
						</tr>
						<tr>
							<td><strong>&nbsp;&nbsp;Complaint Sub Type : </strong></td>
							<td>'.$CM_COMPLAINT_SUB_TYPE_DESC.'</td>					  		
						</tr>
						<tr>
							<td ><strong>&nbsp;&nbsp;Complaint Description :</strong></td>
							<td>'.$CM_COMPLAINT_DESC.'</td>							  		
						</tr>
						<tr>
							<td><strong>&nbsp;&nbsp;Complaint Location :</strong></td>
							<td>'.$CM_USER_LOCATION.'</td>							  		
						</tr>
						<tr>
							<td><strong>&nbsp;&nbsp;Complained Contact No :</strong></td>
							<td>'.$CM_USER_MOBILE.'</td>							  		
						</tr>
						</table>';
		if ($FtsNo) {
			$emailContaint .="You may track your complaint in MIS using File Number :'.$FtsNo.'<br>Any Complaint or suggestion may be sent to the <a href='mailto:admin.myjamia@jmi.ac.in'>Additional Director, FTK-CIT, JMI</a>.<br><br><br><br><b>FTK-Centre for Information Technology,<br>JMI</b>	
			</body></html>";
		}else{
		$emailContaint .="<br>Any Complaint or suggestion may be sent to the <a href='mailto:admin.myjamia@jmi.ac.in'>Additional Director, FTK-CIT, JMI</a>.<br><br><br><br><b>FTK-Centre for Information Technology,<br>JAMIA MILLIA ISLAMIA</b>	
			</body></html>";
		}
		

		$config['charset']			='utf-8';
		$config['newline']			="\r\n";
		$config['mailtype']			='html';
		$config['validation']		=TRUE;

		$this->email->initialize($config);
		$this->email->set_mailtype("html");
		$this->email->from($from, 'Complaint Admin, JMI');
		$this->email->to($to);
		$this->email->cc($cc);
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

	//FUNTION IS USED FOR TRACK COMPLAINT
	public function trackComplaintStatus(){
		$UserId= $_SESSION['login'];
		$data['comp_status'] = $this->CM->getComplaintDtl($UserId);
		$this->load->view('auth/trackComplaint', $data);

	}

	//this function used for to fetch fee paid to view in details
	public function get_details_Status(){
		if ($this->uri->segment(3)) {
			$COM_NO=$this->uri->segment(3);
			$UserId= $_SESSION['login'];
			$data['comp_status_dtl']=$this->CM->getSingleComplaintDetails($COM_NO,$UserId);
			$this->load->view('auth/trackComplaint', $data);
		}
	}

	//this function used for print complaint report
	public function ComplaintReport(){
		$this->load->model('AdminModel', 'admin');
		$DepId 				= $_SESSION['admindepid'];
		$UserType			= $_SESSION['usertype'];
		$cc_no 				=$this->admin->fetch_cc_no($UserType,$DepId);
		$data['comp']	=$this->CM->getComplaints($cc_no, $UserType);
		
		$this->load->view('auth/complaintReport', $data);

	}

	//
	public function getComplaintDtlForPrint(){
		$this->load->library('Pdf');
		$UserType			= $_SESSION['usertype'];
		$DepDesc			= $_SESSION['admindepdesc'];
		if ($this->uri->segment(3)) {
			$COM_NO			=substr($this->uri->segment(3),0,3);
			$TicketNo 		=substr($this->uri->segment(3),3);;
			$UserTypeDesc	=$this->CM->getUserTypeName($UserType);

			$html_content = '<!DOCTYPE html><html>
			<head>
				<style>
				#logoimage {
 					float: left ;
				}
				#headertext {
  					float: left ;
				}
				#headercontainer {
  					width: 400px ;
  					max-width: 100% ;
  					margin-left: auto ;
  					margin-right: auto ;
				}
				#headercontainerlogo {
  					max-width: 100% ;
  					margin-left: auto ;
  					margin-right: auto ;
				}
				footer {
                position: fixed; 
                bottom: -60px; 
                left: 0px; 
                right: 0px;
                height: 50px; 

                /** Extra personal styles **/
                color: black;
                text-align: center;
                line-height: 35px;
            	}
				</style>
			</head>
			<body>
			<div class="container-fluid" id="listdown">
			<div class="col-md-8 ">
			<div id="headercontainerlogo">
				<img id="logoimage" src="'.__DIR__.'/../assets/images/appllogo1.png" alt="" style="width: 80px; height: 80px;">
			</div>
			<div id="headercontainer">
					<ul id="headertext">
					<font size="20px" font-family:Calibri;">'.ucwords(strtolower($DepDesc)).'</font><br>
					<font size="20px" font-family:Calibri;">'.$UserTypeDesc.' Complaint Form</font>
					</ul>
					<br style="clear: both;">
			</div>';
		$html_content .= $this->CM->getComplaintDetails($COM_NO,$TicketNo);
		$html_content .= $this->CM->getComplaintHistory($COM_NO);
		$html_content .= $this->CM->getComplaintAttended($COM_NO,$TicketNo);
		$html_content .='</div></div></body></html>';
			$this->pdf->loadHtml($html_content);
            $this->pdf->render();
            ob_end_clean();
            ob_start();

            $this->pdf->stream("Complaint Form".".pdf",array('Attachment' =>0));
		}

	}
	
}
?>