<?php
include_once 'head.php';
if (isset($_GET['pageno'])) {
            $pageno = $_GET['pageno'];
        } else {
            $pageno = 1;
        }
        $no_of_records_per_page = 50;
        $offset = ($pageno-1) * $no_of_records_per_page;
        $ress = mysqli_query($db,"SELECT * FROM user WHERE NOT active=2 ORDER BY id DESC");
        $usersquery =  mysqli_query($db,"SELECT * FROM user WHERE NOT active=2 ORDER BY id DESC LIMIT $offset, $no_of_records_per_page");
        $total_rows = mysqli_num_rows($ress);
        $total_pages = ceil($total_rows / $no_of_records_per_page);
?>
<body>
<?php
    include_once 'Left_menu.php';
    include_once 'welcome.php';
    include_once 'm_menu.php';
    ?>
            <div class="breadcome-area">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="breadcome-list">
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="analytics-sparkle-area">
            <div class="container-fluid">
                <div>
                    <section class="content">
      <div class="container-fluid">
        <!-- Small boxes (Stat box) -->
        <br>
            <!-- TABLE: LATEST ORDERS -->
            <!--<div class="card">
                <center><h2 >Add Task</h2></center>
              <div class="card-footer clearfix">
                <a data-toggle="modal" data-target="#adddaily" class="btn btn-sm btn-info float-left text-white" style="cursor:pointer;">Add Daily Task</a>
                <a data-toggle="modal" data-target="#addweekly" class="btn btn-sm btn-secondary float-right text-white" style="cursor:pointer;">Add Weekly Task</a>
              </div>
              
            </div>-->
            <div class="card">
                <div class="card-header">
                    <center><h5>Send Message</h5></center>
                </div>
              <div class="card-body p-1">
                  <br>
                <div class="container">
                    <div class="row">
                    <div class="col-md-6 col-12">
                        <div class="form-group">
                            <select class="form-control">
                                <option>-Select Sender Id-</option>
                                <option>digsub</option>
                                <option>digsub</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6"></div>
                    <div class="col-md-6 col-12">
                        <div class="form-group">
                              <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                  <a class="nav-link active" data-toggle="tab" href="#home">Numbers</a>
                                </li>
                                <li class="nav-item">
                                  <a class="nav-link" data-toggle="tab" href="#menu1">Upload File</a>
                                </li>
                                <li class="nav-item">
                                  <a class="nav-link" data-toggle="tab" href="#menu2">Groups</a>
                                </li>
                              </ul>

                             <!-- Tab panes -->
                      <div class="tab-content">
                        <div id="home" class="container tab-pane active"><br>
                          <textarea class="form-control" placeholder="numbers separated by newline... 
							"></textarea>
                        </div>
                        <div id="menu1" class="container tab-pane fade"><br>
                          <input type="file" class="form-control">
                        </div>
                        <div id="menu2" class="container tab-pane fade"><br>
                          <select class="form-control">
                              
                              <option>s1</option>
                          </select>
                        </div>
                      
                      </div>

                        </div>
                    </div>
                    <div class="col-md-6">
                        <textarea class="form-control" col="4" rows="3" placeholder="SMS CONTENT"></textarea>
                        <div class="card-footer">
                            <div class="row">
                            <div class="col-4">
                                <input type="text" size="2"> Characters Used 
                            </div>
                            <div class="col-4">
                                <input type="text" size="2"> SMS
                            </div>
                            <div class="col-4">
                                <a href="">Use Template</a>
                            </div>
                            
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label>Schedule</label>
                        <div class="row">
                        
                        <div class="col-md-6">
                        <div style="border:1px solid #ddd; padding:8px;">
                            <span >Send Now</span>
                            <span style="padding-left:150px;"><input type="radio" name="s10" onclick="myFunction1()"></span>
                        </div>
                        </div>
                        <div class="col-md-6">
                        <div style="border:1px solid #ddd; padding:8px;">
                            <span >Send Later</span>
                            <span style="padding-left:130px;"><input type="radio" onclick="myFunction()" name="s10"></span>
                        </div>
                        </div>
                        </div>
                    </div>
                    <div class="col-md-6" id="myDIV">
                        
                            <label>Schedule Date And Time</label>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <input type="time" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                <br>
                <center><button class="btn btn-primary">Submit</button></center><br>
                </div>
              </div>
            </div>
            <!--<div class="card">
              <div class="card-body p-1">
                <div class="row">
            <div class="col-md-12 col-12">
                <center>Complete Tasks</center>
                <table class="table table-bordered table-responsive-sm table-hover">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Added Time</th>
                            <th>Subject</th>
                            <th>Topic</th>
                            <th>Subtopic</th>
                            <th>Test</th>
                            <th>Status</th>
                            <th>Update Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                        <td>1.</td>
                        <td>12:39 AM</td>
                        <td>web</td>
                        <td>PHP</td>
                        <td>array</td>
                        <td>I will complete array</td>
                        <td>Complete</td>
                        <td>12:39 AM</td>
                        </tr>
                        <tr>
                        <td>2.</td>
                        <td>12:39 AM</td>
                        <td>web</td>
                        <td>PHP</td>
                        <td>array</td>
                        <td>I will complete array</td>
                        <td>Complete</td>
                        <td>12:39 AM</td>
                        </tr>
                    </tbody>
                </table>
            </div>
                </div>
              </div>
            </div>-->
            <!-- /.card -->
        </div>
        </section>
                </div>
            </div>
        </div>

        <br>
        <br>
        <br>
<?php
include_once 'foot.php';
?>
<script>
    function search(){
        var searchtext = $('#searchtext').val();
        $.post("searchtable.php",
            {
              search: searchtext
            },
             function(data,status) {
                 $('#searchtable').html(data);
             });
    }
</script>
</body>

</html>