<?php
session_start();
if(file_exists('../includes/init.php')){
    require_once('../includes/init.php');
    require_once('../library/class.php');    
    if($Connection[0]['DBMS']=='pg'){
        include_once('../library/function_jqgrid.php');  
    }   
    else{
        include_once('../library/function_jqgrid_nonpg.php');  
    }
}
else{
    require_once('includes/init.php');
    require_once('library/class.php');
    if($Connection[0]['DBMS']=='pg'){
        include_once('library/function_jqgrid.php');  
    }   
    else{
        include_once('library/function_jqgrid_nonpg.php');  
    } 
}

$MyDataBase=new MyDataBase;
$Conn=$Connection[0];
if(isset($_GET['page'])){
    $page = $_GET['page']; // get the requested page
}
else{
    $page=1;
}

if(isset($_GET['rows'])){
    $limit = $_GET['rows']; // get how many rows we want to have into the grid
}
else{
    $limit=20;
}

if(isset($_GET['sidx'])){
    //$sidx = $_GET['sidx']; // get index row - i.e. user click to sort
    $sidx = str_replace('-','.',$_GET['sidx']); // get index row - i.e. user click to sort
}
else{
    $sidx =1;
}

if(isset($_GET['sord'])){
    $sord = $_GET['sord']; // get the direction
}
else{
    $sord='';
}
if(!$sidx) $sidx =1;
/*
$db = mysql_connect('localhost', 'BoTaXs', 'BoTaXs')
or die("Connection Error: " . mysql_error());

mysql_select_db('griddemo') or die("Error conecting to db.");
*/
$wh = "";
$searchOn = Strip($_REQUEST['_search']);
if($searchOn=='true') {
	$searchstr = Strip($_REQUEST['filters']);
	//$wh= constructWhere($searchstr);
	$jsona = json_decode($searchstr,true);
	$wh =  " AND ".getStringForGroup($jsona);
	//var_dump($wh);
	//echo $ss;
}
if($_SESSION[$AppName]['Organisasi']==0){
    $_SESSION[$AppName]['Organisasi']='';
}
$SqlResult="SELECT COUNT(*) AS count
        FROM acc_budget a
        LEFT JOIN r_coa c ON a.r_coa_id = c.r_coa_id
        LEFT JOIN r_organisasi o ON a.r_organisasi_id = o.r_organisasi_id
        LEFT JOIN r_activity v ON a.r_activity_id = v.r_activity_id
        WHERE a.acc_budget_id IS NOT NULL ".$wh;
if(isset($_SESSION[$AppName]['Organisasi']) && $_SESSION[$AppName]['Organisasi']!=''){
    $SqlResult.="AND a.r_organisasi_id=".$_SESSION[$AppName]['Organisasi'];
}
$count=$MyDataBase->GetLastId($Conn,$SqlResult,'count');

if( $count >0 ) {
	$total_pages = ceil($count/$limit);
} else {
	$total_pages = 0;
}
if ($page > $total_pages) $page=$total_pages;
$start = $limit*$page - $limit; // do not put $limit*($page - 1
if($start<0){
    $start=0;
}
if($_SESSION[$AppName]['Organisasi']==0){
    $_SESSION[$AppName]['Organisasi']='';
}
$SQL = "SELECT
        a.acc_budget_id,
        c.kdcoa,
        a.r_coa_id,
        a.tahun,
        (budget1+budget2+budget3+budget4+budget5+budget6+budget7+budget8+budget9+budget10+budget11+budget12) AS nilai,
        c.nmcoa,
        o.nmorganisasi,
        v.nmactivity
        FROM acc_budget a
        LEFT JOIN r_coa c ON a.r_coa_id = c.r_coa_id
        LEFT JOIN r_organisasi o ON a.r_organisasi_id = o.r_organisasi_id
        LEFT JOIN r_activity v ON a.r_activity_id = v.r_activity_id
        WHERE a.acc_budget_id IS NOT NULL
        ".$wh."";
if(isset($_SESSION[$AppName]['Organisasi']) && $_SESSION[$AppName]['Organisasi']!=''){
  $SQL.= "AND a.r_organisasi_id=".$_SESSION[$AppName]['Organisasi'];
}
$SQL.= "ORDER BY $sidx $sord
        LIMIT $limit OFFSET $start";
$row=$MyDataBase->GetData($Conn,$SQL);
/*
$responce->page = $page;
$responce->total = $total_pages;
$responce->records = $count;
*/
$output=array(
    'page'=>$page,
    'total'=>$total_pages,
    'records'=>$count,
    'rows'=>array()
);

$rows=array();
$no=$start;
$Path=$MyDataBase->GetDetailMenu($Conn,$_GET['PageCode']);
foreach($row['acc_budget_id'] AS $x=>$value){
    $no++;
    $Kode=str_replace(' ','+',$row['acc_budget_id'][$x]);
    switch($_GET['R']){
        case 0 : $Read='<center><span class="ui-icon ui-icon-minus"></span></center>'; break;
        default : $Read='<center><a href="javascript:ReloadPage(\''.$Path['Id'].'\',\''.$Path['Path'].'&Action=View&Kode='.$Kode.'\')"><span class="ui-icon ui-icon-search"></span></a></center>'; break;
    }
    
    switch($_GET['U']){
        case 0 : $Update='<center><span class="ui-icon ui-icon-minus"></span></center>'; break;
        default : $Update='<center><a href="javascript:ReloadPage(\''.$Path['Id'].'\',\''.$Path['Path'].'&Action=Edit&Kode='.$Kode.'\')"><span class="ui-icon ui-icon-pencil"></span></a></center>'; break;
    }
    
    switch($_GET['D']){
        case 0 : $Delete='<center><span class="ui-icon ui-icon-minus"></span></center>'; break;
        default : $Delete='<center><a href="javascript:Delete(\''.$Kode.'\',\'Hapus\',\''.$Path['Kode'].'\',\'proses/acc_budget.php\')"><span class="ui-icon ui-icon-close"></span></a></center>'; break;
    }
    
    $rows[]=array(
        'cell'=>array(
            $no,
            $row['nmorganisasi'][$x],
            $row['nmactivity'][$x],
            $row['kdcoa'][$x],
            $row['nmcoa'][$x],
            $row['tahun'][$x],
            $row['nilai'][$x],
            $Read,
            $Update,
            $Delete
            )
    );
}        
$output['rows']=$rows;
echo json_encode($output);
?>