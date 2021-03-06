<div class="row">
    <?php foreach($single_comp as $v_single){ ?>
      <div class="col-lg-6 table-responsive">
        <th colspan="8" align="center"><---------------- COMPLAINT DETAILS ----------------></th>
        <table class="table table-bordered" style="text-align: left;">
          <tr>
            <td><b>Complaint No</b></td>
            <td><?php echo $v_single->CM_NO ?></td>
          </tr>
          <tr>
            <td><b>Complaint Type</b></td>
            <td><?php echo $v_single->CSC_NAME ?></td>            
          </tr> 
          <tr>
            <td><b>Complaint Description</b></td>
            <td><?php echo $v_single->CM_COMPLAINT_TEXT ?></td>            
          </tr>
          <tr>
            <td><b>Department</b></td>
            <td><?php echo $v_single->DEP_DESC ?></td>            
          </tr>            
          <tr>
            <td><b>Complaint Location</b></td>
            <td><?php echo $v_single->CM_COMPLAINT_LOCATION ?></td>            
          </tr>
          <tr>
            <td><b>Complaint Status</b></td>
            <?php if ($v_single->CM_COMPLAINT_STATUS == 'R') {?>
            <td>Registered</td>  
            <?php } elseif ($v_single->CM_COMPLAINT_STATUS == 'O') { ?>
              <td>Re-Open</td> 
            <?php } elseif ($v_single->CM_COMPLAINT_STATUS == 'A') { ?> 
              <td>Pending For Acceptance</td>
            <?php } elseif ($v_single->CM_COMPLAINT_STATUS == 'P') { ?>
              <td>Pending</td>   
            <?php } elseif ($v_single->CM_COMPLAINT_STATUS == 'C') { ?>
              <td>Closed</td>
            <?php } else { ?> 
              <td>On Hold</td>  
            <?php } ?>   
          </tr> 
          <tr>
            <td><b>No of faulty Equipment/Services</b></td>
            <td><?php echo $v_single->CM_NO_UNIT ?></td>            
          </tr> 
        </table>
      </div>
      <div class="col-lg-6">
        <th colspan="8" align="center"><---------------- COMPLAINANT DETAILS ----------------></th>
        <table class="table table-bordered" style="text-align: left;">
          <tr>
            <td><b>Complainant Name</b></td>
            <td><?php echo $v_single->NAME ?></td>            
          </tr>
          <tr>
            <td><b>Contact Person</b></td>
            <td><?php echo $v_single->CM_COMPLAINT_CONTACT_PERSON ?></td>            
          </tr>
          <tr>
            <td><b>Mobile Number</b></td>
            <td><?php echo $v_single->CM_COMPLAINT_CONTACT_MOBILE ?></td>
          </tr>                        
          <tr>
            <td><b>Email Id</b></td>
            <td><?php echo $v_single->CM_COMPLAINT_CONTACT_EMAIL ?></td>            
          </tr>                                    
          <tr>
            <td><b>FTS No</b></td>
            <td><?php echo $v_single->CM_COMPLAINT_FTS_NO ?></td>            
          </tr>                        
          <tr>
            <td><b>Registration Date</b></td>
            <td><?php echo $v_single->CM_COMPLAINT_DATE ?></td>            
          </tr>                                 
        </table> 
      </div>
    <?php } ?> 
    <?php if(count($action_dtl) == 1) { ?>
      <div class="col-lg-6 table-responsive">
          <th colspan="8" align="center"><---------------- ACTION DETAILS ----------------></th>
          <?php foreach($action_dtl as $v_action) { ?>
          <table class="table table-bordered" style="text-align: left;">           
            <tr>
              <td><b>Updated By</b></td>
              <td><?php echo $v_action->EMPNAME ?></td>            
            </tr>
            <tr>
              <td><b>Last Action Date</b></td>
              <td><?php echo $v_action->ACTIONDATE ?></td>            
            </tr>
            <tr>
              <td><b>Last Updated Remarks</b></td>
              <td><?php echo $v_action->MJ_CAD_REMARKS ?></td>            
            </tr>
          </table> 
          <?php } ?> 
      </div>
    <?php } else { ?>
    <div class="col-lg-12 table-responsive">
        <?php 
          $i = 1;
          foreach($action_dtl as $v_action) {
            if ($i == 1) { ?>
            <div class="col-lg-6 table-responsive">
              <th colspan="8" align="center"><---------------- ACTION DETAILS ----------------></th>
              <table class="table table-bordered" style="text-align: left;">
                <tr>
                  <td><b>Updated By</b></td>
                  <td><?php echo $v_action->EMPNAME ?></td>            
                </tr>
                <tr>
                  <td><b>Last Action Date</b></td>
                  <td><?php echo $v_action->ACTIONDATE ?></td>            
                </tr>
                <tr>
                  <td><b>Last Updated Remarks</b></td>
                  <td><?php echo $v_action->MJ_CAD_REMARKS ?></td>            
                </tr>
              </table>  
            </div>
            <?php  $i=2; } else {?>
            <div class="col-lg-6 table-responsive">
              <th colspan="8" align="center"><---------------- ACTION DETAILS ----------------></th>
              <table class="table table-bordered" style="text-align: left;">
                <tr>
                  <td><b>Updated By</b></td>
                  <td><?php echo $v_action->EMPNAME ?></td>            
                </tr>
                <tr>
                  <td><b>Last Action Date</b></td>
                  <td><?php echo $v_action->ACTIONDATE ?></td>            
                </tr>
                <tr>
                  <td><b>Last Updated Remarks</b></td>
                  <td><?php echo $v_action->MJ_CAD_REMARKS ?></td>            
                </tr>
              </table>
            </div>  
            <?php $i=1; }
        }?>
      </div>
    <?php } ?>
</div>


     