<?php
include '../db.php';
if(isset($_GET['id'])){
    $id = towreal($_GET['id']);
$loan  = towquery("SELECT loan_apply.*,user.name,user.father_name,user.permanent_address,user.mobile,user.altmobile,user.email,user.signature,user.personaldocument,user.conpanydocument,user.marital_status FROM `loan_apply` INNER JOIN user
      ON loan_apply.uid=user.id WHERE loan_apply.id='$id'");
$loanf = towfetch($loan);
// print_r($loanf);exit;
$a = towquery("SELECT * FROM user_login_details WHERE uid='".$loanf['uid']."' ORDER BY id DESC");
$b = $loanf;
             $lo = towquery("SELECT * FROM loan WHERE lid=".$b['id']);
             $lof = towfetch($lo);
             $loan_amountc = $b['amount'] + $b['processing_fees'];
             $salary_date = $userpro_salary_date;
             $processed_date = date_create($lof['processed_date']);
             $dis_datee = date_format($processed_date,"Y-m-d");
             $dis_date = date('Y-m-d', strtotime( $dis_datee . " -1 day"));
             $sal_day = $dis_date;
             $tax = $loan_amountc / 100 * 1.8;
             $di = strtotime($dis_date);
             $sa = strtotime($sal_day);
             $datediff = $sa - $di;
             $day_gap = round($datediff / (60 * 60 * 24));
                 $femi_date = date('Y-m-d', strtotime( $dis_date . " +".$b['days']." day"));
                 $fe = strtotime($femi_date);
                 $se = strtotime($semi_date);
                 $fedatediff = $fe - $di;
                 $feday_gap = round($fedatediff / (60 * 60 * 24));
                 $femi_amount = ($loan_amountc/2) + ($loan_amountc*0.005*$feday_gap)+($loan_amountc*0.018/2);
                 $sedatediff = $se - $fe;
                 $seday_gap = round($sedatediff / (60 * 60 * 24));
                 $semi_amount = $loan_amountc / 2 + $loan_amountc * 0.005 * $seday_gap + $tax / 2;
}
?><!DOCTYPE html>
<html>
<head><meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
	<title>PDF</title>
	<style type="text/css">
body{margin-top: 0px;margin-left: 0px;}

#page_1 {position:relative; overflow: hidden;margin: 62px 0px 36px 57px;padding: 0px;border: none;width: 737px;}
#page_1 #id1_1 {border:none;margin: 0px 0px 0px 0px;padding: 0px;border:none;width: 737px;overflow: hidden;}
#page_1 #id1_2 {border:none;margin: 55px 0px 0px 0px;padding: 0px;border:none;width: 737px;overflow: hidden;}





#page_2 {position:relative; overflow: hidden;margin: 61px 0px 36px 57px;padding: 0px;border: none;width: 737px;}
#page_2 #id2_1 {border:none;margin: 0px 0px 0px 20px;padding: 0px;border:none;width: 717px;overflow: hidden;}
#page_2 #id2_2 {border:none;margin: 24px 0px 0px 66px;padding: 0px;border:none;width: 599px;overflow: hidden;}
#page_2 #id2_2 #id2_2_1 {float:left;border:none;margin: 2px 0px 0px 0px;padding: 0px;border:none;width: 34px;overflow: hidden;}
#page_2 #id2_2 #id2_2_2 {float:left;border:none;margin: 0px 0px 0px 0px;padding: 0px;border:none;width: 565px;overflow: hidden;}
#page_2 #id2_3 {border:none;margin: 24px 0px 0px 0px;padding: 0px;border:none;width: 737px;overflow: hidden;}
#page_2 #id2_4 {border:none;margin: 219px 0px 0px 0px;padding: 0px;border:none;width: 737px;overflow: hidden;}

#page_2 #p2dimg1 {position:absolute;top:265px;left:50px;z-index:-1;width:616px;height:154px;}
#page_2 #p2dimg1 #p2img1 {width:616px;height:154px;}

#page_3 {position:relative; overflow: hidden;margin: 63px 0px 36px 56px;padding: 0px;border: none;width: 738px;}
#page_3 #id3_1 {border:none;margin: 0px 0px 0px 0px;padding: 0px;border:none;width: 738px;overflow: hidden;}
#page_3 #id3_2 {border:none;margin: 152px 0px 0px 1px;padding: 0px;border:none;width: 737px;overflow: hidden;}

#page_4 {position:relative; overflow: hidden;margin: 63px 0px 36px 56px;padding: 0px;border: none;width: 738px;}
#page_4 #id4_1 {border:none;margin: 0px 0px 0px 0px;padding: 0px;border:none;width: 738px;overflow: hidden;}
#page_4 #id4_2 {border:none;margin: 55px 0px 0px 1px;padding: 0px;border:none;width: 737px;overflow: hidden;}

#page_5 {position:relative; overflow: hidden;margin: 61px 0px 36px 56px;padding: 0px;border: none;width: 738px;}
#page_5 #id5_1 {border:none;margin: 0px 0px 0px 0px;padding: 0px;border:none;width: 738px;overflow: hidden;}
#page_5 #id5_2 {border:none;margin: 259px 0px 0px 1px;padding: 0px;border:none;width: 737px;overflow: hidden;}

#page_6 {position:relative; overflow: hidden;margin: 63px 0px 36px 57px;padding: 0px;border: none;width: 737px;}
#page_6 #id6_1 {border:none;margin: 0px 0px 0px 0px;padding: 0px;border:none;width: 737px;overflow: hidden;}
#page_6 #id6_2 {border:none;margin: 84px 0px 0px 0px;padding: 0px;border:none;width: 737px;overflow: hidden;}

#page_7 {position:relative; overflow: hidden;margin: 61px 0px 36px 56px;padding: 0px;border: none;width: 738px;}
#page_7 #id7_1 {border:none;margin: 0px 0px 0px 0px;padding: 0px;border:none;width: 738px;overflow: hidden;}
#page_7 #id7_2 {border:none;margin: 119px 0px 0px 1px;padding: 0px;border:none;width: 737px;overflow: hidden;}

#page_8 {position:relative; overflow: hidden;margin: 61px 0px 36px 56px;padding: 0px;border: none;width: 738px;}
#page_8 #id8_1 {border:none;margin: 0px 0px 0px 0px;padding: 0px;border:none;width: 738px;overflow: hidden;}
#page_8 #id8_2 {border:none;margin: 68px 0px 0px 1px;padding: 0px;border:none;width: 737px;overflow: hidden;}

#page_9 {position:relative; overflow: hidden;margin: 61px 0px 36px 56px;padding: 0px;border: none;width: 738px;}
#page_9 #id9_1 {border:none;margin: 0px 0px 0px 0px;padding: 0px;border:none;width: 738px;overflow: hidden;}
#page_9 #id9_2 {border:none;margin: 0px 0px 0px 1px;padding: 0px;border:none;width: 737px;overflow: hidden;}

#page_10 {position:relative; overflow: hidden;margin: 63px 0px 36px 56px;padding: 0px;border: none;width: 738px;}
#page_10 #id10_1 {border:none;margin: 0px 0px 0px 0px;padding: 0px;border:none;width: 738px;overflow: hidden;}
#page_10 #id10_2 {border:none;margin: 121px 0px 0px 1px;padding: 0px;border:none;width: 737px;overflow: hidden;}

#page_11 {position:relative; overflow: hidden;margin: 63px 0px 36px 57px;padding: 0px;border: none;width: 737px;}
#page_11 #id11_1 {border:none;margin: 0px 0px 0px 3px;padding: 0px;border:none;width: 734px;overflow: hidden;}
#page_11 #id11_2 {border:none;margin: 82px 0px 0px 0px;padding: 0px;border:none;width: 737px;overflow: hidden;}

#page_12 {position:relative; overflow: hidden;margin: 61px 0px 36px 57px;padding: 0px;border: none;width: 737px;}
#page_12 #id12_1 {border:none;margin: 0px 0px 0px 0px;padding: 0px;border:none;width: 667px;overflow: hidden;}
#page_12 #id12_2 {border:none;margin: 753px 0px 0px 0px;padding: 0px;border:none;width: 737px;overflow: hidden;}

.ft0{font: bold 29px 'Cambria';text-decoration: underline;line-height: 34px;}
.ft1{font: bold 14px 'Cambria';line-height: 16px;}
.ft2{font: 14px 'Cambria';line-height: 16px;}
.ft3{font: 13px 'Cambria';line-height: 15px;}
.ft4{font: bold 13px 'Cambria';margin-left: 13px;line-height: 19px;}
.ft5{font: bold 15px 'Cambria';line-height: 21px;}
.ft6{font: 14px 'Cambria';line-height: 20px;}
.ft7{font: bold 14px 'Cambria';line-height: 20px;}
.ft8{font: bold 13px 'Cambria';line-height: 19px;}
.ft9{font: bold 13px 'Cambria';margin-left: 9px;line-height: 19px;}
.ft10{font: 13px 'Cambria';line-height: 19px;}
.ft11{font: bold 22px 'Cambria';text-decoration: underline;line-height: 26px;}
.ft12{font: bold 16px 'Cambria';margin-left: 9px;line-height: 19px;}
.ft13{font: bold 16px 'Cambria';line-height: 19px;}
.ft14{font: bold 14px 'Cambria';line-height: 19px;}
.ft15{font: 14px 'Cambria';line-height: 19px;}
.ft16{font: 14px 'Cambria';margin-left: 10px;line-height: 16px;}
.ft17{font: 15px 'Cambria';line-height: 17px;}
.ft18{font: 14px 'Cambria';text-decoration: underline;color: #0000ff;line-height: 16px;}
.ft19{font: 14px 'Cambria';color: #0000ff;line-height: 16px;}
.ft20{font: 14px 'Cambria';margin-left: 6px;line-height: 16px;}
.ft21{font: bold 15px 'Cambria';line-height: 17px;}
.ft22{font: 1px 'Cambria';line-height: 9px;}
.ft23{font: 9px 'Cambria';line-height: 11px;}
.ft24{font: 1px 'Cambria';line-height: 1px;}
.ft25{font: 14px 'Cambria';margin-left: 3px;line-height: 16px;}
.ft26{font: 14px 'Cambria';margin-left: 13px;line-height: 17px;}
.ft27{font: 14px 'Cambria';line-height: 17px;}
.ft28{font: 14px 'Cambria';margin-left: 14px;line-height: 17px;}
.ft29{font: bold 14px 'Cambria';margin-left: 6px;line-height: 16px;}
.ft30{font: 15px 'Cambria';margin-left: 16px;line-height: 17px;}
.ft31{font: bold 14px 'Cambria';margin-left: 14px;line-height: 17px;}
.ft32{font: bold 14px 'Cambria';margin-left: 17px;line-height: 16px;}
.ft33{font: bold 14px 'Cambria';margin-left: 15px;line-height: 16px;}
.ft34{font: bold 14px 'Cambria';margin-left: 13px;line-height: 17px;}
.ft35{font: bold 13px 'Cambria';margin-left: 11px;line-height: 16px;}
.ft36{font: 13px 'Cambria';line-height: 16px;}
.ft37{font: bold 14px 'Cambria';margin-left: 21px;line-height: 16px;}
.ft38{font: bold 14px 'Cambria';margin-left: 14px;line-height: 16px;}
.ft39{font: 14px 'Cambria';margin-left: 14px;line-height: 16px;}
.ft40{font: 14px 'Cambria';margin-left: 22px;line-height: 16px;}
.ft41{font: 14px 'Cambria';margin-left: 17px;line-height: 16px;}
.ft42{font: 14px 'Cambria';margin-left: 13px;line-height: 16px;}
.ft43{font: 14px 'Cambria';margin-left: 18px;line-height: 16px;}
.ft44{font: bold 14px 'Cambria';margin-left: 6px;line-height: 26px;}
.ft45{font: bold 14px 'Cambria';line-height: 26px;}
.ft46{font: 13px 'Cambria';line-height: 20px;}
.ft47{font: 14px 'Cambria';text-decoration: underline;color: #0000ff;line-height: 17px;}
.ft48{font: 15px 'Cambria';margin-left: 14px;line-height: 18px;}
.ft49{font: 15px 'Cambria';line-height: 18px;}
.ft50{font: 13px 'Cambria';margin-left: 13px;line-height: 17px;}
.ft51{font: 13px 'Cambria';line-height: 17px;}
.ft52{font: 14px 'Cambria';margin-left: 4px;line-height: 20px;}
.ft53{font: bold 13px 'Cambria';line-height: 20px;}
.ft54{font: bold 14px 'Cambria';margin-left: 6px;line-height: 25px;}
.ft55{font: 14px 'Cambria';line-height: 25px;}
.ft56{font: bold 14px 'Cambria';line-height: 25px;}
.ft57{font: 14px 'Cambria';margin-left: 12px;line-height: 16px;}
.ft58{font: 14px 'Cambria';margin-left: 8px;line-height: 16px;}
.ft59{font: 14px 'Cambria';margin-left: 23px;line-height: 16px;}
.ft60{font: 14px 'Cambria';margin-left: 20px;line-height: 16px;}
.ft61{font: 11px 'Cambria';line-height: 12px;}
.ft62{font: 13px 'Cambria';margin-left: 12px;line-height: 15px;}
.ft63{font: 14px 'Cambria';margin-left: 11px;line-height: 16px;}
.ft64{font: 14px 'Cambria';margin-left: 16px;line-height: 17px;}
.ft65{font: bold 14px 'Cambria';margin-left: 8px;line-height: 16px;}
.ft66{font: bold 14px 'Cambria';margin-left: 16px;line-height: 16px;}
.ft67{font: 13px 'Cambria';margin-left: 3px;line-height: 16px;}
.ft68{font: 13px 'Cambria';text-decoration: underline;color: #0000ff;line-height: 16px;}
.ft69{font: bold 14px 'Cambria';margin-left: 16px;line-height: 17px;}
.ft70{font: bold 10px 'Cambria';line-height: 12px;}
.ft71{font: bold 12px 'Cambria';margin-left: 14px;line-height: 14px;}
.ft72{font: 12px 'Cambria';line-height: 14px;}
.ft73{font: bold 14px 'Cambria';margin-left: 17px;line-height: 17px;}
.ft74{font: bold 12px 'Cambria';line-height: 14px;}
.ft75{font: bold 13px 'Cambria';margin-left: 15px;line-height: 15px;}

.p0{text-align: left;padding-left: 229px;margin-top: 0px;margin-bottom: 0px;}
.p1{text-align: left;margin-top: 44px;margin-bottom: 0px;}
.p2{text-align: left;padding-left: 40px;padding-right: 81px;margin-top: 35px;margin-bottom: 0px;text-indent: -20px;}
.p3{text-align: justify;padding-left: 40px;padding-right: 64px;margin-top: 11px;margin-bottom: 0px;text-indent: -20px;}
.p4{text-align: left;padding-left: 40px;margin-top: 2px;margin-bottom: 0px;}
.p5{text-align: left;margin-top: 34px;margin-bottom: 0px;}
.p6{text-align: left;margin-top: 4px;margin-bottom: 0px;}
.p7{text-align: left;margin-top: 20px;margin-bottom: 0px;}
.p8{text-align: justify;padding-right: 107px;margin-top: 22px;margin-bottom: 0px;}
.p9{text-align: left;padding-left: 20px;margin-top: 11px;margin-bottom: 0px;}
.p10{text-align: left;padding-left: 40px;padding-right: 144px;margin-top: 12px;margin-bottom: 0px;}
.p11{text-align: left;padding-left: 40px;padding-right: 130px;margin-top: 19px;margin-bottom: 0px;text-indent: -20px;}
.p12{text-align: left;padding-left: 40px;padding-right: 63px;margin-top: 13px;margin-bottom: 0px;}
.p13{text-align: left;padding-left: 20px;margin-top: 18px;margin-bottom: 0px;}
.p14{text-align: left;padding-left: 30px;margin-top: 8px;margin-bottom: 0px;}
.p15{text-align: left;padding-left: 30px;margin-top: 9px;margin-bottom: 0px;}
.p16{text-align: left;margin-top: 0px;margin-bottom: 0px;}
.p17{text-align: left;padding-left: 10px;margin-top: 0px;margin-bottom: 0px;}
.p18{text-align: left;margin-top: 27px;margin-bottom: 0px;}
.p19{text-align: left;margin-top: 9px;margin-bottom: 0px;}
.p20{text-align: left;padding-left: 20px;margin-top: 12px;margin-bottom: 0px;}
.p21{text-align: left;padding-left: 20px;margin-top: 9px;margin-bottom: 0px;}
.p22{text-align: left;margin-top: 26px;margin-bottom: 0px;}
.p23{text-align: left;padding-left: 10px;margin-top: 13px;margin-bottom: 0px;}
.p24{text-align: left;padding-left: 10px;margin-top: 9px;margin-bottom: 0px;}
.p25{text-align: left;padding-left: 5px;margin-top: 0px;margin-bottom: 0px;}
.p26{text-align: left;padding-left: 5px;margin-top: 3px;margin-bottom: 0px;}
.p27{text-align: left;padding-left: 5px;margin-top: 21px;margin-bottom: 0px;}
.p28{text-align: left;padding-left: 99px;margin-top: 0px;margin-bottom: 0px;white-space: nowrap;}
.p29{text-align: left;padding-left: 111px;margin-top: 0px;margin-bottom: 0px;white-space: nowrap;}
.p30{text-align: left;margin-top: 0px;margin-bottom: 0px;white-space: nowrap;}
.p31{text-align: center;padding-left: 1px;margin-top: 0px;margin-bottom: 0px;white-space: nowrap;}
.p32{text-align: left;padding-left: 20px;margin-top: 0px;margin-bottom: 0px;}
.p33{text-align: left;padding-left: 40px;margin-top: 7px;margin-bottom: 0px;}
.p34{text-align: left;padding-left: 40px;margin-top: 9px;margin-bottom: 0px;}
.p35{text-align: left;padding-left: 20px;margin-top: 27px;margin-bottom: 0px;}
.p36{text-align: left;margin-top: 43px;margin-bottom: 0px;}
.p37{text-align: left;padding-left: 35px;padding-right: 75px;margin-top: 24px;margin-bottom: 0px;text-indent: -32px;}
.p38{text-align: left;padding-left: 37px;padding-right: 76px;margin-top: 0px;margin-bottom: 0px;text-indent: -33px;}
.p39{text-align: justify;padding-left: 1px;padding-right: 63px;margin-top: 15px;margin-bottom: 0px;}
.p40{text-align: left;padding-left: 1px;margin-top: 17px;margin-bottom: 0px;}
.p41{text-align: left;margin-top: 21px;margin-bottom: 0px;}
.p42{text-align: left;padding-left: 39px;padding-right: 89px;margin-top: 16px;margin-bottom: 0px;text-indent: -35px;}
.p43{text-align: left;padding-left: 35px;padding-right: 65px;margin-top: 19px;margin-bottom: 0px;text-indent: -31px;}
.p44{text-align: left;padding-left: 4px;margin-top: 16px;margin-bottom: 0px;}
.p45{text-align: left;padding-left: 35px;padding-right: 147px;margin-top: 18px;margin-bottom: 0px;text-indent: -31px;}
.p46{text-align: left;padding-left: 35px;padding-right: 83px;margin-top: 18px;margin-bottom: 0px;text-indent: -31px;}
.p47{text-align: justify;padding-left: 32px;padding-right: 74px;margin-top: 17px;margin-bottom: 0px;text-indent: -28px;}
.p48{text-align: left;padding-left: 32px;margin-top: 2px;margin-bottom: 0px;}
.p49{text-align: left;padding-left: 4px;margin-top: 18px;margin-bottom: 0px;}
.p50{text-align: left;padding-left: 35px;padding-right: 104px;margin-top: 18px;margin-bottom: 0px;text-indent: -31px;}
.p51{text-align: left;margin-top: 17px;margin-bottom: 0px;}
.p52{text-align: left;padding-left: 41px;margin-top: 14px;margin-bottom: 0px;}
.p53{text-align: left;padding-left: 44px;margin-top: 12px;margin-bottom: 0px;}
.p54{text-align: left;padding-left: 44px;margin-top: 18px;margin-bottom: 0px;}
.p55{text-align: left;padding-left: 74px;padding-right: 83px;margin-top: 0px;margin-bottom: 0px;text-indent: -30px;}
.p56{text-align: left;padding-left: 44px;margin-top: 17px;margin-bottom: 0px;}
.p57{text-align: left;padding-left: 75px;padding-right: 63px;margin-top: 18px;margin-bottom: 0px;text-indent: -31px;}
.p58{text-align: left;padding-left: 72px;padding-right: 126px;margin-top: 19px;margin-bottom: 0px;text-indent: -28px;}
.p59{text-align: left;padding-left: 75px;padding-right: 26px;margin-top: 19px;margin-bottom: 0px;text-indent: -31px;}
.p60{text-align: left;padding-left: 75px;padding-right: 62px;margin-top: 16px;margin-bottom: 0px;text-indent: -31px;}
.p61{text-align: left;padding-right: 552px;margin-top: 16px;margin-bottom: 0px;}
.p62{text-align: left;padding-left: 41px;padding-right: 57px;margin-top: 8px;margin-bottom: 0px;}
.p63{text-align: left;margin-top: 11px;margin-bottom: 0px;}
.p64{text-align: left;padding-left: 41px;margin-top: 4px;margin-bottom: 0px;}
.p65{text-align: left;margin-top: 14px;margin-bottom: 0px;}
.p66{text-align: left;padding-left: 75px;padding-right: 49px;margin-top: 16px;margin-bottom: 0px;text-indent: -31px;}
.p67{text-align: left;padding-left: 75px;padding-right: 107px;margin-top: 19px;margin-bottom: 0px;text-indent: -31px;}
.p68{text-align: left;padding-left: 74px;padding-right: 58px;margin-top: 19px;margin-bottom: 0px;text-indent: -30px;}
.p69{text-align: left;padding-left: 41px;padding-right: 85px;margin-top: 15px;margin-bottom: 0px;}
.p70{text-align: left;padding-left: 41px;margin-top: 0px;margin-bottom: 0px;}
.p71{text-align: left;padding-left: 41px;margin-top: 5px;margin-bottom: 0px;}
.p72{text-align: left;padding-left: 41px;padding-right: 111px;margin-top: 0px;margin-bottom: 0px;}
.p73{text-align: left;padding-left: 41px;padding-right: 73px;margin-top: 14px;margin-bottom: 0px;}
.p74{text-align: left;margin-top: 10px;margin-bottom: 0px;}
.p75{text-align: left;padding-left: 41px;padding-right: 70px;margin-top: 14px;margin-bottom: 0px;}
.p76{text-align: left;padding-left: 39px;padding-right: 164px;margin-top: 16px;margin-bottom: 0px;text-indent: -35px;}
.p77{text-align: left;padding-left: 75px;padding-right: 46px;margin-top: 19px;margin-bottom: 0px;text-indent: -31px;}
.p78{text-align: left;padding-left: 75px;margin-top: 1px;margin-bottom: 0px;}
.p79{text-align: left;padding-left: 75px;padding-right: 47px;margin-top: 18px;margin-bottom: 0px;text-indent: -31px;}
.p80{text-align: left;padding-left: 74px;padding-right: 41px;margin-top: 17px;margin-bottom: 0px;text-indent: -30px;}
.p81{text-align: justify;padding-left: 75px;padding-right: 39px;margin-top: 17px;margin-bottom: 0px;text-indent: -31px;}
.p82{text-align: left;padding-left: 75px;margin-top: 0px;margin-bottom: 0px;}
.p83{text-align: left;padding-left: 39px;padding-right: 88px;margin-top: 18px;margin-bottom: 0px;text-indent: -35px;}
.p84{text-align: left;padding-left: 39px;padding-right: 107px;margin-top: 18px;margin-bottom: 0px;text-indent: -35px;}
.p85{text-align: left;padding-left: 74px;padding-right: 57px;margin-top: 0px;margin-bottom: 0px;text-indent: -31px;}
.p86{text-align: left;padding-left: 74px;padding-right: 116px;margin-top: 17px;margin-bottom: 0px;text-indent: -31px;}
.p87{text-align: left;padding-left: 73px;padding-right: 21px;margin-top: 19px;margin-bottom: 0px;text-indent: -30px;}
.p88{text-align: left;padding-left: 74px;padding-right: 88px;margin-top: 32px;margin-bottom: 0px;text-indent: -31px;}
.p89{text-align: left;padding-left: 43px;margin-top: 19px;margin-bottom: 0px;}
.p90{text-align: left;padding-left: 71px;padding-right: 70px;margin-top: 17px;margin-bottom: 0px;text-indent: -28px;}
.p91{text-align: left;padding-left: 43px;margin-top: 17px;margin-bottom: 0px;}
.p92{text-align: left;padding-right: 54px;margin-top: 16px;margin-bottom: 0px;}
.p93{text-align: left;margin-top: 1px;margin-bottom: 0px;}
.p94{text-align: left;padding-right: 62px;margin-top: 4px;margin-bottom: 0px;}
.p95{text-align: left;padding-right: 87px;margin-top: 11px;margin-bottom: 0px;}
.p96{text-align: left;padding-right: 104px;margin-top: 0px;margin-bottom: 0px;}
.p97{text-align: left;padding-right: 74px;margin-top: 10px;margin-bottom: 0px;}
.p98{text-align: left;padding-left: 1px;padding-right: 64px;margin-top: 0px;margin-bottom: 0px;}
.p99{text-align: left;padding-left: 39px;padding-right: 74px;margin-top: 13px;margin-bottom: 0px;text-indent: -35px;}
.p100{text-align: left;padding-left: 41px;padding-right: 61px;margin-top: 14px;margin-bottom: 0px;}
.p101{text-align: left;padding-left: 41px;padding-right: 60px;margin-top: 14px;margin-bottom: 0px;}
.p102{text-align: left;padding-left: 41px;padding-right: 110px;margin-top: 14px;margin-bottom: 0px;}
.p103{text-align: left;padding-left: 41px;padding-right: 58px;margin-top: 5px;margin-bottom: 0px;}
.p104{text-align: left;padding-left: 41px;padding-right: 62px;margin-top: 16px;margin-bottom: 0px;}
.p105{text-align: left;padding-left: 41px;margin-top: 11px;margin-bottom: 0px;}
.p106{text-align: justify;padding-left: 41px;padding-right: 63px;margin-top: 0px;margin-bottom: 0px;}
.p107{text-align: left;padding-right: 383px;margin-top: 16px;margin-bottom: 0px;}
.p108{text-align: left;padding-left: 75px;padding-right: 58px;margin-top: 12px;margin-bottom: 0px;text-indent: -31px;}
.p109{text-align: left;padding-left: 75px;padding-right: 71px;margin-top: 17px;margin-bottom: 0px;text-indent: -31px;}
.p110{text-align: left;padding-left: 74px;padding-right: 60px;margin-top: 18px;margin-bottom: 0px;text-indent: -30px;}
.p111{text-align: left;padding-left: 75px;padding-right: 36px;margin-top: 17px;margin-bottom: 0px;text-indent: -31px;}
.p112{text-align: left;padding-left: 75px;padding-right: 30px;margin-top: 17px;margin-bottom: 0px;text-indent: -31px;}
.p113{text-align: left;padding-left: 72px;padding-right: 73px;margin-top: 17px;margin-bottom: 0px;text-indent: -28px;}
.p114{text-align: left;padding-left: 75px;padding-right: 51px;margin-top: 19px;margin-bottom: 0px;text-indent: -31px;}
.p115{text-align: left;padding-left: 75px;padding-right: 41px;margin-top: 19px;margin-bottom: 0px;text-indent: -31px;}
.p116{text-align: left;padding-left: 71px;padding-right: 90px;margin-top: 19px;margin-bottom: 0px;text-indent: -27px;}
.p117{text-align: left;padding-left: 75px;padding-right: 35px;margin-top: 16px;margin-bottom: 0px;text-indent: -31px;}
.p118{text-align: left;padding-left: 75px;padding-right: 84px;margin-top: 17px;margin-bottom: 0px;text-indent: -31px;}
.p119{text-align: left;padding-left: 74px;padding-right: 49px;margin-top: 18px;margin-bottom: 0px;text-indent: -30px;}
.p120{text-align: left;padding-left: 75px;padding-right: 54px;margin-top: 17px;margin-bottom: 0px;text-indent: -31px;}
.p121{text-align: left;margin-top: 15px;margin-bottom: 0px;}
.p122{text-align: left;padding-left: 41px;padding-right: 63px;margin-top: 0px;margin-bottom: 0px;}
.p123{text-align: left;padding-right: 575px;margin-top: 12px;margin-bottom: 0px;}
.p124{text-align: left;padding-left: 41px;padding-right: 63px;margin-top: 10px;margin-bottom: 0px;}
.p125{text-align: left;padding-left: 41px;margin-top: 18px;margin-bottom: 0px;}
.p126{text-align: left;padding-left: 44px;margin-top: 16px;margin-bottom: 0px;}
.p127{text-align: justify;padding-left: 41px;padding-right: 74px;margin-top: 34px;margin-bottom: 0px;}
.p128{text-align: left;padding-left: 39px;padding-right: 98px;margin-top: 21px;margin-bottom: 0px;text-indent: -35px;}
.p129{text-align: left;padding-left: 39px;padding-right: 99px;margin-top: 19px;margin-bottom: 0px;text-indent: -35px;}
.p130{text-align: left;padding-left: 75px;padding-right: 99px;margin-top: 6px;margin-bottom: 0px;text-indent: -31px;}
.p131{text-align: left;padding-left: 72px;padding-right: 60px;margin-top: 17px;margin-bottom: 0px;text-indent: -28px;}
.p132{text-align: left;padding-left: 74px;padding-right: 25px;margin-top: 0px;margin-bottom: 0px;text-indent: -30px;}
.p133{text-align: left;padding-left: 75px;padding-right: 29px;margin-top: 17px;margin-bottom: 0px;text-indent: -31px;}
.p134{text-align: left;padding-left: 72px;padding-right: 39px;margin-top: 17px;margin-bottom: 0px;text-indent: -28px;}
.p135{text-align: left;padding-left: 75px;padding-right: 40px;margin-top: 16px;margin-bottom: 0px;text-indent: -31px;}
.p136{text-align: justify;padding-left: 75px;padding-right: 24px;margin-top: 17px;margin-bottom: 0px;text-indent: -31px;}
.p137{text-align: left;padding-left: 39px;padding-right: 91px;margin-top: 16px;margin-bottom: 0px;text-indent: -35px;}
.p138{text-align: left;padding-left: 39px;padding-right: 69px;margin-top: 3px;margin-bottom: 0px;}
.p139{text-align: left;padding-left: 39px;padding-right: 103px;margin-top: 2px;margin-bottom: 0px;}
.p140{text-align: left;padding-left: 39px;padding-right: 80px;margin-top: 19px;margin-bottom: 0px;text-indent: -35px;}
.p141{text-align: left;padding-left: 34px;padding-right: 69px;margin-top: 0px;margin-bottom: 0px;}
.p142{text-align: justify;padding-left: 35px;padding-right: 76px;margin-top: 0px;margin-bottom: 0px;text-indent: -35px;}
.p143{text-align: left;padding-left: 35px;padding-right: 127px;margin-top: 17px;margin-bottom: 0px;text-indent: -35px;}
.p144{text-align: left;padding-left: 35px;padding-right: 81px;margin-top: 19px;margin-bottom: 0px;text-indent: -35px;}
.p145{text-align: left;padding-left: 35px;padding-right: 76px;margin-top: 16px;margin-bottom: 0px;text-indent: -35px;}
.p146{text-align: left;padding-left: 35px;padding-right: 87px;margin-top: 17px;margin-bottom: 0px;text-indent: -35px;}
.p147{text-align: justify;padding-left: 35px;padding-right: 79px;margin-top: 19px;margin-bottom: 0px;text-indent: -35px;}
.p148{text-align: justify;padding-left: 71px;padding-right: 38px;margin-top: 17px;margin-bottom: 0px;text-indent: -31px;}
.p149{text-align: left;padding-left: 71px;padding-right: 27px;margin-top: 17px;margin-bottom: 0px;text-indent: -31px;}
.p150{text-align: left;padding-left: 70px;padding-right: 24px;margin-top: 17px;margin-bottom: 0px;text-indent: -30px;}
.p151{text-align: left;margin-top: 16px;margin-bottom: 0px;}
.p152{text-align: left;padding-left: 44px;padding-right: 65px;margin-top: 1px;margin-bottom: 0px;}
.p153{text-align: justify;padding-left: 44px;padding-right: 84px;margin-top: 17px;margin-bottom: 0px;text-indent: -44px;}
.p154{text-align: justify;padding-left: 39px;padding-right: 62px;margin-top: 16px;margin-bottom: 0px;text-indent: -39px;}
.p155{text-align: left;padding-right: 52px;margin-top: 0px;margin-bottom: 0px;}
.p156{text-align: left;padding-left: 30px;padding-right: 8px;margin-top: 14px;margin-bottom: 0px;text-indent: -27px;}
.p157{text-align: left;padding-left: 34px;margin-top: 17px;margin-bottom: 0px;text-indent: -31px;}
.p158{text-align: left;padding-left: 38px;padding-right: 69px;margin-top: 17px;margin-bottom: 0px;text-indent: -35px;}

.td0{border-right: #000000 1px solid;padding: 0px;margin: 0px;width: 259px;vertical-align: bottom;background: #dddddd;}
.td1{padding: 0px;margin: 0px;width: 305px;vertical-align: bottom;background: #dddddd;}
.td2{border-right: #000000 1px solid;padding: 0px;margin: 0px;width: 259px;vertical-align: bottom;}
.td3{padding: 0px;margin: 0px;width: 305px;vertical-align: bottom;}

.tr0{height: 28px;}
.tr1{height: 9px;}
.tr2{height: 19px;}
.tr3{height: 38px;}
.tr4{height: 59px;}

.t0{width: 565px;font: 15px 'Cambria';}
	</style>
</head>
<body>
<DIV id="page_1">


<DIV id="id1_1">
<P class="p0 ft0">Loan Agreement</P>
<P class="p1 ft2">This LOAN AGREEMENT (“<SPAN class="ft1">Agreement</SPAN>”) is being entered into this on <NOBR><SPAN class="ft1"><?=date("d-m-Y", strtotime($dis_datee))?></SPAN></NOBR><SPAN class="ft1"> </SPAN>(“<SPAN class="ft1">Execution Date</SPAN>”) between</P>
<P class="p2 ft8"><SPAN class="ft3">1.</SPAN><SPAN class="ft4">creditlab.in, a </SPAN><NOBR>new-age</NOBR> digital lending platform that leverages <NOBR>state-of-the-art</NOBR> technology and data sciences to make lending safe, quick and <NOBR>hassle-free</NOBR> for India’s massive population of underserved customers. A subsidiary of Digital Finance International having 20+ international fintech brands across several countries<SPAN class="ft5">, </SPAN><SPAN class="ft6">herein after referred to as the “</SPAN><SPAN class="ft7">Lender</SPAN><SPAN class="ft6">” (which expression shall unless repugnant to the subject and context hereof be deemed to include its successor(s), novates, transferees, nominees, and assigns) of the </SPAN><SPAN class="ft7">FIRST PART</SPAN><SPAN class="ft6">; and</SPAN></P>
<P class="p3 ft10"><SPAN class="ft1">2.</SPAN><SPAN class="ft9"><?=$loanf['name']?>, <?php if($loanf['marital_status'] == "male"){?>son <?php }else{?> daughter <?php }?> of <?=$loanf['father_name']?>, </SPAN>having a permanent residence at <?=$loanf['permanent_address']?>. Herein after referred to as <SPAN class="ft8">“Borrower” </SPAN>(which expression shall unless repugnant to the subject and context here of be deemed to include the successors of the Borrower) of the</P>
<P class="p4 ft1">OTHER PART.</P>
<P class="p5 ft2">Each of the Lender and the Borrower are herein after individually referred to as <SPAN class="ft1">“Party” </SPAN>and collectively as the</P>
<P class="p6 ft1">“Parties”.</P>
<P class="p7 ft11">Transaction details Schedule</P>
<P class="p8 ft6">This transaction details schedule (“<SPAN class="ft7">TD Schedule</SPAN>”) is an integral part of the Agreement. The terms defined in this TD Schedule shall have the meaning ascribed to them herein when used in this TD Schedule, unless the context otherwise so requires.</P>
<P class="p9 ft13"><SPAN class="ft1">1.</SPAN><SPAN class="ft12">Amount</SPAN></P>
<P class="p10 ft15">This Agreement between the Lender and the Borrower is for a disbursal amount of Rs.<?=$b['amount']?> (“<SPAN class="ft14">Disbursal Amount</SPAN>”).</P>
<P class="p11 ft13"><SPAN class="ft1">2.</SPAN><SPAN class="ft12">Principal Amount, Interest Amount Before Repayment Date, Processing Fee & Convenience Fee</SPAN></P>
<P class="p12 ft6">
    This Agreement between the Lender and the Borrower is for a principal amount of Rs. <?php echo $b['amount'] + $b['processing_fees']?> (“Principal

Amount”). An amountof Rs.<?=$loanf['service_charge']?> (“InterestAmount”)

is payable by the Borrower, along with the Principal Amount, on or before the

corresponding Repayment Date(s) mentioned below. An amount of Rs.<?=$loanf['processing_fees']?> shall be deducted by the Lender from the Principal Amount at the time of disbursal of the Principal Amount by the Lender to the Borrower by way of a processing fee (“Processing Fee”). The Borrower shall be liable to pay an amount of Rs.50 per day as penalty charges on the corresponding Repayment Instalment following the expiry of the relevant Repayment Date (“Interest Amount After Repayment Date”) up to the date of actual payment.
</P>
<P class="p13 ft13"><SPAN class="ft1">3.</SPAN><SPAN class="ft12">Borrower’s phone number and notice details:</SPAN></P>
<P class="p14 ft2"><SPAN class="ft3">1.</SPAN><SPAN class="ft16">Primary mobile number: <?=$loanf['mobile']?></SPAN></P>
<P class="p15 ft2"><SPAN class="ft3">2.</SPAN><SPAN class="ft16">Alternate mobile number: <?=$loanf['altmobile']?></SPAN></P>
<P class="p15 ft2"><SPAN class="ft3">3.</SPAN><SPAN class="ft16">Borrower’s present address: </SPAN><?=$loanf['permanent_address']?></P>
<div style="height:100px;overflow:hidden;"><img src="../user/uploads/<?=$loanf['signature']?>" style="height:100px; float:right;"></div>
</DIV>
<DIV id="id1_2">
<P class="p16 ft17">Page 1 of 12</P>
</DIV>
</DIV>
<DIV id="page_2">
<DIV id="id2_1">
<P class="p17"><SPAN class="ft3">4.</SPAN><SPAN class="ft16">Borrower’s email: </SPAN><SPAN class="ft18"><?=$loanf['email']?></SPAN></P>
<P class="p18 ft13"><SPAN class="ft1">4.</SPAN><SPAN class="ft12">Borrower’s IP Addresses: <?=$loanf['ip_address']?></SPAN></P>
<P class="p19 ft13"><SPAN class="ft1">5.</SPAN><SPAN class="ft12">Other Borrower information:</SPAN></P>
<P class="p20 ft2"><SPAN class="ft3">1.</SPAN><SPAN class="ft20">Mobile handset UID: <?=$loanf['mobile_handset_uid']?></SPAN></P>
<P class="p21 ft2"><SPAN class="ft3">2.</SPAN><SPAN class="ft20">Approximate location at time of signing contract: <!--12.970143 and 77.5917144--></SPAN></P>
<P class="p22 ft13"><SPAN class="ft1">6.</SPAN><SPAN class="ft12">Dates and Time</SPAN></P>
<P class="p23 ft2"><SPAN class="ft3">1.</SPAN><SPAN class="ft16">Execution Date and Time: <?=$dis_datee;?></SPAN></P>
<P class="p24 ft2"><SPAN class="ft3">2.</SPAN><SPAN class="ft16">Repayment as follows:</SPAN></P>
</DIV>
<DIV id="id2_2">
    <table class="table table-bordered">
    <thead>
      <tr>
      	<th>S No.</th>
        <th>Repayment Date</th>
        <th>Repayment Amount</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>1</td>
        <td><?=$femi_date?>
</td>
        <td>RS. <?=$b['amount'] + $b['processing_fees'] + $b['service_charge']?></td>
      </tr>
    </tbody>
  </table>
</DIV>
<?php
$bank = towquery("SELECT * FROM `user_bank` WHERE uid='".$loanf['uid']."' ORDER BY id DESC");
$bankf = towfetch($bank);
?>
<DIV id="id2_3">
<P class="p32 ft13"><SPAN class="ft1">7.</SPAN><SPAN class="ft12">Disbursal information</SPAN></P>
<P class="p33 ft2"><SPAN class="ft3">1.</SPAN><SPAN class="ft20">Bank name: <?=$bankf['bank_name']?></SPAN></P>
<P class="p34 ft2"><SPAN class="ft3">2.</SPAN><SPAN class="ft20">IFSC Code: <?=$bankf['ifsc_code']?></SPAN></P>
<P class="p34 ft2"><SPAN class="ft3">3.</SPAN><SPAN class="ft25">Account number:<?=$bankf['ac_no']?></SPAN></P>
<P class="p34 ft2"><SPAN class="ft3">4.</SPAN><SPAN class="ft25">Bank transaction confirmation number: </SPAN></P>
<P class="p35 ft13"><SPAN class="ft1">8.</SPAN><SPAN class="ft12">Unique Loan ID</SPAN></P>
<P class="p33 ft2"><SPAN class="ft3">1.</SPAN><SPAN class="ft20">Unique Loan ID No.: CLL<?=$id?></SPAN></P>
<P class="p36 ft11">WHEREAS</P>
<P class="p37 ft27"><SPAN class="ft2">(A)</SPAN><SPAN class="ft26">The Borrower has requested the Lender to advance the Principal Amount (as defined in the TD Schedule) and the Lender, relying upon the representations and warranties made by the Borrower, has agreed to advance the Principal Amount to the Borrower, subject to the terms and conditions mentioned in this Agreement.</SPAN></P>
<div style="height:100px;overflow:hidden;"><img src="../user/uploads/<?=$loanf['signature']?>" style="height:100px; float:right;"></div>
</DIV>
<DIV id="id2_4">
<P class="p16 ft17">Page 2 of 12</P>
</DIV>
</DIV>
<DIV id="page_3">


<DIV id="id3_1">
<P class="p38 ft27"><SPAN class="ft2">(B)</SPAN><SPAN class="ft28">The Lender will transfer the Principal Amount, from which the Processing Fee is deducted from such Principal Amount in accordance with this Agreement, to the bank account of the Borrower, the details of which are captured in the TD Schedule. The Principal Amount along with the Interest Amount on or before Repayment Date accrued and GST, thereon and any applicable penalty charges after Repayment Date (as defined below) shall be immediately due and payable upon demand by the Lender at any time after Repayment Date, or earlier in accordance with the terms and conditions containedherein.</SPAN></P>
<P class="p39 ft6"><SPAN class="ft7">IN CONSIDERATION </SPAN>of the mutual covenants and agreements set forth herein, and for other good and valuable consideration, the receipt and sufficiency of which is acknowledged, the Parties, intending to be legally bound by this Agreement, hereby agree as follows:</P>
<P class="p40 ft11">NOW THIS AGREEMENT WITNESSETH AS BELOW:</P>
<P class="p41 ft1"><SPAN class="ft1">1.</SPAN><SPAN class="ft29">DEFINITIONS AND INTERPRETATION</SPAN></P>
<P class="p42 ft17"><SPAN class="ft1">1.1</SPAN><SPAN class="ft30">In this Agreement the following expressions shall, unless the context otherwise requires, have the following meanings:</SPAN></P>
<P class="p43 ft27"><SPAN class="ft2">(a)</SPAN><SPAN class="ft31">“ Applicable Law ” </SPAN>means any statute, law, enactment, regulation, ordinance, policy, treaty, rule, judgment, notification, directive, guideline, requirement, rule of common law, order, decree, bye- law, permits, licenses, approvals, consents, authorizations, government approvals, or any restriction or condition, or any similar form of decision of, or determination, application or execution by, or interpretation or pronouncement having the force of law of, any governmental authority having jurisdiction over the matter in question, whether in effect as of the Execution Date or thereafter;</P>
<P class="p44 ft2"><SPAN class="ft2">(b)</SPAN><SPAN class="ft32">“Expiry Date” </SPAN>shall have the meaning ascribed to it in Clause 2.2 of thisAgreement;</P>
<P class="p45 ft2"><SPAN class="ft2">(c)</SPAN><SPAN class="ft33">“Person” </SPAN>shall mean and include an individual, association of persons, corporation, trust, partnership, unincorporated body or government or subdivision thereof, or any otherentity;</P>
<P class="p46 ft27"><SPAN class="ft2">(d)</SPAN><SPAN class="ft34">“Repayment Date”</SPAN>shall mean each EMI date on which a Repayment shall be paid in the manner set out in the TD Schedule;</P>
<P class="p47 ft36"><SPAN class="ft2">(e)</SPAN><SPAN class="ft35">“Repayment Instalment” </SPAN>shall mean each structured EMI as specified in the Para 7 of the TD Schedule in the corresponding column. It is clarified that where there is only a single bullet repayment envisaged, the term shall refer to such single repayment, the corresponding provisions in this Agreement (including</P>
<P class="p48 ft2">‘Repayment Date’) shall be read accordingly;</P>
<P class="p49 ft2"><SPAN class="ft2">(f)</SPAN><SPAN class="ft37">“Term” </SPAN>shall have the meaning ascribed to it in Clause 2.2 of this Agreement;and</P>
<P class="p50 ft2"><SPAN class="ft2">(g)</SPAN><SPAN class="ft38">“Working Day” </SPAN>shall mean a day on which the Borrower’s and Lender’s bank allows money transfer via national electronic fund transfer (NEFT).</P>
<P class="p51 ft1">1.2 Interpretation</P>
<P class="p52 ft2">Unless the context of this Agreement otherwise requires:</P>
<P class="p53 ft2"><SPAN class="ft2">(a)</SPAN><SPAN class="ft39">words using the singular or plural number also include the plural or singular number,respectively;</SPAN></P>
<P class="p54 ft2"><SPAN class="ft2">(b)</SPAN><SPAN class="ft40">words of any gender are deemed to include all genders;</SPAN></P>
<div style="height:100px;overflow:hidden;"><img src="../user/uploads/<?=$loanf['signature']?>" style="height:100px; float:right;"></div>
</DIV>
<DIV id="id3_2">
<P class="p16 ft17">Page 3 of 12</P>
</DIV>
</DIV>
<DIV id="page_4">


<DIV id="id4_1">
<P class="p55 ft27"><SPAN class="ft2">(c)</SPAN><SPAN class="ft26">the terms “hereof”, “herein”, “hereby”, “hereto” and derivative or similar words refer to this entire Agreement or specified Clauses of this Agreement, as the case may be;</SPAN></P>
<P class="p56 ft2"><SPAN class="ft2">(d)</SPAN><SPAN class="ft41">the term “Clause” refers to the specified Clause of this Agreement;</SPAN></P>
<P class="p57 ft2"><SPAN class="ft2">(e)</SPAN><SPAN class="ft39">references to Recitals, Clauses, Annexures or Schedules are, unless the context otherwise requires, to Recitals, Clauses of, Annexures or Schedules to this Agreement.</SPAN></P>
<P class="p58 ft2"><SPAN class="ft2">(f)</SPAN><SPAN class="ft42">heading and bold typeface are only for convenience and shall be ignored for the purpose of interpretation.</SPAN></P>
<P class="p59 ft27"><SPAN class="ft2">(g)</SPAN><SPAN class="ft26">reference to any legislation or Applicable Law or to any provision thereof shall include references to any such Applicable Law as it may, after the date hereof, from time to time, be amended, supplemented or re- enacted, and any reference to statutory provision shall include any subordinate legislation made from time to time under that provision;</SPAN></P>
<P class="p60 ft27"><SPAN class="ft2">(h)</SPAN><SPAN class="ft26">time is of the essence in the performance of the Parties” respective obligations and if any time period specified herein is extended, such extended time shall also be of theessence;</SPAN></P>
<P class="p56 ft2"><SPAN class="ft2">(i)</SPAN><SPAN class="ft43">reference to the ”include” shall be construed without limitation.</SPAN></P>
<P class="p61 ft45"><SPAN class="ft1">2.</SPAN><SPAN class="ft44">TERM AND TERMINATION 2.1 Facility</SPAN></P>
<P class="p62 ft6">The Borrower hereby requests the Lender for the extension of the Principal Amount and the Lender hereby agrees to grant the Principal Amount to the Borrower in form of a loan in accordance with the terms and conditions of this Agreement.</P>
<P class="p63 ft1">2.2 Term</P>
<P class="p52 ft2">This Agreement shall become effective from the Execution Date, and shall, subject to the provisions of</P>
<P class="p64 ft2">Clause 2.3 (the “<SPAN class="ft1">survival provisions</SPAN>”), remain valid till 8<SPAN class="ft23">th </SPAN>Oct ,2021 (the "<SPAN class="ft1">Expiry Date</SPAN>") (“<SPAN class="ft1">Term</SPAN>”), unless</P>
<P class="p64 ft2">terminated in accordance with Clause 2.3 of this Agreement.</P>
<P class="p65 ft1">2.3 Termination</P>
<P class="p52 ft2">This Agreement may be terminated in the following manners:</P>
<P class="p66 ft2"><SPAN class="ft2">(a)</SPAN><SPAN class="ft39">The Lender may terminate this Agreement without providing any notice in the event of a Default under Clause 3 of this Agreement along with payment of applicable damages.</SPAN></P>
<P class="p67 ft2"><SPAN class="ft2">(b)</SPAN><SPAN class="ft42">This Agreement may be terminated at any time prior to the Term by mutual agreement of the Parties recorded in writing.</SPAN></P>
<P class="p68 ft27"><SPAN class="ft2">(c)</SPAN><SPAN class="ft26">The Agreement shall stand terminated on the date the Borrower has repaid the Amounts Payable (as defined below) in full, and has fulfilled all other obligations under the Agreement to the satisfaction of the Lender.</SPAN></P>
<P class="p69 ft46">In case of termination of this Agreement prior to the repayment of all outstanding amounts in this Agreement, the Borrower shall repay the entire outstanding Principal Amount along with all Interest Amount on or before EMI Date, GST and penalty charges after EMI Date, as applicable, any and all expenses for the enforcement and collection of any amounts due under the Agreement, and any other charges, dues and monies payable, costs and expenses reimbursable, as outstanding from time to time</P>
<P class="p70 ft2">and whether any of them are due or not as per the TD Schedule (“Amounts Payable”) to the Lender within</P>
<P class="p71 ft2">2 Working Days from such date of termination.</P>
<div style="height:100px;overflow:hidden;"><img src="../user/uploads/<?=$loanf['signature']?>" style="height:100px; float:right;"></div>
</DIV>
<DIV id="id4_2">
<P class="p16 ft17">Page 4 of 12</P>
</DIV>
</DIV>
<DIV id="page_5">


<DIV id="id5_1">
<P class="p72 ft6">The provisions of Clauses 1 (Definitions and Interpretation), 3 (Default), 5 (Representations and Warranties), 6 (Indemnities), 7 (Dispute Resolution), 9.2 (Governing Law and Jurisdiction) and 2.3 (Termination) of the Agreement; and point 3 of the TD Schedule, shall survive termination of this Agreement.</P>
<P class="p63 ft1">2.4 Interest Amount on or before EMI Date</P>
<P class="p73 ft6">The Borrower shall pay Interest Amount along with the Principal Amount on or before the EMI Date. The Borrower acknowledges and agrees to repay the full EMI amounts even if the Borrower wants to pre- close the loan as per the TD schedule.</P>
<P class="p74 ft1">2.5 Processing Fee</P>
<P class="p75 ft6">The Lender shall be entitled to receive the Processing Fee towards processing of the Principal Amount. The Borrower agrees and acknowledges that the Processing Fees shall be borne by the Borrower, and the Lender shall deduct such Processing Fee from the Principal Amount at the time of disbursal.</P>
<P class="p74 ft1"><SPAN class="ft1">3.</SPAN><SPAN class="ft29">DEFAULT</SPAN></P>
<P class="p76 ft17"><SPAN class="ft1">3.1</SPAN><SPAN class="ft30">The following events shall be construed as a default for the purpose of Clause 3 of this Agreement (“</SPAN><SPAN class="ft21">Events of Default</SPAN>”):</P>
<P class="p77 ft2"><SPAN class="ft2">(a)</SPAN><SPAN class="ft39">The Borrower is in violation of any Applicable Law which would have or likely to have an adverse effect on (i) the Borrower’s ability to perform all or any of its obligations under this Agreement;or</SPAN></P>
<P class="p78 ft2">(ii) on the business or financial or other conditions or operations of the Lender;</P>
<P class="p79 ft27"><SPAN class="ft2">(b)</SPAN><SPAN class="ft26">The Borrower does not pay any amount payable pursuant this Agreement in accordance with the terms of this Agreement on the due date of such amount;</SPAN></P>
<P class="p80 ft27"><SPAN class="ft2">(c)</SPAN><SPAN class="ft26">Any representation or statement made, or deemed to be made by the Borrower in this Agreement or any other document delivered by or on behalf of the Borrower under or in connection with the Agreement being incorrect or misleading when made or deemed to be made;</SPAN></P>
<P class="p81 ft27"><SPAN class="ft2">(d)</SPAN><SPAN class="ft26">The Borrower is in breach of, or has omitted to observe, or defaulted in any of his obligations, covenants, warranties, undertakings and liabilities under (i) this Agreement; or (ii) the terms and conditions agreed with creditlab.in </SPAN><SPAN class="ft47">(https://creditlab.in/terms.php</SPAN> ,</P>
<P class="p82 ft18">https://creditlab.in/privacy.php).</P>
<P class="p83 ft17"><SPAN class="ft1">3.2</SPAN><SPAN class="ft30">The occurrence of such Events of Default shall be considered to be a default under this Agreement ("</SPAN><SPAN class="ft21">Default</SPAN>"). The Lender shall communicate the Default status to the Borrower either by itself or through any third parties engaged by the Lender for this purpose (“<SPAN class="ft21">Default Notice</SPAN>”).</P>
<P class="p84 ft17"><SPAN class="ft1">3.3</SPAN><SPAN class="ft30">Consequence of an Event of Default Upon the occurrence of a Default, the Lender shall have the following rights:</SPAN></P>
<div style="height:100px;overflow:hidden;"><img src="../user/uploads/<?=$loanf['signature']?>" style="height:100px; float:right;"></div>
</DIV>
<DIV id="id5_2">
<P class="p16 ft17">Page 5 of 12</P>
</DIV>
</DIV>
<DIV id="page_6">


<DIV id="id6_1">
<P class="p85 ft49"><SPAN class="ft2">(a)</SPAN><SPAN class="ft48">to declare in terms of the Default Notice (or such other notice to the Borrower) all or a part of the Amounts Payable to become immediately due and payable, whereupon they shall become immediately due and payable for the purposes of this Agreement;</SPAN></P>
<P class="p86 ft2"><SPAN class="ft2">(b)</SPAN><SPAN class="ft42">initiate appropriate proceedings, whether arbitral, civil or criminal proceedings, against the Borrower;</SPAN></P>
<P class="p87 ft51"><SPAN class="ft2">(c)</SPAN><SPAN class="ft50">disclose the fact of such default by the Borrower to third parties via any medium (online or offline), including by way of SMS to the persons mentioned in the contact list of the Borrower (which information and right of access and dissemination the Borrower has consented to, hereby and otherwise), for necessary remedial steps; without prejudice to the generality of the foregoing. Notwithstanding anything contained in this Agreement, and subject to Applicable Law, the Borrower agrees that the Lender may, either directly or through any agents or employees, publish any information about the Borrower (other than any information that may be construed as sensitive personal data or information as defined under the Information Technology (Reasonable Security Practices and Procedures and Sensitive Personal Data or Information) Rules, 2011) on public websites, social networking websites, notice boards, or other public fora, and share such information with third parties, including without limitation debt collection agencies, credit rating agencies, loan providers, banks, </SPAN><NOBR>non-banking</NOBR> financial institutions, and any other third party that the Lender may in its sole discretion determine appropriate and necessary, and that the Borrower consents to the same and confirm that it does not have, and shall not raise at any time, any objection or action in relation to the Lender publishing or sharing the Borrower’s information in the manner described herein;</P>
<P class="p88 ft2"><SPAN class="ft2">(d)</SPAN><SPAN class="ft42">engage recovery agents or third party service providers for the purpose of </SPAN><NOBR>following-up</NOBR> with the Borrower for recovery of the Amounts Payable by the Borrower;</P>
<P class="p89 ft2"><SPAN class="ft2">(e)</SPAN><SPAN class="ft39">take any action against the Borrower with or without the intervention of the courts of law inIndia;</SPAN></P>
<P class="p90 ft27"><SPAN class="ft2">(f)</SPAN><SPAN class="ft26">exercise any general or special lien or right to </SPAN><NOBR>set-off</NOBR> to which the Lender is or may by law, equity or otherwise be entitled, or any rights or remedies available to the Lender; and</P>
<P class="p91 ft2"><SPAN class="ft2">(g)</SPAN><SPAN class="ft43">exercise such other rights as may be available to the Lender under Applicable Law.</SPAN></P>
<P class="p92 ft46">It is hereby clarified that in case of failure on part of the Borrower to pay the Amounts Payable in accordance with the terms of this Agreement, the Borrower hereby agrees that the Lender or its recovery agents (acting on behalf</P>
<P class="p93 ft2">of the Lender) may recover the amounts due in part or full from the Borrower’s bank accounts or the Borrower’s</P>
<P class="p94 ft6">accounts maintained with third party <NOBR>pre-paid</NOBR> instrument providers or payments banks including without limitation by reversal of any amounts credited to such payments bank accounts or <NOBR>electronic-wallets.</NOBR> The Borrower hereby agrees and consents that the Lender and/or his agents have the right to approach banks, payment banks or third party <NOBR>pre-paid</NOBR> instrument providers with whom the Borrower maintains an account, and instruct that such accounts be frozen and all payments be stopped until such time as the Amounts Payable under this Agreement is fully and duly paid.</P>
<P class="p95 ft6">The Borrower agrees that and creates a lien over all the assets of the Borrower till such time as the Amounts Payable are fully discharged in accordance with the terms herein, and specifically over (i) any credit balances in the bank account (as disclosed in the TD Schedule) and any other bank accounts maintained by the Borrower with a banking or financial institution or any other payment banks; and/or</P>
<P class="p96 ft6"><SPAN class="ft2">(ii)</SPAN><SPAN class="ft52">monies owed to the Borrower, at present or in the future, and/or (iii) any income, earnings of any nature, or gifts received by the Borrower. The Borrower hereby agrees that it shall not in any way, dispose off its assets without the prior written consent of the Lender.</SPAN></P>
<P class="p97 ft15">The rights, powers and remedies given to the Lender by this agreement shall be in addition to all rights, powers and remedies given to the Lender by virtue of any other security, statute or rule of law.</P>
<div style="height:100px;overflow:hidden;"><img src="../user/uploads/<?=$loanf['signature']?>" style="height:100px; float:right;"></div>
</DIV>
<DIV id="id6_2">
<P class="p16 ft17">Page 6 of 12</P>
</DIV>
</DIV>
<DIV id="page_7">


<DIV id="id7_1">
<P class="p98 ft6">The Borrower hereby consents and acknowledges that the Lender is entitled to make claims, in the manner set out in this clause, at any time, upon occurrence of a Default, and the Borrower acknowledges that the time period to recover monies due to the Lender under this Agreement shall stand renewed every time the Borrower comes in possession of any monies whether by way of income, any gifts or otherwise.</P>
<P class="p99 ft17"><SPAN class="ft1">3.4</SPAN><SPAN class="ft30">The remedies available to the Lender under this Agreement, at law, equity, custom, trade practice or otherwise are cumulative and not alternative and may be enforced concurrently or successively at the discretion of the Lender.</SPAN></P>
<P class="p51 ft1"><SPAN class="ft1">4.</SPAN><SPAN class="ft29">TRANSACTION:</SPAN></P>
<P class="p65 ft1">4.1 Disbursal</P>
<P class="p100 ft6">Lender shall advance the Principal Amount, less the Processing Fee deducted from the Principal Amount in accordance with this Agreement, either from its bank account(s) or via API services of the Lender’s designated bank account to either the Borrower’s bank account or to his designated e- wallet, as specified in the TD Schedule, within one Working Day from the Execution Date of this Agreement. The Lender shall assign a ‘Unique Loan ID’ in relation the transaction set out herein, as provided in the TD Schedule.</P>
<P class="p74 ft1">4.2 Prepayment</P>
<P class="p101 ft6">The Borrower may at any time or from time to time prepay the total of the outstanding Repayment EMIs. It is hereby acknowledged by the Parties that in case of such prepayment, there shall be no deducts or discounts that would be available to the Borrower (whether on any interest or GST or otherwise), and the Borrower shall make payment of all outstanding Repayment EMIs and any other Amounts Payable that is applicable at such time.</P>
<P class="p63 ft1">4.3 Repayment</P>
<P class="p102 ft46">The Borrower shall, on each Repayment Date pay the corresponding Repayment EMI directly into the bank accounts of the Lender. Unless otherwise intimated by the Lender, the Borrower shall repay such amounts to the bank accounts mentioned by the customer service of their platform.</P>
<P class="p103 ft46">In the event of payment of any Repayment EMI (or any other Amounts Payable, if applicable) beyond the relevant Repayment Date by the Borrower, the Borrower shall be liable to pay a lump sum additional interest amount equivalent to 0% of the unpaid principal amount. In addition, in the event of late payment of any Repayment EMI (or any other Amounts Payable, if applicable) by the Borrower, the Borrower shall be liable to pay an amount of Rs.50 per day as an additional interest on the corresponding Repayment EMI following the expiry of the relevant Repayment Date (“<SPAN class="ft53">Interest Amount After Repayment Date</SPAN>”) up to the date of actual payment; and the same shall be transferred to the Lender’s account along with the relevant Repayment EMI. It is hereby clarified that the Interest Amount After Repayment Date shall be due and payable for each delay in the payment of the Repayment EMI from its corresponding Repayment Date.</P>
<P class="p104 ft6">It is further clarified that the Borrower shall be deemed to have committed a ‘Default’ in terms of this Agreement upon delay in payment of any or all Repayment Instalment(s) on the corresponding Repayment Date(s). The payment of Late Payment Interest Amount shall not absolve the Borrower of the other obligations including to ensure timely payment of the next Repayment EMI and/or in respect of such default or affect any of the other rights of the Lender under this Agreement.</P>
<P class="p105 ft2">The Borrower explicitly agrees and acknowledges that the Interest Amount after repayment date is</P>
<div style="height:100px;overflow:hidden;"><img src="../user/uploads/<?=$loanf['signature']?>" style="height:100px; float:right;"></div>
</DIV>
<DIV id="id7_2">
<P class="p16 ft17">Page 7 of 12</P>
</DIV>
</DIV>
<DIV id="page_8">


<DIV id="id8_1">
<P class="p106 ft6">a fair estimate of the costs and losses that would be incurred by the Lender, and is not penal in nature; and further agrees that the provision of such Interest Amount after repayment date is without prejudice to the right of the Lender to claim that any <NOBR>non-payment</NOBR> is an even to of default.</P>
<P class="p107 ft56"><SPAN class="ft1">5.</SPAN><SPAN class="ft54">REPRESENTATIONS WARRANTIES AND COVENANTS 5.1 </SPAN><SPAN class="ft55">The Borrower hereby represent and warrant that</SPAN></P>
<P class="p108 ft27"><SPAN class="ft2">(a)</SPAN><SPAN class="ft28">No intellectual property rights, trade secret or other proprietary rights or rights of publicity or privacy rights of any Person is being infringed by entering into or providing any information required by this Agreement or by creditlab.in.</SPAN></P>
<P class="p109 ft2"><SPAN class="ft2">(b)</SPAN><SPAN class="ft42">It has full power and authority to enter into, execute and deliver this Agreement and to perform the obligations contemplated hereby.</SPAN></P>
<P class="p110 ft27"><SPAN class="ft2">(c)</SPAN><SPAN class="ft26">The execution and delivery of this Agreement and the performance of the obligations does not violate with any prior agreement, covenant or requirements of law or contract.</SPAN></P>
<P class="p111 ft27"><SPAN class="ft2">(d)</SPAN><SPAN class="ft26">The execution, delivery and performance of this Agreement and the consummation of the obligations contemplated hereby will not: (i) violate any order, judgment or decree against, or binding upon him/her or upon his/her respective assets, properties or businesses; or (ii) violate any Applicable Law.</SPAN></P>
<P class="p112 ft27"><SPAN class="ft2">(e)</SPAN><SPAN class="ft28">All the information provided by the Borrower in connection with this Agreement (including without limitation [examples of information, details provided to be elaborated]) and the Transaction are complete, true, accurate and current.</SPAN></P>
<P class="p113 ft2"><SPAN class="ft2">(f)</SPAN><SPAN class="ft42">The Borrower is at least 18 years of age and is competent to contract as per the Indian Contract Act, 1872.</SPAN></P>
<P class="p114 ft2"><SPAN class="ft2">(g)</SPAN><SPAN class="ft42">The Borrower is a citizen of India and a person resident in India for the purpose of India’s taxation and foreign exchange laws.</SPAN></P>
<P class="p115 ft2"><SPAN class="ft2">(h)</SPAN><SPAN class="ft42">No litigation, claim, dispute or proceeding is pending against the Borrower which would adversely affect this Agreement in any way.</SPAN></P>
<P class="p116 ft2"><SPAN class="ft2">(i)</SPAN><SPAN class="ft57">The Borrower has not entered into any agreement that would prevent it from fulfilling any of the obligations under this Agreement.</SPAN></P>
<P class="p51 ft2"><SPAN class="ft1">5.2</SPAN><SPAN class="ft58">The Lender represents that it is duly incorporated and validly existing under the laws of India.</SPAN></P>
<P class="p65 ft2"><SPAN class="ft1">5.3</SPAN><SPAN class="ft58">The Borrower covenants that:</SPAN></P>
<P class="p117 ft27"><SPAN class="ft2">(a)</SPAN><SPAN class="ft28">The Borrower shall not close their bank account (including an </SPAN><NOBR>e-wallet)</NOBR> without prior intimation to the Lender, and in case such an account is closed, the Borrower shall substitute the lien agreed herein to such other account, that is reasonably satisfactory to the Lender, as a condition precedent.</P>
<P class="p118 ft2"><SPAN class="ft2">(b)</SPAN><SPAN class="ft42">The Borrower shall only use bank accounts from banks (or such other financial institutions) or e- wallets regulated by RBI for making or receiving payments related to this Agreement.</SPAN></P>
<P class="p119 ft27"><SPAN class="ft2">(c)</SPAN><SPAN class="ft26">The Borrower confirms that it has taken appropriate advice and waives any defenses available to him / her under money lending, usury or other laws relating to the charging of interest.</SPAN></P>
<P class="p120 ft27"><SPAN class="ft2">(d)</SPAN><SPAN class="ft26">The Borrower has read all the terms and conditions, privacy policy, and other material available at the website of the Lender.</SPAN></P>
<P class="p121 ft1">6. INDEMNITY</P>
<div style="height:100px;overflow:hidden;"><img src="../user/uploads/<?=$loanf['signature']?>" style="height:100px; float:right;"></div>
</DIV>
<DIV id="id8_2">
<P class="p16 ft17">Page 8 of 12</P>
</DIV>
</DIV>
<DIV id="page_9">


<DIV id="id9_1">
<P class="p122 ft6">The Borrower shall indemnify, defend and hold the harmless the Lender, its directors, officials, employees, agents, contractors, customers and partners from and against all or any losses, liabilities, obligations, claims, demands, actions, suits, judgments, awards, fines, penalties, Taxes, fees, settlements and proceedings, expenses, deficiencies, damages (whether or not resulting from third party claims), charges, costs (including costs of investigation, recovery or other response actions), interests, processing fee, penalties, reasonable <NOBR>out-of-pocket</NOBR> expenses, reasonable attorneys’ and accountants’ fees and disbursements incurred by the Lender and/or his agents as a result of, arising from, or in connection with, or relating to (a) a Default; or (b) any fraud, gross negligence, willful misconduct attributable to the Borrower.</P>
<P class="p123 ft56"><SPAN class="ft1">7.</SPAN><SPAN class="ft54">DISPUTE RESOLUTION 7.1 Arbitration</SPAN></P>
<P class="p124 ft6">Any and all Dispute(s) arising under or pursuant to this Agreement shall be referred to binding arbitration before a sole arbitrator appointed solely by the Lender under the Arbitration and Conciliation Act, 1996 and the rules made there under, as amended and in force, from time to time.</P>
<P class="p125 ft2">It is expressly agreed between the Parties that:</P>
<P class="p126 ft2"><SPAN class="ft2">(a)</SPAN><SPAN class="ft59">The venue and seat of such arbitration shall be Narmada.</SPAN></P>
<P class="p54 ft2"><SPAN class="ft2">(b)</SPAN><SPAN class="ft60">The arbitration proceedings shall be conducted in the English language.</SPAN></P>
<P class="p54 ft2"><SPAN class="ft2">(c)</SPAN><SPAN class="ft59">The arbitration award shall be final and binding on the Parties.</SPAN></P>
<P class="p127 ft6">The Parties shall continue to fulfill their obligations under this Agreement pending the final resolution of the Dispute and the Parties shall not have the right to suspend their obligations under this Agreement by virtue of any dispute being referred to arbitration.</P>
<P class="p128 ft17"><SPAN class="ft1">7.2</SPAN><SPAN class="ft30">Nothing shall preclude a Party from seeking interim equitable or injunctive relief, or both, from any court having jurisdiction to grant the same. The pursuit of equitable or injunctive relief shall not be a waiver of the right of the Parties to pursue any remedy for monetary losses through the arbitration described in this Clause 7.2.</SPAN></P>
<P class="p129 ft17"><SPAN class="ft1">7.3</SPAN><SPAN class="ft30">The Parties hereby agree that all costs of the arbitration shall be borne by the losing Party in the arbitration.</SPAN></P>
<P class="p51 ft1"><SPAN class="ft1">8.</SPAN><SPAN class="ft29">COVENANTS</SPAN></P>
<P class="p65 ft1">8.1 Use of funds</P>
<P class="p102 ft6">The Borrower hereby covenants and undertakes that, during the entire term of the Agreement, the Principal Amount disbursed by the Lender to the Borrower in accordance with the terms of this Agreement, shall be used by the Borrower for lawful purposes.</P>
<P class="p74 ft1">8.2 creditlab.in Related Representations and Covenants:</P>
<P class="p52 ft2">The Borrower represents, warrants and covenants that:</P>
<P class="p130 ft27"><SPAN class="ft2">(a)</SPAN><SPAN class="ft28">He/she has read all the terms and conditions (https://creditlab.in/terms.php), privacy policy (https://creditlab.in/privacy.php), and other material available at the website of creditlab (https://creditlab.in/).</SPAN></P>
<P class="p131 ft3"><SPAN class="ft61">(b)</SPAN><SPAN class="ft62">The Borrower hereby unconditionally agrees to abide by the terms and conditions, privacy policy and other material contained on the website of the Lender and/or the creditlab.in and such terms and conditions, privacy policy and other material contained on the website of the Lender and/or the creditlab.in shall be incorporated herein by reference.</SPAN></P>
<div style="height:100px;overflow:hidden;"><img src="../user/uploads/<?=$loanf['signature']?>" style="height:100px; float:right;"></div>
</DIV>
<DIV id="id9_2">
<P class="p16 ft17">Page 9 of 12</P>
</DIV>
</DIV>
<DIV id="page_10">


<DIV id="id10_1">
<P class="p132 ft27"><SPAN class="ft2">(c)</SPAN><SPAN class="ft26">The information and financial details submitted by him/her on the creditlab.in website is true and correct. They have not provided any information which is incorrect or materially impairs the decision of creditlab.in to either register him / her or permits to lend him / her through the creditlab.in website.</SPAN></P>
<P class="p133 ft27"><SPAN class="ft2">(d)</SPAN><SPAN class="ft26">The Borrower acknowledges that creditlab.in website operates merely as a technology platform, which brings the Lender and the Borrower together and is otherwise not responsible or liable for any loss incurred by it under this Agreement. The Borrower hereby expressly waives any right to initiate an action, claim, demand or litigation against creditlab.in website in any circumstances whatsoever.</SPAN></P>
<P class="p134 ft2"><SPAN class="ft2">(e)</SPAN><SPAN class="ft63">creditlab.in is in no manner responsible towards either loss of money or breach of privacy or leakage of any confidential information, while it functions on the instructions of the Lender.</SPAN></P>
<P class="p115 ft27"><SPAN class="ft2">(f)</SPAN><SPAN class="ft64">They confirm that all types of communication and cash transactions between them (borrower and lender) will be/have been done online via an online platform provided by creditlab.in & their customer support.</SPAN></P>
<P class="p65 ft1"><SPAN class="ft1">8.3</SPAN><SPAN class="ft65">Other Covenants and Consents of the Borrower</SPAN></P>
<P class="p135 ft27"><SPAN class="ft2">(a)</SPAN><SPAN class="ft28">The Lender shall be entitled to outsource any and all of its functions to any third party, as it may think fit, including the right and authority to collect the outstanding on behalf of the Lender, the dues and unpaid instalments and other amounts due under the Agreement and to perform and execute all lawful acts, deeds and matters and things connected therewith and incidental thereto including sending notices to the Borrower to the extent prescribed under Applicable Laws. The Borrower hereby accepts and confirms that it has no objection to the Lender administering the Amounts Payable through third parties.</SPAN></P>
<P class="p136 ft27"><SPAN class="ft2">(b)</SPAN><SPAN class="ft26">The Lender shall at any time, without consent of the Borrower, be entitled to securitize, sell, assign, novate or transfer all or any of the Lender’s rights and obligations under this Agreement including any lien created on the bank accounts of the Borrower.</SPAN></P>
<P class="p121 ft1"><SPAN class="ft1">9.</SPAN><SPAN class="ft29">MISCELLANEOUS</SPAN></P>
<P class="p137 ft2"><SPAN class="ft1">9.1</SPAN><SPAN class="ft66">Notice: </SPAN>Unless otherwise stated herein, (i) the Lender may issue notices and other communications to the Borrower pursuant to this Agreement by SMS, email, Whatsapp, Facebook or other social media, personal delivery, or by prepaid registered mail addressed to the Borrower at the phone number, address or other coordinates of the Borrower specified in the TDSchedule;</P>
<P class="p138 ft36"><SPAN class="ft2">(ii)</SPAN><SPAN class="ft67">the Borrower may issue notices and other communications to the Lender pursuant to this Agreement by way of email to </SPAN><SPAN class="ft68">support@creditlab.in.</SPAN> Notices shall be deemed to be effective (a) when delivered, if personally delivered, (b) upon receipt in the recipient’s email account or upon receipt in the recipient’s</P>
<P class="p139 ft2">Whatsapp inbox or SMS account/service, when sent by email, Whatsapp or SMS; (c) three days after posting, if sent by registered mail.</P>
<P class="p140 ft27"><SPAN class="ft1">9.2</SPAN><SPAN class="ft69">Governing Law and Jurisdiction: </SPAN>This Agreement is governed by and shall be construed in accordance with the laws of India and subject to the dispute resolution provisions of this Agreement, the courts and tribunals at Narmada shall have exclusive jurisdiction with regard to any disputes arising in relation to this Agreement.</P>
<P class="p44 ft72"><SPAN class="ft70">9.3</SPAN><SPAN class="ft71">Waivers: </SPAN>No forbearance, delay, or inaction by any Party at any time, in exercising a right, power or remedy shall impair any</P>
<P class="p141 ft72">such right, power or remedy or operate as a waiver or acquiescence to a breach under this Agreement by the other Party. No waiver of right or acquiescence to <NOBR>non-compliance</NOBR> shall be effective or deemed made unless made in writing and duly executed by the Lender. Any such waiver or acquiescence shall be effective only in the specific instance and for the specific purpose for which it is given and may be subject to such conditions as the waiving or acquiescing Party may impose at its sole discretion. No such waiver or acquiescence in respect of a breach shall be construed as a waiver, acquiescence or consent to, any continuing or succeeding breach.</P>
<div style="height:100px;overflow:hidden;"><img src="../user/uploads/<?=$loanf['signature']?>" style="height:100px; float:right;"></div>
</DIV>
<DIV id="id10_2">
<P class="p16 ft17">Page 10 of 12</P>
</DIV>
</DIV>
<DIV id="page_11">


<DIV id="id11_1">
<P class="p142 ft27"><SPAN class="ft1">9.4</SPAN><SPAN class="ft69">Force Majeure: </SPAN>Neither Party shall be responsible for a delay in its performance under this Agreement, other than a delay in repayment of amounts due under this Agreement, if such delay is caused by natural catastrophes, war, riots, or acts of any governmental agencies.</P>
<P class="p143 ft2"><SPAN class="ft1">9.5</SPAN><SPAN class="ft66">No Assignment: </SPAN>The obligations of the Borrower under this Agreement shall not be assigned or transferred to any third party without the prior written consent of the Lender.</P>
<P class="p144 ft27"><SPAN class="ft1">9.6</SPAN><SPAN class="ft69">Entire Agreement: </SPAN>This Agreement shall constitute the entire Agreement and understanding between the Parties with respect to this Transaction. Any previous and future Agreement(s) related to other Transactions between the Lender and Borrower shall not be affected by the terms of this Agreement.</P>
<P class="p145 ft27"><SPAN class="ft1">9.7</SPAN><SPAN class="ft69">Partial Invalidity: </SPAN>In the event of one or more of the provisions of this Agreement being invalid, illegal or unenforceable in any respect, the validity, legality and enforceability of the remaining provisions shall not in any way be affected or impaired thereby.</P>
<P class="p146 ft2"><SPAN class="ft1">9.8</SPAN><SPAN class="ft66">Amendment: </SPAN>This Agreement shall not be modified except by an instrument or instruments in writing signed by each Party or an authorized representative of the Party, as the case may be.</P>
<P class="p147 ft27"><SPAN class="ft1">9.9</SPAN><SPAN class="ft69">Harmonious Interpretation: </SPAN>This Agreement must be interpreted and construed in harmony with the General terms of Use and the Terms of Registration that have been agreed to by both the Lender and the Borrower before the use of the creditlab.in website.</P>
<P class="p51 ft1"><SPAN class="ft1">9.10</SPAN><SPAN class="ft32">Costs and stamp duty expenses:</SPAN></P>
<P class="p148 ft27"><SPAN class="ft2">(a)</SPAN><SPAN class="ft28">The Borrower shall bear all costs and expenses incurred in relation to the completion of the Agreement, including stamp duty, costs incurred in relation to recovery of sums due, taxes incurred in relation to the enforcement, under this Agreement and all related agreements, writings and documents executed by and between the Parties hereto in respect of the Amounts Payable.</SPAN></P>
<P class="p149 ft27"><SPAN class="ft2">(b)</SPAN><SPAN class="ft26">Any claim, demands, actions, costs, expenses and liabilities incurred or suffered by the Lender by reason of </SPAN><NOBR>non-payment</NOBR> or insufficient payment of stamp duty on this Agreement and the documents and any other writings or documents which may be executed by the Borrower pursuant to or in relation to this Agreement, will be to the cost of the Borrower.</P>
<P class="p150 ft27"><SPAN class="ft2">(c)</SPAN><SPAN class="ft26">The Borrower hereby acknowledges that he/she is aware of the possibility of the costs and expenses incurred by that Lender, in connection with the enforcement of, or the preservation of any rights under this Agreement, exceeding the aggregate of Principal Amount, Interest Amount Before Repayment Date and Interest Amount After Repayment Date, if any, payable to the Lender. The Borrower further acknowledges that such sums are reasonable given the risk involved in granting unsecured loans to borrowers with lower credit worthiness and the ticket size of the loans that are being provided.</SPAN></P>
<P class="p151 ft2"><SPAN class="ft1">9.11</SPAN><SPAN class="ft32">Information rights: </SPAN>The Borrower hereby agrees and gives consent for the disclosures by the Lender of</P>
<P class="p152 ft27">all or any such: (i) information and data in relation to it; (ii) the information or data relating to any credit facility availed of / to be availed by it; (iii) default if any committed by it in discharge of our such obligation, as the Lender may deem appropriate and necessary to disclose and furnish to third party agencies and any other agency authorized in this behalf by RBI; and (iv) the Borrower declares that the information and data furnished by it to the Lender are true and correct in all respects.</P>
<P class="p153 ft27"><SPAN class="ft1">9.12</SPAN><SPAN class="ft73">Execution in counterparts: </SPAN>This Agreement may be executed in any number of counterparts and all of which taken together shall constitute one and the same instrument. The Parties may enter into this Agreement by signing any such counterpart.</P>
<P class="p154 ft3"><SPAN class="ft74">9.13</SPAN><SPAN class="ft75">Further assurance: </SPAN>Each of the Parties hereto shall cooperate with the other and execute and deliver to the other such instruments and document and take such other actions as may be reasonably requested from time to time in order to carry out evidence and confirm their rights and the intended propose of this Agreement.</P>
<div style="height:100px;overflow:hidden;"><img src="../user/uploads/<?=$loanf['signature']?>" style="height:100px; float:right;"></div>
</DIV>
<DIV id="id11_2">
<P class="p16 ft17">Page 11 of 12</P>
</DIV>
</DIV>
<DIV id="page_12">


<DIV id="id12_1">
<P class="p155 ft15">I/We agree that this Agreement is being executed in an electronic or digital form by way of a click wrap or digital signature for execution of this Agreement. I/We hereby agree and undertake that:</P>
<P class="p156 ft27"><SPAN class="ft2">(i)</SPAN><SPAN class="ft26">notwithstanding the absence of physical signatures of the parties on this Agreement or any of the notice / intimation / offer / acceptance in connection with this Agreement, the Agreement or any of the other documents executed by me/us in electronic or digital form shall be legally valid, binding and enforceable against me/us;</SPAN></P>
<P class="p157 ft27"><SPAN class="ft2">(ii)</SPAN><SPAN class="ft26">it shall not raise any objection or claims in relation to validity or enforceability of this Agreement or any of the other documents solely on account of this Agreement or any of the other documents having been executed in an electronic form or digital form (signature)by way of a click wrap agreement; and</SPAN></P>
<P class="p158 ft27"><SPAN class="ft2">(iii)</SPAN><SPAN class="ft26">it shall not raise any objection or claim in relation to process, method, storage, or means of authentication of execution of this Agreement or any of the documents in connection with this Agreement.</SPAN></P>
<div style="height:100px;overflow:hidden;"><img src="../user/uploads/<?=$loanf['signature']?>" style="height:100px; float:right;"></div>
</DIV>
<DIV id="id12_2">
<P class="p16 ft17">Page 12 of 12</P>
</DIV>
</DIV>
<?php $aadharfile = explode("#",$loanf['personaldocument']);?>
<div style="height:100%;overflow:hidden;"><img src="../user/uploads/<?=$aadharfile[0]?>" style="width:100%;height:100%;"></div>
<div style="height:100%;overflow:hidden;"><img src="../user/uploads/<?=$aadharfile[1]?>" style="width:100%;height:100%;"></div>
<div style="height:100%;overflow:hidden;"><img src="../user/uploads/<?=$loanf['conpanydocument']?>" style="width:100%;height:100%;"></div>
</body>
</html>